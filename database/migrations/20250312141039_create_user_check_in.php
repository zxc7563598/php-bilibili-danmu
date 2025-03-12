<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateUserCheckIn extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_check_in', ['id' => 'log_id', 'comment' => '用户模块 - 用户签到表']);
        $table->addColumn('uid', 'string', ['comment' => 'uid', 'null' => false])
            ->addColumn('name', 'string', ['comment' => '名称', 'null' => false])
            ->addColumn('ruid', 'string', ['comment' => '用户携带的牌子归属主播的uid', 'null' => true])
            ->addColumn('guard_level', 'string', ['comment' => '大航海类型，0=普通用户，1=总督，2=提督，3=舰长', 'null' => true])
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
        $this->table('bl_user_check_in')->drop()->save();
    }
}
