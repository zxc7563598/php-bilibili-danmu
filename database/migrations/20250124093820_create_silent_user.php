<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateSilentUser extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_silent_user', ['id' => 'id', 'comment' => '用户模块 - 禁言用户列表']);
        $table->addColumn('black_id', 'string', ['comment' => '黑名单id', 'null' => false])
            ->addColumn('tuid', 'string', ['comment' => '禁言用户id', 'null' => false])
            ->addColumn('tname', 'string', ['comment' => '禁言用户名称', 'null' => false])
            ->addColumn('silent_minute', 'integer', ['comment' => '禁言截止时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('ransom_amount', 'integer', ['comment' => '解除禁言需要的电池', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
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
        $this->table('bl_silent_user')->drop()->save();
    }
}
