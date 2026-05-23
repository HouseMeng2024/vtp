<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  createContentCategory,
  deleteContentCategory,
  fetchContentCategories,
  updateContentCategory,
  updateContentCategoryStatus,
  type ContentCategoryPayload,
  type ContentCategoryRow,
} from '../../../api/contentCategory'
import { fetchDictOptions, type DictOption } from '../../../api/dict'
import FileSelector from '../../../components/FileSelector.vue'
import { useAuthStore } from '../../../stores/auth'
import { normalizeAssetUrl } from '../../../utils/asset'

const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const fileSelectorVisible = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<ContentCategoryRow[]>([])
const contentModelOptions = ref<DictOption[]>([])
const query = reactive({
  keyword: '',
  type: '',
})
const form = reactive<ContentCategoryPayload>({
  parent_id: 0,
  type: 'article',
  name: '',
  slug: '',
  cover: '',
  description: '',
  sort: 100,
  status: 1,
})
const rules: FormRules = {
  name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择内容模型', trigger: 'change' }],
}
const parentOptions = computed(() => flattenTree(rows.value).filter((item) => item.id !== editingId.value && item.type === form.type))
const modelMap = computed(() => new Map(contentModelOptions.value.map((item) => [item.value, item.label])))

function flattenTree(tree: ContentCategoryRow[], level = 0): Array<ContentCategoryRow & { level: number }> {
  return tree.flatMap((item) => [
    { ...item, level },
    ...flattenTree(item.children || [], level + 1),
  ])
}

async function loadData() {
  loading.value = true
  try {
    rows.value = await fetchContentCategories(query)
  } finally {
    loading.value = false
  }
}

async function loadContentModels() {
  const options = await fetchDictOptions('content_model').catch(() => [])
  contentModelOptions.value = options.length ? options : [{ label: '文章', value: 'article', tag_type: 'primary' }]
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    parent_id: 0,
    type: 'article',
    name: '',
    slug: '',
    cover: '',
    description: '',
    sort: 100,
    status: 1,
  })
  formRef.value?.clearValidate()
}

function modelLabel(type: string) {
  return modelMap.value.get(type) || type
}

function openCreate(parent?: ContentCategoryRow) {
  resetForm()
  if (parent) {
    form.parent_id = parent.id
    form.type = parent.type
  }
  dialogVisible.value = true
}

function handleModelChange() {
  if (form.parent_id > 0 && !parentOptions.value.some((item) => item.id === form.parent_id)) {
    form.parent_id = 0
  }
}

function handleParentChange(parentId: number) {
  const parent = flattenTree(rows.value).find((item) => item.id === parentId)

  if (parent) {
    form.type = parent.type
  }
}

function openEdit(row: ContentCategoryRow) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
    parent_id: row.parent_id,
    type: row.type,
    name: row.name,
    slug: row.slug,
    cover: row.cover,
    description: row.description,
    sort: row.sort,
    status: row.status,
  })
  dialogVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true
  try {
    if (editingId.value) {
      await updateContentCategory(editingId.value, form)
      ElMessage.success('保存成功')
    } else {
      await createContentCategory(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleStatus(row: ContentCategoryRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateContentCategoryStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success('状态已更新')
}

async function handleDelete(row: ContentCategoryRow) {
  await ElMessageBox.confirm(`确定删除分类「${row.name}」吗？`, '删除确认', { type: 'warning' })
  await deleteContentCategory(row.id)
  ElMessage.success('删除成功')
  loadData()
}

function handleCoverSelected(files: Array<{ url: string }>) {
  if (files.length) {
    form.cover = files[0].url
  }
}

onMounted(async () => {
  await loadContentModels()
  await loadData()
})
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">内容分类</div>
        <el-button v-if="authStore.hasPermission('admin:content-category:create')" type="primary" @click="openCreate()">新增</el-button>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="分类名称" @keyup.enter="loadData" />
      </el-form-item>
      <el-form-item label="类型">
        <el-select v-model="query.type" clearable placeholder="全部模型" style="width: 140px" @change="loadData">
          <el-option v-for="item in contentModelOptions" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="loadData">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%" row-key="id" :tree-props="{ children: 'children' }">
        <el-table-column prop="name" label="分类名称" min-width="180" />
        <el-table-column label="内容模型" width="120">
          <template #default="{ row }">
            <el-tag :type="row.type === 'article' ? 'primary' : 'info'">{{ modelLabel(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="slug" label="标识" min-width="140" show-overflow-tooltip />
        <el-table-column label="封面" width="96">
          <template #default="{ row }">
            <el-image v-if="row.cover" class="thumb" :src="normalizeAssetUrl(row.cover)" fit="cover" :preview-src-list="[normalizeAssetUrl(row.cover)]" preview-teleported />
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="90" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-if="authStore.hasPermission('admin:content-category:status')"
              :model-value="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatus(row)"
            />
            <el-tag v-else :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '正常' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:content-category:create')" link type="primary" @click="openCreate(row)">新增子级</el-button>
              <el-button v-if="authStore.hasPermission('admin:content-category:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button v-if="authStore.hasPermission('admin:content-category:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
            </el-space>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑分类' : '新增分类'" width="720px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-row :gutter="14">
          <el-col :span="12">
            <el-form-item label="父级分类">
              <el-select v-model="form.parent_id" class="full" @change="handleParentChange">
                <el-option label="顶级分类" :value="0" />
                <el-option v-for="item in parentOptions" :key="item.id" :label="`${'　'.repeat(item.level)}${item.name}`" :value="item.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="内容模型" prop="type">
              <el-select v-model="form.type" class="full" :disabled="form.parent_id > 0" @change="handleModelChange">
                <el-option v-for="item in contentModelOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="名称" prop="name">
              <el-input v-model="form.name" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="标识">
              <el-input v-model="form.slug" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="排序">
              <el-input-number v-model="form.sort" :min="0" :max="99999" class="full" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态">
              <el-radio-group v-model="form.status">
                <el-radio-button :value="1">正常</el-radio-button>
                <el-radio-button :value="0">禁用</el-radio-button>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="封面">
              <button class="image-picker" type="button" @click="fileSelectorVisible = true">
                <el-image v-if="form.cover" :src="normalizeAssetUrl(form.cover)" fit="cover" />
                <el-icon v-else><Plus /></el-icon>
              </button>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="描述">
              <el-input v-model="form.description" type="textarea" :rows="3" maxlength="500" show-word-limit />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>

    <FileSelector v-model="fileSelectorVisible" accept-type="image" scene="content_category" :current-url="form.cover" @select="handleCoverSelected" />
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}

.thumb {
  width: 48px;
  height: 48px;
  border-radius: 4px;
}

.image-picker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 96px;
  height: 96px;
  overflow: hidden;
  color: var(--el-text-color-secondary);
  cursor: pointer;
  background: var(--el-fill-color-lighter);
  border: 1px dashed var(--el-border-color);
  border-radius: 6px;
}

.image-picker .el-image {
  width: 100%;
  height: 100%;
}
</style>
