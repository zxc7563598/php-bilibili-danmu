<?php

namespace app\server;

use app\queue\SendMessage;
use app\server\core\KeywordEvaluator;
use app\server\core\KeywordMatcher;
use Hejunjie\Bililive;
use Exception;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Redis;

/**
 * 自动回复，优先级15
 */
class Autoresponders
{
    /**
     * 处理数据
     * 
     * @param mixed $msg 消息内容
     * @param mixed $uid 发送人uid
     * @param mixed $uname 发送人名称
     * @param mixed $ruid 发送人携带的牌子归属主播的uid
     * @param mixed $guard_level 大航海类型，0=普通用户，1=总督，2=提督，3=舰长
     * @return void 
     */
    public static function processing($msg, $uid, $uname, $ruid, $guard_level)
    {
        $is_message = false;
        sublog('逻辑检测', '自动回复', [
            'msg' => $msg,
            'uid' => $uid,
            'uname' => $uname,
            'ruid' => $ruid,
            'guard_level' => $guard_level
        ]);
        // 不处理自己发送的消息
        $robot_uid = strval(readFileContent(runtime_path() . '/tmp/uid.cfg'));
        // 获取自动回复配置
        $autoresponders = readFileContent(runtime_path() . '/tmp/autoresponders.cfg');
        if ($autoresponders) {
            $autoresponders = json_decode($autoresponders, true);
        }
        // 开启自动回复
        if (isset($autoresponders['opens']) && $autoresponders['opens'] && $uid != $robot_uid) {
            $autoresponders_type = intval($autoresponders['type']); // 类型
            $autoresponders_status = intval($autoresponders['status']); // 状态：0=不论何时，1-仅在直播时，2-仅在非直播时
            $autoresponders_content = $autoresponders['content']; // 内容
            $message = '';
            // 确认链接直播间的情况
            $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
            $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
            $room_uinfo = !empty(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg'))) ? json_decode(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg')), true) : [];
            if ($cookie && $room_id) {
                // 验证是否有需要发送的内容
                foreach ($autoresponders_content as $item) {
                    if ($item['enable']) {
                        $matcher = new KeywordMatcher($item['keywords']);
                        $parsedTree = $matcher->parse();
                        $evaluator = new KeywordEvaluator($parsedTree, $msg);
                        if ($evaluator->evaluate()) {
                            // 安全词检测，默认没命中
                            $safeword = false;
                            if ($item['safeword']) {
                                $matcher = new KeywordMatcher($item['safeword']);
                                $parsedTree = $matcher->parse();
                                $evaluator = new KeywordEvaluator($parsedTree, $msg);
                                if ($evaluator->evaluate()) {
                                    $safeword = true;
                                }
                            }
                            // 未命中安全词的触发自动回复
                            if (!$safeword) {
                                // 验证牌子
                                $medal = false;
                                switch ($autoresponders_type) {
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
                                    switch ($autoresponders_status) {
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
                                $message = $item['text'];
                            }
                            break;
                        }
                    }
                }
            }
            // 如果发送的话
            if ($is_message) {
                sublog('逻辑检测', '自动回复', '数据匹配成功');
                self::sendMessage($message, [
                    'name' => $uname
                ]);
                sublog('逻辑检测', '自动回复', '----------');
            } else {
                sublog('逻辑检测', '自动回复', '数据未匹配');
                sublog('逻辑检测', '自动回复', '----------');
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
                SendMessage::push($text, 15);
                sublog('逻辑检测', '自动回复', '发送数据：' . $text);
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
