<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\FileService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class File extends AdminBase
{
    /**
     * 获取上传文件分页列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new FileService())->page(array_merge($this->request->get(), $this->scopeContext())));
    }

    /**
     * 上传文件。
     */
    public function upload(): Response
    {
        try {
            return ApiResponse::success((new FileService())->upload(
                $this->request->file('file'),
                $this->adminId(),
                null,
                (string) $this->request->param('scene', 'default')
            ));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除上传文件记录。
     */
    public function delete(int $id): Response
    {
        try {
            (new FileService())->delete($id, $this->adminId(), $this->dataScope());
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 获取文件删除前的引用信息。
     */
    public function deleteInfo(int $id): Response
    {
        try {
            return ApiResponse::success((new FileService())->deleteInfo($id, $this->adminId(), $this->dataScope()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 批量删除上传文件记录。
     */
    public function batchDelete(): Response
    {
        try {
            (new FileService())->batchDelete((array) $this->request->param('ids', []), $this->adminId(), $this->dataScope());
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 重命名上传文件。
     */
    public function rename(int $id): Response
    {
        try {
            return ApiResponse::success((new FileService())->rename($id, (string) $this->request->param('name', ''), $this->adminId(), $this->dataScope()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
