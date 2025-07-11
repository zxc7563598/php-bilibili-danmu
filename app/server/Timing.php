<?php

namespace app\server;

use app\core\RobotServices;
use app\queue\SendMessage;
use Carbon\Carbon;
use Workerman\Timer;
use Workerman\Worker;
use support\Redis;

/**
 * 定时广告，优先级10
 */
class Timing
{
    public function onWorkerStart()
    {
        $this->startUnixWorker();
        $this->startUp();
    }

    /**
     * 启动 Unix Worker
     * 
     * @return void 
     * @throws Exception 
     */
    private function startUnixWorker()
    {
        $socketFile = runtime_path() . '/timing.sock';
        if (file_exists($socketFile)) {
            unlink($socketFile);
        }
        $unixWorker = new Worker("unix://$socketFile");
        $unixWorker->onMessage = function ($connection, $data) {
            if ($data === 'reload') {
                $this->startUp();
                echo Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s') . "已重启定时广告进程" . "\n";
            }
            $connection->send("已重启定时广告进程: $data");
        };
        $unixWorker->listen();
    }

    /**
     * 开启定时任务
     * 
     * @return void 
     */
    private function startUp()
    {
        // 获取定时广告配置
        $timing = readFileContent(runtime_path() . '/tmp/timing.cfg');
        if ($timing) {
            $timing = json_decode($timing, true);
        }
        // 开启定时广告，载入定时
        if (isset($timing['opens']) && $timing['opens']) {
            $intervals = $timing['intervals']; // 间隔时间
            $status = intval($timing['status']); // 状态
            $content = $timing['content']; // 内容
            Timer::add($intervals, function () use ($status, $content) {
                // 确认链接直播间的情况
                $cookie = RobotServices::getCookie();
                $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
                if ($cookie && $room_id) {
                    switch ($status) {
                        case 0: // 不论何时
                            $this->sendMessage($content);
                            break;
                        case 1: // 仅在直播中
                            if (Redis::get('bilibili_live_key')) {
                                $this->sendMessage($content);
                            }
                            break;
                        case 2: // 仅在非直播中
                            if (!Redis::get('bilibili_live_key')) {
                                $this->sendMessage($content);
                            }
                            break;
                    }
                }
            });
        }
    }

    /**
     * 发送广告信息
     * 
     * @param string $content 文本信息
     * 
     * @return void 
     */
    private function sendMessage(string $content)
    {
        // 拆分要发送的内容
        $content = splitAndFilterLines($content);
        if (count($content)) {
            $text = $content[mt_rand(0, (count($content) - 1))];
            if (!empty($text)) {
                // 加入消息发送队列
                $lockKey = config('app')['app_name'] . ':send_message_lock';
                $timing = readFileContent(runtime_path() . '/tmp/timing.cfg');
                $lockExpiration = false;
                if ($timing) {
                    $timing = json_decode($timing, true);
                    $lockExpiration = $timing['intervals'];
                }
                if (!$lockExpiration) {
                    $lockExpiration = 60;
                }
                if (!Redis::get($lockKey)) {
                    SendMessage::push($text, 'Timing');
                    // 设置锁，过期时间为 $lockExpiration - 1 秒
                    Redis::setEx($lockKey, $lockExpiration - 1, 'locked');
                    sublog('核心业务', '定时广告', "发送数据", [
                        'text' => $text
                    ]);
                } else {
                    sublog('核心业务', '定时广告', "死锁", [
                        'text' => $text
                    ]);
                }
            }
        }
    }
}
