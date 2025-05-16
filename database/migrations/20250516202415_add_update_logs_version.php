<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use support\Redis;

final class AddUpdateLogsVersion extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_admin_update_logs');
        $tableAdd->insert([
            [
                "id" => 1,
                "title" => "你好呀",
                "description" => "版本v1.0.0",
                "version" => "v1.0.0",
                "content" => "这是第一个正式版本，当前规划内的功能已基本完成。\n如果你觉得项目还不错，欢迎顺手点个 Star，也许我会因为这个高兴一整天。\n\n其他没太多需要说明的内容。\n如有问题欢迎联系，获取支持的方式并不复杂。\n\n祝你使用顺利。",
                "meta" => time(),
                "created_at" => time(),
                "updated_at" => time(),
                "deleted_at" => null
            ]
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_admin_update_logs` WHERE `id` in (1)");
    }
}
