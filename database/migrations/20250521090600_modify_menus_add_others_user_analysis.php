<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class ModifyMenusAddOthersUserAnalysis extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_menus');
        $tableAdd->insert([
            [
                "id" => 36,
                "code" => "OthersUserAnalysis",
                "enable" => 1,
                "show" => 1,
                "keep_alive" => 0,
                "layout" => "",
                "type" => "MENU",
                "parent_id" => 19,
                "name" => "用户分析",
                "icon" => "i-fe:trello",
                "path" => "/others/user-analysis",
                "component" => "/src/views/others/user-analysis/index.vue",
                "order" => 2,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ]
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_menus` WHERE `id` in (36)");
    }
}
