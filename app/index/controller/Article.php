<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\common\base\IndexBase;
use app\common\service\index\ContentService;
use RuntimeException;
use think\Response;

/**
 * 前台文章控制器。
 */
class Article extends IndexBase
{
    /**
     * 文章列表页。
     */
    public function index(): Response
    {
        $service = new ContentService();
        $site = $service->siteConfig();

        return view('article/index', [
            'site'       => $site,
            'navigation' => $service->navigation('main'),
            'categories' => $service->categories(),
            'articles'   => $service->articles($this->request->get()),
            'page_title' => '文章资讯 - ' . (($site['seo_title'] ?? '') ?: ($site['title'] ?? 'VTP')),
            'page_keywords' => $site['seo_keywords'] ?? '',
            'page_description' => $site['seo_description'] ?? '',
        ]);
    }

    /**
     * 文章详情页。
     */
    public function detail(int $id): Response
    {
        $service = new ContentService();
        $site = $service->siteConfig();

        try {
            $article = $service->articleDetail($id);

            return view('article/detail', [
                'site'       => $site,
                'navigation' => $service->navigation('main'),
                'article'    => $article,
                'page_title' => $article['title'] . ' - ' . (($site['seo_title'] ?? '') ?: ($site['title'] ?? 'VTP')),
                'page_keywords' => $article['keywords'] ?: ($site['seo_keywords'] ?? ''),
                'page_description' => $article['description'] ?: ($article['summary'] ?? ''),
            ]);
        } catch (RuntimeException $exception) {
            abort(404, $exception->getMessage());
        }
    }
}
