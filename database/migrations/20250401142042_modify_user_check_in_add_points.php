<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyUserCheckInAddPoints extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_check_in');
        $table->addColumn('points', 'integer', ['comment' => '签到赠送积分', 'null' => false, 'default' => 0, 'after' => 'guard_level', 'limit' => MysqlAdapter::INT_MEDIUM])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_user_check_in');
        $table->removeColumn('points')
            ->save();
    }
}
