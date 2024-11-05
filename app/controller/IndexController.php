<?php

namespace app\controller;

use app\server\Bilibili;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use support\Request;
use Hejunjie\Bililive;
use Hejunjie\Tools;

class IndexController
{
    public function main(Request $request)
    {
        // 获取登录信息配置
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie'));
        if ($cookie) {
            $user_info = Bililive\Login::getUserInfo($cookie);
            if (!$user_info['is_login']) {
                Tools\FileUtils::fileDelete(runtime_path() . '/tmp/cookie');
            }
        }
        // 获取直播间信息配置
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect'));
        if ($room_id && $cookie) {
            $live_info = Bililive\Live::getRealRoomInfo($room_id, $cookie);
        }

        return view('main/console', [
            'user_info' => [
                'is_login' => isset($user_info['is_login']) ? $user_info['is_login'] : false,
                'uid' => isset($user_info['uid']) ? $user_info['uid'] : 0,
                'uname' => isset($user_info['uname']) ? $user_info['uname'] : '',
                'face' => isset($user_info['face']) ? $user_info['face'] : ''
            ],
            'live_info' => [
                'code' => isset($live_info['code']) ? $live_info['code'] : 0,
                'msg' => isset($live_info['msg']) ? $live_info['msg'] : '',
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

    public function login(Request $request)
    {
        // 获取登录信息
        $getQrcode = Bililive\Login::getQrcode();
        // 信息存储，并生成二维码
        $code = new Builder();
        $code->build(new PngWriter(), null, null, $getQrcode['url'], new Encoding('UTF-8'), null, 300, 10)
            ->saveToFile(public_path() . '/qrcode.png');
        // 返回数据
        return view('main/login', [
            'url' => $request->host() . '/qrcode.png',
            'qrcode_key' => $getQrcode['qrcode_key']
        ]);
    }
}
