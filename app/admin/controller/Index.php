<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\DashboardService;
use app\common\support\ApiResponse;
use think\Response;

class Index extends AdminBase
{
    /**
     * 后台应用健康检查入口。
     */
    public function index(): Response
    {
        return ApiResponse::success([
            'app'  => 'admin',
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 获取控制台统计数据。
     */
    public function dashboard(): Response
    {
        return ApiResponse::success((new DashboardService())->summary());
    }
}
