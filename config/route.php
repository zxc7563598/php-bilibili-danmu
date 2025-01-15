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

use app\core\UserPublicMethods;
use app\server\core\KeywordEvaluator;
use app\server\core\KeywordMatcher;
use Webman\Route;
use support\Request;

Route::get('/', [app\controller\robot\PageController::class, 'main'])->middleware([app\middleware\BasicAuthMiddleware::class]);
Route::get('/login', [app\controller\robot\PageController::class, 'login'])->middleware([app\middleware\BasicAuthMiddleware::class]);

// 积分商城
Route::group('/points-mall', function () {
    Route::get('/system-configuration', [app\controller\shop\ManagementController::class, 'pageSystemConfiguration']); // 系统配置
    Route::get('/mall-configuration', [app\controller\shop\ManagementController::class, 'pageMallConfiguration']); // 商城配置
    Route::get('/user-management', [app\controller\shop\ManagementController::class, 'pageUserManagement']); // 用户管理
    Route::get('/product-management', [app\controller\shop\ManagementController::class, 'pageProductManagement']); // 商品管理
    Route::get('/shipping-management', [app\controller\shop\ManagementController::class, 'pageShippingManagement']); // 发货管理
    Route::get('/complaint-management', [app\controller\shop\ManagementController::class, 'pageComplaintManagement']); // 投诉管理
    Route::get('/feedback', [app\controller\shop\ManagementController::class, 'pageFeedback']); // 问题反馈
})->middleware([
    app\middleware\BasicAuthMiddleware::class
]);

Route::group('/api/points-mall', function () {
    Route::any('/system-configuration/get-data', [app\controller\shop\management\SystemConfigurationController::class, 'getData']);
    Route::any('/system-configuration/get-data-qrcode', [app\controller\shop\management\SystemConfigurationController::class, 'getDataQrCode']);
    Route::any('/system-configuration/set-data', [app\controller\shop\management\SystemConfigurationController::class, 'setData']);
    Route::any('/mall-configuration/get-data', [app\controller\shop\management\MallConfigurationController::class, 'getData']);
    Route::any('/mall-configuration/set-data', [app\controller\shop\management\MallConfigurationController::class, 'setData']);
    Route::any('/mall-configuration/upload-images', [app\controller\shop\management\MallConfigurationController::class, 'uploadImages']);
    Route::any('/user-management/get-data', [app\controller\shop\management\UserManagementController::class, 'getData']);
    Route::any('/user-management/get-user-data', [app\controller\shop\management\UserManagementController::class, 'getUserData']);
    Route::any('/user-management/get-user-info', [app\controller\shop\management\UserManagementController::class, 'getUserInfo']);
    Route::any('/user-management/set-data', [app\controller\shop\management\UserManagementController::class, 'setData']);
    Route::any('/user-management/get-user-records', [app\controller\shop\management\UserManagementController::class, 'getUserRecords']);
    Route::any('/user-management/set-user-point', [app\controller\shop\management\UserManagementController::class, 'setUserPoint']);
    Route::any('/product-management/get-data', [app\controller\shop\management\ProductManagementController::class, 'getData']);
    Route::any('/product-management/get-data-details', [app\controller\shop\management\ProductManagementController::class, 'getDataDetails']);
    Route::any('/product-management/set-data-details', [app\controller\shop\management\ProductManagementController::class, 'setDataDetails']);
    Route::any('/product-management/upload-images', [app\controller\shop\management\ProductManagementController::class, 'uploadImages']);
    Route::any('/shipping-management/get-data', [app\controller\shop\management\ShippingManagementController::class, 'getData']);
    Route::any('/shipping-management/get-data-details', [app\controller\shop\management\ShippingManagementController::class, 'getDataDetails']);
    Route::any('/shipping-management/set-data-details', [app\controller\shop\management\ShippingManagementController::class, 'setDataDetails']);
    Route::any('/complaint-management/get-data', [app\controller\shop\management\ComplaintManagementController::class, 'getData']);
    Route::any('/complaint-management/get-data-details', [app\controller\shop\management\ComplaintManagementController::class, 'getDataDetails']);
})->middleware([
    app\middleware\AccessControl::class,
    app\middleware\SignatureMiddleware::class
]);

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
    Route::post('/login/get-theme-color', [app\controller\shop\LoginController::class, 'getThemeColor']); // 获取主题色
    Route::post('/login/get-config', [app\controller\shop\LoginController::class, 'getConfig']); // 获取登录配置
    Route::post('/login/get-user-vip', [app\controller\shop\LoginController::class, 'getUserVip']); // 获取用户是否存在
    Route::post('/login/perform-login', [app\controller\shop\LoginController::class, 'performLogin']); // 执行登陆
    Route::post('/login/logout', [app\controller\shop\LoginController::class, 'logout']); // 退出登录
    Route::post('/login/get-my', [app\controller\shop\LoginController::class, 'getMy']); // 获取我的

    Route::post('/shop/get-goods', [app\controller\shop\ShopController::class, 'getGoods']); // 获取商品列表
    Route::post('/shop/get-goods-details', [app\controller\shop\ShopController::class, 'getGoodsDetails']); // 获取商品详情
    Route::post('/shop/get-confirm', [app\controller\shop\ShopController::class, 'getConfirm']); // 获取确认订单信息


    Route::post('/user/get-background', [app\controller\shop\UserController::class, 'getBackground']); // 获取背景图片
    Route::post('/user/get-address-list', [app\controller\shop\UserController::class, 'getAddressList']); // 获取用户地址列表
    Route::post('/user/get-address-details', [app\controller\shop\UserController::class, 'getAddressDetails']); // 获取用户地址详情
    Route::post('/user/set-address-list', [app\controller\shop\UserController::class, 'setAddressList']); // 存储地址信息
    Route::post('/user/set-address-selected', [app\controller\shop\UserController::class, 'setAddressSelected']); // 选择地址信息
    Route::post('/user/get-consumers', [app\controller\shop\UserController::class, 'getConsumers']); // 开通记录
    Route::post('/user/get-redeeming', [app\controller\shop\UserController::class, 'getRedeeming']); // 兑换记录
    Route::post('/user/set-complaint', [app\controller\shop\UserController::class, 'setComplaint']); // 投诉上传

    Route::post('/user/get-protocol-credit', [app\controller\shop\UserController::class, 'getProtocolCredit']); // 获取赊销协议
    Route::post('/user/upload-signing', [app\controller\shop\UserController::class, 'uploadSigning']); // 签名上传
    Route::post('/shop/confirm-product', [app\controller\shop\ShopController::class, 'confirmProduct']); // 确认下单
    Route::post('/shop/get-transactions-success', [app\controller\shop\ShopController::class, 'getTransactionsSuccess']); // 获取交易成功页面信息
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
    $data = json_decode('{"uid":4325051,"name":"哎呀又胖辣","guard_level":2,"amount":199800,"payment_at":1736868092,"live_key":"576930226618733507"}', true);
    $res = UserPublicMethods::userOpensVip($data['uid'], $data['name'], $data['guard_level'], $data['amount'], $data['payment_at'], $data['live_key']);
    return success($request, ['res' => $res]);
});

Route::disableDefaultRoute(); // 关闭默认路由