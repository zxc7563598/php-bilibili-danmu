<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUpdateLogsVersion12 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $tableAdd = $this->table('bl_admin_update_logs');
        $tableAdd->insert([
            [
                "id" => 12,
                "title" => "功能更新",
                "description" => "版本v1.6.2",
                "version" => "v1.6.2",
                "content" => "新增 PK 播报「对方舰长数量」显示\n后台同步更新以支持新配置，如不需要该功能，可跳过本次更新\n\n---------------------------------------------\n\n更新方法：拉取最新代码，重新打包覆盖服务器文件。\n\n如果觉得功能还不错，欢迎顺手点个 Star，支持是继续优化的动力。\n如有问题欢迎联系我，我会尽力协助。\n\n祝你使用愉快！",
                "meta" => time(),
                "created_at" => time(),
                "updated_at" => time(),
                "deleted_at" => null
            ]
        ]);
        $tableAdd->saveData();
    }
}
