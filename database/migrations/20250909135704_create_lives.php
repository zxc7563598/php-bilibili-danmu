<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateLives extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_lives', ['id' => 'live_id', 'comment' => '直播模块 - 直播信息表']);
        $table->addColumn('live_key', 'string', ['comment' => 'live_key', 'null' => false])
            ->addColumn('end_time', 'integer', ['comment' => '下播时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('danmu_num', 'integer', ['comment' => '弹幕数量', 'null' => true, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('danmu_path', 'string', ['comment' => '弹幕文件位置', 'null' => true])
            ->addColumn('gift_num', 'integer', ['comment' => '礼物金额', 'null' => true, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('gift_path', 'string', ['comment' => '礼物文件位置', 'null' => true])
            ->addColumn('created_at', 'integer', ['comment' => '创建时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('updated_at', 'integer', ['comment' => '更新时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('deleted_at', 'integer', ['comment' => '逻辑删除', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->create();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('bl_lives')->drop()->save();
    }
}
