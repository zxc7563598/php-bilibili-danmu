<?php

namespace app\controller\admin\others;

use support\Request;
use support\Response;
use app\controller\GeneralMethod;
use app\model\GiftRecords;
use Illuminate\Support\Carbon;
use support\Db;
use resource\enums\GiftRecordsEnums;

class GiftBlindBoxcontroller extends GeneralMethod
{
    /**
     * 获取盲盒信息数据
     * 
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示数量
     * @param string $uid 用户uid
     * @param string $uname 用户名
     * @param array $create_date 赠送时间
     * 
     * @return Response 
     */
    public function getData(Request $request): Response
    {
        $pageNo = $request->post('pageNo', 1);
        $pageSize = $request->post('pageSize', 30);
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        $create_date = $request->post('create_date', null);
        // 获取数据
        $records = GiftRecords::where('original', GiftRecordsEnums\Original::No->value);
        if (!is_null($uid)) {
            $records = $records->where('uid', $uid);
        }
        if (!is_null($uname)) {
            $records = $records->where('uname', 'like', '%' . $uname . '%');
        }
        if (!is_null($create_date)) {
            list($start_time, $end_time) = $create_date;
            $start_time = intval($start_time / 1000);
            $end_time = intval($end_time / 1000);
            $records = $records->whereBetween('created_at', [$start_time, $end_time]);
        }
        $records = $records->orderBy('created_at', 'desc')
            ->paginate($pageSize, [
                'uid' => 'uid',
                'uname' => 'uname',
                'gift_name' => 'gift_name',
                'price' => 'price',
                'num' => 'num',
                'total_price' => 'total_price',
                'original_gift_name' => 'original_gift_name',
                'original_price' => 'original_price',
                'created_at' => 'created_at'
            ], 'page', $pageNo);
        // 处理数据
        $data = is_array($records) ? $records : $records->toArray();
        foreach ($data['data'] as &$_data) {
            $_data['create_time'] = Carbon::parse($_data['created_at'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            $_data['original_price'] = round(($_data['original_price'] * $_data['num']), 2);
            $_data['profit_price'] = round(($_data['total_price'] - $_data['original_price']), 2);
            unset($_data['created_at']);
        }
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }

    /**
     * 获取统计数据
     * 
     * @param string $uid 用户UID
     * @param string $uname 用户名称
     * @param array $create_date 赠送时间
     * 
     * @return Response 
     */
    public function getStatisticData(Request $request)
    {
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        $create_date = $request->post('create_date', null);
        // 获取数据
        $records = GiftRecords::where('original', GiftRecordsEnums\Original::No->value);
        if (!is_null($uid)) {
            $records = $records->where('uid', $uid);
        }
        if (!is_null($uname)) {
            $records = $records->where('uname', 'like', '%' . $uname . '%');
        }
        if (!is_null($create_date)) {
            list($start_time, $end_time) = $create_date;
            $start_time = intval($start_time / 1000);
            $end_time = intval($end_time / 1000);
            $records = $records->whereBetween('created_at', [$start_time, $end_time]);
        }
        $records = $records->first([
            Db::raw("ifNull(sum(total_price),0) as total_price"),
            Db::raw("ifNull(sum(original_price * num),0) as original_price"),
        ]);
        // 返回数据
        return success($request, [
            'price' => number_format($records->total_price, 2, '.', ','),
            'original_price' => number_format($records->original_price, 2, '.', ','),
            'profit_price' => number_format(round($records->total_price - $records->original_price, 2), 2, '.', ',')
        ]);
    }

    /**
     * 导出盲盒信息数据
     * 
     * @param string $uid 用户uid
     * @param string $uname 用户名
     * @param array $create_date 赠送时间
     * 
     * @return Response 
     */
    public function exportData(Request $request): Response
    {
        ini_set('memory_limit', '-1');
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        $create_date = $request->post('create_date', null);
        // 获取数据
        $records = GiftRecords::where('original', GiftRecordsEnums\Original::No->value);
        if (!is_null($uid)) {
            $records = $records->where('uid', $uid);
        }
        if (!is_null($uname)) {
            $records = $records->where('uname', 'like', '%' . $uname . '%');
        }
        if (!is_null($create_date)) {
            list($start_time, $end_time) = $create_date;
            $start_time = intval($start_time / 1000);
            $end_time = intval($end_time / 1000);
            $records = $records->whereBetween('created_at', [$start_time, $end_time]);
        }
        $records = $records->orderBy('created_at', 'desc')
            ->get([
                'uid' => 'uid',
                'uname' => 'uname',
                'gift_name' => 'gift_name',
                'price' => 'price',
                'num' => 'num',
                'total_price' => 'total_price',
                'original_gift_name' => 'original_gift_name',
                'original_price' => 'original_price',
                'created_at' => 'created_at'
            ]);
        // 处理数据
        $csv_data = [];
        $csv_data[] = ["UID", "名称", "礼物名称", "赠送数量", "礼物单价", "礼物总价", "对应盲盒", "盲盒价格", "盈利金额", "赠送时间"];
        foreach ($records as $_data) {
            $original_price = round(($_data->original_price * $_data->num), 2);
            $profit_price = round(($_data->total_price - $original_price), 2);
            $csv_data[] = [
                $_data->uid,
                $_data->uname,
                $_data->gift_name,
                $_data->num,
                $_data->price,
                $_data->total_price,
                $_data->original_gift_name,
                $original_price,
                $profit_price,
                $_data->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s')
            ];
        }
        // 拼接 CSV 字符串
        $csvContent = "\xEF\xBB\xBF";
        foreach ($csv_data as $row) {
            $fh = fopen('php://temp', 'r+'); // 内存临时文件
            fputcsv($fh, $row);
            rewind($fh);
            $csvContent .= stream_get_contents($fh);
            fclose($fh);
        }
        // 构造 Response 返回
        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"export.csv\"; filename*=UTF-8''" . rawurlencode('盲盒信息.csv'),
        ]);
    }
}
