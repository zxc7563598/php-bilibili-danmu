<?php

namespace app\controller\admin\framework;

use Carbon\Carbon;
use support\Request;
use app\model\Roles;
use app\model\Admins;
use support\Response;
use app\model\AdminRoles;
use resource\enums\RolesEnums;
use resource\enums\AdminsEnums;

class AdminUserController
{

    /**
     * 用户管理 - 获取管理员列表（分页）
     * 
     * @param integer $pageNo 页码
     * @param integer $pageSize 每页展示条数
     * @param integer $gender 性别
     * @param integer $enable 状态
     * @param string $username 用户名
     * 
     * @return Response
     */
    public function list(Request $request): Response
    {
        $pageNo = $request->post('pageNo', 1);
        $pageSize = $request->post('pageSize', 10);
        $gender = $request->post('gender');
        $enable = $request->post('enable');
        $username = $request->post('username');
        // 获取数据
        $list = Admins::with('roles');
        if (!is_null($gender)) {
            $list = $list->where('gender', $gender);
        }
        if (!is_null($enable)) {
            $list = $list->where('enable', $enable);
        }
        if (!is_null($username)) {
            $list = $list->where('username', 'like', '%' . $username . '%');
        }
        $list = $list->orderBy('id', 'asc')->paginate($pageSize, [
            'id' => 'id',
            'username' => 'username',
            'enable' => 'enable',
            'gender' => 'gender',
            'avatar' => 'avatar',
            'address' => 'address',
            'email' => 'email',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ], 'page', $pageNo);
        // 获取角色信息
        $roles = Roles::where('enable', RolesEnums\Enable::Enable->value)->get([
            'id' => 'id',
            'code' => 'code',
            'name' => 'name',
            'enable' => 'enable'
        ]);
        // 处理数据
        $roles_data = [];
        foreach ($roles as $_roles) {
            $roles_data[$_roles['id']] = [
                'id' => $_roles['id'],
                'code' => $_roles['code'],
                'name' => $_roles['name'],
                'enable' => $_roles['enable'] == AdminsEnums\Enable::Enable->value
            ];
        }
        $data = is_array($list) ? $list : $list->toArray();
        foreach ($data['data'] as &$_data) {
            $_data['avatar'] = getImageUrl($_data['avatar']);
            $_data['enable'] = $_data['enable'] == AdminsEnums\Enable::Enable->value;
            $_data['createTime'] = Carbon::parse($_data['created_at'])->timezone(config('app.default_timezone'))->format('Y-m-d H:i:s');
            $_data['updateTime'] = Carbon::parse($_data['updated_at'])->timezone(config('app.default_timezone'))->format('Y-m-d H:i:s');
            unset($_data['created_at']);
            unset($_data['updated_at']);
            // 追加角色权限信息
            $user_roles = $_data['roles'];
            $_data['roles'] = [];
            foreach ($user_roles as $_user_roles) {
                $_data['roles'][] = $roles_data[$_user_roles['role_id']];
            }
        }
        // 返回数据
        return success($request, [
            "total" => $data['total'],
            "pageData" => $data['data']
        ]);
    }

