<?php

namespace resource\enums\SystemChangePointRecordsEnums;

/**
 * 来源类型
 */
enum Source: int
{
    case AnchorChange = 0;
    case SignIn = 1;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::AnchorChange => '主播变更',
            static::SignIn => '签到'
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
