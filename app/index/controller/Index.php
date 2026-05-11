<?php

namespace app\index\controller;

use app\common\base\IndexBase;

class Index extends IndexBase
{
    /**
     * 前台首页默认响应。
     */
    public function index()
    {
        return 'VTP';
    }
}
