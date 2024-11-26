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

use app\server\core\KeywordEvaluator;
use app\server\core\KeywordMatcher;
use Webman\Route;
use support\Request;

Route::get('/', [app\controller\PageController::class, 'main'])->middleware([app\middleware\BasicAuthMiddleware::class]);
Route::get('/login', [app\controller\PageController::class, 'login'])->middleware([app\middleware\BasicAuthMiddleware::class]);

Route::group('/api', function () {
    Route::any('/login-check', [app\controller\ApiController::class, 'loginCheck']);
    Route::any('/login-out', [app\controller\ApiController::class, 'loginOut']);
    Route::any('/get-user-info', [app\controller\ApiController::class, 'getUserInfo']);
    Route::any('/get-real-room-info', [app\controller\ApiController::class, 'getRealRoomInfo']);
    Route::any('/get-config', [app\controller\ApiController::class, 'getConfig']);
    Route::any('/set-config', [app\controller\ApiController::class, 'setConfig']);
    Route::any('/version-update', [app\controller\ApiController::class, 'versionUpdate']);
    Route::any('/connect-out', [app\controller\ApiController::class, 'connectOut']);
    Route::any('/export-config', [app\controller\ApiController::class, 'exportConfig']);
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
    $param = $request->all();
    $msg = isset($param['msg']) ? $param['msg'] : '';
    // 处理数据
    $autoresponders = readFileContent(runtime_path() . '/tmp/autoresponders.cfg');
    if ($autoresponders) {
        $autoresponders = json_decode($autoresponders, true);
    }
    // 开启自动回复
    $autoresponders_content = $autoresponders['content']; // 内容
    $result = count($autoresponders_content) . '条数据' . "<br>";
    $result .= '--------------------------' . "<br>";
    // 确认链接直播间的情况]
    // 验证是否有需要发送的内容
    foreach ($autoresponders_content as $item) {
        $result .= $item['keywords'] . "<br>";
        // 解析表达式
        $matcher = new KeywordMatcher($item['keywords']);
        $parsedTree = $matcher->parse();
        // 输出解析后的表达式树
        $result .= json_encode($parsedTree, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION) . "<br>";
        // 检查弹幕是否匹配
        $evaluator = new KeywordEvaluator($parsedTree, $msg);
        $result .= ($evaluator->evaluate() ? '命中' : '未命中')  . "<br>";;
        $result .= '--------------------------' . "<br>";
    }


    return response($result);
});

Route::disableDefaultRoute(); // 关闭默认路由