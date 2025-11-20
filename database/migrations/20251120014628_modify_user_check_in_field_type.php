<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyUserCheckInFieldType extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_user_check_in');
        $table->renameColumn('points', 'currency')
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('bl_user_check_in');
        $table->renameColumn('currency', 'points')
            ->save();
    }
}
