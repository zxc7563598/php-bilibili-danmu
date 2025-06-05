<?php

namespace app\server;

use app\core\RobotServices;
use app\queue\SendMessage;
use support\Redis;
use Hejunjie\Bililive;

/**
 * PK播报，优先级30
 */
class PkLiveReport
{
    /**
     * 处理数据
     * 
     * @param mixed $uid 主播uid
     * @param mixed $uname 主播uname
     * @param mixed $room_id 主播房间号
     * @return void 
     */
    public static function processing($uid, $uname, $room_id)
    {
        // 获取PK播报配置
        $pk = readFileContent(runtime_path() . '/tmp/pk.cfg');
        if ($pk) {
            $pk = json_decode($pk, true);
        }
        // 开启PK播报
        if (isset($pk['opens']) && $pk['opens']) {
            sublog('核心业务', 'PK播报', "入参检测", [
                'uid' => $uid,
                'uname' => $uname,
                'room_id' => $room_id
            ]);
            $enter_content = $pk['content']; // 内容
            // 获取PK信息
            $cookie = RobotServices::getCookie();
            $getOnlineGoldRank = Bililive\Live::getOnlineGoldRank($uid, $room_id, $cookie);
            $online_num = $getOnlineGoldRank['online_num'] ?? 0;
            $online_score = 0;
            $top_three_score = 0;
            $i = 0;
            foreach ($getOnlineGoldRank['online_item'] as $online_item) {
                if ($i < 3) {
                    $top_three_score += $online_item['score'];
                }
                $online_score += $online_item['score'];
            }
            sublog('核心业务', 'PK播报', "数据匹配成功", [
                'message' => $enter_content,
                'args' => [
                    'uname' => $uname,
                    'online_num' => $online_num,
                    'online_score' => $online_score,
                    'top_three_score' => $top_three_score
                ]
            ]);
            self::sendMessage($enter_content, [
                'uname' => $uname,
                'online_num' => $online_num,
                'online_score' => $online_score,
                'top_three_score' => $top_three_score
            ]);
            sublog('核心业务', 'PK播报', '----------', []);
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
            foreach ($content as $text) {
                // 加入消息发送队列
                $text = self::template($text, $args);
                SendMessage::push($text, 'PkLiveReport');
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
