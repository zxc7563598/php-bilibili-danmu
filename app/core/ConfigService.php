<?php

namespace app\core;

use Hejunjie\Utils;

/**
 * 机器人配置管理服务
 *
 * 统一管理所有 .cfg 配置文件的读写，消除各控制器中的重复代码。
 * 每次读取时自动填充默认值，写入时使用原子操作避免配置丢失。
 */
class ConfigService
{
    /** @var string 配置文件存储目录 */
    private const CONFIG_DIR = '/tmp';

    /**
     * 配置项定义：名称 => 默认值
     * 所有配置的默认值集中在此维护，getConfig 和 exportConfig 共用。
     */
    public const SCHEMA = [
        'timing' => [
            'opens' => false,
            'intervals' => null,
            'status' => 0,
            'content' => null,
        ],
        'present' => [
            'opens' => false,
            'merge' => 0,
            'number' => 0,
            'price' => null,
            'name_length' => 0,
            'status' => 0,
            'type' => 0,
            'content' => null,
            'blind_box_stats' => 0,
        ],
        'enter' => [
            'opens' => false,
            'status' => 0,
            'type' => 0,
            'content' => null,
        ],
        'pk' => [
            'opens' => false,
            'content' => null,
        ],
        'follow' => [
            'opens' => false,
            'status' => 0,
            'type' => 0,
            'content' => null,
        ],
        'share' => [
            'opens' => false,
            'status' => 0,
            'type' => 0,
            'content' => null,
        ],
        'autoresponders' => [
            'opens' => false,
            'status' => 0,
            'type' => 0,
            'content' => [],
        ],
        'check_in' => [
            'opens' => false,
            'status' => 0,
            'type' => 0,
            'currency_type' => 1,
            'keywords' => null,
            'select' => null,
            'success' => null,
            'reply' => null,
            'points' => 0,
        ],
    ];

    /**
     * 获取单个配置（自动填充默认值）
     *
     * @param string $name 配置名称（对应 SCHEMA 的 key）
     * @return array
     */
    public static function get(string $name): array
    {
        $defaults = self::SCHEMA[$name] ?? [];
        $content = readFileContent(runtime_path() . self::CONFIG_DIR . '/' . str_replace('_', '-', $name) . '.cfg');
        if ($content) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return array_merge($defaults, $decoded);
            }
        }
        // 确保默认配置文件存在
        self::write($name, $defaults);
        return $defaults;
    }

    /**
     * 获取全部配置
     *
     * @return array<string, array>
     */
    public static function all(): array
    {
        $result = [];
        foreach (self::SCHEMA as $name => $defaults) {
            $result[$name] = self::get($name);
        }
        return $result;
    }

    /**
     * 原子写入配置（先写临时文件再 rename，避免配置丢失）
     *
     * @param string $name 配置名称
     * @param array  $data 配置数据
     * @return void
     */
    public static function write(string $name, array $data): void
    {
        $filePath = runtime_path() . self::CONFIG_DIR . '/' . str_replace('_', '-', $name) . '.cfg';
        $tmpPath = $filePath . '.tmp.' . getmypid();
        Utils\FileUtils::writeToFile($tmpPath, json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        // 原子替换（同一文件系统上 rename 直接覆盖，无需先 unlink）
        rename($tmpPath, $filePath);
    }

    /**
     * 批量写入配置（仅写入非 false 的项）
     *
     * @param array<string, array|false> $configs
     * @return void
     */
    public static function set(array $configs): void
    {
        foreach ($configs as $name => $data) {
            if ($data !== false) {
                self::write($name, $data);
            }
        }
    }

    /**
     * 获取所有配置（仅用于导出，不自动创建文件）
     *
     * @return array<string, array>
     */
    public static function export(): array
    {
        $result = [];
        foreach (self::SCHEMA as $name => $defaults) {
            $content = readFileContent(runtime_path() . self::CONFIG_DIR . '/' . str_replace('_', '-', $name) . '.cfg');
            if ($content) {
                $decoded = json_decode($content, true);
                $result[$name] = is_array($decoded) ? array_merge($defaults, $decoded) : $defaults;
            } else {
                $result[$name] = $defaults;
            }
        }
        return $result;
    }
}
