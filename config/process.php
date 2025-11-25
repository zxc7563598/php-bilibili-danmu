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


use support\Log;
use app\process\Http;
use support\Request;

global $argv;

return [
    'core' => [
        'handler' => Http::class,
        'listen' => getenv('HOST') . ':' . getenv('LISTEN'),
        'count' => cpu_count() * 1,
        'user' => '',
        'group' => '',
        'reusePort' => false,
        'eventLoop' => Workerman\Events\Fiber::class,
        'context' => [],
        'constructor' => [
            'requestClass' => Request::class,
            'logger' => Log::channel('default'),
            'appPath' => app_path(),
            'publicPath' => public_path()
        ]
    ],
    'bilibili' => [
        'handler'  => process\Bilibili::class
    ],
    'timing'  => [
        'handler'  => app\server\Timing::class
    ],
    'task'  => [
        'handler'  => process\Task::class
    ]
];
