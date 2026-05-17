<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\SystemConfig;
use app\common\model\SystemConfigGroup;
use app\common\model\SystemConfigTab;
use app\common\support\ConfigValue;
use RuntimeException;

/**
 * 系统设置服务。
 */
class ConfigService
{
    private const RESERVED_GROUP_KEYS = [
        'app',
        'cache',
        'console',
        'cookie',
        'database',
        'filesystem',
        'gateway',
        'lang',
        'log',
        'middleware',
        'route',
        'session',
        'trace',
        'view',
    ];

    private const TYPES = [
        'text',
        'password',
        'textarea',
        'number',
        'switch',
        'radio',
        'checkbox',
        'select',
        'select_multiple',
        'color',
        'date',
        'daterange',
        'datetime',
        'datetimerange',
        'time',
        'timerange',
        'slider',
        'rate',
        'image',
        'images',
        'file',
        'files',
    ];

    /**
     * 获取配置分组、标签页和配置项树。
     */
    public function groups(): array
    {
        $this->ensureDefaults();

        $groups = SystemConfigGroup::where([])
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        $tabs = SystemConfigTab::where([])
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        $items = SystemConfig::where([])
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $tabMap = [];
        foreach ($tabs as $tab) {
            $tab['items'] = [];
            $tabMap[(int) $tab['id']] = $tab;
        }

        foreach ($items as $item) {
            $tabId = (int) ($item['tab_id'] ?? 0);
            if (!isset($tabMap[$tabId])) {
                continue;
            }

            $tabMap[$tabId]['items'][] = $this->formatItem($item);
        }

        $groupMap = [];
        foreach ($groups as $group) {
            $group['tabs'] = [];
            $groupMap[(int) $group['id']] = $group;
        }

        foreach ($tabMap as $tab) {
            $groupId = (int) $tab['group_id'];
            if (!isset($groupMap[$groupId])) {
                continue;
            }

            $groupMap[$groupId]['tabs'][] = $this->formatTab($tab);
        }

        return array_values(array_map(fn (array $group) => $this->formatGroup($group), $groupMap));
    }

