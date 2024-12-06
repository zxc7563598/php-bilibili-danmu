<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class CreateBlUserVipsTable extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_vips', ['id' => false, 'primary_key' => 'user_id', 'comment' => '用户模块 - 舰长表']);
        $table->addColumn('user_id', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'identity' => true])
            ->addColumn('uid', 'string', ['limit' => 255, 'null' => false, 'comment' => 'uid'])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false, 'comment' => '名称'])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => true, 'comment' => '密码'])
            ->addColumn('salt', 'integer', ['limit' => MysqlAdapter::INT_MEDIUM, 'null' => true, 'comment' => '扰乱码'])
            ->addColumn('token', 'string', ['limit' => 255, 'null' => true, 'comment' => '登陆凭证'])
            ->addColumn('vip_type', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => false, 'comment' => 'vip类型'])
            ->addColumn('last_vip_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '上次vip开通时间'])
            ->addColumn('end_vip_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '到期时间'])
            ->addColumn('point', 'integer', ['limit' => MysqlAdapter::INT_MEDIUM, 'null' => false, 'comment' => '积分'])
            ->addColumn('sign_image', 'string', ['limit' => 255, 'null' => true, 'comment' => '签名图片'])
            ->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '逻辑删除'])
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
