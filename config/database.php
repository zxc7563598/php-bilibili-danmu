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
    // 默认数据库
    'default' => 'mysql',
    // 各种数据库配置
    'connections' => [
        'mysql' => [
            'driver'      => 'mysql',
            'host'        => getenv('DB_HOST'),
            'port'        => getenv('DB_PORT'),
            'database'    => getenv('DB_USER'),
            'username'    => getenv('DB_NAME'),
            'password'    => getenv('DB_PASSWORD'),
            'unix_socket' => '',
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_general_ci',
            'prefix'      => '',
            'strict'      => false,
            'engine'      => null
        ]
    ],
];
