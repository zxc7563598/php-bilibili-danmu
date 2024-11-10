<?php

namespace process;

use app\queue\SendMessage;
use app\server\Autoresponders;
use app\server\Enter;
use app\server\Follow;
use app\server\Present;
use app\server\Share;
use Carbon\Carbon;
use Exception;
use Workerman\Worker;
use Hejunjie\Bililive;
use Workerman\Timer;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Ws;
use Hejunjie\Tools;
use Random\RandomException;
use support\Redis;

class Bilibili
{
    private int $reconnectInterval = 5; // 重连间隔时间（秒）
    private int $maxReconnectAttempts = 5; // 最大重连次数
    private int $reconnectAttempts = 0; // 当前重连次数
    private string|null $cookie; // 用户cookie
    private int|null $roomId; // 直播间房间号
    private ?int $heartbeatTimer = null; // 心跳
    private ?int $sendMessageTimer = null; // 消息

    public function onWorkerStart()
    {
        $this->startUnixWorker();
        $this->connectToWebSocket();
    }

    /**
     * 启动 Unix Worker
     * 
     * @return void 
     * @throws Exception 
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
            }
            $connection->send("已处理Bilibili流程: $data");
        };
        $unixWorker->listen();
    }

    /**
     * 连接到 WebSocket 服务器
     * 
     * @return void 
     * @throws Exception 
     * @throws RandomException 
     */
    private function connectToWebSocket()
    {
        $this->cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        $this->roomId = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        if ($this->cookie && $this->roomId) {
            // 获取真实房间号和WebSocket连接信息
            $realRoomId = Bililive\Live::getRealRoomId($this->roomId, $this->cookie);
            $wsData = Bililive\Live::getInitialWebSocketUrl($realRoomId, $this->cookie);
            $wsUrl = 'ws://' . $wsData['host'] . ':' . $wsData['wss_port'] . '/sub';
            $token = $wsData['token'];
            // 创建 WebSocket 连接
            $con = new AsyncTcpConnection($wsUrl);
            $this->setupConnection($con, $realRoomId, $token);
            $con->connect();
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
     * @throws RandomException 
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
            echo "连接已关闭，正在尝试重新连接...\n";
            $this->clearTimers();
            $this->scheduleReconnect();
        };

        // 设置连接错误回调
        $con->onError = function ($connection, $code, $msg) {
            echo "Error: $msg (code: $code), 尝试重新连接\n";
            $this->clearTimers();
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
        echo "已连接到WebSocket,房间号:" . $roomId . "\n";
        // 发送认证包
        $con->send(Bililive\WebSocket::buildAuthPayload($roomId, $token, $this->cookie));
        $this->heartbeatTimer = Timer::add(30, function () use ($con, $roomId) {
            if ($con->getStatus() === AsyncTcpConnection::STATUS_ESTABLISHED) {
                $con->send(Bililive\WebSocket::buildHeartbeatPayload());
                // 每隔两次发送一次HTTP心跳包
                if (Carbon::now()->second < 30) {
                    $con->send(Bililive\Live::reportLiveHeartbeat($roomId, $this->cookie));
                }
            }
        });
        $this->sendMessageTimer =  Timer::add(3, function () {
            SendMessage::processQueue();
        });
    }

    private function analysis($payload)
    {
        $dir = base_path() . '/runtime/logs/直播间信息记录/';
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
     * @throws Exception 
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
                        break;
                    case 'CUT_OFF': // 直播被超管切断
                    case 'ROOM_LOCK': // 直播间被封
                    case 'PREPARING': // 下播
                        Redis::del('bilibili_live_key');
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
                            $payload['payload']['data']['sender_uinfo']['medal']['ruid'],
                            $payload['payload']['data']['sender_uinfo']['medal']['guard_level'],
                            $payload['payload']['data']['sender_uinfo']['medal']['level']
                        );
                        break;
                    case 'GUARD_BUY': // 开通大航海
                        Present::processing(
                            $payload['payload']['data']['uid'],
                            $payload['payload']['data']['username'],
                            $payload['payload']['data']['gift_id'],
                            $payload['payload']['data']['gift_name'],
                            $payload['payload']['data']['price'],
                            $payload['payload']['data']['num'],
                            0,
                            0,
                            $payload['payload']['data']['guard_level'],
                            0
                        );
                        break;
                    case 'INTERACT_WORD': // 直播间互动
                        switch (intval($payload['payload']['data']['msg_type'])) {
                            case 1: // 进入直播间
                                Enter::processing(
                                    $payload['payload']['data']['uid'],
                                    $payload['payload']['data']['uname'],
                                    $payload['payload']['data']['uinfo']['medal']['ruid'],
                                    $payload['payload']['data']['uinfo']['medal']['guard_level']
                                );
                                break;
                            case 2: // 关注
                                Follow::processing(
                                    $payload['payload']['data']['uid'],
                                    $payload['payload']['data']['uname'],
                                    $payload['payload']['data']['uinfo']['medal']['ruid'],
                                    $payload['payload']['data']['uinfo']['medal']['guard_level']
                                );
                                break;
                            case 3: // 分享直播间
                                Share::processing(
                                    $payload['payload']['data']['uid'],
                                    $payload['payload']['data']['uname'],
                                    $payload['payload']['data']['uinfo']['medal']['ruid'],
                                    $payload['payload']['data']['uinfo']['medal']['guard_level']
                                );
                                break;
                        }
                        break;
                    case 'DANMU_MSG': // 弹幕信息
                        Autoresponders::processing(
                            $payload['payload']['info'][1],
                            $payload['payload']['info'][2][0],
                            $payload['payload']['info'][2][1],
                            $payload['payload']['info'][3][12],
                            $payload['payload']['info'][3][10]
                        );
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
    private function scheduleReconnect()
    {
        $this->reconnectAttempts++; // 增加重连次数计数器
        // 检查是否超过最大重连次数
        if ($this->reconnectAttempts >= $this->maxReconnectAttempts) {
            echo "已达到最大重连次数，不再尝试连接。\n";
            Tools\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
            Tools\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
            $this->cookie = null;
            $this->roomId = null;
            return;
        }
        // 设置重连定时器
        Timer::add($this->reconnectInterval, function () {
            $this->connectToWebSocket();
        }, [], false);
    }
}
