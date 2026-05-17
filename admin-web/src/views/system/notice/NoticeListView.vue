<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  createNotice,
  deleteNotice,
  fetchNoticePage,
  fetchRoleOptions,
  fetchUsers,
  updateNotice,
  updateNoticeStatus,
  type AdminNoticePayload,
  type AdminNoticeRow,
  type AdminRoleRow,
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
const rules = computed<FormRules>(() => ({
  title: [{ required: true, message: t('notice.noticeTitleRequired'), trigger: 'blur' }],
}))
const typeOptions = computed(() => [
  { label: 'Primary', value: 'primary' },
  { label: t('common.success'), value: 'success' },
  { label: 'Info', value: 'info' },
  { label: 'Warning', value: 'warning' },
  { label: 'Danger', value: 'danger' },
])

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
      ElMessage.success(t('common.saved'))
    } else {
      await createNotice(form)
      ElMessage.success(t('common.created'))
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
  ElMessage.success(t('common.statusUpdated'))
}

async function handleDelete(row: AdminNoticeRow) {
  await ElMessageBox.confirm(t('notice.deleteConfirm', { title: row.title }), t('common.deleteConfirmation'), {
    type: 'warning',
  })
  await deleteNotice(row.id)
  ElMessage.success(t('configManage.deleted'))
  loadData()
}

function scopeText(row: AdminNoticeRow) {
  if (row.scope_type === 'role') {
    return t('notice.selectedRoles')
  }

  if (row.scope_type === 'user') {
    return t('notice.selectedAdmins')
  }

  return t('notice.allAdmins')
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
        <div class="page-title">{{ t('notice.notices') }}</div>
        <el-button v-if="authStore.hasPermission('admin:notice:create')" type="primary" @click="openCreate">{{ t('common.create') }}</el-button>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item :label="t('common.keyword')">
        <el-input v-model="query.keyword" clearable :placeholder="t('notice.titleContent')" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item :label="t('common.status')">
        <el-select v-model="query.status" clearable :placeholder="t('common.all')" style="width: 120px">
          <el-option :label="t('common.enable')" :value="1" />
          <el-option :label="t('common.disabled')" :value="0" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">{{ t('common.search') }}</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%">
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="title" :label="t('notice.title')" min-width="180" />
        <el-table-column prop="content" :label="t('notice.content')" min-width="260" show-overflow-tooltip />
        <el-table-column :label="t('file.type')" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type || 'info'">{{ row.type || 'info' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('notice.scope')" width="140">
          <template #default="{ row }">{{ scopeText(row) }}</template>
        </el-table-column>
        <el-table-column :label="t('notice.popup')" width="90">
          <template #default="{ row }">
            <el-tag :type="row.popup === 1 ? 'success' : 'info'">
              {{ row.popup === 1 ? t('common.on') : t('common.off') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('common.status')" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? t('common.enable') : t('common.disabled') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" :label="t('common.createTime')" min-width="170" />
        <el-table-column :label="t('common.actions')" width="180" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:notice:update')" link type="primary" @click="openEdit(row)">{{ t('common.edit') }}</el-button>
              <el-button v-if="authStore.hasPermission('admin:notice:status')" link type="primary" @click="handleStatus(row)">
                {{ row.status === 1 ? t('common.disabled') : t('common.enable') }}
              </el-button>
              <el-button v-if="authStore.hasPermission('admin:notice:delete')" link type="danger" @click="handleDelete(row)">{{ t('common.delete') }}</el-button>
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

    <el-dialog v-model="dialogVisible" :title="editingId ? t('notice.editNotice') : t('notice.createNotice')" width="560px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item :label="t('notice.title')" prop="title">
          <el-input v-model="form.title" maxlength="100" />
        </el-form-item>
        <el-form-item :label="t('notice.content')">
          <el-input v-model="form.content" type="textarea" :rows="4" maxlength="500" show-word-limit />
        </el-form-item>
        <el-form-item :label="t('file.type')">
          <el-select v-model="form.type" class="full">
            <el-option v-for="item in typeOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('notice.scope')">
          <el-radio-group v-model="form.scope_type" @change="form.scope_ids = []">
            <el-radio-button value="all">{{ t('notice.allAdmins') }}</el-radio-button>
            <el-radio-button value="role">{{ t('notice.selectedRoles') }}</el-radio-button>
            <el-radio-button value="user">{{ t('notice.selectedAdmins') }}</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="form.scope_type === 'role'" :label="t('notice.recipientRoles')">
          <el-select v-model="form.scope_ids" class="full" multiple filterable :placeholder="t('adminUser.selectRoles')">
            <el-option
              v-for="item in roleOptions"
              :key="item.id"
              :label="`${item.name}（${item.code}）`"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item v-if="form.scope_type === 'user'" :label="t('notice.recipientAdmins')">
          <el-select v-model="form.scope_ids" class="full" multiple filterable :placeholder="t('adminUser.selectAdmins')">
            <el-option
              v-for="item in userOptions"
              :key="item.id"
              :label="item.nickname || item.username"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('notice.bottomRightPopup')">
          <el-switch v-model="form.popup" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item :label="t('common.status')">
          <el-radio-group v-model="form.status">
            <el-radio-button :value="1">{{ t('common.enable') }}</el-radio-button>
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
