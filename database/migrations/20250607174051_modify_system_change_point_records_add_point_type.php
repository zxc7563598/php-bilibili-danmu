<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifySystemChangePointRecordsAddPointType extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_system_change_point_records');
        $table->addColumn('point_type', 'integer', ['comment' => '变更积分类型', 'null' => false, 'default' => 0, 'after' => 'source', 'limit' => MysqlAdapter::INT_TINY])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('bl_system_change_point_records');
        $table->removeColumn('point_type')
            ->save();
    }
}
