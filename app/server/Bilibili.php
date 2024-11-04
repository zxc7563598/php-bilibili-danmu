<?php

namespace app\server;

use Carbon\Carbon;
use Workerman\Worker;
use Hejunjie\Bililive;
use Workerman\Timer;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Ws;
use Hejunjie\Tools;

class Bilibili
{
    private int $reconnectInterval = 5; // 重连间隔时间（秒）
    private string $cookie;
    private int $roomId;

    public function __construct()
    {
        // 设置cookie和房间ID（可替换成配置项或参数传入）
        $this->cookie = 'SESSDATA=9b1247f4%2C1745588739%2C353a6%2Aa2CjDL5uIImsyB8VCUz2WBVbXhge8btcLj4q69VJogabjBJ5yWEhYfjU5qi2qN-gx38ZMSVlgxbEE0cFBQclhEZ3hlZ0M5TjBoUFYzai1MSXZYRG5CNm9hU0dRWTJzS2liSzl6RXFOcHJjenhRaWcwRkxJZllRMVVLbWJwSjhUeVYtc1BURnZTRll3IIEC;Path=/;Domain=bilibili.com;Expires=Fri, 25 Apr 2025 13:45:39 GMT;bili_jct=86205ff9b1081aa5e7395b0e711637b7;DedeUserID=4325051;DedeUserID__ckMd5=07816212060cbb4a;sid=8jzkqehf;buvid3=6D2B9B6A-8874-F002-90ED-55B9AFAF922339941infoc;buvid4=29F96FC2-498F-CAF4-97C8-1F4472E8C81F39941-024102713-+h273seQjZCLewAJjT6dK/Bt562wtyjJV4Sboe9zi5WMxJn/SmU+l3DQFv+tm7ma;b_nut=1730036739;refresh_token=414bc7c1e77a60fa436e9e32eaf596a2';
        $this->roomId = 22384516;
    }

    public function onWorkerStart(Worker $worker)
    {
        // 创建 Unix 套接字监听
        $socketFile = runtime_path() . '/bilibili.sock'; // 套接字文件路径，确保有权限访问
        if (file_exists($socketFile)) {
            unlink($socketFile); // 如果文件已存在，先删除以避免冲突
        }
        $unixWorker = new Worker("unix://$socketFile");
        $unixWorker->onMessage = function ($connection, $data) {
            if ($data === 'reload') {
                // 执行重载逻辑
                echo '重新启动啦';
                // 添加具体的重载逻辑
            }
            $connection->send("Bilibili process handled: $data");
        };

        // 需要启动这个 Worker
        $unixWorker->listen();
        $this->connectToWebSocket();
    }

    /**
     * 连接到 WebSocket 服务器
     */
    private function connectToWebSocket()
    {
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

    /**
     * 设置 WebSocket 连接的参数和回调
     * 
     * @param AsyncTcpConnection $con
     * @param int $roomId
     * @param string $token
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
        };

        // 设置消息接收回调
        $con->onMessage = function (AsyncTcpConnection $con, $data) {
            $this->onMessageReceived($data);
        };

        // 设置连接关闭回调
        $con->onClose = function () {
            echo "Connection closed, attempting to reconnect...\n";
            $this->scheduleReconnect();
        };

        // 设置连接错误回调
        $con->onError = function ($connection, $code, $msg) {
            echo "Error: $msg (code: $code), attempting to reconnect...\n";
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
     * @param AsyncTcpConnection $con
     * @param int $roomId
     * @param string $token
     */
    private function onConnected(AsyncTcpConnection $con, int $roomId, string $token)
    {
        echo "已连接到WebSocket,房间号:" . $roomId . "\n";
        // 发送认证包
        // $con->send(Bililive\WebSocket::buildAuthPayload($roomId, $token, $this->cookie));

        // 设置 websocket 心跳包发送定时器，每30秒发送一次
        Timer::add(30, function () use ($con) {
            if ($con->getStatus() === AsyncTcpConnection::STATUS_ESTABLISHED) {
                $con->send(Bililive\WebSocket::buildHeartbeatPayload());
            }
        });

        // 设置 http 心跳包发送定时器，每60秒发送一次
        Timer::add(60, function () use ($con, $roomId) {
            if ($con->getStatus() === AsyncTcpConnection::STATUS_ESTABLISHED) {
                $con->send(Bililive\Live::reportLiveHeartbeat($roomId, $this->cookie));
            }
        });
    }

    /**
     * 接收 WebSocket 消息时的处理
     *
     * @param mixed $data
     */
    private function onMessageReceived($data)
    {
        // 解析消息内容
        $message = Bililive\WebSocket::parseResponsePayload($data);
        foreach ($message['payload'] as $payload) {
            if (isset($payload['payload']['cmd'])) {
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
        }
    }

    /**
     * 设置重连的定时任务
     */
    private function scheduleReconnect()
    {
        Timer::add($this->reconnectInterval, function () {
            $this->connectToWebSocket();
            // $url = 'http://127.0.0.1:' . getenv('LISTEN') . '/reload-bilibili';
            // $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
            // Tools\HttpClient::sendPostRequest($url, [], [
            //     'api_key' => md5(getenv('SECURE_API_KEY') . $timestamp),
            //     'timestamp' => $timestamp
            // ]);
        }, [], false);
    }
}
