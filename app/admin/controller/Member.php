<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\MemberService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class Member extends AdminBase
{
    /**
     * 获取会员分页列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new MemberService())->page($this->request->get()));
    }

    /**
     * 获取会员详情。
     */
    public function detail(int $id): Response
    {
        try {
            return ApiResponse::success((new MemberService())->detail($id));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 新增会员。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new MemberService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新会员资料。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new MemberService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改会员启用状态。
     */
    public function status(int $id): Response
    {
        try {
            return ApiResponse::success((new MemberService())->changeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 批量修改会员启用状态。
     */
    public function batchStatus(): Response
    {
        try {
            (new MemberService())->batchChangeStatus(
                (array) $this->request->param('ids', []),
                (int) $this->request->param('status', 0)
            );
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 重置会员密码。
     */
    public function resetPassword(int $id): Response
    {
        try {
            (new MemberService())->resetPassword($id, (string) $this->request->param('password', ''));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除会员。
     */
    public function delete(int $id): Response
    {
        try {
            (new MemberService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 批量删除会员。
     */
    public function batchDelete(): Response
    {
        try {
            (new MemberService())->batchDelete((array) $this->request->param('ids', []));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
