<?php

namespace resource\enums\SystemChangePointRecordsEnums;

/**
 * 变更积分类型
 */
enum PointType: int
{
    case Point = 0;
    case Coin = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Point => '积分',
            static::Coin => '硬币'
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
