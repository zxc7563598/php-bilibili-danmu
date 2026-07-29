<?php

namespace app\core;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\VerifySolutionOptions;
use app\model\Admins;
use Carbon\Carbon;
use support\Cache;
use resource\enums\AdminsEnums;

/**
 * 管理员认证服务
 * @package app\service
 */
class AdminAuthService
{

    /**
     * 执行登录
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $captcha 验证码
     * 
     * @return string|int 登录凭证｜错误码
     */
    public static function login(string $username, string $password, string $captcha = ''): string|int
    {
        $hmacKey = config('app.altcha_hmac_key');
        if ($hmacKey) {
            $algorithm = new Pbkdf2();
            $altcha = new Altcha(hmacSignatureSecret: $hmacKey);
            $result = $altcha->verifySolution(new VerifySolutionOptions(
                payload: $captcha,
                algorithm: $algorithm,
            ));
            if (!$result->verified) {
                if ($result->expired) {
                    return 900008;
                } else {
                    return 900009;
                }
            }
        }
        // 查询账号
        $admins = Admins::where('username', $username)->first();
        if (empty($admins)) {
            return 900006;
        }
        if ($admins->enable == AdminsEnums\Enable::Disable->value) {
            return 900007;
        }
        // 验证密码（优先 bcrypt，回退到旧 SHA1 并自动升级）
        if (!self::verifyPassword($password, $admins)) {
            return 900006;
        }
        // 生成token（使用密码学安全的随机字节）
        $token = bin2hex(random_bytes(32));
        // 删除先前的token信息
        if ($admins->token) {
            Cache::delete($admins->token);
        }
        $admins->token = $token;
        $admins->save();
        Cache::set($token, json_encode([
            'id' => $admins->id,
            'role_id' => $admins->role_id,
            'timestamp' => Carbon::now()->timezone(config('app.default_timezone'))->timestamp
        ]), 86400 * 7);
        // 返回数据
        return $token;
    }

    /**
     * 退出登录
     * 
     * @param integer $id 退出用户id
     * 
     * @return void 
     */
    public static function logout(int $id): void
    {
        $admins = Admins::where('id', $id)->first();
        $token = $admins->token;
        $admins->token = null;
        $admins->save();
        Cache::delete($token);
    }

    /**
     * 验证密码（兼容旧 SHA1 哈希，验证通过后自动升级为 bcrypt）
     *
     * @param string $password 明文密码
     * @param Admins $admins 管理员模型实例
     *
     * @return bool
     */
    public static function verifyPassword(string $password, Admins $admins): bool
    {
        // 优先使用 bcrypt 验证
        if (password_verify($password, $admins->password)) {
            return true;
        }
        // 回退到旧的双 SHA1 + 盐验证
        if (!empty($admins->salt) && sha1(sha1($password) . $admins->salt) === $admins->password) {
            // 自动升级为 bcrypt
            $admins->password = $password;
            $admins->save();
            return true;
        }
        return false;
    }
}
