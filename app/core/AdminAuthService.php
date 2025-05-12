<?php

namespace app\core;

use app\model\Admins;
use app\cache\AdminLoginCache;
use app\model\AdminRoles;
use app\model\Roles;
use Carbon\Carbon;
use Hejunjie\Cache;
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
        // 存储登录信息
        if ($admins->token) {
            $cache = self::getCache();
            $cache->del($admins->token);
        }
        $admins->token = $token;
        $admins->save();
        // 返回数据
        return $token;
    }

    /**
     * 退出登录
     * 
     * @param integer $id 推出用户id
     * @return void 
     */
    public static function logout(string $id)
    {
        $admins = Admins::where('id', $id)->first();
        $token = $admins->token;
        $admins->token = null;
        $admins->save();
        $cache = self::getCache();
        $cache->del($token);
    }

    /**
     * 获取缓存实例
     * 
     * @return object 
     */
    public static function getCache(): object
    {
        return new Cache\RedisCache(
            new Cache\FileCache(
                new AdminLoginCache(),
                runtime_path('admin/login'),
                (3600 * 24 * 7)
            ),
            [
                'host' => config('redis')['default']['host'],
                'port' => config('redis')['default']['port'],
                'password' => !empty(config('redis')['default']['password']) ? config('redis')['default']['password'] : null,
                'db' => config('redis')['default']['database'],
                'ttl' => (3600 * 24),
            ],
            'admin:token:',
            true
        );
    }
}
