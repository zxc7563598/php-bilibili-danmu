<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddShopConfigPointsExpired extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 35, 'title' => 'points-expire-mode', 'description' => '积分过期模式', 'content' => '0', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 36, 'title' => 'points-expire-days', 'description' => '积分过期天数', 'content' => '0', 'created_at' => time(), 'updated_at' => time()]
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_shop_config` WHERE `config_id` in (35,36)");
    }
}
