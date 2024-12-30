<?php

namespace app\controller\shop\management;

use app\controller\GeneralMethod;
use Exception;
use Hejunjie\Tools;
use support\Request;
use support\Response;

class SystemConfigurationController extends GeneralMethod
{
    /**
     * 获取系统配置数据
     * 
     * @return Response 
     */
    public function getData(Request $request)
    {
        // 获取 shop 文件夹是否存在
        $shop = is_dir(public_path('shop'));
        // 返回数据
        return success($request, [
            'shop' => $shop,
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

    /**
     * 设置系统配置数据
     * 
     * @param string $system_api_url 项目地址 
     * @param string $system_aes_key AES加密KEY 
     * @param string $system_aes_iv AES加密IV 
     * @param string $system_key 签名KEY 
     * @param string $host 项目启动地址 
     * @param string $listen 项目启动端口 
     * @param string $re_open_host 重启项目地址 
     * @param string $secure_api_key 重启密钥 
     * @param string $redis_host Redis地址 
     * @param string $redis_port Redis端口 
     * @param string $db_host 数据库地址 
     * @param string $db_port 数据库端口 
     * @param string $db_name 数据库账号 
     * @param string $db_user 数据库名称 
     * @param string $db_password 数据库密码 
     * 
     * @return Response 
     */
    public function setData(Request $request)
    {
        // 获取请求参数
        $param = $request->all();
        $configKeys = [
            'system_api_url',
            'system_aes_key',
            'system_aes_iv',
            'system_key',
            'host',
            'listen',
            're_open_host',
            'secure_api_key',
            'redis_host',
            'redis_port',
            'db_host',
            'db_port',
            'db_name',
            'db_user',
            'db_password'
        ];
        // 组装需要修改的环境变量数据
        $data = array_map(fn($key) => ['key' => strtoupper($key), 'value' => $param[$key]], $configKeys);
        // 读取 .env 文件内容
        $env = Tools\FileUtils::readFile(base_path() . '/.env');
        // 需要重新构建VUE的关键配置项
        $shouldExecuteCode = false;
        $keysToCheck = ['SYSTEM_API_URL', 'SYSTEM_AES_KEY', 'SYSTEM_AES_IV', 'SYSTEM_KEY'];
        foreach ($keysToCheck as $key) {
            preg_match("/^$key=(.*)$/m", $env, $matches);
            $currentValue = $matches[1] ?? null;
            $newValue = $param[strtolower($key)];
            if ($currentValue !== $newValue) {
                $shouldExecuteCode = true;
                break;
            }
        }
        // 更新环境变量
        foreach ($data as $_data) {
            // 检查环境变量是否存在并更新
            $env = strpos($env, $_data['key'] . "=") !== false
                ? preg_replace("/^" . $_data['key'] . "=.*/m", $_data['key'] . "=" . $_data['value'], $env)
                : $env .= "\n" . $_data['key'] . "=" . $_data['value']; // 不存在则添加
        }
        // 写回 .env 文件
        Tools\FileUtils::writeToFile(base_path() . '/.env', $env);
        // 重启系统（发送信号）
        posix_kill(posix_getppid(), SIGUSR1);
        // 重新构建VUE
        if ($shouldExecuteCode) {
            // 定义脚本路径并执行
            $scriptPath = base_path() . '/scripts/build_vue.sh';
            exec("sh $scriptPath", $output, $return_var);
            // 判断脚本执行结果
            if ($return_var !== 0) {
                return fail($request, 900007);
            }
        }
        // 返回数据
        return success($request, []);
    }

    /**
     * 构建积分商城
     * 
     * @return Response 
     */
    public function buildShop(Request $request)
    {
        // 定义脚本路径并执行
        $scriptPath = base_path() . '/scripts/build_vue.sh';
        exec("sh $scriptPath", $output, $return_var);
        // 判断脚本执行结果
        if ($return_var !== 0) {
            return fail($request, 900007);
        }
        // 返回成功响应
        return success($request, [
            'output' => $output,
            'return_var' => $return_var
        ]);
    }
}
