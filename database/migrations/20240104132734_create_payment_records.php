<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreatePaymentRecords extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_payment_records', ['id' => 'records_id', 'comment' => '用户模块 - 付费记录（舰长开通记录）']);
        $table->addColumn('user_id', 'integer', ['comment' => '舰长id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('vip_type', 'integer', ['comment' => 'vip类型', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('amount', 'integer', ['comment' => '消费金额（分）', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('point', 'integer', ['comment' => '增加积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('pre_point', 'integer', ['comment' => '变更前积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('after_point', 'integer', ['comment' => '变更后积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('payment_at', 'integer', ['comment' => '上舰时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
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
        $this->table('bl_payment_records')->drop()->save();
    }
}
