<?php

namespace resource\enums\DanmuLogsEnums;

/**
 * 牌子类型
 */
enum BadgeType: int
{
    case Lv0 = 0;
    case Lv3 = 1;
    case Lv2 = 2;
    case Lv1 = 3;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Lv0 => '普通',
            static::Lv1 => '舰长',
            static::Lv2 => '提督',
            static::Lv3 => '总督'
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
