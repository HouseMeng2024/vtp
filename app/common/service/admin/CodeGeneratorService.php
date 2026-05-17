<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\admin\AdminMenu;
use app\common\model\admin\AdminRoleMenu;
use RuntimeException;
use think\facade\Db;

class CodeGeneratorService
{
    private const FIELD_TYPES = ['text', 'textarea', 'number', 'decimal', 'switch', 'select', 'radio', 'checkbox', 'image', 'images', 'file', 'date', 'datetime', 'richtext'];

    /**
     * 预检本次生成会影响的文件、数据表和菜单。
     */
    public function preview(array $payload): array
    {
        $config = $this->normalizeConfig($payload);
        $options = $this->normalizeOptionsConfig($payload['options'] ?? []);
        $module = $config['module'];
        $class = $this->studly($module);
        $checks = [
            'backend_files' => [],
            'frontend_files' => [],
            'api_files' => [],
            'database' => [],
            'menus' => [],
        ];

        if ($options['write_backend']) {
            foreach ([
                'app/common/model/' . $class . '.php',
                'app/common/service/admin/' . $class . 'Service.php',
                'app/admin/controller/' . $class . '.php',
            ] as $path) {
                $checks['backend_files'][] = $this->fileCheck($path);
            }
        }

        if ($options['write_frontend']) {
            foreach ([
                'admin-web/src/views/' . $module . '/' . $class . 'ListView.vue',
                'admin-web/src/views/' . $module . '/index.vue',
            ] as $path) {
                $checks['frontend_files'][] = $this->fileCheck($path);
            }
        }

        if ($options['merge_api']) {
            $checks['api_files'][] = $this->fileCheck('admin-web/src/api/' . $module . '.ts');
        }

        if ($options['execute_schema']) {
            $checks['database'][] = [
                'label' => $config['table'],
                'path' => $this->physicalTable($config['table']),
                'exists' => $this->tableExists($config['table']),
            ];
        }

        if ($options['create_menu']) {
            $permissions = [
                "admin:{$module}:list",
                "admin:{$module}:create",
                "admin:{$module}:update",
                "admin:{$module}:delete",
            ];

            if ($this->hasStatusField($config['fields'])) {
                $permissions[] = "admin:{$module}:status";
            }

            foreach ($permissions as $permission) {
                $checks['menus'][] = [
                    'label' => $permission,
                    'path' => $permission,
                    'exists' => (bool) AdminMenu::where('permission', $permission)->find(),
                ];
            }
        }

        return [
            'module' => $module,
            'route_path' => $this->menuRoutePath($config, $options),
            'has_conflict' => $this->hasPreviewConflict($checks),
            'checks' => $checks,
        ];
    }

    /**
     * 获取最近一次代码生成结果，便于开发环境热更新刷新后恢复展示。
     */
    public function recent(): ?array
    {
        $path = $this->recentResultPath();

        if (!is_file($path)) {
            return null;
        }

        $result = json_decode((string) file_get_contents($path), true);

        return is_array($result) ? $result : null;
    }

