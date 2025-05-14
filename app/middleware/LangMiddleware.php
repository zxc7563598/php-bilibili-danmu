<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class LangMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        locale($request->header('Accept-Language') ?? null);
        return $handler($request);
    }
}
