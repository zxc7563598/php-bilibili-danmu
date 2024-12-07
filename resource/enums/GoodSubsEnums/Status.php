<?php

namespace resource\enums\GoodSubsEnums;

/**
 * 商品状态
 */
enum Status: int
{
    case Deactivate = 0;
    case Normal = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Deactivate => '停用',
            static::Normal => '正常'
        };
    }

    // 获取全部的枚举
    public static function all(): array
    {
        $cases = self::cases();
        $enums = [];
        foreach ($cases as $_cases) {
            $enums[] = [
                'key' => $_cases->value,
                'value' => $_cases->label()
            ];
        }
        return $enums;
    }
}
