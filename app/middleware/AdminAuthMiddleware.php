<?php

namespace app\middleware;

use Hejunjie\EncryptedRequest\EncryptedRequestHandler;
use support\Cache;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AdminAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 获取路由数据
        $route = $request->route;
        $path = $route->getPath();
        // 如果路由是 /upload，直接跳过认证
        if (in_array($path, [
            '/admin-api-v2/mall-configuration/upload-images',
            '/admin-api-v2/shop-management/product-management/upload-images',
        ])) {
            return $next($request);
        }
        $param = $request->all();
        $handler = new EncryptedRequestHandler(['RSA_PRIVATE_KEY' => file_get_contents(base_path('private_key.pem'))]);
        try {
            $request->data = $handler->handle(
                (string)$param['en_data'] ?? '',
                (string)$param['enc_payload'] ?? '',
                (int)$param['timestamp'] ?? 0,
                (string)$param['sign'] ?? ''
            );
        } catch (\Hejunjie\EncryptedRequest\Exceptions\SignatureException $e) {
            return fail($request, 900002);
        } catch (\Hejunjie\EncryptedRequest\Exceptions\TimestampException $e) {
            return fail($request, 900003);
        } catch (\Hejunjie\EncryptedRequest\Exceptions\DecryptionException $e) {
            return fail($request, 900004);
        }
        // 验证用户登录
        $token = $request->header('X-Auth-Token') ?? null;
        $request->admins = null;
        $whitelisting = [
            '/admin-api-v2/auth/login',
            '/admin-api-v2/projects/leave-message/send',
            '/admin-api-v2/projects/record',
            '/admin-api-v2/projects/pdf'
        ];
        if (!in_array($path, $whitelisting)) {
            if (empty($token)) {
                return fail($request, 900004);
            }
        }
        if (!empty($token)) {
            $loginCheck = self::loginCheck($token);
            if (is_int($loginCheck)) {
                if (!in_array($path, $whitelisting)) {
                    return fail($request, $loginCheck);
                }
            } else {
                $request->admins = $loginCheck;
            }
        }
        return $next($request);
    }

    public static function loginCheck($token): int|array
    {
        $admins = Cache::get($token);
        return !empty($admins) ? json_decode($admins, true) : 900005;
    }
}
