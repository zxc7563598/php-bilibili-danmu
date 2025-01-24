<?php

namespace app\server;

use app\model\SilentUser;
use app\queue\SendMessage;
use Hejunjie\Bililive;
use Exception;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Redis;

/**
 * 礼物答谢，优先级20
 */
class Present
{
    /**
     * 处理数据
     * 
     * @param mixed $uid 送礼人uid
     * @param mixed $uname 送礼人名称
     * @param mixed $gift_id 礼物ID
     * @param mixed $gift_name 礼物名称
     * @param mixed $price 礼物单价
     * @param mixed $num 赠送数量
     * @param mixed $anchor_id 主播uid
     * @param mixed $ruid 用户携带的牌子归属主播的uid
     * @param mixed $guard_level 大航海类型，0=普通用户，1=总督，2=提督，3=舰长
     * @param mixed $level 牌子等级
     * @return void 
     */
    public static function processing($uid, $uname, $gift_id, $gift_name, $price, $num, $anchor_id, $ruid, $guard_level, $level)
    {
        $is_message = false;
        sublog('逻辑检测', '礼物答谢', [
            'uid' => $uid,
            'uname' => $uname,
            'gift_id' => $gift_id,
            'gift_name' => $gift_name,
            'price' => $price,
            'num' => $num,
            'anchor_id' => $anchor_id,
            'ruid' => $ruid,
            'guard_level' => $guard_level,
            'level' => $level
        ]);
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect.cfg'));
        if ($cookie && $room_id) {
            // 获取礼物答谢配置
            $present = readFileContent(runtime_path() . '/tmp/present.cfg');
            if ($present) {
                $present = json_decode($present, true);
            }
            // 开启礼物答谢
            if (isset($present['opens']) && $present['opens']) {
                $present_price = $present['price']; // 起始感谢电池数
                $present_type = intval($present['type']); // 类型
                $present_status = intval($present['status']); // 状态：0=不论何时，1-仅在直播时，2-仅在非直播时
                $present_content = $present['content']; // 内容
                $present_merge = $present['merge']; // 是否合并
                $present_number = $present['number']; // 展示数量
                // 验证是否达到可以感谢的电池数
                if ($price >= $present_price) {
                    // 验证牌子
                    $medal = false;
                    switch ($present_type) {
                        case 0: // 全部答谢
                            $medal = true;
                            break;
                        case 1: // 仅答谢牌子
                            if ($anchor_id == $ruid) {
                                $medal = true;
                            }
                            break;
                        case 2: // 仅答谢航海
                            if (($anchor_id == $ruid) && ($guard_level > 0)) {
                                $medal = true;
                            }
                            break;
                    }
                    // 验证时间段
                    if ($medal) {
                        switch ($present_status) {
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
                    sublog('逻辑检测', '礼物答谢', '数据匹配成功');
                    self::sendMessage($present_content, [
                        'giftName' => $gift_name,
                        'price' => $price,
                        'name' => $uname,
                        'num' => $num
                    ], [
                        'uid' => $uid,
                        'uname' => $uname,
                        'merge' => $present_merge,
                        'number' => $present_number
                    ]);
                } else {
                    sublog('逻辑检测', '礼物答谢', '数据未匹配');
                }
            }
            // 检测禁言是否需要解除
            $silent_user = SilentUser::where('tuid', $uid)->first();
            if (!empty($silent_user)) {
                if ($silent_user->ransom_amount > 0) {
                    if ($price >= $silent_user->ransom_amount) {
                        sublog('逻辑检测', '礼物答谢', '解除禁言：' . $silent_user->tname . ' - ' . $silent_user->tuid);
                        Bililive\Live::delSilentUser($room_id, $cookie, $silent_user->black_id);
                        $silent_user->delete();
                    }
                }
            }
        }
        sublog('逻辑检测', '礼物答谢', '----------');
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
    public static function sendMessage(string $content, array $args, array $extra = [])
    {
        // 拆分要发送的内容
        $content = splitAndFilterLines($content);
        if (count($content)) {
            $text = $content[mt_rand(0, (count($content) - 1))];
            if (!empty($text)) {
                if (isset($extra['merge']) && $extra['merge'] == 1) {
                    SendMessage::mergePush($text, $extra['uid'], $extra['uname'], $extra['number'], $args);
                    sublog('逻辑检测', '礼物答谢', '合并数据发送：' . json_encode($args, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
                } else {
                    $text = self::template($content[mt_rand(0, (count($content) - 1))], $extra['number'], $args);
                    SendMessage::push($text, 'Present');
                    sublog('逻辑检测', '礼物答谢', '发送数据：' . $text);
                }
            }
        }
    }

    /**
     * 短信模板转换
     *
     * @param string $text 文本信息
     * @param integer $number 是否展示数量
     * @param array $args 要替换的模版
     * 
     * @return string
     */
    private static function template(string $text = '', int $number = 0, array $args = []): string
    {
        foreach ($args as $key => $replace) {
            if ($number == 1 && $key == 'giftName') {
                $text = preg_replace('/(@' . $key . '@)/i', $number . '个' . $replace, $text);
            } else {
                $text = preg_replace('/(@' . $key . '@)/i', $replace, $text);
            }
        }
        return $text;
    }
}
