<?php

namespace app\controller\admin\configuration;

use support\Request;
use support\Response;
use Hejunjie\Utils;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use support\Redis;

class SystemSettingsController
{
    /**
     * 获取系统配置数据
     * 
     * @return Response 
     */
    public function getData(Request $request): Response
    {
        // 获取 shop 文件夹是否存在
        $shop = is_dir(public_path('shop'));
        // 返回数据
        return success($request, [
            'shop' => $shop,
            'shop_name' => getenv('SHOP_NAME'),
            'shop_url' => getenv('SHOP_URL'),
            'system_api_url' => getenv('SYSTEM_API_URL'),
            'host' => getenv('HOST'),
            'listen' => getenv('LISTEN'),
            're_open_host' => getenv('RE_OPEN_HOST'),
            'secure_api_key' => getenv('SECURE_API_KEY'),
            'redis_host' => getenv('REDIS_HOST'),
            'redis_port' => getenv('REDIS_PORT'),
            'redis_password' => getenv('REDIS_PASSWORD') ? getenv('REDIS_PASSWORD') : '',
            'db_host' => getenv('DB_HOST'),
            'db_port' => getenv('DB_PORT'),
            'db_name' => getenv('DB_NAME'),
            'db_user' => getenv('DB_USER'),
            'db_password' => getenv('DB_PASSWORD')
        ]);
    }

    /**
     * 获取商城二维码
     * 
     * @param string $url 地址
     *  
     * @return Response 
     */
    public function getDataQrCode(Request $request): Response
    {
        // 获取请求参数
        $url = $request->post('url');
        // 生成二维码
        $qrcode = md5($url) . '.png';
        // 确认目录信息，不存在则创建
        if (!is_dir(public_path() . '/attachment/qrcode/shop')) {
            mkdir(public_path() . '/attachment/qrcode/shop', 0777, true);
        }
        // 信息存储，并生成二维码
        $code = new Builder();
        $code->build(new PngWriter(), null, null, $url, new Encoding('UTF-8'), null, 300, 10)
            ->saveToFile(public_path() . '/attachment/qrcode/shop/' . $qrcode);
        // 返回数据
        return success($request, [
            'url' => getImageUrl('qrcode/shop/' . $qrcode)
        ]);
    }

    /**
     * 设置系统配置数据
     * 
     * @param string $shop_name 商城名称 
     * @param string $shop_url 商城链接 
     * @param string $system_api_url 项目地址 
     * @param string $host 项目启动地址 
     * @param string $listen 项目启动端口 
     * @param string $re_open_host 重启项目地址 
     * @param string $secure_api_key 重启密钥 
     * @param string $redis_host Redis地址 
     * @param string $redis_port Redis端口 
     * @param string $redis_password Redis密码
     * @param string $db_host 数据库地址 
     * @param string $db_port 数据库端口 
     * @param string $db_name 数据库账号 
     * @param string $db_user 数据库名称 
     * @param string $db_password 数据库密码 
     * 
     * @return Response 
     */
    public function setData(Request $request): Response
    {
        // 限制请求频率
        $redis = Redis::get(config('app')['app_name'] . ':system_set_config');
        if (!empty($redis)) {
            return fail($request, 800016);
        }
        Redis::setEx(config('app')['app_name'] . ':system_set_config', 30, 1);
        // 获取请求参数
        $configKeys = [
            'shop_name',
            'shop_url',
            'system_api_url',
            'host',
            'listen',
            're_open_host',
            'secure_api_key',
            'redis_host',
            'redis_port',
            'redis_password',
            'db_host',
            'db_port',
            'db_name',
            'db_user',
            'db_password'
        ];
        // 组装需要修改的环境变量数据
        $data = array_map(fn($key) => ['key' => strtoupper($key), 'value' => $request->post($key, '')], $configKeys);
        // 读取 .env 文件内容
        $env = Utils\FileUtils::readFile(base_path() . '/.env');
        // 需要重新构建VUE的关键配置项
        $shouldExecuteCode = false;
        $keysToCheck = ['SYSTEM_API_URL', 'SHOP_NAME', 'SHOP_URL'];
        foreach ($keysToCheck as $key) {
            preg_match("/^$key=(.*)$/m", $env, $matches);
            $currentValue = $matches[1] ?? null;
            $newValue = $request->post(strtolower($key));
            if ($currentValue !== $newValue) {
                $shouldExecuteCode = true;
                break;
            }
        }
        if (!is_dir(public_path('shop'))) {
            $shouldExecuteCode = true;
        }
        // 更新环境变量
        foreach ($data as $_data) {
            // 检查环境变量是否存在并更新
            $env = strpos($env, $_data['key'] . "=") !== false
                ? preg_replace("/^" . $_data['key'] . "=.*/m", $_data['key'] . "=" . $_data['value'], $env)
                : $env .= "\n" . $_data['key'] . "=" . $_data['value']; // 不存在则添加
        }
        // 写回 .env 文件
        Utils\FileUtils::writeToFile(base_path() . '/.env', $env);
        // 重启系统（发送信号）
        posix_kill(posix_getppid(), SIGUSR1);
        // 重新构建VUE
        $shell = false;
        if ($shouldExecuteCode) {
            // 定义脚本路径
            $scriptPath = base_path() . '/scripts/build_vue.sh';
            // 将脚本放到后台执行
            $command = "sh $scriptPath > /dev/null 2>&1 &";
            exec($command);
            $shell = true;
        }
        // 返回数据
        return success($request, [
            'shell' => $shell
        ]);
    }
}
