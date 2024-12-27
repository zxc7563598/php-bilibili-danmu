<?php

namespace app\controller\shop;

use app\controller\GeneralMethod;
use app\core\UserPublicMethods;
use app\model\Goods;
use app\model\GoodSubs;
use app\model\RedemptionRecords;
use app\model\ShopConfig;
use app\model\UserAddress;
use support\Request;
use Webman\Http\Response;
use resource\enums\GoodsEnums;
use resource\enums\GoodSubsEnums;
use resource\enums\UserAddressEnums;
use support\Db;

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
        $ranking = 0;
        $redemption = [];
        $title = '下单成功！';
        $content = '已经在安排啦';
        $button = '回到首页';
        $images = getImageUrl('order/orderFail.png');
        // 处理信息
        switch ($type) {
            case GoodsEnums\Type::Virtually->value: // 虚拟
                $title = '下单成功！';
                $content = '已经在给你准备啦！记得抓紧来找我要嗷～';
                $button = '啊啊啊啊啊我来了我来了';
                $images = getImageUrl('order/orderFail.png');
                break;
            case GoodsEnums\Type::Entity->value: // 实体
                $title = '下单成功！';
                $content = '已经收到通知啦，很快就会发货嘿嘿嘿';
                $button = '我自愿体谅主包的辛苦，晚点发也可以';
                $images = getImageUrl('order/orderFail.png');
                break;
            case GoodsEnums\Type::Tribute->value: // 贡
                $title = '...';
                // 获取信息
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
                $content = '你连垃圾都不如';
                if ($ranking <= 10) {
                    $content = "你以为把积分上供就能挨骂了？\r\n你什么都得不到，废物东西\r\n就算是废物都有被我踩在脚底下的价值\r\n你那点积分就跟你一样一点用都没有\r\n犯贱就继续上，让我看看你个废物东西能有多贱";
                }
                if ($ranking <= 5) {
                    $content = "你可真是个垃圾\r\n怎么，花钱给主播上供让你感觉很好吗？\r\n才上到个第" . $ranking . "名，你也就这点能耐了，没用的东西\r\n这么爱上就多上点，让我看看你废物到什么程度";
                }
                if ($ranking == 1) {
                    $content = "上供都上这么勤快真贱啊，废物玩意\r\n你也就只配上供了知道吗臭傻逼\r\n给我多去赚点积分，在这个第一大傻逼的位子上待着\r\n方便我什么时候心情好了骂你两句";
                }
                $button = '呜呜呜主人我会继续努力的';
                $images = getImageUrl('order/orderFail.png');
                break;
        }
        // 返回数据
        return success($request, [
            'title' => $title,
            'content' => $content,
            'button' => $button,
            'ranking' => $ranking,
            'images' => $images,
            'redemption' => $redemption
        ]);
    }
}
