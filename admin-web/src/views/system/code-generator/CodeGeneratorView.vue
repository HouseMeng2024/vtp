<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  cleanupGeneratedCode,
  fetchCodeGeneratorStatus,
  fetchRecentCodeGenerate,
  generateCode,
  previewCodeGenerate,
  type CodeGeneratorField,
  type CodeGeneratorPayload,
  type CodeGeneratorPreview,
  type CodeGeneratorResult,
} from '../../../api/codeGenerator'
import {
  fetchDictTypeOptions,
  type DictTypeOption,
} from '../../../api/dict'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const loading = ref(false)
const generating = ref(false)
const formRef = ref<FormInstance>()
const result = ref<CodeGeneratorResult | null>(null)
const optionDialogVisible = ref(false)
const editingOptionField = ref<CodeGeneratorField | null>(null)
const dictTypeOptions = ref<DictTypeOption[]>([])
const generatorStatus = ref({
  enabled: false,
  super_admin: false,
  writable: false,
  message: '',
})

const fieldTypes = [
  { label: '短文本', value: 'text' },
  { label: '长文本', value: 'textarea' },
  { label: '富文本', value: 'richtext' },
  { label: '整数', value: 'number' },
  { label: '小数', value: 'decimal' },
  { label: '开关', value: 'switch' },
  { label: '下拉', value: 'select' },
  { label: '单选', value: 'radio' },
  { label: '多选', value: 'checkbox' },
  { label: '图片', value: 'image' },
  { label: '多图', value: 'images' },
  { label: '文件', value: 'file' },
  { label: '日期', value: 'date' },
  { label: '日期时间', value: 'datetime' },
] as const

const quickFields: CodeGeneratorField[] = [
  { name: 'title', label: '标题', type: 'text', required: true, search: true, list: true, default: '', max_length: 255, min: 0, max: 999999, dict_type: '', options: [] },
  { name: 'cover', label: '封面', type: 'image', required: false, search: false, list: true, default: '', max_length: 500, min: 0, max: 999999, dict_type: '', options: [] },
  { name: 'status', label: '状态', type: 'switch', required: false, search: true, list: true, default: 1, max_length: 255, min: 0, max: 1, dict_type: '', options: [] },
  { name: 'sort', label: '排序', type: 'number', required: false, search: false, list: true, default: 100, max_length: 255, min: 0, max: 999999, dict_type: '', options: [] },
  { name: 'remark', label: '备注', type: 'textarea', required: false, search: false, list: false, default: '', max_length: 500, min: 0, max: 999999, dict_type: '', options: [] },
  { name: 'content', label: '内容', type: 'richtext', required: false, search: false, list: false, default: '', max_length: 5000, min: 0, max: 999999, dict_type: '', options: [] },
]

const form = reactive<CodeGeneratorPayload>({
  module: '',
  title: '',
  table: '',
  route_path: '',
  menu_parent: '',
  navigation_options: [],
  fields: [],
  options: {
    write_backend: true,
    write_frontend: true,
    merge_api: true,
    create_menu: true,
    menu_parent_id: null,
    execute_schema: true,
    overwrite_existing: false,
  },
})

const rules: FormRules = {
  module: [{ required: true, message: '请输入模块标识', trigger: 'blur' }],
  title: [{ required: true, message: '请输入模块名称', trigger: 'blur' }],
  table: [{ required: true, message: '请输入数据表名', trigger: 'blur' }],
}

function createField(): CodeGeneratorField {
  return {
    name: '',
    label: '',
    type: 'text' as const,
    required: false,
    search: false,
    list: true,
    default: '',
    max_length: 255,
    min: 0,
    max: 999999,
    dict_type: '',
    options: [],
  }
}

async function loadDictTypeOptions() {
  dictTypeOptions.value = await fetchDictTypeOptions().catch(() => [])
}

async function loadRecentResult() {
  const data = await fetchRecentCodeGenerate()

  if (data?.module) {
    result.value = data
  }
}

async function loadGeneratorStatus() {
  generatorStatus.value = await fetchCodeGeneratorStatus().catch(() => generatorStatus.value)
}

function addField() {
  form.fields.push(createField())
}

function addQuickField(field: CodeGeneratorField) {
  if (form.fields.some((item) => item.name === field.name)) {
    ElMessage.warning(`字段 ${field.name} 已存在`)
    return
  }

  form.fields.push({
    ...field,
    options: field.options ? [...field.options] : [],
  })
}

