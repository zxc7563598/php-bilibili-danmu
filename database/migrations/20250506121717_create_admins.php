<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class CreateAdmins extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // 创建表
        $table = $this->table('bl_admins', ['id' => 'id', 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '管理员表']);
        // 添加字段
        $table->addColumn('nickname', 'string', ['null' => false, 'comment' => '昵称'])
            ->addColumn('username', 'string', ['null' => false, 'comment' => '用户名'])
            ->addColumn('password', 'string', ['null' => false, 'comment' => '密码'])
            ->addColumn('salt', 'integer', ['limit' => MysqlAdapter::INT_MEDIUM, 'null' => false, 'comment' => '扰乱码'])
            ->addColumn('token', 'string', ['null' => true, 'comment' => '登录凭证'])
            ->addColumn('role_id', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '当前角色ID'])
            ->addColumn('avatar', 'string', ['null' => false, 'default' => 'avatar.png', 'comment' => '头像'])
            ->addColumn('email', 'string', ['null' => true, 'comment' => '邮箱'])
            ->addColumn('address', 'string', ['null' => true, 'comment' => '地址'])
            ->addColumn('gender', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0, 'comment' => '性别'])
            ->addColumn('enable', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0, 'comment' => '是否启用'])
            ->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '逻辑删除'])
            ->create();
        // 预填充信息
        $salt = mt_rand(1000, 9999);
        $rows = [
            [
                'id' => 1,
                'nickname' => '默认管理员',
                'username' => 'admin',
                'password' => sha1(sha1('123456') . $salt),
                'salt' => $salt,
                'token' => null,
                'role_id' => 1,
                'avatar' => 'avatar.png',
                'email' => null,
                'address' => null,
                'gender' => 0,
                'enable' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]
        ];
        $table->insert($rows)->saveData();
    }
}
