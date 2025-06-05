<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use support\Redis;

final class AddUpdateLogsVersion3 extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_admin_update_logs');
        $tableAdd->insert([
            [
                "id" => 3,
                "title" => "签到成功增加关键词",
                "description" => "版本v1.1.1",
                "version" => "v1.1.1",
                "content" => "✅ 增加关于签到成功可回复信息的内容\n\n✅ 修复B站因wbi密钥导致的连不上房间的风控的问题\n\n若机器人失效,退出登录后重新登录即可\n\n---------------------------------------------\n\n更新方法：拉取最新代码，重新打包覆盖服务器文件。\n\n如果觉得功能还不错，欢迎顺手点个 Star，支持是继续优化的动力。\n如有问题欢迎联系我，我会尽力协助。\n\n祝你使用愉快！",
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
        $this->execute("DELETE FROM `bl_admin_update_logs` WHERE `id` in (3)");
    }
}
