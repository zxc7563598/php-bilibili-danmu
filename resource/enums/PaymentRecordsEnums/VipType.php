<?php

namespace resource\enums\PaymentRecordsEnums;

/**
 * 舰长类型
 */
enum VipType: int
{
    case Lv0 = 0;
    case Lv1 = 1;
    case Lv2 = 2;
    case Lv3 = 3;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Lv0 => '潜在老头',
            static::Lv1 => '舰长宝宝',
            static::Lv2 => '提督老公',
            static::Lv3 => '总督主人'
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