    /**
     * 保存配置项值。
     */
    public function save(array $values): array
    {
        $this->ensureDefaults();

        foreach ($values as $id => $value) {
            $config = SystemConfig::find((int) $id);
            if (!$config) {
                throw new RuntimeException(\think\facade\Lang::get('admin.config_item_not_found_with_id') . $id);
            }

            $config->save(['value' => $this->normalizeValue($value, (string) $config->type)]);
        }

        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 获取公开站点配置，用于登录页和首屏品牌展示。
     */
    public function site(): array
    {
        $this->ensureDefaults();

        return [
            'admin_title'      => (string) ConfigValue::getInGroups('title', ['admin'], 'VTP Admin'),
            'site_logo'        => (string) ConfigValue::getInGroups('logo', ['admin'], ''),
            'site_description' => (string) ConfigValue::getInGroups('description', ['admin'], 'General admin system'),
        ];
    }

    /**
     * 新增配置分组。
     */
    public function createGroup(array $payload): array
    {
        $this->ensureDefaults();
        $key = $this->validKey((string) ($payload['key'] ?? ''));
        $this->assertGroupKeyAvailable($key);

        if (SystemConfigGroup::where('key', $key)->find()) {
            throw new RuntimeException(\think\facade\Lang::get('admin.group_key_exists'));
        }

        SystemConfigGroup::create([
            'key'       => $key,
            'title'     => $this->validTitle((string) ($payload['title'] ?? '')),
            'sort'      => (int) ($payload['sort'] ?? 100),
            'is_system' => 0,
            'status'    => (int) ($payload['status'] ?? 1) === 0 ? 0 : 1,
        ]);

        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 更新配置分组。
     */
    public function updateGroup(int $id, array $payload): array
    {
        $this->ensureDefaults();
        $group = $this->findGroup($id);
        $data = [
            'title'  => $this->validTitle((string) ($payload['title'] ?? $group->title)),
            'sort'   => (int) ($payload['sort'] ?? $group->sort),
            'status' => (int) ($payload['status'] ?? $group->status) === 0 ? 0 : 1,
        ];

        if ((int) $group->is_system !== 1 && isset($payload['key'])) {
            $key = $this->validKey((string) $payload['key']);
            $this->assertGroupKeyAvailable($key);
            $exists = SystemConfigGroup::where('key', $key)->where('id', '<>', $id)->find();
            if ($exists) {
                throw new RuntimeException(\think\facade\Lang::get('admin.group_key_exists'));
            }
            $data['key'] = $key;
        }

        $group->save($data);
        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 删除配置分组。
     */
    public function deleteGroup(int $id): array
    {
        $this->ensureDefaults();
        $group = $this->findGroup($id);
        if ((int) $group->is_system === 1) {
            throw new RuntimeException(\think\facade\Lang::get('admin.system_group_delete_forbidden'));
        }

        $tabIds = SystemConfigTab::where('group_id', $id)->column('id');
        $systemItem = $tabIds ? SystemConfig::whereIn('tab_id', $tabIds)->where('is_system', 1)->find() : null;
        if ($systemItem) {
            throw new RuntimeException(\think\facade\Lang::get('admin.system_config_items_under_group'));
        }

        if ($tabIds) {
            SystemConfig::whereIn('tab_id', $tabIds)->delete();
            SystemConfigTab::whereIn('id', $tabIds)->delete();
        }
        $group->delete();

        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 新增配置标签页。
     */
    public function createTab(array $payload): array
    {
        $this->ensureDefaults();
        $group = $this->findGroup((int) ($payload['group_id'] ?? 0));
        $key = $this->validKey((string) ($payload['key'] ?? ''));
        $exists = SystemConfigTab::where('group_id', $group->id)->where('key', $key)->find();
        if ($exists) {
            throw new RuntimeException(\think\facade\Lang::get('admin.tab_key_exists'));
        }

        SystemConfigTab::create([
            'group_id'  => $group->id,
            'key'       => $key,
            'title'     => $this->validTitle((string) ($payload['title'] ?? '')),
            'sort'      => (int) ($payload['sort'] ?? 100),
            'is_system' => 0,
            'status'    => (int) ($payload['status'] ?? 1) === 0 ? 0 : 1,
        ]);

        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 更新配置标签页。
     */
    public function updateTab(int $id, array $payload): array
    {
        $this->ensureDefaults();
        $tab = $this->findTab($id);
        $data = [
            'title'  => $this->validTitle((string) ($payload['title'] ?? $tab->title)),
            'sort'   => (int) ($payload['sort'] ?? $tab->sort),
            'status' => (int) ($payload['status'] ?? $tab->status) === 0 ? 0 : 1,
        ];

        if ((int) $tab->is_system !== 1 && isset($payload['key'])) {
            $key = $this->validKey((string) $payload['key']);
            $exists = SystemConfigTab::where('group_id', $tab->group_id)
                ->where('key', $key)
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                throw new RuntimeException(\think\facade\Lang::get('admin.tab_key_exists'));
            }
            $data['key'] = $key;
        }

        $tab->save($data);
        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 删除配置标签页。
     */
    public function deleteTab(int $id): array
    {
        $this->ensureDefaults();
        $tab = $this->findTab($id);
        if ((int) $tab->is_system === 1) {
            throw new RuntimeException(\think\facade\Lang::get('admin.system_tab_delete_forbidden'));
        }

        $systemItem = SystemConfig::where('tab_id', $id)->where('is_system', 1)->find();
        if ($systemItem) {
            throw new RuntimeException(\think\facade\Lang::get('admin.system_config_items_under_tab'));
        }

        SystemConfig::where('tab_id', $id)->delete();
        $tab->delete();

        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 新增配置项。
     */
    public function createItem(array $payload): array
    {
        $this->ensureDefaults();
        $tab = $this->findTab((int) ($payload['tab_id'] ?? 0));
        $key = $this->validKey((string) ($payload['key'] ?? ''));
        if (SystemConfig::where('group_id', $tab->group_id)->where('key', $key)->find()) {
            throw new RuntimeException(\think\facade\Lang::get('admin.config_key_exists'));
        }

        $type = $this->validType((string) ($payload['type'] ?? 'text'));
        SystemConfig::create([
            'group_id'  => $tab->group_id,
            'tab_id'    => $tab->id,
            'group'     => $tab->key,
            'key'       => $key,
            'value'     => $this->normalizeValue($payload['value'] ?? '', $type),
            'type'      => $type,
            'name'      => $this->validTitle((string) ($payload['name'] ?? '')),
            'remark'    => trim((string) ($payload['remark'] ?? '')),
            'options'   => trim((string) ($payload['options'] ?? '')),
            'sort'      => (int) ($payload['sort'] ?? 100),
            'is_system' => 0,
            'status'    => (int) ($payload['status'] ?? 1) === 0 ? 0 : 1,
        ]);

        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 更新配置项。
     */
    public function updateItem(int $id, array $payload): array
    {
        $this->ensureDefaults();
        $item = $this->findItem($id);
        $isSystem = (int) $item->is_system === 1;
        $type = $isSystem ? (string) $item->type : $this->validType((string) ($payload['type'] ?? $item->type));
        $data = [
            'value'  => $this->normalizeValue($payload['value'] ?? $item->value, $type),
            'type'   => $type,
            'name'   => $this->validTitle((string) ($payload['name'] ?? $item->name)),
            'remark' => trim((string) ($payload['remark'] ?? $item->remark)),
            'options' => trim((string) ($payload['options'] ?? $item->options)),
            'sort'   => (int) ($payload['sort'] ?? $item->sort),
            'status' => (int) ($payload['status'] ?? $item->status) === 0 ? 0 : 1,
        ];

        if (!$isSystem && isset($payload['tab_id'])) {
            $tab = $this->findTab((int) $payload['tab_id']);
            $data['group_id'] = $tab->group_id;
            $data['tab_id'] = $tab->id;
            $data['group'] = $tab->key;
        }

        if (!$isSystem) {
            $key = isset($payload['key']) ? $this->validKey((string) $payload['key']) : (string) $item->key;
            $groupId = (int) ($data['group_id'] ?? $item->group_id);
            $exists = SystemConfig::where('group_id', $groupId)->where('key', $key)->where('id', '<>', $id)->find();
            if ($exists) {
                throw new RuntimeException(\think\facade\Lang::get('admin.config_key_exists'));
            }
            $data['key'] = $key;
        }

        $item->save($data);
        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 删除配置项。
     */
    public function deleteItem(int $id): array
    {
        $this->ensureDefaults();
        $item = $this->findItem($id);
        if ((int) $item->is_system === 1) {
            throw new RuntimeException(\think\facade\Lang::get('admin.system_config_item_delete_forbidden'));
        }

        $item->delete();
        ConfigValue::clear();
        return $this->groups();
    }

    /**
     * 初始化默认项目配置。
     */
    private function ensureDefaults(): void
    {
        $groups = [];
        $tabs = [];

        foreach ($this->groupsConfig() as $groupConfig) {
            $groups[$groupConfig['key']] = $this->ensureGroup($groupConfig['key'], $groupConfig['title'], $groupConfig['sort'], 1);
        }

        foreach ($this->tabs() as $tab) {
            $group = $groups[$tab['group']] ?? null;
            if (!$group) {
                continue;
            }

            $tabs[$tab['key']] = $this->ensureTab((int) $group->id, $tab['key'], $tab['title'], $tab['sort'], 1);
        }

        foreach ($this->definitions() as $definition) {
            $tab = $tabs[$definition['tab']] ?? null;
            if (!$tab) {
                continue;
            }

            $config = SystemConfig::where('group_id', $tab->group_id)->where('key', $definition['key'])->find();
            $data = [
                'group_id'  => $tab->group_id,
                'tab_id'    => $tab->id,
                'group'     => $tab->key,
                'type'      => $definition['type'],
                'name'      => $definition['name'],
                'remark'    => $definition['remark'],
                'options'   => $definition['options'] ?? '',
                'sort'      => $definition['sort'],
                'is_system' => 1,
                'status'    => 1,
            ];

            if ($config) {
                $config->save($data);
                continue;
            }

            SystemConfig::create(array_merge($data, [
                'key'   => $definition['key'],
                'value' => $definition['value'],
            ]));
        }
    }

    /**
     * 初始化或更新内置分组。
     */
    private function ensureGroup(string $key, string $title, int $sort, int $isSystem): SystemConfigGroup
    {
        $group = SystemConfigGroup::where('key', $key)->find();
        if ($group) {
            $group->save(['title' => $title, 'sort' => $sort, 'is_system' => $isSystem, 'status' => 1]);
            return $group;
        }

        return SystemConfigGroup::create([
            'key'       => $key,
            'title'     => $title,
            'sort'      => $sort,
            'is_system' => $isSystem,
            'status'    => 1,
        ]);
    }

    /**
     * 初始化或更新内置标签页。
     */
    private function ensureTab(int $groupId, string $key, string $title, int $sort, int $isSystem): SystemConfigTab
    {
        $tab = SystemConfigTab::where('group_id', $groupId)->where('key', $key)->find();
        if ($tab) {
            $tab->save(['title' => $title, 'sort' => $sort, 'is_system' => $isSystem, 'status' => 1]);
            return $tab;
        }

        return SystemConfigTab::create([
            'group_id'  => $groupId,
            'key'       => $key,
            'title'     => $title,
            'sort'      => $sort,
            'is_system' => $isSystem,
            'status'    => 1,
        ]);
    }

    /**
     * 获取默认配置分组。
     */
    private function groupsConfig(): array
    {
        return [
            ['key' => 'system', 'title' => 'System', 'sort' => 100],
            ['key' => 'admin', 'title' => 'Admin', 'sort' => 200],
            ['key' => 'index', 'title' => 'Index', 'sort' => 300],
        ];
    }

    /**
     * 获取默认配置标签页。
     */
    private function tabs(): array
    {
        return [
            ['group' => 'system', 'key' => 'system_basic', 'title' => 'Basic Rules', 'sort' => 100],
            ['group' => 'system', 'key' => 'system_upload', 'title' => 'Upload Rules', 'sort' => 200],
            ['group' => 'system', 'key' => 'system_security', 'title' => 'Security Rules', 'sort' => 300],
            ['group' => 'admin', 'key' => 'admin_basic', 'title' => 'Admin Basic', 'sort' => 100],
            ['group' => 'admin', 'key' => 'admin_login', 'title' => 'Login Security', 'sort' => 200],
            ['group' => 'index', 'key' => 'index_site', 'title' => 'Site Info', 'sort' => 100],
            ['group' => 'index', 'key' => 'index_seo', 'title' => 'SEO Settings', 'sort' => 200],
        ];
    }

    /**
     * 获取默认配置定义。
     */
    private function definitions(): array
    {
        return [
            ['tab' => 'system_upload', 'key' => 'upload_max_size', 'value' => '10', 'type' => 'number', 'name' => 'Upload Size Limit', 'remark' => 'Unit: MB. Applies to uploads in all modules by default.', 'sort' => 100],
            ['tab' => 'system_upload', 'key' => 'upload_ext', 'value' => 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip', 'type' => 'text', 'name' => 'Allowed Extensions', 'remark' => 'Use commas to separate multiple extensions.', 'sort' => 101],

            ['tab' => 'system_security', 'key' => 'password_min_length', 'value' => '6', 'type' => 'number', 'name' => 'Password Min Length', 'remark' => 'Minimum password length for system accounts.', 'sort' => 100],
            ['tab' => 'system_security', 'key' => 'login_max_attempts', 'value' => '5', 'type' => 'number', 'name' => 'Max Login Attempts', 'remark' => 'Temporarily lock the account after this number of failed attempts.', 'sort' => 101],
            ['tab' => 'system_security', 'key' => 'login_lock_seconds', 'value' => '900', 'type' => 'number', 'name' => 'Login Lock Duration', 'remark' => 'Unit: seconds.', 'sort' => 102],

            ['tab' => 'admin_basic', 'key' => 'title', 'value' => 'VTP Admin', 'type' => 'text', 'name' => 'Admin Title', 'remark' => 'Browser title and top brand name for the admin panel.', 'sort' => 100],
            ['tab' => 'admin_basic', 'key' => 'logo', 'value' => '', 'type' => 'image', 'name' => 'Admin Logo', 'remark' => 'Logo used on the admin login page and header.', 'sort' => 101],
            ['tab' => 'admin_basic', 'key' => 'description', 'value' => 'General admin system', 'type' => 'textarea', 'name' => 'Admin Description', 'remark' => 'Description shown on the admin login page.', 'sort' => 102],

            ['tab' => 'admin_login', 'key' => 'token_expire', 'value' => '86400', 'type' => 'number', 'name' => 'Admin Session TTL', 'remark' => 'Unit: seconds. Applies only to the admin module.', 'sort' => 100],
            ['tab' => 'admin_login', 'key' => 'captcha_enabled', 'value' => '0', 'type' => 'switch', 'name' => 'Login Captcha', 'remark' => 'Require captcha when signing in to the admin panel.', 'sort' => 101],

            ['tab' => 'index_site', 'key' => 'title', 'value' => 'VTP', 'type' => 'text', 'name' => 'Site Title', 'remark' => 'Default site title for the index module.', 'sort' => 100],
            ['tab' => 'index_site', 'key' => 'logo', 'value' => '', 'type' => 'image', 'name' => 'Site Logo', 'remark' => 'Logo used by the index site.', 'sort' => 101],

            ['tab' => 'index_seo', 'key' => 'seo_title', 'value' => 'VTP', 'type' => 'text', 'name' => 'SEO Title', 'remark' => 'Default SEO title for the index site.', 'sort' => 100],
            ['tab' => 'index_seo', 'key' => 'seo_keywords', 'value' => '', 'type' => 'text', 'name' => 'SEO Keywords', 'remark' => 'Use commas to separate multiple keywords.', 'sort' => 101],
            ['tab' => 'index_seo', 'key' => 'seo_description', 'value' => '', 'type' => 'textarea', 'name' => 'SEO Description', 'remark' => 'Default SEO description for the index site.', 'sort' => 102],
        ];
    }

    /**
     * 格式化配置分组。
     */
    private function formatGroup(array $group): array
    {
        return [
            'id'          => (int) $group['id'],
            'key'         => (string) $group['key'],
            'title'       => (string) $group['title'],
            'sort'        => (int) $group['sort'],
            'is_system'   => (int) $group['is_system'],
            'status'      => (int) $group['status'],
            'tabs'        => $group['tabs'] ?? [],
            'create_time' => $group['create_time'] ?? null,
            'update_time' => $group['update_time'] ?? null,
        ];
    }

    /**
     * 格式化配置标签页。
     */
    private function formatTab(array $tab): array
    {
        return [
            'id'          => (int) $tab['id'],
            'group_id'    => (int) $tab['group_id'],
            'key'         => (string) $tab['key'],
            'title'       => (string) $tab['title'],
            'sort'        => (int) $tab['sort'],
            'is_system'   => (int) $tab['is_system'],
            'status'      => (int) $tab['status'],
            'items'       => $tab['items'] ?? [],
            'create_time' => $tab['create_time'] ?? null,
            'update_time' => $tab['update_time'] ?? null,
        ];
    }

    /**
     * 格式化配置项。
     */
    private function formatItem(array $row): array
    {
        return [
            'id'          => (int) $row['id'],
            'group_id'    => (int) ($row['group_id'] ?? 0),
            'tab_id'      => (int) ($row['tab_id'] ?? 0),
            'group'       => (string) $row['group'],
            'key'         => (string) $row['key'],
            'value'       => (string) ($row['value'] ?? ''),
            'type'        => (string) $row['type'],
            'name'        => (string) $row['name'],
            'remark'      => (string) $row['remark'],
            'options'     => (string) ($row['options'] ?? ''),
            'sort'        => (int) ($row['sort'] ?? 100),
            'is_system'   => (int) ($row['is_system'] ?? 0),
            'status'      => (int) ($row['status'] ?? 1),
            'create_time' => $row['create_time'],
            'update_time' => $row['update_time'],
        ];
    }

    private function findGroup(int $id): SystemConfigGroup
    {
        $group = SystemConfigGroup::find($id);
        if (!$group) {
            throw new RuntimeException(\think\facade\Lang::get('admin.config_group_not_found'));
        }
        return $group;
    }

    private function findTab(int $id): SystemConfigTab
    {
        $tab = SystemConfigTab::find($id);
        if (!$tab) {
            throw new RuntimeException(\think\facade\Lang::get('admin.config_tab_not_found'));
        }
        return $tab;
    }

    private function findItem(int $id): SystemConfig
    {
        $item = SystemConfig::find($id);
        if (!$item) {
            throw new RuntimeException(\think\facade\Lang::get('admin.config_item_not_found'));
        }
        return $item;
    }

    private function validKey(string $key): string
    {
        $key = trim($key);
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            throw new RuntimeException(\think\facade\Lang::get('admin.key_invalid'));
        }
        return $key;
    }

    private function assertGroupKeyAvailable(string $key): void
    {
        if (in_array($key, self::RESERVED_GROUP_KEYS, true)) {
            throw new RuntimeException(\think\facade\Lang::get('admin.group_key_reserved') . $key);
        }
    }

    private function validTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            throw new RuntimeException(\think\facade\Lang::get('admin.name_required'));
        }
        return $title;
    }

    private function validType(string $type): string
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new RuntimeException(\think\facade\Lang::get('admin.unsupported_config_type'));
        }
        return $type;
    }

    /**
     * 按配置类型规范化配置值。
     */
    private function normalizeValue(mixed $value, string $type): string
    {
        if (in_array($type, ['number', 'slider', 'rate'], true)) {
            return (string) max(0, (int) $value);
        }

        if ($type === 'switch') {
            return (int) $value === 1 ? '1' : '0';
        }

        if (in_array($type, ['checkbox', 'select_multiple', 'daterange', 'datetimerange', 'timerange', 'images', 'files'], true)) {
            if (is_array($value)) {
                return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
            }

            $value = trim((string) $value);
            if ($value === '') {
                return '[]';
            }

            json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }

            return json_encode(array_values(array_filter(array_map('trim', explode(',', $value)), fn ($item) => $item !== '')), JSON_UNESCAPED_UNICODE);
        }

        return trim((string) $value);
    }
}
