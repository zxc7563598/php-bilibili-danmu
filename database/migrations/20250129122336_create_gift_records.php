<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateGiftRecords extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_gift_records', ['id' => 'records_id', 'comment' => '用户模块 - 礼物赠送记录表']);
        $table
            ->addColumn('uid', 'string', ['comment' => '用户uid', 'null' => false])
            ->addColumn('uname', 'string', ['comment' => '用户名称', 'null' => false])
            ->addColumn('gift_id', 'string', ['comment' => '礼物ID', 'null' => false])
            ->addColumn('gift_name', 'string', ['comment' => '礼物名称', 'null' => false])
            ->addColumn('price', 'decimal', ['comment' => '价格（人民币）', 'default' => 0, 'null' => false, 'precision' => 10, 'scale' => 2])
            ->addColumn('num', 'integer', ['comment' => '数量', 'default' => 0, 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('total_price', 'decimal', ['comment' => '总价（人民币）', 'default' => 0, 'null' => false, 'precision' => 10, 'scale' => 2])
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
        $this->table('bl_gift_records')->drop()->save();
    }
}
