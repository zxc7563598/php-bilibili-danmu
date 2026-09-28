<?php

namespace app\queue\export;

use app\core\ExportService;
use Webman\RedisQueue\Consumer;

/**
 * 数据导出消费者
 *
 * 单独放在 app/queue/export 目录下、由 config/process.php 里独立的 export 进程消费，
 * 避免长时间导出占住 app/queue/redis 那个消费者进程，导致弹幕入库被卡住。
 */
class ExportData implements Consumer
{
    // 要消费的队列名
    public $queue = 'export-data';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        ExportService::runTask($data['task_id']);
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
        sublog('数据导出', '导出任务', '导出异常', [
            'task_id' => $package['data']['task_id'] ?? '',
            'attempts' => $package['attempts'] ?? 0,
            'max_attempts' => $package['max_attempts'] ?? 0,
            'error' => $e->getMessage()
        ]);
    }
}
