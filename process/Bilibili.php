<?php

namespace process;

use app\core\RobotServices;
use app\core\UserPublicMethods;
use app\model\Lives;
use app\model\ShopConfig;
use app\queue\SendMessage;
use app\server\Autoresponders;
use app\server\CheckIn;
use app\server\Enter;
use app\server\Follow;
use app\server\PkLiveReport;
use app\server\Present;
use app\server\Share;
use Carbon\Carbon;
use Workerman\Worker;
use Hejunjie\Bililive;
use Workerman\Timer;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Ws;
use Hejunjie\Utils;
use support\Redis;
use Webman\RedisQueue\Client;
use app\Protobuf\InteractWordV2;

class Bilibili
{
    private string|null $cookie; // 用户cookie
    private int|null $roomId; // 直播间房间号
    private ?int $heartbeatTimer = null; // 心跳
    private ?int $reconnectTimer = null; // 重连
    private ?int $sendMessageTimer = null; // 消息
    private ?int $healthCheckTimer = null; // 健康检查
    private ?AsyncTcpConnection $connection = null; // 连接对象引用

    // 重连配置
    private int $initialReconnectDelay = 1; // 初始延迟（秒）
    private int $maxReconnectDelay = 100;    // 最大延迟（秒）
    private float $backoffMultiplier = 2.0; // 退避乘数
    private int $maxReconnectAttempts = 10;  // 最大重连次数
    private int $reconnectAttempts = 0;     // 当前重连次数
    private array $reconnectAttemptsMessage = []; // 重连信息，用于邮件发送

    // 连接状态
    private bool $isConnecting = false;
    private bool $isConnected = false;

    /**
     * 启动 Unix Worker
     * 
     * @return void 
     */
    public function onWorkerStart(): void
    {
        $this->startUnixWorker();
        $this->connectToWebSocket();
    }

    /**
     * 启动 Unix Worker
     * 
     * @return void 
     */
    private function startUnixWorker(): void
    {
        $socketFile = runtime_path() . '/bilibili.sock';
        if (file_exists($socketFile)) {
            unlink($socketFile);
        }
        $unixWorker = new Worker("unix://$socketFile");
        $unixWorker->onMessage = function ($connection, $data) {
            if ($data === 'reload') {
                // 启动 websocket
                $this->connectToWebSocket();
                echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "已重启Bilibili进程" . "\n";
            }
            $connection->send("已处理Bilibili流程: $data");
        };
        $unixWorker->listen();
    }

    /**
     * 连接到 WebSocket 服务器
     * 
     * @return void 
     */
    private function connectToWebSocket(): void
    {
        $this->clearTimers();
        $this->cookie = RobotServices::getCookie();
        $this->roomId = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        // 如果cookie或roomId无效，则不再尝试连接websocket
        if (empty($this->cookie) || empty($this->roomId)) {
            echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "未检测到有效的cookie或roomId，WebSocket连接已跳过。\n";
            $this->isConnecting = false;
            $this->isConnected = false;
            return;
        }
        // 防止重复连接
        if ($this->isConnecting || $this->isConnected) {
            return;
        }
        $this->isConnecting = true;
        try {
            // 获取真实房间号和WebSocket连接信息
            $realRoomId = Bililive\Live::getRealRoomId($this->roomId, $this->cookie);
            $wsData = Bililive\Live::getInitialWebSocketUrl($realRoomId, $this->cookie);
            $wsUrl = 'ws://' . $wsData['host'] . ':' . $wsData['wss_port'] . '/sub';
            $token = $wsData['token'];
            // 创建 WebSocket 连接
            $this->connection = new AsyncTcpConnection($wsUrl);
            $this->setupConnection($this->connection, $realRoomId, $token);
            $this->connection->connect();
            $this->startHealthCheck();
        } catch (\Exception $e) {
            sublog('Websocket异常/连接失败', $e->getMessage(), [
                'exception' => $e->getTrace(),
                'room_id' => $this->roomId ?? 'unknown'
            ]);
            $this->isConnecting = false;
            $this->scheduleReconnect();
        }
    }

