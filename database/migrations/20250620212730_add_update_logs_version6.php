<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use support\Redis;

final class AddUpdateLogsVersion6 extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_admin_update_logs');
        $tableAdd->insert([
            [
                "id" => 6,
                "title" => "增加连接断开邮件通知",
                "description" => "版本v1.3.0",
                "version" => "v1.3.0",
                "content" => "「配置管理 - 商城配置」中新增是否开启断开链接邮件\n\n用于在机器人与直播间意外断开连接时进行邮件通知\n\n通常建议开启该配置以便第一时间接收异常信息\n\n---------------------------------------------\n\n更新方法：拉取最新代码，重新打包覆盖服务器文件。\n\n如果觉得功能还不错，欢迎顺手点个 Star，支持是继续优化的动力。\n如有问题欢迎联系我，我会尽力协助。\n\n祝你使用愉快！",
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
        $this->execute("DELETE FROM `bl_admin_update_logs` WHERE `id` in (6)");
    }
}
