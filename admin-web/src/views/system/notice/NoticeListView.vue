<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  createNotice,
  deleteNotice,
  fetchNoticePage,
  updateNotice,
  updateNoticeStatus,
  type AdminNoticePayload,
  type AdminNoticeRow,
} from '../../../api/notice'
import {
  fetchRoleOptions,
  type AdminRoleRow,
} from '../../../api/role'
import {
  fetchUsers,
  type AdminUserRow,
} from '../../../api/user'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<AdminNoticeRow[]>([])
const roleOptions = ref<Pick<AdminRoleRow, 'id' | 'name' | 'code'>[]>([])
const userOptions = ref<AdminUserRow[]>([])
const total = ref(0)
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
  status: '' as number | '',
})
const form = reactive<AdminNoticePayload>({
  title: '',
  content: '',
  type: 'info',
  scope_type: 'all',
  scope_ids: [],
  popup: 0,
  status: 1,
})
const rules: FormRules = {
  title: [{ required: true, message: '请输入消息标题', trigger: 'blur' }],
}
const typeOptions = [
  { label: '主要', value: 'primary' },
  { label: '成功', value: 'success' },
  { label: '信息', value: 'info' },
  { label: '警告', value: 'warning' },
  { label: '危险', value: 'danger' },
]

async function loadOptions() {
  const [roles, users] = await Promise.all([
    fetchRoleOptions(),
    fetchUsers({ page: 1, limit: 100 }),
  ])
  roleOptions.value = roles
  userOptions.value = users.data
}

async function loadData() {
  loading.value = true
  try {
    const data = await fetchNoticePage(query)
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
    title: '',
    content: '',
    type: 'info',
    scope_type: 'all',
    scope_ids: [],
    popup: 0,
    status: 1,
  })
  formRef.value?.clearValidate()
}

function openCreate() {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: AdminNoticeRow) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
    title: row.title,
    content: row.content,
    type: row.type || 'info',
    scope_type: row.scope_type || 'all',
    scope_ids: row.scope_ids || [],
    popup: row.popup || 0,
    status: row.status,
  })
  dialogVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true

  try {
    if (editingId.value) {
      await updateNotice(editingId.value, form)
      ElMessage.success('保存成功')
    } else {
      await createNotice(form)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleStatus(row: AdminNoticeRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateNoticeStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success('状态已更新')
}

async function handleDelete(row: AdminNoticeRow) {
  await ElMessageBox.confirm(`确定删除消息「${row.title}」吗？`, '删除确认', {
    type: 'warning',
  })
  await deleteNotice(row.id)
  ElMessage.success('删除成功')
  loadData()
}

function scopeText(row: AdminNoticeRow) {
  if (row.scope_type === 'role') {
    return `指定角色 ${row.scope_ids.length} 个`
  }

  if (row.scope_type === 'user') {
    return `指定管理员 ${row.scope_ids.length} 个`
  }

  return '全部管理员'
}

onMounted(() => {
  loadData()
  loadOptions().catch(() => undefined)
})
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">消息通知</div>
        <el-button v-if="authStore.hasPermission('admin:notice:create')" type="primary" @click="openCreate">新增</el-button>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="标题 / 内容" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" clearable placeholder="全部" style="width: 120px">
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%">
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="title" label="标题" min-width="180" />
        <el-table-column prop="content" label="内容" min-width="260" show-overflow-tooltip />
        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type || 'info'">{{ row.type || 'info' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="接收范围" width="140">
          <template #default="{ row }">{{ scopeText(row) }}</template>
        </el-table-column>
        <el-table-column label="弹出" width="90">
          <template #default="{ row }">
            <el-tag :type="row.popup === 1 ? 'success' : 'info'">
              {{ row.popup === 1 ? '开启' : '关闭' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="创建时间" min-width="170" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:notice:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button v-if="authStore.hasPermission('admin:notice:status')" link type="primary" @click="handleStatus(row)">
                {{ row.status === 1 ? '禁用' : '启用' }}
              </el-button>
              <el-button v-if="authStore.hasPermission('admin:notice:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
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
      :page-sizes="[10, 20, 50, 100]"
      :total="total"
      @size-change="loadData"
      @current-change="loadData"
    />

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑消息' : '新增消息'" width="560px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" maxlength="100" />
        </el-form-item>
        <el-form-item label="内容">
          <el-input v-model="form.content" type="textarea" :rows="4" maxlength="500" show-word-limit />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="form.type" class="full">
            <el-option v-for="item in typeOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="接收范围">
          <el-radio-group v-model="form.scope_type" @change="form.scope_ids = []">
            <el-radio-button value="all">全部管理员</el-radio-button>
            <el-radio-button value="role">指定角色</el-radio-button>
            <el-radio-button value="user">指定管理员</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="form.scope_type === 'role'" label="接收角色">
          <el-select v-model="form.scope_ids" class="full" multiple filterable placeholder="请选择角色">
            <el-option
              v-for="item in roleOptions"
              :key="item.id"
              :label="`${item.name}（${item.code}）`"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item v-if="form.scope_type === 'user'" label="接收管理员">
          <el-select v-model="form.scope_ids" class="full" multiple filterable placeholder="请选择管理员">
            <el-option
              v-for="item in userOptions"
              :key="item.id"
              :label="item.nickname || item.username"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="右下角弹出">
          <el-switch v-model="form.popup" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio-button :value="1">启用</el-radio-button>
            <el-radio-button :value="0">禁用</el-radio-button>
          </el-radio-group>
        </el-form-item>
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
</style>
