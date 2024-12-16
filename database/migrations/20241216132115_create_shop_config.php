<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateShopConfig extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_shop_config', ['id' => 'config_id', 'comment' => '配置模块 - 信息配置表']);
        $table->addColumn('goods_id', 'integer', ['comment' => '商品id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('title', 'string', ['comment' => '标题', 'null' => false])
            ->addColumn('description', 'string', ['comment' => '说明', 'null' => true])
            ->addColumn('content', 'text', ['comment' => '内容', 'null' => false])
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
        $this->table('bl_shop_config')->drop()->save();
    }
}
