<?php

declare(strict_types=1);

use Carbon\Carbon;
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
        $table->addColumn('title', 'string', ['comment' => '标题', 'null' => false])
            ->addColumn('description', 'string', ['comment' => '说明', 'null' => true])
            ->addColumn('content', 'text', ['comment' => '内容', 'null' => true])
            ->addColumn('created_at', 'integer', ['comment' => '创建时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('updated_at', 'integer', ['comment' => '更新时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('deleted_at', 'integer', ['comment' => '逻辑删除', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->create();
        // 添加数据
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            [
                'config_id' => 1,
                'title' => 'login-background-image',
                'description' => '登录页面背景图',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 2,
                'title' => 'personal-background-image',
                'description' => '个人中心背景图',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 3,
                'title' => 'theme-color',
                'description' => '主题色',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 4,
                'title' => 'protocols-surname',
                'description' => '协议人姓名',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 5,
                'title' => 'protocols-uid',
                'description' => '协议人UID',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 6,
                'title' => 'protocols-name',
                'description' => '协议名称',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 7,
                'title' => 'protocols-signature',
                'description' => '协议人签名',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 8,
                'title' => 'protocols-content',
                'description' => '协议内容',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 9,
                'title' => 'virtual-gift-order-successful-icon',
                'description' => '虚拟礼物下单成功图标',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 10,
                'title' => 'virtual-gift-order-successful-title',
                'description' => '虚拟礼物下单成功标题',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 11,
                'title' => 'virtual-gift-order-successful-content',
                'description' => '虚拟礼物下单成功内容',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 12,
                'title' => 'virtual-gift-order-successful-button',
                'description' => '虚拟礼物下单成功按钮',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 13,
                'title' => 'realism-gift-order-successful-icon',
                'description' => '实体礼物下单成功图标',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 14,
                'title' => 'realism-gift-order-successful-title',
                'description' => '实体礼物下单成功标题',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 15,
                'title' => 'realism-gift-order-successful-content',
                'description' => '实体礼物下单成功内容',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 16,
                'title' => 'realism-gift-order-successful-button',
                'description' => '实体礼物下单成功按钮',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 17,
                'title' => 'tribute-gift-order-successful-icon',
                'description' => '贡品下单成功图标',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 18,
                'title' => 'tribute-gift-order-successful-title',
                'description' => '贡品下单成功标题',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 19,
                'title' => 'tribute-gift-order-successful-content',
                'description' => '贡品下单成功内容',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 20,
                'title' => 'tribute-gift-order-successful-button',
                'description' => '贡品下单成功按钮',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 21,
                'title' => 'tribute-gift-order-successful-rankings',
                'description' => '贡品下单成功是否开启排名',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'config_id' => 22,
                'title' => 'tribute-gift-order-successful-rankingslist',
                'description' => '贡品下单成功排名列表',
                'content' => null,
                'created_at' => time(),
                'updated_at' => time()
            ]
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('bl_shop_config')->drop()->save();
    }
}