    /**
     * 根据后台表单配置生成 CRUD 文件。
     */
    public function generate(array $payload): array
    {
        $config = $this->normalizeConfig($payload);
        $options = $this->normalizeOptionsConfig($payload['options'] ?? []);
        $rollback = $this->rollbackSnapshot($config, $options);
        $configPath = $this->writeConfig($config);

        try {
            $output = $this->runGenerator($configPath);
            $outputDir = $this->projectPath('runtime/generator/' . $config['module']);
            $installedFiles = [];
            $mergedFiles = [];
            $messages = [];

            if ($options['write_backend']) {
                $installedFiles = array_merge($installedFiles, $this->installBackendFiles($outputDir, $options['overwrite_existing']));
            }

            if ($options['write_frontend']) {
                $installedFiles = array_merge($installedFiles, $this->installFrontendFiles($outputDir, $options['overwrite_existing']));
            }

            if ($options['merge_api']) {
                $mergedFiles = array_merge($mergedFiles, $this->mergeApi($outputDir, $config['module'], $options['overwrite_existing']));
            }

            if ($options['create_menu']) {
                $messages[] = '菜单已自动写入数据库';
                $this->syncMenu($config, $options);
            }

            if ($options['execute_schema']) {
                $messages[] = '建表 SQL 已执行';
                $this->executeSchema($outputDir);
            }

            $result = [
                'module'          => $config['module'],
                'output_dir'      => $this->projectRelativePath($outputDir),
                'config_path'     => $this->projectRelativePath($configPath),
                'files'           => $this->listFiles($outputDir),
                'installed_files' => array_map(fn (string $file) => $this->projectRelativePath($file), $installedFiles),
                'merged_files'    => array_map(fn (string $file) => $this->projectRelativePath($file), $mergedFiles),
                'messages'        => $messages,
                'log'             => array_map(fn (string $line) => $this->projectRelativeLog($line), $output),
            ];

            $this->saveRecentResult($result);

            return $result;
        } catch (\Throwable $exception) {
            $this->rollbackGenerated($rollback);
            throw $exception instanceof RuntimeException ? $exception : new RuntimeException($exception->getMessage());
        }
    }

    /**
     * 清理指定模块的生成产物。
     */
    public function cleanup(array $payload): array
    {
        $module = $this->normalizeName((string) ($payload['module'] ?? ''));
        $table = $this->normalizeName((string) ($payload['table'] ?? $module));

        if ($module === '') {
            throw new RuntimeException('请输入要清理的模块标识');
        }

        $class = $this->studly($module);
        $deleted = [];
        $paths = [
            'app/common/model/' . $class . '.php',
            'app/common/service/admin/' . $class . 'Service.php',
            'app/admin/controller/' . $class . '.php',
            'admin-web/src/api/' . $module . '.ts',
            'admin-web/src/views/' . $module,
            'runtime/generator/' . $module,
            'runtime/generator/config/' . $module . '.php',
        ];

        foreach ($paths as $path) {
            if ($this->deleteProjectPath($path)) {
                $deleted[] = '/' . ltrim($path, '/');
            }
        }

        $menuIds = AdminMenu::whereLike('permission', "admin:{$module}:%")->column('id');

        if ($menuIds) {
            AdminRoleMenu::whereIn('menu_id', $menuIds)->delete();
            AdminMenu::whereIn('id', $menuIds)->delete();
            $deleted[] = '菜单权限：admin:' . $module . ':*';
        }

        if ($table !== '' && $this->tableExists($table)) {
            $this->dropTable($table);
            $deleted[] = '数据表：' . $this->physicalTable($table);
        }

        return [
            'module' => $module,
            'deleted' => $deleted,
        ];
    }

    /**
     * 获取生成选项默认值。
     */
    private function defaultOptions(): array
    {
        return [
            'write_backend'      => true,
            'write_frontend'     => true,
            'merge_api'          => true,
            'create_menu'        => true,
            'menu_parent_id'     => null,
            'execute_schema'     => true,
            'overwrite_existing' => true,
        ];
    }

    /**
     * 规范生成选项。
     */
    private function normalizeOptionsConfig(array $options): array
    {
        $defaults = $this->defaultOptions();

        foreach ($defaults as $key => $value) {
            $defaults[$key] = $key === 'menu_parent_id'
                ? max(0, (int) ($options[$key] ?? 0))
                : (array_key_exists($key, $options) ? (bool) $options[$key] : $value);
        }

        return $defaults;
    }

