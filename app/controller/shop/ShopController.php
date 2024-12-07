<?php

namespace app\controller\shop;

use app\controller\GeneralMethod;
use app\core\UserPublicMethods;
use app\model\Goods;
use app\model\GoodSubs;
use app\model\RedemptionRecords;
use app\model\UserAddress;
use Carbon\Exceptions\InvalidTimeZoneException;
use InvalidArgumentException;
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
        $user_vips = $request->user_vips;
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
        $user_vips = $request->user_vips;
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
            'service_description_images' => 'service_description_images'
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
        $tips = '';
        switch ($goods->type) {
            case GoodsEnums\Type::Virtually->value:
                $tips = "该礼物为虚拟礼物，下单后将有人与您联系，请留意私信";
                break;
            case GoodsEnums\Type::Tribute->value:
                $tips = "希望您可以支付全部的积分，即使您不会得到任何东西";
                break;
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
            'tips' => $tips
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
        // 返回数据
        return success($request, [
            'user_address' => $user_address,
            'product' => [
                'product_id' => $goods->goods_id,
                'name' => $goods->name,
                'cover' => getImageUrl($goods->cover_image),
                'amount' => round($goods->amount),
                'commodity_type' => implode(',', $commodity_type),
                'freight_fee' => '主包包邮',
                'address' => ($goods->type == GoodsEnums\Type::Virtually->value || $goods->type == GoodsEnums\Type::Tribute->value) ? false : true
            ]
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
     * 上供排名
     * 
     * @return Response 
     */
    public function dedicationRanking(Request $request): Response
    {
        $user_vips = $request->user_vips;
        sublog('积分商城', '上供排名', $user_vips);
        sublog('积分商城', '上供排名', '===================');
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
        $ranking = 0;
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
        // 返回数据
        return success($request, [
            'redemption' => $redemption,
            'ranking' => $ranking
        ]);
    }

    /**
     * 获取商品列表
     * 
     * @param Request $request 
     * @return Response 
     */
    public function getProductList(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取商品列表', $user_vips);
        sublog('积分商城', '获取商品列表', $param);
        sublog('积分商城', '获取商品列表', '===================');
        // 验证添加权限
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            return fail($request, 800010);
        }
        // 获取商品列表
        $goods = Goods::orderBy('sort', 'asc')->get([
            'goods_id' => 'goods_id',
            'cover_image' => 'cover_image',
            'name' => 'name',
            'status' => 'status',
            'type' => 'type'
        ]);
        // 处理数据
        foreach ($goods as $_goods) {
            $_goods->status = GoodsEnums\Status::from($_goods->status)->label();
            $_goods->type = GoodsEnums\Type::from($_goods->type)->label();
            $_goods->cover_image = getImageUrl($_goods->cover_image);
        }
        // 返回数据
        return success($request, [
            'goods' => $goods
        ]);
    }

    /**
     * 获取变更商品信息
     * 
     * @param Request $request 
     * @return Response 
     */
    public function getProductDetails(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取变更商品信息', $user_vips);
        sublog('积分商城', '获取变更商品信息', $param);
        sublog('积分商城', '获取变更商品信息', '===================');
        // 获取参数
        $goods_id = !empty($param['goods_id']) ? $param['goods_id'] : null;
        // 验证添加权限
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            return fail($request, 800010);
        }
        // 获取商品信息
        $goods = Goods::where('goods_id', $goods_id)->first([
            'goods_id' => 'goods_id',
            'name' => 'name',
            'amount' => 'amount',
            'sub_num' => 'sub_num',
            'cover_image' => 'cover_image',
            'carousel_images' => 'carousel_images', // 
            'details_images' => 'details_images', // 
            'service_description_images' => 'service_description_images', // 
            'status' => 'status',
            'type' => 'type',
            'sort' => 'sort'
        ]);
        $carousel_images = [];
        $details_images = [];
        $service_description_images = [];
        $sub = [];
        $sub_list = [];
        if (!empty($goods)) {
            if (!empty($goods->carousel_images)) {
                $carousel = explode('-|-', $goods->carousel_images);
                foreach ($carousel as $_carousel) {
                    $carousel_images[] = [
                        'path' => $_carousel,
                        'url' => getImageUrl($_carousel)
                    ];
                }
            }
            if (!empty($goods->details_images)) {
                $details = explode('-|-', $goods->details_images);
                foreach ($details as $_details) {
                    $details_images[] = [
                        'path' => $_details,
                        'url' => getImageUrl($_details)
                    ];
                }
            }
            if (!empty($goods->service_description_images)) {
                $service_description = explode('-|-', $goods->service_description_images);
                foreach ($service_description as $_service_description) {
                    $service_description_images[] = [
                        'path' => $_service_description,
                        'url' => getImageUrl($_service_description)
                    ];
                }
            }
            $sub = GoodSubs::where('goods_id', $goods->goods_id)->get([
                'sub_id' => 'sub_id',
                'name' => 'name',
                'cover_image' => 'cover_image',
                'status' => 'status'
            ]);
            foreach ($sub as &$_sub) {
                $sub_list[] = [
                    'sub_id' => $_sub->sub_id,
                    'name' => $_sub->name,
                    'cover_image' => [
                        'path' => !empty($_sub->cover_image) ? $_sub->cover_image : null,
                        'url' => !empty($_sub->cover_image) ? getImageUrl($_sub->cover_image) : null
                    ],
                    'status' => $_sub->status
                ];
            }
        }
        // 返回数据
        return success($request, [
            'record' => [
                'goods_id' => !empty($goods) ? $goods->goods_id : null,
                'name' => !empty($goods) ? $goods->name : null,
                'amount' => !empty($goods) ? $goods->amount : null,
                'sub_num' => !empty($goods) ? $goods->sub_num : null,
                'sub' => $sub_list,
                'cover_image' => [
                    'path' => !empty($goods->cover_image) ? $goods->cover_image : null,
                    'url' => !empty($goods->cover_image) ? getImageUrl($goods->cover_image) : null
                ],
                'carousel_images' => $carousel_images,
                'details_images' => $details_images,
                'service_description_images' => $service_description_images,
                'status' => !empty($goods) ? $goods->status : null,
                'type' => !empty($goods) ? $goods->type : null,
                'sort' => !empty($goods) ? $goods->sort : null,
            ],
            'enumeration' => [
                'status' => GoodsEnums\Status::all(),
                'type' => GoodsEnums\Type::all(),
                'sub_status' => GoodSubsEnums\Status::all()
            ]
        ]);
    }

    /**
     * 变更商品
     * 
     * @param integer $goods_id 商品id
     * @param string $name 商品名称
     * @param string $amount 积分
     * @param array $sub 规格
     * @param array $cover_image 封面图
     * @param array $carousel_images 轮播图
     * @param array $details_images 商品详情图
     * @param array $service_description_images 服务说明图
     * @param integer $type 类型
     * @param integer $status 状态
     * @param integer $sort 排序
     * 
     * @return Response 
     */
    public function setProduct(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '变更商品', $user_vips);
        sublog('积分商城', '变更商品', $param);
        sublog('积分商城', '变更商品', '===================');
        // 获取参数
        $goods_id = !empty($param['goods_id']) ? $param['goods_id'] : null;
        $name = $param['name'];
        $amount = $param['amount'];
        $sub = $param['sub'];
        $cover_image = $param['cover_image'][0];
        $carousel_images = $param['carousel_images'];
        $details_images = $param['details_images'];
        $service_description_images = $param['service_description_images'];
        $status = $param['status'];
        $type = $param['type'];
        $sub_num = $param['sub_num'];
        $sort = $param['sort'];
        // 验证添加权限
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            return fail($request, 800010);
        }
        // 处理数据
        if (!count($sub)) {
            return fail($request, 800011);
        }
        if (!count($carousel_images)) {
            return fail($request, 800011);
        }
        if (!count($details_images)) {
            return fail($request, 800011);
        }
        if (!count($service_description_images)) {
            return fail($request, 800011);
        }
        // 录入信息
        $goods = new Goods();
        if (!empty($goods_id)) {
            $goods = Goods::where('goods_id', $goods_id)->first();
        }
        $goods->name = $name;
        $goods->amount = $amount;
        $goods->sub_num = $sub_num;
        $goods->cover_image = $cover_image;
        $goods->carousel_images = implode('-|-', $carousel_images);
        $goods->details_images = implode('-|-', $details_images);
        $goods->service_description_images = implode('-|-', $service_description_images);
        $goods->status = $status;
        $goods->type = $type;
        $goods->sort = $sort;
        $goods->save();
        // 录入或变更子商品信息
        foreach ($sub as $_sub) {
            $goods_subs = new GoodSubs();
            if (!empty($_sub['sub_id'])) {
                $goods_subs = GoodSubs::where('sub_id', $_sub['sub_id'])->first();
            }
            $goods_subs->goods_id = $goods->goods_id;
            $goods_subs->name = $_sub['name'];
            $goods_subs->cover_image = $_sub['cover_image'][0];
            $goods_subs->status = $_sub['status'];
            $goods_subs->save();
        }
        // 返回数据
        return success($request, []);
    }
}
