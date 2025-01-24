<?php

namespace process;

use app\model\SilentUser;
use Carbon\Carbon;
use Workerman\Crontab\Crontab;
use Hejunjie\Tools;
use Hejunjie\Bililive;

class Task
{
    public function onWorkerStart()
    {
        // 每天的0点执行
        new Crontab('0 0 * * *', function () {
            self::logDeletion();
            self::logTransfer();
        });

        // 每分钟执行一次
        new Crontab('0 */1 * * * *', function () {
            self::removeSilent();
        });
    }

    /**
     * 日志转移
     * 
     * @return void 
     */
    private static function logTransfer(): void
    {
        $path = base_path() . '/runtime/logs/' . Carbon::now()->subDays(1)->timezone(config('app')['default_timezone'])->format('Y-m-d');
        $stdout = base_path() . '/runtime/logs/stdout.log';
        $workerman = base_path() . '/runtime/logs/workerman.log';
        // 目录不存在则创建目录
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        // 复制文件
        copy($stdout, $path . '/stdout.log');
        copy($workerman, $path . '/workerman.log');
        // 清空日志文件
        file_put_contents($stdout, '');
        file_put_contents($workerman, '');
    }

    /**
     * 日志删除
     *
     * @return void
     */
    private static function logDeletion(): void
    {
        sublog('定时任务', '初始化', '日志删除');
        $dir = base_path() . '/runtime/logs/' . Carbon::now()->subDays(8)->timezone(config('app')['default_timezone'])->format('Y-m-d');
        sublog('定时任务', '初始化', '删除路径:' . $dir);
        if (is_dir($dir)) {
            $fileDelete = Tools\FileUtils::fileDelete($dir);
            if ($fileDelete) {
                sublog('定时任务', '初始化', '删除成功');
            } else {
                sublog('定时任务', '初始化', '删除失败');
            }
        } else {
            sublog('定时任务', '初始化', '路径不存在');
        }
        sublog('定时任务', '初始化', '==========');
    }

    /**
     * 解除禁言
     * 
     * @return void 
     */
    private static function removeSilent(): void
    {
        // 获取凭证
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        // 获取可以解除禁言的数据
        $silent_minute = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
        $silent_user = SilentUser::where('silent_minute', '<', $silent_minute)->get();
        foreach ($silent_user as $item) {
            sublog('定时任务', '解除禁言', '用户:' . $item->tname . ' - ' . $item->tuid);
            Bililive\Live::delSilentUser($room_id, $cookie, $item->black_id);
            $item->delete();
            sublog('定时任务', '解除禁言', '解除成功');
            sublog('定时任务', '解除禁言', '==========');
        }
    }
}
