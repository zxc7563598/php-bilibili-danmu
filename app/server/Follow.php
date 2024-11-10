<?php

namespace app\server;

use app\queue\SendMessage;
use Exception;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Redis;

/**
 * 感谢关注，优先级5
 */
class Follow
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
        // 获取感谢关注配置
        $follow = readFileContent(runtime_path('/tmp/follow.cfg'));
        if ($follow) {
            $follow = json_decode($follow, true);
        }
        // 开启感谢关注
        if (isset($follow['opens']) && $follow['opens']) {
            $follow_type = intval($follow['type']); // 类型
            $follow_status = intval($follow['status']); // 状态：0=不论何时，1-仅在直播时，2-仅在非直播时
            $follow_content = $follow['content']; // 内容
            // 确认链接直播间的情况
            $cookie = strval(readFileContent(runtime_path('/tmp/cookie.cfg')));
            $room_id = intval(readFileContent(runtime_path('/tmp/connect.cfg')));
            $room_uid = intval(readFileContent(runtime_path('/tmp/room_uid.cfg')));
            if ($cookie && $room_id) {
                // 验证牌子
                $medal = false;
                switch ($follow_type) {
                    case 0: // 全部答谢
                        $medal = true;
                        break;
                    case 0: // 仅答谢牌子
                        if ($room_uid == $ruid) {
                            $medal = true;
                        }
                        break;
                    case 0: // 仅答谢航海
                        if (($room_uid == $ruid) && ($guard_level > 0)) {
                            $medal = true;
                        }
                        break;
                }
                // 验证时间段
                if ($medal) {
                    switch ($follow_status) {
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
                self::sendMessage($follow_content, [
                    'name' => $uname
                ]);
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
                SendMessage::push($text, 5);
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
