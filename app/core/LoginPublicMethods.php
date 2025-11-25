<?php

namespace app\core;

use app\controller\GeneralMethod;
use app\model\UserVips;
use Carbon\Carbon;
use resource\enums\UserVipsEnums;
use Hejunjie\Utils;
use Hejunjie\Bililive;
use support\Cache;

class LoginPublicMethods
{

    /**
     * 商城用户注册
     *
     * @param string $uid 用户uid
     * @param string $name 用户名称
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
        self::updatingUserProfiles($uid);
        return true;
    }

    /**
     * APP登录
     *
     * @param string $uid 用户uid
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
        $token = md5(mt_rand(1000, 9999) . uniqid(md5(microtime(true)), true));
        // 删除先前的token信息
        if ($user_vip->token) {
            Cache::delete($user_vip->token);
        }
        // 存储token
        Cache::set($token, json_encode([
            'user_id' => $user_vip->user_id,
            'uid' => $user_vip->uid,
            'name' => $user_vip->name,
            'timestamp' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp
        ]), 86400 * 7);
        $user_vip->token = $token;
        $user_vip->save();
        // 更新用户信息
        self::updatingUserProfiles($uid);
        // 返回数据 
        return [
            'user_id' => $user_vip->user_id,
            'token' => $token
        ];
    }

    /**
     * 更新用户头像/名称信息
     * 
     * @param integer $uid 用户uid
     * 
     * @return void
     */
    public static function updatingUserProfiles($uid): void
    {
        // 获取用户信息
        $user_vips = UserVips::where('uid', $uid)->first();
        if (!empty($user_vips)) {
            $getMasterInfo = Bililive\Live::getMasterInfo($user_vips->uid);
            // 名称更新
            if (!empty($getMasterInfo['name']) && $user_vips->name != $getMasterInfo['name']) {
                $user_vips->name = $getMasterInfo['name'];
            }
            // 头像更新
            if (!empty($getMasterInfo['face'])) {
                $file_name = pathinfo($getMasterInfo['face'], PATHINFO_FILENAME);
                $path = public_path('attachment/user-info/' . implode('/', str_split(Utils\Str::padString(0, $user_vips->user_id), 2)) . '/avatar/');
                $image_path = Utils\Img::downloadImageFromUrl($getMasterInfo['face'], $path, $file_name);
                $user_vips->avatar = Utils\Str::replaceFirst(public_path() . '/attachment/', '', $image_path);
            }
            $user_vips->save();
        }
    }

    /**
     * APP退出登录
     *
     * @param string $uid 用户uid
     * 
     * @return void
     */
    public static function userLogoutLogin($uid)
    {
        $user_vips = UserVips::where('uid', $uid)->first();
        $token = $user_vips->token;
        $user_vips->token = null;
        $user_vips->save();
        Cache::delete($token);
    }
}
