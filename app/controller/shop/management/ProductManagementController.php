<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use app\model\Goods;
use app\model\GoodSubs;
use Hejunjie\Tools;
use support\Request;
use resource\enums\GoodsEnums;
use resource\enums\GoodSubsEnums;

class ProductManagementController extends GeneralMethod
{
    public function getData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $page = $param['page'];
        $name = isset($param['name']) ? $param['name'] : null;
        $type = isset($param['type']) ? $param['type'] : null;
        // 获取数据
        $goods = new Goods();
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

    public function getDataDetails(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $goods_id = $param['goods_id'];
        // 获取数据
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
        if (empty($goods)) {
            return fail($request, 800013);
        }
        // 处理数据
        $carousel_images = [];
        $carousel_images_data = explode('-|-', $goods->carousel_images);
        foreach ($carousel_images_data as $_carousel_images_data) {
            $carousel_images[] = [
                'url' => getImageUrl($_carousel_images_data),
                'path' => $_carousel_images_data
            ];
        }
        $details_images = [];
        $details_images_data = explode('-|-', $goods->details_images);
        foreach ($details_images_data as $_details_images_data) {
            $details_images[] = [
                'url' => getImageUrl($_details_images_data),
                'path' => $_details_images_data
            ];
        }
        $service_description_images = [];
        $service_description_images_data = explode('-|-', $goods->service_description_images);
        foreach ($service_description_images_data as $_service_description_images_data) {
            $service_description_images[] = [
                'url' => getImageUrl($_service_description_images_data),
                'path' => $_service_description_images_data
            ];
        }
        // 获取规格
        $goods_sub = GoodSubs::where('goods_id', $goods->goods_id)->where('status', GoodSubsEnums\Status::Normal->value)->get([
            'name' => 'name',
            'cover_image' => 'cover_image'
        ]);
        $subs = [];
        foreach ($goods_sub as $_goods_sub) {
            $subs[] = [
                'icon' => [
                    'url' => getImageUrl($_goods_sub->cover_image),
                    'path' => $_goods_sub->cover_image
                ],
                'name' => $_goods_sub->name
            ];
        }
        // 返回数据
        return success($request, [
            'goods' => [
                'goods_id' => $goods->goods_id,
                'name' => $goods->name,
                'amount' => $goods->amount,
                'sub_num' => $goods->sub_num,
                'tips' => $goods->tips,
                'cover_image' => [
                    'url' => getImageUrl($goods->cover_image),
                    'path' => $goods->cover_image
                ],
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

    public function uploadImages(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $base64 = $param['base64'];
        $type = $param['type'];
        // base64存储图片 
        $path = public_path('attachment/shop-config/');
        switch ($type) {
            case 'cover_image':
                $path = public_path('attachment/goods/cover_image/');
                break;
            case 'carousel_images':
                $path = public_path('attachment/goods/carousel_images/');
                break;
            case 'details_images':
                $path = public_path('attachment/goods/details_images/');
                break;
            case 'service_description_images':
                $path = public_path('attachment/goods/service_description_images/');
                break;
            case 'sub_cover_image':
                $path = public_path('attachment/goods/sub_cover_image/');
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

    public function setDataDetails(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $goods_id = !empty($param['goods_id']) ? $param['goods_id'] : null;
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
        $sale_num = !empty($param['sale_num']) ? $param['sale_num'] : 0;
        $sale_increase = !empty($param['sale_increase']) ? $param['sale_increase'] : 1;
        $subs = $param['subs'];
        // 添加数据
        $goods = new Goods();
        if (!empty($goods_id)) {
            $goods = Goods::where('goods_id', $goods_id)->first();
            if (empty($goods)) {
                return fail($request, 800013);
            }
        }
        $goods->name = $name;
        $goods->amount = $amount;
        $goods->sub_num = $sub_num;
        $goods->tips = $tips;
        $goods->cover_image = $cover_image['path'];
        $carousel_images_data = [];
        foreach ($carousel_images as $_carousel_images) {
            $carousel_images_data[] = $_carousel_images['path'];
        }
        $goods->carousel_images = implode('-|-', $carousel_images_data);
        $details_images_data = [];
        foreach ($details_images as $_details_images) {
            $details_images_data[] = $_details_images['path'];
        }
        $goods->details_images = implode('-|-', $details_images_data);
        $service_description_images_data = [];
        foreach ($service_description_images as $_service_description_images) {
            $service_description_images_data[] = $_service_description_images['path'];
        }
        $goods->service_description_images = implode('-|-', $service_description_images_data);
        $goods->status = $status;
        $goods->type = $type;
        $goods->sort = $sort;
        $goods->sale_num = $sale_num;
        $goods->sale_increase = $sale_increase;
        $goods->save();
        // 改变规格
        GoodSubs::where('goods_id', $goods->goods_id)->where('status', GoodSubsEnums\Status::Normal->value)->update(['status' => GoodSubsEnums\Status::Deactivate->value]);
        foreach ($subs as $_subs) {
            $goods_sub = new GoodSubs();
            $goods_sub->goods_id = $goods->goods_id;
            $goods_sub->name = $_subs['name'];
            $goods_sub->cover_image = $_subs['icon']['path'];
            $goods_sub->status = GoodSubsEnums\Status::Normal->value;
            $goods_sub->save();
        }
        // 返回数据
        return success($request, []);
    }
}
