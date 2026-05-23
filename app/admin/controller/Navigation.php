<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\NavigationService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

/**
 * 后台导航控制器。
 */
class Navigation extends AdminBase
{
    /**
     * 获取导航树。
     */
    public function index(): Response
    {
        return ApiResponse::success((new NavigationService())->tree($this->request->get()));
    }

    /**
     * 获取导航表单选项。
     */
    public function options(): Response
    {
        return ApiResponse::success((new NavigationService())->options());
    }

    /**
     * 新增导航。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new NavigationService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新导航。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new NavigationService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改导航状态。
     */
    public function status(int $id): Response
    {
        try {
            return ApiResponse::success((new NavigationService())->changeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除导航。
     */
    public function delete(int $id): Response
    {
        try {
            (new NavigationService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
