<?php

namespace resource\enums\MenusEnums;

/**
 * 是否启用
 */
enum Enable: int
{
    case Disable = 0;
    case Enable = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Disable => trans('disable'),
            static::Enable => trans('enable')
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
