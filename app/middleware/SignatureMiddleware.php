<?php

namespace app\middleware;

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
            return $next($request);
        }

        return response('Invalid signature', 403);
    }
}
