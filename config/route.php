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

use Webman\Route;
use support\Request;
use support\Response;
use app\controller\admin;

// 积分商城接口
Route::group('/api/shop', function () {
    Route::post('/login/get-theme-color', [app\controller\shop\LoginController::class, 'getThemeColor']); // 获取主题色
    Route::post('/login/get-config', [app\controller\shop\LoginController::class, 'getConfig']); // 获取登录配置
    Route::post('/login/get-user-vip', [app\controller\shop\LoginController::class, 'getUserVip']); // 获取用户是否存在
    Route::post('/login/perform-login', [app\controller\shop\LoginController::class, 'performLogin']); // 执行登陆
    Route::post('/login/logout', [app\controller\shop\LoginController::class, 'logout']); // 退出登录
    Route::post('/login/get-my', [app\controller\shop\LoginController::class, 'getMy']); // 获取我的

    Route::post('/shop/get-goods', [app\controller\shop\ShopController::class, 'getGoods']); // 获取商品列表
    Route::post('/shop/get-goods-v2', [app\controller\shop\ShopController::class, 'getGoodsV2']); // 获取商品列表
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

// 新版后台API接口
Route::group('/admin-api-v2', function () {
    // 欢迎页
    Route::post('/home/get-update-logs', [admin\HomeController::class, 'getUpdateLogs'])->name('[欢迎页-获取更新日志]');
    Route::post('/home/read-update-logs', [admin\HomeController::class, 'readUpdateLogs'])->name('[欢迎页-更新日志标记已读]');
    Route::post('/home/download-source-code', [admin\HomeController::class, 'downloadSourceCode'])->name('[欢迎页-由服务器下载后台源码]');
    // 机器人控制相关
    Route::post('/rebot/get-user-info', [admin\RobotControlController::class, 'getUserInfo'])->name('[机器人控制相关-获取用户信息]');
    Route::post('/rebot/get-real-room-info', [admin\RobotControlController::class, 'getRealRoomInfo'])->name('[机器人控制相关-获取直播间信息]');
    Route::post('/rebot/get-config', [admin\RobotControlController::class, 'getConfig'])->name('[机器人控制相关-获取配置信息]');
    Route::post('/rebot/set-config', [admin\RobotControlController::class, 'setConfig'])->name('[机器人控制相关-存储配置信息]');
    Route::post('/rebot/get-login-qr', [admin\RobotControlController::class, 'getLoginQr'])->name('[机器人控制相关-获取登录二维码]');
    Route::post('/rebot/login-check', [admin\RobotControlController::class, 'loginCheck'])->name('[机器人控制相关-验证登录信息]');
    Route::post('/rebot/login-out', [admin\RobotControlController::class, 'loginOut'])->name('[机器人控制相关-退出登录]');
    Route::post('/rebot/connect-out', [admin\RobotControlController::class, 'connectOut'])->name('[机器人控制相关-断开直播间链接]');
    Route::post('/rebot/export-config', [admin\RobotControlController::class, 'exportConfig'])->name('[机器人控制相关-导出配置文件]');
    Route::post('/rebot/import-config', [admin\RobotControlController::class, 'importConfig'])->name('[机器人控制相关-导入配置文件]');
    // 配置管理 - 系统配置相关
    Route::post('/configuration/system-settings/get-data', [admin\configuration\SystemSettingsController::class, 'getData'])->name('[配置管理-系统配置相关-获取配置]');
    Route::post('/configuration/system-settings/get-data-qrcode', [admin\configuration\SystemSettingsController::class, 'getDataQrCode'])->name('[配置管理-系统配置相关-获取二维码]');
    Route::post('/configuration/system-settings/set-data', [admin\configuration\SystemSettingsController::class, 'setData'])->name('[配置管理-系统配置相关-存储数据]');
    // 配置管理 - 商城配置相关
    Route::post('/mall-configuration/get-data', [admin\configuration\ShopSettingsController::class, 'getData'])->name('[配置管理-商城配置相关-获取商城配置信息]');
    Route::post('/mall-configuration/set-data', [admin\configuration\ShopSettingsController::class, 'setData'])->name('[配置管理-商城配置相关-存储商城配置信息]');
    Route::post('/mall-configuration/upload-images', [admin\configuration\ShopSettingsController::class, 'uploadImages'])->name('[配置管理-商城配置相关-上传图片]');
    // 商城管理 - 商品管理
    Route::post('/shop-management/product-management/get-data', [admin\shopManagement\ProductManagementController::class, 'getData'])->name('[商城管理-商品管理-获取商品信息]');
    Route::post('/shop-management/product-management/get-data-details', [admin\shopManagement\ProductManagementController::class, 'getDataDetails'])->name('[商城管理-商品管理-获取商品详细信息]');
    Route::post('/shop-management/product-management/set-data-details', [admin\shopManagement\ProductManagementController::class, 'setDataDetails'])->name('[商城管理-商品管理-变更商品信息]');
    Route::post('/shop-management/product-management/upload-images', [admin\shopManagement\ProductManagementController::class, 'uploadImages'])->name('[商城管理-商品管理-上传图片]');
    // 商城管理 - 用户管理
    Route::post('/shop-management/user-management/get-data', [admin\shopManagement\UserManagementController::class, 'getData'])->name('[商城管理-用户管理-获取用户列表]');
    Route::post('/shop-management/user-management/get-user-data', [admin\shopManagement\UserManagementController::class, 'getUserData'])->name('[商城管理-用户管理-获取用户详细信息]');
    Route::post('/shop-management/user-management/get-user-info', [admin\shopManagement\UserManagementController::class, 'getUserInfo'])->name('[商城管理-用户管理-根据UID查询用户数据]');
    Route::post('/shop-management/user-management/set-data', [admin\shopManagement\UserManagementController::class, 'setData'])->name('[商城管理-用户管理-存储用户信息]');
    Route::post('/shop-management/user-management/reset-password', [admin\shopManagement\UserManagementController::class, 'resetPassword'])->name('[商城管理-用户管理-清空所有用户密码]');
    Route::post('/shop-management/user-management/get-user-point-records', [admin\shopManagement\UserManagementController::class, 'getUserPointRecords'])->name('[商城管理-用户管理-获取用户积分变更记录-弃用]');
    Route::post('/shop-management/user-management/get-user-coin-records', [admin\shopManagement\UserManagementController::class, 'getUserCoinRecords'])->name('[商城管理-用户管理-获取用户硬币变更记录-弃用]');
    Route::post('/shop-management/user-management/get-user-point-records-v2', [admin\shopManagement\UserManagementController::class, 'getUserPointRecordsV2'])->name('[商城管理-用户管理-获取用户积分变更记录]');
    Route::post('/shop-management/user-management/get-user-coin-records-v2', [admin\shopManagement\UserManagementController::class, 'getUserCoinRecordsV2'])->name('[商城管理-用户管理-获取用户硬币变更记录]');
    Route::post('/shop-management/user-management/set-user-point', [admin\shopManagement\UserManagementController::class, 'setUserPoint'])->name('[商城管理-用户管理-变更用户积分]');
    Route::post('/shop-management/user-management/set-user-coin', [admin\shopManagement\UserManagementController::class, 'setUserCoin'])->name('[商城管理-用户管理-变更用户硬币]');
    // 商城管理 - 发货管理
    Route::post('/shop-management/shipping-management/get-data', [admin\shopManagement\ShippingManagementController::class, 'getData'])->name('[商城管理-发货管理-获取发货列表数据]');
    Route::post('/shop-management/shipping-management/get-data-details', [admin\shopManagement\ShippingManagementController::class, 'getDataDetails'])->name('[商城管理-发货管理-获取发货详情]');
    Route::post('/shop-management/shipping-management/set-data-details', [admin\shopManagement\ShippingManagementController::class, 'setDataDetails'])->name('[商城管理-发货管理-变更发货信息]');
    // 其他 - 礼物信息
    Route::post('/others/gift-info/get-data', [admin\others\GiftInfoController::class, 'getData'])->name('[其他-礼物信息-获取列表数据]');
    Route::post('/others/gift-info/get-statistic-data', [admin\others\GiftInfoController::class, 'getStatisticData'])->name('[其他-礼物信息-获取统计数据]');
    // 其他 - 盲盒信息
    Route::post('/others/gift-blind-box/get-data', [admin\others\GiftBlindBoxcontroller::class, 'getData'])->name('[其他-盲盒信息-获取盲盒信息数据]');
    Route::post('/others/gift-blind-box/get-statistic-data', [admin\others\GiftBlindBoxcontroller::class, 'getStatisticData'])->name('[其他-盲盒信息-获取统计数据]');
    // 其他 - 弹幕信息
    Route::post('/others/danmaku-info/get-data', [admin\others\DanmakuInfoController::class, 'getData'])->name('[其他-弹幕信息-获取列表数据]');
    // 其他 - 用户分析
    Route::post('/others/user-analysis/get-data', [admin\others\UserAnalysisController::class, 'getData'])->name('[其他-用户分析-获取列表数据]');
    Route::post('/others/user-analysis/get-daily-active', [admin\others\UserAnalysisController::class, 'getDailyActive'])->name('[其他-用户分析-获取每月分析数据]');
    Route::post('/others/user-analysis/get-word-cloud-from-text', [admin\others\UserAnalysisController::class, 'getWordCloudFromText'])->name('[其他-用户分析-获取用户弹幕词频]');
    // 其他 - 投诉管理
    Route::post('/others/complaint-management/get-data', [admin\others\ComplaintManagementController::class, 'getData'])->name('[其他-投诉管理-获取投诉数据列表]');
    Route::post('/others/complaint-management/get-data-details', [admin\others\ComplaintManagementController::class, 'getDataDetails'])->name('[其他-投诉管理-获取投诉详情]');
    // 认证相关
    Route::post('/auth/login', [admin\framework\AuthenticationController::class, 'login'])->name('[认证相关-登录]');
    Route::post('/auth/logout', [admin\framework\AuthenticationController::class, 'logout'])->name('[认证相关-退出登录]');
    Route::post('/auth/switch-role', [admin\framework\AuthenticationController::class, 'switchRole'])->name('[认证相关-切换角色]');
    Route::post('/auth/update-password', [admin\framework\AuthenticationController::class, 'updatePassword'])->name('[认证相关-修改密码]');
    // 用户管理
    Route::post('/users/list', [admin\framework\AdminUserController::class, 'list'])->name('[用户管理-获取管理员列表（分页）]');
    Route::post('/users/detail', [admin\framework\AdminUserController::class, 'detail'])->name('[用户管理-获取管理员详情]');
    Route::post('/users/create-or-update', [admin\framework\AdminUserController::class, 'createOrUpdate'])->name('[用户管理-创建或更新管理员信息]');
    Route::post('/users/delete', [admin\framework\AdminUserController::class, 'delete'])->name('[用户管理-删除管理员]');
    Route::post('/users/update-password', [admin\framework\AdminUserController::class, 'updatePassword'])->name('[用户管理-修改管理员密码]');
    Route::post('/users/update-profile', [admin\framework\AdminUserController::class, 'updateProfile'])->name('[用户管理-修改管理员个人信息]');
    // 角色管理
    Route::post('/roles/list', [admin\framework\AdminRoleController::class, 'list'])->name('[角色管理-获取角色列表（分页）]');
    Route::post('/roles/all', [admin\framework\AdminRoleController::class, 'all'])->name('[角色管理-获取所有角色列表]');
    Route::post('/roles/create-or-update', [admin\framework\AdminRoleController::class, 'createOrUpdate'])->name('[角色管理-创建或更新角色]');
    Route::post('/roles/delete', [admin\framework\AdminRoleController::class, 'delete'])->name('[角色管理-删除角色]');
    Route::post('/roles/permissions', [admin\framework\AdminRoleController::class, 'permissions'])->name('[角色管理-获取角色的菜单权限树]');
    // 权限管理
    Route::post('/permissions/menu', [admin\framework\AdminPermissionController::class, 'menu'])->name('[权限管理-获取全部菜单]');
    Route::post('/permissions/menu/validate', [admin\framework\AdminPermissionController::class, 'validateMenu'])->name('[权限管理-验证菜单是否存在]');
    Route::post('/permissions/menu/buttons', [admin\framework\AdminPermissionController::class, 'buttons'])->name('[权限管理-获取菜单下的按钮]');
    Route::post('/permissions/menu/create-or-update', [admin\framework\AdminPermissionController::class, 'createOrUpdateMenu'])->name('[权限管理-添加或修改菜单]');
    Route::post('/permissions/menu/toggle', [admin\framework\AdminPermissionController::class, 'toggleMenu'])->name('[权限管理-快速切换菜单的启用状态]');
    Route::post('/permissions/menu/delete', [admin\framework\AdminPermissionController::class, 'deleteMenu'])->name('[权限管理-删除菜单]');
    Route::post('/permissions/role/users', [admin\framework\AdminPermissionController::class, 'assignUsersToRole'])->name('[权限管理-角色与用户绑定]');
})->middleware([
    app\middleware\AccessControl::class,
    app\middleware\LangMiddleware::class,
    app\middleware\AdminAuthMiddleware::class
]);

// 不分离后台页面
Route::get('/', [app\controller\robot\PageController::class, 'main'])->middleware([app\middleware\BasicAuthMiddleware::class]);
Route::get('/login', [app\controller\robot\PageController::class, 'login'])->middleware([app\middleware\BasicAuthMiddleware::class]);
Route::group('/points-mall', function () {
    Route::get('/system-configuration', [app\controller\shop\ManagementController::class, 'pageSystemConfiguration']); // 系统配置
    Route::get('/mall-configuration', [app\controller\shop\ManagementController::class, 'pageMallConfiguration']); // 商城配置
    Route::get('/user-management', [app\controller\shop\ManagementController::class, 'pageUserManagement']); // 用户管理
    Route::get('/product-management', [app\controller\shop\ManagementController::class, 'pageProductManagement']); // 商品管理
    Route::get('/shipping-management', [app\controller\shop\ManagementController::class, 'pageShippingManagement']); // 发货管理
    Route::get('/complaint-management', [app\controller\shop\ManagementController::class, 'pageComplaintManagement']); // 投诉管理
    Route::get('/feedback', [app\controller\shop\ManagementController::class, 'pageFeedback']); // 问题反馈
    Route::get('/gift-records', [app\controller\shop\ManagementController::class, 'pageGiftRecords']); // 礼物记录
    Route::get('/danmu-records', [app\controller\shop\ManagementController::class, 'pageDanmuRecords']); // 弹幕记录
    Route::get('/user-analysis', [app\controller\shop\ManagementController::class, 'pageUserAnalysis']); // 用户分析
    Route::get('/gift-blind-box', [app\controller\shop\ManagementController::class, 'pageGiftBlindBox']); // 礼物盲盒
})->middleware([
    app\middleware\BasicAuthMiddleware::class
]);

// 不分离后台积分商城设置
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
    Route::any('/user-management/reset-password', [app\controller\shop\management\UserManagementController::class, 'resetPassword']);
    Route::any('/user-management/get-user-point-records', [app\controller\shop\management\UserManagementController::class, 'getUserPointRecords']);
    Route::any('/user-management/get-user-coin-records', [app\controller\shop\management\UserManagementController::class, 'getUserCoinRecords']);
    Route::any('/user-management/set-user-point', [app\controller\shop\management\UserManagementController::class, 'setUserPoint']);
    Route::any('/user-management/set-user-coin', [app\controller\shop\management\UserManagementController::class, 'setUserCoin']);
    Route::any('/product-management/get-data', [app\controller\shop\management\ProductManagementController::class, 'getData']);
    Route::any('/product-management/get-data-details', [app\controller\shop\management\ProductManagementController::class, 'getDataDetails']);
    Route::any('/product-management/set-data-details', [app\controller\shop\management\ProductManagementController::class, 'setDataDetails']);
    Route::any('/product-management/upload-images', [app\controller\shop\management\ProductManagementController::class, 'uploadImages']);
    Route::any('/shipping-management/get-data', [app\controller\shop\management\ShippingManagementController::class, 'getData']);
    Route::any('/shipping-management/get-data-details', [app\controller\shop\management\ShippingManagementController::class, 'getDataDetails']);
    Route::any('/shipping-management/set-data-details', [app\controller\shop\management\ShippingManagementController::class, 'setDataDetails']);
    Route::any('/complaint-management/get-data', [app\controller\shop\management\ComplaintManagementController::class, 'getData']);
    Route::any('/complaint-management/get-data-details', [app\controller\shop\management\ComplaintManagementController::class, 'getDataDetails']);
    Route::any('/gift-records-management/get-data', [app\controller\shop\management\GiftRecordsManagementController::class, 'getData']);
})->middleware([
    app\middleware\AccessControl::class,
    app\middleware\SignatureMiddleware::class
]);

