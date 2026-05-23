<?php

namespace app\index\controller;

use app\common\base\IndexBase;
use app\common\service\index\ContentService;

class Index extends IndexBase
{
    /**
     * 前台首页。
     */
    public function index()
    {
        $home = (new ContentService())->home();
        $site = $home['site'];

        return view('index', [
            'home'             => $home,
            'site'             => $site,
            'navigation'       => $home['navigation'],
            'page_title'       => ($site['seo_title'] ?? '') ?: ($site['title'] ?? 'VTP'),
            'page_keywords'    => $site['seo_keywords'] ?? '',
            'page_description' => $site['seo_description'] ?? '',
        ]);
    }
}
