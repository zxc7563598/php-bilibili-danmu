<?php

use Carbon\Carbon;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Response;
use Hejunjie\Utils;
use Hejunjie\ErrorLog\Logger;
use Hejunjie\ErrorLog\Handlers;

/**
 * Api响应成功
 *
 * @param object $request Webman\Http\Request对象
 * @param array|object $data 返回数据
 * 
 * @return Response
 */
function success($request, $data = [], $message = ''): Response
{
    $request->res = [
        'code' => 0,
        'message' => !empty($message) ? $message : (trans(config('code')[0]) ?? 'error'),
        'data' => empty($data) ? [] : $data
    ];
    return json($request->res, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
}

/**
 * Api响应失败
 *
 * @param object $request Webman\Http\Request对象
 * @param array $data 返回数据
 * 
 * @return Response
 */
function fail($request, $code = 500, $data = [], $message = ''): Response
{
    // 记录错误信息
    $request->res = [
        'code' => $code,
        'message' => !empty($message) ? $message : (trans(config('code')[$code]) ?? 'error'),
        'data' => empty($data) ? [] : $data
    ];
    return json($request->res, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
}

/**
 * 读取文件信息
 * 
 * @param string $path 文件路径
 * 
 * @return null|string 
 */
function readFileContent(string $path): ?string
{
    return (file_exists($path) && is_readable($path)) ? Utils\FileUtils::readFile($path) : null;
}

/**
 * 重启bilibili
 * 
 * @return void 
 * @throws InvalidTimeZoneException 
 */
function restartBilibili()
{
    $url = getenv('RE_OPEN_HOST') . ':' . getenv('LISTEN') . '/reload-bilibili';
    $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
    Utils\HttpClient::sendPostRequest($url, [], [
        'api_key' => md5(getenv('SECURE_API_KEY') . $timestamp),
        'timestamp' => $timestamp
    ]);
}

/**
 * 重启timing
 * 
 * @return void 
 * @throws InvalidTimeZoneException 
 */
function restartTiming()
{
    $url = getenv('RE_OPEN_HOST') . ':' . getenv('LISTEN') . '/reload-timing';
    $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
    Utils\HttpClient::sendPostRequest($url, [], [
        'api_key' => md5(getenv('SECURE_API_KEY') . $timestamp),
        'timestamp' => $timestamp
    ]);
}

/**
 * 获取配置文件多行信息
 * 
 * @param string $text 
 * 
 * @return array
 */
function splitAndFilterLines($text)
{
    // 使用 preg_split 按照各种换行符切割字符串
    $lines = preg_split('/\r\n|\r|\n/', $text);
    // 使用 array_filter 去除空白行，并使用 trim 去除每行开头和结尾的空白
    $filteredLines = array_filter(array_map('trim', $lines), function ($line) {
        return $line !== '';
    });
    // 返回有内容的行数组
    return array_values($filteredLines);
}

/**
 * 日志信息存储
 *
 * @param string $paths 存储路径
 * @param string $title 存储名称
 * @param string $contents 存储内容
 * 
 * @return void
 */
function sublog($paths, $title, $message, $context): void
{
    $date = Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d');
    $log = new Logger([
        new Handlers\FileHandler(runtime_path("logs/{$date}/{$paths}"))
    ]);
    $log->info($title, $message, $context);
}

/**
 * 判断当前是否运行在docker中
 * 
 * @return bool 
 */
function isDocker(): bool
{
    $cgroupFile = '/proc/1/cgroup';
    if (file_exists($cgroupFile)) {
        $contents = file_get_contents($cgroupFile);
        return strpos($contents, 'docker') !== false;
    }
    return false;
}

/**
 * 获取图片地址信息
 *
 * @param string $str 路径信息
 * 
 * @return string
 */
function getImageUrl($str): string
{
    if (!$str) {
        return '';
    }
    if (strpos($str, 'http://') === false && strpos($str, 'https://') === false) {
        $str = config('app')['image_url'] . '/' . $str;
    }
    return $str;
}

/**
 * Api数据分页返回
 *
 * @param object|array $list 分页数据
 * 
 * @return array
 */
function pageToArray($list): array
{
    $data = is_array($list) ? $list : $list->toArray();
    $result = [];
    $result['total'] = (int)$data['total']; // 总计条数
    $result['per_page'] = (int)$data['per_page']; // 每页最大展示条数
    $result['current_page'] = (int)$data['current_page']; // 当前页码
    $result['total_page'] = (int)$data['last_page']; // 最大页码
    $result['data'] = $data['data']; // 数据
    return $result;
}

/**
 * 逐行写入文件
 * 
 * @param string $filePath 文件路径
 * @param string $line 内容
 * 
 * @return void 
 */
function writeLinesToFile($filePath, $line)
{
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \Exception("无法创建目录: " . $directory);
        }
    }
    $file = fopen($filePath, 'a');
    if ($file === false) {
        throw new \Exception("无法打开文件: " . $filePath);
    }
    fwrite($file, $line . PHP_EOL);
    fclose($file);
}

