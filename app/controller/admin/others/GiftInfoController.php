<?php

namespace app\controller\admin\others;

use support\Request;
use support\Response;
use app\controller\GeneralMethod;
use app\model\GiftRecords;
use Carbon\Carbon;
use InvalidArgumentException;
use support\Db;

class GiftInfoController extends GeneralMethod
{
    /**
     * 获取礼物记录数据
     * 
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示数量
     * @param string $uid 用户uid
     * @param string $uname 用户名
     * @param array $create_date 赠送时间
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        $pageNo = $request->post('pageNo', 1);
        $pageSize = $request->post('pageSize', 30);
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        $create_date = $request->post('create_date', null);
        // 获取数据
        $records = new GiftRecords();
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
                'created_at' => 'created_at'
            ], 'page', $pageNo);
        // 处理数据
        $data = is_array($records) ? $records : $records->toArray();
        foreach ($data['data'] as &$_data) {
            $_data['create_time'] = Carbon::parse($_data['created_at'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
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
        // 获取参数
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        $create_date = $request->post('create_date', null);
        // 获取数据
        $records = new GiftRecords();
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
            Db::raw("ifNull(sum(num),0) as num"),
            Db::raw("ifNull(sum(total_price),0) as total_price"),
        ]);
        // 返回数据
        return success($request, [
            'num' => number_format($records->num, 0, '.', ','),
            'price' => number_format($records->total_price, 2, '.', ','),
            'unit' => $records->num > 0 ? number_format(round($records->total_price / $records->num, 2), 2, '.', ',') : '0.00'
        ]);
    }
}