    /**
     * 检查连接是否健康
     * 
     * @return bool
     */
    private function isConnectionHealthy(): bool
    {
        return $this->connection && $this->connection->getStatus() === AsyncTcpConnection::STATUS_ESTABLISHED && $this->isConnected;
    }

    /**
     * 设置 WebSocket 连接的参数和回调
     * 
     * @param AsyncTcpConnection $con 连接信息
     * @param int $roomId 用户cookie
     * @param string $token 直播间认证密钥
     * 
     * @return void 
     */
    private function setupConnection(AsyncTcpConnection $con, int $roomId, string $token): void
    {
        // 设置 SSL 和自定义 HTTP 头
        $con->transport = 'ssl';
        $con->headers = $this->buildHeaders();
        // 设置WebSocket为二进制类型
        $con->websocketType = Ws::BINARY_TYPE_ARRAYBUFFER;
        // 设置连接成功回调
        $con->onConnect = function (AsyncTcpConnection $con) use ($roomId, $token) {
            $this->isConnecting = false;
            $this->isConnected = true;
            $this->onConnected($con, $roomId, $token);
            $this->reconnectAttempts = 0; // 连接成功后重置重连计数器
        };
        // 设置消息接收回调
        $con->onMessage = function (AsyncTcpConnection $con, $data) {
            if ($this->validateMessage($data)) {
                $this->onMessageReceived($data);
            }
        };
        // 设置连接关闭回调
        $con->onClose = function () {
            $this->isConnected = false;
            echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "连接已关闭，正在尝试重新连接...\n";
            $this->clearTimers();
            // 设置重连定时器
            $this->scheduleReconnect();
        };
        // 设置连接错误回调
        $con->onError = function ($connection, $code, $msg) {
            $this->isConnected = false;
            $errorMsg = sprintf("WebSocket连接错误: %s (code: %d), 尝试重新连接", $msg, $code);
            sublog('Websocket异常/连接错误', $errorMsg, [
                'code' => $code,
                'msg' => $msg,
                'room_id' => $this->roomId ?? 'unknown'
            ]);
            echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . " $errorMsg\n";
            $this->clearTimers();
            // 设置重连定时器
            $this->scheduleReconnect();
        };
    }

    /**
     * 验证消息格式
     * 
     * @param mixed $data
     * @return bool
     */
    private function validateMessage($data): bool
    {
        if (empty($data) || !is_string($data)) {
            sublog('Websocket异常/无效消息格式', "收到无效消息格式: " . gettype($data), [
                'data' => $data,
                'room_id' => $this->roomId ?? 'unknown'
            ]);
            return false;
        }
        return true;
    }

    /**
     * 构建 WebSocket 请求的自定义 HTTP 头
     * 
     * @return array 
     */
    private function buildHeaders(): array
    {
        return [
            "User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
            "Origin" => "https://live.bilibili.com",
            "Connection" => "Upgrade",
            "Pragma" => "no-cache",
            "Cache-Control" => "no-cache",
            "Upgrade" => "websocket",
            "Sec-WebSocket-Version" => "13",
            "Accept-Encoding" => "gzip, deflate, br, zstd",
            "Accept-Language" => "zh-CN,zh;q=0.9",
            'Sec-WebSocket-Key' => base64_encode(random_bytes(16)),
            "Sec-WebSocket-Extensions" => "permessage-deflate; client_max_window_bits",
            'Cookie' => $this->cookie
        ];
    }

    /**
     * WebSocket连接成功时的处理
     * 
     * @param AsyncTcpConnection $con 连接信息
     * @param int $roomId 用户cookie
     * @param string $token 直播间认证密钥
     * 
     * @return void 
     */
    private function onConnected(AsyncTcpConnection $con, int $roomId, string $token): void
    {
        echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "已连接到WebSocket,房间号:" . $roomId . "\n";
        // 发送认证包
        $con->send(Bililive\WebSocket::buildAuthPayload($roomId, $token, $this->cookie));
        echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "认证包发送" . "\n";
        $con->send(Bililive\WebSocket::buildHeartbeatPayload());
        echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "首次websocket心跳发送" . "\n";
        // 启动心跳定时器
        $this->heartbeatTimer = Timer::add(30, function () use ($roomId) {
            if ($this->isConnectionHealthy()) {
                $this->connection->send(Bililive\WebSocket::buildHeartbeatPayload());
                echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "连续websocket心跳发送" . "\n";
            }
        });
        // 启动消息处理定时器
        $this->sendMessageTimer = Timer::add(5, function () {
            SendMessage::processQueue();
        });
        // 清理重连定时器
        if ($this->reconnectTimer !== null) {
            Timer::del($this->reconnectTimer);
            $this->reconnectTimer = null;
        }
    }

