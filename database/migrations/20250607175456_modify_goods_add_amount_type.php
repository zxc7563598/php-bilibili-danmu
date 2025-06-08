<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyGoodsAddAmountType extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_goods');
        $table->addColumn('amount_type', 'integer', ['comment' => '商品价格类型', 'null' => false, 'default' => 0, 'after' => 'amount', 'limit' => MysqlAdapter::INT_TINY])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('bl_goods');
        $table->removeColumn('amount_type')
            ->save();
    }
}
