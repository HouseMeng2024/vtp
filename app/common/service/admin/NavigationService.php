<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\cache\IndexContentCache;
use app\common\model\Navigation;
use app\common\model\ContentCategory;
use RuntimeException;

/**
 * 后台导航服务。
 */
class NavigationService
{
    /**
     * 获取导航树。
     */
    public function tree(array $filters): array
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $group = trim((string) ($filters['group'] ?? ''));
        $query = Navigation::where([]);

        if ($keyword !== '') {
            $query->whereLike('title', '%' . $keyword . '%');
        }

        if ($group !== '') {
            $query->where('group', $group);
        }

        return $this->buildTree(
            $query->order('sort', 'asc')->order('id', 'asc')->select()->toArray()
        );
    }

    /**
     * 获取导航表单选项。
     */
    public function options(): array
    {
        $links = [
            [
                'label' => '前台首页',
                'url'   => '/',
            ],
            [
                'label' => '文章列表',
                'url'   => '/index/article/index.html',
            ],
        ];

        $categories = ContentCategory::where('type', 'article')
            ->where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->field('id,name')
            ->select()
            ->toArray();

        foreach ($categories as $category) {
            $links[] = [
                'label' => '文章分类 / ' . $category['name'],
                'url'   => '/index/article/index/category_id/' . $category['id'] . '.html',
            ];
        }

        return [
            'groups' => [
                ['label' => '主导航', 'value' => 'main'],
                ['label' => '页脚导航', 'value' => 'footer'],
            ],
            'links'  => $links,
        ];
    }

    /**
     * 创建导航。
     */
    public function create(array $data): array
    {
        $payload = $this->filterPayload($data);
        $navigation = Navigation::create($payload);
        IndexContentCache::clearNavigation((string) $payload['group']);

        return $navigation->toArray();
    }

    /**
     * 更新导航。
     */
    public function update(int $id, array $data): array
    {
        $navigation = $this->findNavigation($id);
        $oldGroup = (string) $navigation->group;
        $payload = $this->filterPayload($data, $id);
        $navigation->save($payload);

        IndexContentCache::clearNavigation($oldGroup);
        IndexContentCache::clearNavigation((string) $payload['group']);

        return $this->findNavigation($id)->toArray();
    }

    /**
     * 修改导航状态。
     */
    public function changeStatus(int $id, int $status): array
    {
        $navigation = $this->findNavigation($id);
        $navigation->save(['status' => $status === 1 ? 1 : 0]);
        IndexContentCache::clearNavigation((string) $navigation->group);

        return $navigation->toArray();
    }

    /**
     * 删除导航。
     */
    public function delete(int $id): void
    {
        $navigation = $this->findNavigation($id);

        if (Navigation::where('parent_id', $id)->find()) {
            throw new RuntimeException('存在子级导航，不能删除');
        }

        IndexContentCache::clearNavigation((string) $navigation->group);
        $navigation->delete();
    }

    /**
     * 将导航列表组装为树。
     */
    private function buildTree(array $rows, int $parentId = 0): array
    {
        $tree = [];

        foreach ($rows as $row) {
            if ((int) $row['parent_id'] !== $parentId) {
                continue;
            }

            $row['children'] = $this->buildTree($rows, (int) $row['id']);
            $tree[] = $row;
        }

        return $tree;
    }

    /**
     * 查找导航。
     */
    private function findNavigation(int $id): Navigation
    {
        $navigation = Navigation::find($id);

        if (!$navigation) {
            throw new RuntimeException('导航不存在');
        }

        return $navigation;
    }

    /**
     * 过滤并校验导航数据。
     */
    private function filterPayload(array $data, int $id = 0): array
    {
        $parentId = max(0, (int) ($data['parent_id'] ?? 0));
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('请输入导航名称');
        }

        if ($id > 0 && $parentId === $id) {
            throw new RuntimeException('父级不能选择自身');
        }

        if ($parentId > 0 && !Navigation::find($parentId)) {
            throw new RuntimeException('父级导航不存在');
        }

        $target = (string) ($data['target'] ?? '_self');

        return [
            'parent_id' => $parentId,
            'group'     => trim((string) ($data['group'] ?? 'main')),
            'title'     => $title,
            'url'       => trim((string) ($data['url'] ?? '')),
            'target'    => in_array($target, ['_self', '_blank'], true) ? $target : '_self',
            'icon'      => trim((string) ($data['icon'] ?? '')),
            'sort'      => max(0, (int) ($data['sort'] ?? 100)),
            'status'    => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
            'remark'    => trim((string) ($data['remark'] ?? '')),
        ];
    }
}
