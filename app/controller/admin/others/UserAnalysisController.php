<?php

namespace app\controller\admin\others;

use support\Request;
use support\Response;
use app\controller\GeneralMethod;
use app\model\GiftRecords;
use app\model\UserVips;
use Carbon\Carbon;
use InvalidArgumentException;
use support\Db;

class GiftInfoController extends GeneralMethod
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
            $user_vips = $user_vips->where('uname', 'like', '%' . $uname . '%');
        }
        $user_vips = $user_vips->orderBy('created_at', 'desc')
            ->paginate($pageSize, [
                'uid' => 'uid',
                'uname' => 'uname',
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
}
