<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddShopConfigProtocolsEnable extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 37, 'title' => 'protocols-enable', 'description' => '是否开启协议', 'content' => '1', 'created_at' => time(), 'updated_at' => time()]
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_shop_config` WHERE `config_id` in (37)");
    }
}
