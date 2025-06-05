<?php

namespace app\core;

use app\controller\GeneralMethod;
use Carbon\Carbon;
use Hejunjie\Utils;
use Hejunjie\Bililive;

class RobotServices extends GeneralMethod
{
    /**
     * 获取cookie
     *
     * @param string $token 用户登录凭证
     * 
     * @return string
     */
    public static function getCookie(): string
    {
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie.cfg'));
        if (!empty($cookie)) {
            $pairs = explode(";", $cookie);
            $result = [];
            foreach ($pairs as $pair) {
                list($key, $value) = explode("=", $pair, 2); // 用 = 分割键和值
                $result[$key] = $value;
            }
            if (!isset($result['bili_ticket']) || !isset($result['bili_ticket_expires']) || $result['bili_ticket_expires'] < Carbon::now()->timezone(config('app')['default_timezone'])->timestamp) {
                $getBiliTicket = Bililive\Service\Processing::getBiliTicket($cookie);
                if (isset($getBiliTicket['bili_ticket'])) {
                    $result['bili_ticket'] = $getBiliTicket['bili_ticket'];
                }
                if (isset($getBiliTicket['bili_ticket_expires'])) {
                    $result['bili_ticket_expires'] = $getBiliTicket['bili_ticket_expires'];
                }
            }
            // 使用 array_map 和 implode 组合成所需格式
            $cookie_str = implode(";", array_map(
                fn($key, $value) => "{$key}={$value}",
                array_keys($result),
                $result
            ));
            if ($cookie != $cookie_str) {
                Utils\FileUtils::fileDelete(runtime_path() . '/tmp/cookie.cfg');
                Utils\FileUtils::writeToFile(runtime_path() . '/tmp/cookie.cfg', $cookie_str);
            }
            return $cookie_str;
        }
        return '';
    }
}
