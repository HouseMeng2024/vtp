<?php
declare (strict_types = 1);

namespace app\common\service\index;

use app\common\cache\IndexContentCache;
use app\common\model\Article;
use app\common\model\Banner;
use app\common\model\ContentCategory;
use app\common\model\Navigation;
use RuntimeException;

/**
 * 前台内容服务。
 */
class ContentService
{
    /**
     * 获取首页展示数据。
     */
    public function home(): array
    {
        return [
            'site'       => $this->siteConfig(),
            'navigation' => $this->navigation('main'),
            'footer_nav' => $this->navigation('footer'),
            'banners'    => $this->banners('home'),
            'categories' => $this->categories(),
            'articles'   => $this->articles(['limit' => 8])['data'],
        ];
    }

    /**
     * 获取前台站点配置。
     */
    public function siteConfig(): array
    {
        $config = config('index', []);
        $title = (string) ($config['title'] ?? 'VTP');

        return [
            'title'           => $title,
            'logo'            => (string) ($config['logo'] ?? ''),
            'seo_title'       => (string) (($config['seo_title'] ?? '') ?: $title),
            'seo_keywords'    => (string) ($config['seo_keywords'] ?? ''),
            'seo_description' => (string) ($config['seo_description'] ?? ''),
        ];
    }

    /**
     * 获取有效导航。
     */
    public function navigation(string $group = 'main'): array
    {
        return IndexContentCache::remember('navigation:' . $group, function () use ($group) {
            $rows = Navigation::where('group', $group)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->buildTree($rows);
        });
    }

    /**
     * 获取有效幻灯。
     */
    public function banners(string $position = 'home'): array
    {
        return IndexContentCache::remember('banner:' . $position, function () use ($position) {
            $now = date('Y-m-d H:i:s');

            return Banner::where('position', $position)
                ->where('status', 1)
                ->where(function ($query) use ($now) {
                    $query->whereNull('start_time')->whereOr('start_time', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_time')->whereOr('end_time', '>=', $now);
                })
                ->order('sort', 'asc')
                ->order('id', 'desc')
                ->select()
                ->toArray();
        });
    }

    /**
     * 获取有效文章分类树。
     */
    public function categories(string $type = 'article'): array
    {
        return IndexContentCache::remember('category:' . $type, function () use ($type) {
            $rows = ContentCategory::where('type', $type)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->buildTree($rows);
        });
    }

    /**
     * 获取文章分页列表。
     */
    public function articles(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(50, max(1, (int) ($filters['limit'] ?? 10)));
        $categoryId = (int) ($filters['category_id'] ?? 0);
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $query = Article::where('status', 1);

        if ($categoryId > 0) {
            $query->whereIn('category_id', $this->categoryIdsWithChildren($categoryId));
        }

        if ($keyword !== '') {
            $query->whereLike('title', '%' . $keyword . '%');
        }

        return $query
            ->field('id,category_id,title,subtitle,cover,summary,author,source,views,publish_time,create_time')
            ->order('sort', 'asc')
            ->order('publish_time', 'desc')
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();
    }

    /**
     * 获取文章详情。
     */
    public function articleDetail(int $id): array
    {
        $article = Article::where('status', 1)->find($id);

        if (!$article) {
            throw new RuntimeException('文章不存在或未发布');
        }

        $article->save([
            'views' => (int) $article->views + 1,
        ]);

        return $article->toArray();
    }

    /**
     * 将列表组装成树。
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
     * 获取分类及其全部子分类 ID。
     */
    private function categoryIdsWithChildren(int $categoryId): array
    {
        $rows = ContentCategory::where('type', 'article')
            ->where('status', 1)
            ->field('id,parent_id')
            ->select()
            ->toArray();

        return $this->collectCategoryIds($rows, $categoryId);
    }

    /**
     * 递归收集分类 ID。
     */
    private function collectCategoryIds(array $rows, int $parentId): array
    {
        $ids = [$parentId];

        foreach ($rows as $row) {
            if ((int) $row['parent_id'] !== $parentId) {
                continue;
            }

            $ids = array_merge($ids, $this->collectCategoryIds($rows, (int) $row['id']));
        }

        return array_values(array_unique($ids));
    }

}
