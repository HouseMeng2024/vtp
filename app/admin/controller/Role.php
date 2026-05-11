<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\RoleService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class Role extends AdminBase
{
    /**
     * 获取角色分页列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new RoleService())->page($this->request->get()));
    }

    /**
     * 获取可选角色列表，用于管理员表单分配角色。
     */
    public function options(): Response
    {
        return ApiResponse::success((new RoleService())->options());
    }

    /**
     * 新增角色。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new RoleService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新角色基础信息。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new RoleService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改角色启用状态。
     */
    public function status(int $id): Response
    {
        try {
            return ApiResponse::success((new RoleService())->changeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 批量修改角色启用状态。
     */
    public function batchStatus(): Response
    {
        try {
            (new RoleService())->batchChangeStatus(
                (array) $this->request->param('ids', []),
                (int) $this->request->param('status', 0)
            );
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除角色，并清理角色菜单关联。
     */
    public function delete(int $id): Response
    {
        try {
            (new RoleService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 批量删除角色。
     */
    public function batchDelete(): Response
    {
        try {
            (new RoleService())->batchDelete((array) $this->request->param('ids', []));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 获取角色已分配的菜单 ID。
     */
    public function menus(int $id): Response
    {
        try {
            return ApiResponse::success(['menu_ids' => (new RoleService())->menuIds($id)]);
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 保存角色菜单权限。
     */
    public function saveMenus(int $id): Response
    {
        try {
            (new RoleService())->syncMenus($id, $this->request->put('menu_ids', []));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
