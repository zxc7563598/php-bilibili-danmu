<?php

namespace app\bootstrap;

use Webman\Bootstrap;

class RsaKeyGenerator implements Bootstrap
{
    protected static string $privateKeyPath = __DIR__ . '/../../private_key.pem';
    protected static string $publicKeyPath  = __DIR__ . '/../../public/public_key.pem';
    protected static string $lockFile       = __DIR__ . '/../../runtime/rsa_key_gen.lock';

    public static function start($worker): void
    {
        // 如果文件都存在，直接返回
        if (file_exists(self::$privateKeyPath) && file_exists(self::$publicKeyPath)) {
            return;
        }
        // 创建锁文件目录
        if (!is_dir(dirname(self::$lockFile))) {
            mkdir(dirname(self::$lockFile), 0777, true);
        }
        $fp = fopen(self::$lockFile, 'c');
        if (!$fp) {
            echo "[RsaKeyGenerator] Failed to open lock file.\n";
            return;
        }
        // 尝试加锁，阻塞模式
        if (!flock($fp, LOCK_EX)) {
            echo "[RsaKeyGenerator] Failed to acquire lock.\n";
            fclose($fp);
            return;
        }
        // 双重检查：防止其他进程已生成
        if (!file_exists(self::$privateKeyPath) || !file_exists(self::$publicKeyPath)) {
            echo "[RsaKeyGenerator] Generating RSA-2048 key pair...\n";
            $config = [
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ];
            $res = openssl_pkey_new($config);
            if ($res) {
                openssl_pkey_export($res, $privateKey);
                $keyDetails = openssl_pkey_get_details($res);
                $publicKey = $keyDetails['key'];
                file_put_contents(self::$privateKeyPath, $privateKey);
                file_put_contents(self::$publicKeyPath, $publicKey);
                echo "[RsaKeyGenerator] RSA-2048 key pair generated successfully.\n";
            } else {
                echo "[RsaKeyGenerator] Failed to generate RSA key pair.\n";
            }
        }
        // 释放锁并关闭文件
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
