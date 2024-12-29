<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use app\model\ShopConfig;
use Hejunjie\Tools;
use support\Request;

class MallConfigurationController extends GeneralMethod
{
    public function getData(Request $request)
    {
        // 获取数据
        $shop_config = ShopConfig::get();
        // 处理数据
        $data = [];
        foreach ($shop_config as $_shop_config) {
            switch ($_shop_config->title) {
                case 'login-background-image':
                case 'personal-background-image':
                case 'protocols-signature':
                case 'virtual-gift-order-successful-icon':
                case 'realism-gift-order-successful-icon':
                case 'tribute-gift-order-successful-icon':
                    $data[$_shop_config->title] = [
                        'url' => getImageUrl($_shop_config->content),
                        'path' => $_shop_config->content
                    ];
                    break;
                case 'theme-color':
                    $data[$_shop_config->title] = explode(',', $_shop_config->content);
                    break;
                case 'tribute-gift-order-successful-rankingslist':
                    $data[$_shop_config->title] = json_decode($_shop_config->content, true);
                    break;
                default:
                    $data[$_shop_config->title] = $_shop_config->content;
                    break;
            }
        }
        // 返回数据
        return success($request, [
            'login_background_image' => $data['login-background-image'], // 登录页面背景图
            'personal_background_image' => $data['personal-background-image'], // 个人中心背景图
            'theme_color' => $data['theme-color'], // 主题色
            'live_streaming_link' => $data['live-streaming-link'], // 直播间链接
            'protocols_surname' => $data['protocols-surname'], // 协议人姓名
            'protocols_uid' => $data['protocols-uid'], // 协议人UID
            'protocols_name' => $data['protocols-name'], // 协议名称
            'protocols_signature' => $data['protocols-signature'], // 协议人签名
            'protocols_content' => $data['protocols-content'], // 协议内容
            'listening_open_vip' => $data['listening-open-vip'], // 大航海监听
            'vip_lv1_bonus_points' => $data['vip-lv1-bonus-points'], // 开通舰长奖励积分
            'vip_lv2_bonus_points' => $data['vip-lv2-bonus-points'], // 开通提督奖励积分
            'vip_lv3_bonus_points' => $data['vip-lv3-bonus-points'], // 开通总督奖励积分
            'virtual_gift_order_successful_icon' => $data['virtual-gift-order-successful-icon'], // 虚拟礼物下单成功图标
            'virtual_gift_order_successful_title' => $data['virtual-gift-order-successful-title'], // 虚拟礼物下单成功标题
            'virtual_gift_order_successful_content' => $data['virtual-gift-order-successful-content'], // 虚拟礼物下单成功内容
            'virtual_gift_order_successful_button' => $data['virtual-gift-order-successful-button'], // 虚拟礼物下单成功按钮
            'realism_gift_order_successful_icon' => $data['realism-gift-order-successful-icon'], // 实体礼物下单成功图标
            'realism_gift_order_successful_title' => $data['realism-gift-order-successful-title'], // 实体礼物下单成功标题
            'realism_gift_order_successful_content' => $data['realism-gift-order-successful-content'], // 实体礼物下单成功内容
            'realism_gift_order_successful_button' => $data['realism-gift-order-successful-button'], // 实体礼物下单成功按钮
            'tribute_gift_order_successful_icon' => $data['tribute-gift-order-successful-icon'], // 贡品下单成功图标
            'tribute_gift_order_successful_title' => $data['tribute-gift-order-successful-title'], // 贡品下单成功标题
            'tribute_gift_order_successful_content' => $data['tribute-gift-order-successful-content'], // 贡品下单成功内容
            'tribute_gift_order_successful_button' => $data['tribute-gift-order-successful-button'], // 贡品下单成功按钮
            'tribute_gift_order_successful_rankings' => $data['tribute-gift-order-successful-rankings'], // 贡品下单成功是否开启排名
            'tribute_gift_order_successful_rankingslist' => $data['tribute-gift-order-successful-rankingslist'], // 贡品下单成功排名列表
        ]);
    }

