<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  createNavigation,
  deleteNavigation,
  fetchNavigationOptions,
  fetchNavigations,
  updateNavigation,
  updateNavigationStatus,
  type NavigationOptions,
  type NavigationPayload,
  type NavigationRow,
} from '../../../api/navigation'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<NavigationRow[]>([])
const options = ref<NavigationOptions>({
  groups: [
    { label: '主导航', value: 'main' },
    { label: '页脚导航', value: 'footer' },
  ],
  links: [],
})
const query = reactive({
  keyword: '',
  group: '',
})
const form = reactive<NavigationPayload>({
  parent_id: 0,
  group: 'main',
  title: '',
  url: '',
  target: '_self',
  icon: '',
  sort: 100,
  status: 1,
  remark: '',
})
const rules: FormRules = {
  title: [{ required: true, message: '请输入导航名称', trigger: 'blur' }],
}
const parentOptions = computed(() => flattenTree(rows.value).filter((item) => item.id !== editingId.value))

function flattenTree(tree: NavigationRow[], level = 0): Array<NavigationRow & { level: number }> {
  return tree.flatMap((item) => [
    { ...item, level },
    ...flattenTree(item.children || [], level + 1),
  ])
}

async function loadData() {
  loading.value = true
  try {
    rows.value = await fetchNavigations(query)
  } finally {
    loading.value = false
  }
}

async function loadOptions() {
  options.value = await fetchNavigationOptions()
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    parent_id: 0,
    group: 'main',
    title: '',
    url: '',
    target: '_self',
    icon: '',
    sort: 100,
    status: 1,
    remark: '',
  })
  formRef.value?.clearValidate()
}

function openCreate(parent?: NavigationRow) {
  resetForm()
  if (parent) {
    form.parent_id = parent.id
    form.group = parent.group
  }
  dialogVisible.value = true
}

function openEdit(row: NavigationRow) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
    parent_id: row.parent_id,
    group: row.group,
    title: row.title,
    url: row.url,
    target: row.target,
    icon: row.icon,
    sort: row.sort,
    status: row.status,
    remark: row.remark,
  })
  dialogVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true
  try {
    if (editingId.value) {
      await updateNavigation(editingId.value, form)
      ElMessage.success('保存成功')
    } else {
      await createNavigation(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleStatus(row: NavigationRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateNavigationStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success('状态已更新')
}

async function handleDelete(row: NavigationRow) {
  await ElMessageBox.confirm(`确定删除导航「${row.title}」吗？`, '删除确认', { type: 'warning' })
  await deleteNavigation(row.id)
  ElMessage.success('删除成功')
  loadData()
}

onMounted(() => {
  loadData()
  loadOptions()
})
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">导航管理</div>
        <el-button v-if="authStore.hasPermission('admin:navigation:create')" type="primary" @click="openCreate()">新增</el-button>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="导航名称" @keyup.enter="loadData" />
      </el-form-item>
      <el-form-item label="分组">
        <el-select v-model="query.group" clearable placeholder="全部分组" style="width: 140px" @change="loadData">
          <el-option v-for="item in options.groups" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="loadData">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%" row-key="id" default-expand-all :tree-props="{ children: 'children' }">
        <el-table-column prop="title" label="导航名称" min-width="180" />
        <el-table-column prop="group" label="分组" width="120" />
        <el-table-column prop="url" label="链接" min-width="220" show-overflow-tooltip />
        <el-table-column prop="target" label="打开方式" width="110" />
        <el-table-column prop="sort" label="排序" width="90" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-if="authStore.hasPermission('admin:navigation:status')"
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
              <el-button v-if="authStore.hasPermission('admin:navigation:create')" link type="primary" @click="openCreate(row)">新增子级</el-button>
              <el-button v-if="authStore.hasPermission('admin:navigation:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button v-if="authStore.hasPermission('admin:navigation:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
            </el-space>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑导航' : '新增导航'" width="680px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-row :gutter="14">
          <el-col :span="12">
            <el-form-item label="父级导航">
              <el-select v-model="form.parent_id" class="full">
                <el-option label="顶级导航" :value="0" />
                <el-option v-for="item in parentOptions" :key="item.id" :label="`${'　'.repeat(item.level)}${item.title}`" :value="item.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="分组">
              <el-select v-model="form.group" class="full" filterable allow-create default-first-option>
                <el-option v-for="item in options.groups" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="名称" prop="title">
              <el-input v-model="form.title" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="图标">
              <el-input v-model="form.icon" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="链接">
              <el-select
                v-model="form.url"
                class="full"
                filterable
                allow-create
                default-first-option
                placeholder="选择内部页面或输入自定义链接"
              >
                <el-option v-for="item in options.links" :key="item.url" :label="item.label" :value="item.url">
                  <span>{{ item.label }}</span>
                  <span class="link-option-url">{{ item.url }}</span>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="打开方式">
              <el-radio-group v-model="form.target">
                <el-radio-button value="_self">当前窗口</el-radio-button>
                <el-radio-button value="_blank">新窗口</el-radio-button>
              </el-radio-group>
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
            <el-form-item label="备注">
              <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="255" show-word-limit />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}

.link-option-url {
  float: right;
  margin-left: 18px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}
</style>
