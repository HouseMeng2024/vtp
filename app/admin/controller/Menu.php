<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\MenuService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class Menu extends AdminBase
{
    /**
     * 获取后台菜单树。
     */
    public function index(): Response
    {
        return ApiResponse::success((new MenuService())->all());
    }

    /**
     * 新增后台菜单。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new MenuService())->create($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新后台菜单。
     */
    public function update(int $id): Response
    {
        try {
            return ApiResponse::success((new MenuService())->update($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除后台菜单。
     */
    public function delete(int $id): Response
    {
        try {
            (new MenuService())->delete($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
