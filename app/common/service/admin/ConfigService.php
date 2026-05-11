<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\SystemConfig;
use RuntimeException;

class ConfigService
{
    /**
     * 获取项目配置分组列表。
     */
    public function groups(): array
    {
        $this->ensureDefaults();

        $rows = SystemConfig::where([])
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $groups = [];

        foreach ($rows as $row) {
            $group = (string) $row['group'];

            if (!isset($groups[$group])) {
                $groups[$group] = [
                    'group' => $group,
                    'title' => $this->groupTitle($group),
                    'items' => [],
                ];
            }

            $groups[$group]['items'][] = [
                'id'          => (int) $row['id'],
                'group'       => $group,
                'key'         => $row['key'],
                'value'       => $row['value'],
                'type'        => $row['type'],
                'name'        => $row['name'],
                'remark'      => $row['remark'],
                'create_time' => $row['create_time'],
                'update_time' => $row['update_time'],
            ];
        }

        return array_values($groups);
    }

    /**
     * 保存项目配置值。
     */
    public function save(array $values): array
    {
        $this->ensureDefaults();
        $definitions = $this->definitionMap();

        foreach ($values as $key => $value) {
            if (!isset($definitions[$key])) {
                throw new RuntimeException('配置项不存在：' . $key);
            }

            $definition = $definitions[$key];
            $config = SystemConfig::where('group', $definition['group'])
                ->where('key', $key)
                ->find();

            if (!$config) {
                continue;
            }

            $config->save(['value' => $this->normalizeValue($value, $definition['type'])]);
        }

        return $this->groups();
    }

    /**
     * 获取公开站点配置，用于登录页和首屏品牌展示。
     */
    public function site(): array
    {
        $this->ensureDefaults();
        $keys = ['site_name', 'admin_title', 'site_logo', 'site_description'];
        $rows = SystemConfig::whereIn('key', $keys)
            ->column('value', 'key');

        return [
            'admin_title'      => (string) ($rows['admin_title'] ?? $rows['site_name'] ?? 'VTP Admin'),
            'site_logo'        => (string) ($rows['site_logo'] ?? ''),
            'site_description' => (string) ($rows['site_description'] ?? '通用后台管理系统'),
        ];
    }

    /**
     * 初始化默认项目配置。
     */
    private function ensureDefaults(): void
    {
        foreach ($this->definitions() as $definition) {
            $exists = SystemConfig::where('group', $definition['group'])
                ->where('key', $definition['key'])
                ->find();

            if ($exists) {
                continue;
            }

            SystemConfig::create([
                'group'  => $definition['group'],
                'key'    => $definition['key'],
                'value'  => $definition['value'],
                'type'   => $definition['type'],
                'name'   => $definition['name'],
                'remark' => $definition['remark'],
            ]);
        }
    }

    /**
     * 获取配置定义映射。
     */
    private function definitionMap(): array
    {
        $map = [];

        foreach ($this->definitions() as $definition) {
            $map[$definition['key']] = $definition;
        }

        return $map;
    }

    /**
     * 获取默认项目配置定义。
     */
    private function definitions(): array
    {
        return [
            ['group' => 'basic', 'key' => 'site_name', 'value' => 'VTP Admin', 'type' => 'text', 'name' => '站点名称', 'remark' => '后台和项目默认显示名称'],
            ['group' => 'basic', 'key' => 'admin_title', 'value' => 'VTP Admin', 'type' => 'text', 'name' => '后台标题', 'remark' => '后台浏览器标题和顶部品牌名称'],
            ['group' => 'basic', 'key' => 'site_logo', 'value' => '', 'type' => 'image', 'name' => '站点 Logo', 'remark' => '填写图片 URL，用于后台品牌展示'],
            ['group' => 'basic', 'key' => 'site_description', 'value' => '通用后台管理系统', 'type' => 'textarea', 'name' => '站点描述', 'remark' => '用于项目说明、SEO 或接口展示'],
            ['group' => 'basic', 'key' => 'site_keywords', 'value' => '', 'type' => 'text', 'name' => '站点关键词', 'remark' => '多个关键词用英文逗号分隔'],
            ['group' => 'basic', 'key' => 'site_icp', 'value' => '', 'type' => 'text', 'name' => 'ICP备案号', 'remark' => '需要展示备案信息时填写'],

            ['group' => 'upload', 'key' => 'upload_disk', 'value' => 'local', 'type' => 'text', 'name' => '上传磁盘', 'remark' => '默认 local，后续可扩展 oss、cos 等'],
            ['group' => 'upload', 'key' => 'upload_max_size', 'value' => '10', 'type' => 'number', 'name' => '上传大小限制', 'remark' => '单位 MB'],
            ['group' => 'upload', 'key' => 'upload_ext', 'value' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx', 'type' => 'text', 'name' => '允许扩展名', 'remark' => '多个扩展名用英文逗号分隔'],

            ['group' => 'security', 'key' => 'password_min_length', 'value' => '6', 'type' => 'number', 'name' => '密码最小长度', 'remark' => '管理员密码最小长度'],
            ['group' => 'security', 'key' => 'admin_token_expire', 'value' => '86400', 'type' => 'number', 'name' => '后台登录有效期', 'remark' => '单位秒'],
            ['group' => 'security', 'key' => 'login_captcha_enabled', 'value' => '0', 'type' => 'switch', 'name' => '登录验证码', 'remark' => '开启后后台登录需要输入验证码'],
            ['group' => 'security', 'key' => 'login_max_attempts', 'value' => '5', 'type' => 'number', 'name' => '登录失败次数', 'remark' => '达到次数后临时锁定'],
            ['group' => 'security', 'key' => 'login_lock_seconds', 'value' => '900', 'type' => 'number', 'name' => '登录锁定时长', 'remark' => '单位秒'],
        ];
    }

    /**
     * 获取配置分组中文名称。
     */
    private function groupTitle(string $group): string
    {
        return [
            'basic'    => '基础配置',
            'upload'   => '上传配置',
            'security' => '安全配置',
        ][$group] ?? $group;
    }

    /**
     * 按配置类型规范化配置值。
     */
    private function normalizeValue(mixed $value, string $type): string
    {
        if ($type === 'number') {
            return (string) max(0, (int) $value);
        }

        if ($type === 'switch') {
            return (int) $value === 1 ? '1' : '0';
        }

        return trim((string) $value);
    }
}
