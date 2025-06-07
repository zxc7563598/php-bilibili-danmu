<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyUserVipsAddRewardPoints extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_user_vips');
        $table->addColumn('reward_point', 'integer', ['comment' => '签到积分', 'null' => false, 'default' => 0, 'after' => 'point', 'limit' => MysqlAdapter::INT_MEDIUM])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('bl_user_vips');
        $table->removeColumn('reward_point')
            ->save();
    }
}
