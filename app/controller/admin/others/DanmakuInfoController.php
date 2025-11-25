<?php

namespace app\controller\admin\others;

use support\Request;
use support\Response;
use app\controller\GeneralMethod;
use app\model\DanmuLogs;
use Carbon\Carbon;
use resource\enums\DanmuLogsEnums;

class DanmakuInfoController extends GeneralMethod
{
    /**
     * 获取列表数据
     *
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示数量
     * @param string $uid 用户uid
     * @param string $uname 用户名
     * @param string $send_date 发送时间
     * 
     * @return Response
     */
    public function getData(Request $request): Response
    {
        $pageNo = $request->post('pageNo');
        $pageSize = $request->post('pageSize');
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        $send_date = $request->post('send_date', null);
        // 构建查询
        $danmu_logs = new DanmuLogs();
        if (!is_null($uname)) {
            $danmu_logs = $danmu_logs->where('uname', 'like', '%' . $uname . '%');
        }
        if (!is_null($uid)) {
            $danmu_logs = $danmu_logs->where('uid', $uid);
        }
        if (!is_null($send_date)) {
            list($start_time, $end_time) = $send_date;
            $start_time = intval($start_time / 1000);
            $end_time = intval($end_time / 1000);
            $danmu_logs = $danmu_logs->whereBetween('send_at', [$start_time, $end_time]);
        }
        // 查询并分页
        $danmu_logs = $danmu_logs->orderBy('send_at', 'desc')
            ->paginate($pageSize, [
                'id' => 'id',
                'uid' => 'uid',
                'uname' => 'uname',
                'msg' => 'msg',
                'live' => 'live',
                'badge_uid' => 'badge_uid',
                'badge_uname' => 'badge_uname',
                'badge_room_id' => 'badge_room_id',
                'badge_name' => 'badge_name',
                'badge_level' => 'badge_level',
                'badge_type' => 'badge_type',
                'send_at' => 'send_at'
            ], 'page', $pageNo);
        // 格式化数据
        $data = is_array($danmu_logs) ? $danmu_logs : $danmu_logs->toArray();
        foreach ($data['data'] as &$_data) {
            $_data['live'] = DanmuLogsEnums\Live::from($_data['live'])->label();
            $_data['send_time'] = Carbon::parse((int)$_data['send_at'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            unset($_data['send_at']);
        }
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }
}
