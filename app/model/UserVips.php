<?php

namespace app\model;

use support\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserVips extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'bl_user_vips';

    /**
     * 重定义主键，默认是id
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

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
            // 用户信息变更
            $model->total_gift_amount = GiftRecords::where('uid', $model->uid)->sum('total_price');
            $model->total_danmu_count = DanmuLogs::where('uid', $model->uid)->count();
        });
    }
}
