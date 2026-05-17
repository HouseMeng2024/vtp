<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  cleanupGeneratedCode,
  fetchDictTypeOptions,
  fetchRecentCodeGenerate,
  generateCode,
  previewCodeGenerate,
  type CodeGeneratorField,
  type CodeGeneratorPayload,
  type CodeGeneratorPreview,
  type CodeGeneratorResult,
  type DictTypeOption,
} from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const { t } = useI18n()
const loading = ref(false)
const generating = ref(false)
const formRef = ref<FormInstance>()
const result = ref<CodeGeneratorResult | null>(null)
const optionDialogVisible = ref(false)
const editingOptionField = ref<CodeGeneratorField | null>(null)
const dictTypeOptions = ref<DictTypeOption[]>([])

const fieldTypes = [
  { label: 'Short Text', value: 'text' },
  { label: 'Long Text', value: 'textarea' },
  { label: 'Rich Text', value: 'richtext' },
  { label: 'Integer', value: 'number' },
  { label: 'Decimal', value: 'decimal' },
  { label: 'Switch', value: 'switch' },
  { label: 'Select', value: 'select' },
  { label: 'Radio', value: 'radio' },
  { label: 'Checkbox', value: 'checkbox' },
  { label: 'Image', value: 'image' },
  { label: 'Multiple Images', value: 'images' },
  { label: 'File', value: 'file' },
  { label: 'Date', value: 'date' },
  { label: 'Datetime', value: 'datetime' },
] as const

