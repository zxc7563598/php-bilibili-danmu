<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyUserCheckInAddCurrencyType extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_user_check_in');
        $table->addColumn('currency_type', 'integer', ['comment' => '奖励类型，1=硬币，0=积分', 'null' => false, 'default' => 1, 'after' => 'guard_level', 'limit' => MysqlAdapter::INT_TINY])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('bl_user_check_in');
        $table->removeColumn('currency_type')
            ->save();
    }
}
