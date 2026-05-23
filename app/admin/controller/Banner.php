<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\BannerService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

/**
 * 后台幻灯控制器。
 */
class Banner extends AdminBase
{
    /**
     * 获取幻灯分页列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new BannerService())->page($this->request->get()));
    }

    /**
     * 获取幻灯表单选项。
     */
    public function options(): Response
    {
        return ApiResponse::success((new BannerService())->options());
    }

    /**
     * 新增幻灯。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new BannerService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新幻灯。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new BannerService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改幻灯状态。
     */
    public function status(int $id): Response
    {
        try {
            return ApiResponse::success((new BannerService())->changeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除幻灯。
     */
    public function delete(int $id): Response
    {
        try {
            (new BannerService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
