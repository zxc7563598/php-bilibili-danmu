<?php

namespace app\controller\shop\management;

use support\Request;
use support\Response;
use app\model\Complaint;
use app\controller\GeneralMethod;
use resource\enums\ComplaintEnums;

class ComplaintManagementController extends GeneralMethod
{
    /**
     * 获取投诉数据列表
     *
     * @param integer $page 页码
     * @param integer $uid 用户uid
     * @param string $uname 用户名
     * 
     * @return Response
     */
    public function getData(Request $request)
    {
        $param = $request->all();
        // 分页参数
        $page = $param['page'] ?? 1;
        // 查询参数
        $uid = $param['uid'] ?? null;
        $uname = $param['uname'] ?? null;
        // 构建查询
        $complaintQuery = Complaint::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_complaint.user_id');
        if (!is_null($uname)) {
            $complaintQuery->where('bl_user_vips.name', 'like', '%' . $uname . '%');
        }
        if (!is_null($uid)) {
            $complaintQuery->where('bl_user_vips.uid', 'like', '%' . $uid . '%');
        }
        // 查询并分页
        $complaints = $complaintQuery->orderBy('bl_complaint.created_at', 'desc')
            ->paginate(100, [
                'complaint_id' => 'bl_complaint.complaint_id',
                'uid' => 'bl_user_vips.uid as uid',
                'user_name' => 'bl_user_vips.name as user_name',
                'title' => 'bl_complaint.title as title',
                'created_at' => 'bl_complaint.created_at as created_at',
            ], 'page', $page);
        // 格式化数据
        foreach ($complaints as $complaint) {
            $complaint->create_time = $complaint->created_at->timezone(config('app.default_timezone'))->format('Y-m-d H:i:s');
            unset($complaint->created_at);
        }
        // 返回结果
        return success($request, [
            'list' => pageToArray($complaints),
        ]);
    }

    /**
     * 获取投诉详情
     *
     * @param integer $complaint_id 投诉ID
     * 
     * @return Response
     */
    public function getDataDetails(Request $request)
    {
        $param = $request->all();
        // 获取投诉 ID
        $complaintId = $param['complaint_id'] ?? null;
        if (!$complaintId) {
            return fail($request, 800015);
        }
        // 查询投诉详情
        $complaint = Complaint::where('complaint_id', $complaintId)->first([
            'complaint_id' => 'complaint_id',
            'title' => 'title',
            'content' => 'content',
            'read' => 'read'
        ]);
        if (!$complaint) {
            return fail($request, 800014);
        }
        // 更新为已读状态
        $complaint->read = ComplaintEnums\Read::Read->value;
        $complaint->save();
        // 返回投诉详情
        return success($request, [
            'complaint' => $complaint,
        ]);
    }
}
