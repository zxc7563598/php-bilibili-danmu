<?php

namespace app\queue;

use app\core\RobotServices;
use Carbon\Carbon;
use Exception;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Redis;
use Hejunjie\Bililive;

class SendMessage
{
    protected static $queueKey = 'bilibili_send_message';
    protected static $mergeKey = 'bilibili_send_message_merge';

    /**
     * 获取优先级
     * 
     * @param mixed $input 输入
     * 
     * @return int 
     */
    private static function getPriority($input): int
    {
        return match ($input) {
            'Autoresponders' => 15,
            'CheckIn' => 15,
            'Enter' => 5,
            'Follow' => 5,
            'Present' => 20,
            'Share' => 5,
            'Timing' => 10,
            'PkLiveReport' => 30,
            default => 1,
        };
    }

    /**
     * 获取直播间最大发言长度
     * 
     * @param mixed $room_id 房间号
     * @param mixed $cookie 登录cookir
     * 
     * @return int 
     */
    private static function getBilibiliSpeakLength($room_id = null, $cookie = null): int
    {
        $room_id = $room_id ?: intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        $cookie = $cookie ?: RobotServices::getCookie();
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
            $length = 20;
        }
        return $length;
    }

    /**
     * 获取礼物弹幕信息
     * 
     * @param string $uid 用户uid
     * @param string $name 用户名称
     * @param string $message 消息
     * @param bool $number 是否展示数量
     * 
     * @return array
     */
    private static function getGiftMessage(string $uid, string $name, string $message, bool $number): array
    {
        // 获取当前用户的礼物信息，使用默认值避免空结果
        $current_message = Redis::hGet(self::$mergeKey, $uid) ?: '[]';
        $extra = json_decode($current_message, true);
        // 提前检查是否解析成功，避免后续代码出错
        if (!is_array($extra)) {
            return [];
        }
        // 分类和汇总礼物信息
        $gift = [];
        foreach ($extra as $item) {
            $giftName = $item['giftName'];
            $price = (int) $item['price'];
            $num = (int) $item['num'];

            // 使用简洁的方式进行累加
            $gift[$giftName] = [
                'price' => ($gift[$giftName]['price'] ?? 0) + $price,
                'num' => ($gift[$giftName]['num'] ?? 0) + $num
            ];
        }
        // 如果没有礼物数据，直接返回空数组
        if (empty($gift)) {
            return [];
        }
        // 计算总价格并构造礼物名称列表
        $price = 0;
        $gift_name = [];
        foreach ($gift as $key => $item) {
            $price += $item['price'];
            // 判断是否需要显示数量
            $gift_name[] = $number ? $item['num'] . '个' . $key : $key;
        }
        // 构建替换数组
        $args = [
            'name' => $name,
            'giftName' => implode('、', $gift_name),
            'price' => $price
        ];
        // 使用一次替换完成所有变量替换
        foreach ($args as $key => $replace) {
            $message = preg_replace('/(@' . $key . '@)/i', $replace, $message);
        }
        // 获取直播间最大发言长度
        $length = self::getBilibiliSpeakLength();
        // 删除累计信息
        Redis::hDel(self::$mergeKey, $uid);
        // 返回切割后的消息列表
        return mb_str_split($message, $length, 'UTF-8');
    }

    /**
     * 合并投递消息到队列
     * 
     * @param string $message 消息信息
     * @param string $uid 用户uid
     * @param string $uname 用户名
     * @param int $number 是否展示数量
     * @param array $extra 额外信息
     * 
     * @return void 
     */
    public static function mergePush(string $message, string $uid = '', string $uname = '', int $number = 0, array $extra = []): void
    {
        $cookie = RobotServices::getCookie();
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        if ($cookie && $room_id) {
            // 获取优先级
            $priority = self::getPriority('Present');
            // 设置一个初始的数值
            if (!Redis::get('bilibili_send_sequence')) {
                Redis::set('bilibili_send_sequence', 1);
            }
            // 设置时间
            $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
            // 获取数据是否存在
            $exist = false;
            $tasks = Redis::zRange(self::$queueKey, 0, -1);
            // 遍历扫描到的任务
            if ($tasks) {
                foreach ($tasks as $task) {
                    $taskData = json_decode($task, true);
                    if ($taskData['uid'] == $uid) {
                        $exist = true;
                        break; // 找到后退出循环
                    }
                }
            }
            // 加入数据
            if (!$exist) {
                $bilibili_send_sequence = Redis::get('bilibili_send_sequence');
                $score = $priority - $bilibili_send_sequence * 0.000001;
                $task = json_encode([
                    'message' => $message,
                    'score' => $score,
                    'timestamp' => $timestamp,
                    'type' => $number == 1 ? 'PresentArrNum' : 'PresentArr',
                    'uid' => $uid,
                    'name' => $uname
                ]);
                // 使用 Redis 的 ZSET 存储，优先级为负数让高优先级排前
                Redis::zAdd(self::$queueKey, -$score, $task);
                Redis::incr('bilibili_send_sequence');
            }
            // 额外信息存入缓存区
            $current_message = Redis::hGet(self::$mergeKey, $uid) ?: '';
            $new_message = json_encode([$extra]);
            if ($current_message) {
                $new_message = json_encode(array_merge(json_decode($current_message, true), [$extra]));
            }
            Redis::hSet(self::$mergeKey, $uid, $new_message);
        }
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
    public static function push(string $message, string $type = ''): void
    {
        $cookie = RobotServices::getCookie();
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
                    'uid' => '',
                    'name' => ''
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
        if (Redis::get('bilibili_stop_message')) {
            return;
        }
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
            $cookie = RobotServices::getCookie();
            $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
            // 如果任务时间超过 30 秒，则跳过此任务
            if ($timeDifference > 30) {
                echo "跳过弹幕: `" . $task['message'] . "` 的发送，因为他已经超过30秒未发送\n";
            } else {
                // 执行任务
                switch ($task['type']) {
                    case 'PresentArr':
                    case 'PresentArrNum':
                        if ($cookie && $room_id) {
                            $number = $task['type'] == 'PresentArrNum' ? true : false;
                            $message = self::getGiftMessage($task['uid'], $task['name'], $task['message'], $number);
                            foreach ($message as $_message) {
                                echo "发送优先级为" . $task['score'] . "的弹幕: " . $_message . PHP_EOL;
                                BiliLive\Live::sendMsg($room_id, $cookie, $_message);
                                Redis::setEx('bilibili_stop_message', 4, 1);
                                sublog('核心逻辑/机器人信息发送', '连续信息发送', [
                                    'message' => $_message,
                                    'score' => $task['score'],
                                    'timestamp' => Carbon::parse($task['timestamp'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s')
                                ]);
                                sleep(5);
                            }
                        }
                        break;
                    default:
                        // 直接发送
                        echo "发送优先级为" . $task['score'] . "的弹幕: " . $task['message'] . PHP_EOL;
                        if ($cookie && $room_id) {
                            BiliLive\Live::sendMsg($room_id, $cookie, $task['message']);
                            sublog('核心逻辑/机器人信息发送', '单条信息发送', [
                                'message' => $task['message'],
                                'score' => $task['score'],
                                'timestamp' => Carbon::parse($task['timestamp'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s')
                            ]);
                        }
                        break;
                }
                return;
            }
        }
    }
}
