<?php
declare(strict_types=1);

/**
 * Simple CRUD generator for the admin backend.
 *
 * Usage:
 * php generator/make_crud.php runtime/generator/config/module.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script can only run in CLI.\n");
    exit(1);
}

$configPath = $argv[1] ?? '';

if ($configPath === '' || !is_file($configPath)) {
    fwrite(STDERR, "Usage: php generator/make_crud.php runtime/generator/config/module.php\n");
    exit(1);
}

$config = require $configPath;

if (!is_array($config)) {
    fwrite(STDERR, "Config must return an array.\n");
    exit(1);
}

$generator = new CrudGenerator($config);
$generator->generate();

final class CrudGenerator
{
    private string $module;
    private string $title;
    private string $table;
    private string $class;
    private string $camel;
    private string $route;
    private string $outputDir;
    private array $fields;

    public function __construct(private array $config)
    {
        $this->module = $this->normalizeName((string) ($config['module'] ?? ''));
        $this->title = trim((string) ($config['title'] ?? ''));
        $this->table = $this->normalizeName((string) ($config['table'] ?? $this->module));
        $this->fields = $config['fields'] ?? [];

        if ($this->module === '' || $this->title === '' || !$this->fields) {
            throw new RuntimeException('module、title、fields 不能为空');
        }

        $this->class = $this->studly($this->module);
        $this->camel = lcfirst($this->class);
        $routePath = trim((string) ($config['route_path'] ?? ''));
        $this->route = $this->normalizeRoutePath($routePath !== '' ? $routePath : $this->module);
        $this->outputDir = dirname(__DIR__) . '/runtime/generator/' . $this->module;
    }

    public function generate(): void
    {
        $files = [
            'backend/app/common/model/' . $this->class . '.php' => $this->modelPhp(),
            'backend/app/common/service/admin/' . $this->class . 'Service.php' => $this->servicePhp(),
            'backend/app/admin/controller/' . $this->class . '.php' => $this->controllerPhp(),
            'frontend/admin-web/src/views/' . $this->module . '/' . $this->class . 'ListView.vue' => $this->vue(),
            'frontend/admin-web/src/views/' . $this->module . '/index.vue' => $this->vueIndex(),
            'snippets/api.ts' => $this->apiSnippet(),
            'snippets/schema.sql' => $this->schemaSql(),
            'snippets/menu.sql' => $this->menuSql(),
        ];

        foreach ($files as $relativePath => $content) {
            $path = $this->outputDir . '/' . $relativePath;
            $dir = dirname($path);

            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('目录创建失败：' . $dir);
            }

            file_put_contents($path, $content);
        }

        echo "Generated CRUD module: {$this->module}\n";
        echo "Output: {$this->outputDir}\n";
    }

    private function modelPhp(): string
    {
        return <<<PHP
<?php
declare (strict_types = 1);

namespace app\\common\\model;

use think\\Model;
use think\\model\\concern\\SoftDelete;

class {$this->class} extends Model
{
    use SoftDelete;
}

PHP;
    }

    private function servicePhp(): string
    {
        $searchFields = array_values(array_filter($this->fields, fn (array $field) => !empty($field['search']) && ($field['type'] ?? '') !== 'switch'));
        $statusField = $this->statusField();
        $sortLines = $this->hasField('sort')
            ? "            ->order('sort', 'asc')\n            ->order('id', 'desc')"
            : "            ->order('id', 'desc')";
        $searchCode = '';

        if ($searchFields) {
            $first = array_shift($searchFields);
            $searchCode .= "        if (\$keyword !== '') {\n";
            $searchCode .= "            \$query->where(function (\$query) use (\$keyword) {\n";
            $searchCode .= "                \$query->whereLike('{$first['name']}', '%' . \$keyword . '%')";

            foreach ($searchFields as $field) {
                $searchCode .= "\n                    ->whereOr('{$field['name']}', 'like', '%' . \$keyword . '%')";
            }

            $searchCode .= ";\n            });\n        }\n\n";
        }

        $statusCode = $statusField
            ? "        \$status = \$filters['{$statusField}'] ?? '';\n\n        if (\$status !== '' && \$status !== null) {\n            \$query->where('{$statusField}', (int) \$status);\n        }\n\n"
            : '';
        $pageReturn = $this->hasJsonFields()
            ? "        \$page = \$query\n{$sortLines}\n            ->paginate([\n                'list_rows' => \$limit,\n                'page'      => \$page,\n            ])\n            ->toArray();\n        \$page['data'] = array_map(fn (array \$row) => \$this->formatRow(\$row), \$page['data'] ?? []);\n\n        return \$page;"
            : "        return \$query\n{$sortLines}\n            ->paginate([\n                'list_rows' => \$limit,\n                'page'      => \$page,\n            ])\n            ->toArray();";
        $formatRowMethod = $this->formatRowMethodPhp();
        $changeStatusMethod = $statusField ? <<<PHP

    /**
     * 修改{$this->title}状态。
     */
    public function changeStatus(int \$id, int \$status): array
    {
        \${$this->camel} = \$this->find{$this->class}(\$id);
        \${$this->camel}->save(['{$statusField}' => \$status === 1 ? 1 : 0]);

        return \$this->formatRow(\${$this->camel}->toArray());
    }
