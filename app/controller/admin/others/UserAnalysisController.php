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
use Carbon\Exceptions\InvalidTimeZoneException;
use Carbon\Exceptions\InvalidFormatException;
use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\Jieba;
use InvalidArgumentException;
use RuntimeException;
use support\Db;

class UserAnalysisController extends GeneralMethod
{
    /**
     * 获取用户分析列表
     * 
     * @param integer $page 页码
     * @param string $uid 用户UID
     * @param string $uname 用户名称
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        // 获取参数
        $pageNo = $request->data['pageNo'];
        $pageSize = $request->data['pageSize'];
        $uid = $request->data['uid'] ?? null;
        $uname = $request->data['uname'] ?? null;
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
        // 获取数据
        $uid = $request->data['uid'];
        // 获取目标年月，默认当前
        $timezone = config('app.default_timezone');
        $now = Carbon::now()->timezone($timezone);
        $year = ($request->data['year'] ?? 0) > 0 ? (int)$request->data['year'] : (int)$now->year;
        $month = ($request->data['month'] ?? 0) > 0 ? (int)$request->data['month'] : (int)$now->month;
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
        // 获取数据
        $uid = $request->data['uid'];
        // 获取词频
        ini_set('memory_limit', '512M');
        Jieba::init();
        Finalseg::init();
        $danmu_logs = DanmuLogs::where('uid', $uid)->get([
            'msg' => 'msg',
        ]);
        // 初始化词频数组
        $wordFrequency = [];
        foreach ($danmu_logs as $_danmu_logs) {
            if (!str_starts_with($_danmu_logs->msg, '[')) {
                $words = Jieba::cut($_danmu_logs->msg, true); // 精确模式分词
                foreach ($words as $word) {
                    // 跳过标点、空白、停用词
                    if (mb_strlen($word) < 2) continue;
                    if (!isset($wordFrequency[$word])) {
                        $wordFrequency[$word] = 1;
                    } else {
                        $wordFrequency[$word]++;
                    }
                }
            }
        }
        // 排序：按词频从高到低
        arsort($wordFrequency);
        // 取前 50 个最常见词
        $topWords = array_slice($wordFrequency, 0, 50, true);
        // 输出词频
        $text = [];
        foreach ($topWords as $word => $count) {
            $text[] = [
                'text' => $word,
                'weight' => $count
            ];
        }
        // 返回数据
        return success($request, [
            'text' => $text
        ]);
    }
}
