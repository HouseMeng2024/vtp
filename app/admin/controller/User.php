<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\UserService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class User extends AdminBase
{
    /**
     * 获取管理员分页列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new UserService())->page(array_merge($this->request->get(), $this->scopeContext())));
    }

    /**
     * 新增管理员。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new UserService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新管理员资料和角色。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new UserService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改管理员启用状态。
     */
    public function status(int $id): Response
    {
        try {
            return ApiResponse::success((new UserService())->changeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 批量修改管理员启用状态。
     */
    public function batchStatus(): Response
    {
        try {
            (new UserService())->batchChangeStatus(
                (array) $this->request->param('ids', []),
                (int) $this->request->param('status', 0)
            );
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除管理员，并清理管理员角色关联。
     */
    public function delete(int $id): Response
    {
        try {
            (new UserService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 批量删除管理员。
     */
    public function batchDelete(): Response
    {
        try {
            (new UserService())->batchDelete((array) $this->request->param('ids', []));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 强制管理员下线。
     */
    public function forceLogout(int $id): Response
    {
        try {
            (new UserService())->forceLogout($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
