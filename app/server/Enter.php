<?php

namespace app\server;

use app\queue\SendMessage;
use Exception;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Redis;

/**
 * 进房欢迎，优先级5
 */
class Enter
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
        sublog('逻辑检测', '进房欢迎', [
            'uid' => $uid,
            'uname' => $uname,
            'ruid' => $ruid,
            'guard_level' => $guard_level
        ]);
        // 获取进房欢迎配置
        $enter = readFileContent(runtime_path() . '/tmp/enter.cfg');
        if ($enter) {
            $enter = json_decode($enter, true);
        }
        // 开启进房欢迎
        if (isset($enter['opens']) && $enter['opens']) {
            $enter_type = intval($enter['type']); // 类型
            $enter_status = intval($enter['status']); // 状态：0=不论何时，1-仅在直播时，2-仅在非直播时
            $enter_content = $enter['content']; // 内容
            // 确认链接直播间的情况
            $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
            $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
            $room_uinfo = !empty(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg'))) ? json_decode(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg')), true) : [];
            if ($cookie && $room_id) {
                // 验证牌子
                $medal = false;
                switch ($enter_type) {
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
                    switch ($enter_status) {
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
                sublog('逻辑检测', '进房欢迎', '数据匹配成功');
                self::sendMessage($enter_content, [
                    'name' => $uname
                ]);
                sublog('逻辑检测', '进房欢迎', '----------');
            } else {
                sublog('逻辑检测', '进房欢迎', '数据未匹配');
                sublog('逻辑检测', '进房欢迎', '----------');
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
     * @throws Exception 
     * @throws InvalidTimeZoneException 
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
                SendMessage::push($text, 'Enter');
                sublog('逻辑检测', '进房欢迎', '发送数据：' . $text);
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
