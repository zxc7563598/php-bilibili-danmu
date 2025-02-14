<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use support\Redis;

final class AddShopConfigEmailData extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 33, 'title' => 'enable-shop-mail', 'description' => '是否开启兑换通知', 'content' => '1', 'created_at' => time(), 'updated_at' => time()]
        ]);
        $tableAdd->saveData();
        Redis::del(config('app')['app_name'] . ':config');
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_shop_config` WHERE `config_id` in (33)");
    }
}