    /**
     * 规范并校验生成配置。
     */
    private function normalizeConfig(array $payload): array
    {
        $module = $this->normalizeName((string) ($payload['module'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        $table = $this->normalizeName((string) ($payload['table'] ?? $module));
        $routePath = trim((string) ($payload['route_path'] ?? ''));
        $fields = $payload['fields'] ?? [];

        if ($module === '' || $title === '' || $table === '') {
            throw new RuntimeException('模块标识、模块名称、数据表名不能为空');
        }

        if (!$fields || !is_array($fields)) {
            throw new RuntimeException('至少需要配置一个字段');
        }

        return [
            'module'      => $module,
            'title'       => $title,
            'table'       => $table,
            'table_prefix' => $this->tablePrefix(),
            'route_path'  => $this->normalizeRoutePath($routePath !== '' ? $routePath : $module),
            'menu_parent' => $this->normalizeName((string) ($payload['menu_parent'] ?? '')),
            'fields'      => $this->normalizeFields($fields),
        ];
    }

    /**
     * 规范并校验字段配置。
     */
    private function normalizeFields(array $fields): array
    {
        $items = [];
        $names = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = $this->normalizeName((string) ($field['name'] ?? ''));
            $label = trim((string) ($field['label'] ?? ''));
            $type = (string) ($field['type'] ?? 'text');

            if ($name === '' || $label === '') {
                throw new RuntimeException('字段名和字段标题不能为空');
            }

            if (in_array($name, $names, true)) {
                throw new RuntimeException('字段名不能重复：' . $name);
            }

            if (!in_array($type, self::FIELD_TYPES, true)) {
                throw new RuntimeException('不支持的字段类型：' . $type);
            }

            $names[] = $name;
            $items[] = [
                'name'       => $name,
                'label'      => $label,
                'type'       => $type,
                'required'   => !empty($field['required']),
                'search'     => !empty($field['search']),
                'list'       => !empty($field['list']),
                'default'    => $this->normalizeDefault($field, $type),
                'max_length' => $this->normalizeMaxLength($field, $type),
                'min'        => $this->normalizeMin($field, $type),
                'max'        => $this->normalizeMax($field, $type),
                'dict_type'  => $this->normalizeDictType($field, $type),
                'options'    => $this->normalizeOptions($field['options'] ?? []),
            ];
        }

        if (!$items) {
            throw new RuntimeException('至少需要配置一个有效字段');
        }

        return $items;
    }

    /**
     * 规范字段默认值。
     */
    private function normalizeDefault(array $field, string $type): int|float|string
    {
        $default = $field['default'] ?? '';

        return match ($type) {
            'number', 'switch' => (int) $default,
            'decimal' => (float) $default,
            default => trim((string) $default),
        };
    }

    /**
     * 规范文本字段最大长度。
     */
    private function normalizeMaxLength(array $field, string $type): int
    {
        $default = match ($type) {
            'textarea' => 500,
            'richtext' => 5000,
            'image', 'file' => 500,
            default => 255,
        };

        return max(1, min(10000, (int) ($field['max_length'] ?? $default)));
    }

    /**
     * 规范数字字段最小值。
     */
    private function normalizeMin(array $field, string $type): int|float
    {
        $value = $field['min'] ?? 0;

        return $type === 'decimal' ? (float) $value : (int) $value;
    }

    /**
     * 规范数字字段最大值。
     */
    private function normalizeMax(array $field, string $type): int|float
    {
        $value = $field['max'] ?? 999999;

        return $type === 'decimal' ? (float) $value : (int) $value;
    }

    /**
     * 规范字段绑定的字典标识。
     */
    private function normalizeDictType(array $field, string $type): string
    {
        if (!in_array($type, ['select', 'radio', 'checkbox'], true)) {
            return '';
        }

        return $this->normalizeName((string) ($field['dict_type'] ?? ''));
    }

    /**
     * 获取回滚快照，只回滚本次新建的资源。
     */
    private function rollbackSnapshot(array $config, array $options): array
    {
        $module = $config['module'];
        $class = $this->studly($module);
        $files = [];

        foreach ([
            'app/common/model/' . $class . '.php',
            'app/common/service/admin/' . $class . 'Service.php',
            'app/admin/controller/' . $class . '.php',
            'admin-web/src/api/' . $module . '.ts',
            'admin-web/src/views/' . $module . '/' . $class . 'ListView.vue',
            'admin-web/src/views/' . $module . '/index.vue',
        ] as $path) {
            $files[$path] = is_file($this->projectPath($path));
        }

        return [
            'module' => $module,
            'table' => $config['table'],
            'table_exists' => $this->tableExists($config['table']),
            'menu_ids' => AdminMenu::whereLike('permission', "admin:{$module}:%")->column('id'),
            'files' => $files,
            'options' => $options,
        ];
    }

    /**
     * 生成失败时回滚本次新建资源。
     */
    private function rollbackGenerated(array $snapshot): void
    {
        foreach ($snapshot['files'] as $path => $existed) {
            if (!$existed) {
                $this->deleteProjectPath($path);
            }
        }

        $module = $snapshot['module'];
        $currentMenuIds = AdminMenu::whereLike('permission', "admin:{$module}:%")->column('id');
        $newMenuIds = array_values(array_diff($currentMenuIds, $snapshot['menu_ids']));

        if ($newMenuIds) {
            AdminRoleMenu::whereIn('menu_id', $newMenuIds)->delete();
            AdminMenu::whereIn('id', $newMenuIds)->delete();
        }

        if (!$snapshot['table_exists'] && $this->tableExists($snapshot['table'])) {
            $this->dropTable($snapshot['table']);
        }
    }

    /**
     * 规范下拉选项。
     */
    private function normalizeOptions(array $options): array
    {
        $items = [];

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $label = trim((string) ($option['label'] ?? ''));
            $value = trim((string) ($option['value'] ?? ''));

            if ($label === '' || $value === '') {
                continue;
            }

            $items[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $items;
    }

    /**
     * 安装生成的后端文件到项目目录。
     */
    private function installBackendFiles(string $outputDir, bool $overwrite): array
    {
        $sourceRoot = $outputDir . DIRECTORY_SEPARATOR . 'backend';

        if (!is_dir($sourceRoot)) {
            return [];
        }

        return $this->copyGeneratedFiles($sourceRoot, $this->projectPath(), $overwrite);
    }

    /**
     * 安装生成的前端文件到 admin-web，目录不存在时保留在 runtime。
     */
    private function installFrontendFiles(string $outputDir, bool $overwrite): array
    {
        $sourceRoot = $outputDir . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'admin-web';
        $targetRoot = $this->projectPath('admin-web');

        if (!is_dir($sourceRoot) || !is_dir($targetRoot)) {
            return [];
        }

        return $this->copyGeneratedFiles($sourceRoot, $targetRoot, $overwrite);
    }

    /**
     * 复制生成文件，默认不覆盖已存在文件。
     */
    private function copyGeneratedFiles(string $sourceRoot, string $targetRoot, bool $overwrite): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relative = str_replace($sourceRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $target = $targetRoot . DIRECTORY_SEPARATOR . $relative;

            if (is_file($target) && !$overwrite) {
                throw new RuntimeException('目标文件已存在，请勾选覆盖同名文件：' . $target);
            }

            $dir = dirname($target);

            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('目录创建失败：' . $dir);
            }

            if (!copy($file->getPathname(), $target)) {
                throw new RuntimeException('文件写入失败：' . $target);
            }

            $files[] = $target;
        }

        sort($files);

        return $files;
    }

    /**
     * 自动合并前端 API。
     */
    private function mergeApi(string $outputDir, string $module, bool $overwrite): array
    {
        $targetDir = $this->projectPath('admin-web/src/api');

        if (!is_dir($targetDir)) {
            return [];
        }

        $target = $targetDir . DIRECTORY_SEPARATOR . $module . '.ts';

        $snippetPath = $outputDir . '/snippets/api.ts';

        if (!is_file($snippetPath)) {
            throw new RuntimeException('前端 API 片段不存在：' . $snippetPath);
        }

        $snippet = trim((string) file_get_contents($snippetPath));

        if ($snippet === '') {
            return [];
        }

        if (is_file($target) && !$overwrite) {
            throw new RuntimeException('前端 API 文件已存在，请勾选覆盖同名文件：' . $target);
        }

        file_put_contents($target, $snippet . "\n");

        return [$target];
    }

    /**
     * 自动写入后台菜单和按钮权限。
     */
    private function syncMenu(array $config, array $options): void
    {
        $module = $config['module'];
        $title = $config['title'];
        $parentId = $this->resolveParentMenuId((int) $options['menu_parent_id']);
        $parentPath = $this->parentMenuPath($parentId);
        $menu = AdminMenu::where('permission', "admin:{$module}:list")->find();
        $payload = [
            'parent_id'  => $parentId,
            'type'       => 2,
            'title'      => $title . '管理',
            'permission' => "admin:{$module}:list",
            'path'       => rtrim($parentPath, '/') . '/' . $config['route_path'],
            'component'  => $module . '/index',
            'icon'       => 'Document',
            'sort'       => 100,
            'visible'    => 1,
            'status'     => 1,
            'remark'     => '',
        ];

        if ($menu) {
            $menu->save($payload);
        } else {
            $menu = AdminMenu::create($payload);
        }

        $this->syncButtonMenu((int) $menu->id, "新增{$title}", "admin:{$module}:create", 100);
        $this->syncButtonMenu((int) $menu->id, "编辑{$title}", "admin:{$module}:update", 101);

        if ($this->hasStatusField($config['fields'])) {
            $this->syncButtonMenu((int) $menu->id, "启用禁用{$title}", "admin:{$module}:status", 102);
        }

        $this->syncButtonMenu((int) $menu->id, "删除{$title}", "admin:{$module}:delete", 103);
        $this->grantSuperAdminMenus((int) $menu->id);
    }

    /**
     * 自动写入按钮权限菜单。
     */
    private function syncButtonMenu(int $parentId, string $title, string $permission, int $sort): void
    {
        $menu = AdminMenu::where('permission', $permission)->find();
        $payload = [
            'parent_id'  => $parentId,
            'type'       => 3,
            'title'      => $title,
            'permission' => $permission,
            'path'       => '',
            'component'  => '',
            'icon'       => '',
            'sort'       => $sort,
            'visible'    => 0,
            'status'     => 1,
            'remark'     => '',
        ];

        if ($menu) {
            $menu->save($payload);
            return;
        }

        AdminMenu::create($payload);
    }

    /**
     * 给超级管理员角色授权本次生成的菜单。
     */
    private function grantSuperAdminMenus(int $menuId): void
    {
        $menuIds = AdminMenu::where('id', $menuId)
            ->whereOr('parent_id', $menuId)
            ->column('id');

        foreach ($menuIds as $id) {
            if (!AdminRoleMenu::where('role_id', 1)->where('menu_id', $id)->find()) {
                AdminRoleMenu::create([
                    'role_id'     => 1,
                    'menu_id'     => $id,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * 执行生成的建表 SQL。
     */
    private function executeSchema(string $outputDir): void
    {
        $sql = trim((string) file_get_contents($outputDir . '/snippets/schema.sql'));

        if ($sql === '') {
            return;
        }

        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $statement = $this->applyTablePrefixToSql($statement);
            Db::execute($statement);
            $this->syncCreateTableSchema($statement);
        }
    }

    /**
     * 根据 CREATE TABLE 语句补齐已存在表缺失的字段和普通索引。
     */
    private function syncCreateTableSchema(string $statement): void
    {
        if (!preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
            return;
        }

        $table = $matches[1];
        $columns = $this->existingColumns($table);
        $indexes = $this->existingIndexes($table);
        $lines = preg_split('/\R/', $statement) ?: [];

        foreach ($lines as $line) {
            $definition = rtrim(trim($line), ',');

            if (preg_match('/^`([a-zA-Z0-9_]+)`\s+(.+)$/', $definition, $columnMatches)) {
                $column = $columnMatches[1];

                if (!in_array($column, $columns, true)) {
                    Db::execute("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
                    $columns[] = $column;
                }

                continue;
            }

            if (preg_match('/^KEY\s+`([a-zA-Z0-9_]+)`\s+\((.+)\)$/i', $definition, $indexMatches)) {
                $index = $indexMatches[1];

                if (!in_array($index, $indexes, true)) {
                    Db::execute("ALTER TABLE `{$table}` ADD {$definition}");
                    $indexes[] = $index;
                }
            }
        }
    }

    /**
     * 获取数据表已有字段。
     */
    private function existingColumns(string $table): array
    {
        $table = $this->physicalTable($table);

        return array_map(
            'strval',
            array_column(Db::query('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]), 'COLUMN_NAME')
        );
    }

    /**
     * 获取数据表已有索引。
     */
    private function existingIndexes(string $table): array
    {
        $table = $this->physicalTable($table);

        return array_map(
            'strval',
            array_column(Db::query('SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]), 'INDEX_NAME')
        );
    }

    /**
     * 解析父级菜单 ID。
     */
    private function resolveParentMenuId(int $menuParentId): int
    {
        if ($menuParentId <= 0) {
            return 0;
        }

        $menu = AdminMenu::where('id', $menuParentId)->where('type', 1)->find();

        return $menu ? (int) $menu->id : 0;
    }

    /**
     * 获取父级菜单路径。
     */
    private function parentMenuPath(int $parentId): string
    {
        if ($parentId <= 0) {
            return '';
        }

        $menu = AdminMenu::find($parentId);
        $path = $menu ? trim((string) $menu->path) : '';

        return $path === '' ? '' : '/' . trim($path, '/');
    }

    /**
     * 判断配置是否包含状态字段。
     */
    private function hasStatusField(array $fields): bool
    {
        foreach ($fields as $field) {
            if (($field['name'] ?? '') === 'status' && ($field['type'] ?? '') === 'switch') {
                return true;
            }
        }

        return false;
    }

    /**
     * 转换为大驼峰类名。
     */
    private function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    /**
     * 写入临时生成配置。
     */
    private function writeConfig(array $config): string
    {
        $dir = $this->projectPath('runtime/generator/config');

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('生成配置目录创建失败');
        }

        $path = $dir . DIRECTORY_SEPARATOR . $config['module'] . '.php';
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('生成配置写入失败');
        }

        return $path;
    }

    /**
     * 保存最近一次生成结果。
     */
    private function saveRecentResult(array $result): void
    {
        $path = $this->recentResultPath();
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        file_put_contents($path, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * 最近生成结果文件路径。
     */
    private function recentResultPath(): string
    {
        return $this->projectPath('runtime/generator/recent_result.json');
    }

    /**
     * 调用生成器脚本。
     */
    private function runGenerator(string $configPath): array
    {
        $script = $this->projectPath('generator/make_crud.php');
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($configPath) . ' 2>&1';
        $output = [];
        $code = 0;

        exec($command, $output, $code);

        if ($code !== 0) {
            throw new RuntimeException('代码生成失败：' . implode("\n", $output));
        }

        return $output;
    }

    /**
     * 获取生成文件列表。
     */
    private function listFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $files[] = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
        }

        sort($files);

        return $files;
    }

    /**
     * 获取项目文件预检结果。
     */
    private function fileCheck(string $path): array
    {
        return [
            'label' => basename($path),
            'path' => '/' . ltrim($path, '/'),
            'exists' => is_file($this->projectPath($path)),
        ];
    }

    /**
     * 删除项目内文件或目录。
     */
    private function deleteProjectPath(string $path): bool
    {
        $target = $this->projectPath($path);

        if (is_file($target)) {
            return unlink($target);
        }

        if (!is_dir($target)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        return rmdir($target);
    }

    /**
     * 判断数据表是否存在。
     */
    private function tableExists(string $table): bool
    {
        $table = $this->physicalTable($table);
        $rows = Db::query('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]);

        return (int) ($rows[0]['total'] ?? 0) > 0;
    }

    /**
     * 获取当前数据库表前缀。
     */
    private function tablePrefix(): string
    {
        $connection = (string) config('database.default', 'mysql');
        $config = (array) config('database.connections.' . $connection, []);

        return (string) ($config['prefix'] ?? '');
    }

    /**
     * 将逻辑表名转换成数据库真实表名。
     */
    private function physicalTable(string $table): string
    {
        $table = str_replace('`', '', $table);
        $prefix = $this->tablePrefix();

        if ($prefix === '' || str_starts_with($table, $prefix)) {
            return $table;
        }

        return $prefix . $table;
    }

    /**
     * 删除逻辑表名对应的真实数据表。
     */
    private function dropTable(string $table): void
    {
        Db::execute('DROP TABLE IF EXISTS `' . $this->physicalTable($table) . '`');
    }

    /**
     * 给生成器输出的原生 SQL 补齐数据库表前缀。
     */
    private function applyTablePrefixToSql(string $sql): string
    {
        return preg_replace_callback(
            '/\b(CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|DROP\s+TABLE\s+IF\s+EXISTS|INSERT\s+INTO|ALTER\s+TABLE)\s+`([a-zA-Z0-9_]+)`/i',
            fn (array $matches) => $matches[1] . ' `' . $this->physicalTable($matches[2]) . '`',
            $sql
        ) ?? $sql;
    }

    /**
     * 判断预检结果里是否存在冲突项。
     */
    private function hasPreviewConflict(array $checks): bool
    {
        foreach ($checks as $items) {
            foreach ($items as $item) {
                if (!empty($item['exists'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 获取本次生成的菜单访问路径。
     */
    private function menuRoutePath(array $config, array $options): string
    {
        $parentId = $this->resolveParentMenuId((int) $options['menu_parent_id']);
        $parentPath = $this->parentMenuPath($parentId);

        return rtrim($parentPath, '/') . '/' . $config['route_path'];
    }

    /**
     * 规范配置标识名称。
     */
    private function normalizeName(string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $name) ?? '', '_'));
    }

    /**
     * 规范前端菜单路由路径。
     */
    private function normalizeRoutePath(string $path): string
    {
        $normalized = strtolower(trim(preg_replace('/[^a-zA-Z0-9_\/-]/', '-', $path) ?? '', '/-'));

        return $normalized !== '' ? $normalized : 'index';
    }

    /**
     * 获取项目内文件或目录的绝对路径。
     */
    private function projectPath(string $path = ''): string
    {
        $root = dirname(__DIR__, 4);

        return $path === '' ? $root : $root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * 转换为项目根目录相对路径，避免后台页面暴露本机绝对路径。
     */
    private function projectRelativePath(string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $this->projectPath()), '/');
        $normalized = str_replace('\\', '/', $path);

        if ($normalized === $root) {
            return '/';
        }

        if (str_starts_with($normalized, $root . '/')) {
            return '/' . ltrim(substr($normalized, strlen($root)), '/');
        }

        return $normalized;
    }

    /**
     * 转换生成日志中的项目绝对路径。
     */
    private function projectRelativeLog(string $line): string
    {
        $root = rtrim(str_replace('\\', '/', $this->projectPath()), '/');

        return str_replace($root, '', str_replace('\\', '/', $line));
    }
}
