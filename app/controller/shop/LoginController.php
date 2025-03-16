<?php

namespace app\controller\shop;

use support\Redis;
use support\Request;
use app\model\UserVips;
use Webman\Http\Response;
use app\core\LoginPublicMethods;
use resource\enums\UserVipsEnums;
use app\controller\GeneralMethod;

class LoginController extends GeneralMethod
{

    /**
     * 获取商城主题色
     * 
     * @return Response 
     */
    public function getThemeColor(Request $request): Response
    {
        // 获取数据
        $config = self::getShopConfig();
        $theme_color = explode(',', $config['theme-color']);
        $color = isset($theme_color[0]) ? $theme_color[0] : '#7232dd';
        // 返回数据
        return success($request, [
            $color,
            isset($theme_color[1]) ? $theme_color[1] : $color
        ]);
    }

    /**
     * 获取登录页配置
     * 
     * @return Response 
     */
    public function getConfig(Request $request): Response
    {
        // 获取数据
        $config = self::getShopConfig();
        // 返回数据
        return success($request, [
            'background' => !empty($config['login-background-image']) ? getImageUrl($config['login-background-image']) : null,
            'password' => isset($config['user-login-password']) ? ($config['user-login-password'] == 0 ? false : true) : true
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
        sublog('积分商城用户端', '获取用户是否存在', '入参', $param);
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
        sublog('积分商城用户端', '执行登录', '入参', $param);
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
        // 跳过密码
        $skip = false;
        $config = self::getShopConfig();
        if (isset($config['user-login-password']) && $config['user-login-password'] == 0) {
            $skip = true;
        }
        if (!$skip) {
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
        // 获取链接
        $config = self::getShopConfig();
        // 返回处理
        return success($request, [
            'uid' => $user_vips->uid,
            'uname' => $user_vips->name,
            'point' => $user_vips->point,
            'type' => UserVipsEnums\VipType::from($user_vips->vip_type)->label(),
            'avatar' => !empty($user_vips->avatar) ? getImageUrl($user_vips->avatar) : getImageUrl('shop-config/supreme.png'),
            'link' => !empty($config['live-streaming-link']) ? $config['live-streaming-link'] : 'javascript:;',
            'icon' => [
                'pay_log' => getImageUrl('icon/pay_log.png'),
                'convertible' => getImageUrl('icon/convertible.png'),
                'address' => getImageUrl('icon/address.png'),
                'complain' => getImageUrl('icon/complain.png')
            ]
        ]);
    }
}
