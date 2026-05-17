<?php

namespace app\index\controller;

use app\common\base\IndexBase;

class Index extends IndexBase
{
    /**
     * 前台首页。
     */
    public function index()
    {
        $config = config('index', []);
        $config['page_title'] = ($config['seo_title'] ?? '') ?: ($config['title'] ?? 'VTP');

        return view('index', [
            'config' => $config,
        ]);
    }
}
