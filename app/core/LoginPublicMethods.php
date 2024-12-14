<?php

namespace app\core;

use app\controller\GeneralMethod;
use app\model\UserVips;
use Carbon\Carbon;
use resource\enums\UserVipsEnums;

class LoginPublicMethods extends GeneralMethod
{

    /**
     * 商城用户注册
     *
     * @param string $uid 手机号
     * @param integer $channel_id 渠道
     * @param string $referrer 来源链接
     * 
     * @return int|bool
     */
    public static function userRegister($uid, $name = null): int|bool
    {
        // 创建用户
        $user_vip = new UserVips();
        $user_vip->uid = $uid;
        $user_vip->name = !empty($name) ? $name : '潜在老头';
        $user_vip->vip_type = UserVipsEnums\VipType::Lv0->value;
        $user_vip->point = 0;
        $user_vip->save();
        return true;
    }

    /**
     * APP登录
     *
     * @param string $uid 手机号
     * 
     * @return integer|array
     */
    public static function userLogin($uid): int|array
    {
        $user_vip = UserVips::where('uid', $uid)->first();
        if (empty($user_vip)) {
            return 800005;
        }
        // 创建token执行登录
        $token = self::createToken();
        $setToken = self::setToken($token, [
            'uid' => $user_vip->uid,
            'name' => $user_vip->name,
            'timestamp' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp
        ], 'vip');
        if (!$setToken) {
            return 900005;
        }
        // 返回数据 
        return [
            'user_id' => $user_vip->user_id,
            'token' => $token
        ];
    }

    /**
     * APP退出登录
     *
     * @param string $token 用户登录凭证
     * 
     * @return void
     */
    public static function userLogoutLogin($token)
    {
        self::delToken($token, 'vip');
    }
}
