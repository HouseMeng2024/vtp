<?php

namespace app\index\controller;

use app\common\base\IndexBase;

class Index extends IndexBase
{
    /**
     * 渲染前台首页。
     */
    public function index()
    {
        return view();
    }

    /**
     * ThinkPHP 默认示例接口。
     */
    public function hello($name = 'ThinkPHP8')
    {
        return 'hello,' . $name;
    }
}
