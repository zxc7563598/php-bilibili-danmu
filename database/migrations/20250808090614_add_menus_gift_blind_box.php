<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMenusGiftBlindBox extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_menus');
        $tableAdd->insert([
            [
                "id" => 37,
                "code" => "GiftBlindBox",
                "enable" => 1,
                "show" => 1,
                "keep_alive" => 0,
                "layout" => "",
                "type" => "MENU",
                "parent_id" => 19,
                "name" => "盲盒信息",
                "icon" => "i-fe:gift",
                "path" => "/others/gift-blind-box",
                "component" => "/src/views/others/gift-blind-box/index.vue",
                "order" => 4,
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
        $this->execute("DELETE FROM `bl_menus` WHERE `id` in (37)");
    }
}
