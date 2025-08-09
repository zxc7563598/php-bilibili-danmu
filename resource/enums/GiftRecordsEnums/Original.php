<?php

namespace resource\enums\GiftRecordsEnums;

/**
 * 是否是原始商品
 */
enum Original: int
{
    case No = 0;
    case Yes = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::No => '否',
            static::Yes => '是'
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
