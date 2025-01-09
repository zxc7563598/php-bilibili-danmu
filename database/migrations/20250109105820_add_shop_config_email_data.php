<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddShopConfigEmailData extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 29, 'title' => 'enable-aggregate-mail', 'description' => '下播邮件', 'content' => '1', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 30, 'title' => 'email-address', 'description' => '邮箱', 'content' => '', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 31, 'title' => 'address-as', 'description' => '邮箱用户名', 'content' => '', 'created_at' => time(), 'updated_at' => time()]
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_shop_config` WHERE `config_id` in (29,30,31)");
    }
}
