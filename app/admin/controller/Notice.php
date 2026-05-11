<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\NoticeService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class Notice extends AdminBase
{
    /**
     * 获取消息通知分页列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new NoticeService())->page($this->request->get()));
    }

    /**
     * 获取当前管理员最近有效消息。
     */
    public function recent(): Response
    {
        return ApiResponse::success((new NoticeService())->recent($this->adminId()));
    }

    /**
     * 新增消息通知。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new NoticeService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新消息通知。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new NoticeService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改消息通知启用状态。
     */
    public function status(int $id): Response
    {
        try {
            return ApiResponse::success((new NoticeService())->changeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除消息通知。
     */
    public function delete(int $id): Response
    {
        try {
            (new NoticeService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 标记单条消息已读。
     */
    public function read(int $id): Response
    {
        try {
            (new NoticeService())->read($this->adminId(), $id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 标记全部消息已读。
     */
    public function readAll(): Response
    {
        (new NoticeService())->readAll($this->adminId());

        return ApiResponse::success();
    }
}
