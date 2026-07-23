<?php

namespace app\controller;

use app\model\ShopConfig;
use support\Redis;

class GeneralMethod
{
    /**
     * 获取商城配置信息（带 Redis 缓存，TTL 5 分钟）
     *
     * @return array
     */
    public static function getShopConfig(): array
    {
        $cacheKey = config('app')['app_name'] . ':config';
        $config = Redis::get($cacheKey);
        if (!empty($config)) {
            return json_decode($config, true);
        }
        // 使用 SETNX 避免并发重复查库
        $lockKey = $cacheKey . ':lock';
        if (Redis::setnx($lockKey, 1)) {
            Redis::expire($lockKey, 10); // 锁过期 10 秒，防止死锁
        } else {
            // 其他进程正在刷新缓存，等待后重试读取
            usleep(100000); // 100ms
            $config = Redis::get($cacheKey);
            if (!empty($config)) {
                return json_decode($config, true);
            }
        }
        $shop_config = ShopConfig::get();
        $data = [];
        foreach ($shop_config as $_shop_config) {
            $data[$_shop_config->title] = $_shop_config->content;
        }
        Redis::setex($cacheKey, 300, json_encode($data)); // TTL 5 分钟
        Redis::del($lockKey);
        return $data;
    }
}