function removeField(index: number) {
  form.fields.splice(index, 1)
}

function syncTableName() {
  if (!form.table) {
    form.table = form.module
  }

  if (!form.route_path) {
    form.route_path = form.module
  }
}

function openOptionDialog(row: CodeGeneratorField) {
  editingOptionField.value = row
  row.options = row.options || []
  optionDialogVisible.value = true
}

function addSelectOption() {
  editingOptionField.value?.options?.push({
    label: '',
    value: '',
  })
}

function removeSelectOption(index: number) {
  editingOptionField.value?.options?.splice(index, 1)
}

function isTextLimitField(type: CodeGeneratorField['type']) {
  return ['text', 'textarea', 'richtext', 'image', 'file', 'select', 'radio'].includes(type)
}

function isNumberLimitField(type: CodeGeneratorField['type']) {
  return ['number', 'decimal'].includes(type)
}

function isOptionField(type: CodeGeneratorField['type']) {
  return ['select', 'radio', 'checkbox'].includes(type)
}

function previewGroups(preview: CodeGeneratorPreview) {
  return [
    { title: '后端文件', items: preview.checks.backend_files },
    { title: 'Vue 页面', items: preview.checks.frontend_files },
    { title: '前端 API', items: preview.checks.api_files },
    { title: '数据表', items: preview.checks.database },
    { title: '菜单权限', items: preview.checks.menus },
  ].filter((group) => group.items.length)
}

function formatPreviewMessage(preview: CodeGeneratorPreview) {
  const lines = [`页面路径：${preview.route_path}`]

  for (const group of previewGroups(preview)) {
    lines.push('', `${group.title}：`)
    for (const item of group.items) {
      lines.push(`${item.exists ? '已存在' : '将创建'} ${item.path}`)
    }
  }

  if (preview.has_conflict && !form.options.overwrite_existing) {
    lines.push('', '检测到已存在文件或数据，请确认是否需要勾选覆盖同名文件。')
  }

  return lines.join('\n')
}

function hasWriteConflict(preview: CodeGeneratorPreview) {
  return [
    ...preview.checks.backend_files,
    ...preview.checks.frontend_files,
    ...preview.checks.api_files,
  ].some((item) => item.exists)
}

async function handleGenerate() {
  if (!generatorStatus.value.writable) {
    ElMessage.warning(generatorStatus.value.message || '当前不能执行代码生成写入操作')
    return
  }

  await formRef.value?.validate()

  if (!form.fields.length) {
    ElMessage.warning('至少添加一个字段')
    return
  }

  const preview = await previewCodeGenerate(form)

  if (!form.options.overwrite_existing && hasWriteConflict(preview)) {
    await ElMessageBox.alert(formatPreviewMessage(preview), '存在同名文件', {
      type: 'warning',
      customClass: 'code-generator-preview-message',
    })
    return
  }

  await ElMessageBox.confirm(formatPreviewMessage(preview), '生成确认', {
    type: 'warning',
    customClass: 'code-generator-preview-message',
  })

  generating.value = true
  try {
    result.value = await generateCode(form)
    if (form.options.create_menu) {
      await authStore.fetchMenus()
      ElMessage.info('如果新菜单没有显示，请检查当前角色权限或重新登录')
    }
    ElMessage.success('代码生成成功')
  } finally {
    generating.value = false
  }
}

async function handleCleanup() {
  if (!generatorStatus.value.writable) {
    ElMessage.warning(generatorStatus.value.message || '当前不能执行代码生成写入操作')
    return
  }

  if (!form.module) {
    ElMessage.warning('请输入模块标识')
    return
  }

  await ElMessageBox.confirm(`确定清理模块「${form.module}」生成的文件、菜单和数据表吗？`, '清理确认', {
    type: 'warning',
  })
  const data = await cleanupGeneratedCode({
    module: form.module,
    table: form.table || form.module,
  })
  result.value = null
  await authStore.fetchMenus()
  ElMessage.success(data.deleted.length ? `已清理 ${data.deleted.length} 项` : '没有需要清理的内容')
}

onMounted(async () => {
  await loadDictTypeOptions()
  await loadGeneratorStatus()
  await loadRecentResult()
})
</script>

