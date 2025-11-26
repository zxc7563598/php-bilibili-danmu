<?php

namespace app\middleware;

use Hejunjie\EncryptedRequest\EncryptedRequestHandler;
use Carbon\Carbon;
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
            $decoded = $handler->handle(
                (string)$param['en_data'] ?? '',
                (string)$param['enc_payload'] ?? '',
                (int)$param['timestamp'] ?? 0,
                (string)$param['sign'] ?? ''
            );
            $post = [];
            foreach ($decoded as $key => $value) {
                $post[$key] = $value;
            }
            $request->setPost($post);
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
        // 从缓存获取用户信息
        $adminsDataJson = Cache::get($token);
        if (empty($adminsDataJson)) {
            return 900005;
        }
        $data = json_decode($adminsDataJson, true);
        // 确保 timestamp 存在且为整数
        $currentTimestamp = Carbon::now()->timezone(config('app.default_timezone'))->timestamp;
        if (!empty($data['timestamp']) && ($currentTimestamp - (int)$data['timestamp']) > 86400 * 3) {
            // 超过 3 天，更新缓存 timestamp 并延长缓存有效期
            $data['timestamp'] = $currentTimestamp;
            Cache::set($token, json_encode($data), 86400 * 7);
        }
        return $data;
    }
}
