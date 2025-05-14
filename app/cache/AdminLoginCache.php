<?php

namespace app\cache;

use app\model\Admins;
use Hejunjie\Cache\Interfaces\DataSourceInterface;

class AdminLoginCache  implements \Hejunjie\Cache\Interfaces\DataSourceInterface
{
    protected DataSourceInterface $wrapped;

    /**
     * 获取管理员用户信息
     * 
     * @param string $token 
     * @return string|null 
     */
    public function get(string $token): ?string
    {
        $admins = Admins::where('token', $token)->first([
            'id' => 'id',
            'nickname' => 'nickname',
            'username' => 'username',
            'enable' => 'enable',
            'role_id' => 'role_id',
            'avatar' => 'avatar',
            'email' => 'email',
            'address' => 'address',
            'gender' => 'gender',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ]);
        if (!empty($admins)) {
            $admins->avatar = getImageUrl($admins->avatar);
            return json_encode($admins, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
        }
        return null;
    }

    /**
     * 存储管理员登录信息
     * 
     * @param string $token 登录凭证
     * @param string $admin_json 管理员信息
     * @return bool 
     */
    public function set(string $token, string $admin_json): bool
    {
        $admin_data = json_decode($admin_json, true);
        $admins = Admins::where('id', $admin_data['id'])->first();
        if (!empty($admins)) {
            $admins->token = $token;
            $admins->save();
        }
        return true;
    }

    /**
     * 删除管理员登录信息，不要实现
     * 
     * @param string $token 
     * @return void 
     */
    public function del(string $token): void {}
}
