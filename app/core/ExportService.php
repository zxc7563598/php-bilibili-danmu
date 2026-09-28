<?php

namespace app\core;

use Carbon\Carbon;
use RuntimeException;
use support\Db;
use support\Redis;
use Webman\RedisQueue\Client;

/**
 * 数据导出服务（供新项目导入）
 *
 * 把 bl_danmu_logs、bl_gift_records、bl_user_vips 三张表全量导出成一个 gzip 压缩的 JSON 文件。
 * 弹幕表可能上百万行，因此：
 *   - 走查询构造器分块读取（不走 Eloquent 模型），全程不把数据堆进内存；查询构造器没有
 *     SoftDeletes 全局作用域，所以天然包含软删除行，deleted_at 原值保留；
 *   - 用 gzopen 流式压缩写盘，输出体积约为原始 JSON 的 1/10；
 *   - 由 Redis 队列异步执行，进度写回 Redis 供前端轮询，避免 HTTP 超时。
 */
class ExportService
{
    /**
     * 要导出的表：表名 => 主键
     */
    private const TABLES = [
        'bl_danmu_logs' => 'id',
        'bl_gift_records' => 'records_id',
        'bl_user_vips' => 'user_id',
    ];

    /**
     * 不参与导出的字段
     *
     * bl_user_vips 的这三个字段是用户凭证：password 是密码摘要、salt 是扰乱码、
     * token 是用户端商城登录凭证。导出文件是可以通过 URL 下载的，因此不下发。
     */
    private const EXCLUDED_COLUMNS = [
        'bl_user_vips' => ['password', 'salt', 'token'],
    ];

    private const QUEUE_NAME = 'export-data'; // 队列名，对应 app/queue/export/ExportData.php
    private const CHUNK_SIZE = 2000;          // 每批读取行数
    private const TASK_TTL = 604800;          // 任务状态保留 7 天
    private const RUNNING_TTL = 7200;         // 单飞锁 2 小时（要长于一次导出可能消耗的时间）
    private const STALE_SECONDS = 300;        // running 超过 5 分钟没有心跳视为已中断
    private const PENDING_SECONDS = 120;      // pending 超过 2 分钟视为投递失败
    private const DURATION_KEEP_HOURS = 24;   // 导出文件保留时长

    private const RUNNING_KEY = 'export_task_running';

    /**
     * 获取任务状态的 Redis 键
     *
     * @param string $taskId 任务ID
     *
     * @return string
     */
    private static function taskKey(string $taskId): string
    {
        return 'export_task:' . $taskId;
    }

    /**
     * 获取任务的文件目录
     *
     * 放在 public 下，目录名是不可猜的 task_id（32 位十六进制），
     * 下载地址由需要登录才能拿到的进度接口下发，文件 24 小时后自动清理。
     *
     * @param string $taskId 任务ID
     *
     * @return string
     */
    public static function taskDir(string $taskId): string
    {
        return public_path() . '/export/' . $taskId;
    }

    /**
     * 创建导出任务
     *
     * @param integer $adminId 管理员ID
     *
     * @return array
     * @throws RuntimeException
     */
    public static function createTask(int $adminId): array
    {
        $now = self::now();
        // 已有任务在排队或执行中，直接复用，避免同时跑多个导出；僵死的不复用，让用户能马上重试
        $running = Redis::get(self::RUNNING_KEY);
        if (!empty($running)) {
            $task = self::getTask($running);
            if ($task && in_array($task['status'], ['pending', 'running']) && !self::isDead($task, $now)) {
                return ['task_id' => $running, 'reused' => true];
            }
        }
        $taskId = bin2hex(random_bytes(16));
        // 构建任务状态
        $tables = [];
        foreach (array_keys(self::TABLES) as $table) {
            $tables[$table] = ['total' => 0, 'processed' => 0];
        }
        $task = [
            'task_id' => $taskId,
            'admin_id' => $adminId,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
            'started_at' => 0,
            'finished_at' => 0,
            'current_table' => '',
            'processed' => 0,
            'total' => 0,
            'tables' => $tables,
            'file' => '',
            'filename' => '',
            'size' => 0,
            'error' => ''
        ];
        // 先落状态再投递，避免消息先被消费时读不到任务
        self::saveTask($task);
        Redis::setEx(self::RUNNING_KEY, self::RUNNING_TTL, $taskId);
        // 投递到队列异步执行
        try {
            Client::send(self::QUEUE_NAME, ['task_id' => $taskId], 0, function ($result) use ($taskId) {
                if (!$result) {
                    self::markFailed($taskId, '导出任务投递失败，请检查 Redis 是否可用');
                }
            });
        } catch (\Throwable $e) {
            // 投递失败立刻标记失败并释放锁，避免用户要等心跳超时才能重新发起
            self::markFailed($taskId, '导出任务投递失败：' . $e->getMessage());
            throw $e;
        }
        // 返回数据
        return ['task_id' => $taskId, 'reused' => false];
    }

