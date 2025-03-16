<?php

namespace app\server;

use app\model\SilentUser;
use app\queue\SendMessage;
use app\server\core\KeywordEvaluator;
use app\server\core\KeywordMatcher;
use Carbon\Carbon;
use Hejunjie\Bililive;
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
        // 不处理自己发送的消息
        $robot_uid = strval(readFileContent(runtime_path() . '/tmp/uid.cfg'));
        // 获取自动回复配置
        $autoresponders = readFileContent(runtime_path() . '/tmp/autoresponders.cfg');
        if ($autoresponders) {
            $autoresponders = json_decode($autoresponders, true);
        }
        // 开启自动回复
        if (isset($autoresponders['opens']) && $autoresponders['opens'] && $uid != $robot_uid) {
            sublog('核心业务', '自动回复', "入参检测", [
                'msg' => $msg,
                'uid' => $uid,
                'uname' => $uname,
                'ruid' => $ruid,
                'guard_level' => $guard_level
            ]);
            $autoresponders_type = intval($autoresponders['type']); // 类型
            $autoresponders_status = intval($autoresponders['status']); // 状态：0=不论何时，1-仅在直播时，2-仅在非直播时
            $autoresponders_content = $autoresponders['content']; // 内容
            $message = '';
            $silent = false;
            $silent_minute = 0;
            $ransom_amount = 0;
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
                                $silent = isset($item['silent']) ? $item['silent'] : false;
                                $silent_minute = isset($item['silent_minute']) ? $item['silent_minute'] : 0;
                                $ransom_amount = isset($item['ransom_amount']) ? $item['ransom_amount'] : 0;
                            }
                            break;
                        }
                    }
                }
            }
            // 如果发送的话
            if ($is_message) {
                sublog('核心业务', '自动回复', "数据匹配成功", [
                    'message' => $message
                ]);
                self::sendMessage($message, [
                    'name' => $uname
                ], $msg, $silent, $silent_minute, $ransom_amount, (string)$uid, $uname);
                sublog('核心业务', '自动回复', "----------", []);
            } else {
                sublog('核心业务', '自动回复', "数据匹配失败", []);
                sublog('核心业务', '自动回复', "----------", []);
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
    public static function sendMessage(string $content, array $args, string $msg, bool $silent, int $silent_minute, int $ransom_amount, string $uid, string $uname)
    {
        // 加入禁言
        if ($silent) {
            // 创建数据
            SilentUser::where('tuid', $uid)->delete();
            // 添加禁言
            $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
            $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));

            Bililive\Live::addSilentUser($room_id, $cookie, $uid, $msg);
            // 获取black_id
            $black_id = '';
            $getSilentUserList = Bililive\Live::getSilentUserList($room_id, $cookie, 1);
            if (isset($getSilentUserList['total_page'])) {
                // 确认第一页是否存在用户
                if (isset($getSilentUserList['data'])) {
                    foreach ($getSilentUserList['data'] as $item) {
                        if ($item['tuid'] == $uid) {
                            $black_id = $item['id'];
                            break;
                        }
                    }
                }
                // 确认其他页是否存在用户
                if (empty($black_id)) {
                    for ($i = $getSilentUserList['total_page']; $i > 1; $i--) {
                        $getSilentUserList = Bililive\Live::getSilentUserList($room_id, $cookie, $i);
                        if (isset($getSilentUserList['data'])) {
                            foreach ($getSilentUserList['data'] as $item) {
                                if ($item['tuid'] == $uid) {
                                    $black_id = $item['id'];
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            // 记录数据
            if (!empty($black_id)) {
                $silent_user = new SilentUser();
                $silent_user->tuid = $uid;
                $silent_user->tname = $uname;
                $silent_user->silent_minute = $silent_minute > 0 ? Carbon::now()->timezone(config('app')['default_timezone'])->addMinutes($silent_minute)->timestamp : Carbon::now()->timezone(config('app')['default_timezone'])->addYears(1)->timestamp;
                $silent_user->ransom_amount = $ransom_amount;
                $silent_user->black_id = $black_id;
                $silent_user->save();
            }
        }
        // 拆分要发送的内容
        $content = splitAndFilterLines($content);
        if (count($content)) {
            $text = $content[mt_rand(0, (count($content) - 1))];
            if (!empty($text)) {
                // 加入消息发送队列
                $text = self::template($content[mt_rand(0, (count($content) - 1))], $args);
                SendMessage::push($text, 'Autoresponders');
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
