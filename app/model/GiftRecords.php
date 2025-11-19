<?php

namespace app\model;

use app\core\LoginPublicMethods;
use support\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use resource\enums\UserCurrencyLogsEnums;

class GiftRecords extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'bl_gift_records';

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

        static::created(function ($model) {
            // 用户信息变更
            $user_vips = UserVips::where('uid', $model->uid)->first();
            if (empty($user_vips)) {
                LoginPublicMethods::userRegister($model->uid, '');
                $user_vips = UserVips::where('uid', $model->uid)->first();
            }
            $user_vips->total_gift_amount += $model->total_price;
            $user_vips->save();
            // 礼物返利
            if ($model->rebate_point > 0) {
                $user_currency_logs = new UserCurrencyLogs();
                $user_currency_logs->user_id = $user_vips->user_id;
                $user_currency_logs->type = UserCurrencyLogsEnums\Type::Up->value;
                $user_currency_logs->source = UserCurrencyLogsEnums\Source::GiftRebate->value;
                $user_currency_logs->currency_type = UserCurrencyLogsEnums\CurrencyType::Point->value;
                $user_currency_logs->currency = $model->rebate_point;
                $user_currency_logs->pre_currency = $user_vips->point;
                $user_currency_logs->after_currency = $model->rebate_point + $user_vips->point;
                $user_currency_logs->save();
            }
        });
    }
}
