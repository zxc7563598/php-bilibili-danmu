<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddShopConfigUserLoginPasswordData extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 28, 'title' => 'user-login-password', 'description' => '用户是否需要密码登录', 'content' => '1', 'created_at' => time(), 'updated_at' => time()],
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_shop_config` WHERE `config_id` = 28");
    }
}
