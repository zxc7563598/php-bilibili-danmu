<?php

namespace app\controller\admin\framework;

use app\model\AdminRoles;
use app\model\Menus;
use app\model\RolePermissions;
use app\model\Roles;
use Carbon\Exceptions\InvalidTimeZoneException;
use support\Request;
use Webman\Openai\Chat;
use Workerman\Protocols\Http\Chunk;
use DeepSeek\DeepSeekClient;
use resource\enums\MenusEnums;
use support\Response;

class AdminPermissionController
{

    /**
     * 权限管理-获取全部菜单
     * 
     * @return Response
     */
    public function menu(Request $request): Response
    {
        // 获取菜单信息
        $menus = Menus::select([
            'id' => 'id',
            'code' => 'code',
            'enable' => 'enable',
            'show' => 'show',
            'keep_alive' => 'keep_alive as keepAlive',
            'layout' => 'layout',
            'type' => 'type',
            'parent_id' => 'parent_id as parentId',
            'name' => 'name',
            'icon' => 'icon',
            'path' => 'path',
            'component' => 'component',
            'order' => 'order'
        ])->get()->toArray();
        // 处理数据
        foreach ($menus as &$_menus) {
            $_menus['redirect'] = null;
            $_menus['method'] = null;
            $_menus['description'] = null;
            $_menus['enable'] = $_menus['enable'] != MenusEnums\Enable::Disable->value;
            $_menus['show'] = $_menus['show'] != MenusEnums\Show::Hide->value;
            $_menus['keepAlive'] = $_menus['keepAlive'] != MenusEnums\KeepAlive::No->value;
        }
        $data = buildTree($menus);
        // 返回数据
        return success($request, $data);
    }

    /**
     * 权限管理-验证菜单是否存在
     * 
     * @param string $path 菜单路径
     * 
     * @return Response 
     */
    public function validateMenu(Request $request): Response
    {
        $path = $request->data['path'];
        // 获取菜单信息
        $menus = Menus::where('path', $path)->count();
        // 返回数据
        return success($request, [
            'has' => ($menus > 0)
        ]);
    }

    /**
     * 权限管理-获取菜单下的按钮
     * 
     * @param integer $parent_id 菜单id
     * 
     * @return Response
     */
    public function buttons(Request $request): Response
    {
        $parent_id = $request->data['parent_id'];
        // 获取数据
        $menus = Menus::where('parent_id', $parent_id)->where('type', MenusEnums\Type::Button->value)->select([
            'id' => 'id',
            'code' => 'code',
            'enable' => 'enable',
            'show' => 'show',
            'keep_alive' => 'keep_alive as keepAlive',
            'layout' => 'layout',
            'type' => 'type',
            'parent_id' => 'parent_id as parentId',
            'name' => 'name',
            'icon' => 'icon',
            'path' => 'path',
            'component' => 'component',
            'order' => 'order'
        ])->get()->toArray();
        // 处理数据
        foreach ($menus as &$_menus) {
            $_menus['redirect'] = null;
            $_menus['method'] = null;
            $_menus['description'] = null;
            $_menus['enable'] = $_menus['enable'] != MenusEnums\Enable::Disable->value;
            $_menus['show'] = $_menus['show'] != MenusEnums\Show::Hide->value;
            $_menus['keepAlive'] = $_menus['keepAlive'] != MenusEnums\KeepAlive::No->value;
        }
        // 返回数据
        return success($request, $menus);
    }

    /**
     * 权限管理-添加或修改菜单
     * 
     * @param integer $id 菜单ID 
     * @param string $code 菜单ID 
     * @param bool $enable 状态 
     * @param bool $show 显示状态 
     * @param bool $keep_alive KeepAlive 
     * @param string $layout 样式 
     * @param string $type 类型 
     * @param integer $parent_id 所属菜单 
     * @param string $name 菜单名称 
     * @param string $icon 菜单图标 
     * @param string $path 路由地址 
     * @param string $component 组件路径 
     * @param integer $order 排序 
     * 
     * @return Response
     */
    public function createOrUpdateMenu(Request $request): Response
    {
        $id = $request->data['id'] ?? 0; // id
        $code = $request->data['code']; // 编码
        $enable = $request->data['enable']; // 启用
        $show = $request->data['show']; // 显示状态
        $keep_alive = $request->data['keepAlive'] ?? false; // KeepAlive(缓存组件)
        $layout = $request->data['layout'] ?? ''; // layout
        $type = $request->data['type']; // 类型：MENU 或者 BUTTON
        $parent_id = $request->data['parentId'] ?? 0; // 所属菜单
        $name = $request->data['name']; // 名称
        $icon = $request->data['icon'] ?? ""; // 菜单图标
        $path = $request->data['path'] ?? ""; // 路由地址
        $component = $request->data['component'] ?? ""; // 组件路径
        $order = $request->data['order'] ?? 0; // 排序
        // 处理数据
        $menus = new Menus();
        if (!empty($id)) {
            $menus = Menus::find($id);
        }
        $menus->code = $code;
        $menus->enable = $enable ? MenusEnums\Enable::Enable->value : MenusEnums\Enable::Disable->value;
        $menus->show = $show ? MenusEnums\Show::Show->value : MenusEnums\Show::Hide->value;
        $menus->keep_alive = $keep_alive ? MenusEnums\KeepAlive::Yes->value : MenusEnums\KeepAlive::No->value;
        $menus->layout = $layout;
        $menus->type = $type;
        $menus->parent_id = $parent_id;
        $menus->name = $name;
        $menus->icon = $icon;
        $menus->path = $path;
        $menus->component = $component;
        $menus->order = $order;
        $menus->save();
        // 返回数据
        return success($request);
    }

    /**
     * 权限管理-快速切换菜单的启用状态
     * 
     * @param integer $id 菜单ID
     * @param bool $enable 状态
     * 
     * @return Response 
     */
    public function toggleMenu(Request $request): Response
    {
        $id = $request->data['id'];
        $enable = $request->data['enable'];
        // 变更数据
        Menus::where('id', $id)->update([
            'enable' => $enable ? MenusEnums\Enable::Enable->value : MenusEnums\Enable::Disable->value
        ]);
        // 返回数据
        return success($request);
    }

    /**
     * 权限管理-删除菜单
     * 
     * @param integer $id 菜单ID
     * 
     * @return Response 
     */
    public function deleteMenu(Request $request): Response
    {
        $id = $request->data['id'];
        // 删除
        Menus::where('id', $id)->delete();
        // 返回数据
        return success($request);
    }

    /**
     * 权限管理 - 角色与用户绑定
     * 
     * @param bool $give 绑定类型「绑定/解绑」
     * @param integer $role_id 角色ID
     * @param array $userIds 管理员ID
     * 
     * @return Response 
     */
    public function assignUsersToRole(Request $request): Response
    {
        $give = $request->data['give'];
        $role_id = $request->data['role_id'];
        $userIds = $request->data['userIds'];
        // 处理数据
        if ($give) {
            foreach ($userIds as $userId) {
                $role = AdminRoles::where('admin_id', $userId)->where('role_id', $role_id)->count();
                if ($role == 0) {
                    $admin_roles = new AdminRoles();
                    $admin_roles->admin_id = $userId;
                    $admin_roles->role_id = $role_id;
                    $admin_roles->save();
                }
            }
        } else {
            foreach ($userIds as $userId) {
                AdminRoles::where('admin_id', $userId)->where('role_id', $role_id)->delete();
            }
        }
        // 返回数据
        return success($request);
    }
}
