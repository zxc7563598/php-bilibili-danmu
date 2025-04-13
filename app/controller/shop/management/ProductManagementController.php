<?php

namespace app\controller\shop\management;

use Hejunjie\Utils;
use support\Request;
use app\model\Goods;
use support\Response;
use app\model\GoodSubs;
use resource\enums\GoodsEnums;
use app\controller\GeneralMethod;
use resource\enums\GoodSubsEnums;

class ProductManagementController extends GeneralMethod
{
    /**
     * 获取商品信息
     * 
     * @param integer $page 页码
     * @param string $name 商品名称
     * @param integer $type 商品类型
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $page = $param['page'];
        $name = $param['name'] ?? null;
        $type = $param['type'] ?? null;
        // 获取数据
        $goods = Goods::query();
        if (!is_null($name)) {
            $goods = $goods->where('name', 'like', '%' . $name . '%');
        }
        if (!is_null($type)) {
            $goods = $goods->where('type', $type);
        }
        $goods = $goods->orderBy('sort', 'asc')
            ->paginate(100, [
                'goods_id' => 'goods_id',
                'name' => 'name',
                'amount' => 'amount',
                'cover_image' => 'cover_image',
                'status' => 'status',
                'type' => 'type',
                'sort' => 'sort'
            ], 'page', $page);
        // 处理数据
        foreach ($goods as &$_goods) {
            $_goods->cover_image = getImageUrl($_goods->cover_image);
            $_goods->status = GoodsEnums\Status::from($_goods->status)->label();
            $_goods->type = GoodsEnums\Type::from($_goods->type)->label();
        }
        // 返回数据
        return success($request, [
            'list' => pageToArray($goods)
        ]);
    }

    /**
     * 获取商品详细信息
     * 
     * @param integer $goods_id 商品ID
     * 
     * @return Response 
     */
    public function getDataDetails(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $goods_id = $param['goods_id'];
        // 获取商品数据
        $goods = Goods::where('goods_id', $goods_id)->first([
            'goods_id' => 'goods_id',
            'name' => 'name',
            'amount' => 'amount',
            'sub_num' => 'sub_num',
            'tips' => 'tips',
            'cover_image' => 'cover_image',
            'carousel_images' => 'carousel_images',
            'details_images' => 'details_images',
            'service_description_images' => 'service_description_images',
            'status' => 'status',
            'type' => 'type',
            'sort' => 'sort',
            'sale_num' => 'sale_num',
            'sale_increase' => 'sale_increase'
        ]);
        if (!$goods) {
            return fail($request, 800013);
        }
        // 处理图片
        $carousel_images = array_map(fn($img) => ['url' => getImageUrl($img), 'path' => $img], explode('-|-', $goods->carousel_images));
        $details_images = array_map(fn($img) => ['url' => getImageUrl($img), 'path' => $img], explode('-|-', $goods->details_images));
        $service_description_images = array_map(fn($img) => ['url' => getImageUrl($img), 'path' => $img], explode('-|-', $goods->service_description_images));
        // 获取规格
        $goods_sub = GoodSubs::where('goods_id', $goods->goods_id)
            ->where('status', GoodSubsEnums\Status::Normal->value)
            ->get([
                'name' => 'name',
                'cover_image' => 'cover_image'
            ]);
        $subs = $goods_sub->map(fn($sub) => [
            'icon' => ['url' => getImageUrl($sub->cover_image), 'path' => $sub->cover_image],
            'name' => $sub->name
        ])->toArray();
        // 返回数据
        return success($request, [
            'goods' => [
                'goods_id' => $goods->goods_id,
                'name' => $goods->name,
                'amount' => $goods->amount,
                'sub_num' => $goods->sub_num,
                'tips' => $goods->tips,
                'cover_image' => ['url' => getImageUrl($goods->cover_image), 'path' => $goods->cover_image],
                'carousel_images' => $carousel_images,
                'details_images' => $details_images,
                'service_description_images' => $service_description_images,
                'status' => $goods->status,
                'type' => $goods->type,
                'sort' => $goods->sort,
                'sale_num' => $goods->sale_num,
                'sale_increase' => $goods->sale_increase,
                'subs' => $subs
            ]
        ]);
    }

