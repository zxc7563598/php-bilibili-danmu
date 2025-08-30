<?php

namespace app\controller\admin\shopManagement;

use support\Request;
use app\model\Goods;
use support\Response;
use app\model\GoodSubs;
use resource\enums\GoodsEnums;
use app\controller\GeneralMethod;
use Carbon\Carbon;
use resource\enums\GoodSubsEnums;

class ProductManagementController extends GeneralMethod
{
    /**
     * 获取商品信息
     * 
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示数量
     * @param string $name 商品名称
     * @param integer $type 商品类型
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        // 获取参数
        $pageNo = $request->data['pageNo'] ?? 1;
        $pageSize = $request->data['pageSize'] ?? 10;
        $name = $request->data['name'] ?? null;
        $type = $request->data['type'] ?? null;
        // 获取数据
        $goods = Goods::query();
        if (!is_null($name)) {
            $goods = $goods->where('name', 'like', '%' . $name . '%');
        }
        if (!is_null($type)) {
            $goods = $goods->where('type', $type);
        }
        $goods = $goods->orderBy('sort', 'asc')
            ->paginate($pageSize, [
                'goods_id' => 'goods_id',
                'name' => 'name',
                'amount' => 'amount',
                'amount_type' => 'amount_type',
                'cover_image' => 'cover_image',
                'status' => 'status',
                'type' => 'type',
                'sort' => 'sort'
            ], 'page', $pageNo);
        // 处理数据
        foreach ($goods as &$_goods) {
            $_goods->amount_type = GoodsEnums\AmountType::from($_goods->amount_type)->label();
            $_goods->cover_image = getImageUrl($_goods->cover_image);
            $_goods->status = GoodsEnums\Status::from($_goods->status)->label();
            $_goods->type = GoodsEnums\Type::from($_goods->type)->label();
        }
        $data = is_array($goods) ? $goods : $goods->toArray();
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
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
        // 获取参数
        $goods_id = $request->data['goods_id'];
        // 获取商品数据
        $goods = Goods::where('goods_id', $goods_id)->first([
            'goods_id' => 'goods_id',
            'name' => 'name',
            'amount_type' => 'amount_type',
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
                'amount_type' => $goods->amount_type,
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
     * @param file $images 图片
     * @param string $type 图片类型
     * 
     * @return Response 
     */
    public function uploadImages(Request $request)
    {
        $file = $request->file('image');
        $type = $request->input('type', '');
        if (!$file || !$file->isValid()) {
            return fail($request, 800013);
        }
        if (!str_contains($file->getUploadMimeType(), 'image')) {
            return fail($request, 800017);
        }
        $filePath = public_path('attachment/goods/default/');
        switch ($type) {
            case 'cover_image':
                $filePath = public_path('attachment/goods/cover_image/');
                break;
            case 'carousel_images':
                $filePath = public_path('attachment/goods/carousel_images/');
                break;
            case 'details_images':
                $filePath = public_path('attachment/goods/details_images/');
                break;
            case 'service_description_images':
                $filePath = public_path('attachment/goods/service_description_images/');
                break;
            case 'sub_cover_image':
                $filePath = public_path('attachment/goods/sub_cover_image/');
                break;
        }
        if (!is_dir($filePath)) {
            if (!mkdir($filePath, 0755, true)) {  // 尝试递归创建目录
                throw new \Exception("无法创建目标目录: " . $filePath);
            }
        }
        $fileName = uniqid('image_', true) . '.' . $file->getUploadExtension();
        $file->move($filePath . $fileName);
        $image_path = str_replace(public_path('attachment/'), "", ($filePath . $fileName));
        // 返回数据
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
     * @param string $amount_type 价格类型
     * @param string $amount 商品价格
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
     * @param integer $sub_num 可购买规格数量
     * 
     * @return Response 
     */
    public function setDataDetails(Request $request)
    {
        // 获取参数
        $goods_id = $request->data['goods_id'] ?? null;
        $name = $request->data['name'];
        $amount_type = $request->data['amount_type'];
        $amount = $request->data['amount'];
        $subs = $request->data['subs'];
        $sub_num = $request->data['sub_num'] ?? count($request->data['subs']);
        $tips = $request->data['tips'] ?? null;
        $cover_image = $request->data['cover_image'];
        $carousel_images = $request->data['carousel_images'];
        $details_images = $request->data['details_images'];
        $service_description_images = $request->data['service_description_images'];
        $status = $request->data['status'];
        $type = $request->data['type'];
        $sort = $request->data['sort'];
        $sale_num = $request->data['sale_num'] ?? 0;
        $sale_increase = $request->data['sale_increase'] ?? 1;
        // 查找现有商品
        $goods = (!is_null($goods_id) && ($goods_id > 0)) ? Goods::find($goods_id) : new Goods();
        if (!$goods && !is_null($goods_id)) {
            return fail($request, 800013);
        }
        // 更新商品数据
        $goods->name = $name;
        $goods->amount_type = $amount_type;
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
                'status' => GoodSubsEnums\Status::Normal->value,
                'created_at' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp,
                'updated_at' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp
            ]);
        }
        // 返回信息
        return success($request, []);
    }
}
