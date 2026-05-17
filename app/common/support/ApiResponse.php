<?php
declare (strict_types = 1);

namespace app\common\support;

use think\facade\Lang;
use think\Response;

class ApiResponse
{
    /**
     * 返回成功 JSON 响应。
     */
    public static function success(array $data = [], string $message = 'success', int $code = 0): Response
    {
        return json([
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * 返回失败 JSON 响应。
     */
    public static function fail(string $message = 'error', int $code = 1, array $data = []): Response
    {
        return json([
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * 返回未登录 JSON 响应。
     */
    public static function unauthorized(string $message = ''): Response
    {
        $message = $message !== '' ? $message : Lang::get('auth.required');

        return self::fail($message, 401)->code(401);
    }

    /**
     * 返回无权限 JSON 响应。
     */
    public static function forbidden(string $message = ''): Response
    {
        $message = $message !== '' ? $message : Lang::get('auth.forbidden');

        return self::fail($message, 403)->code(403);
    }
}
