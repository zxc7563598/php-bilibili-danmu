<?php

namespace app\model;

use app\core\AdminAuthService;
use support\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use support\Cache;

class Admins extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'bl_admins';

    /**
     * 重定义主键，默认是id
     *
     * @var string
     */
    protected $primaryKey = 'id';

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
            // 密码变更
            if (!empty($model->password)) {
                if ($model->getOriginal('password') != $model->password) {
                    $model->salt = mt_rand(1000, 9999);
                    $model->password = sha1(sha1($model->password) . $model->salt);
                }
            }
            // 如果用户登录，清除缓存信息
            if (!empty($model->token)) {
                Cache::delete($model->token);
            }
        });
    }

    // 定义与 AdminRoles 的一对多关系
    public function roles(): HasMany
    {
        return $this->hasMany(AdminRoles::class, 'admin_id', 'id');
    }

    // 定义与 AdminLogs 的一对多关系
    public function logs(): HasMany
    {
        return $this->hasMany(AdminLogs::class, 'admin_id', 'id');
    }
}
