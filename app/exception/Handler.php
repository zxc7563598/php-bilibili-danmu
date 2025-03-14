<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace app\exception;

use Carbon\Carbon;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;
use support\exception\BusinessException;
use Hejunjie\Tools\Log;

/**
 * Class Handler
 * @package support\exception
 */
class Handler extends ExceptionHandler
{
    public $dontReport = [
        BusinessException::class,
    ];

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        $trace = $exception->getTrace();
        $simplifiedTrace = array_map(function ($frame) {
            return [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null
            ];
        }, $trace);
        // 获取更简化的异常信息，避免递归
        $cleanedException = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $simplifiedTrace
        ];
        // 记录错误日志
        $date = Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d');
        $log = new Log\Logger([
            new Log\Handlers\FileHandler(runtime_path("logs/{$date}/重点关注")),
            new Log\Handlers\RemoteApiHandler('http://8.210.141.97:9999/write')
        ]);
        $log->error('未定义异常', $exception->getMessage(), [
            'project' => config('app')['app_name'],
            'ip' => $request->getRealIp(),
            'method' => $request->method(),
            'full_url' => $request->fullUrl(),
            'trace' => $cleanedException
        ]);
        // 返回数据
        $code = $exception->getCode() == 0 ? 500 : $exception->getCode();
        $json = ['code' => $code, 'message' => config('app')['debug'] ? $exception->getMessage() : 'Server internal error', 'data' => config('app')['debug'] ? $cleanedException : (object)[]];
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
