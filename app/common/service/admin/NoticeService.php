<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\admin\AdminNotice;
use app\common\model\admin\AdminNoticeRead;
use app\common\model\admin\AdminUserRole;
use RuntimeException;

class NoticeService
{
    /**
     * 获取当前管理员最近有效消息。
     */
    public function recent(int $userId): array
    {
        $rows = AdminNotice::where('status', 1)
            ->order('id', 'desc')
            ->limit(100)
            ->select()
            ->toArray();
        $roleIds = $this->userRoleIds($userId);
        $rows = array_values(array_filter($rows, fn (array $row) => $this->canReceive($row, $userId, $roleIds)));
        $rows = array_slice($rows, 0, 20);
        $readIds = AdminNoticeRead::where('user_id', $userId)->column('notice_id');

        foreach ($rows as &$row) {
            $row['read'] = in_array((int) $row['id'], array_map('intval', $readIds), true) ? 1 : 0;
            $row = $this->formatNotice($row);
        }

        return [
            'items'        => $rows,
            'unread_count' => count(array_filter($rows, fn (array $row) => (int) $row['read'] === 0)),
        ];
    }

    /**
     * 获取消息通知分页列表。
     */
    public function page(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $status = $filters['status'] ?? '';

        $query = AdminNotice::where([]);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('title', '%' . $keyword . '%')
                    ->whereOr('content', 'like', '%' . $keyword . '%');
            });
        }

        if ($status !== '' && $status !== null) {
            $query->where('status', (int) $status);
        }

        $result = $query
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();

        $result['data'] = array_map(fn (array $row) => $this->formatNotice($row), $result['data'] ?? []);

        return $result;
    }

    /**
     * 新增消息通知。
     */
    public function create(array $data): array
    {
        $notice = AdminNotice::create($this->filterPayload($data));

        return $this->formatNotice($notice->toArray());
    }

    /**
     * 更新消息通知。
     */
    public function update(int $id, array $data): array
    {
        $notice = $this->findNotice($id);
        $notice->save($this->filterPayload($data));

        return $this->formatNotice($notice->toArray());
    }

    /**
     * 修改消息通知启用状态。
     */
    public function changeStatus(int $id, int $status): array
    {
        $notice = $this->findNotice($id);
        $notice->save(['status' => $status === 1 ? 1 : 0]);

        return $this->formatNotice($notice->toArray());
    }

    /**
     * 删除消息通知和已读记录。
     */
    public function delete(int $id): void
    {
        $notice = $this->findNotice($id);
        $notice->delete();
        AdminNoticeRead::where('notice_id', $id)->delete();
    }

    /**
     * 标记单条消息已读。
     */
    public function read(int $userId, int $noticeId): void
    {
        $notice = AdminNotice::find($noticeId);

        if (!$notice || (int) $notice->status !== 1) {
            throw new RuntimeException('消息不存在');
        }

        if (!$this->canReceive($notice->toArray(), $userId, $this->userRoleIds($userId))) {
            throw new RuntimeException('消息不存在');
        }

        if (AdminNoticeRead::where('user_id', $userId)->where('notice_id', $noticeId)->find()) {
            return;
        }

        AdminNoticeRead::create([
            'user_id'   => $userId,
            'notice_id' => $noticeId,
        ]);
    }

    /**
     * 标记全部消息已读。
     */
    public function readAll(int $userId): void
    {
        $roleIds = $this->userRoleIds($userId);
        $rows = AdminNotice::where('status', 1)->field('id,scope_type,scope_ids')->select()->toArray();
        $ids = array_map(
            fn (array $row) => (int) $row['id'],
            array_values(array_filter($rows, fn (array $row) => $this->canReceive($row, $userId, $roleIds)))
        );

        foreach ($ids as $id) {
            $this->read($userId, (int) $id);
        }
    }

    /**
     * 查找消息通知，不存在时抛出业务异常。
     */
    private function findNotice(int $id): AdminNotice
    {
        $notice = AdminNotice::find($id);

        if (!$notice) {
            throw new RuntimeException('消息不存在');
        }

        return $notice;
    }

    /**
     * 过滤并校验消息表单数据。
     */
    private function filterPayload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'info'));

        if ($title === '') {
            throw new RuntimeException('请输入消息标题');
        }

        if (!in_array($type, ['primary', 'success', 'info', 'warning', 'danger'], true)) {
            $type = 'info';
        }

        $scopeType = trim((string) ($data['scope_type'] ?? 'all'));

        if (!in_array($scopeType, ['all', 'role', 'user'], true)) {
            $scopeType = 'all';
        }

        $scopeIds = $scopeType === 'all' ? '' : $this->normalizeScopeIds($data['scope_ids'] ?? []);

        if ($scopeType !== 'all' && $scopeIds === '') {
            throw new RuntimeException('请选择接收对象');
        }

        return [
            'title'      => $title,
            'content'    => $content,
            'type'       => $type,
            'scope_type' => $scopeType,
            'scope_ids'  => $scopeIds,
            'popup'      => (int) ($data['popup'] ?? 0) === 1 ? 1 : 0,
            'status'     => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
        ];
    }

    /**
     * 获取管理员当前绑定的角色 ID。
     */
    private function userRoleIds(int $userId): array
    {
        return array_map('intval', AdminUserRole::where('user_id', $userId)->column('role_id'));
    }

    /**
     * 判断当前管理员是否在消息接收范围内。
     */
    private function canReceive(array $notice, int $userId, array $roleIds): bool
    {
        $scopeType = (string) ($notice['scope_type'] ?? 'all');

        if ($scopeType === 'all') {
            return true;
        }

        $scopeIds = $this->parseScopeIds((string) ($notice['scope_ids'] ?? ''));

        if ($scopeType === 'user') {
            return in_array($userId, $scopeIds, true);
        }

        if ($scopeType === 'role') {
            return count(array_intersect($roleIds, $scopeIds)) > 0;
        }

        return false;
    }

    /**
     * 规范化接收对象 ID，数据库中用逗号包裹，避免 LIKE 匹配串号。
     */
    private function normalizeScopeIds(mixed $ids): string
    {
        if (is_string($ids)) {
            $ids = explode(',', trim($ids, ','));
        }

        if (!is_array($ids)) {
            return '';
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, fn (int $id) => $id > 0));

        return $ids ? ',' . implode(',', $ids) . ',' : '';
    }

    /**
     * 解析数据库中的接收对象 ID。
     */
    private function parseScopeIds(string $ids): array
    {
        if ($ids === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', trim($ids, ',')))));
    }

    /**
     * 组装前端需要的消息通知结构。
     */
    private function formatNotice(array $notice): array
    {
        $notice['scope_type'] = $notice['scope_type'] ?? 'all';
        $notice['scope_ids'] = $this->parseScopeIds((string) ($notice['scope_ids'] ?? ''));
        $notice['popup'] = (int) ($notice['popup'] ?? 0);

        return $notice;
    }
}
