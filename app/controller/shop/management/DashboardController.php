<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use app\core\LoginPublicMethods;
use app\model\UserVips;
use Carbon\Carbon;
use Hejunjie\HardwareMonitor\CPUInfo;
use Hejunjie\HardwareMonitor\DiskInfo;
use Hejunjie\HardwareMonitor\MemoryInfo;
use Hejunjie\Tools;
use support\Request;
use support\Redis;
use Webman\Http\Response;
use resource\enums\UserVipsEnums;

class DashboardController extends GeneralMethod
{
    public function getRealTimeData(Request $request)
    {
        // 获取CPU信息
        $cpuInfo = CPUInfo::getCpuInfo();
        // 获取cpu使用情况
        $cpuUsage = CPUInfo::getCpuUsage();
        // 内存使用情况
        $memoryInfo = MemoryInfo::getMemoryUsage();
        // 硬盘使用情况
        $diskInfo = DiskInfo::getDiskInfo();
        // 返回数据
        return success($request, [
            'cpu' => [
                'model' => $cpuInfo['model'],
                'cores' => $cpuInfo['cores'],
                'logical_cores' => $cpuInfo['logical_cores'],
                'cores_per_socket' => $cpuInfo['cores_per_socket'],
                'user' => $cpuUsage['user'],
                'sys' => $cpuUsage['sys'],
                'idle' => $cpuUsage['idle'],
                'wait' => $cpuUsage['wait'],
            ],
            'memory' => [
                'total' => $memoryInfo['total'],
                'used' => $memoryInfo['used'],
                'free' => $memoryInfo['free'],
                'cached' => $memoryInfo['cached'],
                'buffers' => $memoryInfo['buffers'],
            ],
            'disk' => $diskInfo
        ]);
    }

    public function getRealTimeHistoriesData(Request $request)
    {
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
        return success($request, [
            'cpu' => $cpuData,
            'memory' => $memoryData
        ]);
    }
}
