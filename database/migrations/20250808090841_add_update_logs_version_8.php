<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUpdateLogsVersion8 extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_admin_update_logs');
        $tableAdd->insert([
            [
                "id" => 8,
                "title" => "新增功能",
                "description" => "版本v1.4.0",
                "version" => "v1.4.0",
                "content" => "新增「盲盒信息」页面\n可以按用户/时间阶段查询盲盒收益信息\n\n---------------------------------------------\n\n更新方法：拉取最新代码，重新打包覆盖服务器文件。\n\n如果觉得功能还不错，欢迎顺手点个 Star，支持是继续优化的动力。\n如有问题欢迎联系我，我会尽力协助。\n\n祝你使用愉快！",
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
        $this->execute("DELETE FROM `bl_admin_update_logs` WHERE `id` in (8)");
    }
}
