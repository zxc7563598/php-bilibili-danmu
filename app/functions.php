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
 * 重启websocket
 * 
 * @return void 
 * @throws InvalidTimeZoneException 
 */
function restartWebSocket()
{
    $url = 'http://127.0.0.1:' . getenv('LISTEN') . '/reload-bilibili';
    $timestamp = Carbon::now()->timezone(config('app')['default_timezone'])->timestamp;
    Tools\HttpClient::sendPostRequest($url, [], [
        'api_key' => md5(getenv('SECURE_API_KEY') . $timestamp),
        'timestamp' => $timestamp
    ]);
}
