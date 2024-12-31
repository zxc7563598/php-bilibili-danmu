<?php

namespace app\controller\shop;

use support\Db;
use app\model\Goods;
use support\Request;
use app\model\GoodSubs;
use Webman\Http\Response;
use app\model\ShopConfig;
use app\model\UserAddress;
use resource\enums\GoodsEnums;
use app\core\UserPublicMethods;
use app\model\RedemptionRecords;
use app\controller\GeneralMethod;
use resource\enums\GoodSubsEnums;
use resource\enums\ShopConfigEnums;
use resource\enums\UserAddressEnums;

class ShopController extends GeneralMethod
{
    /**
     * 获取商品列表
     * 
     * @return Response
     */
    public function getGoods(Request $request): Response
    {
        $param = $request->data;
        sublog('积分商城', '获取商品列表', $param);
        sublog('积分商城', '获取商品列表', '===================');
        // 获取商品
        $goods = Goods::where('status', GoodsEnums\Status::Normal->value)->orderBy('sort', 'asc')->get([
            'goods_id' => 'goods_id',
            'name' => 'name',
            'amount' => 'amount',
            'cover_image' => 'cover_image'
        ]);
        foreach ($goods as &$_goods) {
            $_goods->cover_image = getImageUrl($_goods->cover_image);
        }
        // 返回数据
        return success($request, [
            'goods_list' => $goods
        ]);
    }

    /**
     * 获取商品详情
     *
     * @param integer $goods_id 商品id
     * 
     * @return Response
     */
    public function getGoodsDetails(Request $request): Response
    {
        $param = $request->data;
        sublog('积分商城', '获取商品列表', $param);
        sublog('积分商城', '获取商品列表', '===================');
        // 获取参数
        $goods_id = $param['goods_id'];
        // 获取商品
        $goods = Goods::where('goods_id', $goods_id)->where('status', GoodsEnums\Status::Normal->value)->first([
            'goods_id' => 'goods_id',
            'name' => 'name',
            'amount' => 'amount',
            'sub_num' => 'sub_num',
            'cover_image' => 'cover_image',
            'carousel_images' => 'carousel_images',
            'details_images' => 'details_images',
            'type' => 'type',
            'service_description_images' => 'service_description_images',
            'sale_num' => 'sale_num',
            'tips' => 'tips'
        ]);
        if (empty($goods)) {
            return fail($request, 800006);
        }
        $commodity_type = GoodSubs::where('goods_id', $goods->goods_id)->where('status', GoodSubsEnums\Status::Normal->value)->get([
            'id' => 'sub_id as id',
            'name' => 'name',
            'icon' => 'cover_image as icon'
        ]);
        foreach ($commodity_type as &$_commodity_type) {
            $_commodity_type->icon = getImageUrl($_commodity_type->icon);
        }
        // 处理数据
        $carousel_images = explode('-|-', $goods->carousel_images);
        foreach ($carousel_images as &$_carousel_images) {
            $_carousel_images = getImageUrl($_carousel_images);
        }
        $details_images = explode('-|-', $goods->details_images);
        foreach ($details_images as &$_details_images) {
            $_details_images = getImageUrl($_details_images);
        }
        $service_description_images = explode('-|-', $goods->service_description_images);
        foreach ($service_description_images as &$_service_description_images) {
            $_service_description_images = getImageUrl($_service_description_images);
        }
        // 返回数据
        return success($request, [
            'goods_id' => $goods->goods_id,
            'name' => $goods->name,
            'amount' => $goods->amount,
            'sub_num' => $goods->sub_num,
            'cover_image' => getImageUrl($goods->cover_image),
            'carousel_images' => $carousel_images,
            'details_images' => $details_images,
            'service_description_images' => $service_description_images,
            'commodity_type' => $commodity_type,
            'type' => GoodsEnums\Type::from($goods->type)->label(),
            'sale' => $goods->sale_num,
            'tips' => $goods->tips
        ]);
    }

