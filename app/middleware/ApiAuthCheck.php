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

use app\model\UserVips;
use Hejunjie\EncryptedRequest\EncryptedRequestHandler;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\Redis;

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
                $param['en_data'] ?? '',
                $param['enc_payload'] ?? '',
                $param['timestamp'] ?? '',
                $param['sign'] ?? ''
            );
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
        $request->user_vips = null;
        $token = isset($param['token']) ? $param['token'] : null;
        $whitelisting = [
            '/api/shop/login/get-user-vip',
            '/api/shop/login/perform-login',
            '/api/shop/login/get-config',
            '/api/shop/login/get-theme-color'
        ];
        if (!in_array($path, $whitelisting)) {
            if (empty($token)) {
                return fail($request, 900005);
            }
        }
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

    /**
     * 检查用户登陆信息
     *
     * @param string $token 管理员登陆凭证
     * 
     * @return object|int
     */
    public static function loginCheck($token)
    {
        $uinfo = Redis::get(config('app')['app_name'] . ':token_to_info:vip:' . $token);
        if (empty($uinfo)) {
            return 800004;
        }
        $uinfo = unserialize($uinfo);
        $uid = !empty($uinfo['uid']) ? (string)$uinfo['uid'] : null;
        $users = UserVips::where('uid', $uid)->first();
        if (empty($users)) {
            return 800004;
        }
        return $users;
    }
}
