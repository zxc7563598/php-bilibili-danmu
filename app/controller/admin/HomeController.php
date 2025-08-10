<?php

namespace app\controller\admin;

use app\model\AdminUpdateLogs;
use app\model\AdminUpdateReads;
use Carbon\Carbon;
use support\Request;
use Hejunjie\Utils;
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
        $update_logs = AdminUpdateLogs::orderBy('id', 'desc')->get([
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
        ])->toArray();
        // 提取所有已读
        $log_id = array_map(fn($item) => $item['log_id'], $update_reads);
        // 构建展示数据
        $data = [];
        foreach ($update_logs as $i => $log) {
            if ($i === 0 || !in_array($log['id'], $log_id)) {
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
     * @param integer $id 版本日志ID
     * 
     * @return Response 
     */
    public function readUpdateLogs(Request $request)
    {
        // 获取管理员id
        $id = $request->data['id'];
        $admin_id = $request->admins['id'];
        // 获取是否已读
        $is_exist = AdminUpdateReads::where('log_id', $id)->where('admin_id', $admin_id)->count();
        // 增加已读
        if (!$is_exist) {
            $update_reads = new AdminUpdateReads();
            $update_reads->log_id = $id;
            $update_reads->admin_id = $admin_id;
            $update_reads->save();
        }
        // 返回数据
        return success($request, []);
    }

    /**
     * 由服务器下载后台源码
     * 
     * @param string $version 版本号
     * 
     * @return Response 
     */
    public function downloadSourceCode(Request $request)
    {
        // 获取参数
        $version = $request->data['version'];
        // 下载源代码
        $url = 'https://github.com/zxc7563598/vue-bilibili-danmu-admin/archive/refs/tags/' . $version . '.zip';
        $path = public_path('distSourceCode');
        try {
            $file_path = Utils\HttpClient::downloadFile($url, $path, $version);
        } catch (\Exception $e) {
            return fail($request, 800018);
        }
        // 返回数据
        return success($request, [
            'url' => config('app')['api_url'] . '/' . str_replace(public_path(), '', $file_path)
        ]);
    }
}
