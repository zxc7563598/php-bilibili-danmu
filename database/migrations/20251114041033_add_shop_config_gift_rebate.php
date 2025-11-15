<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddShopConfigGiftRebate extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 38, 'title' => 'rebate-enable', 'description' => '是否开启礼物返利', 'content' => '0', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 39, 'title' => 'rebate-proportion', 'description' => '返利比例', 'content' => '0', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 40, 'title' => 'min-rebate-point', 'description' => '最低返利积分', 'content' => '0', 'created_at' => time(), 'updated_at' => time()],
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_shop_config` WHERE `config_id` in (38,39,40)");
    }
}
