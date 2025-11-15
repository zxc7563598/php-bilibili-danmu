<?php

namespace app\model;

use support\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use resource\enums\UserCurrencyLogsEnums;
use resource\enums\GoodsEnums;

class RedemptionRecords extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'bl_redemption_records';

    /**
     * 重定义主键，默认是id
     *
     * @var string
     */
    protected $primaryKey = 'records_id';

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = true;
    protected $dateFormat = 'U';

    /**
     * 指示模型主键是否递增
     *
     * @var bool
     */
    public $incrementing = true;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // 用户余额变更
            $user_currency_logs = new UserCurrencyLogs();
            $user_currency_logs->user_id = $model->user_id;
            $user_currency_logs->type = UserCurrencyLogsEnums\Type::Down->value;
            $user_currency_logs->source = UserCurrencyLogsEnums\Source::Exchange->value;
            switch ($model->amount_type) {
                case GoodsEnums\AmountType::Point->value:
                    $user_currency_logs->currency_type = UserCurrencyLogsEnums\CurrencyType::Point->value;
                    break;
                case GoodsEnums\AmountType::Coin->value:
                    $user_currency_logs->currency_type = UserCurrencyLogsEnums\CurrencyType::Coin->value;
                    break;
            }
            $user_currency_logs->currency = $model->point;
            $user_currency_logs->pre_currency = $model->pre_point;
            $user_currency_logs->after_currency = $model->after_point;
            $user_currency_logs->save();
        });
    }
}
