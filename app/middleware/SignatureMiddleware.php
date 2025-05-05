<?php

namespace app\middleware;

use app\model\RequestLog;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

/**
 * 鉴权
 * @package app\middleware
 */
class SignatureMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $signature = $request->header('X-Signature');
        $secretKey = getenv('SECURE_API_KEY');

        $body = $request->rawBody();
        $computedSignature = hash_hmac('sha256', $body, $secretKey);

        if (hash_equals($computedSignature, $signature)) {
            $route = $request->route;
            $path = $route->getPath();
            switch ($path) {
                case '/api/points-mall/system-configuration/set-data':
                case '/api/points-mall/mall-configuration/set-data':
                case '/api/points-mall/user-management/set-data':
                case '/api/points-mall/user-management/set-user-point':
                case '/api/points-mall/product-management/set-data-details':
                case '/api/points-mall/shipping-management/set-data-details':
                    $request_log = new RequestLog();
                    $request_log->path = $path;
                    $request_log->json = json_encode($request->all(), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
                    $request_log->save();
                    break;
            }
            return $next($request);
        }

        return response('Invalid signature', 403);
    }
}
