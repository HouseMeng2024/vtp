<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\AdminLoginLog;
use app\common\model\AdminMenu;
use app\common\model\AdminRole;
use app\common\model\AdminUser;
use app\common\support\ConfigValue;
use RuntimeException;
use think\facade\Cache;
use think\file\UploadedFile;
use think\Request;

class AuthService
{
    private string $cachePrefix = 'admin_token:';
    private string $userTokenPrefix = 'admin_user_tokens:';
    private string $loginFailPrefix = 'admin_login_fail:';
    private string $captchaPrefix = 'admin_captcha:';

    /**
     * 校验账号密码，登录成功后签发 token 并记录登录日志。
     */
    public function login(string $username, string $password, ?Request $request = null, string $captchaKey = '', string $captchaCode = ''): array
    {
        $this->verifyCaptcha($captchaKey, $captchaCode);
        $this->ensureLoginAllowed($username, $request);

        $user = AdminUser::where([])
            ->where('username', $username)
            ->find();

        if (!$user || !password_verify($password, (string) $user->getData('password'))) {
            $this->increaseLoginFailure($username, $request);
            $this->recordLoginLog($request, 0, $username, 0, '账号或密码错误');
            throw new RuntimeException('账号或密码错误');
        }

        if ((int) $user->status !== 1) {
            $this->recordLoginLog($request, 0, $username, (int) $user->id, '账号已被禁用');
            throw new RuntimeException('账号已被禁用');
        }

        $user->save([
            'last_login_ip'   => $request ? $request->ip() : '',
            'last_login_time' => date('Y-m-d H:i:s'),
        ]);

        $this->recordLoginLog($request, 1, $username, (int) $user->id, '登录成功');
        $this->clearLoginFailure($username, $request);

        $token = bin2hex(random_bytes(32));
        $expire = $this->tokenExpire();
        $profile = $this->formatProfile($user->toArray());

        Cache::set($this->getTokenKey($token), $profile, $expire);
        $this->rememberUserToken((int) $user->id, $token, $expire);

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expire,
            'user'       => $profile,
        ];
    }

    /**
     * 生成后台登录验证码。
     */
    public function captcha(): array
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $answer = (string) ($left + $right);
        $key = bin2hex(random_bytes(16));
        $text = $left . ' + ' . $right . ' = ?';
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="40"><rect width="120" height="40" rx="4" fill="#f5f7fa"/><text x="60" y="26" text-anchor="middle" font-size="18" font-family="Arial" fill="#303133">%s</text></svg>',
            htmlspecialchars($text, ENT_QUOTES)
        );

        Cache::set($this->captchaPrefix . $key, $answer, 300);

        return [
            'enabled' => $this->captchaEnabled(),
            'key'     => $key,
            'image'   => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ];
    }

    /**
     * 根据 token 获取当前管理员资料。
     */
    public function profile(string $token): array
    {
        $profile = Cache::get($this->getTokenKey($token));

        if (!$profile) {
            throw new RuntimeException('登录已失效，请重新登录');
        }

        $user = AdminUser::find((int) ($profile['id'] ?? 0));

        if (!$user || (int) $user->status !== 1) {
            throw new RuntimeException('登录已失效，请重新登录');
        }

        return $this->formatProfile($user->toArray());
    }

    /**
     * 删除 token 缓存，完成退出登录。
     */
    public function logout(string $token): void
    {
        $profile = Cache::get($this->getTokenKey($token));
        Cache::delete($this->getTokenKey($token));

        if (is_array($profile) && isset($profile['id'])) {
            $this->forgetUserToken((int) $profile['id'], $token);
        }
    }

    /**
     * 删除指定管理员的全部登录 token，用于后台强制下线。
     */
    public function revokeUserTokens(int $userId): void
    {
        $tokens = Cache::get($this->getUserTokenKey($userId), []);

        if (!is_array($tokens)) {
            $tokens = [];
        }

        foreach ($tokens as $token) {
            Cache::delete($this->getTokenKey((string) $token));
        }

        Cache::delete($this->getUserTokenKey($userId));
    }

    /**
     * 更新当前管理员资料。
     */
    public function updateProfile(int $userId, array $data): array
    {
        $user = $this->findUser($userId);
        $nickname = trim((string) ($data['nickname'] ?? ''));
        $mobile = trim((string) ($data['mobile'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if ($nickname === '') {
            throw new RuntimeException('请输入昵称');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('邮箱格式不正确');
        }

        $user->save([
            'nickname' => $nickname,
            'mobile'   => $mobile,
            'email'    => $email,
        ]);

        return $this->formatProfile($user->toArray());
    }

    /**
     * 修改当前管理员密码。
     */
    public function changePassword(int $userId, array $data): void
    {
        $user = $this->findUser($userId);
        $oldPassword = (string) ($data['old_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');

        if (!password_verify($oldPassword, (string) $user->getData('password'))) {
            throw new RuntimeException('原密码不正确');
        }

        if ($newPassword !== $confirmPassword) {
            throw new RuntimeException('两次输入的新密码不一致');
        }

        if (strlen($newPassword) < $this->passwordMinLength()) {
            throw new RuntimeException('新密码长度不足');
        }

        if (password_verify($newPassword, (string) $user->getData('password'))) {
            throw new RuntimeException('新密码不能和原密码相同');
        }

        $user->save(['password' => password_hash($newPassword, PASSWORD_BCRYPT)]);
        $this->revokeUserTokens($userId);
    }

    /**
     * 上传并更新当前管理员头像。
     */
    public function updateAvatar(int $userId, ?UploadedFile $file): array
    {
        $user = $this->findUser($userId);
        $record = (new FileService())->upload($file, $userId, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'avatar');
        $user->save(['avatar' => $record['url'] ?? '']);

        return $this->formatProfile($user->toArray());
    }

    /**
     * 从 Authorization 请求头解析 Bearer token。
     */
    public function tokenFromAuthorization(string $authorization): string
    {
        if (preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * 查找管理员，不存在时抛出业务异常。
     */
    private function findUser(int $userId): AdminUser
    {
        $user = AdminUser::find($userId);

        if (!$user) {
            throw new RuntimeException('管理员不存在');
        }

        return $user;
    }

    /**
     * 获取密码最小长度配置。
     */
    private function passwordMinLength(): int
    {
        return max(6, (int) ConfigValue::getInGroups('password_min_length', ['system'], 6));
    }

    /**
     * 获取后台 token 有效期配置。
     */
    private function tokenExpire(): int
    {
        return max(300, (int) ConfigValue::getInGroups('token_expire', ['admin'], 86400));
    }

    /**
     * 获取登录失败锁定阈值配置。
     */
    private function loginMaxAttempts(): int
    {
        return max(1, (int) ConfigValue::getInGroups('login_max_attempts', ['system'], 5));
    }

    /**
     * 获取登录失败锁定时长配置。
     */
    private function loginLockSeconds(): int
    {
        return max(60, (int) ConfigValue::getInGroups('login_lock_seconds', ['system'], 900));
    }

    /**
     * 判断是否启用登录验证码。
     */
    private function captchaEnabled(): bool
    {
        $value = ConfigValue::getInGroups('captcha_enabled', ['admin'], false);

        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * 启用验证码后校验验证码答案。
     */
    private function verifyCaptcha(string $key, string $code): void
    {
        if (!$this->captchaEnabled()) {
            return;
        }

        if ($key === '' || trim($code) === '') {
            throw new RuntimeException('请输入验证码');
        }

        $cacheKey = $this->captchaPrefix . $key;
        $answer = Cache::get($cacheKey);
        Cache::delete($cacheKey);

        if (!$answer || trim($code) !== (string) $answer) {
            throw new RuntimeException('验证码错误');
        }
    }

    /**
     * 生成 token 缓存键。
     */
    private function getTokenKey(string $token): string
    {
        return $this->cachePrefix . hash('sha256', $token);
    }

    /**
     * 生成管理员 token 列表缓存键。
     */
    private function getUserTokenKey(int $userId): string
    {
        return $this->userTokenPrefix . $userId;
    }

    /**
     * 生成登录失败计数缓存键。
     */
    private function getLoginFailKey(string $username, ?Request $request): string
    {
        $ip = $request ? $request->ip() : '';

        return $this->loginFailPrefix . hash('sha256', strtolower(trim($username)) . '|' . $ip);
    }

    /**
     * 登录前检查当前账号和 IP 是否已经被临时锁定。
     */
    private function ensureLoginAllowed(string $username, ?Request $request): void
    {
        $state = Cache::get($this->getLoginFailKey($username, $request), []);
        $lockedUntil = (int) ($state['locked_until'] ?? 0);

        if ($lockedUntil > time()) {
            throw new RuntimeException('登录失败次数过多，请稍后再试');
        }
    }

    /**
     * 记录登录失败次数，达到阈值后临时锁定。
     */
    private function increaseLoginFailure(string $username, ?Request $request): void
    {
        $key = $this->getLoginFailKey($username, $request);
        $state = Cache::get($key, []);
        $count = (int) ($state['count'] ?? 0) + 1;
        $lockSeconds = $this->loginLockSeconds();
        $state = [
            'count'        => $count,
            'locked_until' => $count >= $this->loginMaxAttempts() ? time() + $lockSeconds : 0,
        ];

        Cache::set($key, $state, $lockSeconds);
    }

    /**
     * 登录成功后清理失败计数。
     */
    private function clearLoginFailure(string $username, ?Request $request): void
    {
        Cache::delete($this->getLoginFailKey($username, $request));
    }

    /**
     * 记录管理员当前有效 token，便于后续强制下线。
     */
    private function rememberUserToken(int $userId, string $token, int $expire): void
    {
        $key = $this->getUserTokenKey($userId);
        $tokens = Cache::get($key, []);

        if (!is_array($tokens)) {
            $tokens = [];
        }

        $tokens[] = $token;
        Cache::set($key, array_values(array_unique($tokens)), $expire);
    }

    /**
     * 管理员退出时从 token 列表里移除当前 token。
     */
    private function forgetUserToken(int $userId, string $token): void
    {
        $key = $this->getUserTokenKey($userId);
        $tokens = Cache::get($key, []);

        if (!is_array($tokens)) {
            return;
        }

        Cache::set($key, array_values(array_filter($tokens, fn (string $item) => $item !== $token)), $this->tokenExpire());
    }

    /**
     * 组装前端需要的管理员登录资料。
     */
    private function formatProfile(array $user): array
    {
        $roles = $this->getRoleCodes((int) $user['id']);
        $permissions = in_array('super_admin', $roles, true)
            ? ['*']
            : $this->getPermissions((int) $user['id']);

        return [
            'id'          => $user['id'],
            'username'    => $user['username'],
            'nickname'    => $user['nickname'],
            'avatar'      => $user['avatar'] ?? '',
            'mobile'      => $user['mobile'] ?? '',
            'email'       => $user['email'] ?? '',
            'roles'       => $roles,
            'data_scope'  => $this->getDataScope((int) $user['id'], $roles),
            'permissions' => $permissions,
        ];
    }

    /**
     * 获取管理员拥有的有效角色标识。
     */
    private function getRoleCodes(int $userId): array
    {
        return AdminRole::where([])
            ->alias('r')
            ->join('admin_user_role ur', 'ur.role_id = r.id')
            ->where('ur.user_id', $userId)
            ->where('r.status', 1)
            ->column('r.code');
    }

    /**
     * 获取管理员通过角色拥有的权限标识。
     */
    private function getPermissions(int $userId): array
    {
        return AdminMenu::where([])
            ->alias('m')
            ->join('admin_role_menu rm', 'rm.menu_id = m.id')
            ->join('admin_user_role ur', 'ur.role_id = rm.role_id')
            ->join('admin_role r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->where('m.status', 1)
            ->where('r.status', 1)
            ->whereNull('r.delete_time')
            ->where('m.permission', '<>', '')
            ->distinct(true)
            ->column('m.permission');
    }

    /**
     * 根据有效角色计算管理员数据范围。
     */
    private function getDataScope(int $userId, array $roles): string
    {
        if (in_array('super_admin', $roles, true)) {
            return 'all';
        }

        $scopes = AdminRole::where([])
            ->alias('r')
            ->join('admin_user_role ur', 'ur.role_id = r.id')
            ->where('ur.user_id', $userId)
            ->where('r.status', 1)
            ->whereNull('r.delete_time')
            ->column('r.data_scope');

        return in_array('all', $scopes, true) ? 'all' : 'self';
    }

    /**
     * 记录管理员登录日志。
     */
    private function recordLoginLog(?Request $request, int $status, string $username, int $userId, string $message): void
    {
        AdminLoginLog::create([
            'user_id'     => $userId,
            'username'    => $username,
            'ip'          => $request ? $request->ip() : '',
            'user_agent'  => $request ? (string) $request->header('user-agent', '') : '',
            'status'      => $status,
            'message'     => $message,
        ]);
    }
}
