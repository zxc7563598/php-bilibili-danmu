<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace app\middleware;

use Hejunjie\EncryptedRequest\EncryptedRequestHandler;
use Carbon\Carbon;
use support\Cache;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

/**
 * Api鉴权
 * @package app\middleware
 */
class ApiAuthCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 获取路由数据
        $route = $request->route;
        $path = $route->getPath();
        $param = $request->post();
        $handler = new EncryptedRequestHandler(['RSA_PRIVATE_KEY' => file_get_contents(base_path('private_key.pem'))]);
        try {
            $decoded = $handler->handle(
                (string)$param['en_data'] ?? '',
                (string)$param['enc_payload'] ?? '',
                (string)$param['timestamp'] ?? '',
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
        $request->user_vips = [];
        $token = isset($param['token']) ? $param['token'] : null;
        $whitelisting = [
            '/api/shop/login/get-user-vip',
            '/api/shop/login/perform-login',
            '/api/shop/login/get-config',
            '/api/shop/login/get-theme-color'
        ];
        // 不在白名单的接口需要token才能访问
        if (!in_array($path, $whitelisting)) {
            if (empty($token)) {
                return fail($request, 800004);
            }
        }
        // 验证token有效性
        if (!empty($token)) {
            $loginCheck = self::loginCheck($token);
            if (is_int($loginCheck)) {
                if (!in_array($path, $whitelisting)) {
                    return fail($request, $loginCheck);
                }
            } else {
                $request->user_vips = $loginCheck;
            }
        }
        // 继续处理
        $response = $next($request);
        return $response;
    }

    public static function loginCheck($token): int|array
    {
        // 从缓存获取用户信息
        $userDataJson = Cache::get($token);
        if (empty($userDataJson)) {
            return 800004;
        }
        $data = json_decode($userDataJson, true);
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
