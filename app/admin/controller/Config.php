<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\ConfigService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class Config extends AdminBase
{
    /**
     * 获取公开站点配置。
     */
    public function site(): Response
    {
        return ApiResponse::success((new ConfigService())->site());
    }

    /**
     * 获取项目配置列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new ConfigService())->groups());
    }

    /**
     * 保存项目配置。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->save($this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
