<?php

namespace app\controller\admin\others;

use support\Request;
use support\Response;
use app\model\Complaint;
use app\controller\GeneralMethod;
use app\model\DanmuLogs;
use Carbon\Carbon;
use resource\enums\DanmuLogsEnums;

class DanmakuInfoController extends GeneralMethod
{
    /**
     * 获取列表数据
     *
     * @param integer $page 页码
     * @param integer $uid 用户uid
     * @param string $uname 用户名
     * 
     * @return Response
     */
    public function getData(Request $request)
    {
        $pageNo = $request->data['pageNo'];
        $pageSize = $request->data['pageSize'];
        $uid = $request->data['uid'] ?? null;
        $uname = $request->data['uname'] ?? null;
        $send_date = $request->data['send_date'] ?? null;
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
        foreach ($danmu_logs as &$_danmu_logs) {
            // $_danmu_logs->badge_type = DanmuLogsEnums\BadgeType::from($_danmu_logs->badge_type)->label();
            $_danmu_logs->live = DanmuLogsEnums\Live::from($_danmu_logs->live)->label();
            $_danmu_logs->send_time = Carbon::parse((int)$_danmu_logs->send_at)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            unset($_danmu_logs->send_at);
        }
        $data = is_array($danmu_logs) ? $danmu_logs : $danmu_logs->toArray();
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }
}
