<?php

namespace app\middleware;

use app\core\AdminAuthService;
use Carbon\Carbon;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class ExpiredAdminAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 获取路由数据
        $route = $request->route;
        $path = $route->getPath();
        // 如果路由是 /upload，直接跳过认证
        if (in_array($path, [
            '/admin-api/mall-configuration/upload-images',
            '/admin-api/shop-management/product-management/upload-images',
        ])) {
            return $handler($request);
        }
        // 获取请求参数
        $param = $request->all();
        // 验证签名
        if (!isset($param['timestamp']) || !isset($param['sign'])) {
            return fail($request, 900001);
        }
        // 验证签名
        if (md5(config('app')['key'] . $param['timestamp']) != $param['sign']) {
            return fail($request, 900002);
        }
        // 验证时间是否正确
        $difference = Carbon::now()->timezone(config('app')['default_timezone'])->diffInSeconds(Carbon::parse((int)$param['timestamp'])->timezone(config('app')['default_timezone']));
        if ($difference > 60) {
            return fail($request, 900003);
        }
        // 解密数据
        $data = openssl_decrypt($param['en_data'], 'aes-128-cbc', config('app')['aes_key'], 0, config('app')['aes_iv']);
        if (!$data) {
            return fail($request, 900004);
        }
        // 完成签名验证,传递数据
        $request->data = json_decode($data, true);
        // 验证用户登录
        $token = $request->header('X-Auth-Token') ?? null;
        $request->admins = null;
        $whitelisting = [
            '/admin-api/auth/login',
            '/admin-api/projects/leave-message/send',
            '/admin-api/projects/record',
            '/admin-api/projects/pdf'
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
        if (config('app')['debug'] == 1) {
            sublog('接口调用', $route->getName(), $request->method(), [
                'data' => $request->data,
                'admins' => $request->admins
            ]);
        }
        return $handler($request);
    }

    public static function loginCheck($token): int|array
    {
        $cache = AdminAuthService::getCache();
        $admins = $cache->get($token);
        return !empty($admins) ? json_decode($admins, true) : 900005;
    }
}
