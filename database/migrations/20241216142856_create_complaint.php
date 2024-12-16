<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateShopConfig extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_complaint', ['id' => 'complaint_id', 'comment' => '用户模块 - 意见反馈表']);
        $table->addColumn('user_id', 'integer', ['comment' => '舰长id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('title', 'string', ['comment' => '标题', 'null' => false])
            ->addColumn('content', 'text', ['comment' => '内容', 'null' => false])
            ->addColumn('read', 'integer', ['comment' => '是否已读', 'null' => false, 'limit' => MysqlAdapter::INT_TINY, 'default' => 0])
            ->addColumn('created_at', 'integer', ['comment' => '创建时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('updated_at', 'integer', ['comment' => '更新时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('deleted_at', 'integer', ['comment' => '逻辑删除', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->create();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('bl_complaint')->drop()->save();
    }
}
