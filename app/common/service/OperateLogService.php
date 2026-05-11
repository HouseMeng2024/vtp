<?php
declare (strict_types = 1);

namespace app\common\service;

use app\common\model\admin\AdminOperateLog;
use think\Request;

class OperateLogService
{
    /**
     * 持久化后台操作日志。
     */
    public function record(Request $request, int $statusCode, float $duration, string $responseBody = ''): void
    {
        $adminUser = $request->adminUser ?? [];
        $params = $request->param();
        $title = $this->operationTitle($request->method(), $request->pathinfo());

        foreach (['password', 'old_password', 'new_password', 'confirm_password'] as $key) {
            if (isset($params[$key])) {
                $params[$key] = '******';
            }
        }

        AdminOperateLog::create([
            'user_id'     => (int) ($adminUser['id'] ?? 0),
            'username'    => (string) ($adminUser['username'] ?? ''),
            'title'       => $title,
            'method'      => $request->method(),
            'path'        => $request->pathinfo(),
            'params'      => json_encode($params, JSON_UNESCAPED_UNICODE),
            'response'    => mb_substr($responseBody, 0, 1000),
            'ip'          => $request->ip(),
            'user_agent'  => (string) $request->header('user-agent', ''),
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
        ]);
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
            str_contains($path, 'users') => '管理员操作',
            str_contains($path, 'roles') => '角色操作',
            str_contains($path, 'menus') => '菜单操作',
            str_contains($path, 'configs') => '项目配置操作',
            str_contains($path, 'files') => '文件操作',
            str_contains($path, 'dict') => '字典操作',
            str_contains($path, 'logs') => '日志操作',
            str_contains($path, 'system-tools') => '系统工具操作',
            default => '后台操作',
        };
    }
}
