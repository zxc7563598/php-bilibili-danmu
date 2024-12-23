<?php

namespace app\model;

use Carbon\Carbon;
use support\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemChangePointRecords extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'bl_system_change_point_records';

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
            // 用户信息变更
            $user_vips = UserVips::where('user_id', $model->user_id)->first();
            $user_vips->point = $model->after_point;
            $user_vips->save();
        });
    }
}