    /**
     * 启动健康检查
     * 
     * @return void
     */
    private function startHealthCheck(): void
    {
        $this->healthCheckTimer = Timer::add(60, function () {
            if (!$this->isConnectionHealthy() && !$this->isConnecting) {
                echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "健康检查失败，尝试重连...\n";
                $this->scheduleReconnect();
            }
        });
    }

    /**
     * 记录原始日志
     * 
     * @param mixed $payload 记录数据
     * 
     * @return void 
     */
    private function analysis($payload): void
    {
        try {
            $dir = base_path() . '/runtime/logs/' . Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d') . '/直播间信息记录/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            // 获取当前文件编号
            $baseFileName = $dir . $payload['payload']['cmd'];
            $fileExtension = ".log";
            $currentFile = $baseFileName . $fileExtension;
            // 检查当前文件是否已存在且行数达到1000
            if (file_exists($currentFile) && count(file($currentFile)) >= 1000) {
                // 找到下一个可用的文件编号
                $i = 1;
                do {
                    $newFile = $baseFileName . "_" . $i . $fileExtension;
                    $i++;
                } while (file_exists($newFile) && count(file($newFile)) >= 1000);

                $currentFile = $newFile;
            }
            $content = json_encode($payload['payload'], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION) . "\n";
            file_put_contents($currentFile, $content, FILE_APPEND);
        } catch (\Exception $e) {
            sublog('Websocket异常/记录日志失败', $e->getMessage(), [
                'payload' => $payload,
                'room_id' => $this->roomId ?? 'unknown'
            ]);
        }
    }

    /**
     * 接收 WebSocket 消息时的处理
     * @param mixed $data 消息信息
     * 
     * @return void 
     */
    private function onMessageReceived($data): void
    {
        try {
            // 解析消息内容
            $message = Bililive\WebSocket::parseResponsePayload($data);
            foreach ($message['payload'] as $payload) {
                if (isset($payload['payload']['cmd'])) {
                    // 记录分析日志
                    $this->analysis($payload);
                    // 处理逻辑
                    $this->processMessage($payload);
                }
            }
        } catch (\Exception $e) {
            sublog('Websocket异常/处理消息失败', $e->getMessage(), [
                'room_id' => $this->roomId ?? 'unknown'
            ]);
        }
    }

    /**
     * 处理不同类型的消息
     * 
     * @param array $payload
     * @return void
     */
    private function processMessage($payload): void
    {
        $cmd = $payload['payload']['cmd'];
        try {
            switch ($cmd) {
                case 'LIVE': // 直播开始
                    $this->handleLiveStart($payload);
                    break;
                case 'CUT_OFF': // 直播被超管切断
                case 'ROOM_LOCK': // 直播间被封
                case 'PREPARING': // 下播
                    $this->handleLiveEnd($payload);
                    break;
                case 'SEND_GIFT': // 赠送礼物
                    $this->handleGift($payload);
                    break;
                case 'GUARD_BUY': // 开通大航海
                    $this->handleGuardBuy($payload);
                    break;
                case 'INTERACT_WORD_V2': // 直播间互动
                    $this->handleInteractWord($payload);
                    break;
                case 'DANMU_MSG': // 弹幕信息
                    $this->handleDanmu($payload);
                    break;
                case 'PK_BATTLE_PRE_NEW': // PK马上开始
                    $this->handlePkBattle($payload);
                    break;
            }
        } catch (\Exception $e) {
            sublog('Websocket异常/处理消息类型失败', $e->getMessage(), [
                'cmd' => $cmd,
                'room_id' => $this->roomId ?? 'unknown'
            ]);
        }
    }

