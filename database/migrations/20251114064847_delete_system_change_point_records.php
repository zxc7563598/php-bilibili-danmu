<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class DeleteSystemChangePointRecords extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->table('bl_system_change_point_records')->drop()->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_system_change_point_records', ['id' => 'records_id', 'comment' => '用户模块 - 系统变更积分记录']);
        $table->addColumn('user_id', 'integer', ['comment' => '舰长id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('type', 'integer', ['comment' => '变更类型', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('source', 'integer', ['comment' => '变更来源', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('point_type', 'integer', ['comment' => '变更积分类型', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('point', 'integer', ['comment' => '积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('pre_point', 'integer', ['comment' => '变更前积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('after_point', 'integer', ['comment' => '变更后积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('created_at', 'integer', ['comment' => '创建时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('updated_at', 'integer', ['comment' => '更新时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('deleted_at', 'integer', ['comment' => '逻辑删除', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->create();
    }
}
