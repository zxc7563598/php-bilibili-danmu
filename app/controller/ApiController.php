<?php

namespace app\controller;

use app\server\Bilibili;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidTimeZoneException;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use support\Request;
use Hejunjie\Bililive;
use Hejunjie\Tools;
use support\Response;

class ApiController
{

    /**
     * 获取用户信息
     * 
     * @return Response 
     * @throws Exception 
     */
    public function getUserInfo(Request $request)
    {
        // 获取登录信息配置
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        if ($cookie) {
            $user_info = Bililive\Login::getUserInfo($cookie);
            if (!$user_info['is_login']) {
                Tools\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
            }
        }
        // 返回数据
        return success($request, [
            'is_login' => isset($user_info['is_login']) ? $user_info['is_login'] : false,
            'data' => [
                'uid' => isset($user_info['uid']) ? $user_info['uid'] : 0,
                'uname' => isset($user_info['uname']) ? $user_info['uname'] : '',
                'face' => isset($user_info['face']) ? $user_info['face'] : ''
            ]
        ]);
    }

    /**
     * 获取直播间信息
     * 
     * @return Response 
     * @throws Exception 
     * @throws InvalidTimeZoneException 
     */
    public function getRealRoomInfo(Request $request)
    {
        $param = $request->all();
        $room_id = isset($param['room_id']) ? $param['room_id'] : 0;
        // 如果存在房间号则变更配置房间号
        $reconnect = false;
        if ($room_id > 0) {
            Tools\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
            Tools\FileUtils::writeToFile(runtime_path() . '/tmp/connect.cfg', $room_id);
            $reconnect = true;
        }
        // 获取直播间信息配置
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        $is_live = false;
        if ($room_id && $cookie) {
            $live_info = Bililive\Live::getRealRoomInfo($room_id, $cookie);
            $is_live = true;
            // 房间连接成功，重启websocket
            if ($live_info['code'] == 0 && $reconnect) {
                restartWebSocket();
            }
        }
        // 返回数据
        return success($request, [
            'is_live' => $is_live,
            'code' => isset($live_info['code']) ? $live_info['code'] : 0,
            'msg' => isset($live_info['msg']) ? $live_info['msg'] : '',
            'data' => [
                'uid' => isset($live_info['data']['uid']) ? $live_info['data']['uid'] : 0, // uid
                'room_id' => isset($live_info['data']['room_id']) ? $live_info['data']['room_id'] : 0, // 房间号
                'attention' => isset($live_info['data']['attention']) ? $live_info['data']['attention'] : 0, // 关注数量
                'online' => isset($live_info['data']['online']) ? $live_info['data']['online'] : 0, // 观看人数
                'live_status' => isset($live_info['data']['live_status']) ? $live_info['data']['live_status'] : 0, // 直播状态，0=未开播,1=直播中,2=轮播中
                'title' => isset($live_info['data']['title']) ? $live_info['data']['title'] : '', // 直播间标题
                'live_time' => isset($live_info['data']['live_time']) ? $live_info['data']['live_time'] : '', // 直播开始时间
                'keyframe' => isset($live_info['data']['keyframe']) ? $live_info['data']['keyframe'] : '' // 关键帧
            ]
        ]);
    }

    /**
     * 验证登录信息
     * 
     * @param string $qrcode_key 扫码登录密钥
     *  
     * @return Response 
     * @throws Exception 
     */
    public function loginCheck(Request $request)
    {
        $param = $request->all();
        $qrcode_key = isset($param['qrcode_key']) ? $param['qrcode_key'] : '';
        // 获取登录信息
        $checkQrcode = Bililive\Login::checkQrcode($qrcode_key);
        // 如果登录成功，存储cookie
        if ($checkQrcode['code'] == 0) {
            Tools\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
            Tools\FileUtils::writeToFile(runtime_path() . '/tmp/cookie.cfg', $checkQrcode['cookie']);
        }
        // 返回数据
        return success($request, [
            'code' => isset($checkQrcode['code']) ? $checkQrcode['code'] : '',
            'message' => isset($checkQrcode['message']) ? $checkQrcode['message'] : ''
        ]);
    }

    /**
     * 退出登录
     * 
     * @return Response 
     * @throws InvalidTimeZoneException 
     */
    public function loginOut(Request $request)
    {
        // 删除配置信息
        Tools\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
        Tools\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
        // 重启websocket
        restartWebSocket();
        // 返回数据
        return success($request);
    }

    /**
     * 断开直播间链接
     * 
     * @return Response 
     * @throws InvalidTimeZoneException 
     */
    public function connectOut(Request $request)
    {
        // 删除配置信息
        Tools\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
        // 重启websocket
        restartWebSocket();
        // 返回数据
        return success($request, []);
    }
}
