<?php

namespace app\controller\robot\management;

use support\Request;
use support\Response;
use app\controller\GeneralMethod;
use app\model\GiftRecords;
use Carbon\Carbon;
use support\Db;

class GiftRecordsManagementController extends GeneralMethod
{
    /**
     * 获取礼物记录数据
     * 
     * @param integer $page 页码
     * @param string $uid 用户UID
     * @param string $uname 用户名称
     * 
     * @return Response 
     */
    public function getData(Request $request): Response
    {
        $page = $request->post('page', 1);
        $uid = $request->post('uid', null);
        $uname = $request->post('uname', null);
        $start_time = $request->post('start_time', null);
        $end_time = $request->post('end_time', null);
        // 获取数据
        $records = GiftRecords::query();
        $records_total = GiftRecords::query();
        if (!is_null($uid)) {
            $records->where('uid', 'like', '%' . $uid . '%');
            $records_total->where('uid', 'like', '%' . $uid . '%');
        }
        if (!is_null($uname)) {
            $records->where('uname', 'like', '%' . $uname . '%');
            $records_total->where('uname', 'like', '%' . $uname . '%');
        }
        if (!is_null($start_time)) {
            $start_time = Carbon::parse($start_time)->timezone(config('app')['default_timezone'])->timestamp;
            $records->where('created_at', '>=', $start_time);
            $records_total->where('created_at', '>=', $start_time);
        }
        if (!is_null($end_time)) {
            $end_time = Carbon::parse($end_time)->timezone(config('app')['default_timezone'])->timestamp;
            $records->where('created_at', '<', $end_time);
            $records_total->where('created_at', '<', $end_time);
        }
        $records = $records->orderBy('created_at', 'desc')
            ->paginate(100, [
                'uid' => 'uid',
                'uname' => 'uname',
                'gift_name' => 'gift_name',
                'price' => 'price',
                'num' => 'num',
                'total_price' => 'total_price',
                'created_at' => 'created_at'
            ], 'page', $page);
        // 获取总计数据
        $records_total = $records_total->first([
            Db::raw("ifNull(sum(num),0) as num"),
            Db::raw("ifNull(sum(total_price),0) as total_price"),
        ]);
        // 处理数据
        $list = pageToArray($records);
        foreach ($list['data'] as &$_list) {
            $_list['create_time'] = Carbon::parse($_list['created_at'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            unset($_list['created_at']);
        }
        // 返回数据
        return success($request, [
            'list' => $list,
            'num' => number_format($records_total->num, 0, '.', ','),
            'total_price' => number_format($records_total->total_price, 2, '.', ',')
        ]);
    }
}
