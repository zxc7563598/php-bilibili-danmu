<?php

namespace app\controller\shop\management;

use support\Request;
use support\Response;
use app\controller\GeneralMethod;
use app\model\GiftRecords;
use support\Db;

class GiftRecordsManagementController extends GeneralMethod
{
    /**
     * 获取用户列表
     * 
     * @param integer $page 页码
     * @param string $uid 用户UID
     * @param string $uname 用户名称
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        $param = $request->all();
        // 获取参数
        $page = $param['page'];
        $uid = $param['uid'] ?? null;
        $uname = $param['uname'] ?? null;
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
        foreach ($records as &$_records) {
            $_records->create_time = $_records->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s');
            unset($_records->created_at);
        }
        // 返回数据
        return success($request, [
            'list' => pageToArray($records),
            'num' => number_format($records_total->num, 0, '.', ','),
            'total_price' => number_format($records_total->total_price, 2, '.', ',')
        ]);
    }
}
