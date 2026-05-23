<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\cache\IndexContentCache;
use app\common\model\Banner;
use app\common\model\ContentCategory;
use RuntimeException;

/**
 * 后台幻灯服务。
 */
class BannerService
{
    /**
     * 获取幻灯分页列表。
     */
    public function page(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $position = trim((string) ($filters['position'] ?? ''));
        $status = $filters['status'] ?? '';
        $query = Banner::where([]);

        if ($keyword !== '') {
            $query->whereLike('title', '%' . $keyword . '%');
        }

        if ($position !== '') {
            $query->where('position', $position);
        }

        if ($status !== '' && $status !== null) {
            $query->where('status', (int) $status);
        }

        return $query
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();
    }

    /**
     * 获取幻灯表单选项。
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
            'positions' => [
                ['label' => '首页幻灯', 'value' => 'home'],
                ['label' => '文章页', 'value' => 'article'],
                ['label' => '侧边栏', 'value' => 'sidebar'],
            ],
            'links'     => $links,
        ];
    }

    /**
     * 创建幻灯。
     */
    public function create(array $data): array
    {
        $payload = $this->filterPayload($data);
        $banner = Banner::create($payload);
        IndexContentCache::clearBanner((string) $payload['position']);

        return $banner->toArray();
    }

    /**
     * 更新幻灯。
     */
    public function update(int $id, array $data): array
    {
        $banner = $this->findBanner($id);
        $oldPosition = (string) $banner->position;
        $payload = $this->filterPayload($data);
        $banner->save($payload);

        IndexContentCache::clearBanner($oldPosition);
        IndexContentCache::clearBanner((string) $payload['position']);

        return $this->findBanner($id)->toArray();
    }

    /**
     * 修改幻灯状态。
     */
    public function changeStatus(int $id, int $status): array
    {
        $banner = $this->findBanner($id);
        $banner->save(['status' => $status === 1 ? 1 : 0]);
        IndexContentCache::clearBanner((string) $banner->position);

        return $banner->toArray();
    }

    /**
     * 删除幻灯。
     */
    public function delete(int $id): void
    {
        $banner = $this->findBanner($id);
        IndexContentCache::clearBanner((string) $banner->position);
        $banner->delete();
    }

    /**
     * 查找幻灯。
     */
    private function findBanner(int $id): Banner
    {
        $banner = Banner::find($id);

        if (!$banner) {
            throw new RuntimeException('幻灯不存在');
        }

        return $banner;
    }

    /**
     * 过滤并校验幻灯数据。
     */
    private function filterPayload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $image = trim((string) ($data['image'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('请输入幻灯标题');
        }

        if ($image === '') {
            throw new RuntimeException('请选择幻灯图片');
        }

        $target = (string) ($data['target'] ?? '_self');

        return [
            'position'   => trim((string) ($data['position'] ?? 'home')),
            'title'      => $title,
            'subtitle'   => trim((string) ($data['subtitle'] ?? '')),
            'image'      => $image,
            'link_url'   => trim((string) ($data['link_url'] ?? '')),
            'target'     => in_array($target, ['_self', '_blank'], true) ? $target : '_self',
            'start_time' => trim((string) ($data['start_time'] ?? '')) ?: null,
            'end_time'   => trim((string) ($data['end_time'] ?? '')) ?: null,
            'sort'       => max(0, (int) ($data['sort'] ?? 100)),
            'status'     => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
            'remark'     => trim((string) ($data['remark'] ?? '')),
        ];
    }
}
