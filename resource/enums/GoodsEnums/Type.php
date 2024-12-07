<?php

namespace resource\enums\GoodsEnums;

/**
 * 商品类型
 */
enum Type: int
{
    case Virtually = 0;
    case Entity = 1;
    case Tribute = 2;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Virtually => '虚拟礼物',
            static::Entity => '实体礼物',
            static::Tribute => '贡品'
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