const quickFields: CodeGeneratorField[] = [
  { name: 'title', label: 'Title', type: 'text', required: true, search: true, list: true, default: '', max_length: 255, min: 0, max: 999999, dict_type: '', options: [] },
  { name: 'cover', label: 'Cover', type: 'image', required: false, search: false, list: true, default: '', max_length: 500, min: 0, max: 999999, dict_type: '', options: [] },
  { name: 'status', label: 'Status', type: 'switch', required: false, search: true, list: true, default: 1, max_length: 255, min: 0, max: 1, dict_type: '', options: [] },
  { name: 'sort', label: 'Sort', type: 'number', required: false, search: false, list: true, default: 100, max_length: 255, min: 0, max: 999999, dict_type: '', options: [] },
  { name: 'remark', label: 'Remark', type: 'textarea', required: false, search: false, list: false, default: '', max_length: 500, min: 0, max: 999999, dict_type: '', options: [] },
  { name: 'content', label: 'Content', type: 'richtext', required: false, search: false, list: false, default: '', max_length: 5000, min: 0, max: 999999, dict_type: '', options: [] },
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
  module: [{ required: true, message: t('generator.moduleKeyRequired'), trigger: 'blur' }],
  title: [{ required: true, message: t('generator.moduleNameRequired'), trigger: 'blur' }],
  table: [{ required: true, message: t('generator.tableNameRequired'), trigger: 'blur' }],
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

function addField() {
  form.fields.push(createField())
}

function addQuickField(field: CodeGeneratorField) {
  if (form.fields.some((item) => item.name === field.name)) {
    ElMessage.warning(t('generator.fieldAlreadyExists', { name: field.name }))
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
    { title: t('generator.backendFiles'), items: preview.checks.backend_files },
    { title: t('generator.vuePage'), items: preview.checks.frontend_files },
    { title: t('generator.frontendApi'), items: preview.checks.api_files },
    { title: t('generator.dataTable'), items: preview.checks.database },
    { title: t('generator.menuPermissions'), items: preview.checks.menus },
  ].filter((group) => group.items.length)
}

function formatPreviewMessage(preview: CodeGeneratorPreview) {
  const lines = [`${t('generator.pagePath')}: ${preview.route_path}`]

  for (const group of previewGroups(preview)) {
    lines.push('', `${group.title}：`)
    for (const item of group.items) {
      lines.push(`${item.exists ? t('generator.alreadyExists') : t('generator.willCreate')} ${item.path}`)
    }
  }

  if (preview.has_conflict && !form.options.overwrite_existing) {
    lines.push('', t('generator.existingConflict'))
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
  await formRef.value?.validate()

  if (!form.fields.length) {
    ElMessage.warning(t('generator.addAtLeastOneField'))
    return
  }

  const preview = await previewCodeGenerate(form)

  if (!form.options.overwrite_existing && hasWriteConflict(preview)) {
    await ElMessageBox.alert(formatPreviewMessage(preview), t('generator.duplicateFiles'), {
      type: 'warning',
      customClass: 'code-generator-preview-message',
    })
    return
  }

  await ElMessageBox.confirm(formatPreviewMessage(preview), t('generator.generateConfirmation'), {
    type: 'warning',
    customClass: 'code-generator-preview-message',
  })

  generating.value = true
  try {
    result.value = await generateCode(form)
    if (form.options.create_menu) {
      await authStore.fetchMenus()
      ElMessage.info(t('generator.navigationHint'))
    }
    ElMessage.success(t('generator.codeGenerated'))
  } finally {
    generating.value = false
  }
}

async function handleCleanup() {
  if (!form.module) {
    ElMessage.warning(t('generator.moduleKeyRequired'))
    return
  }

  await ElMessageBox.confirm(t('generator.cleanupConfirm', { module: form.module }), t('common.clearConfirmation'), {
    type: 'warning',
  })
  const data = await cleanupGeneratedCode({
    module: form.module,
    table: form.table || form.module,
  })
  result.value = null
  await authStore.fetchMenus()
  ElMessage.success(data.deleted.length ? t('generator.cleanedItems', { count: data.deleted.length }) : t('generator.nothingToClean'))
}

onMounted(async () => {
  await loadDictTypeOptions()
  await loadRecentResult()
})
</script>

<template>
  <div v-loading="loading" class="generator-page">
    <el-card class="page-card generator-form-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">{{ t('generator.codeGenerator') }}</div>
          <el-space>
            <el-button
              v-if="authStore.hasPermission('admin:code-generator:generate')"
              type="danger"
              plain
              @click="handleCleanup"
            >
              {{ t('generator.cleanCurrentModule') }}
            </el-button>
            <el-button
              v-if="authStore.hasPermission('admin:code-generator:generate')"
              type="primary"
              :loading="generating"
              @click="handleGenerate"
            >
              {{ t('generator.generateCode') }}
            </el-button>
          </el-space>
        </div>
      </template>

      <el-form ref="formRef" :model="form" :rules="rules" label-width="96px">
        <el-row :gutter="14">
          <el-col :span="6">
            <el-form-item :label="t('generator.moduleKey')" prop="module">
              <el-input v-model="form.module" placeholder="e.g. goods" @blur="syncTableName" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('generator.moduleName')" prop="title">
              <el-input v-model="form.title" placeholder="e.g. Goods" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('generator.tableName')" prop="table">
              <el-input v-model="form.table" placeholder="e.g. goods" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('menu.routePath')">
              <el-input v-model="form.route_path" placeholder="e.g. goods" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <el-divider />

      <div class="field-toolbar">
        <div class="section-title">{{ t('generator.generationOptions') }}</div>
      </div>

      <el-row :gutter="14" class="option-grid">
        <el-col :span="6">
          <el-checkbox v-model="form.options.write_backend">{{ t('generator.writeBackend') }}</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.write_frontend">{{ t('generator.writeFrontend') }}</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.merge_api">{{ t('generator.writeFrontendApi') }}</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.create_menu">{{ t('generator.generateNavigation') }}</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.execute_schema">{{ t('generator.writeSchema') }}</el-checkbox>
        </el-col>
        <el-col :span="6">
          <el-checkbox v-model="form.options.overwrite_existing">{{ t('generator.overwriteExisting') }}</el-checkbox>
        </el-col>
      </el-row>

      <el-row v-if="form.options.create_menu" :gutter="14" class="navigation-row">
        <el-col :span="8">
          <el-form-item :label="t('generator.parentNavigation')">
            <el-select
              v-model="form.options.menu_parent_id"
              class="full"
              clearable
              :placeholder="t('generator.parentNavigationPlaceholder')"
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
        <div class="section-title">{{ t('generator.fieldConfiguration') }}</div>
        <el-space wrap>
          <el-button v-for="field in quickFields" :key="field.name" plain @click="addQuickField(field)">
            {{ field.label }}
          </el-button>
          <el-button type="primary" plain @click="addField">{{ t('common.create') }}</el-button>
        </el-space>
      </div>

      <div class="field-table">
        <el-table :data="form.fields" border height="100%">
          <el-table-column :label="t('generator.fieldName')" min-width="150">
            <template #default="{ row }">
              <el-input v-model="row.name" placeholder="title" />
            </template>
          </el-table-column>
          <el-table-column :label="t('notice.title')" min-width="150">
            <template #default="{ row }">
              <el-input v-model="row.label" placeholder="Title" />
            </template>
          </el-table-column>
          <el-table-column :label="t('file.type')" width="150">
            <template #default="{ row }">
              <el-select v-model="row.type" class="full">
                <el-option v-for="item in fieldTypes" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </template>
          </el-table-column>
          <el-table-column :label="t('generator.defaultValue')" min-width="130">
            <template #default="{ row }">
              <el-input v-model="row.default" />
            </template>
          </el-table-column>
          <el-table-column :label="t('generator.length')" width="120">
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
          <el-table-column :label="t('generator.minValue')" width="120">
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
          <el-table-column :label="t('generator.maxValue')" width="120">
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
          <el-table-column :label="t('dict.types')" min-width="180">
            <template #default="{ row }">
              <el-select v-if="isOptionField(row.type)" v-model="row.dict_type" clearable filterable class="full" :placeholder="t('generator.optionalDictionary')">
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
          <el-table-column :label="t('common.actions')" width="92" align="center">
            <template #default="{ row }">
              <el-button v-if="isOptionField(row.type) && !row.dict_type" link type="primary" @click="openOptionDialog(row)">{{ t('generator.configure') }}</el-button>
              <span v-else-if="isOptionField(row.type)">{{ t('dict.types') }}</span>
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column :label="t('generator.required')" width="82" align="center">
            <template #default="{ row }">
              <el-checkbox v-model="row.required" />
            </template>
          </el-table-column>
          <el-table-column :label="t('common.search')" width="82" align="center">
            <template #default="{ row }">
              <el-checkbox v-model="row.search" />
            </template>
          </el-table-column>
          <el-table-column :label="t('generator.list')" width="82" align="center">
            <template #default="{ row }">
              <el-checkbox v-model="row.list" />
            </template>
          </el-table-column>
          <el-table-column :label="t('common.actions')" width="90" fixed="right">
            <template #default="{ $index }">
              <el-button link type="danger" @click="removeField($index)">{{ t('common.delete') }}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-card>

    <el-dialog v-model="optionDialogVisible" :title="t('generator.optionConfig')" width="520px">
      <div class="select-option-list">
        <el-row v-for="(option, index) in editingOptionField?.options" :key="index" :gutter="10" class="select-option-row">
          <el-col :span="10">
            <el-input v-model="option.label" :placeholder="t('dict.label')" />
          </el-col>
          <el-col :span="10">
            <el-input v-model="option.value" :placeholder="t('configManage.optionValue')" />
          </el-col>
          <el-col :span="4">
            <el-button link type="danger" @click="removeSelectOption(index)">{{ t('common.delete') }}</el-button>
          </el-col>
        </el-row>
      </div>
      <el-button type="primary" plain @click="addSelectOption">{{ t('configManage.addOption') }}</el-button>
      <template #footer>
        <el-button type="primary" @click="optionDialogVisible = false">{{ t('common.ok') }}</el-button>
      </template>
    </el-dialog>

    <el-card class="page-card result-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
        <div class="page-title">{{ t('generator.generationResult') }}</div>
        </div>
      </template>

      <el-empty v-if="!result" :description="t('generator.noGenerationResult')" />
      <template v-else>
        <el-descriptions :column="1" border>
          <el-descriptions-item :label="t('generator.outputDirectory')">{{ result.output_dir }}</el-descriptions-item>
          <el-descriptions-item :label="t('generator.configFile')">{{ result.config_path }}</el-descriptions-item>
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

        <div class="result-title">{{ t('generator.writeProjectFiles') }}</div>
        <el-scrollbar class="result-files small">
          <el-tree :data="result.installed_files.map((file) => ({ label: file }))" :empty-text="t('generator.noProjectFilesWritten')" />
        </el-scrollbar>

        <el-divider />

        <div class="result-title">{{ t('generator.writtenFrontendApi') }}</div>
        <el-scrollbar class="result-files small">
          <el-tree :data="result.merged_files.map((file) => ({ label: file }))" :empty-text="t('generator.noFrontendApiWritten')" />
        </el-scrollbar>

        <el-divider />

        <div class="result-title">{{ t('generator.runtimeBackups') }}</div>
        <el-scrollbar class="result-files">
          <el-tree :data="result.files.map((file) => ({ label: file }))" />
        </el-scrollbar>

        <el-divider />

        <div class="result-title">{{ t('generator.generationLog') }}</div>
        <el-scrollbar class="result-log">
          <pre>{{ result.log.join('\n') }}</pre>
        </el-scrollbar>

        <el-divider />

        <el-alert :title="t('generator.generatorNotice')" type="info" :closable="false" />
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
