<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class CreateDanmuLogs extends AbstractMigration
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
        // 创建表
        $table = $this->table('bl_danmu_logs', ['id' => 'id', 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '直播模块 - 弹幕记录']);
        // 添加字段
        $table->addColumn('uid', 'string', ['null' => false, 'comment' => '用户uid'])
            ->addColumn('uname', 'string', ['null' => false, 'comment' => '用户名称'])
            ->addColumn('msg', 'string', ['null' => false, 'comment' => '发送弹幕'])
            ->addColumn('live', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => false, 'comment' => '是否在直播中发送'])
            ->addColumn('badge_uid', 'string', ['null' => true, 'comment' => '牌子主播uid'])
            ->addColumn('badge_uname', 'string', ['null' => true, 'comment' => '牌子主播名称'])
            ->addColumn('badge_room_id', 'string', ['null' => true, 'comment' => '牌子主播直播间房间号'])
            ->addColumn('badge_name', 'string', ['null' => true, 'comment' => '牌子名称'])
            ->addColumn('badge_level', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => true, 'comment' => '牌子等级'])
            ->addColumn('badge_type', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => true, 'comment' => '牌子航海类型'])
            ->addColumn('send_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '弹幕发送时间'])
            ->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '逻辑删除'])
            ->create();
    }
}
