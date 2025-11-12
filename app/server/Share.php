<?php

namespace app\server;

use app\core\RobotServices;
use app\queue\SendMessage;
use support\Redis;

/**
 * 感谢分享，优先级5
 */
class Share
{
    /**
     * 处理数据
     * 
     * @param mixed $uid 送礼人uid
     * @param mixed $uname 送礼人名称
     * @param mixed $ruid 用户携带的牌子归属主播的uid
     * @param mixed $guard_level 大航海类型，0=普通用户，1=总督，2=提督，3=舰长
     * @return void 
     */
    public static function processing($uid, $uname, $ruid, $guard_level)
    {
        $is_message = false;
        // 不处理自己发送的消息
        $robot_uid = strval(readFileContent(runtime_path() . '/tmp/uid.cfg'));
        // 获取感谢分享配置
        $share = readFileContent(runtime_path() . '/tmp/share.cfg');
        if ($share) {
            $share = json_decode($share, true);
        }
        // 开启感谢分享
        if (isset($share['opens']) && $share['opens'] && $uid != $robot_uid) {
            sublog('核心业务/感谢分享', "入参", [
                'uid' => $uid,
                'uname' => $uname,
                'ruid' => $ruid,
                'guard_level' => $guard_level
            ]);
            $share_type = intval($share['type']); // 类型
            $share_status = intval($share['status']); // 状态：0=不论何时，1-仅在直播时，2-仅在非直播时
            $share_content = $share['content']; // 内容
            // 确认链接直播间的情况
            $cookie = RobotServices::getCookie();
            $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
            $room_uinfo = !empty(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg'))) ? json_decode(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg')), true) : [];
            if ($cookie && $room_id) {
                // 验证牌子
                $medal = false;
                switch ($share_type) {
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
                    switch ($share_status) {
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
            $guard = match ($guard_level) {
                1 => '总督',
                2 => '提督',
                3 => '舰长',
                default => '',
            };
            $up_name = isset($room_uinfo['uname']) ? $room_uinfo['uname'] : '';
            if ($is_message) {
                sublog('核心业务/感谢分享', "数据匹配", [
                    'message' => $share_content,
                    'args' => [
                        'name' => $uname,
                        'guard' => $guard,
                        'up_name' => $up_name
                    ]
                ]);
                self::sendMessage($share_content, [
                    'name' => $uname,
                    'guard' => $guard,
                    'up_name' => $up_name
                ]);
            } else {
                sublog('核心业务/感谢分享', '数据不匹配', 'N/A');
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
                $text = self::template($text, $args);
                SendMessage::push($text, 'Share');
            }
        }
    }

    /**
     * 消息模板转换
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
