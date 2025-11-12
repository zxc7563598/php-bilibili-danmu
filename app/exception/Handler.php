<?php

namespace app\exception;

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

    public function report(Throwable $exception)
    {
        parent::report($exception);
        $request = request();
        Logger::reportSync($exception, 'https://error.hejunjie.life/write', 'bilibili-danmu', [
            'request_url' => $request->fullUrl() ?? '',
            'method'      => $request->method() ?? '',
            'data' => json_encode($request->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?? ''
        ]);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        $isDebug = config('app')['debug'] == 1;
        $statusCode = $this->getHttpStatusCode($exception);
        $response = [
            'code' => $this->getErrorCode($exception),
            'message' => $isDebug ? $exception->getMessage() : 'Server Error',
            'data' => $isDebug ? Logger::formatThrowable($exception, 'bilibili-danmu', [
                'request_url' => $request->fullUrl() ?? '',
                'method'      => $request->method() ?? ''
            ]) : new \stdClass()
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
}