    /**
     * 处理直播开始
     * 
     * @param array $payload
     * @return void
     */
    private function handleLiveStart($payload): void
    {
        Redis::set('bilibili_live_key', $payload['payload']['live_key']);
        Redis::set('bilibili_live_create', Carbon::now()->timezone(config('app')['default_timezone'])->timestamp);
        Redis::del('bilibili_send_sequence');
        // 增加记录
        $lives = Lives::where('live_key', $payload['payload']['live_key'])->first();
        if (empty($lives)) {
            $lives = new Lives();
            $lives->live_key = $payload['payload']['live_key'];
            $lives->save();
        }
    }

    /**
     * 处理直播结束
     * 
     * @param array $payload
     * @return void
     */
    private function handleLiveEnd($payload): void
    {
        // 记录下播
        if (Redis::get('bilibili_live_key')) {
            $lives = Lives::where('live_key', Redis::get('bilibili_live_key'))->first();
            if (empty($lives)) {
                $lives = new Lives();
                $lives->live_key = Redis::get('bilibili_live_key');
                $lives->save();
            }
            $lives->danmu_num = 0;
            $lives->gift_num = 0;
            $lives->end_time = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
            $lives->save();
            // 发送下播邮件
            UserPublicMethods::aggregateMail($lives->live_id);
        }
        // 清空下播信息
        Redis::del('bilibili_live_key');
        Redis::del('bilibili_live_create');
        Redis::del('bilibili_send_sequence');
    }

    /**
     * 处理礼物
     * 
     * @param array $payload
     * @return void
     */
    private function handleGift($payload): void
    {
        $data = $payload['payload']['data'];
        Present::processing(
            $data['uid'],
            $data['uname'],
            $data['giftId'],
            $data['giftName'],
            intval($data['price'] / 100),
            $data['num'],
            $data['receiver_uinfo']['uid'],
            isset($data['sender_uinfo']['medal']['ruid']) ? $data['sender_uinfo']['medal']['ruid'] : null,
            isset($data['sender_uinfo']['medal']['guard_level']) ? $data['sender_uinfo']['medal']['guard_level'] : null,
            isset($data['sender_uinfo']['medal']['level']) ? $data['sender_uinfo']['medal']['level'] : null,
            $data['blind_gift'] ?? null,
            'gift'
        );
        // 记录信息
        $this->recordGiftInfo($data);
    }

    /**
     * 处理大航海开通
     * 
     * @param array $payload
     * @return void
     */
    private function handleGuardBuy($payload): void
    {
        $data = $payload['payload']['data'];
        Present::processing(
            $data['uid'],
            $data['username'],
            $data['gift_id'],
            $data['gift_name'],
            ($data['price'] / 100),
            $data['num'],
            0,
            0,
            $data['guard_level'],
            0,
            null,
            'vip'
        );
        // 上舰
        $uid = $data['uid'];
        $name = $data['username'];
        $guard_level = $data['guard_level'];
        $amount = intval($data['price'] / 10);
        $payment_at = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
        $live_key = Redis::get('bilibili_live_key') ? Redis::get('bilibili_live_key') : null;
        UserPublicMethods::userOpensVip($uid, $name, $guard_level, $amount, $payment_at, $live_key);
        // 记录信息
        $this->recordGiftInfo($data);
    }

    /**
     * 处理互动消息
     * 
     * @param array $payload
     * @return void
     */
    private function handleInteractWord($payload): void
    {
        try {
            $pbBinary = base64_decode($payload['payload']['data']['pb']);
            $interact = new InteractWordV2\InteractWordV2();
            $interact->mergeFromString($pbBinary);
            switch (intval($interact->getMsgType())) {
                case 1: // 进入直播间
                    Enter::processing(
                        $interact->getUid(),
                        $interact->getUname(),
                        ($interact->getUinfo()?->getMedal()?->getRuid()) ? $interact->getUinfo()?->getMedal()?->getRuid() : null,
                        ($interact->getUinfo()?->getMedal()?->getGuardLevel()) ? $interact->getUinfo()?->getMedal()?->getGuardLevel() : null
                    );
                    break;
                case 2: // 关注
                    Follow::processing(
                        $interact->getUid(),
                        $interact->getUname(),
                        ($interact->getUinfo()?->getMedal()?->getRuid()) ? $interact->getUinfo()?->getMedal()?->getRuid() : null,
                        ($interact->getUinfo()?->getMedal()?->getGuardLevel()) ? $interact->getUinfo()?->getMedal()?->getGuardLevel() : null
                    );
                    break;
                case 3: // 分享直播间
                    Share::processing(
                        $interact->getUid(),
                        $interact->getUname(),
                        ($interact->getUinfo()?->getMedal()?->getRuid()) ? $interact->getUinfo()?->getMedal()?->getRuid() : null,
                        ($interact->getUinfo()?->getMedal()?->getGuardLevel()) ? $interact->getUinfo()?->getMedal()?->getGuardLevel() : null
                    );
                    break;
            }
        } catch (\Exception $e) {
            sublog('Websocket异常/处理互动消息失败', $e->getMessage(), [
                'room_id' => $this->roomId ?? 'unknown'
            ]);
        }
    }

