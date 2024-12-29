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

        static::saving(function ($model) {
            if ($model->getOriginal('password') != $model->password) {
                $model->salt = mt_rand(1000, 9999);
                $model->password = sha1(sha1($model->password) . $model->salt);
            }
        });
    }
}
