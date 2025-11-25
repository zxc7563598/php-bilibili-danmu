<?php

namespace app\controller\admin\others;

use support\Request;
use support\Response;
use app\model\Complaint;
use app\controller\GeneralMethod;
use Illuminate\Support\Carbon;
use resource\enums\ComplaintEnums;

class ComplaintManagementController extends GeneralMethod
{
    /**
     * 获取投诉数据列表
     *
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示数量
     * @param string $uid 用户uid
     * @param string $uname 用户名
     * 
     * @return Response
     */
    public function getData(Request $request): Response
    {
        $pageNo = $request->post('pageNo', 1);
        $pageSize = $request->post('pageSize', 30);
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        // 构建查询
        $complaintQuery = Complaint::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_complaint.user_id');
        if (!is_null($uname)) {
            $complaintQuery->where('bl_user_vips.name', 'like', '%' . $uname . '%');
        }
        if (!is_null($uid)) {
            $complaintQuery->where('bl_user_vips.uid', $uid);
        }
        // 查询并分页
        $complaints = $complaintQuery->orderBy('bl_complaint.created_at', 'desc')
            ->paginate($pageSize, [
                'complaint_id' => 'bl_complaint.complaint_id',
                'uid' => 'bl_user_vips.uid as uid',
                'user_name' => 'bl_user_vips.name as user_name',
                'title' => 'bl_complaint.title as title',
                'read' => 'bl_complaint.read as read',
                'created_at' => 'bl_complaint.created_at as created_at',
            ], 'page', $pageNo);
        // 格式化数据
        $data = is_array($complaints) ? $complaints : $complaints->toArray();
        foreach ($data['data'] as &$_data) {
            $_data['read'] = ComplaintEnums\Read::from($_data['read'])->label();
            $_data['create_time'] = Carbon::parse($_data['created_at'])->timezone(config('app.default_timezone'))->format('Y-m-d H:i:s');
            unset($_data['created_at']);
        }
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }

    /**
     * 获取投诉详情
     *
     * @param integer $complaint_id 投诉ID
     * 
     * @return Response
     */
    public function getDataDetails(Request $request): Response
    {
        // 获取投诉 ID
        $complaintId = $request->post('complaint_id', null);
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
