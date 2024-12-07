<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Hejunjie\Tools;

class BasicAuthMiddleware implements MiddlewareInterface
{

    // 实现 MiddlewareInterface 的 process 方法
    public function process(Request $request, callable $next): Response
    {
        $authHeader = $request->header('authorization');
        // 验证 Basic Auth 头部信息
        if (!$authHeader || !$this->validateAuth($authHeader)) {
            return new Response(401, ['WWW-Authenticate' => 'Basic realm="Protected Area"'], 'Unauthorized');
        }
        // 验证通过，继续处理请求
        return $next($request);
    }

    private function validateAuth($authHeader): bool
    {
        if (preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
            list($username, $password) = explode(':', base64_decode($matches[1]), 2);
            $account = readFileContent(runtime_path() . '/tmp/account.cfg');
            if ($account) {
                $account = json_decode($account, true);
            } else {
                $account = ['username' => $username, 'password' => $password];
                Tools\FileUtils::fileDelete(runtime_path() . '/tmp/account.cfg');
                Tools\FileUtils::writeToFile(runtime_path() . '/tmp/account.cfg', json_encode($account, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
            }
            return $username === $account['username'] && $password === $account['password'];
        }
        return false;
    }
}