<template>
  <div v-loading="loading" class="generator-page">
    <el-card class="page-card generator-form-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">代码生成</div>
          <el-space>
            <el-button
              v-if="authStore.hasPermission('admin:code-generator:generate')"
              type="danger"
              plain
              :disabled="!generatorStatus.writable"
              :title="generatorStatus.message"
              @click="handleCleanup"
            >
              清理当前模块
            </el-button>
            <el-button
              v-if="authStore.hasPermission('admin:code-generator:generate')"
              type="primary"
              :loading="generating"
              :disabled="!generatorStatus.writable"
              :title="generatorStatus.message"
              @click="handleGenerate"
            >
              生成代码
            </el-button>
          </el-space>
        </div>
      </template>

      <el-form ref="formRef" :model="form" :rules="rules" label-width="96px">
        <el-row :gutter="14">
          <el-col :span="6">
            <el-form-item label="模块标识" prop="module">
              <el-input v-model="form.module" placeholder="例如 goods" @blur="syncTableName" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="模块名称" prop="title">
              <el-input v-model="form.title" placeholder="例如 商品" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="数据表名" prop="table">
              <el-input v-model="form.table" placeholder="例如 goods" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="路由路径">
              <el-input v-model="form.route_path" placeholder="例如 goods" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <el-divider />

      <div class="field-toolbar">
        <div class="section-title">生成选项</div>
      </div>

      <el-row :gutter="14" class="option-grid">
        <el-col :span="6">
          <el-checkbox v-model="form.options.write_backend">写入后端文件</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.write_frontend">写入 Vue 页面</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.merge_api">写入前端 API</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.create_menu">生成导航</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.execute_schema">执行建表 SQL</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.overwrite_existing">覆盖同名文件</el-checkbox>
        </el-col>
      </el-row>

      <el-row v-if="form.options.create_menu" :gutter="14" class="navigation-row">
        <el-col :span="8">
          <el-form-item label="所属导航">
            <el-select
              v-model="form.options.menu_parent_id"
              class="full"
              clearable
              placeholder="不选择则生成顶级导航"
            >
              <el-option
                v-for="item in form.navigation_options"
                :key="item.id"
                :label="`${item.title} ${item.path}`"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <div class="field-toolbar">
        <div class="section-title">字段配置</div>
        <el-space wrap>
          <el-button v-for="field in quickFields" :key="field.name" plain @click="addQuickField(field)">
            {{ field.label }}
          </el-button>
          <el-button type="primary" plain @click="addField">新增字段</el-button>
        </el-space>
      </div>

      <div class="field-table">
        <el-table :data="form.fields" border height="100%">
          <el-table-column label="字段名" min-width="150">
            <template #default="{ row }">
              <el-input v-model="row.name" placeholder="title" />
            </template>
          </el-table-column>
          <el-table-column label="标题" min-width="150">
            <template #default="{ row }">
              <el-input v-model="row.label" placeholder="标题" />
            </template>
          </el-table-column>
          <el-table-column label="类型" width="150">
            <template #default="{ row }">
              <el-select v-model="row.type" class="full">
                <el-option v-for="item in fieldTypes" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </template>
          </el-table-column>
          <el-table-column label="默认值" min-width="130">
            <template #default="{ row }">
              <el-input v-model="row.default" />
            </template>
          </el-table-column>
          <el-table-column label="长度" width="120">
            <template #default="{ row }">
              <el-input-number
                v-if="isTextLimitField(row.type)"
                v-model="row.max_length"
                :min="1"
                :max="10000"
                controls-position="right"
                class="full"
              />
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="最小值" width="120">
            <template #default="{ row }">
              <el-input-number
                v-if="isNumberLimitField(row.type)"
                v-model="row.min"
                controls-position="right"
                class="full"
              />
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="最大值" width="120">
            <template #default="{ row }">
              <el-input-number
                v-if="isNumberLimitField(row.type)"
                v-model="row.max"
                controls-position="right"
                class="full"
              />
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="字典" min-width="180">
            <template #default="{ row }">
              <el-select v-if="isOptionField(row.type)" v-model="row.dict_type" clearable filterable class="full" placeholder="可选字典">
                <el-option
                  v-for="item in dictTypeOptions"
                  :key="item.type"
                  :label="`${item.name} (${item.type})`"
                  :value="item.type"
                />
              </el-select>
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="选项" width="92" align="center">
            <template #default="{ row }">
              <el-button v-if="isOptionField(row.type) && !row.dict_type" link type="primary" @click="openOptionDialog(row)">配置</el-button>
              <span v-else-if="isOptionField(row.type)">字典</span>
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="必填" width="82" align="center">
            <template #default="{ row }">
              <el-checkbox v-model="row.required" />
            </template>
          </el-table-column>
          <el-table-column label="搜索" width="82" align="center">
            <template #default="{ row }">
              <el-checkbox v-model="row.search" />
            </template>
          </el-table-column>
          <el-table-column label="列表" width="82" align="center">
            <template #default="{ row }">
              <el-checkbox v-model="row.list" />
            </template>
          </el-table-column>
          <el-table-column label="操作" width="90" fixed="right">
            <template #default="{ $index }">
              <el-button link type="danger" @click="removeField($index)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-card>

    <el-dialog v-model="optionDialogVisible" title="选项配置" width="520px">
      <div class="select-option-list">
        <el-row v-for="(option, index) in editingOptionField?.options" :key="index" :gutter="10" class="select-option-row">
          <el-col :span="10">
            <el-input v-model="option.label" placeholder="显示文字" />
          </el-col>
          <el-col :span="10">
            <el-input v-model="option.value" placeholder="选项值" />
          </el-col>
          <el-col :span="4">
            <el-button link type="danger" @click="removeSelectOption(index)">删除</el-button>
          </el-col>
        </el-row>
      </div>
      <el-button type="primary" plain @click="addSelectOption">新增选项</el-button>
      <template #footer>
        <el-button type="primary" @click="optionDialogVisible = false">确定</el-button>
      </template>
    </el-dialog>

    <el-card class="page-card result-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">生成结果</div>
        </div>
      </template>

      <el-empty v-if="!result" description="还没有生成结果" />
      <template v-else>
        <el-descriptions :column="1" border>
          <el-descriptions-item label="输出目录">{{ result.output_dir }}</el-descriptions-item>
          <el-descriptions-item label="配置文件">{{ result.config_path }}</el-descriptions-item>
        </el-descriptions>

        <el-divider />

        <el-alert
          v-for="message in result.messages"
          :key="message"
          class="result-message"
          :title="message"
          type="success"
          :closable="false"
        />

        <el-divider v-if="result.messages.length" />

        <div class="result-title">已写入项目文件</div>
        <el-scrollbar class="result-files small">
          <el-tree :data="result.installed_files.map((file) => ({ label: file }))" empty-text="没有直接写入项目文件" />
        </el-scrollbar>

        <el-divider />

        <div class="result-title">已写入前端 API</div>
        <el-scrollbar class="result-files small">
          <el-tree :data="result.merged_files.map((file) => ({ label: file }))" empty-text="没有写入前端 API" />
        </el-scrollbar>

        <el-divider />

        <div class="result-title">Runtime 生成备份</div>
        <el-scrollbar class="result-files">
          <el-tree :data="result.files.map((file) => ({ label: file }))" />
        </el-scrollbar>

        <el-divider />

        <div class="result-title">生成日志</div>
        <el-scrollbar class="result-log">
          <pre>{{ result.log.join('\n') }}</pre>
        </el-scrollbar>

        <el-divider />

        <el-alert title="生成器会保留 runtime 备份；真实项目文件是否落地由上方生成选项控制。" type="info" :closable="false" />
      </template>
    </el-card>
  </div>
</template>

<style scoped>
.generator-page {
  display: grid;
  height: 100%;
  min-height: 0;
  grid-template-columns: minmax(0, 1fr) 360px;
  gap: 14px;
}

.generator-form-card,
.result-card {
  min-height: 0;
}

.field-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.option-grid {
  margin-bottom: 14px;
}

.navigation-row {
  margin-bottom: 14px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
}

.field-table {
  height: calc(100vh - 360px);
  min-height: 320px;
}

.result-files {
  height: 300px;
}

.result-files.small {
  height: 120px;
}

.result-log {
  height: 90px;
}

.result-log pre {
  margin: 0;
  font-size: 12px;
  line-height: 1.6;
}

.result-title {
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
}

.result-message + .result-message {
  margin-top: 8px;
}

.select-option-list {
  margin-bottom: 12px;
}

.select-option-row + .select-option-row {
  margin-top: 10px;
}

.full {
  width: 100%;
}

:global(.code-generator-preview-message .el-message-box__message) {
  white-space: pre-line;
}

@media (max-width: 1100px) {
  .generator-page {
    grid-template-columns: 1fr;
  }
}
</style>
