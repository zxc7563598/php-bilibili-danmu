<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateGoods extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_goods', ['id' => 'goods_id', 'comment' => '商品模块 - 商品表']);
        $table->addColumn('name', 'string', ['comment' => '商品名称', 'null' => false])
            ->addColumn('amount', 'integer', ['comment' => '商品价格', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('sub_num', 'integer', ['comment' => '规格选择数量', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('cover_image', 'string', ['comment' => '封面图', 'null' => true])
            ->addColumn('carousel_images', 'text', ['comment' => '轮播图（多个）', 'null' => true])
            ->addColumn('details_images', 'text', ['comment' => '详情图（多个）', 'null' => true])
            ->addColumn('service_description_images', 'text', ['comment' => '服务说明图（多个）', 'null' => true])
            ->addColumn('status', 'integer', ['comment' => '状态', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('type', 'integer', ['comment' => '商品类型', 'default' => 1, 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('sort', 'integer', ['comment' => '排序，从小到大', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
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
        $this->table('bl_goods')->drop()->save();
    }
}
