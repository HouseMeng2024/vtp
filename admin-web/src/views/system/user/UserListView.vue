<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  batchDeleteUsers,
  batchUpdateUserStatus,
  createUser,
  deleteUser,
  fetchRoleOptions,
  fetchUsers,
  forceLogoutUser,
  updateUser,
  updateUserStatus,
  type AdminRoleRow,
  type AdminUserPayload,
  type AdminUserRow,
} from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<AdminUserRow[]>([])
const selectedRows = ref<AdminUserRow[]>([])
const roleOptions = ref<Pick<AdminRoleRow, 'id' | 'name' | 'code'>[]>([])
const total = ref(0)
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
})
const form = reactive<AdminUserPayload>({
  username: '',
  password: '',
  nickname: '',
  mobile: '',
  email: '',
  status: 1,
  role_ids: [],
})
const rules: FormRules = {
  username: [{ required: true, message: '请输入账号', trigger: 'blur' }],
  nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
  role_ids: [{ required: true, message: '请选择角色', trigger: 'change' }],
}

async function loadData() {
  loading.value = true
  try {
    const data = await fetchUsers(query)
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

async function loadRoleOptions() {
  roleOptions.value = await fetchRoleOptions()
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    username: '',
    password: '',
    nickname: '',
    mobile: '',
    email: '',
    status: 1,
    role_ids: [],
  })
  formRef.value?.clearValidate()
}

function openCreate() {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: AdminUserRow) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
    username: row.username,
    password: '',
    nickname: row.nickname,
    mobile: row.mobile,
    email: row.email,
    status: row.status,
    role_ids: [...row.role_ids],
  })
  dialogVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true

  try {
    if (editingId.value) {
      await updateUser(editingId.value, form)
      ElMessage.success('保存成功')
    } else {
      await createUser(form)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleStatusChange(row: AdminUserRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateUserStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success('状态已更新')
}

async function handleDelete(row: AdminUserRow) {
  await ElMessageBox.confirm(`确定删除管理员「${row.username}」吗？`, '删除确认', {
    type: 'warning',
  })
  await deleteUser(row.id)
  ElMessage.success('删除成功')
  loadData()
}

function selectedIds() {
  return selectedRows.value.map((row) => row.id)
}

function handleSelectionChange(selection: AdminUserRow[]) {
  selectedRows.value = selection
}

async function handleBatchStatus(status: number) {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择管理员')
    return
  }

  await batchUpdateUserStatus(selectedIds(), status)
  ElMessage.success('批量状态已更新')
  loadData()
}

async function handleBatchDelete() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择管理员')
    return
  }

  await ElMessageBox.confirm(`确定删除选中的 ${selectedRows.value.length} 个管理员吗？`, '批量删除确认', {
    type: 'warning',
  })
  await batchDeleteUsers(selectedIds())
  ElMessage.success('批量删除成功')
  loadData()
}

async function handleForceLogout(row: AdminUserRow) {
  await forceLogoutUser(row.id)
  ElMessage.success('已强制下线')
}

onMounted(() => {
  loadData()
  loadRoleOptions()
})
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">管理员管理</div>
        <el-space>
          <el-button
            v-if="authStore.hasPermission('admin:user:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            批量启用
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:user:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            批量禁用
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:user:delete')"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            批量删除
          </el-button>
          <el-button v-if="authStore.hasPermission('admin:user:create')" type="primary" @click="openCreate">新增</el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="账号 / 昵称 / 手机号" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="48" />
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="username" label="账号" min-width="140" />
        <el-table-column prop="nickname" label="昵称" min-width="140" />
        <el-table-column prop="mobile" label="手机号" min-width="140" />
        <el-table-column prop="email" label="邮箱" min-width="180" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_login_time" label="最后登录" min-width="170" />
        <el-table-column prop="create_time" label="创建时间" min-width="170" />
        <el-table-column label="操作" width="210" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:user:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button
                v-if="authStore.hasPermission('admin:user:force-logout')"
                link
                type="primary"
                @click="handleForceLogout(row)"
              >
                下线
              </el-button>
              <el-button v-if="authStore.hasPermission('admin:user:status')" link type="primary" @click="handleStatusChange(row)">
                {{ row.status === 1 ? '禁用' : '启用' }}
              </el-button>
              <el-button v-if="authStore.hasPermission('admin:user:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
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

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑管理员' : '新增管理员'" width="520px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="账号" prop="username">
          <el-input v-model="form.username" :disabled="Boolean(editingId)" maxlength="50" />
        </el-form-item>
        <el-form-item label="密码" :required="!editingId">
          <el-input
            v-model="form.password"
            type="password"
            show-password
            maxlength="50"
            :placeholder="editingId ? '留空则不修改' : '至少 6 位'"
          />
        </el-form-item>
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="form.nickname" maxlength="50" />
        </el-form-item>
        <el-form-item label="角色" prop="role_ids">
          <el-select v-model="form.role_ids" multiple clearable placeholder="请选择角色" class="full">
            <el-option
              v-for="role in roleOptions"
              :key="role.id"
              :label="role.name"
              :value="role.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="手机号">
          <el-input v-model="form.mobile" maxlength="20" />
        </el-form-item>
        <el-form-item label="邮箱">
          <el-input v-model="form.email" maxlength="100" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio-button :value="1">正常</el-radio-button>
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
