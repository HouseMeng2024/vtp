<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\AuthService;
use app\common\service\admin\ConfigService;
use app\common\service\admin\FileService;
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

        if (!$this->isScalarInput($data['username'] ?? '') || !$this->isScalarInput($data['password'] ?? '')
            || !$this->isScalarInput($data['captcha_key'] ?? '') || !$this->isScalarInput($data['captcha_code'] ?? '')
        ) {
            return ApiResponse::fail('请求参数格式不正确');
        }

        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return ApiResponse::fail('请输入账号和密码');
        }

        try {
            $result = (new AuthService())->login(
                $username,
                $password,
                $this->request,
                (string) ($data['captcha_key'] ?? ''),
                (string) ($data['captcha_code'] ?? '')
            );

            return ApiResponse::success(array_merge($result, $this->backendContext($result['user'] ?? [])));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 判断登录入参是否为可转字符串的标量值。
     */
    private function isScalarInput(mixed $value): bool
    {
        return is_scalar($value) || $value === null;
    }

    /**
     * 获取后台登录验证码。
     */
    public function captcha(): Response
    {
        $captcha = (new AuthService())->captcha();
        $captcha['site_config'] = (new ConfigService())->site();

        return ApiResponse::success($captcha);
    }

    /**
     * 获取当前登录管理员信息。
     */
    public function profile(): Response
    {
        return ApiResponse::success($this->adminUser());
    }

    /**
     * 获取后台首屏上下文。
     */
    public function context(): Response
    {
        return ApiResponse::success($this->backendContext($this->adminUser()));
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

    /**
     * 组装后台首屏需要的公共上下文。
     */
    private function backendContext(array $user): array
    {
        return [
            'user'           => $user,
            'menus'          => (new MenuService())->tree($user),
            'site_config'    => (new ConfigService())->site(),
            'config_options' => (new ConfigService())->options(),
            'file_options'   => (new FileService())->options(),
        ];
    }
}
