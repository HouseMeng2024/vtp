<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\ContentCategoryService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

/**
 * 后台内容分类控制器。
 */
class ContentCategory extends AdminBase
{
    /**
     * 获取内容分类树。
     */
    public function index(): Response
    {
        return ApiResponse::success((new ContentCategoryService())->tree($this->request->get()));
    }

    /**
     * 新增内容分类。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new ContentCategoryService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新内容分类。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new ContentCategoryService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改内容分类状态。
     */
    public function status(int $id): Response
    {
        try {
            return ApiResponse::success((new ContentCategoryService())->changeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除内容分类。
     */
    public function delete(int $id): Response
    {
        try {
            (new ContentCategoryService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}