    /**
     * 获取导出进度
     *
     * @param string $taskId 任务ID
     *
     * @return array|null
     */
    public static function getProgress(string $taskId): array|null
    {
        $task = self::getTask($taskId);
        if (empty($task)) {
            return null;
        }
        $now = self::now();
        // 队列没有 ack 机制，消费者进程被杀 / 队列消息丢失时任务不会自己回来，用心跳兜底
        if (self::isDead($task, $now)) {
            $dead_status = $task['status'];
            $task['status'] = 'failed';
            $task['error'] = $dead_status === 'pending'
                ? '导出任务投递失败（等待超过 ' . intval(self::PENDING_SECONDS / 60) . ' 分钟仍未开始），请重新发起导出'
                : '导出进程已中断（超过 ' . intval(self::STALE_SECONDS / 60) . ' 分钟没有进度更新），请重新发起导出';
        }
        // 文件已被定时清理（超过保留时长），当成失败让用户重新导出
        if ($task['status'] === 'finished' && (empty($task['file']) || !is_file($task['file']))) {
            $task['status'] = 'failed';
            $task['error'] = '导出文件已过期被清理，请重新发起导出';
        }
        if ($task['status'] === 'failed') {
            $task['updated_at'] = $now;
            self::saveTask($task);
            self::releaseRunning($taskId);
        }
        // 组装返回数据
        $percent = $task['total'] > 0 ? round($task['processed'] / $task['total'] * 100, 2) : 0;
        return [
            'task_id' => $task['task_id'],
            'status' => $task['status'],
            'current_table' => $task['current_table'],
            'processed' => $task['processed'],
            'total' => $task['total'],
            'percent' => $percent,
            'tables' => $task['tables'],
            'filename' => $task['filename'],
            'size' => $task['size'],
            'error' => $task['error'],
            'download_url' => $task['status'] === 'finished' ? self::buildDownloadUrl($task) : ''
        ];
    }

