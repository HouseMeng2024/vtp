<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use app\common\support\ApiResponse;
use Closure;
use think\Request;
use think\Response;

class Permission
{
    /**
     * 校验后台接口权限。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $required = $this->requiredPermission($request);

        if (!$required || $this->hasPermission($request->adminUser['permissions'] ?? [], $required)) {
            return $next($request);
        }

        return ApiResponse::forbidden();
    }

    /**
     * 判断当前权限集合是否满足接口权限要求。
     */
    private function hasPermission(array $permissions, string|array $required): bool
    {
        if (in_array('*', $permissions, true)) {
            return true;
        }

        foreach ((array) $required as $permission) {
            if (in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根据自动路由推导权限标识。
     */
    private function requiredPermission(Request $request): string|array|null
    {
        $path = trim($request->pathinfo(), '/');

        if (str_starts_with($path, 'admin/')) {
            $path = substr($path, 6);
        }

        if ($this->isPublicPath($path)) {
            return null;
        }

        $parts = explode('/', $path);
        $controller = $parts[0] ?? '';
        $action = $parts[1] ?? 'index';

        if ($controller === '' || $this->isCommonController($controller)) {
            return null;
        }

        return $this->permissionCandidates($controller, $action);
    }

    /**
     * 判断是否为所有登录管理员都可访问的控制器。
     */
    private function isCommonController(string $controller): bool
    {
        return in_array($controller, ['auth', 'index'], true);
    }

    /**
     * 判断公开接口是否跳过权限校验。
     */
    private function isPublicPath(string $path): bool
    {
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

    /**
     * 生成可能的权限标识，最终仍由数据库权限列表决定是否通过。
     */
    private function permissionCandidates(string $controller, string $action): string|array
    {
        $special = $this->specialPermission($controller, $action);

        if ($special) {
            return $special;
        }

        return 'admin:' . $this->permissionModule($controller) . ':' . $this->permissionAction($action);
    }

    /**
     * 少量非 CRUD 接口的权限别名。
     */
    private function specialPermission(string $controller, string $action): string|array|null
    {
        $key = $controller . '/' . $action;
        $permissions = [
            'user/forceLogout'             => 'admin:user:force-logout',
            'member/detail'                => 'admin:member:list',
            'member/batchStatus'           => 'admin:member:status',
            'member/batchDelete'           => 'admin:member:delete',
            'member/resetPassword'         => 'admin:member:reset-password',
            'role/menus'                   => 'admin:role:permission',
            'role/saveMenus'               => 'admin:role:permission',
            'menu/index'                   => ['admin:menu:list', 'admin:role:permission'],
            'dict/typeOptions'             => 'admin:dict:list',
            'config/index'                 => ['admin:config:list', 'admin:config-manage:list'],
            'config/save'                  => 'admin:config:update',
            'config/createGroup'           => 'admin:config-manage:create',
            'config/updateGroup'           => 'admin:config-manage:update',
            'config/deleteGroup'           => 'admin:config-manage:delete',
            'config/createTab'             => 'admin:config-manage:create',
            'config/updateTab'             => 'admin:config-manage:update',
            'config/deleteTab'             => 'admin:config-manage:delete',
            'config/createItem'            => 'admin:config-manage:create',
            'config/updateItem'            => 'admin:config-manage:update',
            'config/deleteItem'            => 'admin:config-manage:delete',
            'file/upload'                  => 'admin:file:upload',
            'file/deleteInfo'              => 'admin:file:delete',
            'log/login'                    => 'admin:login-log:list',
            'log/batchDeleteLogin'         => 'admin:login-log:delete',
            'log/clearLogin'               => 'admin:login-log:clear',
            'log/operate'                  => 'admin:operate-log:list',
            'log/batchDeleteOperate'       => 'admin:operate-log:delete',
            'log/clearOperate'             => 'admin:operate-log:clear',
            'system_tool/index'            => 'admin:tool:list',
            'system_tool/clearCache'       => 'admin:tool:cache-clear',
            'system_tool/backups'          => 'admin:tool:backup-list',
            'system_tool/createBackup'     => 'admin:tool:backup-create',
            'system_tool/downloadBackup'   => 'admin:tool:backup-download',
            'system_tool/restoreBackup'    => 'admin:tool:backup-restore',
            'system_tool/deleteBackup'     => 'admin:tool:backup-delete',
            'code_generator/recent'        => 'admin:code-generator:list',
            'code_generator/preview'       => 'admin:code-generator:generate',
            'code_generator/generate'      => 'admin:code-generator:generate',
            'code_generator/cleanup'       => 'admin:code-generator:generate',
        ];

        return $permissions[$key] ?? null;
    }

    /**
     * 将控制器名转换为权限模块名。
     */
    private function permissionModule(string $controller): string
    {
        return str_replace('_', '-', $controller);
    }

    /**
     * 将方法名转换为权限动作名。
     */
    private function permissionAction(string $action): string
    {
        $actions = [
            'index'       => 'list',
            'types'       => 'list',
            'data'        => 'list',
            'recent'      => 'list',
            'options'     => 'list',
            'save'        => 'create',
            'saveType'    => 'create',
            'saveData'    => 'create',
            'updateType'  => 'update',
            'updateData'  => 'update',
            'status'      => 'status',
            'typeStatus'  => 'status',
            'dataStatus'  => 'status',
            'deleteType'  => 'delete',
            'deleteData'  => 'delete',
            'batchDelete' => 'delete',
            'rename'      => 'update',
            'saveMenus'   => 'permission',
            'read'        => 'read',
            'readAll'     => 'read',
        ];

        return $actions[$action] ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $action) ?? $action);
    }
}
