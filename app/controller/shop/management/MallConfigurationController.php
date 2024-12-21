<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use app\model\ShopConfig;
use Hejunjie\Tools;
use support\Request;

class MallConfigurationController extends GeneralMethod
{
    public function getData(Request $request)
    {
        // 获取数据
        $shop_config = ShopConfig::get([
            'title' => 'title',
            'description' => 'description',
            'content' => 'content'
        ]);
        // 处理数据
        $data = [];
        foreach ($shop_config as $_shop_config) {
            $data[$_shop_config->title] = $_shop_config->content;
        }
        // 返回数据
        return success($request, [
            'login-background-image' => $data['login-background-image'], // 登录页面背景图
            'personal-background-image' => $data['personal-background-image'], // 个人中心背景图
            'theme-color' => $data['theme-color'], // 主题色
            'protocols-surname' => $data['protocols-surname'], // 协议人姓名
            'protocols-uid' => $data['protocols-uid'], // 协议人UID
            'protocols-name' => $data['protocols-name'], // 协议名称
            'protocols-signature' => $data['protocols-signature'], // 协议人签名
            'protocols-content' => $data['protocols-content'], // 协议内容
            'virtual-gift-order-successful-icon' => $data['virtual-gift-order-successful-icon'], // 虚拟礼物下单成功图标
            'virtual-gift-order-successful-title' => $data['virtual-gift-order-successful-title'], // 虚拟礼物下单成功标题
            'virtual-gift-order-successful-content' => $data['virtual-gift-order-successful-content'], // 虚拟礼物下单成功内容
            'virtual-gift-order-successful-button' => $data['virtual-gift-order-successful-button'], // 虚拟礼物下单成功按钮
            'realism-gift-order-successful-icon' => $data['realism-gift-order-successful-icon'], // 实体礼物下单成功图标
            'realism-gift-order-successful-title' => $data['realism-gift-order-successful-title'], // 实体礼物下单成功标题
            'realism-gift-order-successful-content' => $data['realism-gift-order-successful-content'], // 实体礼物下单成功内容
            'realism-gift-order-successful-button' => $data['realism-gift-order-successful-button'], // 实体礼物下单成功按钮
            'tribute-gift-order-successful-icon' => $data['tribute-gift-order-successful-icon'], // 贡品下单成功图标
            'tribute-gift-order-successful-title' => $data['tribute-gift-order-successful-title'], // 贡品下单成功标题
            'tribute-gift-order-successful-content' => $data['tribute-gift-order-successful-content'], // 贡品下单成功内容
            'tribute-gift-order-successful-button' => $data['tribute-gift-order-successful-button'], // 贡品下单成功按钮
            'tribute-gift-order-successful-rankings' => $data['tribute-gift-order-successful-rankings'], // 贡品下单成功是否开启排名
            'tribute-gift-order-successful-rankingslist' => $data['tribute-gift-order-successful-rankingslist'], // 贡品下单成功排名列表
        ]);
    }

    public function setData(Request $request)
    {
        $param = $request->all();
        $system_api_url = isset($param['system_api_url']) ? $param['system_api_url'] : '';
        $system_aes_key = isset($param['system_aes_key']) ? $param['system_aes_key'] : '';
        $system_aes_iv = isset($param['system_aes_iv']) ? $param['system_aes_iv'] : '';
        $system_key = isset($param['system_key']) ? $param['system_key'] : '';
        $host = isset($param['host']) ? $param['host'] : '';
        $listen = isset($param['listen']) ? $param['listen'] : '';
        $re_open_host = isset($param['re_open_host']) ? $param['re_open_host'] : '';
        $secure_api_key = isset($param['secure_api_key']) ? $param['secure_api_key'] : '';
        $redis_host = isset($param['redis_host']) ? $param['redis_host'] : '';
        $redis_port = isset($param['redis_port']) ? $param['redis_port'] : '';
        $db_host = isset($param['db_host']) ? $param['db_host'] : '';
        $db_port = isset($param['db_port']) ? $param['db_port'] : '';
        $db_name = isset($param['db_name']) ? $param['db_name'] : '';
        $db_user = isset($param['db_user']) ? $param['db_user'] : '';
        $db_password = isset($param['db_password']) ? $param['db_password'] : '';
        // 存储数据
        // 要修改的环境变量名和值
        $data = [
            ['key' => 'SYSTEM_API_URL', 'value' => $system_api_url],
            ['key' => 'SYSTEM_AES_KEY', 'value' => $system_aes_key],
            ['key' => 'SYSTEM_AES_IV', 'value' => $system_aes_iv],
            ['key' => 'SYSTEM_KEY', 'value' => $system_key],
            ['key' => 'HOST', 'value' => $host],
            ['key' => 'LISTEN', 'value' => $listen],
            ['key' => 'RE_OPEN_HOST', 'value' => $re_open_host],
            ['key' => 'SECURE_API_KEY', 'value' => $secure_api_key],
            ['key' => 'REDIS_HOST', 'value' => $redis_host],
            ['key' => 'REDIS_PORT', 'value' => $redis_port],
            ['key' => 'DB_HOST', 'value' => $db_host],
            ['key' => 'DB_PORT', 'value' => $db_port],
            ['key' => 'DB_NAME', 'value' => $db_name],
            ['key' => 'DB_USER', 'value' => $db_user],
            ['key' => 'DB_PASSWORD', 'value' => $db_password]
        ];
        // 读取 .env 文件内容
        $env = Tools\FileUtils::readFile(base_path() . '/.env');
        foreach ($data as $_data) {
            // 检查环境变量是否存在
            if (strpos($env, $_data['key'] . "=") !== false) {
                // 替换掉旧的值
                $env = preg_replace("/^" . $_data['key'] . "=.*/m", $_data['key'] . "=" . $_data['value'], $env);
            } else {
                // 如果环境变量不存在，则新增
                $env .= "\n" . $_data['key'] . "=" . $_data['value'];
            }
        }
        Tools\FileUtils::writeToFile(base_path() . '/.env', $env);
        // 重启系统
        posix_kill(posix_getppid(), SIGUSR1);
        // 返回数据
        return success($request, []);
    }

    public function uploadImages(Request $request) {}
}
