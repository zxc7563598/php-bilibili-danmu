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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://error.hejunjie.life/write');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // 设置请求头
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        // 设置请求数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->formatThrowable($exception, [
            'request_url' => $_SERVER['REQUEST_URI'] ?? '',
            'method'      => $_SERVER['REQUEST_METHOD'] ?? '',
            'data' => json_encode($request->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?? ''
        ]));
        curl_exec($ch);
        curl_close($ch);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        $isDebug = config('app')['debug'] == 1;
        $statusCode = $this->getHttpStatusCode($exception);
        $response = [
            'code' => $this->getErrorCode($exception),
            'message' => $isDebug ? $exception->getMessage() : 'Server Error',
            'data' => $isDebug ? $this->formatThrowable($exception, [
                'request_url' => $_SERVER['REQUEST_URI'] ?? '',
                'method'      => $_SERVER['REQUEST_METHOD'] ?? '',
                'data' => json_encode($request->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?? ''
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

    function formatThrowable(Throwable $e, array $context = []): string
    {
        // 清理并标准化 trace 结构
        $trace = array_map(static function ($t) {
            return [
                'file'     => isset($t['file']) ? (string)$t['file'] : null,
                'line'     => isset($t['line']) ? (int)$t['line'] : null,
                'function' => isset($t['function']) ? (string)$t['function'] : null,
                'class'    => isset($t['class']) ? (string)$t['class'] : null,
            ];
        }, $e->getTrace() ?? []);
        // 构造完整结构
        $data = [
            'uuid'        => bin2hex(random_bytes(8)),
            'project'     => 'bilibili-danmu',
            'level'       => 'error',
            'timestamp'   => date('c'),
            'message'     => (string)$e->getMessage(),
            'code'        => (int)$e->getCode(),
            'file'        => (string)$e->getFile(),
            'line'        => (int)$e->getLine(),
            'trace'       => $trace,
            'context'     => (object)$context,
            'server'      => [
                'hostname'    => gethostname() ?: 'unknown',
                'ip'          => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
                'php_version' => PHP_VERSION,
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