    public function setData(Request $request)
    {
        $param = $request->all();
        $input = [];
        $input['login-background-image'] = $param['login_background_image']; // 登录页面背景图
        $input['personal-background-image'] = $param['personal_background_image']; // 个人中心背景图
        $input['theme-color'] = $param['theme_color']; // 主题色
        $input['live-streaming-link'] = $param['live_streaming_link']; // 直播间链接
        $input['protocols-surname'] = $param['protocols_surname']; // 协议人姓名
        $input['protocols-uid'] = $param['protocols_uid']; // 协议人UID
        $input['protocols-name'] = $param['protocols_name']; // 协议名称
        $input['protocols-signature'] = $param['protocols_signature']; // 协议人签名
        $input['protocols-content'] = $param['protocols_content']; // 协议内容
        $input['listening-open-vip'] = $param['listening_open_vip']; // 大航海监听
        $input['vip-lv1-bonus-points'] = $param['vip_lv1_bonus_points']; // 开通舰长奖励积分
        $input['vip-lv2-bonus-points'] = $param['vip_lv2_bonus_points']; // 开通提督奖励积分
        $input['vip-lv3-bonus-points'] = $param['vip_lv3_bonus_points']; // 开通总督奖励积分
        $input['virtual-gift-order-successful-icon'] = $param['virtual_gift_order_successful_icon']; // 虚拟礼物下单成功图标
        $input['virtual-gift-order-successful-title'] = $param['virtual_gift_order_successful_title']; // 虚拟礼物下单成功标题
        $input['virtual-gift-order-successful-content'] = $param['virtual_gift_order_successful_content']; // 虚拟礼物下单成功内容
        $input['virtual-gift-order-successful-button'] = $param['virtual_gift_order_successful_button']; // 虚拟礼物下单成功按钮
        $input['realism-gift-order-successful-icon'] = $param['realism_gift_order_successful_icon']; // 实体礼物下单成功图标
        $input['realism-gift-order-successful-title'] = $param['realism_gift_order_successful_title']; // 实体礼物下单成功标题
        $input['realism-gift-order-successful-content'] = $param['realism_gift_order_successful_content']; // 实体礼物下单成功内容
        $input['realism-gift-order-successful-button'] = $param['realism_gift_order_successful_button']; // 实体礼物下单成功按钮
        $input['tribute-gift-order-successful-icon'] = $param['tribute_gift_order_successful_icon']; // 贡品下单成功图标
        $input['tribute-gift-order-successful-title'] = $param['tribute_gift_order_successful_title']; // 贡品下单成功标题
        $input['tribute-gift-order-successful-content'] = $param['tribute_gift_order_successful_content']; // 贡品下单成功内容
        $input['tribute-gift-order-successful-button'] = $param['tribute_gift_order_successful_button']; // 贡品下单成功按钮
        $input['tribute-gift-order-successful-rankings'] = $param['tribute_gift_order_successful_rankings']; // 贡品下单成功是否开启排名
        $input['tribute-gift-order-successful-rankingslist'] = $param['tribute_gift_order_successful_rankingslist']; // 贡品下单成功排名列表
        // 获取数据
        $shop_config = ShopConfig::get([
            'config_id' => 'config_id',
            'title' => 'title',
            'description' => 'description',
            'content' => 'content'
        ]);
        // 处理数据
        foreach ($shop_config as $_shop_config) {
            foreach ($input as $title => $content) {
                if ($_shop_config->title == $title) {
                    switch ($_shop_config->title) {
                        case 'login-background-image':
                        case 'personal-background-image':
                        case 'protocols-signature':
                        case 'virtual-gift-order-successful-icon':
                        case 'realism-gift-order-successful-icon':
                        case 'tribute-gift-order-successful-icon':
                            $content = $content['path'];
                            break;
                        case 'theme-color':
                            $content = implode(',', $content);
                            break;
                        case 'tribute-gift-order-successful-rankingslist':
                            $content = json_encode($content, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
                            break;
                    }
                    if ($_shop_config->content != $content) {
                        $_shop_config->content = $content;
                        $_shop_config->save();
                    }
                }
            }
        }
        // 返回数据
        return success($request, []);
    }

    public function uploadImages(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $base64 = $param['base64'];
        $type = $param['type'];
        // base64存储图片 
        $path = public_path('attachment/shop-config/');
        switch ($type) {
            case 'login_background_image':
                $path = public_path('attachment/shop-config/login-background-image/');
                break;
            case 'personal_background_image':
                $path = public_path('attachment/shop-config/personal-background-image/');
                break;
            case 'protocols_signature':
                $path = public_path('attachment/shop-config/protocols-signature/');
                break;
            case 'virtual_gift_order_successful_icon':
                $path = public_path('attachment/shop-config/virtual-gift-order-successful-icon/');
                break;
            case 'realism_gift_order_successful_icon':
                $path = public_path('attachment/shop-config/realism-gift-order-successful-icon/');
                break;
            case 'tribute_gift_order_successful_icon':
                $path = public_path('attachment/shop-config/tribute-gift-order-successful-icon/');
                break;
        }
        $base64ToImage = Tools\Img::base64ToImage($base64, $path);
        $image_path = Tools\Str::replaceFirst(public_path() . '/attachment/', '', $base64ToImage);
        // 返回数据
        return success($request, [
            'path' => $image_path,
            'url' => getImageUrl($image_path)
        ]);
    }
}
