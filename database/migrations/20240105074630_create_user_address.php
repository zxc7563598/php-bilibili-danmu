<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateUserAddress extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_address', ['id' => 'id', 'comment' => '用户模块 - 用户收货地址表']);
        $table->addColumn('user_id', 'integer', ['comment' => '用户id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('name', 'string', ['comment' => '姓名', 'null' => false])
            ->addColumn('phone', 'string', ['comment' => '电话', 'null' => false])
            ->addColumn('province', 'string', ['comment' => '省', 'null' => false])
            ->addColumn('city', 'string', ['comment' => '市', 'null' => false])
            ->addColumn('county', 'string', ['comment' => '区', 'null' => false])
            ->addColumn('detail', 'string', ['comment' => '详细地址', 'null' => false])
            ->addColumn('selected', 'integer', ['comment' => '是否选择', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
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
        $this->table('bl_user_address')->drop()->save();
    }
}
