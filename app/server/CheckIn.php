<?php

namespace app\server;

use app\core\LoginPublicMethods;
use app\core\RobotServices;
use app\model\SystemChangePointRecords;
use app\model\UserCheckIn;
use app\model\UserVips;
use app\queue\SendMessage;
use Carbon\Carbon;
use support\Redis;
use resource\enums\SystemChangePointRecordsEnums;

/**
 * 签到，优先级15
 */
class CheckIn
{
    /**
     * 处理数据
     * 
     * @param mixed $uid 用户uid
     * @param mixed $uname 用户名称
     * @param mixed $ruid 用户携带的牌子归属主播的uid
     * @param mixed $guard_level 大航海类型，0=普通用户，1=总督，2=提督，3=舰长
     * @param mixed $msg 消息内容
     * 
     * @return void 
     */
    public static function processing($uid, $uname, $ruid, $guard_level, $msg)
    {
        $is_message = false;
        // 不处理自己发送的消息
        $robot_uid = strval(readFileContent(runtime_path() . '/tmp/uid.cfg'));
        // 获取感谢关注配置
        $check_in = readFileContent(runtime_path() . '/tmp/check-in.cfg');
        if ($check_in) {
            $check_in = json_decode($check_in, true);
        }
        // 开启感谢关注
        if (isset($check_in['opens']) && $check_in['opens'] && $uid != $robot_uid) {
            sublog('核心业务', '用户签到', "入参检测", [
                'uid' => $uid,
                'uname' => $uname,
                'ruid' => $ruid,
                'guard_level' => $guard_level,
                'msg' => $msg
            ]);
            $check_in_type = intval($check_in['type']); // 类型
            $check_in_status = intval($check_in['status']); // 状态：0=不论何时，1-仅在直播时，2-仅在非直播时
            $check_in_points = intval($check_in['points'] ?? 0); // 赠送积分
            $check_in_content = '';
            $total_point = 0;
            $coin = 0;
            $next = false;
            $total = 0;
            $serial = 0;
            // 用户签到
            if (!empty($check_in['keywords']) && $check_in['keywords'] == $msg) {
                // 确认当天是否签到
                $today = UserCheckIn::where('uid', $uid)->where('created_at', '>=', Carbon::today()->timezone(config('app')['default_timezone'])->timestamp)->count();
                if ($today == 0) {
                    // 记录签到
                    $user_check_in = new UserCheckIn();
                    $user_check_in->uid = $uid;
                    $user_check_in->name = $uname;
                    $user_check_in->ruid = $ruid;
                    $user_check_in->guard_level = $guard_level;
                    $user_check_in->points = $check_in_points;
                    $user_check_in->save();
                    // 获取用户信息
                    $user_vips = UserVips::where('uid', $uid)->first();
                    if (empty($user_vips)) {
                        LoginPublicMethods::userRegister($uid, $uname);
                        $user_vips = UserVips::where('uid', $uid)->first();
                    }
                    $total_point = $user_vips->point;
                    $coin = $user_vips->coin;
                    // 查询昨天是否签到
                    $day = UserCheckIn::where('uid', $uid)
                        ->where('created_at', '>=', Carbon::today()->subDays(1)->timezone(config('app')['default_timezone'])->timestamp)
                        ->where('created_at', '<', Carbon::today()->timestamp)
                        ->count();
                    // 记录用户签到天数信息
                    $user_vips->total_check_in = UserCheckIn::where('uid', $uid)->count();
                    if ($day > 0) {
                        $user_vips->serial_check_in += 1;
                    } else {
                        $user_vips->serial_check_in = 1;
                    }
                    $user_vips->save();
                    // 增加积分
                    if ($user_check_in->points > 0) {
                        $system_change_point_records = new SystemChangePointRecords();
                        $system_change_point_records->user_id = $user_vips->user_id;
                        $system_change_point_records->type = SystemChangePointRecordsEnums\Type::Up->value;
                        $system_change_point_records->source = SystemChangePointRecordsEnums\Source::SignIn->value;
                        $system_change_point_records->point_type = SystemChangePointRecordsEnums\PointType::Coin->value;
                        $system_change_point_records->point = $user_check_in->points;
                        $system_change_point_records->pre_point = $user_vips->coin;
                        $system_change_point_records->after_point = $user_vips->coin + $user_check_in->points;
                        $system_change_point_records->save();
                    }
                    $total = $user_vips->total_check_in;
                    $serial = $user_vips->serial_check_in;
                    $check_in_content = $check_in['success'];
                    $next = true;
                } else {
                    $check_in_content = "@name@你已经签到啦";
                    $next = true;
                }
            }
            // 用户查询
            if (!empty($check_in['select']) && $check_in['select'] == $msg) {
                // 获取用户信息
                $user_vips = UserVips::where('uid', $uid)->first();
                if (empty($user_vips)) {
                    LoginPublicMethods::userRegister($uid, $uname);
                    $user_vips = UserVips::where('uid', $uid)->first();
                }
                $total = $user_vips->total_check_in;
                $serial = $user_vips->serial_check_in;
                $check_in_content = $check_in['reply'];
                $total_point = $user_vips->point;
                $coin = $user_vips->coin;
                $next = true;
            }
            // 回复消息
            if ($next) {
                // 确认链接直播间的情况
                $cookie = RobotServices::getCookie();
                $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
                $room_uinfo = !empty(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg'))) ? json_decode(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg')), true) : [];
                if ($cookie && $room_id) {
                    // 验证牌子
                    $medal = false;
                    switch ($check_in_type) {
                        case 0: // 全部答谢
                            $medal = true;
                            break;
                        case 1: // 仅答谢牌子
                            if (isset($room_uinfo['uid']) && $room_uinfo['uid'] == $ruid) {
                                $medal = true;
                            }
                            break;
                        case 2: // 仅答谢航海
                            if ((isset($room_uinfo['uid']) && $room_uinfo['uid'] == $ruid) && ($guard_level > 0)) {
                                $medal = true;
                            }
                            break;
                    }
                    // 验证时间段
                    if ($medal) {
                        switch ($check_in_status) {
                            case 0: // 不论何时
                                $is_message = true;
                                break;
                            case 1: // 仅在直播中
                                if (Redis::get('bilibili_live_key')) {
                                    $is_message = true;
                                }
                                break;
                            case 2: // 仅在非直播中
                                if (!Redis::get('bilibili_live_key')) {
                                    $is_message = true;
                                }
                                break;
                        }
                    }
                }
                // 如果发送的话
                if ($is_message) {
                    sublog('核心业务', '用户签到', "数据匹配成功", [
                        'message' => $check_in_content,
                        'args' => [
                            'total_coin' => $coin,
                            'total_point' => $total_point,
                            'name' => $uname,
                            'total' => $total,
                            'serial' => $serial
                        ]
                    ]);
                    self::sendMessage($check_in_content, [
                        'total_coin' => $coin,
                        'total_point' => $total_point,
                        'name' => $uname,
                        'total' => $total,
                        'serial' => $serial
                    ]);
                    sublog('核心业务', '用户签到', "----------", []);
                } else {
                    sublog('核心业务', '用户签到', "数据未匹配", []);
                    sublog('核心业务', '用户签到', "----------", []);
                }
            }
        }
    }

    /**
     * 发送信息
     * 
     * @param string $content 文本信息
     * @param array $args 要替换的模版
     * 
     * @return void 
     */
    public static function sendMessage(string $content, array $args)
    {
        // 拆分要发送的内容
        $content = splitAndFilterLines($content);
        if (count($content)) {
            $text = $content[mt_rand(0, (count($content) - 1))];
            if (!empty($text)) {
                // 加入消息发送队列
                $text = self::template($content[mt_rand(0, (count($content) - 1))], $args);
                SendMessage::push($text, 'CheckIn');
            }
        }
    }

    /**
     * 短信模板转换
     *
     * @param string $text 文本信息
     * @param array $args 要替换的模版
     * 
     * @return string
     */
    private static function template(string $text = '', array $args = []): string
    {
        foreach ($args as $key => $replace) {
            $text = preg_replace('/(@' . $key . '@)/i', $replace, $text);
        }
        return $text;
    }
}
