<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyUserVipsAddAvatar extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_vips');
        $table->addColumn('avatar', 'string', ['comment' => '头像', 'null' => true, 'after' => 'name'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_user_vips');
        $table->removeColumn('avatar')
            ->save();
    }
}
