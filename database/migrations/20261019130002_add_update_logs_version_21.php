<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUpdateLogsVersion21 extends AbstractMigration
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
                "id" => 21,
                "title" => "功能更新",
                "description" => "版本v1.8.4",
                "version" => "v1.8.4",
                "content" => "自动回复新增用户与直播间的本日、本周、本月、总计盲盒数量变量\n可与已有的盲盒盈利变量搭配使用，在回复文案中展示开盒数量\n\n下播邮件增加本场盲盒盈亏数据\n邮件中会给出本场盲盒的总盈亏，以及每个盲盒的数量与盈亏明细\n\n---------------------------------------------\n\n更新方法：拉取最新代码，重新打包覆盖服务器文件。\n\n如果觉得功能还不错，欢迎顺手点个 Star，支持是继续优化的动力。\n如有问题欢迎联系我，我会尽力协助。\n\n祝你使用愉快！",
                "meta" => time(),
                "created_at" => time(),
                "updated_at" => time(),
                "deleted_at" => null
            ]
        ]);
        $tableAdd->saveData();
    }
}
