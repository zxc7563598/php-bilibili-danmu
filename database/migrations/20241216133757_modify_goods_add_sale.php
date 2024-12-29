<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyGoodsAddSale extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_goods');
        $table->addColumn('sale_increase', 'integer', ['comment' => '每次销售递增', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM, 'default' => 1, 'after' => 'sort'])
            ->addColumn('sale_num', 'integer', ['comment' => '销售数量', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM, 'default' => 0, 'after' => 'sort'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_goods');
        $table->removeColumn('sale_increase')
            ->removeColumn('sale_num')
            ->save();
    }
}
