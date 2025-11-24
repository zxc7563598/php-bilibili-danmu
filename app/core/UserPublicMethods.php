<?php

namespace app\core;

use app\controller\GeneralMethod;
use app\model\DanmuLogs;
use app\model\GiftRecords;
use app\model\Goods;
use app\model\GoodSubs;
use app\model\Lives;
use app\model\PaymentRecords;
use app\model\RedemptionRecords;
use app\model\ShopConfig;
use app\model\UserAddress;
use app\model\UserVips;
use Carbon\Carbon;
use resource\enums\GoodsEnums;
use resource\enums\UserAddressEnums;
use resource\enums\PaymentRecordsEnums;
use resource\enums\RedemptionRecordsEnums;
use Hejunjie\Utils;
use support\Db;

class UserPublicMethods extends GeneralMethod
{
    /**
     * 兑换商品
     *
     * @param string $user_id 用户id
     * @param string $goods_id 商品id
     * @param array $sub_id 款式
     * @param string $email 邮箱地址
     * 
     * @return bool|integer
     */
    public static function redeemingGoods($user_id, $goods_id, $sub_id, $email): bool|int
    {
        $user_vips = UserVips::where('user_id', $user_id)->first();
        if (!empty($email)) {
            $user_vips->email = $email;
            $user_vips->save();
        }
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
        $after_point = 0;
        switch ($goods->amount_type) {
            case GoodsEnums\AmountType::Point->value:
                $after_point = $user_vips->point - $goods->amount;
                break;
            case GoodsEnums\AmountType::Coin->value:
                $after_point = $user_vips->coin - $goods->amount;
                break;
        }
        if ($after_point < 0) {
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
        $redemption_records->amount_type = $goods->amount_type;
        $redemption_records->point = $goods->amount;
        $redemption_records->pre_point = $user_vips->point;
        $redemption_records->status = RedemptionRecordsEnums\Status::NoShipment->value;
        $redemption_records->after_point = $after_point;
        $redemption_records->shipping_address = $shipping_address;
        $redemption_records->shipping_name = $shipping_name;
        $redemption_records->shipping_phone = $shipping_phone;
        $redemption_records->shipping_email = $email;
        $redemption_records->save();
        // 发送邮件
        $shop_config = self::getShopConfig();
        if ($shop_config['enable-shop-mail'] && $shop_config['email-address'] && $shop_config['address-as']) {
            // 获取用户历史兑换
            $history = [];
            $redemption_records_logs = RedemptionRecords::where('user_id', $user_id)->orderBy('created_at', 'desc')->get([
                'goods_id' => 'goods_id',
                'sub_id' => 'sub_id',
                'created_at' => 'created_at'
            ]);
            foreach ($redemption_records_logs as $_redemption_records_logs) {
                $goods = Goods::where('goods_id', $_redemption_records_logs->goods_id)->first([
                    'name' => 'name'
                ]);
                $subs = GoodSubs::whereIn('sub_id', explode(',', $_redemption_records_logs->sub_id))->get([
                    'name' => 'name'
                ]);
                $subs_name = [];
                foreach ($subs as $_subs) {
                    $subs_name[] = $_subs->name;
                }
                $history[] = [
                    'goods_name' => $goods->name,
                    'sub_name' => implode(',', $subs_name),
                    'time' => $_redemption_records_logs->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s')
                ];
            }
            // 发送邮件
            Utils\HttpClient::sendPostRequest('https://tools.api.hejunjie.life/bilibilidanmu-api/shop-email', [
                'Content-Type: application/json'
            ], json_encode([
                'mail' => $shop_config['email-address'],
                'name' => $shop_config['address-as'],
                'uid' => $user_vips->uid,
                'uname' => $user_vips->name,
                'gift_type' => $goods->type,
                'gift_name' => $goods->name,
                'sub_name' => implode(',', $sub_name),
                'point' => $redemption_records->after_point,
                'history' => $history
            ]));
        }
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
     * @param string $live_key 直播间key
     * 
     * @return void
     */
    public static function userOpensVip($uid, $name, $guard_level, $amount, $payment_at, $live_key): void
    {
        sublog('核心业务/记录舰长付费', '舰长付费', [
            'uid' => $uid,
            'name' => $name,
            'guard_level' => $guard_level,
            'amount' => $amount,
            'payment_at' => $payment_at,
            'live_key' => $live_key
        ]);
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
            $payment_records->amount = round(($amount / 100), 2);
            $payment_records->point = $point;
            $payment_records->pre_point = $user_vips->point;
            $payment_records->after_point = $payment_records->pre_point + $point;
            $payment_records->live_key = $live_key;
            $payment_records->payment_at = $payment_at;
            $payment_records->save();
        }
    }

    /**
     * 下播邮件发送
     * 
     * @param string $live_id 直播记录id
     * 
     * @return void 
     */
    public static function aggregateMail($live_id): void
    {
        // 获取配置信息
        $config = ShopConfig::whereIn('title', [
            'enable-aggregate-mail',
            'listening-open-vip',
            'email-address',
            'address-as'
        ])->get([
            'title' => 'title',
            'content' => 'content'
        ]);
        $shop_config = [];
        foreach ($config as $_config) {
            $shop_config[$_config->title] = $_config->content;
        }
        if (!empty($shop_config['enable-aggregate-mail']) && $shop_config['enable-aggregate-mail']) {
            if (!empty($shop_config['email-address']) && !empty($shop_config['address-as'])) {
                // 获取直播信息
                $lives = Lives::where('live_id', $live_id)->first([
                    'live_id' => 'live_id',
                    'live_key' => 'live_key',
                    'created_at' => 'created_at',
                    'end_time' => 'end_time',
                    'danmu_path' => 'danmu_path',
                    'gift_path' => 'gift_path'
                ]);
                // 获取大航海数据
                $open_list = [];
                $payment_records = PaymentRecords::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_payment_records.user_id')
                    ->where('bl_payment_records.live_key', $lives->live_key)
                    ->get([
                        'uid' => 'bl_user_vips.uid',
                        'name' => 'bl_user_vips.name',
                        'time' => 'bl_payment_records.payment_at as time',
                        'type' => 'bl_payment_records.vip_type as type',
                        'point' => 'bl_payment_records.point as point',
                        'pre_point' => 'bl_payment_records.pre_point as pre_point',
                        'after_point' => 'bl_payment_records.after_point as after_point',
                    ]);
                foreach ($payment_records as $_payment_records) {
                    $open_list[] = [
                        'uid' => $_payment_records->uid,
                        'name' => $_payment_records->name,
                        'pre_point' => $_payment_records->pre_point,
                        'point' => $_payment_records->point,
                        'after_point' => $_payment_records->after_point,
                        'time' => Carbon::parse($_payment_records->time)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
                        'type' => PaymentRecordsEnums\VipType::from($_payment_records->type)->label()
                    ];
                }
                // 分析弹幕数据
                $danmu_list = [];
                $getTopSpeakers = DanmuLogs::whereBetween('send_at', [
                    $lives->created_at->timezone(config('app')['default_timezone'])->timestamp,
                    Carbon::parse($lives->end_time)->timezone(config('app')['default_timezone'])->timestamp
                ])->groupBy('uid')->orderByRaw('count(*) desc')->get([
                    'uid' => 'uid',
                    'uname' => 'uname',
                    'count' => Db::raw('count(*) as count')
                ]);
                $danmu_count = 0;
                foreach ($getTopSpeakers as $_getTopSpeakers) {
                    $danmu_count += 1;
                    if (count($danmu_list) < 10) {
                        $danmu_list[] = [
                            'uid' => $_getTopSpeakers['uid'],
                            'name' => $_getTopSpeakers['uname'],
                            'count' => $_getTopSpeakers['count']
                        ];
                    }
                }
                // 分析礼物数据
                $gift_list = [];
                $getTopSpenders = GiftRecords::whereBetween('created_at', [
                    $lives->created_at->timezone(config('app')['default_timezone'])->timestamp,
                    Carbon::parse($lives->end_time)->timezone(config('app')['default_timezone'])->timestamp
                ])->groupBy('uid')->orderByRaw('sum(total_price) desc')->get([
                    'uid' => 'uid',
                    'uname' => 'uname',
                    'count' => Db::raw('sum(total_price) as count')
                ]);
                $gift_total_price = 0;
                foreach ($getTopSpenders as $_getTopSpenders) {
                    $gift_total_price += $_getTopSpenders['count'];
                    if (count($gift_list) < 10) {
                        $gift_list[] = [
                            'uid' => $_getTopSpenders['uid'],
                            'name' => $_getTopSpenders['uname'],
                            'count' => $_getTopSpenders['count'],
                        ];
                    }
                }
                // 发送邮件
                Utils\HttpClient::sendPostRequest('https://tools.api.hejunjie.life/bilibilidanmu-api/live-end-email', [
                    'Content-Type: application/json'
                ], json_encode([
                    'mail' => $shop_config['email-address'],
                    'name' => $shop_config['address-as'],
                    'starting_time' => $lives->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
                    'end_time' => Carbon::parse($lives->end_time)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
                    'listening_open_vip' => $shop_config['listening-open-vip'],
                    'open_list' => $open_list,
                    'danmu_list' => $danmu_list,
                    'danmu_count' => $danmu_count,
                    'gift_list' => $gift_list,
                    'gift_count' => round($gift_total_price, 2)
                ]));
            }
        }
    }
}
