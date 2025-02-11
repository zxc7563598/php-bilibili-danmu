<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyUserVipsAddEmail extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_vips');
        $table->addColumn('email', 'string', ['comment' => '邮件地址', 'null' => true, 'after' => 'name'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_user_vips');
        $table->removeColumn('email')
            ->save();
    }
}
