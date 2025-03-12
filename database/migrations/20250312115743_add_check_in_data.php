<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use support\Redis;

final class AddCheckInData extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 34, 'title' => 'check-in', 'description' => '是否开启签到', 'content' => '1', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 35, 'title' => 'check-in-keywords', 'description' => '签到词', 'content' => '#签到', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 36, 'title' => 'check-in-select', 'description' => '签到查询词', 'content' => '#查询', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 37, 'title' => 'check-in-success', 'description' => '签到成功回复', 'content' => "恭喜@name@签到成功\r\n@name@您已经签到@day@天啦\r\n签到成功", 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 38, 'title' => 'check-in-reply', 'description' => '签到查询回复', 'content' => "@name@已经连续签到@day@天啦\r\n已经签到@day@天了", 'created_at' => time(), 'updated_at' => time()]
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DELETE FROM `bl_shop_config` WHERE `config_id` in (34, 35, 36, 37, 38)");
    }
}
