<?php

namespace process;

use Carbon\Carbon;
use Hejunjie\HardwareMonitor\CPUInfo;
use Hejunjie\HardwareMonitor\MemoryInfo;
use Workerman\Crontab\Crontab;
use Hejunjie\Tools;

class Task
{
    public function onWorkerStart()
    {
        // 每天0点执行，注意这里省略了秒位
        new Crontab('0 0 * * *', function () {
            Tools\FileUtils::fileDelete(runtime_path() . '/tmp/cpuRealTimeData.cfg');
            Tools\FileUtils::fileDelete(runtime_path() . '/tmp/memoryRealTimeData.cfg');
        });

        // 5分钟执行一次
        new Crontab('0 */5 * * * *', function () {
            if (!file_exists(runtime_path() . '/tmp/cpuRealTimeData.cfg')) {
                $cpuData = [];
                for ($i = 0; $i < 288; $i++) {
                    array_push($cpuData, null);
                }
            } else {
                $cpuData = json_decode(Tools\FileUtils::readFile(runtime_path() . '/tmp/cpuRealTimeData.cfg'), true);
            }
            if (!file_exists(runtime_path() . '/tmp/memoryRealTimeData.cfg')) {
                $memoryData = [];
                for ($i = 0; $i < 288; $i++) {
                    array_push($memoryData, null);
                }
            } else {
                $memoryData = json_decode(Tools\FileUtils::readFile(runtime_path() . '/tmp/memoryRealTimeData.cfg'), true);
            }
            $index = floor(Carbon::now()->timezone(config('app')['default_timezone'])->minuteOfDay / 5);
            $cpuUsage = CPUInfo::getCpuUsage();
            $memoryInfo = MemoryInfo::getMemoryUsage();
            $cpuData[$index] = floor(100 - $cpuUsage['idle']);
            $memoryData[$index] = floor(($memoryInfo['used'] / $memoryInfo['total']) * 100);
            Tools\FileUtils::writeToFile(runtime_path() . '/tmp/cpuRealTimeData.cfg', json_encode($cpuData));
            Tools\FileUtils::writeToFile(runtime_path() . '/tmp/memoryRealTimeData.cfg', json_encode($memoryData));
        });
    }
}
