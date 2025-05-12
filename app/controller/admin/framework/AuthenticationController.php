<?php

namespace app\controller\admin\framework;

use support\Request;
use app\model\Roles;
use support\Response;
use app\model\Admins;
use app\model\AdminRoles;
use app\core\AdminAuthService;

class AuthenticationController
{
    /**
     * 认证相关 - 登录
     * 
     * @param string $username 账号
     * @param string $password 密码
     * @param string $captcha 验证码
     * 
     * @return Response
     */
    public function login(Request $request): Response
    {
        // 获取请求参数
        $username = $request->data['username'];
        $password = $request->data['password'];
        $captcha = $request->data['captcha'] ?? '';
        // 执行登录
        $token = AdminAuthService::login($username, $password, $captcha);
        if (is_int($token)) {
            return fail($request, $token);
        }
        // 返回数据
        return success($request, [
            'accessToken' => $token
        ]);
    }

    /**
     * 认证相关 - 退出登录
     * 
     * @return Response
     */
    public function logout(Request $request): Response
    {
        // 退出登录
        AdminAuthService::logout($request->admins['id']);
        // 返回数据
        return success($request);
    }

    /**
     * 认证相关 - 切换角色
     * 
     * @param string $code 角色code
     * 
     * @return Response 
     */
    public function switchRole(Request $request): Response
    {
        $code = $request->data['code'];
        // 获取角色信息
        $roles = Roles::where('code', $code)->first();
        if (empty($roles)) {
            return fail($request, 900010);
        }
        // 确认管理员是否可以切换到角色
        $permissions = AdminRoles::where('role_id', $roles->id)->where('admin_id', $request->admins['id'])->count();
        if ($permissions == 0) {
            return fail($request, 900011);
        }
        $admins = Admins::where('id', $request->admins['id'])->first();
        $admins->role_id = $roles->id;
        $admins->save();
        // 返回数据
        return success($request);
    }

    /**
     * 认证相关 - 修改密码
     * 
     * @param string $newPassword 新密码
     * @param string $oldPassword 老密码
     * 
     * @return Response
     */
    public function updatePassword(Request $request): Response
    {
        $param = $request->data;
        $newPassword = $param['newPassword'];
        $oldPassword = $param['oldPassword'];
        // 验证密码
        $admins = Admins::where('id', $request->admins['id'])->first();
        if ($admins->password == sha1(sha1($oldPassword) . $admins->salt)) {
            $admins->password = $newPassword;
            $admins->save();
        }
        // 返回数据
        return success($request);
    }
}
