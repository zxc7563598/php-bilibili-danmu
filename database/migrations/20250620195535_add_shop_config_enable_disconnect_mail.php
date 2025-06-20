<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddShopConfigEnableDisconnectMail extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 34, 'title' => 'enable-disconnect-mail', 'description' => '是否开启断开链接邮件通知', 'content' => '1', 'created_at' => time(), 'updated_at' => time()]
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_shop_config` WHERE `config_id` in (34)");
    }
}
