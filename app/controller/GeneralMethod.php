<?php

namespace app\controller;

use app\model\ShopConfig;
use support\Redis;

class GeneralMethod
{
    /**
     * 获取商城配置信息
     * 
     * @return array 
     */
    protected static function getShopConfig(): array
    {
        $config = Redis::get(config('app')['app_name'] . ':config');
        if (empty($config)) {
            $shop_config = ShopConfig::get();
            $data = [];
            foreach ($shop_config as $_shop_config) {
                $data[$_shop_config->title] = $_shop_config->content;
            }
            Redis::set(config('app')['app_name'] . ':config', json_encode($data));
        } else {
            $data = json_decode($config, true);
        }
        return $data;
    }
}
