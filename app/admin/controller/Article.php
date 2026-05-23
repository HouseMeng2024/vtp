<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\ArticleService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

/**
 * 后台文章控制器。
 */
class Article extends AdminBase
{
    /**
     * 获取文章分页列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new ArticleService())->page($this->request->get()));
    }

    /**
     * 获取文章详情。
     */
    public function detail(int $id): Response
    {
        try {
            return ApiResponse::success((new ArticleService())->detail($id));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 新增文章。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new ArticleService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新文章。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new ArticleService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改文章状态。
     */
    public function status(int $id): Response
    {
        try {
            return ApiResponse::success((new ArticleService())->changeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除文章。
     */
    public function delete(int $id): Response
    {
        try {
            (new ArticleService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
