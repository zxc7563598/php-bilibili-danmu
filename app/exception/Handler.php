<?php

namespace app\exception;

use Carbon\Carbon;
use Hejunjie\Lazylog\Logger;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;
use support\exception\BusinessException;

/**
 * Class Handler
 * @package support\exception
 */
class Handler extends ExceptionHandler
{
    public $dontReport = [
        BusinessException::class,
    ];

    /**
     * 记录日志
     *
     * @param Throwable $exception 异常信息
     * 
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
        if ($this->shouldntReport($exception)) {
            return;
        }
        $request = request();
        $date = Carbon::now()->timezone(config('app.default_timezone'))->format('Y-m-d');
        // 写本地日志
        Logger::write(runtime_path("logs/{$date}"), '重点关注', $exception->getMessage(), [
            'ip' => $request->getRealIp(),
            'method' => $request->method(),
            'full_url' => $request->fullUrl(),
            'class' => get_class($exception),
            'code'  => $exception->getCode(),
            'file'  => $exception->getFile() . ':' . $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
        // 错误信息推送
        if (config('app.error_report_url')) {
            Logger::reportSync($exception, config('app.error_report_url'), 'bilibili-danmu', [
                'ip' => $request->getRealIp(),
                'method' => $request->method(),
                'full_url' => $request->fullUrl(),
                'data' => json_encode($request->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?? ''
            ]);
        }
    }

    /**
     * 渲染返回
     *
     * @param Request $request 请求信息
     * @param Throwable $exception 异常信息
     * 
     * @return Response
     */
    public function render(Request $request, Throwable $exception): Response
    {
        $isDebug = in_array(config('app.debug'), [1, true, "1", "true"]);
        // 业务异常：始终展示错误消息给用户，生产环境也如此
        if ($exception instanceof BusinessException) {
            $code = (int)$exception->getCode() ?: 500;
            $response = [
                'code' => $code,
                'message' => $exception->getMessage() ?: (trans(config('code')[$code]) ?? 'error'),
                'data' => $isDebug ? ($exception->getData() ?: new \stdClass()) : new \stdClass()
            ];
            return new Response(
                $code,
                ['Content-Type' => 'application/json'],
                json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)
            );
        }
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
            json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)
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
            'code' => $exception->getCode(),
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
