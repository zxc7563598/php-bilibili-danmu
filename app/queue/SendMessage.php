<?php

namespace app\queue;

use Carbon\Carbon;
use Exception;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Redis;
use Hejunjie\Bililive;

class SendMessage
{
    protected static $queueKey = 'bilibili_send_message';

    /**
     * 投递消息到队列
     * 
     * @param string $message 消息信息
     * @param int $priority 优先级，数字越大越优先发送
     * 
     * @return void 
     * @throws Exception 
     * @throws InvalidTimeZoneException 
     */
    public static function push(string $message, int $priority = 0): void
    {
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        if ($cookie && $room_id) {
            // 获取直播间最大发言长度
            $length = Redis::get('bilibili_speak_length');
            if (empty($length)) {
                $getUserBarrageMsg = Bililive\Live::getUserBarrageMsg($room_id, $cookie);
                if (isset($getUserBarrageMsg['length'])) {
                    $length = $getUserBarrageMsg['length'];
                    Redis::setEx('bilibili_speak_length', 3600, $length);
                }
            }
            if (empty($length)) {
                $length = 30;
            }
            // 数据追加到队列
            $message_list = mb_str_split($message, $length, 'UTF-8');
            $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
            // 设置一个初始的数值
            if (!Redis::get('bilibili_send_sequence')) {
                Redis::set('bilibili_send_sequence', 1);
            }
            foreach ($message_list as $item) {
                $bilibili_send_sequence = Redis::get('bilibili_send_sequence');
                $score = $priority - $bilibili_send_sequence * 0.000001;
                $task = json_encode([
                    'message' => $item,
                    'score' => $score,
                    'timestamp' => $timestamp
                ]);
                // 使用 Redis 的 ZSET 存储，优先级为负数让高优先级排前
                Redis::zAdd(self::$queueKey, -$score, $task);
                Redis::incr('bilibili_send_sequence');
            }
        }
    }

    /**
     * 处理队列信息
     * 
     * @return void 
     * @throws InvalidTimeZoneException 
     */
    public static function processQueue(): void
    {
        $currentTimestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
        while (true) {
            // 获取优先级最高的任务
            $taskData = Redis::zRange(self::$queueKey, 0, 0);
            if (empty($taskData)) {
                return;
            }
            // 从 Redis 中移除任务
            $taskJson = $taskData[0];
            Redis::zRem(self::$queueKey, $taskJson);
            // 解码任务数据
            $task = json_decode($taskJson, true);
            $timeDifference = $currentTimestamp - $task['timestamp'];
            // 如果任务时间超过 30 秒，则跳过此任务
            if ($timeDifference > 30) {
                echo "跳过弹幕: `" . $task['message'] . "` 的发送，因为他已经超过30秒未发送\n";
            } else {
                // 执行任务
                echo "发送优先级为" . $task['score'] . "的弹幕: " . $task['message'] . PHP_EOL;
                $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
                $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
                if ($cookie && $room_id) {
                    BiliLive\Live::sendMsg($room_id, $cookie, $task['message']);
                    sublog('逻辑检测', '信息发送', [
                        'message' => $task['message'],
                        'score' => $task['score'],
                        'timestamp' => Carbon::parse($task['timestamp'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s')
                    ]);
                }
                return;
            }
        }
    }
}