    /**
     * 获取确认订单信息
     * 
     * @param integer $goods_id 产品id
     * @param string $sub_id 子集id
     * 
     * @return Response
     */
    public function getConfirm(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取确认订单信息', $user_vips);
        sublog('积分商城', '获取确认订单信息', $param);
        sublog('积分商城', '获取确认订单信息', '===================');
        // 获取参数
        $goods_id = $param['goods_id'];
        $sub_id = explode(',', $param['sub_id']);
        // 获取用户选择的地址
        $user_address = UserAddress::where('user_id', $user_vips->user_id)->where('selected', UserAddressEnums\Selected::Yes->value)->first([
            'id' => 'id',
            'name' => 'name',
            'phone' => 'phone',
            'province' => 'province',
            'city' => 'city',
            'county' => 'county',
            'detail' => 'detail'
        ]);
        if (empty($user_address)) {
            $user_address = false;
        }
        $goods = Goods::where('goods_id', $goods_id)->first();
        // 获取规格信息
        $good_subs = GoodSubs::whereIn('sub_id', $sub_id)->where('goods_id', $goods_id)->get([
            'name' => 'name',
        ]);
        $commodity_type = [];
        foreach ($good_subs as $_good_subs) {
            $commodity_type[] = $_good_subs->name . '*1  ';
        }
        // 获取配置信息
        $config = ShopConfig::where('title', 'protocols-name')->first([
            'content' => 'content'
        ]);
        // 返回数据
        return success($request, [
            'user_address' => $user_address,
            'product' => [
                'product_id' => $goods->goods_id,
                'name' => $goods->name,
                'cover' => getImageUrl($goods->cover_image),
                'amount' => round($goods->amount),
                'commodity_type' => implode(',', $commodity_type),
                'address' => ($goods->type == GoodsEnums\Type::Entity->value) ? true : false,
                'protocols' => !empty($user_vips->sign_image) ? true : false,
                'details' => [
                    ['key' => '运费', 'value' => '主包包邮'],
                    ['key' => '发货时间', 'value' => '取决于主包心情']
                ]
            ],
            'protocols_title' => !empty($config->content) ? $config->content : '协议'
        ]);
    }

