<?php

namespace app\controller;

use support\Redis;

class GeneralMethod
{
    /**
     * 创建用户token
     *
     * @return string
     */
    protected static function createToken(): string
    {
        $str = md5(mt_rand(1000, 9999) . uniqid(md5(microtime(true)), true));
        return $str;
    }

    /**
     * 检查用户
     *
     * @param string $token 用户token
     * @param string $type token类型
     * @return void
     */
    protected static function checkToken($token, $type)
    {
        $uinfo = Redis::get(config('app')['app_name'] . ':token_to_info:' . $type . ':' . $token);
        if (empty($uinfo)) {
            return 800004;
        }
        $uinfo = unserialize($uinfo);
        return !empty($uinfo['uid']) ? (string)$uinfo['uid'] : null;
    }

    /**
     * token挂载
     *
     * @param string $token 用户token
     * @param array $uinfo 用户信息
     * @param string $type token类型
     * @return bool
     */
    protected static function setToken($token, $uinfo, $type): bool
    {
        if (empty($token) || empty($uinfo['uid'])) {
            return false;
        }
        $uid = $uinfo['uid'];
        $user = serialize($uinfo);
        $user_token = Redis::get(config('app')['app_name'] . ':uid_to_token:' . $type . ':' . $uid);
        if (!empty($user_token)) {
            self::delToken($user_token, $type);
        }
        // token_to_info
        // uid_to_token
        Redis::set(config('app')['app_name'] . ':uid_to_token:' . $type . ':' . $uid, $token);
        return Redis::set(config('app')['app_name'] . ':token_to_info:' . $type . ':' . $token, $user);
    }

    /**
     * 删除token
     *
     * @param string $token 需要删除的token
     * @param string $type token类型
     * @return void
     */
    protected static function delToken($token, $type): void
    {
        $user = Redis::get(config('app')['app_name'] . ':token_to_info:' . $type . ':' . $token);
        if (!empty($user)) {
            Redis::del(config('app')['app_name'] . ':token_to_info:' . $type . ':' . $token);
            $user = unserialize($user);
            Redis::del(config('app')['app_name'] . ':uid_to_token:' . $type . ':' . $user['uid']);
        }
    }
}
