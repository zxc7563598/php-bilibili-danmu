<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Request;

return [
    'debug' => false,
    'error_reporting' => E_ALL,
    'default_timezone' => 'Asia/Shanghai',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    'app_name' => 'BilibiliDanmuji',
    'api_url' => getenv('SYSTEM_API_URL'), // api链接
    'image_url' => getenv('SYSTEM_API_URL') . '/attachment', // 图片链接
    'tools_api_url' => getenv('TOOLS_API_URL') ?: 'https://tools.api.hejunjie.life', // 外部工具服务地址
    'error_report_url' => getenv('ERROR_REPORT_URL') ?: 'https://error.hejunjie.life/write', // 错误上报服务地址
    'altcha_hmac_key' => getenv('ALTCHA_HMAC_KEY'), // altcha 验证密钥
];
