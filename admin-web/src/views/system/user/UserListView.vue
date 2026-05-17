<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
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
const { t } = useI18n()
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
const rules = computed<FormRules>(() => ({
  username: [{ required: true, message: t('adminUser.usernameRequired'), trigger: 'blur' }],
  nickname: [{ required: true, message: t('adminUser.nicknameRequired'), trigger: 'blur' }],
  role_ids: [{ required: true, message: t('adminUser.rolesRequired'), trigger: 'change' }],
}))

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
      ElMessage.success(t('common.saved'))
    } else {
      await createUser(form)
      ElMessage.success(t('common.created'))
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
  ElMessage.success(t('common.statusUpdated'))
}

async function handleDelete(row: AdminUserRow) {
  await ElMessageBox.confirm(t('adminUser.deleteConfirm', { name: row.username }), t('common.deleteConfirmation'), {
    type: 'warning',
  })
  await deleteUser(row.id)
  ElMessage.success(t('configManage.deleted'))
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
    ElMessage.warning(t('adminUser.selectAdminsFirst'))
    return
  }

  await batchUpdateUserStatus(selectedIds(), status)
  ElMessage.success(t('common.bulkStatusUpdated'))
  loadData()
}

async function handleBatchDelete() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning(t('adminUser.selectAdminsFirst'))
    return
  }

  await ElMessageBox.confirm(t('adminUser.batchDeleteConfirm', { count: selectedRows.value.length }), t('log.bulkDeleteTitle'), {
    type: 'warning',
  })
  await batchDeleteUsers(selectedIds())
  ElMessage.success(t('common.bulkDeleteSuccess'))
  loadData()
}

async function handleForceLogout(row: AdminUserRow) {
  await forceLogoutUser(row.id)
  ElMessage.success(t('adminUser.forceOffline'))
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
        <div class="page-title">{{ t('adminUser.adminUsers') }}</div>
        <el-space>
          <el-button
            v-if="authStore.hasPermission('admin:user:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            {{ t('common.bulkEnable') }}
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:user:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            {{ t('common.bulkDisable') }}
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:user:delete')"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            {{ t('common.bulkDelete') }}
          </el-button>
          <el-button v-if="authStore.hasPermission('admin:user:create')" type="primary" @click="openCreate">{{ t('common.create') }}</el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item :label="t('common.keyword')">
        <el-input v-model="query.keyword" clearable :placeholder="t('adminUser.accountNicknameMobile')" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">{{ t('common.search') }}</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="48" />
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="username" :label="t('common.account')" min-width="140" />
        <el-table-column prop="nickname" :label="t('profile.nickname')" min-width="140" />
        <el-table-column prop="mobile" :label="t('profile.mobile')" min-width="140" />
        <el-table-column prop="email" :label="t('profile.email')" min-width="180" />
        <el-table-column :label="t('common.status')" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? t('common.enabled') : t('common.disabled') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_login_time" :label="t('adminUser.lastLogin')" min-width="170" />
        <el-table-column prop="create_time" :label="t('common.createTime')" min-width="170" />
        <el-table-column :label="t('common.actions')" width="210" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:user:update')" link type="primary" @click="openEdit(row)">{{ t('common.edit') }}</el-button>
              <el-button
                v-if="authStore.hasPermission('admin:user:force-logout')"
                link
                type="primary"
                @click="handleForceLogout(row)"
              >
                {{ t('adminUser.offline') }}
              </el-button>
              <el-button v-if="authStore.hasPermission('admin:user:status')" link type="primary" @click="handleStatusChange(row)">
                {{ row.status === 1 ? t('common.disabled') : t('common.enable') }}
              </el-button>
              <el-button v-if="authStore.hasPermission('admin:user:delete')" link type="danger" @click="handleDelete(row)">{{ t('common.delete') }}</el-button>
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

    <el-dialog v-model="dialogVisible" :title="editingId ? t('adminUser.editAdmin') : t('adminUser.createAdmin')" width="520px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item :label="t('common.account')" prop="username">
          <el-input v-model="form.username" :disabled="Boolean(editingId)" maxlength="50" />
        </el-form-item>
        <el-form-item :label="t('common.password')" :required="!editingId">
          <el-input
            v-model="form.password"
            type="password"
            show-password
            maxlength="50"
            :placeholder="editingId ? t('adminUser.leavePasswordBlank') : t('adminUser.passwordMin')"
          />
        </el-form-item>
        <el-form-item :label="t('profile.nickname')" prop="nickname">
          <el-input v-model="form.nickname" maxlength="50" />
        </el-form-item>
        <el-form-item :label="t('adminUser.role')" prop="role_ids">
          <el-select v-model="form.role_ids" multiple clearable :placeholder="t('adminUser.selectRoles')" class="full">
            <el-option
              v-for="role in roleOptions"
              :key="role.id"
              :label="role.name"
              :value="role.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('profile.mobile')">
          <el-input v-model="form.mobile" maxlength="20" />
        </el-form-item>
        <el-form-item :label="t('profile.email')">
          <el-input v-model="form.email" maxlength="100" />
        </el-form-item>
        <el-form-item :label="t('common.status')">
          <el-radio-group v-model="form.status">
            <el-radio-button :value="1">{{ t('common.enabled') }}</el-radio-button>
            <el-radio-button :value="0">{{ t('common.disabled') }}</el-radio-button>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">{{ t('common.save') }}</el-button>
      </template>
    </el-dialog>
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}
</style>
