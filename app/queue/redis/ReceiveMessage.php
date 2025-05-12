<?php

namespace app\queue\redis;

use app\model\DanmuLogs;
use Webman\RedisQueue\Consumer;
use resource\enums\DanmuLogsEnums;

class ReceiveMessage implements Consumer
{
    // 要消费的队列名
    public $queue = 'receive-message';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        // 获取参数
        $param['live_key'] = $data['live_key'];
        $param['uid'] = $data['uid'];
        $param['uname'] = $data['uname'];
        $param['msg'] = $data['msg'];
        $param['badge_uid'] = $data['badge_uid'];
        $param['badge_uname'] = $data['badge_uname'];
        $param['badge_room_id'] = $data['badge_room_id'];
        $param['badge_name'] = $data['badge_name'];
        $param['badge_level'] = $data['badge_level'];
        $param['badge_type'] = $data['badge_type'];
        $param['time'] = $data['time'];
        // 记录数据
        $danmu = new DanmuLogs();
        $danmu->uid = $param['uid'];
        $danmu->uname = $param['uname'];
        $danmu->msg = $param['msg'];
        $danmu->live = !is_null($param['live_key']) ? DanmuLogsEnums\Live::Yes->value : DanmuLogsEnums\Live::No->value;
        $danmu->badge_uid = $param['badge_uid'];
        $danmu->badge_uname = $param['badge_uname'];
        $danmu->badge_room_id = $param['badge_room_id'];
        $danmu->badge_name = $param['badge_name'];
        $danmu->badge_level = $param['badge_level'];
        $danmu->badge_type = $param['badge_type'] ?: 0;
        $danmu->send_at = $param['send_at'];
        $danmu->save();
    }

    // 消费失败回调
    /* 
    $package = [
        'id' => 1357277951, // 消息ID
        'time' => 1709170510, // 消息时间
        'delay' => 0, // 延迟时间
        'attempts' => 2, // 消费次数
        'queue' => 'send-mail', // 队列名
        'data' => ['to' => 'tom@gmail.com', 'content' => 'hello'], // 消息内容
        'max_attempts' => 5, // 最大重试次数
        'error' => '错误信息' // 错误信息
    ]
    */
    public function onConsumeFailure(\Throwable $e, $package)
    {
        echo "consume failure\n";
        echo $e->getMessage() . "\n";
        // 无需反序列化
        var_export($package);
    }
}
