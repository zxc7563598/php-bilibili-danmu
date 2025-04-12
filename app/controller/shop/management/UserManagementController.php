<?php

namespace app\controller\shop\management;

use Carbon\Carbon;
use Hejunjie\Utils;
use support\Request;
use support\Response;
use app\model\UserVips;
use app\model\PaymentRecords;
use app\model\RedemptionRecords;
use app\controller\GeneralMethod;
use resource\enums\UserVipsEnums;
use resource\enums\PaymentRecordsEnums;
use app\model\SystemChangePointRecords;
use resource\enums\SystemChangePointRecordsEnums;

class UserManagementController extends GeneralMethod
{
    /**
     * 获取用户列表
     * 
     * @param integer $page 页码
     * @param string $uid 用户UID
     * @param string $uname 用户名称
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $page = $param['page'];
        $uid = $param['uid'] ?? null;
        $uname = $param['uname'] ?? null;
        // 获取数据
        $users = UserVips::query();
        if (!is_null($uid)) {
            $users->where('uid', 'like', '%' . $uid . '%');
        }
        if (!is_null($uname)) {
            $users->where('name', 'like', '%' . $uname . '%');
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
        foreach ($users as &$_user) {
            $_user->vip_type = UserVipsEnums\VipType::from($_user->vip_type)->label();
            $_user->last_vip_at = Carbon::parse($_user->last_vip_at)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            $_user->end_vip_at = Carbon::parse($_user->end_vip_at)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
        }
        // 返回数据
        return success($request, ['list' => pageToArray($users)]);
    }

    /**
     * 获取用户详细信息
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $user_id = $param['user_id'];
        // 获取用户数据
        $user = UserVips::where('user_id', $user_id)->first([
            'user_id' => 'user_id',
            'uid' => 'uid',
            'name' => 'name',
            'vip_type' => 'vip_type',
            'point' => 'point'
        ]);
        if (empty($user)) {
            return fail($request, 800013);
        }
        // 返回数据
        return success($request, ['users' => $user]);
    }

    /**
     * 根据UID查询用户数据
     * 
     * @param integer $uid 用户UID
     * 
     * @return Response 
     */
    public function getUserInfo(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $uid = $param['uid'];
        // 获取数据
        $getMasterInfo = Utils\HttpClient::sendGetRequest('https://api.live.bilibili.com/live_user/v1/Master/info?uid=' . $uid, [
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
            "Origin: https://live.bilibili.com",
        ], 10);
        // 处理数据
        $getMasterInfoData = $getMasterInfo['httpStatus'] == 200 ? json_decode($getMasterInfo['data'], true) : [];
        // 返回数据
        return success($request, [
            'uname' => $getMasterInfoData['data']['info']['uname'] ?? null,
            'face' => $getMasterInfoData['data']['info']['face'] ?? null,
        ]);
    }

    /**
     * 存储用户信息
     * 
     * @param integer $user_id 用户ID
     * @param integer $uid 用户UID
     * @param string $name 用户名称
     * @param string $password 登录密码
     * @param integer $vip_type 航海类型
     * 
     * @return Response 
     */
    public function setData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $user_id = $param['user_id'] ?? null;
        $uid = $param['uid'];
        $name = $param['name'];
        $password = $param['password'] ?? null;
        $vip_type = $param['vip_type'];
        // 获取数据
        $user = !is_null($user_id) ? UserVips::find($user_id) : new UserVips();
        if (!$user && !is_null($user_id)) {
            return fail($request, 800013);
        }
        // 更新用户数据
        $user->uid = $uid;
        $user->name = $name;
        if (!is_null($password)) {
            $user->salt = mt_rand(1000, 9999);
            $user->password = sha1(sha1($password) . $user->salt);
        }
        $user->vip_type = $vip_type;
        $user->save();
        // 返回数据
        return success($request, []);
    }

    /**
     * 清空所有用户密码
     * 
     * @return Response 
     */
    public function resetPassword(Request $request)
    {
        // 处理数据
        UserVips::where('created_at', '>', 0)->update([
            'password' => null,
            'salt' => null
        ]);
        // 返回数据
        return success($request, []);
    }

    /**
     * 获取用户航海开通记录
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserRecords(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $user_id = $param['user_id'];
        // 获取记录
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
            'source' => 'source',
            'after_point' => 'after_point',
            'created_at' => 'created_at'
        ]);
        // 处理数据
        $data = [];
        // 整合上舰数据
        foreach ($payment_records as $_payment_records) {
            $icon = getImageUrl('shop-config/jian.png');
            switch ($_payment_records->vip_type) {
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
                'date' => Carbon::parse($_payment_records->payment_at)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
            ];
        }
        // 整合系统变更数据
        foreach ($system_change_point_records as $_system_change_point_records) {
            $type = $_system_change_point_records->type == SystemChangePointRecordsEnums\Type::Up->value ? '+' : '-';
            $data[] = [
                'icon' => getImageUrl('shop-config/supreme.png'),
                'name' => SystemChangePointRecordsEnums\Source::from($_system_change_point_records->source)->label(),
                'point' => $type . ' ' . $_system_change_point_records->point,
                'after_point' => $_system_change_point_records->after_point,
                'date' => $_system_change_point_records->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
            ];
        }
        // 整合消费数据
        foreach ($redemption_records as $_redemption_records) {
            $data[] = [
                'icon' => getImageUrl($_redemption_records->cover_image),
                'name' => $_redemption_records->name,
                'point' => '- ' . $_redemption_records->point,
                'after_point' => $_redemption_records->after_point,
                'date' => $_redemption_records->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
            ];
        }
        // 排序
        $data = Utils\Arr::sortByField($data, 'date', false);
        // 返回数据
        return success($request, ['records' => $data]);
    }

    /**
     * 变更用户积分
     * 
     * @param integer $type 变更类型 
     * @param integer $point 变更积分 
     * @param integer $user_id 用户ID 
     * 
     * @return Response 
     */
    public function setUserPoint(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $type = $param['type'];
        $point = $param['point'];
        $user_id = $param['user_id'];
        // 获取用户数据
        $user_vips = UserVips::where('user_id', $user_id)->first();
        if (empty($user_vips)) {
            return fail($request, 800013);
        }
        // 添加数据
        $system_change_point_records = new SystemChangePointRecords();
        $system_change_point_records->user_id = $user_id;
        $system_change_point_records->type = $type;
        $system_change_point_records->point = $point;
        $system_change_point_records->source = SystemChangePointRecordsEnums\Source::AnchorChange->value;
        $system_change_point_records->pre_point = $user_vips->point;
        $system_change_point_records->after_point = $type === SystemChangePointRecordsEnums\Type::Up->value ? $user_vips->point + $point : $user_vips->point - $point;
        $system_change_point_records->save();
        // 返回数据
        return success($request, []);
    }
}
