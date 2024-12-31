<?php

namespace resource\enums\ComplaintEnums;

/**
 * 是否已读
 */
enum Read: int
{
    case Unread = 0;
    case Read = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Unread => '未读',
            static::Read => '已读'
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
