<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class CreateAdminUpdateLogs extends AbstractMigration
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
        $table = $this->table('bl_admin_update_logs', ['id' => 'id', 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '后台模块 - 后台更新日志表']);
        // 添加字段
        $table->addColumn('version', 'string', ['null' => false, 'comment' => '版本号'])
            ->addColumn('title', 'string', ['null' => false, 'comment' => '标题'])
            ->addColumn('description', 'string', ['null' => false, 'comment' => '副标题'])
            ->addColumn('content', 'text', ['null' => false, 'comment' => '正文内容'])
            ->addColumn('meta', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'comment' => '发布时间'])
            ->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '逻辑删除'])
            ->create();
    }
}
