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

    /**
     * 新增配置分组。
     */
    public function createGroup(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->createGroup($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新配置分组。
     */
    public function updateGroup(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->updateGroup((int) $this->request->param('id'), $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除配置分组。
     */
    public function deleteGroup(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->deleteGroup((int) $this->request->param('id')));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 新增配置标签页。
     */
    public function createTab(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->createTab($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新配置标签页。
     */
    public function updateTab(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->updateTab((int) $this->request->param('id'), $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除配置标签页。
     */
    public function deleteTab(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->deleteTab((int) $this->request->param('id')));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 新增配置项。
     */
    public function createItem(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->createItem($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新配置项。
     */
    public function updateItem(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->updateItem((int) $this->request->param('id'), $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除配置项。
     */
    public function deleteItem(): Response
    {
        try {
            return ApiResponse::success((new ConfigService())->deleteItem((int) $this->request->param('id')));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