    /**
     * 上传图片
     * 
     * @param string $base64 图片base64
     * @param string $type 图片类型
     * 
     * @return Response 
     */
    public function uploadImages(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $base64 = $param['base64'];
        $type = $param['type'];
        // 定义不同类型的路径
        $pathMap = [
            'cover_image' => 'attachment/goods/cover_image/',
            'carousel_images' => 'attachment/goods/carousel_images/',
            'details_images' => 'attachment/goods/details_images/',
            'service_description_images' => 'attachment/goods/service_description_images/',
            'sub_cover_image' => 'attachment/goods/sub_cover_image/',
        ];
        $path = $pathMap[$type] ?? 'attachment/shop-config/';
        // 转换base64为图片
        $base64ToImage = Utils\Img::base64ToImage($base64, public_path($path));
        $image_path = Utils\Str::replaceFirst(public_path() . '/attachment/', '', $base64ToImage);
        return success($request, [
            'path' => $image_path,
            'url' => getImageUrl($image_path)
        ]);
    }

    /**
     * 变更商品信息
     * 
     * @param integer $goods_id 商品ID
     * @param string $name 商品名称
     * @param string $amount 商品价格
     * @param string $sub_num 规格选择数量
     * @param string $tips 购买说明
     * @param string $cover_image 封面图
     * @param string $carousel_images 商品展示图（多个）
     * @param string $details_images 详情图（多个）
     * @param string $service_description_images 服务说明图（多个）
     * @param integer $status 状态
     * @param integer $type 商品类型
     * @param integer $sort 排序，从小到大
     * @param integer $sale_num 销售数量
     * @param integer $sale_increase 每次销售递增
     * @param array $subs 商品规格
     * 
     * @return Response 
     */
    public function setDataDetails(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $goods_id = $param['goods_id'] ?? null;
        $name = $param['name'];
        $amount = $param['amount'];
        $sub_num = $param['sub_num'];
        $tips = $param['tips'];
        $cover_image = $param['cover_image'];
        $carousel_images = $param['carousel_images'];
        $details_images = $param['details_images'];
        $service_description_images = $param['service_description_images'];
        $status = $param['status'];
        $type = $param['type'];
        $sort = $param['sort'];
        $sale_num = $param['sale_num'] ?? 0;
        $sale_increase = $param['sale_increase'] ?? 1;
        $subs = $param['subs'];
        // 查找现有商品
        $goods = !is_null($goods_id) ? Goods::find($goods_id) : new Goods();
        if (!$goods && !is_null($goods_id)) {
            return fail($request, 800013);
        }
        // 更新商品数据
        $goods->name = $name;
        $goods->amount = $amount;
        $goods->sub_num = $sub_num;
        $goods->tips = $tips;
        $goods->cover_image = $cover_image['path'];
        $goods->carousel_images = implode('-|-', array_column($carousel_images, 'path'));
        $goods->details_images = implode('-|-', array_column($details_images, 'path'));
        $goods->service_description_images = implode('-|-', array_column($service_description_images, 'path'));
        $goods->status = $status;
        $goods->type = $type;
        $goods->sort = $sort;
        $goods->sale_num = $sale_num;
        $goods->sale_increase = $sale_increase;
        $goods->save();
        // 更新规格
        GoodSubs::where('goods_id', $goods->goods_id)
            ->where('status', GoodSubsEnums\Status::Normal->value)
            ->update(['status' => GoodSubsEnums\Status::Deactivate->value]);
        foreach ($subs as $_subs) {
            GoodSubs::insert([
                'goods_id' => $goods->goods_id,
                'name' => $_subs['name'],
                'cover_image' => $_subs['icon']['path'],
                'status' => GoodSubsEnums\Status::Normal->value
            ]);
        }
        // 返回信息
        return success($request, []);
    }
}
