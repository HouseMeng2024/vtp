<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\AdminLoginLog;
use app\common\model\AdminOperateLog;
use RuntimeException;

class LogService
{
    /**
     * 获取登录日志分页列表。
     */
    public function loginPage(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $status = $filters['status'] ?? '';

        $query = AdminLoginLog::where([]);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('username', '%' . $keyword . '%')
                    ->whereOr('ip', 'like', '%' . $keyword . '%');
            });
        }

        if ($status !== '' && $status !== null) {
            $query->where('status', (int) $status);
        }

        return $query
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();
    }

    /**
     * 获取操作日志分页列表。
     */
    public function operatePage(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $method = strtoupper(trim((string) ($filters['method'] ?? '')));

        $query = AdminOperateLog::where([]);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('username', '%' . $keyword . '%')
                    ->whereOr('path', 'like', '%' . $keyword . '%')
                    ->whereOr('ip', 'like', '%' . $keyword . '%');
            });
        }

        if ($method !== '') {
            $query->where('method', $method);
        }

        return $query
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();
    }

    /**
     * 批量删除登录日志。
     */
    public function batchDeleteLogin(array $ids): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException('请选择登录日志');
        }

        AdminLoginLog::whereIn('id', $ids)->delete();
    }

    /**
     * 批量删除操作日志。
     */
    public function batchDeleteOperate(array $ids): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException('请选择操作日志');
        }

        AdminOperateLog::whereIn('id', $ids)->delete();
    }

    /**
     * 清空指定类型日志。
     */
    public function clear(string $type): void
    {
        if ($type === 'login') {
            AdminLoginLog::where('id', '>', 0)->delete();
            return;
        }

        if ($type === 'operate') {
            AdminOperateLog::where('id', '>', 0)->delete();
            return;
        }

        throw new RuntimeException('日志类型错误');
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
