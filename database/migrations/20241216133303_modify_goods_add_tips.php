<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyGoodsAddTips extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_goods');
        $table->addColumn('tips', 'string', ['comment' => '购买说明', 'null' => true, 'after' => 'sub_num'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_goods');
        $table->removeColumn('tips')
            ->save();
    }
}
