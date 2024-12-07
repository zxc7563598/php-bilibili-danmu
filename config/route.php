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

Route::get('/', [app\controller\robot\PageController::class, 'main'])->middleware([app\middleware\BasicAuthMiddleware::class]);
Route::get('/login', [app\controller\robot\PageController::class, 'login'])->middleware([app\middleware\BasicAuthMiddleware::class]);

Route::group('/api/robot', function () {
    Route::any('/login-check', [app\controller\robot\ApiController::class, 'loginCheck']);
    Route::any('/login-out', [app\controller\robot\ApiController::class, 'loginOut']);
    Route::any('/get-user-info', [app\controller\robot\ApiController::class, 'getUserInfo']);
    Route::any('/get-real-room-info', [app\controller\robot\ApiController::class, 'getRealRoomInfo']);
    Route::any('/get-config', [app\controller\robot\ApiController::class, 'getConfig']);
    Route::any('/set-config', [app\controller\robot\ApiController::class, 'setConfig']);
    Route::any('/connect-out', [app\controller\robot\ApiController::class, 'connectOut']);
    Route::any('/export-config', [app\controller\robot\ApiController::class, 'exportConfig']);
})->middleware([
    app\middleware\SignatureMiddleware::class
]);


// API接口
Route::group('/api/shop', function () {
    Route::post('/login/get-user-vip', [app\controller\shop\LoginController::class, 'getUserVip']); // 获取用户是否存在
    Route::post('/login/perform-login', [app\controller\shop\LoginController::class, 'performLogin']); // 执行登陆
    Route::post('/login/logout', [app\controller\shop\LoginController::class, 'logout']); // 退出登录
    Route::post('/login/get-my', [app\controller\shop\LoginController::class, 'getMy']); // 获取我的

    Route::post('/shop/get-goods', [app\controller\shop\ShopController::class, 'getGoods']); // 获取商品列表
    Route::post('/shop/get-goods-details', [app\controller\shop\ShopController::class, 'getGoodsDetails']); // 获取商品详情
    Route::post('/shop/get-confirm', [app\controller\shop\ShopController::class, 'getConfirm']); // 获取确认订单信息

    Route::post('/user/get-address-list', [app\controller\shop\UserController::class, 'getAddressList']); // 获取用户地址列表
    Route::post('/user/get-address-details', [app\controller\shop\UserController::class, 'getAddressDetails']); // 获取用户地址详情
    Route::post('/user/set-address-list', [app\controller\shop\UserController::class, 'setAddressList']); // 存储地址信息
    Route::post('/user/set-address-selected', [app\controller\shop\UserController::class, 'setAddressSelected']); // 选择地址信息
    Route::post('/user/get-consumers', [app\controller\shop\UserController::class, 'getConsumers']); // 开通记录
    Route::post('/user/get-redeeming', [app\controller\shop\UserController::class, 'getRedeeming']); // 兑换记录
    Route::post('/user/set-complaint', [app\controller\shop\UserController::class, 'setComplaint']); // 投诉上传
    Route::post('/user/add-consumers', [app\controller\shop\UserController::class, 'addConsumers']); // 补充开通记录
    Route::post('/user/add-redemption', [app\controller\shop\UserController::class, 'addRedemption']); // 增加兑换记录
    Route::post('/user/get-redeeming-address', [app\controller\shop\UserController::class, 'getRedeemingAddress']); // 获取地址
    Route::post('/user/set-redeeming-complete', [app\controller\shop\UserController::class, 'setRedeemingComplete']); // 标记完成

    Route::post('/user/upload-base64-images', [app\controller\shop\UserController::class, 'uploadBase64Images']); // 图片上传

    Route::post('/user/get-protocol-credit', [app\controller\shop\UserController::class, 'getProtocolCredit']); // 获取赊销协议
    Route::post('/user/upload-signing', [app\controller\shop\UserController::class, 'uploadSigning']); // 签名上传
    Route::post('/shop/confirm-product', [app\controller\shop\ShopController::class, 'confirmProduct']); // 确认下单
    Route::post('/shop/dedication-ranking', [app\controller\shop\ShopController::class, 'dedicationRanking']); // 上供排名

    Route::post('/shop/get-product-list', [app\controller\shop\ShopController::class, 'getProductList']); // 确认下单
    Route::post('/shop/get-product-details', [app\controller\shop\ShopController::class, 'getProductDetails']); // 确认下单
    Route::post('/shop/set-product', [app\controller\shop\ShopController::class, 'setProduct']); // 确认下单
})->middleware([
    app\middleware\AccessControl::class,
    app\middleware\ApiAuthCheck::class
]);

Route::any('/file/import-config', [app\controller\robot\ApiController::class, 'importConfig']);

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