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

use app\model\Users;
use app\model\UserVips;
use Carbon\Carbon;
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
        $param = $request->all();
        // 验证时间是否正确
        $difference = Carbon::now()->timezone(config('app')['default_timezone'])->diffInSeconds(Carbon::parse((int)$param['timestamp'])->timezone(config('app')['default_timezone']));
        if ($difference > 300) {
            return fail($request, 900001);
        }
        // 验证签名
        if (md5(config('app')['key'] . $param['timestamp']) != $param['sign']) {
            return fail($request, 900002, [
                'str' => config('app')['key'] . $param['timestamp'],
                'md5' => md5(config('app')['key'] . $param['timestamp'])
            ]);
        }
        // 解密数据
        $data = openssl_decrypt($param['en_data'], 'aes-128-cbc', config('app')['aes_key'], 0, config('app')['aes_iv']);
        if (!$data) {
            return fail($request, 900003);
        }
        // 完成签名验证，没问题，透传account_id与appid
        $request->data = json_decode($data, true);
        // 验证用户登录
        $request->user_vips = null;
        $token = isset($param['token']) ? $param['token'] : null;
        $whitelisting = [
            '/api/shop/login/get-user-vip',
            '/api/shop/login/perform-login',
            '/api/shop/login/get-login-background',
            '/api/shop/login/get-theme-color'
        ];
        if (!in_array($path, $whitelisting)) {
            if (empty($token)) {
                return fail($request, 900006);
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
