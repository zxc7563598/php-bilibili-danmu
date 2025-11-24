<?php

namespace app\controller\robot\management;

use Carbon\Carbon;
use Hejunjie\Utils;
use support\Request;
use support\Response;
use app\model\UserVips;
use app\controller\GeneralMethod;
use resource\enums\UserVipsEnums;
use app\model\UserCurrencyLogs;
use Hejunjie\Bililive;
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
    public function getData(Request $request): Response
    {
        $param = $request->all();
        // 获取参数
        $page = $request->post('page', 1);
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
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
                'point' => 'point',
                'coin' => 'coin',
            ], 'page', $page);
        // 处理数据
        $list = pageToArray($users);
        foreach ($list['data'] as &$_list) {
            $_list['vip_type'] = UserVipsEnums\VipType::from($_list['vip_type'])->label();
            $_list['last_vip_at'] = Carbon::parse($_list['last_vip_at'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            $_list['end_vip_at'] = Carbon::parse($_list['end_vip_at'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
        }
        // 返回数据
        return success($request, ['list' => $list]);
    }

    /**
     * 获取用户详细信息
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserData(Request $request): Response
    {
        $user_id = $request->post('user_id');
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
    public function getUserInfo(Request $request): Response
    {
        $uid = $request->post('uid');
        // 获取数据
        $getMasterInfo = Bililive\Live::getMasterInfo($uid);
        // 返回数据
        return success($request, [
            'uname' => $getMasterInfo['name'] ?? null,
            'face' => $getMasterInfo['face'] ?? null,
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
    public function setData(Request $request): Response
    {
        $user_id = $request->post('user_id', null);
        $uid = $request->post('uid');
        $name = $request->post('name');
        $password = $request->post('password', null);
        $vip_type = $request->post('vip_type');
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
    public function resetPassword(Request $request): Response
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
     * 获取用户积分记录
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserPointRecords(Request $request): Response
    {
        $user_id = $request->post('user_id');
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
     * 获取用户硬币记录
     * 
     * @param integer $user_id 用户ID
     * 
     * @return Response 
     */
    public function getUserCoinRecords(Request $request): Response
    {
        $user_id = $request->post('user_id');
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
        $type = $request->post('type');
        $point = $request->post('point');
        $user_id = $request->post('user_id');
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
     * @param integer $point 变更积分 
     * @param integer $user_id 用户ID 
     * 
     * @return Response 
     */
    public function setUserCoin(Request $request)
    {
        $type = $request->post('type');
        $point = $request->post('point');
        $user_id = $request->post('user_id');
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
        $user_currency_logs->currency = $point;
        $user_currency_logs->pre_currency = $user_vips->coin;
        $user_currency_logs->after_currency = $type === UserCurrencyLogsEnums\Type::Up->value ? $user_vips->coin + $point : $user_vips->coin - $point;
        $user_currency_logs->save();
        // 返回数据
        return success($request, []);
    }
}