PHP : '';

        return <<<PHP
<?php
declare (strict_types = 1);

namespace app\\common\\service\\admin;

use app\\common\\model\\{$this->class};
use RuntimeException;

class {$this->class}Service
{
    /**
     * 获取{$this->title}分页列表。
     */
    public function page(array \$filters): array
    {
        \$page = max(1, (int) (\$filters['page'] ?? 1));
        \$limit = min(100, max(1, (int) (\$filters['limit'] ?? 20)));
        \$keyword = trim((string) (\$filters['keyword'] ?? ''));

        \$query = {$this->class}::where([]);

{$searchCode}{$statusCode}{$pageReturn}
    }

    /**
     * 创建{$this->title}。
     */
    public function create(array \$data): array
    {
        return \$this->formatRow({$this->class}::create(\$this->filterPayload(\$data))->toArray());
    }

    /**
     * 更新{$this->title}。
     */
    public function update(int \$id, array \$data): array
    {
        \${$this->camel} = \$this->find{$this->class}(\$id);
        \${$this->camel}->save(\$this->filterPayload(\$data));

        return \$this->formatRow(\${$this->camel}->toArray());
    }
{$changeStatusMethod}

    /**
     * 删除{$this->title}。
     */
    public function delete(int \$id): void
    {
        \$this->find{$this->class}(\$id)->delete();
    }

    /**
     * 查找{$this->title}，不存在时抛出业务异常。
     */
    private function find{$this->class}(int \$id): {$this->class}
    {
        \${$this->camel} = {$this->class}::find(\$id);

        if (!\${$this->camel}) {
            throw new RuntimeException('{$this->title}不存在');
        }

        return \${$this->camel};
    }

    /**
     * 过滤并校验{$this->title}表单数据。
     */
    private function filterPayload(array \$data): array
    {
{$this->filterPayloadPhp()}    }
{$formatRowMethod}
}

PHP;
    }

    private function controllerPhp(): string
    {
        $statusMethod = $this->statusField() ? <<<PHP

    /**
     * 修改{$this->title}状态。
     */
    public function status(int \$id): Response
    {
        try {
            return ApiResponse::success((new {$this->class}Service())->changeStatus(\$id, (int) \$this->request->param('status', 0)));
        } catch (RuntimeException \$exception) {
            return ApiResponse::fail(\$exception->getMessage());
        }
    }
PHP : '';

        return <<<PHP
<?php
declare (strict_types = 1);

namespace app\\admin\\controller;

use app\\common\\base\\AdminBase;
use app\\common\\service\\admin\\{$this->class}Service;
use app\\common\\support\\ApiResponse;
use RuntimeException;
use think\\Response;

class {$this->class} extends AdminBase
{
    /**
     * 获取{$this->title}分页列表。
     */
    public function index(): Response
    {
        return ApiResponse::success((new {$this->class}Service())->page(\$this->request->get()));
    }

    /**
     * 新增{$this->title}。
     */
    public function save(): Response
    {
        try {
            return ApiResponse::success((new {$this->class}Service())->create(\$this->request->post()));
        } catch (RuntimeException \$exception) {
            return ApiResponse::fail(\$exception->getMessage());
        }
    }

    /**
     * 更新{$this->title}。
     */
    public function update(int \$id): Response
    {
        try {
            return ApiResponse::success((new {$this->class}Service())->update(\$id, \$this->request->put()));
        } catch (RuntimeException \$exception) {
            return ApiResponse::fail(\$exception->getMessage());
        }
    }
{$statusMethod}

    /**
     * 删除{$this->title}。
     */
    public function delete(int \$id): Response
    {
        try {
            (new {$this->class}Service())->delete(\$id);
            return ApiResponse::success();
        } catch (RuntimeException \$exception) {
            return ApiResponse::fail(\$exception->getMessage());
        }
    }
}

PHP;
    }

    private function vue(): string
    {
        $rowType = $this->class . 'Row';
        $payloadType = $this->class . 'Payload';
        $hasFileSelector = (bool) array_filter($this->fields, fn (array $field) => in_array($field['type'] ?? '', ['image', 'images', 'file'], true));
        $hasDictFields = $this->hasDictFields();
        $statusField = $this->statusField();
        $imports = [
            "create{$this->class}",
            "delete{$this->class}",
            "fetch{$this->class}s",
            "update{$this->class}",
            "type {$payloadType}",
            "type {$rowType}",
        ];

        if ($statusField) {
            $imports[] = "update{$this->class}Status";
        }

        $fileSelectorImport = $hasFileSelector ? "import FileSelector from '../../components/FileSelector.vue'\n" : '';
        $dictImport = $hasDictFields ? "import { fetchDictOptions } from '../../api/system'\n" : '';
        $fileSelectorState = $hasFileSelector ? "const fileSelectorVisible = ref(false)\nconst selectingFileField = ref<keyof {$payloadType} | ''>('')\nconst fileSelectorAcceptType = ref<'image' | 'file'>('file')\nconst fileSelectorMultiple = ref(false)\n" : '';
        $fileSelectorMethods = $hasFileSelector ? $this->vueFileSelectorMethods() : '';
        $fileSelectorTemplate = $hasFileSelector ? "\n    <FileSelector v-model=\"fileSelectorVisible\" :accept-type=\"fileSelectorAcceptType\" :multiple=\"fileSelectorMultiple\" scene=\"{$this->module}\" :current-url=\"currentFileUrl()\" @select=\"handleFileSelected\" />" : '';
        $dictMethods = $hasDictFields ? $this->vueDictMethods() : '';

        return <<<VUE
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  {$this->joinImport($imports)}
} from '../../api/{$this->module}'
import { useAuthStore } from '../../stores/auth'
{$dictImport}
{$fileSelectorImport}
const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const detailVisible = ref(false)
const editingId = ref<number | null>(null)
const detailRow = ref<{$rowType} | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<{$rowType}[]>([])
const total = ref(0)
{$fileSelectorState}{$this->vueOptionConstants()}const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
  {$this->vueQueryStatus()}
})
const form = reactive<{$payloadType}>({
{$this->vueFormDefaults()}})
const rules: FormRules = {
{$this->vueRules()}}

