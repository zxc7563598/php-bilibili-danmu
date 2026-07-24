<?php

namespace app\controller\admin;

use app\core\ConfigService;
use app\core\RobotServices;
use Carbon\Carbon;
use support\Request;
use support\Response;
use Hejunjie\Bililive;
use Hejunjie\Utils;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use support\Cache;

class RobotControlController
{

    /**
     * 获取用户信息
     * 
     * @return Response 
     */
    public function getUserInfo(Request $request): Response
    {
        // 获取登录信息配置
        $cookie = RobotServices::getCookie();
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
    public function getRealRoomInfo(Request $request): Response
    {
        $room_id = $request->post('room_id', 0);
        // 如果存在房间号则变更配置房间号
        $reconnect = false;
        if ($room_id > 0) {
            Utils\FileUtils::fileDelete(runtime_path() . '/tmp/connect.cfg');
            Utils\FileUtils::writeToFile(runtime_path() . '/tmp/connect.cfg', $room_id);
            $reconnect = true;
        }
        // 获取直播间信息配置
        $cookie = RobotServices::getCookie();
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
    public function getConfig(Request $request): Response
    {
        $configs = ConfigService::all();
        return success($request, $configs);
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
    public function setConfig(Request $request): Response
    {
        // 限制请求频率
        $cache = Cache::get('robot_set_config');
        if (!empty($cache)) {
            return fail($request, 800016);
        }
        Cache::set('robot_set_config', 1, 30);
        // 批量写入配置
        ConfigService::set([
            'timing' => $request->post('timing', false),
            'present' => $request->post('present', false),
            'enter' => $request->post('enter', false),
            'pk' => $request->post('pk', false),
            'follow' => $request->post('follow', false),
            'share' => $request->post('share', false),
            'autoresponders' => $request->post('autoresponders', false),
            'check_in' => $request->post('check_in', false),
        ]);
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
    public function getLoginQr(Request $request): Response
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
    public function loginCheck(Request $request): Response
    {
        $qrcode_key = $request->post('qrcode_key', '');
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
    public function loginOut(Request $request): Response
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
    public function connectOut(Request $request): Response
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
    public function exportConfig(Request $request): Response
    {
        $path_name = Carbon::now()->timezone(config('app.default_timezone'))->format('YmdHis') . '.cfg';
        $data = ConfigService::export();
        // 写入导出文件
        $exportDir = public_path() . '/config/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        Utils\FileUtils::writeToFile($exportDir . $path_name, json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
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
    public function importConfig(Request $request): Response
    {
        // 获取上传的文件
        $file = $request->file('file');
        // 检查文件是否上传成功
        if (!$file || !$file->isValid()) {
            throw new \Exception("文件上传失败");
        }
        // 读取文件内容
        $text = Utils\FileUtils::readFile($file->getPathname());
        $data = json_decode($text, true);
        if (!is_array($data)) {
            throw new \Exception("配置文件格式无效");
        }
        // 批量写入配置
        ConfigService::set([
            'timing' => $data['timing'] ?? false,
            'present' => $data['present'] ?? false,
            'enter' => $data['enter'] ?? false,
            'pk' => $data['pk'] ?? false,
            'follow' => $data['follow'] ?? false,
            'share' => $data['share'] ?? false,
            'autoresponders' => $data['autoresponders'] ?? false,
            'check_in' => $data['check_in'] ?? false,
        ]);
        // 重启定时广告
        restartTiming();
        // 返回数据
        return success($request, [
            'data' => $data
        ]);
    }
}
