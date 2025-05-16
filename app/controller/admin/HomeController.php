<?php

namespace app\controller\admin;

use app\model\AdminUpdateLogs;
use app\model\AdminUpdateReads;
use Carbon\Carbon;
use support\Request;
use support\Response;

class HomeController
{
    /**
     * 获取更新日志
     * 
     * @return Response 
     */
    public function getUpdateLogs(Request $request)
    {
        // 获取管理员id
        $admin_id = $request->admins['id'];
        // 获取日志
        $update_logs = AdminUpdateLogs::orderBy('created_at', 'desc')->get([
            'id' => 'id',
            'version' => 'version',
            'title' => 'title',
            'description' => 'description',
            'content' => 'content',
            'meta' => 'meta'
        ])->toArray();
        // 获取管理员已读
        $update_reads = AdminUpdateReads::where('admin_id', $admin_id)->get([
            'log_id' => 'log_id'
        ]);
        // 提取所有已读
        $log_id = array_map(fn($item) => $item->log_id, $update_reads);
        // 构建展示数据
        $data = [];
        foreach ($update_logs as $i => $log) {
            if ($i === 0 || !in_array($log->id, $log_id)) {
                $log['meta'] = Carbon::parse($log['meta'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
                $data[] = $log;
            }
        }
        // 返回数据
        return success($request, [
            'logs' => $data
        ]);
    }

    /**
     * 更新日志标记已读
     * 
     * @return Response 
     */
    public function readUpdateLogs(Request $request)
    {
        // 获取管理员id
        $id = $request->data['id'];
        $admin_id = $request->admins['id'];
        // 增加已读
        $update_reads = new AdminUpdateReads();
        $update_reads->log_id = $id;
        $update_reads->admin_id = $admin_id;
        $update_reads->save();
        // 返回数据
        return success($request, []);
    }
}
