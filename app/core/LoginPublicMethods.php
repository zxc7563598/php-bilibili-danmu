<?php

namespace app\core;

use app\controller\GeneralMethod;
use app\model\UserVips;
use Carbon\Carbon;
use resource\enums\UserVipsEnums;
use Hejunjie\Utils;

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
        self::updatingUserProfiles($uid);
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
            // 获取用户名称与头像
            $getMasterInfo = Utils\HttpClient::sendGetRequest('https://api.live.bilibili.com/live_user/v1/Master/info?uid=' . $user_vips->uid, [
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                "Origin: https://live.bilibili.com",
            ], 10);
            if ($getMasterInfo['httpStatus'] == 200) {
                $getMasterInfoData = json_decode($getMasterInfo['data'], true);
            }
            if (isset($getMasterInfoData['data']['info']['uname']) && isset($getMasterInfoData['data']['info']['face'])) {
                if ($user_vips->name != $getMasterInfoData['data']['info']['uname']) {
                    $user_vips->name = $getMasterInfoData['data']['info']['uname'];
                }
                $file_name = pathinfo($getMasterInfoData['data']['info']['face'], PATHINFO_FILENAME);
                $path = public_path('attachment/user-info/' . implode('/', str_split(Utils\Str::padString(0, $user_vips->user_id), 2)) . '/avatar/');
                $image_path = Utils\Img::downloadImageFromUrl($getMasterInfoData['data']['info']['face'], $path, $file_name);
                $user_vips->avatar = Utils\Str::replaceFirst(public_path() . '/attachment/', '', $image_path);
                $user_vips->save();
            }
        }
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
