<?php

namespace app\controller\admin\shopManagement;

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
use resource\enums\RedemptionRecordsEnums;
use app\model\UserCurrencyLogs;
use resource\enums\UserCurrencyLogsEnums;

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
        // 获取参数
        $pageNo = $request->data['pageNo'] ?? 1;
        $pageSize = $request->data['pageSize'] ?? 10;
        $uid = $request->data['uid'] ?? null;
        $uname = $request->data['uname'] ?? null;
        // 获取数据
        $users = UserVips::query();
        if (!is_null($uid)) {
            $users->where('uid', 'like', $uid);
        }
        if (!is_null($uname)) {
            $users->where('name', 'like', '%' . $uname . '%');
        }
        $users = $users->orderBy('last_vip_at', 'desc')
            ->paginate($pageSize, [
                'user_id' => 'user_id',
                'uid' => 'uid',
                'name' => 'name',
                'vip_type' => 'vip_type',
                'last_vip_at' => 'last_vip_at',
                'end_vip_at' => 'end_vip_at',
                'point' => 'point',
                'coin' => 'coin'
            ], 'page', $pageNo);
        // 处理数据
        foreach ($users as &$_user) {
            $_user->vip_type = UserVipsEnums\VipType::from($_user->vip_type)->label();
            $_user->last_vip_at = Carbon::parse($_user->last_vip_at)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            $_user->end_vip_at = Carbon::parse($_user->end_vip_at)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
        }
        $data = is_array($users) ? $users : $users->toArray();
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
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
        // 获取参数
        $user_id = $request->data['user_id'];
        // 获取用户数据
        $user = UserVips::where('user_id', $user_id)->first([
            'user_id' => 'user_id',
            'uid' => 'uid',
            'name' => 'name',
            'vip_type' => 'vip_type'
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
        // 获取参数
        $uid = $request->data['uid'];
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
        // 获取参数
        $user_id = $request->data['user_id'] ?? null;
        $uid = $request->data['uid'];
        $name = $request->data['name'];
        $password = $request->data['password'] ?? null;
        $vip_type = $request->data['vip_type'];
        // 获取数据
        $user = (!is_null($user_id) && ($user_id > 0)) ? UserVips::find($user_id) : new UserVips();
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
     * 获取用户积分变更记录
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserPointRecords(Request $request)
    {
        // 获取参数
        $user_id = $request->data['user_id'];
        // 获取记录
        $user_currency_logs = UserCurrencyLogs::where('user_id', $user_id)
            ->where('currency_type', UserCurrencyLogsEnums\CurrencyType::Point->value)
            ->orderBy('created_at', 'desc')
            ->get([
                'type' => 'type',
                'currency' => 'currency',
                'source' => 'source',
                'after_currency' => 'after_currency',
                'created_at' => 'created_at'
            ]);
        // 处理数据
        $data = [];
        foreach ($user_currency_logs as $_user_currency_logs) {
            $type = $_user_currency_logs->type == UserCurrencyLogsEnums\Type::Up->value ? '+' : '-';
            $data[] = [
                'icon' => getImageUrl('shop-config/supreme.png'),
                'name' => UserCurrencyLogsEnums\Source::from($_user_currency_logs->source)->label(),
                'point' => $type . ' ' . $_user_currency_logs->currency,
                'after_point' => $_user_currency_logs->after_currency,
                'date' => $_user_currency_logs->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
            ];
        }
        // 返回数据
        return success($request, ['records' => $data]);
    }

    /**
     * 获取用户硬币变更记录
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserCoinRecords(Request $request)
    {
        // 获取参数
        $user_id = $request->data['user_id'];
        // 获取记录
        $user_currency_logs = UserCurrencyLogs::where('user_id', $user_id)
            ->where('currency_type', UserCurrencyLogsEnums\CurrencyType::Coin->value)
            ->orderBy('created_at', 'desc')
            ->get([
                'type' => 'type',
                'currency' => 'currency',
                'source' => 'source',
                'after_currency' => 'after_currency',
                'created_at' => 'created_at'
            ]);
        // 处理数据
        $data = [];
        foreach ($user_currency_logs as $_user_currency_logs) {
            $type = $_user_currency_logs->type == UserCurrencyLogsEnums\Type::Up->value ? '+' : '-';
            $data[] = [
                'icon' => getImageUrl('shop-config/supreme.png'),
                'name' => UserCurrencyLogsEnums\Source::from($_user_currency_logs->source)->label(),
                'point' => $type . ' ' . $_user_currency_logs->currency,
                'after_point' => $_user_currency_logs->after_currency,
                'date' => $_user_currency_logs->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
            ];
        }
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
        // 获取参数
        $type = $request->data['type'];
        $point = $request->data['point'];
        $user_id = $request->data['user_id'];
        // 获取用户数据
        $user_vips = UserVips::where('user_id', $user_id)->first();
        if (empty($user_vips)) {
            return fail($request, 800013);
        }
        // 添加数据
        $user_currency_logs = new UserCurrencyLogs();
        $user_currency_logs->user_id = $user_id;
        $user_currency_logs->type = $type;
        $user_currency_logs->source = UserCurrencyLogsEnums\Source::AnchorChange->value;
        $user_currency_logs->currency_type = UserCurrencyLogsEnums\CurrencyType::Point->value;
        $user_currency_logs->currency = $point;
        $user_currency_logs->pre_currency = $user_vips->point;
        $user_currency_logs->after_currency = $type === UserCurrencyLogsEnums\Type::Up->value ? $user_vips->point + $point : $user_vips->point - $point;
        $user_currency_logs->save();
        // 返回数据
        return success($request, []);
    }

    /**
     * 变更用户硬币
     * 
     * @param integer $type 变更类型 
     * @param integer $coin 变更硬币 
     * @param integer $user_id 用户ID 
     * 
     * @return Response 
     */
    public function setUserCoin(Request $request)
    {
        // 获取参数
        $type = $request->data['type'];
        $coin = $request->data['coin'];
        $user_id = $request->data['user_id'];
        // 获取用户数据
        $user_vips = UserVips::where('user_id', $user_id)->first();
        if (empty($user_vips)) {
            return fail($request, 800013);
        }
        // 添加数据
        $user_currency_logs = new UserCurrencyLogs();
        $user_currency_logs->user_id = $user_id;
        $user_currency_logs->type = $type;
        $user_currency_logs->source = UserCurrencyLogsEnums\Source::AnchorChange->value;
        $user_currency_logs->currency_type = UserCurrencyLogsEnums\CurrencyType::Coin->value;
        $user_currency_logs->currency = $coin;
        $user_currency_logs->pre_currency = $user_vips->coin;
        $user_currency_logs->after_currency = $type === UserCurrencyLogsEnums\Type::Up->value ? $user_vips->coin + $coin : $user_vips->coin - $coin;
        $user_currency_logs->save();
        // 返回数据
        return success($request, []);
    }

    /**
     * 获取用户积分变更记录
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserPointRecordsV2(Request $request)
    {
        // 获取参数
        $pageNo = $request->data['pageNo'] ?? 1;
        $pageSize = $request->data['pageSize'] ?? 6;
        $user_id = $request->data['user_id'];
        // 获取记录
        $user_currency_logs = UserCurrencyLogs::where('user_id', $user_id)
            ->where('currency_type', UserCurrencyLogsEnums\CurrencyType::Point->value)
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize, [
                'type' => 'type',
                'currency' => 'currency',
                'source' => 'source',
                'after_currency' => 'after_currency',
                'created_at' => 'created_at'
            ], 'page', $pageNo);
        // 处理数据
        foreach ($user_currency_logs as &$_user_currency_logs) {
            $type = $_user_currency_logs->type == UserCurrencyLogsEnums\Type::Up->value ? '+' : '-';
            $_user_currency_logs->icon = getImageUrl('shop-config/supreme.png');
            $_user_currency_logs->name = UserCurrencyLogsEnums\Source::from($_user_currency_logs->source)->label();
            $_user_currency_logs->currency = $type . ' ' . $_user_currency_logs->currency;
            $_user_currency_logs->date = $_user_currency_logs->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
        }
        $data = is_array($user_currency_logs) ? $user_currency_logs : $user_currency_logs->toArray();
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }

    /**
     * 获取用户硬币变更记录
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserCoinRecordsV2(Request $request)
    {
        // 获取参数
        $pageNo = $request->data['pageNo'] ?? 1;
        $pageSize = $request->data['pageSize'] ?? 6;
        $user_id = $request->data['user_id'];
        // 获取记录
        $user_currency_logs = UserCurrencyLogs::where('user_id', $user_id)
            ->where('currency_type', UserCurrencyLogsEnums\CurrencyType::Coin->value)
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize, [
                'type' => 'type',
                'currency' => 'currency',
                'source' => 'source',
                'after_currency' => 'after_currency',
                'created_at' => 'created_at'
            ], 'page', $pageNo);
        // 处理数据
        foreach ($user_currency_logs as &$_user_currency_logs) {
            $type = $_user_currency_logs->type == UserCurrencyLogsEnums\Type::Up->value ? '+' : '-';
            $_user_currency_logs->icon = getImageUrl('shop-config/supreme.png');
            $_user_currency_logs->name = UserCurrencyLogsEnums\Source::from($_user_currency_logs->source)->label();
            $_user_currency_logs->currency = $type . ' ' . $_user_currency_logs->currency;
            $_user_currency_logs->date = $_user_currency_logs->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
        }
        $data = is_array($user_currency_logs) ? $user_currency_logs : $user_currency_logs->toArray();
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }
}
