<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\AuthService;
use app\common\service\admin\MenuService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class Auth extends AdminBase
{
    /**
     * 管理员登录并签发访问 token。
     */
    public function login(): Response
    {
        $data = $this->request->only(['username', 'password', 'captcha_key', 'captcha_code'], 'post');
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return ApiResponse::fail('请输入账号和密码');
        }

        try {
            return ApiResponse::success((new AuthService())->login(
                $username,
                $password,
                $this->request,
                (string) ($data['captcha_key'] ?? ''),
                (string) ($data['captcha_code'] ?? '')
            ));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 获取后台登录验证码。
     */
    public function captcha(): Response
    {
        return ApiResponse::success((new AuthService())->captcha());
    }

    /**
     * 获取当前登录管理员信息。
     */
    public function profile(): Response
    {
        return ApiResponse::success($this->adminUser());
    }

    /**
     * 更新当前管理员资料。
     */
    public function updateProfile(): Response
    {
        try {
            return ApiResponse::success((new AuthService())->updateProfile($this->adminId(), $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改当前管理员密码。
     */
    public function changePassword(): Response
    {
        try {
            (new AuthService())->changePassword($this->adminId(), $this->request->put());
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 上传并更新当前管理员头像。
     */
    public function avatar(): Response
    {
        try {
            return ApiResponse::success((new AuthService())->updateAvatar(
                $this->adminId(),
                $this->request->file('file')
            ));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 退出登录并清理当前 token。
     */
    public function logout(): Response
    {
        $token = (string) ($this->request->adminToken ?? '');

        if ($token !== '') {
            (new AuthService())->logout($token);
        }

        return ApiResponse::success();
    }

    /**
     * 获取当前管理员可访问的后台菜单。
     */
    public function menus(): Response
    {
        return ApiResponse::success((new MenuService())->tree($this->adminUser()));
    }
}
