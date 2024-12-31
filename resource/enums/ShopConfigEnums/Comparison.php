<?php

namespace resource\enums\ShopConfigEnums;

/**
 * 对比符
 */
enum Comparison: int
{
    case GreaterThan = 0;
    case GreaterThanOrEqualTo = 1;
    case LessThan = 2;
    case LessThanOrEqualTo = 3;
    case EqualTo = 4;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::GreaterThan => '大于',
            static::GreaterThanOrEqualTo => '大于等于',
            static::LessThan => '小于',
            static::LessThanOrEqualTo => '小于等于',
            static::EqualTo => '等于'
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
