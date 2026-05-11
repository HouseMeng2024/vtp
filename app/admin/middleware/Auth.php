<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use app\common\service\admin\AuthService;
use app\common\support\ApiResponse;
use Closure;
use think\Request;
use think\Response;

class Auth
{
    /**
     * 校验后台接口 token，并把管理员上下文写入请求对象。
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isPublicPath($request)) {
            return $next($request);
        }

        $authService = new AuthService();
        $token = $authService->tokenFromAuthorization((string) $request->header('authorization', ''));

        if ($token === '') {
            return ApiResponse::unauthorized();
        }

        try {
            $request->adminUser = $authService->profile($token);
            $request->adminToken = $token;
        } catch (\RuntimeException $exception) {
            return ApiResponse::unauthorized($exception->getMessage());
        }

        return $next($request);
    }

    /**
     * 判断当前后台接口是否允许未登录访问。
     */
    private function isPublicPath(Request $request): bool
    {
        $path = trim($request->pathinfo(), '/');

        if (str_starts_with($path, 'admin/')) {
            $path = substr($path, 6);
        }

        return in_array($path, [
            '',
            'index',
            'index/index',
            'index/ping',
            'auth/login',
            'auth/captcha',
            'config/site',
        ], true);
    }
}
