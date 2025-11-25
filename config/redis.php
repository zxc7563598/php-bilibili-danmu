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

return [
    'default' => [
        'host' => getenv('REDIS_HOST'),
        'password' => getenv('REDIS_PASSWORD') ? getenv('REDIS_PASSWORD') : null,
        'port' => getenv('REDIS_PORT'),
        'database' => 0,
    ],
    'cache' => [
        'host' => getenv('REDIS_HOST'),
        'password' => getenv('REDIS_PASSWORD') ? getenv('REDIS_PASSWORD') : null,
        'port' => getenv('REDIS_PORT'),
        'database' => 1,
        'prefix' => 'bilibili_danmu_cache-',
    ]
];
