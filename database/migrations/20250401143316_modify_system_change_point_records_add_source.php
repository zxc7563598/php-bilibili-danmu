<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifySystemChangePointRecordsAddSource extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_system_change_point_records');
        $table->addColumn('source', 'integer', ['comment' => '变更来源', 'null' => false, 'default' => 0, 'after' => 'type', 'limit' => MysqlAdapter::INT_TINY])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_system_change_point_records');
        $table->removeColumn('source')
            ->save();
    }
}
