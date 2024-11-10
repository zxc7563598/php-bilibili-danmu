<?php

use Carbon\Carbon;
use Carbon\Exceptions\InvalidTimeZoneException;
use Hejunjie\Tools;
use support\Response;

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
        'message' => !empty($message) ? $message : '成功',
        'data' => empty($data) ? (object)[] : $data
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
        'message' => !empty($message) ? $message : '失败',
        'data' => empty($data) ? (object)[] : $data
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
    return (file_exists($path) && is_readable($path)) ? Tools\FileUtils::readFile($path) : null;
}

/**
 * 重启bilibili
 * 
 * @return void 
 * @throws InvalidTimeZoneException 
 */
function restartBilibili()
{
    $url = 'http://127.0.0.1:' . getenv('LISTEN') . '/reload-bilibili';
    $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
    Tools\HttpClient::sendPostRequest($url, [], [
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
    $url = 'http://127.0.0.1:' . getenv('LISTEN') . '/reload-timing';
    $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
    Tools\HttpClient::sendPostRequest($url, [], [
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
 * @param string $filename 存储名称
 * @param string $contents 存储内容
 * 
 * @return void
 */
function sublog($paths, $filename, $contents): void
{
    $dir = base_path() . '/runtime/logs/' . Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d') . '/' . $paths . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = $dir . $filename . ".log";
    $content = Carbon::now()->timezone(config('app')['default_timezone'])->format('H:i:s') . "        " . json_encode($contents, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION) . "\r\n";
    file_put_contents($file, $content, FILE_APPEND);
}