// 不分离后台机器人设置
Route::group('/api/robot', function () {
    Route::any('/login-check', [app\controller\robot\ApiController::class, 'loginCheck']);
    Route::any('/login-out', [app\controller\robot\ApiController::class, 'loginOut']);
    Route::any('/get-user-info', [app\controller\robot\ApiController::class, 'getUserInfo']);
    Route::any('/get-real-room-info', [app\controller\robot\ApiController::class, 'getRealRoomInfo']);
    Route::any('/get-config', [app\controller\robot\ApiController::class, 'getConfig']);
    Route::any('/set-config', [app\controller\robot\ApiController::class, 'setConfig']);
    Route::any('/connect-out', [app\controller\robot\ApiController::class, 'connectOut']);
    Route::any('/export-config', [app\controller\robot\ApiController::class, 'exportConfig']);
    Route::any('/update-read', [app\controller\robot\ApiController::class, 'updateRead']);
})->middleware([
    app\middleware\SignatureMiddleware::class
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

// 允许对公钥的请求
Route::any('/public_key', function (Request $request) {
    $path = public_path('public_key.pem');
    if (!is_file($path)) {
        return json([
            'code' => 0,
            'message' => '别看了哥们，没这个证书',
            'data' => (object)[]
        ]);
    }
    return new Response(
        200,
        [
            'Content-Type' => 'application/x-pem-file',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
        ],
        file_get_contents($path)
    );
});

// 允许所有的options请求
Route::options('[{path:.+}]', function () {
    return response('', 204)
        ->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Auth-Token, Accept-Language',
        ]);
});

Route::fallback(function (Request $request) {
    $path = $request->path();
    // 如果是 dist 静态资源
    if (str_starts_with($path, 'dist/')) {
        $filePath = public_path() . '/' . $path;
        if (is_file($filePath)) {
            return response()->file($filePath);
        }
        return response('File not found', 404);
    }
    // 如果是前端路由（非 API），统一返回 index.html
    $indexPath = public_path() . '/dist/index.html';
    if (is_file($indexPath)) {
        return response()->file($indexPath);
    }
    // 最后兜底
    return json([
        'code' => 0,
        'message' => '别看了哥们，没这个页面',
        'data' => (object)[]
    ]);
});

Route::disableDefaultRoute(); // 关闭默认路由