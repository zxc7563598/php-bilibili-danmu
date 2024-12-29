<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateUserVips extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_vips', ['id' => 'user_id', 'comment' => '用户模块 - 舰长表']);
        $table->addColumn('uid', 'string', ['comment' => 'uid', 'null' => false])
            ->addColumn('name', 'string', ['comment' => '名称', 'null' => false])
            ->addColumn('password', 'string', ['comment' => '密码', 'null' => true])
            ->addColumn('salt', 'integer', ['comment' => '扰乱码', 'null' => true, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('token', 'string', ['comment' => '登陆凭证', 'null' => true])
            ->addColumn('vip_type', 'integer', ['comment' => 'vip类型', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('last_vip_at', 'integer', ['comment' => '最后一次vip开通时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('end_vip_at', 'integer', ['comment' => '最后一次vip到期时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('point', 'integer', ['comment' => '积分', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('sign_image', 'string', ['comment' => '签名图片', 'null' => true])
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
        $this->table('bl_user_vips')->drop()->save();
    }
}
