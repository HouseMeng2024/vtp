<?php
declare (strict_types = 1);

namespace app\common\service;

use app\common\model\AdminOperateLog;
use think\Request;

class OperateLogService
{
    /**
     * 日志里需要脱敏的字段名。
     */
    private const SENSITIVE_KEYS = [
        'password',
        'old_password',
        'new_password',
        'confirm_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'key',
    ];

    /**
     * 持久化后台操作日志。
     */
    public function record(Request $request, int $statusCode, float $duration, string $responseBody = ''): void
    {
        $adminUser = $request->adminUser ?? [];
        $params = $this->maskSensitive($request->param());
        $title = $this->operationTitle($request->method(), $request->pathinfo());

        AdminOperateLog::create([
            'user_id'     => (int) ($adminUser['id'] ?? 0),
            'username'    => (string) ($adminUser['username'] ?? ''),
            'title'       => $title,
            'method'      => $request->method(),
            'path'        => $request->pathinfo(),
            'params'      => json_encode($params, JSON_UNESCAPED_UNICODE),
            'response'    => $this->formatResponseBody($request, $responseBody),
            'ip'          => $request->ip(),
            'user_agent'  => (string) $request->header('user-agent', ''),
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
        ]);
    }

    /**
     * 递归脱敏数组里的敏感字段。
     */
    private function maskSensitive(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $value[$key] = '******';
                continue;
            }

            $value[$key] = $this->maskSensitive($item);
        }

        return $value;
    }

    /**
     * 格式化响应体，避免 token 等敏感信息进入操作日志。
     */
    private function formatResponseBody(Request $request, string $responseBody): string
    {
        if ($this->isLoginRequest($request)) {
            return '';
        }

        $decoded = json_decode($responseBody, true);

        if (is_array($decoded)) {
            return mb_substr((string) json_encode($this->maskSensitive($decoded), JSON_UNESCAPED_UNICODE), 0, 1000);
        }

        return mb_substr($responseBody, 0, 1000);
    }

    /**
     * 判断是否为管理员登录请求。
     */
    private function isLoginRequest(Request $request): bool
    {
        $path = trim($request->pathinfo(), '/');

        return strtoupper($request->method()) === 'POST'
            && (str_ends_with($path, 'auth/login') || $path === 'auth/login');
    }

    /**
     * 根据请求方法和路径生成操作标题。
     */
    private function operationTitle(string $method, string $path): string
    {
        $path = trim($path, '/');
        $method = strtoupper($method);

        return match (true) {
            str_contains($path, 'login') && $method === 'POST' => '管理员登录',
            str_contains($path, 'logout') => '退出登录',
            str_contains($path, 'config/createGroup') => '新增配置分组',
            str_contains($path, 'config/updateGroup') => '编辑配置分组',
            str_contains($path, 'config/deleteGroup') => '删除配置分组',
            str_contains($path, 'config/createTab') => '新增配置标签',
            str_contains($path, 'config/updateTab') => '编辑配置标签',
            str_contains($path, 'config/deleteTab') => '删除配置标签',
            str_contains($path, 'config/createItem') => '新增配置项',
            str_contains($path, 'config/updateItem') => '编辑配置项',
            str_contains($path, 'config/deleteItem') => '删除配置项',
            str_contains($path, 'config/save') => '保存项目配置',
            str_contains($path, 'users') => '管理员操作',
            str_contains($path, 'roles') => '角色操作',
            str_contains($path, 'menus') => '菜单操作',
            str_contains($path, 'config') => '项目配置操作',
            str_contains($path, 'files') => '文件操作',
            str_contains($path, 'dict') => '字典操作',
            str_contains($path, 'logs') => '日志操作',
            str_contains($path, 'system-tools') => '系统工具操作',
            default => '后台操作',
        };
    }
}
