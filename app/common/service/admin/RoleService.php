<?php

declare(strict_types=1);

namespace app\common\service\admin;

use app\common\model\AdminRole;
use app\common\model\AdminRoleMenu;
use app\common\model\AdminUserRole;
use RuntimeException;

class RoleService
{
    /**
     * 获取启用中的角色选项。
     */
    public function options(): array
    {
        return AdminRole::where('status', 1)
            ->field('id,name,code')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 创建角色并返回角色详情。
     */
    public function create(array $data): array
    {
        $payload = $this->filterPayload($data);

        if (AdminRole::where('code', $payload['code'])->find()) {
            throw new RuntimeException('角色标识已存在');
        }

        $role = AdminRole::create($payload);

        return $this->detail((int) $role->id);
    }

    /**
     * 更新角色基础信息并返回角色详情。
     */
    public function update(int $id, array $data): array
    {
        $role = $this->findRole($id);
        $payload = $this->filterPayload($data);

        $exists = AdminRole::where('code', $payload['code'])
            ->where('id', '<>', $id)
            ->find();

        if ($exists) {
            throw new RuntimeException('角色标识已存在');
        }

        $role->save($payload);

        return $this->detail($id);
    }

    /**
     * 修改角色启用状态。
     */
    public function changeStatus(int $id, int $status): array
    {
        $role = $this->findRole($id);
        $role->save(['status' => $status === 1 ? 1 : 0]);

        return $this->detail($id);
    }

    /**
     * 批量修改角色启用状态。
     */
    public function batchChangeStatus(array $ids, int $status): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException('请选择角色');
        }

        AdminRole::whereIn('id', $ids)->update(['status' => $status === 1 ? 1 : 0]);
    }

    /**
     * 删除角色，并校验默认角色和管理员绑定关系。
     */
    public function delete(int $id): void
    {
        if ($id === 1) {
            throw new RuntimeException('默认超级管理员角色不能删除');
        }

        if (AdminUserRole::where('role_id', $id)->find()) {
            throw new RuntimeException('角色已绑定管理员，不能删除');
        }

        $role = $this->findRole($id);
        $role->delete();
        AdminRoleMenu::where('role_id', $id)->delete();
    }

    /**
     * 批量删除角色，并校验默认角色和管理员绑定关系。
     */
    public function batchDelete(array $ids): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException('请选择角色');
        }

        if (in_array(1, $ids, true)) {
            throw new RuntimeException('默认超级管理员角色不能删除');
        }

        if (AdminUserRole::whereIn('role_id', $ids)->find()) {
            throw new RuntimeException('存在已绑定管理员的角色，不能删除');
        }

        foreach ($ids as $id) {
            $this->findRole($id)->delete();
        }

        AdminRoleMenu::whereIn('role_id', $ids)->delete();
    }

    /**
     * 获取角色已绑定的菜单 ID 列表。
     */
    public function menuIds(int $id): array
    {
        $this->findRole($id);

        return array_map('intval', AdminRoleMenu::where('role_id', $id)
            ->column('menu_id'));
    }

    /**
     * 同步角色菜单权限。
     */
    public function syncMenus(int $id, mixed $menuIds): void
    {
        $this->findRole($id);
        $menuIds = $this->filterMenuIds($menuIds);

        AdminRoleMenu::where('role_id', $id)->delete();

        foreach ($menuIds as $menuId) {
            AdminRoleMenu::create([
                'role_id' => $id,
                'menu_id' => $menuId,
            ]);
        }
    }

    /**
     * 获取角色分页列表。
     */
    public function page(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));

        $query = AdminRole::where([]);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('name', '%' . $keyword . '%')
                    ->whereOr('code', 'like', '%' . $keyword . '%');
            });
        }

        return $query
            ->field('id,name,code,sort,status,data_scope,remark,create_time,update_time')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();
    }

    /**
     * 获取角色详情。
     */
    private function detail(int $id): array
    {
        return $this->findRole($id)->toArray();
    }

    /**
     * 查找角色，不存在时抛出业务异常。
     */
    private function findRole(int $id): AdminRole
    {
        $role = AdminRole::find($id);

        if (!$role) {
            throw new RuntimeException('角色不存在');
        }

        return $role;
    }

    /**
     * 过滤并校验角色表单数据。
     */
    private function filterPayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('请输入角色名称');
        }

        if ($code === '') {
            throw new RuntimeException('请输入角色标识');
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
            throw new RuntimeException('角色标识只能使用小写字母、数字、下划线，并以字母开头');
        }

        $dataScope = trim((string) ($data['data_scope'] ?? 'all'));

        if (!in_array($dataScope, ['all', 'self'], true)) {
            $dataScope = 'all';
        }

        return [
            'name'       => $name,
            'code'       => $code,
            'sort'       => max(0, (int) ($data['sort'] ?? 100)),
            'status'     => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
            'data_scope' => $dataScope,
            'remark'     => trim((string) ($data['remark'] ?? '')),
        ];
    }

    /**
     * 过滤菜单 ID，确保写入关联表的数据有效。
     */
    private function filterMenuIds(mixed $menuIds): array
    {
        if (!is_array($menuIds)) {
            return [];
        }

        $menuIds = array_values(array_unique(array_map('intval', $menuIds)));

        return array_values(array_filter($menuIds, fn(int $menuId) => $menuId > 0));
    }

    /**
     * 过滤批量操作 ID。
     */
    private function filterIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        return array_values(array_filter($ids, fn (int $id) => $id > 0));
    }
}
