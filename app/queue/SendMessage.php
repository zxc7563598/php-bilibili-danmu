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
    protected static $mergeKey = 'bilibili_send_message_merge';

    private static function getPriority($input): int
    {
        return match ($input) {
            'Autoresponders' => 15,
            'Enter' => 5,
            'Follow' => 5,
            'Present' => 20,
            'Share' => 5,
            'Timing' => 10,
            default => 1,
        };
    }

    private static function getBilibiliSpeakLength($room_id, $cookie): int
    {
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
        return $length;
    }

    private static function getGiftMessage($uid, $name, $message): array
    {
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        $message_list = [];
        $current_message = Redis::hGet(self::$mergeKey, $uid) ?: '';
        echo json_encode($current_message, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
        $extra = json_decode($current_message, true);
        // 初始化一个空数组用于存储分类数据
        $gift = [];
        // 遍历原始数据并分类
        foreach ($extra as $item) {
            $giftName = $item['giftName'];
            $price = (int) $item['price'];
            $num = (int) $item['num'];
            // 如果该礼物已经在 $gift 中，累加相应的 price 和 num
            if (isset($gift[$giftName])) {
                $gift[$giftName]['price'] += $price;
                $gift[$giftName]['num'] += $num;
            } else {
                // 否则，初始化该礼物的分类
                $gift[$giftName] = [
                    'price' => $price,
                    'num' => $num
                ];
            }
        }
        if (count($gift)) {
            $price = 0;
            $gift_name = '';
            foreach ($gift as $name => $item) {
                $price += $item['price'];
                if (preg_match('/@num@/', $message)) {
                    $gift_name = $item['num'] . '个' . $name;
                } else {
                    $gift_name = $name;
                }
            }
            $args = [
                'name' => $name,
                'giftName' => $gift_name,
                'price' => $price
            ];
            foreach ($args as $key => $replace) {
                $message = preg_replace('/(@' . $key . '@)/i', $replace, $message);
            }
            // 获取直播间最大发言长度
            $length = self::getBilibiliSpeakLength($room_id, $cookie);
            // 数据追加到队列
            $message_list = mb_str_split($message, $length, 'UTF-8');
        }

        return $message_list;
    }

    /**
     * 投递消息到队列
     * 
     * @param string $message 消息信息
     * @param int $priority 消息类型
     * @param string $uid 用户uid
     * @param string $uname 用户名
     * @param array $extra 额外信息
     * 
     * @return void 
     * @throws Exception 
     * @throws InvalidTimeZoneException 
     */
    public static function push(string $message, string $type = '', string $uid = '', string $uname = '', array $extra = []): void
    {
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        if ($cookie && $room_id) {
            // 获取优先级
            $priority = self::getPriority($type);
            // 设置一个初始的数值
            if (!Redis::get('bilibili_send_sequence')) {
                Redis::set('bilibili_send_sequence', 1);
            }
            // 设置时间
            $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
            // 处理数据
            if ($type == 'Present') {
                // 获取数据是否存在
                $exist = false;
                $cursor = 0;
                do {
                    list($cursor, $tasks) = Redis::zScan(self::$queueKey, $cursor);
                    // 遍历扫描到的任务
                    if ($tasks) {
                        foreach ($tasks as $task) {
                            $taskData = json_decode($task, true);
                            if ($taskData['uid'] == $uid) {
                                $exist = true;
                                break 2; // 找到后退出循环
                            }
                        }
                    }
                } while ($cursor != 0);
                // 加入数据
                if (!$exist) {
                    $bilibili_send_sequence = Redis::get('bilibili_send_sequence');
                    $score = $priority - $bilibili_send_sequence * 0.000001;
                    $task = json_encode([
                        'message' => $message,
                        'score' => $score,
                        'timestamp' => $timestamp,
                        'type' => $type,
                        'uid' => $uid,
                        'name' => $uname
                    ]);
                    // 使用 Redis 的 ZSET 存储，优先级为负数让高优先级排前
                    Redis::zAdd(self::$queueKey, -$score, $task);
                    Redis::incr('bilibili_send_sequence');
                }
                // 额外信息存入缓存区
                $current_message = Redis::hGet(self::$mergeKey, $uid) ?: '';
                $new_message = json_encode($extra);
                if ($current_message) {
                    $new_message = json_encode(array_merge(json_decode($current_message, true), [$extra]));
                }
                Redis::hSet(self::$mergeKey, $uid, $new_message);
            } else {
                // 获取直播间最大发言长度
                $length = self::getBilibiliSpeakLength($room_id, $cookie);
                // 数据追加到队列
                $message_list = mb_str_split($message, $length, 'UTF-8');
                // 处理数据
                foreach ($message_list as $item) {
                    $bilibili_send_sequence = Redis::get('bilibili_send_sequence');
                    $score = $priority - $bilibili_send_sequence * 0.000001;
                    $task = json_encode([
                        'message' => $item,
                        'score' => $score,
                        'timestamp' => $timestamp,
                        'type' => $type,
                        'uid' => $uid,
                        'name' => $uname
                    ]);
                    // 使用 Redis 的 ZSET 存储，优先级为负数让高优先级排前
                    Redis::zAdd(self::$queueKey, -$score, $task);
                    Redis::incr('bilibili_send_sequence');
                }
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
            $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
            $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
            // 如果任务时间超过 30 秒，则跳过此任务
            if ($timeDifference > 30) {
                echo "跳过弹幕: `" . $task['message'] . "` 的发送，因为他已经超过30秒未发送\n";
            } else {
                // 执行任务
                if ($task['type'] == 'Present') {
                    // 礼物类型信息，读取数据，根据模板生成答谢内容
                    if ($cookie && $room_id) {
                        $message = self::getGiftMessage($task['uid'], $task['name'], $task['message']);
                        foreach ($message as $_message) {
                            echo "发送优先级为" . $task['score'] . "的弹幕: " . $_message . PHP_EOL;
                            // BiliLive\Live::sendMsg($room_id, $cookie, $_message);
                            sublog('逻辑检测', '信息发送', [
                                'message' => $_message,
                                'score' => $task['score'],
                                'timestamp' => Carbon::parse($task['timestamp'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s')
                            ]);
                        }
                    }
                } else {
                    // 非礼物类型信息，直接发送
                    echo "发送优先级为" . $task['score'] . "的弹幕: " . $task['message'] . PHP_EOL;
                    if ($cookie && $room_id) {
                        // BiliLive\Live::sendMsg($room_id, $cookie, $task['message']);
                        sublog('逻辑检测', '信息发送', [
                            'message' => $task['message'],
                            'score' => $task['score'],
                            'timestamp' => Carbon::parse($task['timestamp'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s')
                        ]);
                    }
                }
                return;
            }
        }
    }
}
