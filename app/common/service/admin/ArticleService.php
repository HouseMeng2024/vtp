<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\cache\IndexContentCache;
use app\common\model\Article;
use app\common\model\ContentCategory;
use RuntimeException;

/**
 * 后台文章服务。
 */
class ArticleService
{
    /**
     * 获取文章分页列表。
     */
    public function page(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $status = $filters['status'] ?? '';
        $categoryId = (int) ($filters['category_id'] ?? 0);
        $query = Article::where([]);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('title', '%' . $keyword . '%')
                    ->whereOr('subtitle', 'like', '%' . $keyword . '%')
                    ->whereOr('author', 'like', '%' . $keyword . '%');
            });
        }

        if ($status !== '' && $status !== null) {
            $query->where('status', (int) $status);
        }

        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        return $query
            ->field('id,category_id,title,subtitle,cover,summary,author,source,views,sort,status,publish_time,create_time,update_time')
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
    public function detail(int $id): array
    {
        return $this->findArticle($id)->toArray();
    }

    /**
     * 创建文章。
     */
    public function create(array $data): array
    {
        $article = Article::create($this->filterPayload($data));
        IndexContentCache::clearContentList('article');

        return $article->toArray();
    }

    /**
     * 更新文章。
     */
    public function update(int $id, array $data): array
    {
        $article = $this->findArticle($id);
        $article->save($this->filterPayload($data));
        IndexContentCache::clearContentList('article');

        return $this->findArticle($id)->toArray();
    }

    /**
     * 修改文章状态。
     */
    public function changeStatus(int $id, int $status): array
    {
        $article = $this->findArticle($id);
        $nextStatus = $status === 1 ? 1 : 0;
        $payload = ['status' => $nextStatus];

        if ($nextStatus === 1 && !$article->publish_time) {
            $payload['publish_time'] = date('Y-m-d H:i:s');
        }

        $article->save($payload);
        IndexContentCache::clearContentList('article');

        return $article->toArray();
    }

    /**
     * 删除文章。
     */
    public function delete(int $id): void
    {
        $this->findArticle($id)->delete();
        IndexContentCache::clearContentList('article');
    }

    /**
     * 查找文章。
     */
    private function findArticle(int $id): Article
    {
        $article = Article::find($id);

        if (!$article) {
            throw new RuntimeException('文章不存在');
        }

        return $article;
    }

    /**
     * 过滤并校验文章数据。
     */
    private function filterPayload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $categoryId = (int) ($data['category_id'] ?? 0);
        $status = (int) ($data['status'] ?? 1) === 1 ? 1 : 0;
        $publishTime = trim((string) ($data['publish_time'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('请输入文章标题');
        }

        if ($categoryId > 0 && !ContentCategory::find($categoryId)) {
            throw new RuntimeException('内容分类不存在');
        }

        return [
            'category_id'  => $categoryId,
            'title'        => $title,
            'subtitle'     => trim((string) ($data['subtitle'] ?? '')),
            'cover'        => trim((string) ($data['cover'] ?? '')),
            'summary'      => trim((string) ($data['summary'] ?? '')),
            'content'      => (string) ($data['content'] ?? ''),
            'author'       => trim((string) ($data['author'] ?? '')),
            'source'       => trim((string) ($data['source'] ?? '')),
            'source_url'   => trim((string) ($data['source_url'] ?? '')),
            'keywords'     => trim((string) ($data['keywords'] ?? '')),
            'description'  => trim((string) ($data['description'] ?? '')),
            'views'        => max(0, (int) ($data['views'] ?? 0)),
            'sort'         => max(0, (int) ($data['sort'] ?? 100)),
            'status'       => $status,
            'publish_time' => $publishTime !== '' ? $publishTime : ($status === 1 ? date('Y-m-d H:i:s') : null),
        ];
    }
}