/**
 * 读取文件并统计发言次数
 *
 * @param string $filePath 文本文件路径
 * @param integer $num 需要获取多少名
 * 
 * @return array 
 */
function getTopSpeakers(string $filePath, int $num): array
{
    if (!file_exists($filePath)) {
        throw new Exception("文件不存在: $filePath");
    }

    $userStats = []; // 用户统计数据
    // 打开文件逐行读取
    $file = fopen($filePath, 'r');
    if ($file === false) {
        throw new Exception("无法打开文件: $filePath");
    }
    $count = 0;
    while (($line = fgets($file)) !== false) {
        $data = json_decode(trim($line), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue; // 跳过解析失败的行
        }
        $uid = $data['uid'];
        $uname = $data['uname'];
        $time = $data['time'];
        if (!isset($userStats[$uid])) {
            // 初始化用户数据
            $userStats[$uid] = [
                'uid' => $uid,
                'uname' => $uname,
                'count' => 0,
                'firstTime' => $time,
            ];
            $count++;
        }
        // 更新发言次数和最早时间
        $userStats[$uid]['count']++;
        if ($time < $userStats[$uid]['firstTime']) {
            $userStats[$uid]['firstTime'] = $time;
        }
    }
    fclose($file);
    // 按发言次数降序排序，如果次数相同按最早时间升序
    usort($userStats, function ($a, $b) {
        if ($a['count'] === $b['count']) {
            return $a['firstTime'] <=> $b['firstTime'];
        }
        return $b['count'] <=> $a['count'];
    });
    // 返回数据
    return [
        'count' => $count,
        'rankings' => array_slice($userStats, 0, $num)
    ];
}

/**
 * 读取文件并统计总金额
 *
 * @param string $filePath 文本文件路径
 * @param integer $num 需要获取多少名
 * 
 * @return array 
 */
function getTopSpenders(string $filePath, int $num): array
{
    if (!file_exists($filePath)) {
        throw new Exception("文件不存在: $filePath");
    }
    $userStats = []; // 用户统计数据
    // 打开文件逐行读取
    $file = fopen($filePath, 'r');
    if ($file === false) {
        throw new Exception("无法打开文件: $filePath");
    }
    $count = 0;
    while (($line = fgets($file)) !== false) {
        $data = json_decode(trim($line), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue; // 跳过解析失败的行
        }
        $uid = $data['uid'];
        $uname = $data['uname'];
        $price = $data['price'];
        $time = $data['time'];
        if (!isset($userStats[$uid])) {
            // 初始化用户数据
            $userStats[$uid] = [
                'uid' => $uid,
                'uname' => $uname,
                'totalPrice' => 0,
                'firstTime' => $time,
            ];
            $count++;
        }
        // 累加金额和更新最早时间
        $userStats[$uid]['totalPrice'] += $price;
        if ($time < $userStats[$uid]['firstTime']) {
            $userStats[$uid]['firstTime'] = $time;
        }
    }
    fclose($file);
    // 按总金额降序排序，如果金额相同按最早时间升序
    usort($userStats, function ($a, $b) {
        if ($a['totalPrice'] === $b['totalPrice']) {
            return $a['firstTime'] <=> $b['firstTime'];
        }
        return $b['totalPrice'] <=> $a['totalPrice'];
    });
    // 返回数据
    return [
        'count' => $count,
        'rankings' => array_slice($userStats, 0, $num)
    ];
}

/**
 * 获取文件行数
 *
 * @param string $filePath 文本文件路径
 * 
 * @return integer 
 */
function countFileLines(string $filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception("文件不存在: $filePath");
    }
    // 打开文件逐行读取
    $file = fopen($filePath, 'r');
    if ($file === false) {
        throw new Exception("无法打开文件: $filePath");
    }
    $count = 0;
    while (($line = fgets($file)) !== false) {
        json_decode(trim($line), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue; // 跳过解析失败的行
        }
        $count++;
    }
    return $count;
}


/**
 * 构建树形结构
 *
 * @param array $data 
 * @return array 
 */
function buildTree(array $elements, int $parentId = 0): array
{
    // 过滤出所有的子项
    $branch = array_filter($elements, fn($el) => $el['parentId'] === $parentId);

    // 按 order 排序
    usort($branch, fn($a, $b) => $a['order'] <=> $b['order']);

    // 递归构建子树
    foreach ($branch as &$item) {
        $item['children'] = buildTree($elements, $item['id']);
    }

    return array_values($branch);
}

/**
 * 递归对树形结构进行排序
 *
 * @param array $tree 
 * @return array 
 */
function sortTree(array $tree): array
{
    // 对当前层级的节点按 order 排序
    usort($tree, function ($a, $b) {
        return $a['order'] <=> $b['order'];
    });

    // 递归对子节点排序
    foreach ($tree as &$node) {
        if (!empty($node['children'])) {
            $node['children'] = sortTree($node['children']);
        }
    }
    return $tree;
}