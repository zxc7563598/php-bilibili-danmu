<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyUserVipsAddCheck extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_vips');
        $table->addColumn('total_check_in', 'integer', ['comment' => '累计签到天数', 'null' => false, 'default' => 0, 'after' => 'point', 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('serial_check_in', 'integer', ['comment' => '连续签到天数', 'null' => false, 'default' => 0, 'after' => 'point', 'limit' => MysqlAdapter::INT_MEDIUM])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_user_vips');
        $table->removeColumn('total_check_in')
            ->removeColumn('serial_check_in')
            ->save();
    }
}
