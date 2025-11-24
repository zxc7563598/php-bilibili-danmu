<?php

namespace app\core;

use app\model\Admins;
use support\Cache;
use resource\enums\AdminsEnums;

/**
 * 管理员认证服务
 * @package app\service
 */
class AdminAuthService
{

    /**
     * 执行登录
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $captcha 验证码
     * 
     * @return string|int 登录凭证｜错误码
     */
    public static function login(string $username, string $password, string $captcha = ''): string|int
    {
        // 查询账号
        $admins = Admins::where('username', $username)->first();
        if (empty($admins)) {
            return 900006;
        }
        if ($admins->enable == AdminsEnums\Enable::Disable->value) {
            return 900007;
        }
        // 验证密码
        if (sha1(sha1($password) . $admins->salt) != $admins->password) {
            return 900006;
        }
        // 生成token
        $token = md5(mt_rand(1000, 9999) . uniqid(md5(microtime(true)), true));
        // 删除先前的token信息
        if ($admins->token) {
            Cache::delete($admins->token);
        }
        $admins->token = $token;
        $admins->save();
        Cache::set($token, json_encode($admins), 86400 * 7);
        // 返回数据
        return $token;
    }

    /**
     * 退出登录
     * 
     * @param integer $id 退出用户id
     * 
     * @return void 
     */
    public static function logout(int $id): void
    {
        $admins = Admins::where('id', $id)->first();
        $token = $admins->token;
        $admins->token = null;
        $admins->save();
        Cache::delete($token);
    }
}
