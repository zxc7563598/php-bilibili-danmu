<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyRequestLogChangeJson extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_request_log');
        $table->changeColumn('json', 'text', ['comment' => 'json内容', 'null' => false])
            ->update();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_request_log');
        $table->changeColumn('json', 'string', ['comment' => 'json内容', 'null' => false])
            ->update();
    }
}
