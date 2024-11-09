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

use app\queue\SendMessage;
use Carbon\Carbon;
use Hejunjie\Tools;
use Webman\Route;
use support\Request;
use Workerman\Worker;

Route::get('/', [app\controller\PageController::class, 'main']);
Route::get('/login', [app\controller\PageController::class, 'login']);

Route::group('/api', function () {
    Route::any('/login-check', [app\controller\ApiController::class, 'loginCheck']);
    Route::any('/login-out', [app\controller\ApiController::class, 'loginOut']);
    Route::any('/get-user-info', [app\controller\ApiController::class, 'getUserInfo']);
    Route::any('/get-real-room-info', [app\controller\ApiController::class, 'getRealRoomInfo']);
    Route::any('/get-config', [app\controller\ApiController::class, 'getConfig']);
    Route::any('/set-config', [app\controller\ApiController::class, 'setConfig']);
    Route::any('/connect-out', [app\controller\ApiController::class, 'connectOut']);
})->middleware([
    app\middleware\SignatureMiddleware::class
]);

Route::post('/reload-bilibili', function (Request $request) {
    // 预定义的 API 密钥（可以从配置文件或环境变量中读取）
    $validApiKey = getenv('SECURE_API_KEY');
    // 获取请求中的 API 密钥
    $api_key = $request->post('api_key');
    $timestamp = $request->post('timestamp');
    // 验证 API 密钥
    if ($api_key !== md5($validApiKey . $timestamp)) {
        return response('Unauthorized', 401);
    }
    $socketFile = runtime_path() . '/bilibili.sock'; // 套接字文件路径，确保有权限访问
    if (!file_exists($socketFile)) {
        return response('Unix socket not found', 404);
    }
    $socket = stream_socket_client("unix://$socketFile", $errno, $errstr);
    if (!$socket) {
        return response("Error connecting to Unix socket: $errstr ($errno)", 500);
    }
    // 发送 reload 命令
    fwrite($socket, 'reload');
    $response = fread($socket, 1024);
    fclose($socket);
    return response($response);
});

Route::post('/reload-timing', function (Request $request) {
    // 预定义的 API 密钥（可以从配置文件或环境变量中读取）
    $validApiKey = getenv('SECURE_API_KEY');
    // 获取请求中的 API 密钥
    $api_key = $request->post('api_key');
    $timestamp = $request->post('timestamp');
    // 验证 API 密钥
    if ($api_key !== md5($validApiKey . $timestamp)) {
        return response('Unauthorized', 401);
    }
    $socketFile = runtime_path() . '/timing.sock'; // 套接字文件路径，确保有权限访问
    if (!file_exists($socketFile)) {
        return response('Unix socket not found', 404);
    }
    $socket = stream_socket_client("unix://$socketFile", $errno, $errstr);
    if (!$socket) {
        return response("Error connecting to Unix socket: $errstr ($errno)", 500);
    }
    // 发送 reload 命令
    fwrite($socket, 'reload');
    $response = fread($socket, 1024);
    fclose($socket);
    return response($response);
});



Route::get('/test', function (Request $request) {
    $timing = readFileContent(runtime_path() . '/tmp/timing.cfg');
    if ($timing) {
        $timing = json_decode($timing, true);
        $content = splitAndFilterLines($timing['content']);
        if (count($content)) {
            $text = $content[mt_rand(0, (count($content) - 1))];
        }

        return response($text);
    }

    return response('无数据');
});

Route::disableDefaultRoute(); // 关闭默认路由