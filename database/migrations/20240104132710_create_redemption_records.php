<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateRedemptionRecords extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_redemption_records', ['id' => 'records_id', 'comment' => '用户模块 - 商品兑换记录']);
        $table->addColumn('user_id', 'integer', ['comment' => '舰长id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('goods_id', 'integer', ['comment' => '商品id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('sub_id', 'string', ['comment' => '子集id', 'null' => false])
            ->addColumn('point', 'integer', ['comment' => '消耗积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('pre_point', 'integer', ['comment' => '变更前积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('after_point', 'integer', ['comment' => '变更后积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('shipping_address', 'string', ['comment' => '收货地址', 'null' => true])
            ->addColumn('shipping_name', 'string', ['comment' => '收货人', 'null' => true])
            ->addColumn('shipping_phone', 'string', ['comment' => '收货手机号', 'null' => true])
            ->addColumn('status', 'integer', ['comment' => '状态', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
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
        $this->table('bl_redemption_records')->drop()->save();
    }
}
