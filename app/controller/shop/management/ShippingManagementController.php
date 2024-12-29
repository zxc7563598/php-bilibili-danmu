<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use app\model\Goods;
use app\model\GoodSubs;
use app\model\RedemptionRecords;
use app\model\UserVips;
use Hejunjie\Tools;
use support\Request;
use resource\enums\RedemptionRecordsEnums;

class ShippingManagementController extends GeneralMethod
{
    public function getData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $page = $param['page'];
        $user_name = isset($param['user_name']) ? $param['user_name'] : null;
        $user_uid = isset($param['user_uid']) ? $param['user_uid'] : null;
        $goods_name = isset($param['goods_name']) ? $param['goods_name'] : null;
        $goods_type = isset($param['goods_type']) ? $param['goods_type'] : null;
        $status = isset($param['status']) ? $param['status'] : null;
        // 获取数据
        $redemption_records = RedemptionRecords::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_redemption_records.user_id')->join('bl_goods', 'bl_goods.goods_id', '=', 'bl_redemption_records.goods_id');

        if (!is_null($user_name)) {
            $redemption_records = $redemption_records->where('bl_user_vips.name', 'like', '%' . $user_name . '%');
        }
        if (!is_null($user_uid)) {
            $redemption_records = $redemption_records->where('bl_user_vips.uid', 'like', '%' . $user_uid . '%');
        }
        if (!is_null($goods_name)) {
            $redemption_records = $redemption_records->where('bl_goods.name', 'like', '%' . $goods_name . '%');
        }
        if (!is_null($goods_type)) {
            $redemption_records = $redemption_records->where('bl_goods.type', $goods_type);
        }
        if (!is_null($status)) {
            $redemption_records = $redemption_records->where('bl_redemption_records.status', $status);
        }
        $redemption_records = $redemption_records->orderBy('bl_redemption_records.created_at', 'desc')
            ->paginate(100, [
                'records_id' => 'bl_redemption_records.records_id',
                'uid' => 'bl_user_vips.uid',
                'user_name' => 'bl_user_vips.name as user_name', // 用户名
                'goods_name' => 'bl_goods.name as goods_name', // 商品名
                'sub_id' => 'bl_redemption_records.sub_id', // 子集id
                'point' => 'bl_redemption_records.point', // 消耗积分
                'status' => 'bl_redemption_records.status',
                'created_at' => 'bl_redemption_records.created_at'
            ], 'page', $page);
        // 处理数据
        $sub_id = [];
        foreach ($redemption_records as &$_redemption_records) {
            $data = explode(',', $_redemption_records->sub_id);
            foreach ($data as $_data) {
                if (!in_array($_data, $sub_id)) {
                    $sub_id[] = $_data;
                }
            }
        }
        $goods_subs_database = GoodSubs::whereIn('sub_id', $sub_id)->get([
            'sub_id' => 'sub_id',
            'name' => 'name'
        ]);
        $goods_subs = [];
        foreach ($goods_subs_database as $_goods_subs_database) {
            $goods_subs[$_goods_subs_database->sub_id] = $_goods_subs_database->name;
        }
        foreach ($redemption_records as &$_redemption_records) {
            $subs = explode(',', $_redemption_records->sub_id);
            $subs_array = [];
            foreach ($subs as $_subs) {
                $subs_array[] = $goods_subs[$_subs];
            }
            $_redemption_records->goods_sub = implode("\r\n", $subs_array);
            $_redemption_records->status = RedemptionRecordsEnums\Status::from($_redemption_records->status)->label();
            $_redemption_records->create_time = $_redemption_records->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            unset($_redemption_records->sub_id);
            unset($_redemption_records->created_at);
        }
        // 返回数据
        return success($request, [
            'list' => pageToArray($redemption_records)
        ]);
    }

    public function getDataDetails(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $records_id = $param['records_id'];
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
        if (empty($redemption_records)) {
            return fail($request, 800013);
        }
        // 获取商品信息
        $goods = Goods::where('goods_id', $redemption_records->goods_id)->first([
            'name' => 'name',
            'cover_image' => 'cover_image',
            'type' => 'type'
        ]);
        if (empty($goods)) {
            return fail($request, 800013);
        }
        $subs = GoodSubs::whereIn('sub_id', explode(',', $redemption_records->sub_id))->get([
            'name' => 'name'
        ]);
        $goods_sub = [];
        foreach ($subs as $_subs) {
            $goods_sub[] = $_subs->name;
        }
        // 获取用户信息
        $user_vips = UserVips::where('user_id', $redemption_records->user_id)->first([
            'uid' => 'uid',
            'name' => 'name'
        ]);
        if (empty($user_vips)) {
            return fail($request, 800013);
        }
        // 返回信息
        return success($request, [
            'records_id' => $redemption_records->records_id,
            'goods' => [
                'name' => $goods->name,
                'cover_image' => getImageUrl($goods->cover_image),
                'subs' => $goods_sub,
                'type' => $goods->type
            ],
            'user' => [
                'uid' => $user_vips->uid,
                'name' => $user_vips->name
            ],
            'shipping_email' => $redemption_records->shipping_email,
            'shipping_address' => $redemption_records->shipping_address,
            'shipping_name' => $redemption_records->shipping_name,
            'shipping_phone' => $redemption_records->shipping_phone,
            'tracking_number' => $redemption_records->tracking_number,
            'status' => $redemption_records->status
        ]);
    }

    public function setDataDetails(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $records_id = $param['records_id'];
        $tracking_number = $param['tracking_number'];
        $status = $param['status'];
        // 获取数据
        $records = RedemptionRecords::where('records_id', $records_id)->first();
        if (empty($records)) {
            return fail($request, 800013);
        }
        // 存储数据
        $records->tracking_number = $tracking_number;
        $records->status = $status;
        $records->save();
        // 返回数据
        return success($request, []);
    }
}