    /**
     * 执行导出任务（由队列消费者调用）
     *
     * @param string $taskId 任务ID
     *
     * @return void
     * @throws RuntimeException
     */
    public static function runTask(string $taskId): void
    {
        $task = self::getTask($taskId);
        if (empty($task)) {
            throw new RuntimeException('导出任务不存在或已过期：' . $taskId);
        }
        // 幂等：队列失败会按 5s / 10s / 15s ... 重投同一个任务，已完成的不重复跑
        if ($task['status'] === 'finished') {
            return;
        }
        ini_set('memory_limit', '512M');
        $startAt = microtime(true);
        $now = self::now();
        // 每次尝试都从干净的状态开始，清掉上一次残留的文件，避免重试时追加脏数据
        $dir = self::taskDir($taskId);
        self::ensureDir($dir);
        self::purgeDir($dir);
        $filename = 'bilibili_danmu_export_' . Carbon::createFromTimestamp($now)->timezone(config('app.default_timezone'))->format('Ymd_His') . '.json.gz';
        $filePath = $dir . '/' . $filename;
        $tmpPath = $filePath . '.tmp';
        $task['status'] = 'running';
        $task['started_at'] = $now;
        $task['updated_at'] = $now;
        $task['filename'] = $filename;
        $task['file'] = $filePath;
        $task['error'] = '';
        self::saveTask($task);
        try {
            // 取各表列名与总数，为 manifest 和进度做准备
            $manifest_tables = [];
            $tables = [];
            $total = 0;
            foreach (self::TABLES as $table => $primary_key) {
                $columns = self::columns($table);
                // 快照上界：弹幕表在导出期间仍在写入，锁定上界保证每次尝试读到同一批数据
                $maxId = Db::table($table)->max($primary_key);
                $maxId = is_null($maxId) ? 0 : intval($maxId);
                $count = $maxId > 0 ? Db::table($table)->where($primary_key, '<=', $maxId)->count() : 0;
                $manifest_tables[$table] = [
                    'primary_key' => $primary_key,
                    'max_id' => $maxId,
                    'columns' => $columns
                ];
                $tables[$table] = ['total' => $count, 'processed' => 0, 'max_id' => $maxId];
                $total += $count;
            }
            // 写回一次总数，前端进度条才有分母
            $task['tables'] = array_map(fn($item) => ['total' => $item['total'], 'processed' => 0], $tables);
            $task['total'] = $total;
            $task['updated_at'] = self::now();
            self::saveTask($task);
            // 流式压缩写盘
            $fh = gzopen($tmpPath, 'wb6');
            if (!$fh) {
                throw new RuntimeException('创建导出文件失败：' . $tmpPath);
            }
            try {
                $manifest = [
                    'source' => 'BilibiliDanmuji',
                    'app_name' => config('app.app_name'),
                    'exported_at' => $now,
                    'exported_at_text' => Carbon::createFromTimestamp($now)->timezone(config('app.default_timezone'))->format('Y-m-d H:i:s'),
                    'include_deleted' => true,
                    'excluded_columns' => self::EXCLUDED_COLUMNS,
                    'tables' => $manifest_tables
                ];
                self::write($fh, '{"manifest":' . self::jsonEncode($manifest) . ',"tables":{');
                foreach (self::TABLES as $table => $primary_key) {
                    self::write($fh, '"' . $table . '":[');
                    $task['current_table'] = $table;
                    $task['updated_at'] = self::now();
                    self::saveTask($task);
                    $processed = 0;
                    $first = true;
                    // 分块读取 + 逐块写入，单块内攒成一个字符串再一次性写，减少 gzwrite 调用次数
                    if ($tables[$table]['max_id'] > 0) {
                        Db::table($table)
                            ->select($manifest_tables[$table]['columns'])
                            ->where($primary_key, '<=', $tables[$table]['max_id'])
                            ->chunkById(self::CHUNK_SIZE, function ($rows) use ($fh, &$first, &$processed, &$task, $table) {
                                $buffer = '';
                                foreach ($rows as $row) {
                                    $buffer .= ($first ? '' : ',') . self::jsonEncode((array)$row);
                                    $first = false;
                                }
                                self::write($fh, $buffer);
                                // 写回进度与心跳
                                $processed += count($rows);
                                $task['tables'][$table]['processed'] = $processed;
                                $task['processed'] = array_sum(array_column($task['tables'], 'processed'));
                                $task['updated_at'] = self::now();
                                self::saveTask($task);
                            }, $primary_key);
                    }
                    self::write($fh, ']');
                    // 下一张表前补逗号
                    if (array_key_last(self::TABLES) !== $table) {
                        self::write($fh, ',');
                    }
                }
                // 收尾：写实际行数与耗时，仅作校验用
                $duration = intval(microtime(true) - $startAt);
                $counts = [];
                foreach ($task['tables'] as $table => $item) {
                    $counts[$table] = $item['processed'];
                }
                $summary = [
                    'exported_at' => self::now(),
                    'duration' => $duration,
                    'counts' => $counts
                ];
                self::write($fh, '},"summary":' . self::jsonEncode($summary) . '}');
            } catch (\Throwable $e) {
                gzclose($fh);
                throw $e;
            }
            if (!gzclose($fh)) {
                throw new RuntimeException('导出文件写入失败，可能是磁盘空间不足');
            }
            // 写临时文件、成功后才改名，避免下载到半个文件
            if (!rename($tmpPath, $filePath)) {
                throw new RuntimeException('导出文件重命名失败：' . $filePath);
            }
            // 更新任务状态
            $task['status'] = 'finished';
            $task['finished_at'] = self::now();
            $task['updated_at'] = $task['finished_at'];
            $task['size'] = filesize($filePath);
            $task['current_table'] = '';
            self::saveTask($task);
            self::releaseRunning($taskId);
        } catch (\Throwable $e) {
            self::markFailed($taskId, $e->getMessage());
            sublog('数据导出', '导出任务', '导出失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            // 重新抛出，让队列记录失败信息并按策略重试
            throw $e;
        }
        // 清理过期文件
        self::clearExpiredFiles();
        sublog('数据导出', '导出任务', '导出完成', [
            'task_id' => $taskId,
            'filename' => $filename,
            'size' => $task['size'],
            'duration' => intval(microtime(true) - $startAt)
        ]);
    }

    /**
     * 清理过期的导出文件
     *
     * @param integer $hours 保留时长（小时）
     *
     * @return void
     */
    public static function clearExpiredFiles(int $hours = self::DURATION_KEEP_HOURS): void
    {
        $root = public_path() . '/export';
        if (!is_dir($root)) {
            return;
        }
        $expire = self::now() - $hours * 3600;
        foreach (glob($root . '/*') as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $expired = true;
            foreach (glob($dir . '/*') as $file) {
                if (filemtime($file) > $expire) {
                    $expired = false;
                    break;
                }
            }
            if ($expired) {
                self::removeDir($dir);
            }
        }
    }

    /**
     * 读取任务状态
     *
     * @param string $taskId 任务ID
     *
     * @return array|null
     */
    private static function getTask(string $taskId): array|null
    {
        if ($taskId === '') {
            return null;
        }
        $json = Redis::get(self::taskKey($taskId));
        if (empty($json)) {
            return null;
        }
        $task = json_decode($json, true);
        return is_array($task) ? $task : null;
    }

    /**
     * 判断任务是否已经卡死
     *
     * 队列没有 ack / 可见性超时机制：消费者进程被杀、OOM 或消息丢失时，任务会永久停在
     * pending / running，只能靠心跳时间兜底判定。
     *
     * @param array $task 任务数据
     * @param int $now 当前时间戳
     *
     * @return bool
     */
    private static function isDead(array $task, int $now): bool
    {
        if ($task['status'] === 'pending') {
            return ($now - $task['created_at']) > self::PENDING_SECONDS;
        }
        if ($task['status'] === 'running') {
            return ($now - $task['updated_at']) > self::STALE_SECONDS;
        }
        return false;
    }

    /**
     * 保存任务状态
     *
     * @param array $task 任务数据
     *
     * @return void
     */
    private static function saveTask(array $task): void
    {
        Redis::setEx(self::taskKey($task['task_id']), self::TASK_TTL, self::jsonEncode($task));
    }

    /**
     * 标记任务失败
     *
     * @param string $taskId 任务ID
     * @param string $message 失败原因
     *
     * @return void
     */
    private static function markFailed(string $taskId, string $message): void
    {
        $task = self::getTask($taskId);
        if (empty($task)) {
            return;
        }
        $task['status'] = 'failed';
        $task['error'] = $message;
        $task['updated_at'] = self::now();
        self::saveTask($task);
        self::releaseRunning($taskId);
    }

    /**
     * 释放单飞锁
     *
     * @param string $taskId 任务ID
     *
     * @return void
     */
    private static function releaseRunning(string $taskId): void
    {
        $running = Redis::get(self::RUNNING_KEY);
        if ($running === $taskId) {
            Redis::del(self::RUNNING_KEY);
        }
    }

    /**
     * 构建下载地址
     *
     * @param array $task 任务数据
     *
     * @return string
     */
    private static function buildDownloadUrl(array $task): string
    {
        if (empty($task['file'])) {
            return '';
        }
        return config('app.api_url') . '/' . ltrim(str_replace(public_path(), '', $task['file']), '/');
    }

    /**
     * 获取表字段列表（已剔除凭证字段）
     *
     * @param string $table 表名
     *
     * @return array
     */
    private static function columns(string $table): array
    {
        $columns = Db::connection()->getSchemaBuilder()->getColumnListing($table);
        $excluded = self::EXCLUDED_COLUMNS[$table] ?? [];
        foreach ($excluded as $column) {
            $index = array_search($column, $columns);
            if ($index !== false) {
                unset($columns[$index]);
            }
        }
        return array_values($columns);
    }

    /**
     * 写入压缩流
     *
     * @param resource $fh 压缩流句柄
     * @param string $data 数据
     *
     * @return void
     * @throws RuntimeException
     */
    private static function write($fh, string $data): void
    {
        if ($data === '') {
            return;
        }
        if (gzwrite($fh, $data) !== strlen($data)) {
            throw new RuntimeException('写入导出文件失败，可能是磁盘空间不足');
        }
    }

    /**
     * JSON 编码
     *
     * @param mixed $value 数据
     *
     * @return string
     * @throws RuntimeException
     */
    private static function jsonEncode($value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION + JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            throw new RuntimeException('数据编码失败：' . json_last_error_msg());
        }
        return $json;
    }

    /**
     * 确保目录存在
     *
     * @param string $dir 目录
     *
     * @return void
     */
    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * 清空目录内容（保留目录本身，不存在则创建）
     *
     * @param string $dir 目录
     *
     * @return void
     */
    private static function purgeDir(string $dir): void
    {
        self::ensureDir($dir);
        foreach (glob($dir . '/*') as $file) {
            is_dir($file) ? self::removeDir($file) : unlink($file);
        }
    }

    /**
     * 删除目录及其内容
     *
     * @param string $dir 目录
     *
     * @return void
     */
    private static function removeDir(string $dir): void
    {
        foreach (glob($dir . '/*') as $file) {
            is_dir($file) ? self::removeDir($file) : unlink($file);
        }
        rmdir($dir);
    }

    /**
     * 获取当前时间戳
     *
     * @return int
     */
    private static function now(): int
    {
        return Carbon::now()->timezone(config('app.default_timezone'))->timestamp;
    }
}
