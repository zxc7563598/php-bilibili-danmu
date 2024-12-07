<?php

namespace resource\enums\RedemptionRecordsEnums;

/**
 * 发货状态
 */
enum Status: int
{
    case NoShipment = 0;
    case Shipment = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::NoShipment => '未发货',
            static::Shipment => '已发货'
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
