<?php

namespace app\controller\admin;

use app\core\ExportService;
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
    public function getUpdateLogs(Request $request): Response
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
                $log['meta'] = Carbon::parse($log['meta'])->timezone(config('app.default_timezone'))->format('Y-m-d H:i:s');
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
    public function readUpdateLogs(Request $request): Response
    {
        $id = $request->post('id');
        // 获取管理员id
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
    public function downloadSourceCode(Request $request): Response
    {
        // 获取参数
        $version = $request->post('version');
        // 校验版本号格式，防止路径遍历
        if (!preg_match('/^v\d+\.\d+\.\d+$/', $version)) {
            return fail($request, 800018);
        }
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
            'url' => config('app.api_url') . '/' . str_replace(public_path(), '', $file_path)
        ]);
    }

    /**
     * 创建数据导出任务
     *
     * 导出 bl_danmu_logs、bl_gift_records、bl_user_vips 三张表，数据量大，由队列异步执行，
     * 创建后通过 getExportProgress 轮询进度。
     *
     * @return Response
     */
    public function createExportTask(Request $request): Response
    {
        // 获取管理员id
        $admin_id = $request->admins['id'];
        // 创建任务
        try {
            $result = ExportService::createTask($admin_id);
        } catch (\Throwable $e) {
            sublog('数据导出', '创建任务', '创建失败', [
                'admin_id' => $admin_id,
                'error' => $e->getMessage()
            ]);
            return fail($request, 800021);
        }
        // 返回数据
        return success($request, [
            'task_id' => $result['task_id'],
            'reused' => $result['reused']
        ]);
    }

    /**
     * 获取数据导出进度
     *
     * @param string $task_id 导出任务ID
     *
     * @return Response
     */
    public function getExportProgress(Request $request): Response
    {
        // 获取参数
        $task_id = $request->post('task_id');
        // 获取进度
        $progress = ExportService::getProgress((string)$task_id);
        if (is_null($progress)) {
            return fail($request, 800020);
        }
        // 返回数据
        return success($request, $progress);
    }
}
