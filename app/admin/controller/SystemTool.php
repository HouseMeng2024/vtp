<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\SystemToolService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class SystemTool extends AdminBase
{
    /**
     * 获取缓存和数据库备份概览。
     */
    public function index(): Response
    {
        return ApiResponse::success((new SystemToolService())->overview());
    }

    /**
     * 清理系统缓存。
     */
    public function clearCache(): Response
    {
        try {
            (new SystemToolService())->clearCache((string) $this->request->param('type', 'all'));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 清理前台内容缓存。
     */
    public function clearIndexContentCache(): Response
    {
        try {
            (new SystemToolService())->clearIndexContentCache();
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 获取数据库备份列表。
     */
    public function backups(): Response
    {
        return ApiResponse::success((new SystemToolService())->backups());
    }

    /**
     * 创建数据库备份。
     */
    public function createBackup(): Response
    {
        try {
            return ApiResponse::success((new SystemToolService())->createBackup());
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 下载数据库备份。
     */
    public function downloadBackup(): Response
    {
        try {
            $name = (string) $this->request->param('name', '');
            return download((new SystemToolService())->downloadPath($name), $name);
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 恢复数据库备份。
     */
    public function restoreBackup(): Response
    {
        try {
            (new SystemToolService())->restoreBackup((string) $this->request->param('name', ''));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 删除数据库备份。
     */
    public function deleteBackup(): Response
    {
        try {
            (new SystemToolService())->deleteBackup((string) $this->request->param('name', ''));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