async function loadData() {
  loading.value = true
  try {
    const data = await fetch{$this->class}s(query)
    rows.value = data.data
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  query.page = 1
  loadData()
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
{$this->vueFormDefaults(4)}  })
  formRef.value?.clearValidate()
}

function openCreate() {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: {$rowType}) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
{$this->vueAssignFromRow()}  })
  dialogVisible.value = true
}

function openDetail(row: {$rowType}) {
  detailRow.value = row
  detailVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true

  try {
    if (editingId.value) {
      await update{$this->class}(editingId.value, form)
      ElMessage.success('保存成功')
    } else {
      await create{$this->class}(form)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}
{$this->vueStatusMethod()}
async function handleDelete(row: {$rowType}) {
  await ElMessageBox.confirm(`确定删除{$this->title}「\${row.{$this->displayField()}}」吗？`, '删除确认', {
    type: 'warning',
  })
  await delete{$this->class}(row.id)
  ElMessage.success('删除成功')
  loadData()
}
{$fileSelectorMethods}
{$dictMethods}
{$this->vueFormatMethods()}
onMounted(() => {
  loadData()
{$this->vueOnMountedDictLoad()}});
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">{$this->title}管理</div>
        <el-button v-if="authStore.hasPermission('admin:{$this->module}:create')" type="primary" @click="openCreate">新增</el-button>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="请输入关键词" @keyup.enter="handleSearch" />
      </el-form-item>
{$this->vueSearchStatus()}      <el-form-item>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%">
        <el-table-column prop="id" label="ID" width="90" />
{$this->vueTableColumns()}        <el-table-column prop="create_time" label="创建时间" min-width="170" />
        <el-table-column label="操作" width="230" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button link type="primary" @click="openDetail(row)">查看</el-button>
              <el-button v-if="authStore.hasPermission('admin:{$this->module}:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
{$this->vueStatusButton()}              <el-button v-if="authStore.hasPermission('admin:{$this->module}:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
            </el-space>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-pagination
      v-model:current-page="query.page"
      v-model:page-size="query.limit"
      class="page-pagination"
      layout="total, sizes, prev, pager, next, jumper"
      :total="total"
      :page-sizes="[10, 20, 50, 100]"
      @size-change="loadData"
      @current-change="loadData"
    />

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑{$this->title}' : '新增{$this->title}'" width="760px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-row :gutter="14">
{$this->vueFormItems()}        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="detailVisible" title="{$this->title}详情" width="760px">
      <el-descriptions v-if="detailRow" :column="2" border>
        <el-descriptions-item label="ID">{{ detailRow.id }}</el-descriptions-item>
{$this->vueDetailItems()}        <el-descriptions-item label="创建时间">{{ detailRow.create_time || '-' }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>{$fileSelectorTemplate}
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}

.selector-button {
  margin-top: 8px;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.image-list-item {
  width: 64px;
  height: 64px;
}

.detail-image {
  width: 72px;
  height: 72px;
}
</style>
VUE;
    }

    private function vueIndex(): string
    {
        return <<<VUE
<script setup lang="ts">
import {$this->class}ListView from './{$this->class}ListView.vue'
</script>

<template>
  <{$this->class}ListView />
</template>
VUE;
    }

    private function apiSnippet(): string
    {
        $rowFields = $this->tsFields(true);
        $payloadFields = $this->tsFields(false);
        $status = $this->statusField() ? <<<TS

export function update{$this->class}Status(id: number, status: number) {
  return request.patch<{$this->class}Row>(`/{$this->module}/status/id/\${id}`, { status }).then((response) => response.data)
}
TS : '';

        return <<<TS
import request from '../utils/request'
import type { PageResult } from './types'

export interface {$this->class}Row {
  id: number
{$rowFields}  create_time: string | null
}

export interface {$this->class}Payload {
{$payloadFields}}

export function fetch{$this->class}s(params: { page: number; limit: number; keyword?: string; status?: number | '' }) {
  return request.get<PageResult<{$this->class}Row>>('/{$this->module}/index', { params }).then((response) => response.data)
}

export function create{$this->class}(data: {$this->class}Payload) {
  return request.post<{$this->class}Row>('/{$this->module}/save', data).then((response) => response.data)
}

export function update{$this->class}(id: number, data: {$this->class}Payload) {
  return request.put<{$this->class}Row>(`/{$this->module}/update/id/\${id}`, data).then((response) => response.data)
}
{$status}

export function delete{$this->class}(id: number) {
  return request.delete<never>(`/{$this->module}/delete/id/\${id}`).then((response) => response.data)
}

TS;
    }

    private function schemaSql(): string
    {
        $columns = '';
        $indexes = '';

        foreach ($this->fields as $field) {
            $columns .= '  `' . $field['name'] . '` ' . $this->sqlType($field) . " COMMENT '" . $field['label'] . "',\n";

            if (!empty($field['search']) || ($field['type'] ?? '') === 'switch') {
                $indexes .= "  KEY `idx_{$field['name']}` (`{$field['name']}`),\n";
            }
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->table}` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
{$columns}  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
{$indexes}  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='{$this->title}';

SQL;
    }

    private function menuSql(): string
    {
        $status = $this->statusField()
            ? "(@menu_id, 3, '启用禁用{$this->title}', 'admin:{$this->module}:status', '', '', '', 102, 0, 1, NOW(), NOW()),\n"
            : '';

        return <<<SQL
-- 请先确认 @parent_id 是目标父级菜单 ID
SET @parent_id = 0;

INSERT INTO `admin_menu` (`parent_id`, `type`, `title`, `permission`, `path`, `component`, `icon`, `sort`, `visible`, `status`, `create_time`, `update_time`) VALUES
(@parent_id, 2, '{$this->title}管理', 'admin:{$this->module}:list', '/{$this->route}', '{$this->module}/index', 'Document', 100, 1, 1, NOW(), NOW());

SET @menu_id = LAST_INSERT_ID();

INSERT INTO `admin_menu` (`parent_id`, `type`, `title`, `permission`, `path`, `component`, `icon`, `sort`, `visible`, `status`, `create_time`, `update_time`) VALUES
(@menu_id, 3, '新增{$this->title}', 'admin:{$this->module}:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(@menu_id, 3, '编辑{$this->title}', 'admin:{$this->module}:update', '', '', '', 101, 0, 1, NOW(), NOW()),
{$status}(@menu_id, 3, '删除{$this->title}', 'admin:{$this->module}:delete', '', '', '', 103, 0, 1, NOW(), NOW());

SQL;
    }

    private function filterPayloadPhp(): string
    {
        $code = '';
        $payloadLines = [];

        foreach ($this->fields as $field) {
            $name = $field['name'];
            $label = $field['label'];
            $type = $field['type'] ?? 'text';

            if (in_array($type, ['text', 'textarea', 'richtext', 'image', 'file', 'select', 'radio', 'date', 'datetime'], true)) {
                $code .= "        \${$name} = trim((string) (\$data['{$name}'] ?? ''));\n";

                if (!empty($field['required'])) {
                    $code .= "\n        if (\${$name} === '') {\n            throw new RuntimeException('请输入{$label}');\n        }\n";
                }

                $payloadLines[] = in_array($type, ['date', 'datetime'], true)
                    ? "            '{$name}' => \${$name} !== '' ? \${$name} : null,"
                    : "            '{$name}' => \${$name},";
            } elseif (in_array($type, ['checkbox', 'images'], true)) {
                $code .= "        \${$name} = \$data['{$name}'] ?? [];\n";
                $code .= "\n        if (is_string(\${$name})) {\n            \${$name} = json_decode(\${$name}, true) ?: [];\n        }\n";

                if (!empty($field['required'])) {
                    $code .= "\n        if (!\${$name}) {\n            throw new RuntimeException('请选择{$label}');\n        }\n";
                }

                $payloadLines[] = "            '{$name}' => json_encode(array_values(array_filter(array_map('strval', (array) \${$name}))), JSON_UNESCAPED_UNICODE),";
            } elseif ($type === 'decimal') {
                $min = (float) ($field['min'] ?? 0);
                $max = (float) ($field['max'] ?? 999999);
                $payloadLines[] = "            '{$name}' => min({$max}, max({$min}, round((float) (\$data['{$name}'] ?? " . ($field['default'] ?? 0) . "), 2))),";
            } elseif ($type === 'number') {
                $min = (int) ($field['min'] ?? 0);
                $max = (int) ($field['max'] ?? 999999);
                $payloadLines[] = "            '{$name}' => min({$max}, max({$min}, (int) (\$data['{$name}'] ?? " . ($field['default'] ?? 0) . "))),";
            } elseif ($type === 'switch') {
                $payloadLines[] = "            '{$name}' => (int) (\$data['{$name}'] ?? " . ($field['default'] ?? 0) . ") === 1 ? 1 : 0,";
            }
        }

        return $code . "\n        return [\n" . implode("\n", $payloadLines) . "\n        ];\n";
    }

    private function vueFormDefaults(int $indent = 2): string
    {
        $space = str_repeat(' ', $indent);

        return implode('', array_map(fn (array $field) => "{$space}{$field['name']}: " . $this->tsDefault($field) . ",\n", $this->fields));
    }

    private function vueAssignFromRow(): string
    {
        return implode('', array_map(fn (array $field) => "    {$field['name']}: row.{$field['name']},\n", $this->fields));
    }

    private function vueRules(): string
    {
        $rules = '';

        foreach ($this->fields as $field) {
            $items = [];
            $label = $field['label'];
            $trigger = in_array($field['type'] ?? 'text', ['select', 'radio', 'checkbox', 'image', 'images', 'file', 'date', 'datetime', 'switch'], true) ? 'change' : 'blur';

            if (!empty($field['required'])) {
                $message = in_array($field['type'] ?? 'text', ['select', 'radio', 'checkbox', 'image', 'images', 'file', 'date', 'datetime'], true)
                    ? "请选择{$label}"
                    : "请输入{$label}";
                $items[] = "{ required: true, message: '{$message}', trigger: '{$trigger}' }";
            }

            if (!empty($field['max_length']) && in_array($field['type'] ?? 'text', ['text', 'textarea', 'richtext', 'image', 'file', 'select', 'radio'], true)) {
                $items[] = "{ max: " . (int) $field['max_length'] . ", message: '{$label}最多 " . (int) $field['max_length'] . " 个字符', trigger: 'blur' }";
            }

            if ($items) {
                $rules .= "  {$field['name']}: [" . implode(', ', $items) . "],\n";
            }
        }

        return $rules;
    }

    private function vueSearchStatus(): string
    {
        $status = $this->statusField();

        if (!$status) {
            return '';
        }

        return <<<VUE
      <el-form-item label="状态">
        <el-select v-model="query.{$status}" clearable placeholder="全部" style="width: 120px">
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
      </el-form-item>

VUE;
    }

    private function vueQueryStatus(): string
    {
        $status = $this->statusField();

        return $status ? "{$status}: '' as number | ''," : '';
    }

    private function vueTableColumns(): string
    {
        $columns = '';

        foreach ($this->fields as $field) {
            if (empty($field['list'])) {
                continue;
            }

            $name = $field['name'];
            $label = $field['label'];
            $type = $field['type'] ?? 'text';

            if ($type === 'switch') {
                $columns .= <<<VUE
        <el-table-column label="{$label}" width="100">
          <template #default="{ row }">
            <el-tag :type="row.{$name} === 1 ? 'success' : 'info'">
              {{ row.{$name} === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>

VUE;
            } elseif ($type === 'image') {
                $columns .= <<<VUE
        <el-table-column label="{$label}" width="100">
          <template #default="{ row }">
            <el-image v-if="row.{$name}" :src="row.{$name}" fit="cover" style="width: 44px; height: 44px" />
            <span v-else>-</span>
          </template>
        </el-table-column>

VUE;
            } elseif ($type === 'images') {
                $columns .= <<<VUE
        <el-table-column label="{$label}" min-width="140">
          <template #default="{ row }">
            <div v-if="row.{$name}.length" class="image-list">
              <el-image v-for="url in row.{$name}.slice(0, 3)" :key="url" :src="url" fit="cover" class="image-list-item" />
            </div>
            <span v-else>-</span>
          </template>
        </el-table-column>

VUE;
            } elseif ($type === 'file') {
                $columns .= <<<VUE
        <el-table-column label="{$label}" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">
            <el-link v-if="row.{$name}" :href="row.{$name}" target="_blank" type="primary">查看文件</el-link>
            <span v-else>-</span>
          </template>
        </el-table-column>

VUE;
            } elseif (in_array($type, ['select', 'radio'], true)) {
                $optionsName = $this->vueOptionsName($name);
                $columns .= <<<VUE
        <el-table-column label="{$label}" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">
            {{ optionLabel({$optionsName}, row.{$name}) }}
          </template>
        </el-table-column>

VUE;
            } elseif ($type === 'checkbox') {
                $optionsName = $this->vueOptionsName($name);
                $columns .= <<<VUE
        <el-table-column label="{$label}" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">
            {{ optionLabels({$optionsName}, row.{$name}).join('、') || '-' }}
          </template>
        </el-table-column>

VUE;
            } else {
                $columns .= "        <el-table-column prop=\"{$name}\" label=\"{$label}\" min-width=\"140\" show-overflow-tooltip />\n";
            }
        }

        return $columns;
    }

    private function vueFormItems(): string
    {
        $items = '';

        foreach ($this->fields as $field) {
            $name = $field['name'];
            $label = $field['label'];
            $type = $field['type'] ?? 'text';
            $prop = !empty($field['required']) ? " prop=\"{$name}\"" : '';

            $span = $this->vueFormItemSpan($field);
            $maxLength = (int) ($field['max_length'] ?? ($type === 'richtext' ? 5000 : ($type === 'textarea' ? 500 : 255)));
            $min = $field['min'] ?? 0;
            $max = $field['max'] ?? 999999;

            if ($type === 'textarea') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-input v-model=\"form.{$name}\" type=\"textarea\" :rows=\"4\" maxlength=\"{$maxLength}\" show-word-limit />\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'richtext') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-input v-model=\"form.{$name}\" type=\"textarea\" :rows=\"8\" maxlength=\"{$maxLength}\" show-word-limit />\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'number') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-input-number v-model=\"form.{$name}\" :min=\"{$min}\" :max=\"{$max}\" />\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'decimal') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-input-number v-model=\"form.{$name}\" :min=\"{$min}\" :max=\"{$max}\" :precision=\"2\" :step=\"0.01\" />\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'switch') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-switch v-model=\"form.{$name}\" :active-value=\"1\" :inactive-value=\"0\" />\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'image') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-input v-model=\"form.{$name}\" class=\"full\" placeholder=\"请选择图片\">\n                <template #append>\n                  <el-button @click=\"openImageSelector('{$name}')\">选择</el-button>\n                </template>\n              </el-input>\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'images') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <div class=\"full\">\n                <div v-if=\"form.{$name}.length\" class=\"image-list\">\n                  <el-image v-for=\"url in form.{$name}\" :key=\"url\" :src=\"url\" fit=\"cover\" class=\"image-list-item\" />\n                </div>\n                <el-empty v-else description=\"未选择图片\" :image-size=\"48\" />\n                <el-button class=\"selector-button\" @click=\"openImagesSelector('{$name}')\">选择图片</el-button>\n              </div>\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'file') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-input v-model=\"form.{$name}\" class=\"full\" placeholder=\"请选择文件\">\n                <template #append>\n                  <el-button @click=\"openFileSelector('{$name}')\">选择</el-button>\n                </template>\n              </el-input>\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'select') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-select v-model=\"form.{$name}\" class=\"full\" clearable placeholder=\"请选择{$label}\">\n{$this->vueSelectOptions($field)}              </el-select>\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'radio') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-radio-group v-model=\"form.{$name}\">\n{$this->vueRadioOptions($field)}              </el-radio-group>\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'checkbox') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-checkbox-group v-model=\"form.{$name}\">\n{$this->vueCheckboxOptions($field)}              </el-checkbox-group>\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'date') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-date-picker v-model=\"form.{$name}\" class=\"full\" type=\"date\" value-format=\"YYYY-MM-DD\" placeholder=\"请选择{$label}\" />\n            </el-form-item>\n          </el-col>\n";
            } elseif ($type === 'datetime') {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-date-picker v-model=\"form.{$name}\" class=\"full\" type=\"datetime\" value-format=\"YYYY-MM-DD HH:mm:ss\" placeholder=\"请选择{$label}\" />\n            </el-form-item>\n          </el-col>\n";
            } else {
                $items .= "          <el-col :span=\"{$span}\">\n            <el-form-item label=\"{$label}\"{$prop}>\n              <el-input v-model=\"form.{$name}\" maxlength=\"{$maxLength}\" />\n            </el-form-item>\n          </el-col>\n";
            }
        }

        return $items;
    }

    private function vueFormItemSpan(array $field): int
    {
        return in_array($field['type'] ?? 'text', ['textarea', 'richtext', 'images'], true) ? 24 : 12;
    }

    private function vueOptionConstants(): string
    {
        $constants = '';

        foreach ($this->fields as $field) {
            if (!in_array($field['type'] ?? '', ['select', 'radio', 'checkbox'], true)) {
                continue;
            }

            if (($field['dict_type'] ?? '') !== '') {
                $constants .= 'const ' . $this->vueOptionsName($field['name']) . " = ref<Array<{ label: string; value: string }>>([])\n";
                continue;
            }

            $items = [];

            foreach (($field['options'] ?? []) as $option) {
                $label = $this->jsString((string) ($option['label'] ?? $option['value'] ?? ''));
                $value = $this->jsString((string) ($option['value'] ?? $option['label'] ?? ''));
                $items[] = "{ label: {$label}, value: {$value} }";
            }

            $constants .= 'const ' . $this->vueOptionsName($field['name']) . ' = [' . implode(', ', $items) . "]\n";
        }

        return $constants !== '' ? $constants . "\n" : '';
    }

    private function vueDictMethods(): string
    {
        $loads = '';

        foreach ($this->fields as $field) {
            $dictType = (string) ($field['dict_type'] ?? '');

            if ($dictType === '' || !in_array($field['type'] ?? '', ['select', 'radio', 'checkbox'], true)) {
                continue;
            }

            $loads .= "    fetchDictOptions('{$dictType}').then((items) => ({$this->vueOptionsName($field['name'])}.value = items)),\n";
        }

        if ($loads === '') {
            return '';
        }

        return <<<VUE

async function loadDictOptions() {
  await Promise.all([
{$loads}  ])
}

VUE;
    }

    private function vueOnMountedDictLoad(): string
    {
        return $this->hasDictFields() ? "  loadDictOptions()\n" : '';
    }

    private function vueFormatMethods(): string
    {
        $methods = <<<VUE

function formatValue(value: string | number | null | undefined) {
  return value === '' || value === null || value === undefined ? '-' : value
}

VUE;

        if (!$this->hasOptionFields()) {
            return $methods;
        }

        return $methods . <<<VUE

function optionLabel(options: Array<{ label: string; value: string }>, value: string) {
  return options.find((item) => item.value === value)?.label || value || '-'
}

function optionLabels(options: Array<{ label: string; value: string }>, values: string[]) {
  return values.map((value) => optionLabel(options, value)).filter(Boolean)
}

VUE;
    }

    private function vueDetailItems(): string
    {
        $items = '';

        foreach ($this->fields as $field) {
            $name = $field['name'];
            $label = $field['label'];
            $type = $field['type'] ?? 'text';
            $span = in_array($type, ['textarea', 'richtext', 'images'], true) ? ' :span="2"' : '';

            if ($type === 'image') {
                $items .= <<<VUE
        <el-descriptions-item label="{$label}">
          <el-image v-if="detailRow.{$name}" :src="detailRow.{$name}" fit="cover" class="detail-image" />
          <span v-else>-</span>
        </el-descriptions-item>

VUE;
            } elseif ($type === 'images') {
                $items .= <<<VUE
        <el-descriptions-item label="{$label}"{$span}>
          <div v-if="detailRow.{$name}.length" class="image-list">
            <el-image v-for="url in detailRow.{$name}" :key="url" :src="url" fit="cover" class="image-list-item" />
          </div>
          <span v-else>-</span>
        </el-descriptions-item>

VUE;
            } elseif ($type === 'file') {
                $items .= <<<VUE
        <el-descriptions-item label="{$label}">
          <el-link v-if="detailRow.{$name}" :href="detailRow.{$name}" target="_blank" type="primary">查看文件</el-link>
          <span v-else>-</span>
        </el-descriptions-item>

VUE;
            } elseif ($type === 'switch') {
                $items .= <<<VUE
        <el-descriptions-item label="{$label}">
          <el-tag :type="detailRow.{$name} === 1 ? 'success' : 'info'">{{ detailRow.{$name} === 1 ? '启用' : '禁用' }}</el-tag>
        </el-descriptions-item>

VUE;
            } elseif (in_array($type, ['select', 'radio'], true)) {
                $optionsName = $this->vueOptionsName($name);
                $items .= <<<VUE
        <el-descriptions-item label="{$label}">{{ optionLabel({$optionsName}, detailRow.{$name}) }}</el-descriptions-item>

VUE;
            } elseif ($type === 'checkbox') {
                $optionsName = $this->vueOptionsName($name);
                $items .= <<<VUE
        <el-descriptions-item label="{$label}">{{ optionLabels({$optionsName}, detailRow.{$name}).join('、') || '-' }}</el-descriptions-item>

VUE;
            } else {
                $items .= "        <el-descriptions-item label=\"{$label}\"{$span}>{{ formatValue(detailRow.{$name}) }}</el-descriptions-item>\n";
            }
        }

        return $items;
    }

    private function hasOptionFields(): bool
    {
        return (bool) array_filter($this->fields, fn (array $field) => in_array($field['type'] ?? '', ['select', 'radio', 'checkbox'], true));
    }

    private function hasDictFields(): bool
    {
        return (bool) array_filter($this->fields, fn (array $field) => in_array($field['type'] ?? '', ['select', 'radio', 'checkbox'], true) && ($field['dict_type'] ?? '') !== '');
    }

    private function vueOptionsName(string $field): string
    {
        return $field . 'Options';
    }

    private function vueStatusMethod(): string
    {
        $status = $this->statusField();

        if (!$status) {
            return '';
        }

        return <<<VUE

async function handleStatus(row: {$this->class}Row) {
  const nextStatus = row.{$status} === 1 ? 0 : 1
  await update{$this->class}Status(row.id, nextStatus)
  row.{$status} = nextStatus
  ElMessage.success('状态已更新')
}

VUE;
    }

    private function vueStatusButton(): string
    {
        $status = $this->statusField();

        if (!$status) {
            return '';
        }

        return "              <el-button v-if=\"authStore.hasPermission('admin:{$this->module}:status')\" link type=\"primary\" @click=\"handleStatus(row)\">{{ row.{$status} === 1 ? '禁用' : '启用' }}</el-button>\n";
    }

    private function vueFileSelectorMethods(): string
    {
        return <<<VUE

function openImageSelector(field: string) {
  selectingFileField.value = field as keyof {$this->class}Payload
  fileSelectorAcceptType.value = 'image'
  fileSelectorMultiple.value = false
  fileSelectorVisible.value = true
}

function openImagesSelector(field: string) {
  selectingFileField.value = field as keyof {$this->class}Payload
  fileSelectorAcceptType.value = 'image'
  fileSelectorMultiple.value = true
  fileSelectorVisible.value = true
}

function openFileSelector(field: string) {
  selectingFileField.value = field as keyof {$this->class}Payload
  fileSelectorAcceptType.value = 'file'
  fileSelectorMultiple.value = false
  fileSelectorVisible.value = true
}

function currentFileUrl() {
  const value = selectingFileField.value ? form[selectingFileField.value] : ''

  return Array.isArray(value) ? String(value[0] || '') : String(value || '')
}

function handleFileSelected(files: any[]) {
  if (!files.length || !selectingFileField.value) {
    return
  }

  ;(form as any)[selectingFileField.value] = fileSelectorMultiple.value
    ? files.map((file) => file.url)
    : files[0].url
}

VUE;
    }

    private function vueSelectOptions(array $field): string
    {
        if (($field['dict_type'] ?? '') !== '') {
            $optionsName = $this->vueOptionsName($field['name']);

            return "                <el-option v-for=\"item in {$optionsName}\" :key=\"item.value\" :label=\"item.label\" :value=\"item.value\" />\n";
        }

        $options = $field['options'] ?? [];

        if (!$options) {
            return '';
        }

        $items = '';

        foreach ($options as $option) {
            $label = (string) ($option['label'] ?? $option['value'] ?? '');
            $value = (string) ($option['value'] ?? $label);
            $items .= "                <el-option label=\"{$label}\" value=\"{$value}\" />\n";
        }

        return $items;
    }

    private function vueRadioOptions(array $field): string
    {
        if (($field['dict_type'] ?? '') !== '') {
            $optionsName = $this->vueOptionsName($field['name']);

            return "                <el-radio v-for=\"item in {$optionsName}\" :key=\"item.value\" :value=\"item.value\">{{ item.label }}</el-radio>\n";
        }

        $items = '';

        foreach (($field['options'] ?? []) as $option) {
            $label = (string) ($option['label'] ?? $option['value'] ?? '');
            $value = (string) ($option['value'] ?? $label);
            $items .= "                <el-radio value=\"{$value}\">{$label}</el-radio>\n";
        }

        return $items;
    }

    private function vueCheckboxOptions(array $field): string
    {
        if (($field['dict_type'] ?? '') !== '') {
            $optionsName = $this->vueOptionsName($field['name']);

            return "                <el-checkbox v-for=\"item in {$optionsName}\" :key=\"item.value\" :value=\"item.value\">{{ item.label }}</el-checkbox>\n";
        }

        $items = '';

        foreach (($field['options'] ?? []) as $option) {
            $label = (string) ($option['label'] ?? $option['value'] ?? '');
            $value = (string) ($option['value'] ?? $label);
            $items .= "                <el-checkbox value=\"{$value}\">{$label}</el-checkbox>\n";
        }

        return $items;
    }

    private function tsFields(bool $includeReadonly): string
    {
        $fields = '';

        foreach ($this->fields as $field) {
            $fields .= '  ' . $field['name'] . ': ' . $this->tsType($field) . "\n";
        }

        return $fields;
    }

    private function sqlType(array $field): string
    {
        $type = $field['type'] ?? 'text';
        $default = $field['default'] ?? null;
        $maxLength = min(1000, max(1, (int) ($field['max_length'] ?? 255)));

        return match ($type) {
            'textarea', 'checkbox', 'images' => 'text',
            'richtext' => 'longtext',
            'number' => "int unsigned NOT NULL DEFAULT '" . (int) ($default ?? 0) . "'",
            'decimal' => "decimal(10,2) NOT NULL DEFAULT '" . number_format((float) ($default ?? 0), 2, '.', '') . "'",
            'switch' => "tinyint unsigned NOT NULL DEFAULT '" . (int) ($default ?? 0) . "'",
            'image', 'file' => "varchar(500) NOT NULL DEFAULT ''",
            'select', 'radio' => "varchar(50) NOT NULL DEFAULT ''",
            'date' => 'date DEFAULT NULL',
            'datetime' => 'datetime DEFAULT NULL',
            default => "varchar({$maxLength}) NOT NULL DEFAULT ''",
        };
    }

    private function tsType(array $field): string
    {
        if (in_array($field['type'] ?? 'text', ['number', 'decimal', 'switch'], true)) {
            return 'number';
        }

        return in_array($field['type'] ?? 'text', ['checkbox', 'images'], true) ? 'string[]' : 'string';
    }

    private function tsDefault(array $field): string
    {
        $type = $field['type'] ?? 'text';

        if (in_array($type, ['number', 'decimal', 'switch'], true)) {
            return (string) ($field['default'] ?? 0);
        }

        if (in_array($type, ['checkbox', 'images'], true)) {
            return '[]';
        }

        return "''";
    }

    private function hasJsonFields(): bool
    {
        return (bool) array_filter($this->fields, fn (array $field) => in_array($field['type'] ?? '', ['checkbox', 'images'], true));
    }

    private function formatRowMethodPhp(): string
    {
        $fields = array_values(array_filter($this->fields, fn (array $field) => in_array($field['type'] ?? '', ['checkbox', 'images'], true)));

        $lines = '';

        foreach ($fields as $field) {
            $name = $field['name'];
            $lines .= "        \$row['{$name}'] = json_decode((string) (\$row['{$name}'] ?? '[]'), true) ?: [];\n";
        }

        return <<<PHP

    /**
     * 格式化{$this->title}输出数据。
     */
    private function formatRow(array \$row): array
    {
{$lines}
        return \$row;
    }
PHP;
    }

    private function statusField(): string
    {
        foreach ($this->fields as $field) {
            if (($field['type'] ?? '') === 'switch' && ($field['name'] ?? '') === 'status') {
                return 'status';
            }
        }

        return '';
    }

    private function hasField(string $name): bool
    {
        return (bool) array_filter($this->fields, fn (array $field) => ($field['name'] ?? '') === $name);
    }

    private function displayField(): string
    {
        foreach (['title', 'name'] as $name) {
            if ($this->hasField($name)) {
                return $name;
            }
        }

        return $this->fields[0]['name'];
    }

    private function normalizeName(string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $name) ?? '', '_'));
    }

    private function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    private function kebab(string $value): string
    {
        return str_replace('_', '-', $value);
    }

    private function plural(string $value): string
    {
        return str_ends_with($value, 's') ? $value : $value . 's';
    }

    private function normalizeRoutePath(string $path): string
    {
        $normalized = strtolower(trim(preg_replace('/[^a-zA-Z0-9_\/-]/', '-', $path) ?? '', '/-'));

        return $normalized !== '' ? $normalized : 'index';
    }

    private function joinImport(array $imports): string
    {
        return implode(",\n  ", $imports);
    }

    private function jsString(string $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
