<?php

namespace app\model;

use support\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
            $user_vips->total_gift_amount += $model->total_price;
            $user_vips->save();
        });
    }
}
