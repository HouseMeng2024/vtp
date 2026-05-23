<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\AdminMenu;
use app\common\model\AdminRoleMenu;
use RuntimeException;

class MenuService
{
    /**
     * 获取完整后台菜单树，用于菜单管理。
     */
    public function all(): array
    {
        return $this->buildTree(
            AdminMenu::where([])
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray()
        );
    }

    /**
     * 创建菜单并返回菜单详情。
     */
    public function create(array $data): array
    {
        $payload = $this->filterPayload($data);
        $menu = AdminMenu::create($payload);

        return $this->detail((int) $menu->id);
    }

    /**
     * 更新菜单并返回菜单详情。
     */
    public function update(int $id, array $data): array
    {
        $menu = $this->findMenu($id);
        $payload = $this->filterPayload($data, $id);
        $menu->save($payload);

        return $this->detail($id);
    }

    /**
     * 删除菜单，并校验是否存在子级菜单。
     */
    public function delete(int $id): void
    {
        $menu = $this->findMenu($id);

        if (AdminMenu::where('parent_id', $id)->find()) {
            throw new RuntimeException('存在子级菜单，不能删除');
        }

        $menu->delete();
        AdminRoleMenu::where('menu_id', $id)->delete();
    }

    /**
     * 获取当前管理员可访问的菜单树。
     */
    public function tree(array $adminUser): array
    {
        $roles = $adminUser['roles'] ?? [];

        if (in_array('super_admin', $roles, true)) {
            $menus = $this->allMenus();
        } else {
            $menus = $this->roleMenus((int) ($adminUser['id'] ?? 0));
        }

        return $this->buildTree($menus);
    }

    /**
     * 获取超级管理员可访问的全部有效菜单。
     */
    private function allMenus(): array
    {
        return AdminMenu::where([])
            ->whereIn('type', [1, 2])
            ->where('status', 1)
            ->where('visible', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取普通管理员通过角色授权得到的有效菜单。
     */
    private function roleMenus(int $userId): array
    {
        return AdminMenu::where([])
            ->alias('m')
            ->join('admin_role_menu rm', 'rm.menu_id = m.id')
            ->join('admin_user_role ur', 'ur.role_id = rm.role_id')
            ->join('admin_role r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->whereIn('m.type', [1, 2])
            ->where('m.status', 1)
            ->where('m.visible', 1)
            ->where('r.status', 1)
            ->whereNull('r.delete_time')
            ->field('m.*')
            ->distinct(true)
            ->order('m.sort', 'asc')
            ->order('m.id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 将菜单列表组装成树形结构。
     */
    private function buildTree(array $menus, int $parentId = 0): array
    {
        $tree = [];

        foreach ($menus as $menu) {
            if ((int) $menu['parent_id'] !== $parentId) {
                continue;
            }

            $children = $this->buildTree($menus, (int) $menu['id']);
            $item = [
                'id'         => (int) $menu['id'],
                'parent_id'  => (int) $menu['parent_id'],
                'type'       => (int) $menu['type'],
                'title'      => $menu['title'],
                'permission' => $menu['permission'],
                'path'       => $menu['path'],
                'component'  => $menu['component'],
                'icon'       => $menu['icon'],
                'sort'       => (int) $menu['sort'],
                'visible'    => (int) $menu['visible'],
                'status'     => (int) $menu['status'],
                'remark'     => $menu['remark'],
                'children'   => $children,
            ];

            $tree[] = $item;
        }

        return $tree;
    }

    /**
     * 获取菜单详情。
     */
    private function detail(int $id): array
    {
        return $this->findMenu($id)->toArray();
    }

    /**
     * 查找菜单，不存在时抛出业务异常。
     */
    private function findMenu(int $id): AdminMenu
    {
        $menu = AdminMenu::find($id);

        if (!$menu) {
            throw new RuntimeException('菜单不存在');
        }

        return $menu;
    }

    /**
     * 过滤并校验菜单表单数据。
     */
    private function filterPayload(array $data, int $id = 0): array
    {
        $parentId = max(0, (int) ($data['parent_id'] ?? 0));
        $type = (int) ($data['type'] ?? 2);
        $title = trim((string) ($data['title'] ?? ''));

        if (!in_array($type, [1, 2, 3], true)) {
            throw new RuntimeException('菜单类型错误');
        }

        if ($title === '') {
            throw new RuntimeException('请输入菜单名称');
        }

        if ($id > 0 && $parentId === $id) {
            throw new RuntimeException('父级不能选择自身');
        }

        if ($parentId > 0 && !AdminMenu::find($parentId)) {
            throw new RuntimeException('父级菜单不存在');
        }

        return [
            'parent_id'  => $parentId,
            'type'       => $type,
            'title'      => $title,
            'permission' => trim((string) ($data['permission'] ?? '')),
            'path'       => trim((string) ($data['path'] ?? '')),
            'component'  => trim((string) ($data['component'] ?? '')),
            'icon'       => trim((string) ($data['icon'] ?? '')),
            'sort'       => max(0, (int) ($data['sort'] ?? 100)),
            'visible'    => (int) ($data['visible'] ?? 1) === 1 ? 1 : 0,
            'status'     => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
            'remark'     => trim((string) ($data['remark'] ?? '')),
        ];
    }
}
