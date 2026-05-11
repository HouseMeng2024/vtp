<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\base\AdminBase;
use app\common\service\admin\CodeGeneratorService;
use app\common\support\ApiResponse;
use RuntimeException;
use think\Response;

class CodeGenerator extends AdminBase
{
    /**
     * 预检本次代码生成会影响的文件、数据表和菜单。
     */
    public function preview(): Response
    {
        try {
            return ApiResponse::success((new CodeGeneratorService())->preview($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 获取最近一次代码生成结果。
     */
    public function recent(): Response
    {
        return ApiResponse::success((new CodeGeneratorService())->recent() ?? []);
    }

    /**
     * 根据后台配置生成 CRUD 代码。
     */
    public function generate(): Response
    {
        try {
            return ApiResponse::success((new CodeGeneratorService())->generate($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 清理指定模块的生成文件、菜单和数据表。
     */
    public function cleanup(): Response
    {
        try {
            return ApiResponse::success((new CodeGeneratorService())->cleanup($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }
}
