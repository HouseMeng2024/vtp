<?php

return [
    app\admin\middleware\Auth::class,
    app\admin\middleware\OperateLog::class,
    app\admin\middleware\Permission::class,
];
