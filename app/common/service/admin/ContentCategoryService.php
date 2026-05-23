<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\cache\IndexContentCache;
use app\common\model\Article;
use app\common\model\ContentCategory;
use app\common\model\DictData;
use app\common\model\DictType;
use RuntimeException;

/**
 * 后台内容分类服务。
 */
class ContentCategoryService
{
    /**
     * 获取内容分类树。
     */
    public function tree(array $filters): array
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $type = trim((string) ($filters['type'] ?? ''));

        $query = ContentCategory::where([]);

        if ($keyword !== '') {
            $query->whereLike('name', '%' . $keyword . '%');
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        return $this->buildTree(
            $query->order('sort', 'asc')->order('id', 'asc')->select()->toArray()
        );
    }

    /**
     * 创建内容分类。
     */
    public function create(array $data): array
    {
        $payload = $this->filterPayload($data);
        $category = ContentCategory::create($payload);

        IndexContentCache::clearCategory((string) $payload['type']);

        return $category->toArray();
    }

    /**
     * 更新内容分类。
     */
    public function update(int $id, array $data): array
    {
        $category = $this->findCategory($id);
        $oldType = (string) $category->type;
        $payload = $this->filterPayload($data, $id);
        $category->save($payload);

        IndexContentCache::clearCategory($oldType);
        IndexContentCache::clearCategory((string) $payload['type']);
        IndexContentCache::clearContentList($oldType);
        IndexContentCache::clearContentList((string) $payload['type']);

        return $this->findCategory($id)->toArray();
    }

    /**
     * 修改内容分类状态。
     */
    public function changeStatus(int $id, int $status): array
    {
        $category = $this->findCategory($id);
        $category->save(['status' => $status === 1 ? 1 : 0]);
        IndexContentCache::clearCategory((string) $category->type);
        IndexContentCache::clearContentList((string) $category->type);

        return $category->toArray();
    }

    /**
     * 删除内容分类。
     */
    public function delete(int $id): void
    {
        $category = $this->findCategory($id);

        if (ContentCategory::where('parent_id', $id)->find()) {
            throw new RuntimeException('存在子级分类，不能删除');
        }

        if (Article::where('category_id', $id)->find()) {
            throw new RuntimeException('分类下存在文章，不能删除');
        }

        IndexContentCache::clearCategory((string) $category->type);
        IndexContentCache::clearContentList((string) $category->type);
        $category->delete();
    }

    /**
     * 将分类列表组装为树。
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
     * 查找内容分类。
     */
    private function findCategory(int $id): ContentCategory
    {
        $category = ContentCategory::find($id);

        if (!$category) {
            throw new RuntimeException('内容分类不存在');
        }

        return $category;
    }

    /**
     * 过滤并校验内容分类数据。
     */
    private function filterPayload(array $data, int $id = 0): array
    {
        $parentId = max(0, (int) ($data['parent_id'] ?? 0));
        $name = trim((string) ($data['name'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'article'));

        if ($name === '') {
            throw new RuntimeException('请输入分类名称');
        }

        if ($type === '') {
            throw new RuntimeException('请输入分类类型');
        }

        if (!$this->isValidContentModel($type)) {
            throw new RuntimeException('内容模型不存在或已禁用');
        }

        if ($id > 0 && $parentId === $id) {
            throw new RuntimeException('父级不能选择自身');
        }

        if ($parentId > 0) {
            $parent = ContentCategory::find($parentId);

            if (!$parent) {
                throw new RuntimeException('父级分类不存在');
            }

            if ((string) $parent->type !== $type) {
                throw new RuntimeException('子级分类必须和父级分类使用同一内容模型');
            }
        }

        return [
            'parent_id'   => $parentId,
            'type'        => $type,
            'name'        => $name,
            'slug'        => trim((string) ($data['slug'] ?? '')),
            'cover'       => trim((string) ($data['cover'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'sort'        => max(0, (int) ($data['sort'] ?? 100)),
            'status'      => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
        ];
    }

    /**
     * 判断内容模型是否为启用的字典项。
     */
    private function isValidContentModel(string $type): bool
    {
        $dictType = DictType::where('type', 'content_model')->where('status', 1)->find();

        if (!$dictType) {
            return false;
        }

        return DictData::where('type_id', (int) $dictType->id)
            ->where('value', $type)
            ->where('status', 1)
            ->find() !== null;
    }
}
