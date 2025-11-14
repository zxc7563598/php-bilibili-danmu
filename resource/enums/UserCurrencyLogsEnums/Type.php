<?php

namespace resource\enums\UserCurrencyLogsEnums;

/**
 * 类型
 */
enum Type: int
{
    case Up = 0;
    case Down = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Up => '增加',
            static::Down => '减少'
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