    /**
     * 用户管理 - 获取管理员详情
     * 
     * @return Response
     */
    public function detail(Request $request): Response
    {
        // 获取角色信息
        $roles = Roles::where('enable', RolesEnums\Enable::Enable->value)->get([
            'id' => 'id',
            'code' => 'code',
            'name' => 'name',
            'enable' => 'enable'
        ]);
        // 处理数据
        $roles_data = [];
        foreach ($roles as &$_roles) {
            $_roles->enable = $_roles->enable == RolesEnums\Enable::Enable->value;
            $roles_data[$_roles->id] = $_roles;
        }
        // 返回数据
        return success($request, [
            'id' => $request->admins['id'],
            'username' => $request->admins['username'],
            'enable' => $request->admins['enable'] == AdminsEnums\Enable::Enable->value,
            'createTime' => Carbon::parse($request->admins['created_at'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
            'updateTime' => Carbon::parse($request->admins['updated_at'])->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
            'profile' => [
                'id' => $request->admins['id'],
                'nickName' => $request->admins['nickname'],
                "gender" =>  $request->admins['gender'],
                "avatar" =>  $request->admins['avatar'],
                "address" =>  $request->admins['address'],
                "email" =>  $request->admins['email'],
                "userId" =>  $request->admins['id'],
            ],
            'roles' => $roles,
            'currentRole' => $roles_data[$request->admins['role_id']]
        ]);
    }

    /**
     * 用户管理 - 创建或更新管理员信息
     * 
     * @param integer $id 管理员id
     * @param integer $username 账号
     * @param integer $password 密码
     * @param bool $enable 是否启用
     * @param array $roleIds 角色id
     * 
     * @return Response
     */
    public function createOrUpdate(Request $request): Response
    {
        $id = $request->post('id', null);
        $enable = $request->post('enable', true);
        $username = $request->post('username', null);
        $password = $request->post('password', null);
        $roleIds = $request->post('roleIds', null);
        // 处理数据
        $admins = new Admins();
        if (!is_null($id)) {
            $admins = Admins::where('id', $id)->first();
            if (!is_null($roleIds)) {
                AdminRoles::where('admin_id', $id)->delete();
            }
        }
        if (!is_null($username)) {
            $admins->username = $username;
            if (empty($admins->nickname)) {
                $admins->nickname = $username;
            }
        }
        if (!is_null($enable)) {
            $admins->enable = $enable ? AdminsEnums\Enable::Enable->value : AdminsEnums\Enable::Disable->value;
        }
        if (!is_null($password)) {
            $admins->password = $password;
        }
        if (!is_null($roleIds) && is_null($admins->role_id)) {
            $admins->role_id = $roleIds[0];
        }
        $admins->save();
        // 添加角色关联
        if (!is_null($roleIds)) {
            $insert = [];
            foreach ($roleIds as $_roleIds) {
                $insert[] = [
                    'admin_id' => $admins->id,
                    'role_id' => $_roleIds,
                    'created_at' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp,
                    'updated_at' => Carbon::now()->timezone(config('app')['default_timezone'])->timestamp
                ];
            }
            if (count($insert)) {
                AdminRoles::insert($insert);
            }
        }
        // 返回数据
        return success($request);
    }

    /**
     * 用户管理 - 删除管理员
     * 
     * @param integer $id 管理员ID 
     * 
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $id = $request->post('id');
        // 删除管理员与角色关联
        Admins::where('id', $id)->delete();
        AdminRoles::where('admin_id', $id)->delete();
        // 返回数据
        return success($request);
    }

    /**
     * 用户管理 - 修改管理员密码
     * 
     * @param integer $id 管理员ID 
     * @param string $password 密码 
     * 
     * @return Response
     */
    public function updatePassword(Request $request): Response
    {
        $id = $request->post('id');
        $password = $request->post('password');
        // 获取数据
        $admins = Admins::where('id', $id)->first();
        $admins->password = $password;
        $admins->save();
        // 返回数据
        return success($request);
    }

    /**
     * 用户管理 - 修改管理员个人信息
     * 
     * @param integer $id 管理员ID 
     * @param string $address 地址 
     * @param string $email 邮箱 
     * @param integer $gender 性别 
     * @param string $nickName 昵称 
     * 
     * @return Response
     */
    public function updateProfile(Request $request): Response
    {
        $id = $request->post('id');
        $address = $request->post('address', null);
        $email = $request->post('email', null);
        $gender = $request->post('gender', null);
        $nickName = $request->post('nickName', null);
        // 获取数据
        $admins = Admins::where('id', $id)->first();
        $admins->address = $address;
        $admins->email = $email;
        $admins->gender = $gender;
        $admins->nickname = $nickName;
        $admins->save();
        // 返回数据
        return success($request);
    }
}