    /**
     * 处理弹幕消息
     * 
     * @param array $payload
     * @return void
     */
    private function handleDanmu($payload): void
    {
        $info = $payload['payload']['info'];
        CheckIn::processing(
            $info[2][0],
            $info[2][1],
            isset($info[3][12]) ? $info[3][12] : null,
            isset($info[3][10]) ? $info[3][10] : null,
            $info[1]
        );
        Autoresponders::processing(
            $info[1],
            $info[2][0],
            $info[2][1],
            isset($info[3][12]) ? $info[3][12] : null,
            isset($info[3][10]) ? $info[3][10] : null
        );
        // 记录信息
        Client::send('receive-message', [
            'live_key' => Redis::get('bilibili_live_key') ?? null,
            'uid' => $info[2][0],
            'uname' => $info[2][1],
            'msg' => $info[1],
            'badge_uid' => isset($info[3][12]) ? $info[3][12] : null,
            'badge_uname' => isset($info[3][2]) ? $info[3][2] : null,
            'badge_room_id' => isset($info[3][3]) ? $info[3][3] : null,
            'badge_name' => isset($info[3][1]) ? $info[3][1] : null,
            'badge_level' => isset($info[3][0]) ? $info[3][0] : null,
            'badge_type' => isset($info[3][10]) ? $info[3][10] : null,
            'time' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp,
        ]);
    }

    /**
     * 处理PK对战
     * 
     * @param array $payload
     * @return void
     */
    private function handlePkBattle($payload): void
    {
        $data = $payload['payload']['data'];
        PkLiveReport::processing($data['uid'], $data['uname'], $data['room_id']);
    }

