<?php

namespace app\exception;

use Carbon\Carbon;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;
use support\exception\BusinessException;
use Hejunjie\ErrorLog\Logger;
use Hejunjie\ErrorLog\Handlers;

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
        if ($this->shouldntReport($exception)) {
            return;
        }
        $request = request();
        $date = Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d');
        (new Logger([
            new Handlers\FileHandler(runtime_path("logs/{$date}/重点关注")),
            new Handlers\RemoteApiHandler('http://8.210.141.97:9999/write')
        ]))->error('未定义异常', $exception->getMessage(), [
            'project' => config('app')['app_name'],
            'ip' => $request->getRealIp(),
            'method' => $request->method(),
            'full_url' => $request->fullUrl(),
            'trace' => $this->getDebugData($exception)
        ]);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        $isDebug = config('app')['debug'] == 1;
        $statusCode = $this->getHttpStatusCode($exception);
        $response = [
            'code' => $this->getErrorCode($exception),
            'message' => $isDebug ? $exception->getMessage() : 'Server Error',
            'data' => $isDebug ? $this->getDebugData($exception) : new \stdClass()
        ];
        if ($requestId = $request->header('X-Request-ID')) {
            $response['request_id'] = $requestId;
        }
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function getHttpStatusCode(Throwable $exception): int
    {
        $code = (int)$exception->getCode();
        return ($code >= 100 && $code < 600) ? $code : 500;
    }

    protected function getErrorCode(Throwable $exception): string
    {
        return (string)$exception->getCode() ?: '500';
    }

    protected function getDebugData(Throwable $exception): array
    {
        $trace = $exception->getTrace();
        $simplifiedTrace = array_map(function ($frame) {
            return [
                'file' => $frame['file'] ?? '[internal function]',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null
            ];
        }, $trace);
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => (string)$exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $simplifiedTrace,
            'previous' => $exception->getPrevious() ? [
                'class' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage()
            ] : null
        ];
    }
}
