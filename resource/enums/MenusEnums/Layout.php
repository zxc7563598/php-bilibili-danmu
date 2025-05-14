<?php

namespace resource\enums\MenusEnums;

/**
 * 布局
 */
enum Layout: string
{
    case Follow = '';
    case Simple = 'simple';
    case Normal = 'normal';
    case Full = 'full';
    case Empty = 'empty';

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Follow => trans('follow'),
            static::Simple => trans('simple'),
            static::Normal => trans('normal'),
            static::Full => trans('full'),
            static::Empty => trans('empty'),
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
