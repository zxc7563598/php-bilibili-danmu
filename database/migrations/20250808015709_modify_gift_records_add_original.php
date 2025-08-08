<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyGiftRecordsAddOriginal extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_gift_records');
        $table->addColumn('original_price', 'string', ['comment' => '原始商品价格', 'null' => true, 'after' => 'total_price'])
            ->addColumn('original_gift_name', 'string', ['comment' => '原始商品名称', 'null' => true, 'after' => 'total_price'])
            ->addColumn('original', 'integer', ['comment' => '是否是原始商品', 'null' => true, 'default' => 1, 'after' => 'total_price', 'limit' => MysqlAdapter::INT_TINY])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('bl_gift_records');
        $table->removeColumn('original_price')
            ->removeColumn('original_gift_name')
            ->removeColumn('original')
            ->save();
    }
}
