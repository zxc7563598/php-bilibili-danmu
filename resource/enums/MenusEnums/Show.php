<?php

namespace resource\enums\MenusEnums;

/**
 * 是否显示
 */
enum Show: int
{
    case Hide = 0;
    case Show = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Hide => trans('hide'),
            static::Show => trans('show')
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
