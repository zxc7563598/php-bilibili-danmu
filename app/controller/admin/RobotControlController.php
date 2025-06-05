<?php

namespace app\controller\admin;

use Carbon\Carbon;
use support\Request;
use support\Response;
use Hejunjie\Bililive;
use Hejunjie\Utils;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Exception;
use support\Redis;

class RobotControlController
{

    /**
     * 获取用户信息
     * 
     * @return Response 
     */
    public function getUserInfo(Request $request)
    {
        // 获取登录信息配置
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        if ($cookie) {
            $user_info = Bililive\Login::getUserInfo($cookie);
            if (!$user_info['is_login']) {
                Utils\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
                Utils\FileUtils::fileDelete(runtime_path() . '/tmp/uid.cfg');
            }
        }
        if (isset($user_info['uid'])) {
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/uid.cfg', $user_info['uid']);
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
     * @param int $room_id 房间号
     * 
     * @return Response 
     */
    public function getRealRoomInfo(Request $request)
    {
        $room_id = isset($request->data['room_id']) ? $request->data['room_id'] : 0;
        // 如果存在房间号则变更配置房间号
        $reconnect = false;
        if ($room_id > 0) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/connect.cfg', $room_id);
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
                try {
                    Bililive\Live::getInitialWebSocketUrl($room_id, $cookie);
                } catch (\Exception $e) {
                    return fail($request, 800019);
                }
                restartBilibili();
                Utils\FileUtils::fileDelete(runtime_path() . '/tmp/room_uinfo.cfg');
                Utils\FileUtils::writeToFile(runtime_path() . '/tmp/room_uinfo.cfg', json_encode($live_info['data'], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
            }
        }
        // 返回数据
        return success($request, [
            'is_live' => $is_live,
            'code' => isset($live_info['code']) ? $live_info['code'] : 0,
            'msg' => isset($live_info['msg']) ? $live_info['msg'] : '',
            'data' => [
                'uid' => isset($live_info['data']['uid']) ? $live_info['data']['uid'] : 0, // uid
                'uname' => isset($live_info['data']['uname']) ? $live_info['data']['uname'] : '', // uname
                'face' => isset($live_info['data']['face']) ? $live_info['data']['face'] : '', // 头像
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
     * 获取配置信息
     * 
     * @return Response 
     */
    public function getConfig(Request $request)
    {
        // 获取定时广告配置
        $timing = readFileContent(runtime_path() . '/tmp/timing.cfg');
        if ($timing) {
            $timing = json_decode($timing, true);
        }
        if (!$timing) {
            $timing = [
                'opens' => false, // 是否开启
                'intervals' => "60", // 间隔时间
                'status' => "0", // 状态
                'content' => "" // 内容
            ];
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/timing.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/timing.cfg', json_encode($timing));
        }
        // 获取礼物答谢配置
        $present = readFileContent(runtime_path() . '/tmp/present.cfg');
        if ($present) {
            $present = json_decode($present, true);
        }
        if (!$present) {
            $present = [
                'opens' => false, // 是否开启
                'merge' => "0", // 是否合并
                'number' => "0", // 展示数量
                'price' => "0", // 起始感谢金额
                'status' => "0", // 状态 
                'type' => "0", // 状态 0=全部答谢，1=仅答谢牌子，2=仅答谢航海
                'content' => "" // 内容
            ];
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/present.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/present.cfg', json_encode($present));
        }
        // 获取进房欢迎配置
        $enter = readFileContent(runtime_path() . '/tmp/enter.cfg');
        if ($enter) {
            $enter = json_decode($enter, true);
        }
        if (!$enter) {
            $enter = [
                'opens' => false, // 是否开启
                'status' => "0", // 状态
                'type' => "0", // 类型：0=全部欢迎，1=仅欢迎牌子，2=仅欢迎航海
                'content' => "" // 内容
            ];
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/enter.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/enter.cfg', json_encode($enter));
        }
        // 获取PK播报配置
        $pk = readFileContent(runtime_path() . '/tmp/pk.cfg');
        if ($pk) {
            $pk = json_decode($pk, true);
        }
        if (!$pk) {
            $pk = [
                'opens' => false, // 是否开启
                'content' => "" // 内容
            ];
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/pk.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/pk.cfg', json_encode($pk));
        }
        // 获取感谢关注配置
        $follow = readFileContent(runtime_path() . '/tmp/follow.cfg');
        if ($follow) {
            $follow = json_decode($follow, true);
        }
        if (!$follow) {
            $follow = [
                'opens' => false, // 是否开启
                'status' => "0", // 状态
                'type' => "0", // 类型：0=全部感谢，1=仅感谢牌子，2=仅感谢航海
                'content' => "" // 内容
            ];
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/follow.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/follow.cfg', json_encode($follow));
        }
        // 获取定时广告配置
        $share = readFileContent(runtime_path() . '/tmp/share.cfg');
        if ($share) {
            $share = json_decode($share, true);
        }
        if (!$share) {
            $share = [
                'opens' => false, // 是否开启
                'status' => "0", // 状态
                'type' => "0", // 类型：0=全部感谢，1=仅感谢牌子，2=仅感谢航海
                'content' => "" // 内容
            ];
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/share.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/share.cfg', json_encode($share));
        }
        // 获取自动回复配置
        $checkIn = readFileContent(runtime_path() . '/tmp/check-in.cfg');
        if ($checkIn) {
            $checkIn = json_decode($checkIn, true);
        }
        if (!$checkIn) {
            $checkIn = [
                'opens' => false, // 是否开启
                'status' => "0", // 状态
                'type' => "0", // 类型：0=全部感谢，1=仅感谢牌子，2=仅感谢航海
                'keywords' => "", // 签到词
                'select' => "", // 查询词
                'success' => "", // 成功回复
                'reply' => "" // 查询回复
            ];
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/check-in.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/check-in.cfg', json_encode($checkIn));
        }
        // 获取自动回复配置
        $autoresponders = readFileContent(runtime_path() . '/tmp/autoresponders.cfg');
        if ($autoresponders) {
            $autoresponders = json_decode($autoresponders, true);
        }
        if (!$autoresponders) {
            $autoresponders = [
                'opens' => false, // 是否开启
                'status' => "0", // 状态
                'type' => "0", // 类型：0=全部响应，1=仅响应牌子，2=仅响应航海
                'content' => [] // 内容
            ];
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/autoresponders.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/autoresponders.cfg', json_encode($autoresponders));
        }
        // 返回数据
        return success($request, [
            'timing' => $timing,
            'present' => $present,
            'enter' => $enter,
            'pk' => $pk,
            'follow' => $follow,
            'share' => $share,
            'autoresponders' => $autoresponders,
            'check_in' => $checkIn
        ]);
    }

    /**
     * 存储配置信息
     * 
     * @param array $timing 定时广告配置
     * @param array $present 礼物答谢配置
     * @param array $enter 进房欢迎配置
     * @param array $pk PK播报配置
     * @param array $follow 感谢关注配置
     * @param array $share 感谢分享配置
     * @param array $autoresponders 自动回复配置
     * @param array $check_in 签到配置
     * 
     * @return Response 
     */
    public function setConfig(Request $request)
    {
        // 限制请求频率
        $redis = Redis::get(config('app')['app_name'] . ':robot_set_config');
        if (!empty($redis)) {
            return fail($request, 800016);
        }
        Redis::setEx(config('app')['app_name'] . ':robot_set_config', 30, 1);
        // 获取参数
        $timing = $request->data['timing'] ?: false;
        $present = $request->data['present'] ?: false;
        $enter = $request->data['enter'] ?: false;
        $pk = $request->data['pk'] ?: false;
        $follow = $request->data['follow'] ?: false;
        $share = $request->data['share'] ?: false;
        $autoresponders = $request->data['autoresponders'] ?: false;
        $check_in = $request->data['check_in'] ?: false;
        // 存储数据
        if ($timing) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/timing.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/timing.cfg', json_encode($timing, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($present) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/present.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/present.cfg', json_encode($present, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($enter) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/enter.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/enter.cfg', json_encode($enter, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($pk) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/pk.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/pk.cfg', json_encode($pk, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($follow) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/follow.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/follow.cfg', json_encode($follow, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($share) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/share.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/share.cfg', json_encode($share, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($autoresponders) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/autoresponders.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/autoresponders.cfg', json_encode($autoresponders, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($check_in) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/check-in.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/check-in.cfg', json_encode($check_in, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        // 重启定时广告
        restartTiming();
        // 返回数据
        return success($request);
    }

    /**
     * 获取登录二维码
     * 
     * @return Response 
     */
    public function getLoginQr(Request $request)
    {
        // 获取登录信息
        $getQrcode = Bililive\Login::getQrcode();
        $qrcode = md5($getQrcode['qrcode_key'] . 'qrcode') . '.png';
        // 确认目录信息，不存在则创建
        if (!is_dir(public_path() . '/attachment/qrcode')) {
            mkdir(public_path() . '/attachment/qrcode', 0777, true);
        }
        // 信息存储，并生成二维码
        $code = new Builder();
        $code->build(new PngWriter(), null, null, $getQrcode['url'], new Encoding('UTF-8'), null, 300, 10)
            ->saveToFile(public_path() . '/attachment/qrcode/' . $qrcode);
        // 返回数据
        return success($request, [
            'url' => getImageUrl('qrcode/' . $qrcode),
            'qrcode_key' => $getQrcode['qrcode_key']
        ]);
    }

    /**
     * 验证登录信息
     * 
     * @param string $qrcode_key 扫码登录密钥
     *  
     * @return Response 
     */
    public function loginCheck(Request $request)
    {
        $qrcode_key = $request->data['qrcode_key'] ?: '';
        // 获取登录信息
        $checkQrcode = Bililive\Login::checkQrcode($qrcode_key);
        // 如果登录成功，存储cookie
        if ($checkQrcode['code'] == 0) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/cookie.cfg', $checkQrcode['cookie']);
            // 删除二维码
            $qrcode = md5($qrcode_key . 'qrcode') . '.png';
            Utils\FileUtils::fileDelete(public_path() . '/attachment/qrcode/' . $qrcode);
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
     */
    public function loginOut(Request $request)
    {
        // 删除配置信息
        Utils\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
        Utils\FileUtils::fileDelete(runtime_path() . '/tmp/uid.cfg');
        Utils\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
        // 重启websocket
        restartBilibili();
        // 返回数据
        return success($request);
    }

    /**
     * 断开直播间链接
     * 
     * @return Response 
     */
    public function connectOut(Request $request)
    {
        // 删除配置信息
        Utils\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
        // 重启websocket
        restartBilibili();
        // 返回数据
        return success($request, []);
    }

    /**
     * 导出配置文件
     * 
     * @return Response 
     */
    public function exportConfig(Request $request)
    {
        $path_name = Carbon::now()->timezone(config('app')['default_timezone'])->format('YmdHis') . '.cfg';
        // 获取定时广告配置
        $timing = readFileContent(runtime_path() . '/tmp/timing.cfg');
        if ($timing) {
            $timing = json_decode($timing, true);
        }
        if (!$timing) {
            $timing = [
                'opens' => false, // 是否开启
                'intervals' => null, // 间隔时间
                'status' => 0, // 状态
                'content' => null // 内容
            ];
        }
        // 获取礼物答谢配置
        $present = readFileContent(runtime_path() . '/tmp/present.cfg');
        if ($present) {
            $present = json_decode($present, true);
        }
        if (!$present) {
            $present = [
                'opens' => false, // 是否开启
                'price' => null, // 起始感谢金额
                'number' => 0, // 数量
                'merge' => 0, // 礼物合并
                'status' => 0, // 状态 
                'type' => 0, // 状态 0=全部答谢，1=仅答谢牌子，2=仅答谢航海
                'content' => null // 内容
            ];
        }
        // 获取进房欢迎配置
        $enter = readFileContent(runtime_path() . '/tmp/enter.cfg');
        if ($enter) {
            $enter = json_decode($enter, true);
        }
        if (!$enter) {
            $enter = [
                'opens' => false, // 是否开启
                'status' => 0, // 状态
                'type' => 0, // 类型：0=全部欢迎，1=仅欢迎牌子，2=仅欢迎航海
                'content' => null // 内容
            ];
        }
        // 获取PK播报配置
        $pk = readFileContent(runtime_path() . '/tmp/pk.cfg');
        if ($pk) {
            $pk = json_decode($pk, true);
        }
        if (!$pk) {
            $pk = [
                'opens' => false, // 是否开启
                'content' => null // 内容
            ];
        }
        // 获取感谢关注配置
        $follow = readFileContent(runtime_path() . '/tmp/follow.cfg');
        if ($follow) {
            $follow = json_decode($follow, true);
        }
        if (!$follow) {
            $follow = [
                'opens' => false, // 是否开启
                'status' => 0, // 状态
                'type' => 0, // 类型：0=全部感谢，1=仅感谢牌子，2=仅感谢航海
                'content' => null // 内容
            ];
        }
        // 获取定时广告配置
        $share = readFileContent(runtime_path() . '/tmp/share.cfg');
        if ($share) {
            $share = json_decode($share, true);
        }
        if (!$share) {
            $share = [
                'opens' => false, // 是否开启
                'status' => 0, // 状态
                'type' => 0, // 类型：0=全部感谢，1=仅感谢牌子，2=仅感谢航海
                'content' => null // 内容
            ];
        }
        // 获取自动回复配置
        $check_in = readFileContent(runtime_path() . '/tmp/check-in.cfg');
        if ($check_in) {
            $check_in = json_decode($check_in, true);
        }
        if (!$check_in) {
            $check_in = [
                'opens' => false, // 是否开启
                'status' => 0, // 状态
                'type' => 0, // 类型：0=全部感谢，1=仅感谢牌子，2=仅感谢航海
                'points' => 0, // 赠送积分
                'keywords' => null, // 签到词
                'select' => null, // 查询词
                'success' => null, // 成功回复
                'reply' => null // 查询回复
            ];
        }
        // 获取自动回复配置
        $autoresponders = readFileContent(runtime_path() . '/tmp/autoresponders.cfg');
        if ($autoresponders) {
            $autoresponders = json_decode($autoresponders, true);
        }
        if (!$autoresponders) {
            $autoresponders = [
                'opens' => false, // 是否开启
                'status' => 0, // 状态
                'type' => 0, // 类型：0=全部响应，1=仅响应牌子，2=仅响应航海
                'content' => [] // 内容
            ];
        }
        // 返回数据
        Utils\FileUtils::writeToFile(public_path() . '/config/' . $path_name, json_encode([
            'timing' => $timing,
            'present' => $present,
            'enter' => $enter,
            'pk' => $pk,
            'follow' => $follow,
            'share' => $share,
            'autoresponders' => $autoresponders,
            'check_in' => $check_in
        ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        // 返回数据
        return success($request, [
            'url' => $request->host() . '/config/' . $path_name
        ]);
    }

    /**
     * 导入配置文件
     * 
     * @return Response 
     */
    public function importConfig(Request $request)
    {
        // 获取上传的文件
        $file = $request->file('file');
        // 检查文件是否上传成功
        if (!$file || !$file->isValid()) {
            throw new \Exception("文件上传失败");
        }
        // 获取文件临时路径
        $text = Utils\FileUtils::readFile($file->getPathname());
        // 读取文件内容
        $data = json_decode($text, true);
        $timing = isset($data['timing']) ? $data['timing'] : false;
        $present = isset($data['present']) ? $data['present'] : false;
        $enter = isset($data['enter']) ? $data['enter'] : false;
        $pk = isset($data['pk']) ? $data['pk'] : false;
        $follow = isset($data['follow']) ? $data['follow'] : false;
        $share = isset($data['share']) ? $data['share'] : false;
        $autoresponders = isset($data['autoresponders']) ? $data['autoresponders'] : false;
        $check_in = isset($data['check_in']) ? $data['check_in'] : false;
        // 存储数据
        if ($timing) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/timing.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/timing.cfg', json_encode($timing, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($present) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/present.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/present.cfg', json_encode($present, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($enter) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/enter.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/enter.cfg', json_encode($enter, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($pk) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/pk.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/pk.cfg', json_encode($pk, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($follow) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/follow.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/follow.cfg', json_encode($follow, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($share) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/share.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/share.cfg', json_encode($share, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($autoresponders) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/autoresponders.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/autoresponders.cfg', json_encode($autoresponders, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        if ($check_in) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/check-in.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/check-in.cfg', json_encode($check_in, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        }
        // 重启定时广告
        restartTiming();
        // 返回数据
        return success($request, [
            'data' => $data
        ]);
    }
}
