<?php

namespace resource\enums\MenusEnums;

/**
 * 类型
 */
enum Type: string
{
    case Button = 'BUTTON';
    case Menu = 'MENU';

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Button => trans('button'),
            static::Menu => trans('menu')
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
