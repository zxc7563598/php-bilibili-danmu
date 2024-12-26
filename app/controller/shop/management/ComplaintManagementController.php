<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use app\model\Complaint;
use app\model\Goods;
use app\model\GoodSubs;
use app\model\RedemptionRecords;
use app\model\UserVips;
use Hejunjie\Tools;
use support\Request;
use resource\enums\ComplaintEnums;

class ComplaintManagementController extends GeneralMethod
{
    public function getData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $page = $param['page'];
        $uid = isset($param['uid']) ? $param['uid'] : null;
        $uname = isset($param['uname']) ? $param['uname'] : null;
        // 获取数据
        $complaint = Complaint::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_complaint.user_id');
        if (!is_null($uname)) {
            $complaint = $complaint->where('bl_user_vips.name', 'like', '%' . $uname . '%');
        }
        if (!is_null($uid)) {
            $complaint = $complaint->where('bl_user_vips.uid', 'like', '%' . $uid . '%');
        }
        $complaint = $complaint->orderBy('bl_complaint.created_at', 'desc')
            ->paginate(100, [
                'complaint_id' => 'bl_complaint.complaint_id',
                'uid' => 'bl_user_vips.uid',
                'user_name' => 'bl_user_vips.name as user_name',
                'title' => 'bl_complaint.title',
                'created_at' => 'bl_complaint.created_at'
            ], 'page', $page);
        // 处理数据
        foreach ($complaint as &$_complaint) {
            $_complaint->create_time = $_complaint->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            unset($_complaint->created_at);
        }
        // 返回数据
        return success($request, [
            'list' => pageToArray($complaint)
        ]);
    }

    public function getDataDetails(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $complaint_id = $param['complaint_id'];
        // 获取数据
        $complaint = Complaint::where('complaint_id', $complaint_id)->first([
            'complaint_id' => 'complaint_id',
            'title' => 'title',
            'content' => 'content',
            'read' => 'read'
        ]);
        $complaint->read = ComplaintEnums\Read::Read->value;
        $complaint->save();
        // 返回信息
        return success($request, [
            'complaint' => $complaint
        ]);
    }
}
