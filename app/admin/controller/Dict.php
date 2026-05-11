<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\DictService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class Dict extends AdminBase
{
    /**
     * 获取字典类型分页列表。
     */
    public function types(): Response
    {
        return ApiResponse::success((new DictService())->typePage($this->request->get()));
    }

    /**
     * 新增字典类型。
     */
    public function saveType(): Response
    {
        try {
            return ApiResponse::success((new DictService())->createType($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新字典类型。
     */
    public function updateType(int $id): Response
    {
        try {
            return ApiResponse::success((new DictService())->updateType($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改字典类型状态。
     */
    public function typeStatus(int $id): Response
    {
        try {
            return ApiResponse::success((new DictService())->changeTypeStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除字典类型。
     */
    public function deleteType(int $id): Response
    {
        try {
            (new DictService())->deleteType($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 获取字典项分页列表。
     */
    public function data(): Response
    {
        return ApiResponse::success((new DictService())->dataPage($this->request->get()));
    }

    /**
     * 获取字典选项。
     */
    public function options(): Response
    {
        return ApiResponse::success((new DictService())->options((string) $this->request->get('type', '')));
    }

    /**
     * 获取字典类型选项。
     */
    public function typeOptions(): Response
    {
        return ApiResponse::success((new DictService())->typeOptions());
    }

    /**
     * 新增字典项。
     */
    public function saveData(): Response
    {
        try {
            return ApiResponse::success((new DictService())->createData($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 更新字典项。
     */
    public function updateData(int $id): Response
    {
        try {
            return ApiResponse::success((new DictService())->updateData($id, $this->request->put()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 修改字典项状态。
     */
    public function dataStatus(int $id): Response
    {
        try {
            return ApiResponse::success((new DictService())->changeDataStatus($id, (int) $this->request->param('status', 0)));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除字典项。
     */
    public function deleteData(int $id): Response
    {
        try {
            (new DictService())->deleteData($id);
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
