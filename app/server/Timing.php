<?php

namespace app\server;

use app\queue\SendMessage;
use Workerman\Crontab\Crontab;
use Workerman\Timer;
use Workerman\Worker;
use Hejunjie\Bililive;
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
        $socketFile = runtime_path('/timing.sock');
        if (file_exists($socketFile)) {
            unlink($socketFile);
        }
        $unixWorker = new Worker("unix://$socketFile");
        $unixWorker->onMessage = function ($connection, $data) {
            if ($data === 'reload') {
                $this->startUp();
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
        $timing = readFileContent(runtime_path('/tmp/timing.cfg'));
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
                $cookie = strval(readFileContent(runtime_path('/tmp/cookie.cfg')));
                $room_id = intval(readFileContent(runtime_path('/tmp/connect.cfg')));
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
                SendMessage::push($text, 10);
                sublog('逻辑检测', '定时广告', '发送数据：' . $text);
            }
        }
    }
}
