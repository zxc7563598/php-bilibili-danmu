<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateGoodSubs extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_good_subs', ['id' => 'sub_id', 'comment' => '商品模块 - 商品分类表']);
        $table->addColumn('goods_id', 'integer', ['comment' => '商品id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('name', 'string', ['comment' => '规格名称', 'null' => false])
            ->addColumn('cover_image', 'string', ['comment' => '封面图', 'null' => true])
            ->addColumn('status', 'integer', ['comment' => '状态', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
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
        $this->table('bl_good_subs')->drop()->save();
    }
}
