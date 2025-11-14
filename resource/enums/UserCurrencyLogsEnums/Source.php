<?php

namespace resource\enums\UserCurrencyLogsEnums;

/**
 * 来源类型
 */
enum Source: int
{
    case AnchorChange = 0;
    case SignIn = 1;
    case AutomaticallyClear = 2;
    case PurchaseVip = 3;
    case GiftRebate = 4;
    case Exchange = 5;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::AnchorChange => '主播变更',
            static::SignIn => '签到',
            static::AutomaticallyClear => '系统自动清理',
            static::PurchaseVip => '开通航海',
            static::GiftRebate => '礼物返利',
            static::Exchange => '兑换商品',
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
