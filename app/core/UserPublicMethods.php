<?php

namespace app\core;

use app\controller\GeneralMethod;
use app\model\Goods;
use app\model\GoodSubs;
use app\model\PaymentRecords;
use app\model\RedemptionRecords;
use app\model\ShopConfig;
use app\model\UserAddress;
use app\model\UserVips;
use resource\enums\GoodsEnums;
use resource\enums\UserVipsEnums;
use resource\enums\UserAddressEnums;
use resource\enums\PaymentRecordsEnums;
use resource\enums\RedemptionRecordsEnums;
use yzh52521\mailer\Mailer;

class UserPublicMethods extends GeneralMethod
{
    /**
     * 兑换商品
     *
     * @param string $user_id 用户id
     * @param string $goods_id 商品id
     * @param array $sub_id 款式
     * 
     * @return bool|integer
     */
    public static function redeemingGoods($user_id, $goods_id, $sub_id): bool|int
    {
        $user_vips = UserVips::where('user_id', $user_id)->first();
        $goods = Goods::where('goods_id', $goods_id)->where('status', GoodsEnums\Status::Normal->value)->first();
        if (empty($goods)) {
            return 800006;
        }
        $good_subs = GoodSubs::whereIn('sub_id', $sub_id)->where('goods_id', $goods->goods_id)->get([
            'sub_id' => 'sub_id',
            'name' => 'name'
        ]);
        $sub = [];
        $sub_name = [];
        foreach ($good_subs as $_good_subs) {
            $sub[] = $_good_subs->sub_id;
            $sub_name[] = $_good_subs->name;
        }
        if (count($sub) != count($sub_id)) {
            return 800008;
        }
        $user_address = UserAddress::where('user_id', $user_id)->where('selected', UserAddressEnums\Selected::Yes->value)->first();
        if (($user_vips->point - $goods->amount) < 0) {
            return 800007;
        }
        if ($goods->type == GoodsEnums\Type::Entity->value) {
            $shipping_address = !empty($user_address) ? ($user_address->province . '/' . $user_address->city . '/' . $user_address->county . '/' . $user_address->detail) : null;
            $shipping_name = !empty($user_address) ? ($user_address->name) : null;
            $shipping_phone = !empty($user_address) ? ($user_address->phone) : null;
        } else {
            $shipping_address = '虚拟礼物';
            $shipping_name = '虚拟礼物';
            $shipping_phone = '虚拟礼物';
        }
        // 增加兑换记录
        $redemption_records = new RedemptionRecords();
        $redemption_records->user_id = $user_id;
        $redemption_records->goods_id = $goods_id;
        $redemption_records->sub_id = implode(',', $sub);
        $redemption_records->point = $goods->amount;
        $redemption_records->pre_point = $user_vips->point;
        $redemption_records->status = RedemptionRecordsEnums\Status::NoShipment->value;
        $redemption_records->after_point = $user_vips->point - $goods->amount;
        $redemption_records->shipping_address = $shipping_address;
        $redemption_records->shipping_name = $shipping_name;
        $redemption_records->shipping_phone = $shipping_phone;
        $redemption_records->save();
        // 发送邮件
        $subject = UserVipsEnums\VipType::from($user_vips->vip_type)->label() . $user_vips->name . ', uid:' . $user_vips->uid . '兑换商品';
        $set_html_body = '<p>兑换商品：' . $goods->name . '</p>';
        $set_html_body .= '<p>兑换规格：' . implode(',', $sub_name) . '</p>';
        $set_html_body .= '<p>配送到：' . $redemption_records->shipping_address . '</p>';
        $set_html_body .= '<p>姓名：' . $redemption_records->shipping_name . '</p>';
        $set_html_body .= '<p>手机号：' . $redemption_records->shipping_phone . '</p>';
        $set_html_body .= '<p>用户兑换后剩余积分：' . $redemption_records->after_point . '</p>';
        $set_html_body .= '<p>详细信息请进入积分商城查看</p>';
        sublog('邮件发送', '商品兑换', $subject);
        sublog('邮件发送', '商品兑换', $set_html_body);
        // $mailer = Mailer::setFrom(['992182040@qq.com' => "商品兑换"])
        //     ->setTo('junjie.he.925@gmail.com')
        //     ->setCc('482335887@qq.com')
        //     ->setSubject($subject)
        //     ->setHtmlBody($set_html_body)
        //     ->send();
        // sublog('邮件发送', '商品兑换', '发送结果');
        // sublog('邮件发送', '商品兑换', $mailer);
        // sublog('邮件发送', '商品兑换', '----------');
        // 返回成功
        return true;
    }

    /**
     * 舰长付费
     *
     * @param string $uid uid
     * @param string $name 名称
     * @param string $guard_level 开通类型
     * @param string $amount 金额
     * @param string $payment_at 上舰时间
     * @param string $live_key 上舰时间
     * 
     * @return void
     */
    public static function userOpensVip($uid, $name, $guard_level, $amount, $payment_at, $live_key)
    {
        $config = ShopConfig::whereIn('title', [
            'listening-open-vip',
            'vip-lv3-bonus-points',
            'vip-lv2-bonus-points',
            'vip-lv1-bonus-points'
        ])->get([
            'title' => 'title',
            'content' => 'content'
        ]);
        $shop_config = [];
        foreach ($config as $_config) {
            $shop_config[$_config->title] = $_config->content;
        }
        if (!empty($shop_config['listening-open-vip']) && $shop_config['listening-open-vip'] == 1) {
            $user_vips = UserVips::where('uid', $uid)->first();
            if (empty($user_vips)) {
                LoginPublicMethods::userRegister($uid, $name);
                $user_vips = UserVips::where('uid', $uid)->first();
            }
            $user_vips->name = $name;
            $user_vips->save();
            // 获取需要增加的积分
            $point = 0;
            switch ($guard_level) {
                case 1: // 总督
                    $point = !empty($shop_config['vip-lv3-bonus-points']) ? $shop_config['vip-lv3-bonus-points'] : 0;
                    $vip_type = PaymentRecordsEnums\VipType::Lv3->value;
                    break;
                case 2: // 提督
                    $point = !empty($shop_config['vip-lv2-bonus-points']) ? $shop_config['vip-lv2-bonus-points'] : 0;
                    $vip_type = PaymentRecordsEnums\VipType::Lv2->value;
                    break;
                case 3: // 舰长
                    $point = !empty($shop_config['vip-lv1-bonus-points']) ? $shop_config['vip-lv1-bonus-points'] : 0;
                    $vip_type = PaymentRecordsEnums\VipType::Lv1->value;
                    break;
            }
            // 增加兑换记录
            $payment_records = new PaymentRecords();
            $payment_records->user_id = $user_vips->user_id;
            $payment_records->vip_type = $vip_type;
            $payment_records->amount = intval($amount);
            $payment_records->point = $point;
            $payment_records->pre_point = $user_vips->point;
            $payment_records->after_point = $payment_records->pre_point + $point;
            $payment_records->live_key = $live_key;
            $payment_records->payment_at = $payment_at;
            $payment_records->save();
        }
        // 返回成功
        return true;
    }
}
