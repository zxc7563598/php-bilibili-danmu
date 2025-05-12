<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class CreateMenus extends AbstractMigration
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
        $table = $this->table('bl_menus', ['id' => 'id', 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '菜单表']);
        // 添加字段
        $table->addColumn('code', 'string', ['null' => false, 'comment' => '编码'])
            ->addColumn('enable', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0, 'comment' => '是否启用'])
            ->addColumn('show', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0, 'comment' => '显示状态'])
            ->addColumn('keep_alive', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0, 'comment' => '是否KeepAlive'])
            ->addColumn('layout', 'string', ['null' => false, 'default' => '', 'comment' => 'layout'])
            ->addColumn('type', 'string', ['null' => false, 'default' => '', 'comment' => '类型'])
            ->addColumn('parent_id', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'default' => 0, 'comment' => '父级ID'])
            ->addColumn('name', 'string', ['null' => false, 'comment' => '名称'])
            ->addColumn('icon', 'string', ['null' => false, 'comment' => '菜单图标'])
            ->addColumn('path', 'string', ['null' => false, 'comment' => '路由地址'])
            ->addColumn('component', 'string', ['null' => false, 'comment' => '组件路径'])
            ->addColumn('order', 'integer', ['limit' => MysqlAdapter::INT_MEDIUM, 'null' => false, 'comment' => '排序'])
            ->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => false, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_BIG, 'null' => true, 'comment' => '逻辑删除'])
            ->create();
        // 预填充信息
        $rows = [
            [
                'id' => 1,
                'code' => 'SysMgt',
                'enable' => 1,
                'show' => 1,
                'keep_alive' => 0,
                'layout' => '',
                'type' => 'MENU',
                'parent_id' => 0,
                'name' => '系统管理',
                'icon' => 'i-fe:grid',
                'path' => '',
                'component' => '',
                'order' => 98,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 2,
                'code' => 'MenuMgt',
                'enable' => 1,
                'show' => 1,
                'keep_alive' => 0,
                'layout' => '',
                'type' => 'MENU',
                'parent_id' => 1,
                'name' => '菜单管理',
                'icon' => 'i-fe:list',
                'path' => '/pms/resource',
                'component' => '/src/views/pms/resource/index.vue',
                'order' => 1,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 3,
                'code' => 'RoleMgt',
                'enable' => 1,
                'show' => 1,
                'keep_alive' => 0,
                'layout' => '',
                'type' => 'MENU',
                'parent_id' => 1,
                'name' => '角色管理',
                'icon' => 'i-fe:user-check',
                'path' => '/pms/role',
                'component' => '/src/views/pms/role/index.vue',
                'order' => 2,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 4,
                'code' => 'UserMgt',
                'enable' => 1,
                'show' => 1,
                'keep_alive' => 1,
                'layout' => '',
                'type' => 'MENU',
                'parent_id' => 1,
                'name' => '用户管理',
                'icon' => 'i-fe:user',
                'path' => '/pms/user',
                'component' => '/src/views/pms/user/index.vue',
                'order' => 3,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 5,
                'code' => 'RoleUser',
                'enable' => 1,
                'show' => 0,
                'keep_alive' => 0,
                'layout' => 'full',
                'type' => 'MENU',
                'parent_id' => 3,
                'name' => '分配用户',
                'icon' => 'i-fe:user-plus',
                'path' => '/pms/role/user/:roleId',
                'component' => '/src/views/pms/role/role-user.vue',
                'order' => 1,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 6,
                'code' => 'AddRole',
                'enable' => 1,
                'show' => 1,
                'keep_alive' => 0,
                'layout' => '',
                'type' => 'BUTTON',
                'parent_id' => 3,
                'name' => '新增角色',
                'icon' => '',
                'path' => '',
                'component' => '',
                'order' => 0,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 7,
                'code' => 'AddUser',
                'enable' => 1,
                'show' => 1,
                'keep_alive' => 0,
                'layout' => '',
                'type' => 'BUTTON',
                'parent_id' => 4,
                'name' => '添加用户',
                'icon' => '',
                'path' => '',
                'component' => '',
                'order' => 0,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 8,
                'code' => 'UserProfile',
                'enable' => 1,
                'show' => 0,
                'keep_alive' => 0,
                'layout' => '',
                'type' => 'MENU',
                'parent_id' => 0,
                'name' => '个人资料',
                'icon' => 'i-fe:user',
                'path' => '/profile',
                'component' => '/src/views/profile/index.vue',
                'order' => 99,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 9,
                'code' => 'Home',
                'enable' => 1,
                'show' => 1,
                'keep_alive' => 0,
                'layout' => '',
                'type' => 'MENU',
                'parent_id' => 0,
                'name' => '资产大盘',
                'icon' => 'i-fe:home',
                'path' => '/',
                'component' => '/src/views/home/index.vue',
                'order' => 0,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
            [
                'id' => 10,
                'code' => 'iFrame',
                'enable' => 1,
                'show' => 1,
                'keep_alive' => 0,
                'layout' => '',
                'type' => 'MENU',
                'parent_id' => 0,
                'name' => '外部链接',
                'icon' => 'i-fe:insert-link',
                'path' => 'https://hejunjie.life',
                'component' => '',
                'order' => 0,
                'created_at' => time(),
                'updated_at' => time(),
                'deleted_at' => NULL
            ],
        ];
        $table->insert($rows)->saveData();
    }
}
