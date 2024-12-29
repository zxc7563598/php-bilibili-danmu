<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use Hejunjie\Tools;
use support\Request;

class SystemConfigurationController extends GeneralMethod
{
    public function getData(Request $request)
    {
        // 返回数据
        return success($request, [
            'system_api_url' => getenv('SYSTEM_API_URL', ''),
            'system_aes_key' => getenv('SYSTEM_AES_KEY', ''),
            'system_aes_iv' => getenv('SYSTEM_AES_IV', ''),
            'system_key' => getenv('SYSTEM_KEY', ''),
            'host' => getenv('HOST', ''),
            'listen' => getenv('LISTEN', ''),
            're_open_host' => getenv('RE_OPEN_HOST', ''),
            'secure_api_key' => getenv('SECURE_API_KEY', ''),
            'redis_host' => getenv('REDIS_HOST', ''),
            'redis_port' => getenv('REDIS_PORT', ''),
            'db_host' => getenv('DB_HOST', ''),
            'db_port' => getenv('DB_PORT', ''),
            'db_name' => getenv('DB_NAME', ''),
            'db_user' => getenv('DB_USER', ''),
            'db_password' => getenv('DB_PASSWORD', '')
        ]);
    }

    public function setData(Request $request)
    {
        $param = $request->all();
        $system_api_url = $param['system_api_url'];
        $system_aes_key = $param['system_aes_key'];
        $system_aes_iv = $param['system_aes_iv'];
        $system_key = $param['system_key'];
        $host = $param['host'];
        $listen = $param['listen'];
        $re_open_host = $param['re_open_host'];
        $secure_api_key = $param['secure_api_key'];
        $redis_host = $param['redis_host'];
        $redis_port = $param['redis_port'];
        $db_host = $param['db_host'];
        $db_port = $param['db_port'];
        $db_name = $param['db_name'];
        $db_user = $param['db_user'];
        $db_password = $param['db_password'];
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
}
