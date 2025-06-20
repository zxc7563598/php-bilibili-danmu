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

class Bilibili
{
    private string|null $cookie; // 用户cookie
    private int|null $roomId; // 直播间房间号
    private ?int $heartbeatTimer = null; // 心跳
    private ?int $reconnectTimer = null; // 重连
    private ?int $sendMessageTimer = null; // 消息
    // 重连配置
    private int $initialReconnectDelay = 1; // 初始延迟（秒）
    private int $maxReconnectDelay = 100;    // 最大延迟（秒）
    private float $backoffMultiplier = 2.0; // 退避乘数
    private int $maxReconnectAttempts = 10;  // 最大重连次数
    private int $reconnectAttempts = 0;     // 当前重连次数
    private array $reconnectAttemptsMessage = []; // 重连信息，用于邮件发送

    public function onWorkerStart()
    {
        $this->startUnixWorker();
        $this->connectToWebSocket();
    }

    /**
     * 启动 Unix Worker
     * 
     * @return void 
     */
    private function startUnixWorker()
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
    private function connectToWebSocket()
    {
        $this->cookie = RobotServices::getCookie();
        $this->roomId = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        if ($this->cookie && $this->roomId) {
            // 获取真实房间号和WebSocket连接信息
            try {
                $realRoomId = Bililive\Live::getRealRoomId($this->roomId, $this->cookie);
                $wsData = Bililive\Live::getInitialWebSocketUrl($realRoomId, $this->cookie);
                $wsUrl = 'ws://' . $wsData['host'] . ':' . $wsData['wss_port'] . '/sub';
                $token = $wsData['token'];
                // 创建 WebSocket 连接
                $con = new AsyncTcpConnection($wsUrl);
                $this->setupConnection($con, $realRoomId, $token);
                $con->connect();
            } catch (\Exception $e) {
                // 重试
                $this->scheduleReconnect();
            }
        }
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
    private function setupConnection(AsyncTcpConnection $con, int $roomId, string $token)
    {
        // 设置 SSL 和自定义 HTTP 头
        $con->transport = 'ssl';
        $con->headers = $this->buildHeaders();

        // 设置WebSocket为二进制类型
        $con->websocketType = Ws::BINARY_TYPE_ARRAYBUFFER;

        // 设置连接成功回调
        $con->onConnect = function (AsyncTcpConnection $con) use ($roomId, $token) {
            $this->onConnected($con, $roomId, $token);
            $this->reconnectAttempts = 0; // 连接成功后重置重连计数器
        };

        // 设置消息接收回调
        $con->onMessage = function (AsyncTcpConnection $con, $data) {
            $this->onMessageReceived($data);
        };

        // 设置连接关闭回调
        $con->onClose = function () {
            echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "连接已关闭，正在尝试重新连接...\n";
            $this->clearTimers();
            // 设置重连定时器
            $this->scheduleReconnect();
        };

        // 设置连接错误回调
        $con->onError = function ($connection, $code, $msg) {
            echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "Error: $msg (code: $code), 尝试重新连接\n";
            $this->clearTimers();
            // 设置重连定时器
            $this->scheduleReconnect();
        };
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
    private function onConnected(AsyncTcpConnection $con, int $roomId, string $token)
    {
        echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "已连接到WebSocket,房间号:" . $roomId . "\n";
        // 发送认证包
        $con->send(Bililive\WebSocket::buildAuthPayload($roomId, $token, $this->cookie));
        echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "认证包发送" . "\n";
        $con->send(Bililive\WebSocket::buildHeartbeatPayload());
        echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "首次websocket心跳发送" . "\n";
        $this->heartbeatTimer = Timer::add(30, function () use ($con, $roomId) {
            if ($con->getStatus() === AsyncTcpConnection::STATUS_ESTABLISHED) {
                $con->send(Bililive\WebSocket::buildHeartbeatPayload());
                echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "连续websocket心跳发送" . "\n";
            }
        });
        $this->sendMessageTimer = Timer::add(5, function () {
            SendMessage::processQueue();
        });
        if ($this->reconnectTimer !== null) {
            Timer::del($this->reconnectTimer);
            $this->reconnectTimer = null;
        }
    }

    /**
     * 记录原始日志
     * 
     * @param mixed $payload 记录数据
     * 
     * @return void 
     */
    private function analysis($payload)
    {
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
    }

    /**
     * 接收 WebSocket 消息时的处理
     * @param mixed $data 消息信息
     * 
     * @return void 
     */
    private function onMessageReceived($data)
    {
        // 解析消息内容
        $message = Bililive\WebSocket::parseResponsePayload($data);
        foreach ($message['payload'] as $payload) {
            if (isset($payload['payload']['cmd'])) {
                // 记录分析日志
                $this->analysis($payload);
                // 处理逻辑
                switch ($payload['payload']['cmd']) {
                    case 'LIVE': // 直播开始
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
                        break;
                    case 'CUT_OFF': // 直播被超管切断
                    case 'ROOM_LOCK': // 直播间被封
                    case 'PREPARING': // 下播
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
                        break;
                    case 'SEND_GIFT': // 赠送礼物
                        Present::processing(
                            $payload['payload']['data']['uid'],
                            $payload['payload']['data']['uname'],
                            $payload['payload']['data']['giftId'],
                            $payload['payload']['data']['giftName'],
                            intval($payload['payload']['data']['price'] / 100),
                            $payload['payload']['data']['num'],
                            $payload['payload']['data']['receiver_uinfo']['uid'],
                            isset($payload['payload']['data']['sender_uinfo']['medal']['ruid']) ? $payload['payload']['data']['sender_uinfo']['medal']['ruid'] : null,
                            isset($payload['payload']['data']['sender_uinfo']['medal']['guard_level']) ? $payload['payload']['data']['sender_uinfo']['medal']['guard_level'] : null,
                            isset($payload['payload']['data']['sender_uinfo']['medal']['level']) ? $payload['payload']['data']['sender_uinfo']['medal']['level'] : null
                        );
                        // 记录信息
                        if (Redis::get('bilibili_live_key')) {
                            $filePath = base_path() . '/runtime/lives/直播礼物记录/' . Redis::get('bilibili_live_key') . '.log';
                            $line = json_encode([
                                'uid' => $payload['payload']['data']['uid'],
                                'uname' => $payload['payload']['data']['uname'],
                                'gift_id' => $payload['payload']['data']['giftId'],
                                'gift_name' => $payload['payload']['data']['giftName'],
                                'price' => intval($payload['payload']['data']['price'] / 100),
                                'num' => $payload['payload']['data']['num'],
                                'time' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp
                            ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
                            writeLinesToFile($filePath, $line);
                        }
                        break;
                    case 'GUARD_BUY': // 开通大航海
                        Present::processing(
                            $payload['payload']['data']['uid'],
                            $payload['payload']['data']['username'],
                            $payload['payload']['data']['gift_id'],
                            $payload['payload']['data']['gift_name'],
                            ($payload['payload']['data']['price'] / 100),
                            $payload['payload']['data']['num'],
                            0,
                            0,
                            $payload['payload']['data']['guard_level'],
                            0
                        );
                        // 上舰
                        $uid = $payload['payload']['data']['uid'];
                        $name = $payload['payload']['data']['username'];
                        $guard_level = $payload['payload']['data']['guard_level'];
                        $amount = intval($payload['payload']['data']['price'] / 10);
                        $payment_at = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
                        $live_key = Redis::get('bilibili_live_key') ? Redis::get('bilibili_live_key') : null;
                        UserPublicMethods::userOpensVip($uid, $name, $guard_level, $amount, $payment_at, $live_key);
                        // 记录信息
                        if (Redis::get('bilibili_live_key')) {
                            $filePath = base_path() . '/runtime/lives/直播礼物记录/' . Redis::get('bilibili_live_key') . '.log';
                            $line = json_encode([
                                'uid' => $payload['payload']['data']['uid'],
                                'uname' => $payload['payload']['data']['username'],
                                'gift_id' => $payload['payload']['data']['gift_id'],
                                'gift_name' => $payload['payload']['data']['gift_name'],
                                'price' => intval($payload['payload']['data']['price'] / 100),
                                'num' => $payload['payload']['data']['num'],
                                'time' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp
                            ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
                            writeLinesToFile($filePath, $line);
                        }
                        break;
                    case 'INTERACT_WORD': // 直播间互动
                        switch (intval($payload['payload']['data']['msg_type'])) {
                            case 1: // 进入直播间
                                Enter::processing(
                                    $payload['payload']['data']['uid'],
                                    $payload['payload']['data']['uname'],
                                    isset($payload['payload']['data']['uinfo']['medal']['ruid']) ? $payload['payload']['data']['uinfo']['medal']['ruid'] : null,
                                    isset($payload['payload']['data']['uinfo']['medal']['guard_level']) ? $payload['payload']['data']['uinfo']['medal']['guard_level'] : null
                                );
                                break;
                            case 2: // 关注
                                Follow::processing(
                                    $payload['payload']['data']['uid'],
                                    $payload['payload']['data']['uname'],
                                    isset($payload['payload']['data']['uinfo']['medal']['ruid']) ? $payload['payload']['data']['uinfo']['medal']['ruid'] : null,
                                    isset($payload['payload']['data']['uinfo']['medal']['guard_level']) ? $payload['payload']['data']['uinfo']['medal']['guard_level'] : null
                                );
                                break;
                            case 3: // 分享直播间
                                Share::processing(
                                    $payload['payload']['data']['uid'],
                                    $payload['payload']['data']['uname'],
                                    isset($payload['payload']['data']['uinfo']['medal']['ruid']) ? $payload['payload']['data']['uinfo']['medal']['ruid'] : null,
                                    isset($payload['payload']['data']['uinfo']['medal']['guard_level']) ? $payload['payload']['data']['uinfo']['medal']['guard_level'] : null
                                );
                                break;
                        }
                        break;
                    case 'DANMU_MSG': // 弹幕信息
                        CheckIn::processing(
                            $payload['payload']['info'][2][0],
                            $payload['payload']['info'][2][1],
                            isset($payload['payload']['info'][3][12]) ? $payload['payload']['info'][3][12] : null,
                            isset($payload['payload']['info'][3][10]) ? $payload['payload']['info'][3][10] : null,
                            $payload['payload']['info'][1]
                        );
                        Autoresponders::processing(
                            $payload['payload']['info'][1],
                            $payload['payload']['info'][2][0],
                            $payload['payload']['info'][2][1],
                            isset($payload['payload']['info'][3][12]) ? $payload['payload']['info'][3][12] : null,
                            isset($payload['payload']['info'][3][10]) ? $payload['payload']['info'][3][10] : null
                        );
                        // 记录信息
                        Client::send('receive-message', [
                            'live_key' => Redis::get('bilibili_live_key') ?? null,
                            'uid' => $payload['payload']['info'][2][0],
                            'uname' => $payload['payload']['info'][2][1],
                            'msg' => $payload['payload']['info'][1],
                            'badge_uid' => isset($payload['payload']['info'][3][12]) ? $payload['payload']['info'][3][12] : null,
                            'badge_uname' => isset($payload['payload']['info'][3][2]) ? $payload['payload']['info'][3][2] : null,
                            'badge_room_id' => isset($payload['payload']['info'][3][3]) ? $payload['payload']['info'][3][3] : null,
                            'badge_name' => isset($payload['payload']['info'][3][1]) ? $payload['payload']['info'][3][1] : null,
                            'badge_level' => isset($payload['payload']['info'][3][0]) ? $payload['payload']['info'][3][0] : null,
                            'badge_type' => isset($payload['payload']['info'][3][10]) ? $payload['payload']['info'][3][10] : null,
                            'time' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp,
                        ]);
                        break;
                    case 'PK_BATTLE_PRE_NEW': // PK马上开始
                        PkLiveReport::processing($payload['payload']['data']['uid'], $payload['payload']['data']['uname'], $payload['payload']['data']['room_id']);
                        break;
                }
            }
        }
    }

    /**
     * 清空定时器
     * 
     * @return void 
     */
    public function clearTimers()
    {
        if ($this->heartbeatTimer !== null) {
            Timer::del($this->heartbeatTimer);
            $this->heartbeatTimer = null;
        }
        if ($this->sendMessageTimer !== null) {
            Timer::del($this->sendMessageTimer);
            $this->sendMessageTimer = null;
        }
    }

    /**
     * 设置重连的定时任务
     * 
     * @return void 
     */
    public function scheduleReconnect()
    {
        if ($this->reconnectTimer !== null) {
            Timer::del($this->reconnectTimer);
            $this->reconnectTimer = null;
        }
        $this->reconnectAttempts++;
        // 检查是否超过最大重连次数
        if ($this->reconnectAttempts >= $this->maxReconnectAttempts) {
            $room_id = $this->roomId;
            echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "已达到最大重连次数，不再尝试连接。\n";
            $this->cleanupResources();
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
            // 重置最大重试信息
            $this->reconnectAttempts = 0;     // 当前重连次数
            $this->reconnectAttemptsMessage = []; // 重连信息，用于邮件发送
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
            $this->connectToWebSocket();
        }, [], false);
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
    private function cleanupResources()
    {
        Utils\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
        Utils\FileUtils::fileDelete(runtime_path() . '/tmp/uid.cfg');
        Utils\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
        $this->cookie = null;
        $this->roomId = null;
    }
}
