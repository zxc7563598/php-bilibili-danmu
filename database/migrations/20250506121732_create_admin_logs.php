<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class CreateAdminLogs extends AbstractMigration
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
        $table = $this->table('bl_admin_logs', ['id' => 'id', 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '管理员登录记录表']);
        // 添加字段
        $table->addColumn('admin_id', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'comment' => '管理员id'])
            ->addColumn('ip', 'string', ['null' => false, 'comment' => 'IP地址'])
            ->addColumn('ip_address', 'string', ['null' => true, 'comment' => 'IP对应地址'])
            ->addColumn('browser_name', 'string', ['null' => true, 'comment' => '浏览器名称'])
            ->addColumn('browser_version', 'string', ['null' => true, 'comment' => '浏览器版本'])
            ->addColumn('engine_name', 'string', ['null' => true, 'comment' => '获取引擎名称'])
            ->addColumn('os_name', 'string', ['null' => true, 'comment' => '操作系统名称'])
            ->addColumn('os_version', 'string', ['null' => true, 'comment' => '操作系统版本'])
            ->addColumn('platform_type', 'string', ['null' => true, 'comment' => '获取平台类型'])
            ->addColumn('ua', 'string', ['null' => true, 'comment' => '提取ua'])
            ->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '逻辑删除'])
            ->create();
    }
}
