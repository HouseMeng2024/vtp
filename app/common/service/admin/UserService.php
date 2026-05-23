<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\AdminUser;
use app\common\model\AdminUserRole;
use RuntimeException;

class UserService
{
    /**
     * 获取管理员分页列表，并附带角色 ID。
     */
    public function page(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));

        $query = AdminUser::where([]);
        $this->applyDataScope($query, $filters, 'id');

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('username', '%' . $keyword . '%')
                    ->whereOr('nickname', 'like', '%' . $keyword . '%')
                    ->whereOr('mobile', 'like', '%' . $keyword . '%');
            });
        }

        $result = $query
            ->field('id,username,nickname,avatar,mobile,email,status,last_login_ip,last_login_time,create_time')
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();

        $items = $result['data'] ?? [];
        $roleMap = $this->roleMap(array_column($items, 'id'));

        foreach ($items as &$item) {
            $item['role_ids'] = $roleMap[(int) $item['id']] ?? [];
        }

        $result['data'] = $items;

        return $result;
    }

    /**
     * 创建管理员并同步角色。
     */
    public function create(array $data): array
    {
        $payload = $this->filterPayload($data, true);
        $roleIds = $this->filterRoleIds($data['role_ids'] ?? []);

        if (AdminUser::where('username', $payload['username'])->find()) {
            throw new RuntimeException('账号已存在');
        }

        $user = AdminUser::create($payload);
        $this->syncRoles((int) $user->id, $roleIds);

        return $this->detail((int) $user->id);
    }

    /**
     * 更新管理员资料并同步角色。
     */
    public function update(int $id, array $data): array
    {
        $user = $this->findUser($id);
        $payload = $this->filterPayload($data, false);
        $roleIds = $this->filterRoleIds($data['role_ids'] ?? []);

        $exists = AdminUser::where('username', $payload['username'])
            ->where('id', '<>', $id)
            ->find();

        if ($exists) {
            throw new RuntimeException('账号已存在');
        }

        $user->save($payload);
        $this->syncRoles($id, $roleIds);

        if (isset($payload['password'])) {
            (new AuthService())->revokeUserTokens($id);
        }

        return $this->detail($id);
    }

    /**
     * 修改管理员启用状态。
     */
    public function changeStatus(int $id, int $status): array
    {
        $user = $this->findUser($id);
        $status = $status === 1 ? 1 : 0;
        $user->save(['status' => $status]);

        if ($status === 0) {
            (new AuthService())->revokeUserTokens($id);
        }

        return $this->detail($id);
    }

    /**
     * 批量修改管理员启用状态。
     */
    public function batchChangeStatus(array $ids, int $status): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException('请选择管理员');
        }

        $status = $status === 1 ? 1 : 0;
        AdminUser::whereIn('id', $ids)->update(['status' => $status]);

        if ($status === 0) {
            $authService = new AuthService();

            foreach ($ids as $id) {
                $authService->revokeUserTokens($id);
            }
        }
    }

    /**
     * 删除管理员，并清理管理员角色关联。
     */
    public function delete(int $id): void
    {
        if ($id === 1) {
            throw new RuntimeException('默认超级管理员不能删除');
        }

        $user = $this->findUser($id);
        $user->delete();
        AdminUserRole::where('user_id', $id)->delete();
    }

    /**
     * 批量删除管理员，并清理管理员角色关联。
     */
    public function batchDelete(array $ids): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException('请选择管理员');
        }

        if (in_array(1, $ids, true)) {
            throw new RuntimeException('默认超级管理员不能删除');
        }

        foreach ($ids as $id) {
            $this->findUser($id)->delete();
        }

        AdminUserRole::whereIn('user_id', $ids)->delete();
    }

    /**
     * 强制指定管理员下线。
     */
    public function forceLogout(int $id): void
    {
        $this->findUser($id);
        (new AuthService())->revokeUserTokens($id);
    }

    /**
     * 获取管理员详情，并隐藏密码字段。
     */
    private function detail(int $id): array
    {
        $user = $this->findUser($id)->toArray();
        $user['role_ids'] = $this->roleMap([$id])[$id] ?? [];

        unset($user['password']);

        return $user;
    }

    /**
     * 查找管理员，不存在时抛出业务异常。
     */
    private function findUser(int $id): AdminUser
    {
        $user = AdminUser::find($id);

        if (!$user) {
            throw new RuntimeException('管理员不存在');
        }

        return $user;
    }

    /**
     * 过滤并校验管理员表单数据。
     */
    private function filterPayload(array $data, bool $isCreate): array
    {
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '') {
            throw new RuntimeException('请输入账号');
        }

        if ($isCreate && $password === '') {
            throw new RuntimeException('请输入密码');
        }

        $payload = [
            'username' => $username,
            'nickname' => trim((string) ($data['nickname'] ?? '')),
            'mobile'   => trim((string) ($data['mobile'] ?? '')),
            'email'    => trim((string) ($data['email'] ?? '')),
            'status'   => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
        ];

        if ($password !== '') {
            if (strlen($password) < 6) {
                throw new RuntimeException('密码至少 6 位');
            }

            $payload['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        return $payload;
    }

    /**
     * 过滤角色 ID，确保写入关联表的数据有效。
     */
    private function filterRoleIds(mixed $roleIds): array
    {
        if (!is_array($roleIds)) {
            return [];
        }

        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        return array_values(array_filter($roleIds, fn (int $roleId) => $roleId > 0));
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

    /**
     * 同步管理员角色关系。
     */
    private function syncRoles(int $userId, array $roleIds): void
    {
        AdminUserRole::where('user_id', $userId)->delete();

        foreach ($roleIds as $roleId) {
            AdminUserRole::create([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
        }
    }

    /**
     * 批量获取管理员和角色 ID 的映射关系。
     */
    private function roleMap(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if (!$userIds) {
            return [];
        }

        $rows = AdminUserRole::whereIn('user_id', $userIds)
            ->field('user_id,role_id')
            ->select()
            ->toArray();

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['user_id']][] = (int) $row['role_id'];
        }

        return $map;
    }

    /**
     * 根据数据权限上下文限制查询范围。
     */
    private function applyDataScope(mixed $query, array $filters, string $ownerField): void
    {
        if (($filters['_data_scope'] ?? 'all') === 'self') {
            $query->where($ownerField, (int) ($filters['_admin_id'] ?? 0));
        }
    }
}
