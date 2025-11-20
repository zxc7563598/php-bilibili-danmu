<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUpdateLogsVersion15 extends AbstractMigration
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
                "id" => 15,
                "title" => "功能更新",
                "description" => "版本v1.7.1",
                "version" => "v1.7.1",
                "content" => "签到功能新增「奖励类型」配置\n原本签到仅支持获取硬币，现在可选择奖励积分或硬币，更灵活适配各类运营需求\n\n优化用户积分/硬币变更记录展示\n调整数据表格的图标与视觉样式，使信息更直观、操作更顺畅\n\n---------------------------------------------\n\n更新方法：拉取最新代码，重新打包覆盖服务器文件。\n\n如果觉得功能还不错，欢迎顺手点个 Star，支持是继续优化的动力。\n如有问题欢迎联系我，我会尽力协助。\n\n祝你使用愉快！",
                "meta" => time(),
                "created_at" => time(),
                "updated_at" => time(),
                "deleted_at" => null
            ]
        ]);
        $tableAdd->saveData();
    }
}

