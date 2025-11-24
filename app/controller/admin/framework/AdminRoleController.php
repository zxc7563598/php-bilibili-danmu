<?php

namespace app\controller\admin\framework;

use Carbon\Carbon;
use app\model\Roles;
use support\Request;
use app\model\Menus;
use app\model\Admins;
use support\Response;
use app\model\AdminRoles;
use resource\enums\RolesEnums;
use resource\enums\MenusEnums;
use app\model\RolePermissions;

class AdminRoleController
{

    /**
     * 角色管理-获取角色列表（分页）
     * 
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示条数
     * @param string $name 角色名
     * @param integer $enable 状态
     * 
     * @return Response
     */
    public function list(Request $request): Response
    {
        $pageNo = $request->post('pageNo', 1);
        $pageSize = $request->post('pageSize', 10);
        $name = $request->post('name', null);
        $enable = $request->post('enable', null);
        // 获取数据
        $list = Roles::with('permissions');
        if (!is_null($name)) {
            $list = $list->where('name', 'like', '%' . $name . '%');
        }
        if (!is_null($enable)) {
            $list = $list->where('enable', $enable);
        }
        $list = $list->orderBy('id', 'asc')->paginate($pageSize, [
            'id' => 'id',
            'code' => 'code',
            'name' => 'name',
            'enable' => 'enable'
        ], 'page', $pageNo);
        // 处理数据
        $data = is_array($list) ? $list : $list->toArray();
        foreach ($data['data'] as &$_data) {
            $permissionIds = [];
            foreach ($_data['permissions'] as $permissions) {
                $permissionIds[] = $permissions['menu_id'];
            }
            $_data['permissionIds'] = $permissionIds;
            $_data['enable'] = $_data['enable'] != RolesEnums\Enable::Disable->value;
            unset($_data->permissions);
        }
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }

    /**
     * 角色管理-获取所有角色列表
     * 
     * @return Response
     */
    public function all(Request $request): Response
    {
        // 获取数据
        $list = Roles::where('enable', RolesEnums\Enable::Enable->value)->orderBy('id', 'asc')->get([
            'id' => 'id',
            'code' => 'code',
            'name' => 'name',
            'enable' => 'enable'
        ]);
        // 处理数据
        foreach ($list as &$_list) {
            $_list->enable = $_list->enable != RolesEnums\Enable::Disable->value;
        }
        // 返回数据
        return success($request, $list);
    }

    /**
     * 角色管理-创建或更新角色
     * 
     * @param integer $id 角色ID
     * @param string $code 角色code
     * @param string $name 角色名
     * @param bool $enable 状态
     * @param array $permissionIds 菜单ID
     * 
     * @return Response
     */
    public function createOrUpdate(Request $request): Response
    {
        $id = $request->post('id', null);
        $code = $request->post('code', null);
        $name = $request->post('name', null);
        $enable = $request->post('enable', null);
        $permissionIds = $request->post('permissionIds', null);
        // 获取数据
        $roles = new Roles();
        if (!empty($id)) {
            $roles = Roles::find($id);
        }
        if (!is_null($code)) {
            $roles->code = $code;
        }
        if (!is_null($name)) {
            $roles->name = $name;
        }
        if (!is_null($enable)) {
            $roles->enable = $enable ? RolesEnums\Enable::Enable->value : RolesEnums\Enable::Disable->value;
        }
        $roles->save();
        // 处理菜单数据
        if (!is_null($permissionIds)) {
            RolePermissions::where('role_id', $roles->id)->delete();
            $insert = [];
            foreach ($permissionIds as $menu_id) {
                $insert[] = [
                    'role_id' => $roles->id,
                    'menu_id' => $menu_id,
                    'created_at' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp,
                    'updated_at' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp
                ];
            }
            RolePermissions::insert($insert);
        }
        // 返回数据
        return success($request);
    }

    /**
     * 角色管理-删除角色
     * 
     * @param integer $id 角色ID 
     * 
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $id = $request->post('id');
        // 删除角色
        Roles::destroy($id);
        // 删除相关菜单
        RolePermissions::where('role_id', $id)->delete();
        // 删除分配管理员角色表
        AdminRoles::where('role_id', $id)->delete();
        // 确认管理员不是该角色，如果是，自动更换为其他角色
        $admins = Admins::with('roles')->where('role_id', $id)->get();
        foreach ($admins as $_admins) {
            if (isset($_admins->roles[0]->role_id)) {
                $_admins->role_id = $_admins->roles[0]->role_id;
            } else {
                $_admins->role_id = null;
            }
            $_admins->save();
        }
        // 返回数据
        return success($request);
    }

    /**
     * 角色管理-获取角色的菜单权限树
     * 
     * @return Response
     */
    public function permissions(Request $request): Response
    {
        // 获取数据
        $menus = new Menus();
        if ($request->admins['role_id'] != 1) {
            $menu_id = RolePermissions::where('role_id', $request->admins['role_id'])->pluck('menu_id')->toArray();
            $menus = $menus->whereIn('id', $menu_id);
        }
        $menus = $menus->where('enable', MenusEnums\Enable::Enable->value)->select([
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
}
