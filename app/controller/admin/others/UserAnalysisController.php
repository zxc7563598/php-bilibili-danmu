<?php

namespace app\controller\admin\others;

use support\Request;
use support\Response;
use app\controller\GeneralMethod;
use app\model\DanmuLogs;
use app\model\GiftRecords;
use app\model\Lives;
use app\model\UserVips;
use Carbon\Carbon;
use support\Db;

class UserAnalysisController extends GeneralMethod
{
    /**
     * 获取用户分析列表
     * 
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示数量
     * @param string $uid 用户uid
     * @param string $uname 用户名
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        $pageNo = $request->post('pageNo', 1);
        $pageSize = $request->post('pageSize', 30);
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        // 获取数据
        $user_vips = new UserVips();
        if (!is_null($uid)) {
            $user_vips = $user_vips->where('uid', $uid);
        }
        if (!is_null($uname)) {
            $user_vips = $user_vips->where('name', 'like', '%' . $uname . '%');
        }
        $user_vips = $user_vips->orderBy('total_gift_amount', 'desc')
            ->paginate($pageSize, [
                'uid' => 'uid',
                'name' => 'name',
                'point' => 'point',
                'total_gift_amount' => 'total_gift_amount',
                'total_danmu_count' => 'total_danmu_count'
            ], 'page', $pageNo);
        $data = is_array($user_vips) ? $user_vips : $user_vips->toArray();
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }

    /**
     * 获取每月分析数据
     * 
     * @param string $uid 用户UID
     * @param int $year 年份
     * @param int $month 月份
     * 
     * @return Response 
     */
    public function getDailyActive(Request $request)
    {
        $uid = $request->post('uid');
        $year = $request->post('year', 0);
        $month = $request->post('month', 0);
        // 获取目标年月，默认当前
        $timezone = config('app.default_timezone');
        $now = Carbon::now()->timezone($timezone);
        $year = ($year) > 0 ? (int)$year : (int)$now->year;
        $month = ($month) > 0 ? (int)$month : (int)$now->month;
        // 构造月份字符串并获取起止时间戳
        $targetDate = Carbon::createFromDate($year, $month, 1, $timezone);
        $start = $targetDate->copy()->startOfMonth()->startOfDay()->timestamp;
        $end = $targetDate->copy()->endOfMonth()->endOfDay()->timestamp;
        $daysInMonth = $targetDate->daysInMonth;
        // 初始化结果数组
        $monthData = array_fill(1, $daysInMonth, [
            'live' => false,
            'total_danmu_count' => 0,
            'total_gift_amount' => 0,
        ]);
        // 直播数据
        $lives = Lives::whereBetween('created_at', [$start, $end])
            ->groupByRaw("FROM_UNIXTIME(created_at, '%d')")
            ->get([
                'day' => Db::raw("FROM_UNIXTIME(created_at, '%d') as day")
            ]);
        foreach ($lives as $live) {
            $monthData[(int)$live->day]['live'] = true;
        }
        // 弹幕数据
        $danmuLogs = DanmuLogs::where('uid', $uid)
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw("FROM_UNIXTIME(created_at, '%d')")
            ->get([
                'day' => Db::raw("FROM_UNIXTIME(created_at, '%d') as day"),
                'count' => Db::raw("count(*) as count"),
            ]);
        foreach ($danmuLogs as $log) {
            $monthData[(int)$log->day]['total_danmu_count'] = $log->count;
        }
        // 礼物数据
        $giftRecords = GiftRecords::where('uid', $uid)
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw("FROM_UNIXTIME(created_at, '%d')")
            ->get([
                'day' => Db::raw("FROM_UNIXTIME(created_at, '%d') as day"),
                'total_price' => Db::raw("sum(total_price) as total_price"),
            ]);
        foreach ($giftRecords as $record) {
            $monthData[(int)$record->day]['total_gift_amount'] = $record->total_price;
        }
        // 返回数据
        return success($request, [
            'data' => $monthData,
            'month' => $month
        ]);
    }

    /**
     * 获取用户弹幕词频
     * 
     * @param string $uid 用户UID
     * 
     * @return Response 
     */
    public function getWordCloudFromText(Request $request)
    {
        $uid = $request->post('uid');
        // 从数据库获取弹幕内容
        $danmu_logs = DanmuLogs::where('uid', $uid)->pluck('msg')->toArray();
        // 准备 JSON 输入
        $jsonInput = json_encode($danmu_logs, JSON_UNESCAPED_UNICODE);
        // 调用 segment.php，并通过 stdin 传数据
        $descriptorSpec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];
        $process = proc_open("php " . base_path('scripts/segment.php'), $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            return fail($request, 900004);
        }
        fwrite($pipes[0], $jsonInput);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            return error($request, "分词失败: $errorOutput");
        }

        $topWords = json_decode($output, true);

        $text = [];
        foreach ($topWords as $word => $count) {
            $text[] = [
                'text' => $word,
                'weight' => $count
            ];
        }

        return success($request, [
            'text' => $text
        ]);
    }
}
