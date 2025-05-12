<?php

namespace app\controller\admin\shopManagement;

use support\Request;
use app\model\Goods;
use support\Response;
use app\model\GoodSubs;
use resource\enums\GoodsEnums;
use resource\enums\RedemptionRecordsEnums;
use app\controller\GeneralMethod;
use app\model\RedemptionRecords;
use app\model\UserVips;

class ShippingManagementController extends GeneralMethod
{
    /**
     * 获取发货列表数据
     * 
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示数量
     * @param string $user_name 用户名称
     * @param string $user_uid 用户UID
     * @param string $goods_name 商品名称
     * @param integer $goods_type 商品类型
     * @param integer $status 发货状态
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        // 获取参数
        $pageNo = $request->data['pageNo'];
        $pageSize = $request->data['pageSize'];
        $user_name = $request->data['user_name'] ?? null;
        $user_uid = $request->data['user_uid'] ?? null;
        $goods_name = $request->data['goods_name'] ?? null;
        $goods_type = $request->data['goods_type'] ?? null;
        $status = $request->data['status'] ?? null;
        // 获取数据并构建查询
        $redemption_records = RedemptionRecords::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_redemption_records.user_id')
            ->join('bl_goods', 'bl_goods.goods_id', '=', 'bl_redemption_records.goods_id');
        if (!is_null($user_name)) {
            $redemption_records = $redemption_records->where("bl_user_vips.name", 'like', '%' . $user_name . '%');
        }
        if (!is_null($user_uid)) {
            $redemption_records = $redemption_records->where("bl_user_vips.uid", 'like', '%' . $user_uid . '%');
        }
        if (!is_null($goods_name)) {
            $redemption_records = $redemption_records->where("bl_goods.name", 'like', '%' . $goods_name . '%');
        }
        if (!is_null($goods_type)) {
            $redemption_records = $redemption_records->where("bl_goods.type", $goods_type);
        }
        if (!is_null($status)) {
            $redemption_records = $redemption_records->where("bl_redemption_records.status", $status);
        }
        $redemption_records = $redemption_records->orderBy('bl_redemption_records.created_at', 'desc')
            ->paginate($pageSize, [
                'records_id' => 'bl_redemption_records.records_id',
                'uid' => 'bl_user_vips.uid',
                'user_name' => 'bl_user_vips.name as user_name',
                'goods_name' => 'bl_goods.name as goods_name',
                'sub_id' => 'bl_redemption_records.sub_id',
                'point' => 'bl_redemption_records.point',
                'status' => 'bl_redemption_records.status',
                'created_at' => 'bl_redemption_records.created_at'
            ], 'page', $pageNo);

        // 处理子集数据
        $sub_ids = [];
        foreach ($redemption_records as &$_redemption_records) {
            $sub_ids = array_merge($sub_ids, explode(',', $_redemption_records->sub_id));
        }
        $goods_subs_database = GoodSubs::whereIn('sub_id', array_unique($sub_ids))->get([
            'sub_id' => 'sub_id',
            'name' => 'name'
        ]);
        // 将子集信息映射为数组
        $goods_subs = [];
        foreach ($goods_subs_database as $_goods_subs_database) {
            $goods_subs[$_goods_subs_database->sub_id] = $_goods_subs_database->name;
        }
        // 添加子集和状态等字段
        foreach ($redemption_records as &$_redemption_records) {
            $subs = explode(',', $_redemption_records->sub_id);
            $_redemption_records->goods_sub = implode(";", array_map(fn($sub) => $goods_subs[$sub], $subs));
            $_redemption_records->status = RedemptionRecordsEnums\Status::from($_redemption_records->status)->label();
            $_redemption_records->create_time = $_redemption_records->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            unset($_redemption_records->sub_id, $_redemption_records->created_at);
        }
        $data = is_array($redemption_records) ? $redemption_records : $redemption_records->toArray();
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }

    /**
     * 获取发货详情
     * 
     * @param integer $records_id 记录ID
     * 
     * @return Response 
     */
    public function getDataDetails(Request $request)
    {
        // 获取参数
        $records_id = $request->data['records_id'];
        // 获取数据
        $redemption_records = RedemptionRecords::where('records_id', $records_id)->first([
            'records_id' => 'records_id',
            'user_id' => 'user_id',
            'goods_id' => 'goods_id',
            'sub_id' => 'sub_id',
            'shipping_email' => 'shipping_email',
            'shipping_address' => 'shipping_address',
            'shipping_name' => 'shipping_name',
            'shipping_phone' => 'shipping_phone',
            'tracking_number' => 'tracking_number',
            'status' => 'status'
        ]);
        if (!$redemption_records) {
            return fail($request, 800013);
        }
        // 获取商品信息
        $goods = Goods::where('goods_id', $redemption_records->goods_id)->first([
            'name' => 'name',
            'cover_image' => 'cover_image',
            'type' => 'type'
        ]);
        if (!$goods) {
            return fail($request, 800013);
        }
        // 获取商品子集
        $subs = GoodSubs::whereIn('sub_id', explode(',', $redemption_records->sub_id))->get([
            'name' => 'name'
        ])->toArray();
        $goods_sub = array_map(fn($sub) => $sub['name'], $subs);
        // 获取用户信息
        $user_vips = UserVips::where('user_id', $redemption_records->user_id)->first([
            'uid' => 'uid',
            'name' => 'name'
        ]);
        if (!$user_vips) {
            return fail($request, 800013);
        }
        // 返回数据
        return success($request, [
            'records_id' => $redemption_records->records_id,
            'goods' => [
                'name' => $goods->name,
                'cover_image' => getImageUrl($goods->cover_image),
                'subs' => implode(";", $goods_sub),
                'type' => GoodsEnums\Type::from($goods->type)->label()
            ],
            'user' => [
                'uid' => $user_vips->uid,
                'name' => $user_vips->name
            ],
            'shipping_email' => $redemption_records->shipping_email,
            'shipping_address' => implode(" ", explode('/', $redemption_records->shipping_address)),
            'shipping_name' => $redemption_records->shipping_name,
            'shipping_phone' => $redemption_records->shipping_phone,
            'tracking_number' => $redemption_records->tracking_number,
            'status' => $redemption_records->status
        ]);
    }

    /**
     * 变更发货信息
     * 
     * @param integer $records_id 记录ID
     * @param string $tracking_number 快递单号
     * @param integer $status 发货状态
     * 
     * @return Response 
     */
    public function setDataDetails(Request $request)
    {
        // 获取参数
        $records_id = $request->data['records_id'];
        $tracking_number = $request->data['tracking_number'] ?? '';
        $status = $request->data['status'];
        // 获取数据
        $records = RedemptionRecords::where('records_id', $records_id)->first();
        if (!$records) {
            return fail($request, 800013);
        }
        // 更新数据
        $records->tracking_number = $tracking_number;
        $records->status = $status;
        $records->save();
        // 返回数据
        return success($request, []);
    }
}
