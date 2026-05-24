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
     * 获取代码生成器写入能力状态。
     */
    public function status(): Response
    {
        return ApiResponse::success($this->writeStatus());
    }

    /**
     * 根据后台配置生成 CRUD 代码。
     */
    public function generate(): Response
    {
        try {
            $this->assertCanWrite();
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
            $this->assertCanWrite();
            return ApiResponse::success((new CodeGeneratorService())->cleanup($this->request->post()));
        } catch (RuntimeException $exception) {
            return ApiResponse::fail($exception->getMessage());
        }
    }

    /**
     * 校验当前环境是否允许写入代码生成结果。
     */
    private function assertCanWrite(): void
    {
        $status = $this->writeStatus();

        if (!$status['writable']) {
            throw new RuntimeException($status['message']);
        }
    }

    /**
     * 生成代码写入状态，供前端展示和后端兜底校验共用。
     */
    private function writeStatus(): array
    {
        $enabled = filter_var(env('CODE_GENERATOR_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        $roles = $this->adminUser()['roles'] ?? [];
        $isSuperAdmin = in_array('super_admin', $roles, true);

        return [
            'enabled' => $enabled,
            'super_admin' => $isSuperAdmin,
            'writable' => $enabled && $isSuperAdmin,
            'message' => match (true) {
                !$enabled => '代码生成器写入能力未开启',
                !$isSuperAdmin => '仅超级管理员可执行代码生成写入操作',
                default => '',
            },
        ];
    }
}
