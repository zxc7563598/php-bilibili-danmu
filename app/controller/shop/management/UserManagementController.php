<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use app\model\PaymentRecords;
use app\model\RedemptionRecords;
use app\model\SystemChangePointRecords;
use app\model\UserVips;
use Carbon\Carbon;
use Hejunjie\Tools;
use support\Request;
use resource\enums\UserVipsEnums;
use resource\enums\PaymentRecordsEnums;
use resource\enums\SystemChangePointRecordsEnums;
use Hejunjie\Bililive;

class UserManagementController extends GeneralMethod
{
    public function getData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $page = $param['page'];
        $uid = isset($param['uid']) ? $param['uid'] : null;
        $uname = isset($param['uname']) ? $param['uname'] : null;
        // 获取数据
        $users = new UserVips();
        if (!empty($uid)) {
            $users = $users->where('uid', 'like', '%' . $uid . '%');
        }
        if (!empty($uname)) {
            $users = $users->where('name', 'like', '%' . $uname . '%');
        }
        $users = $users->orderBy('last_vip_at', 'desc')
            ->paginate(100, [
                'user_id' => 'user_id',
                'uid' => 'uid',
                'name' => 'name',
                'vip_type' => 'vip_type',
                'last_vip_at' => 'last_vip_at',
                'end_vip_at' => 'end_vip_at',
                'point' => 'point'
            ], 'page', $page);
        // 处理数据
        foreach ($users as &$_users) {
            $_users->vip_type = UserVipsEnums\VipType::from($_users->vip_type)->label();
            $_users->last_vip_at = Carbon::parse($_users->last_vip_at)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            $_users->end_vip_at = Carbon::parse($_users->end_vip_at)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
        }
        // 返回数据
        return success($request, [
            'list' => pageToArray($users)
        ]);
    }

    public function getUserData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $user_id = $param['user_id'];
        // 获取数据
        $users = UserVips::where('user_id', $user_id)->first([
            'user_id' => 'user_id',
            'uid' => 'uid',
            'name' => 'name',
            'vip_type' => 'vip_type',
            'point' => 'point'
        ]);
        // 返回数据
        return success($request, [
            'users' => $users
        ]);
    }

    public function getUserInfo(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $uid = $param['uid'];
        // 获取数据
        $getMasterInfo = Tools\HttpClient::sendGetRequest('https://api.live.bilibili.com/live_user/v1/Master/info?uid=' . $uid, [
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
            "Origin: https://live.bilibili.com",
        ], 10);
        if ($getMasterInfo['httpStatus'] == 200) {
            $getMasterInfoData = json_decode($getMasterInfo['data'], true);
        }
        // 返回数据
        return success($request, [
            'uname' => isset($getMasterInfoData['data']['info']['uname']) ? $getMasterInfoData['data']['info']['uname'] : null,
            'face' => isset($getMasterInfoData['data']['info']['face']) ? $getMasterInfoData['data']['info']['face'] : null
        ]);
    }

    public function setData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $user_id = $param['user_id'];
        $uid = $param['uid'];
        $name = $param['name'];
        $password = !empty($param['password']) ? $param['password'] : null;
        $vip_type = $param['vip_type'];
        $point = $param['point'];
        // 获取数据
        $users = new UserVips();
        if (!empty($user_id)) {
            $users = UserVips::where('user_id', $user_id)->first();
            if (empty($users)) {
                return fail($request, 800013);
            }
        } else {
            $user_vips = UserVips::where('uid', $uid)->first();
            if (!empty($user_vips)) {
                return fail($request, 800012);
            }
        }
        $users->user_id = $user_id;
        $users->uid = $uid;
        $users->name = $name;
        if (!is_null($password)) {
            $users->password = $password;
        }
        $users->vip_type = $vip_type;
        $users->point = $point;
        $users->save();
        // 返回数据
        return success($request, []);
    }

    public function getUserRecords(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $user_id = $param['user_id'];
        // 获取数据
        $payment_records = PaymentRecords::where('user_id', $user_id)->get([
            'vip_type' => 'vip_type',
            'point' => 'point',
            'after_point' => 'after_point',
            'payment_at' => 'payment_at'
        ]);
        $redemption_records = RedemptionRecords::join('bl_goods', 'bl_redemption_records.goods_id', '=', 'bl_goods.goods_id')
            ->where('bl_redemption_records.user_id', $user_id)
            ->get([
                'name' => 'bl_goods.name',
                'cover_image' => 'bl_goods.cover_image',
                'point' => 'bl_redemption_records.point',
                'after_point' => 'bl_redemption_records.after_point',
                'created_at' => 'bl_redemption_records.created_at'
            ]);
        $system_change_point_records = SystemChangePointRecords::where('user_id', $user_id)->get([
            'type' => 'type',
            'point' => 'point',
            'after_point' => 'after_point',
            'created_at' => 'created_at'
        ]);
        // 处理数据
        $data = [];
        foreach ($payment_records as $_payment_records) {
            $icon = getImageUrl('shop-config/jian.png');
            switch ($_payment_records->vip_type) {
                case PaymentRecordsEnums\VipType::Lv1->value:
                    $icon = getImageUrl('shop-config/jian.png');
                    break;
                case PaymentRecordsEnums\VipType::Lv2->value:
                    $icon = getImageUrl('shop-config/ti.png');
                    break;
                case PaymentRecordsEnums\VipType::Lv3->value:
                    $icon = getImageUrl('shop-config/zong.png');
                    break;
            }
            $data[] = [
                'icon' => $icon,
                'name' => '成为' . PaymentRecordsEnums\VipType::from($_payment_records->vip_type)->label(),
                'point' => '+ ' . $_payment_records->point,
                'after_point' => $_payment_records->after_point,
                'date' => $_payment_records->payment_at
            ];
        }
        foreach ($system_change_point_records as $_system_change_point_records) {
            $type = '';
            switch ($_system_change_point_records->type) {
                case SystemChangePointRecordsEnums\Type::Up->value:
                    $type = '+';
                    break;
                case SystemChangePointRecordsEnums\Type::Down->value:
                    $type = '-';
                    break;
            }
            $data[] = [
                'icon' => getImageUrl('shop-config/supreme.png'),
                'name' => '主播变更',
                'point' => $type . ' ' . $_system_change_point_records->point,
                'after_point' => $_system_change_point_records->after_point,
                'date' => $_system_change_point_records->created_at->timezone(config('app')['default_timezone'])->timestamp
            ];
        }
        foreach ($redemption_records as $_redemption_records) {
            $data[] = [
                'icon' => getImageUrl($_redemption_records->cover_image),
                'name' => $_redemption_records->name,
                'point' => '- ' . $_redemption_records->point,
                'after_point' => $_redemption_records->after_point,
                'date' => $_redemption_records->created_at->timezone(config('app')['default_timezone'])->timestamp
            ];
        }
        // 数组排序后整理时间
        $data = Tools\Arr::sortByField($data, 'date', false);
        foreach ($data as &$_data) {
            $_data['date'] = Carbon::parse($_data['date'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
        }
        // 返回数据
        return success($request, [
            'records' => $data
        ]);
    }

    public function setUserPoint(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $type = $param['type'];
        $point = $param['point'];
        $user_id = $param['user_id'];
        // 获取数据
        $user_vips = UserVips::where('user_id', $user_id)->first();
        if (empty($user_vips)) {
            return fail($request, 800013);
        }
        // 添加数据
        $system_change_point_records = new SystemChangePointRecords();
        $system_change_point_records->user_id = $user_id;
        $system_change_point_records->type = $type;
        $system_change_point_records->point = $point;
        $system_change_point_records->pre_point = $user_vips->point;
        switch ($type) {
            case SystemChangePointRecordsEnums\Type::Up->value:
                $system_change_point_records->after_point = $system_change_point_records->pre_point + $point;
                break;
            case SystemChangePointRecordsEnums\Type::Down->value:
                $system_change_point_records->after_point = $system_change_point_records->pre_point - $point;
                break;
        }
        $system_change_point_records->save();
        // 返回数据
        return success($request, []);
    }
}