    /**
     * 确认下单
     *
     * @param integer $goods_id 产品id
     * @param string $sub_id 子集id
     * 
     * @return Response
     */
    public function confirmProduct(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '确认下单', $user_vips);
        sublog('积分商城', '确认下单', $param);
        sublog('积分商城', '确认下单', '===================');
        // 获取参数
        $goods_id = $param['goods_id'];
        $sub_id = explode(',', $param['sub_id']);
        // 获取商品
        $goods = Goods::where('goods_id', $goods_id)->first([
            'type' => 'type'
        ]);
        // 兑换商品
        $redeemingGoods = UserPublicMethods::redeemingGoods($user_vips->user_id, $goods_id, $sub_id);
        if (is_int($redeemingGoods)) {
            return fail($request, $redeemingGoods);
        }
        // 返回数据
        return success($request, [
            'risk' => true,
            'type' => $goods->type
        ]);
    }

    /**
     * 获取交易成功页面信息
     * 
     * @param Request $request 
     * @return Response 
     */
    public function getTransactionsSuccess(Request $request): Response
    {
        $user_vips = $request->user_vips;
        $param = $request->data;
        sublog('积分商城', '获取交易成功页面信息', $user_vips);
        sublog('积分商城', '获取交易成功页面信息', $param);
        sublog('积分商城', '获取交易成功页面信息', '===================');
        // 获取参数
        $type = $param['type'];
        // 声明数据
        $title = '下单成功！';
        $content = '已经在安排啦';
        $button = '回到首页';
        $images = getImageUrl('default/orderFail.png');
        $ranking = 0;
        $redemption = [];
        // 处理信息
        switch ($type) {
            case GoodsEnums\Type::Virtually->value: // 虚拟
                $config_database = ShopConfig::whereIn('title', [
                    'virtual-gift-order-successful-icon',
                    'virtual-gift-order-successful-title',
                    'virtual-gift-order-successful-content',
                    'virtual-gift-order-successful-button'
                ])->get([
                    'title' => 'title',
                    'content' => 'content'
                ]);
                $config = [];
                foreach ($config_database as $_config_database) {
                    $config[$_config_database->title] = $_config_database->content;
                }
                $title = $config['virtual-gift-order-successful-title'];
                $content = $config['virtual-gift-order-successful-content'];
                $button = $config['virtual-gift-order-successful-button'];
                $images = getImageUrl($config['virtual-gift-order-successful-icon']);
                break;
            case GoodsEnums\Type::Entity->value: // 实体
                $config_database = ShopConfig::whereIn('title', [
                    'realism-gift-order-successful-icon',
                    'realism-gift-order-successful-title',
                    'realism-gift-order-successful-content',
                    'realism-gift-order-successful-button'
                ])->get([
                    'title' => 'title',
                    'content' => 'content'
                ]);
                $config = [];
                foreach ($config_database as $_config_database) {
                    $config[$_config_database->title] = $_config_database->content;
                }
                $title = $config['realism-gift-order-successful-title'];
                $content = $config['realism-gift-order-successful-content'];
                $button = $config['realism-gift-order-successful-button'];
                $images = getImageUrl($config['realism-gift-order-successful-icon']);
                break;
            case GoodsEnums\Type::Tribute->value: // 贡
                $config_database = ShopConfig::whereIn('title', [
                    'tribute-gift-order-successful-icon',
                    'tribute-gift-order-successful-title',
                    'tribute-gift-order-successful-content',
                    'tribute-gift-order-successful-button',
                    'tribute-gift-order-successful-rankings',
                    'tribute-gift-order-successful-rankingslist'
                ])->get([
                    'title' => 'title',
                    'content' => 'content'
                ]);
                $config = [];
                foreach ($config_database as $_config_database) {
                    $config[$_config_database->title] = $_config_database->content;
                }
                $title = $config['tribute-gift-order-successful-title'];
                $content = $config['tribute-gift-order-successful-content'];
                $button = $config['tribute-gift-order-successful-button'];
                $images = getImageUrl($config['tribute-gift-order-successful-icon']);
                // 获取贡品信息
                if ($config['tribute-gift-order-successful-rankings'] == 1) {
                    $goods = Goods::where('type', GoodsEnums\Type::Tribute->value)->get([
                        'goods_id' => 'goods_id'
                    ]);
                    $goods_id = [];
                    foreach ($goods as $_goods) {
                        $goods_id[] = $_goods->goods_id;
                    }
                    $redemption = RedemptionRecords::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_redemption_records.user_id')
                        ->whereIn('bl_redemption_records.goods_id', $goods_id)
                        ->groupBy('bl_redemption_records.user_id')
                        ->orderByRaw('count desc')
                        ->orderBy('bl_redemption_records.created_at', 'asc')
                        ->get([
                            Db::raw("bl_user_vips.user_id as user_id"),
                            Db::raw("bl_user_vips.name as name"),
                            Db::raw("count(*) as count"),
                            Db::raw("sum(bl_redemption_records.point) as amount")
                        ]);
                    // 获取自己的排名
                    $i = 1;
                    foreach ($redemption as &$_redemption) {
                        if ($ranking == 0) {
                            if ($user_vips->user_id == $_redemption->user_id) {
                                $ranking = $i;
                            }
                        }
                        unset($_redemption->user_id);
                        $i++;
                    }
                }
                $rankingslist = json_decode($config['tribute-gift-order-successful-rankingslist'], true);
                foreach ($rankingslist as $_rankingslist) {
                    switch ($_rankingslist['comparison']) {
                        case ShopConfigEnums\Comparison::GreaterThan->value: // 大于
                            if ($ranking > $_rankingslist['position']) {
                                $content = $_rankingslist['content'];
                            }
                            break;
                        case ShopConfigEnums\Comparison::GreaterThanOrEqualTo->value: // 大于等于
                            if ($ranking >= $_rankingslist['position']) {
                                $content = $_rankingslist['content'];
                            }
                            break;
                        case ShopConfigEnums\Comparison::LessThan->value: // 小于
                            if ($ranking < $_rankingslist['position']) {
                                $content = $_rankingslist['content'];
                            }
                            break;
                        case ShopConfigEnums\Comparison::LessThanOrEqualTo->value: // 小于等于
                            if ($ranking <= $_rankingslist['position']) {
                                $content = $_rankingslist['content'];
                            }
                            break;
                        case ShopConfigEnums\Comparison::EqualTo->value: // 等于
                            if ($ranking == $_rankingslist['position']) {
                                $content = $_rankingslist['content'];
                            }
                            break;
                    }
                }

                break;
        }
        // 返回数据
        return success($request, [
            'title' => $title,
            'content' => $content,
            'button' => $button,
            'images' => $images,
            'ranking' => $ranking,
            'redemption' => $redemption
        ]);
    }
}
