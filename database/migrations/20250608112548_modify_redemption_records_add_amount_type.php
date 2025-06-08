<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyRedemptionRecordsAddAmountType extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_redemption_records');
        $table->addColumn('amount_type', 'integer', ['comment' => '商品价格类型', 'null' => false, 'default' => 0, 'after' => 'sub_id', 'limit' => MysqlAdapter::INT_TINY])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('bl_redemption_records');
        $table->removeColumn('amount_type')
            ->save();
    }
}
