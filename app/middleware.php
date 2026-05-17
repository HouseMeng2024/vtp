<?php
// 全局中间件定义文件
return [
    // 全局请求缓存
    // \think\middleware\CheckRequestCache::class,
    // 多语言加载
    app\common\middleware\Locale::class,
    // Session初始化
    // \think\middleware\SessionInit::class
];
