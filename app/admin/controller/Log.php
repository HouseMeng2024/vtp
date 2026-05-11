<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\LogService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class Log extends AdminBase
{
    /**
     * 获取登录日志分页列表。
     */
    public function login(): Response
    {
        return ApiResponse::success((new LogService())->loginPage($this->request->get()));
    }

    /**
     * 获取操作日志分页列表。
     */
    public function operate(): Response
    {
        return ApiResponse::success((new LogService())->operatePage($this->request->get()));
    }

    /**
     * 批量删除登录日志。
     */
    public function batchDeleteLogin(): Response
    {
        try {
            (new LogService())->batchDeleteLogin((array) $this->request->param('ids', []));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 批量删除操作日志。
     */
    public function batchDeleteOperate(): Response
    {
        try {
            (new LogService())->batchDeleteOperate((array) $this->request->param('ids', []));
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 清空登录日志。
     */
    public function clearLogin(): Response
    {
        try {
            (new LogService())->clear('login');
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 清空操作日志。
     */
    public function clearOperate(): Response
    {
        try {
            (new LogService())->clear('operate');
            return ApiResponse::success();
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
