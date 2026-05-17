<?php
declare (strict_types = 1);

namespace app\common\base;

use app\BaseController;
use app\common\support\ConfigValue;

class AdminBase extends BaseController
{
    /**
     * 初始化后台控制器。
     */
    protected function initialize()
    {
        parent::initialize();
        ConfigValue::loadGroupsToConfig('system', ['system']);
        ConfigValue::loadGroupsToConfig('admin', ['admin']);
    }

    /**
     * 获取当前登录管理员上下文。
     */
    protected function adminUser(): array
    {
        return $this->request->adminUser ?? [];
    }

    /**
     * 获取当前登录管理员 ID。
     */
    protected function adminId(): int
    {
        return (int) ($this->adminUser()['id'] ?? 0);
    }

    /**
     * 获取当前管理员的数据权限范围。
     */
    protected function dataScope(): string
    {
        return (string) ($this->adminUser()['data_scope'] ?? 'self');
    }

    /**
     * 给查询对象追加通用数据权限条件。
     */
    protected function applyDataScope(mixed $query, string $ownerField = 'create_by'): mixed
    {
        if ($this->dataScope() === 'all') {
            return $query;
        }

        return $query->where($ownerField, $this->adminId());
    }

    /**
     * 生成服务层可复用的数据权限上下文。
     */
    protected function scopeContext(): array
    {
        return [
            '_admin_id'    => $this->adminId(),
            '_data_scope'  => $this->dataScope(),
        ];
    }
}
