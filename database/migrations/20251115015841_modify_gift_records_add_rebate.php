<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyGiftRecordsAddRebate extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_gift_records');
        $table->addColumn('rebate_point', 'integer', ['comment' => '返利积分', 'null' => false, 'default' => 0, 'after' => 'original_price', 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('min_rebate_point', 'string', ['comment' => '最低返利积分', 'null' => false, 'default' => '0', 'after' => 'original_price'])
            ->addColumn('rebate_proportion', 'string', ['comment' => '返利比例', 'null' => false, 'default' => '0', 'after' => 'original_price'])
            ->addColumn('rebate_enable', 'integer', ['comment' => '是否开启返利（0-否，1-是）', 'null' => false, 'default' => 0, 'after' => 'original_price', 'limit' => MysqlAdapter::INT_TINY])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('bl_gift_records');
        $table->removeColumn('rebate_point')
            ->removeColumn('min_rebate_point')
            ->removeColumn('rebate_proportion')
            ->removeColumn('rebate_enable')
            ->save();
    }
}