    /**
     * 记录礼物信息
     * 
     * @param array $data 数据
     * 
     * @return void
     */
    private function recordGiftInfo($data): void
    {
        if (Redis::get('bilibili_live_key')) {
            try {
                $filePath = base_path() . '/runtime/lives/直播礼物记录/' . Redis::get('bilibili_live_key') . '.log';
                $line = json_encode([
                    'uid' => $data['uid'],
                    'uname' => $data['uname'] ?? $data['username'],
                    'gift_id' => $data['giftId'] ?? $data['gift_id'],
                    'gift_name' => $data['giftName'] ?? $data['gift_name'],
                    'price' => intval(($data['price'] ?? 0) / 100),
                    'num' => $data['num'],
                    'time' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp
                ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
                writeLinesToFile($filePath, $line);
            } catch (\Exception $e) {
                sublog('Websocket异常/记录礼物信息失败', $e->getMessage(), [
                    'room_id' => $this->roomId ?? 'unknown'
                ]);
            }
        }
    }

    /**
     * 清空定时器
     * 
     * @return void 
     */
    public function clearTimers(): void
    {
        $timers = [
            $this->heartbeatTimer,
            $this->sendMessageTimer,
            $this->reconnectTimer,
            $this->healthCheckTimer
        ];
        foreach ($timers as $timer) {
            if ($timer !== null) {
                Timer::del($timer);
            }
        }
        $this->heartbeatTimer = null;
        $this->sendMessageTimer = null;
        $this->reconnectTimer = null;
        $this->healthCheckTimer = null;
    }

    /**
     * 设置重连的定时任务
     * 
     * @return void 
     */
    public function scheduleReconnect(): void
    {
        $this->clearTimers();
        $this->cookie = RobotServices::getCookie();
        $this->roomId = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        // 如果cookie或roomId无效，则不再尝试连接websocket
        if (empty($this->cookie) || empty($this->roomId)) {
            echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "未检测到有效的cookie或roomId，WebSocket连接已跳过。\n";
            $this->isConnecting = false;
            $this->isConnected = false;
            return;
        }
        if ($this->reconnectTimer !== null) {
            Timer::del($this->reconnectTimer);
            $this->reconnectTimer = null;
        }
        $this->reconnectAttempts++;
        // 检查是否超过最大重连次数
        if ($this->reconnectAttempts >= $this->maxReconnectAttempts) {
            $room_id = $this->roomId;
            echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "已达到最大重连次数，不再尝试连接。\n";
            $this->sendDisconnectNotification($room_id);
            $this->cleanupResources();
            return;
        }
        // 计算指数退避延迟（含随机抖动）
        $delay = $this->calculateExponentialDelay();
        echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "第 {$this->reconnectAttempts} 次重试，将在 {$delay} 秒后尝试...\n";
        $this->reconnectAttemptsMessage[] = [
            'reconnect_attempts' => $this->reconnectAttempts,
            'delay' => round($delay, 2),
            'time' => Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
        ];
        // 设置延迟重连定时器
        $this->reconnectTimer = Timer::add($delay, function () {
            if (!$this->isConnectionHealthy() && !$this->isConnecting) {
                $this->connectToWebSocket();
            }
        }, [], false);
    }

    /**
     * 发送断开连接通知
     * 
     * @param int $room_id
     * @return void
     */
    private function sendDisconnectNotification($room_id): void
    {
        try {
            // 获取配置信息
            $config = ShopConfig::whereIn('title', [
                'enable-disconnect-mail',
                'email-address',
                'address-as'
            ])->get([
                'title' => 'title',
                'content' => 'content'
            ]);
            $shop_config = [];
            foreach ($config as $_config) {
                $shop_config[$_config->title] = $_config->content;
            }
            if (!empty($shop_config['enable-disconnect-mail']) && $shop_config['enable-disconnect-mail']) {
                if (!empty($shop_config['email-address']) && !empty($shop_config['address-as'])) {
                    // 发送邮件
                    Utils\HttpClient::sendPostRequest('https://tools.api.hejunjie.life/bilibilidanmu-api/live-disconnect-email', [
                        'Content-Type: application/json'
                    ], json_encode([
                        'mail' => $shop_config['email-address'],
                        'name' => $shop_config['address-as'],
                        'room_id' => $room_id,
                        'error_queue' => $this->reconnectAttemptsMessage
                    ]));
                }
            }
        } catch (\Exception $e) {
            sublog('Websocket异常/发送断开连接通知失败', $e->getMessage(), [
                'room_id' => $room_id,
                'error_queue' => $this->reconnectAttemptsMessage
            ]);
        }
        // 重置重试信息
        $this->reconnectAttempts = 0;
        $this->reconnectAttemptsMessage = [];
    }

    /**
     * 计算指数退避延迟时间（含随机抖动）
     * 
     * @return float 
     */
    private function calculateExponentialDelay(): float
    {
        // 基础延迟 = initialDelay * (backoffMultiplier ^ retryCount)
        $exponentialDelay = $this->initialReconnectDelay * pow($this->backoffMultiplier, $this->reconnectAttempts - 1);
        // 限制最大延迟
        $clampedDelay = min($exponentialDelay, $this->maxReconnectDelay);
        // 添加随机抖动
        $jitter = $clampedDelay * 0.1 * mt_rand(0, 10); // 10% 范围内的随机抖动
        return $clampedDelay + $jitter;
    }

    /**
     * 清理资源（如配置文件等）
     * 
     * @return void 
     */
    private function cleanupResources(): void
    {
        $this->clearTimers();
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
        // 清理配置文件
        $files = ['cookie.cfg', 'uid.cfg', 'connect.cfg'];
        foreach ($files as $file) {
            $path = runtime_path() . '/tmp/' . $file;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $this->cookie = null;
        $this->roomId = null;
        $this->isConnecting = false;
        $this->isConnected = false;
    }
}
