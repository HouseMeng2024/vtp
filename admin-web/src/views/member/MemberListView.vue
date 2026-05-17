<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  batchDeleteMembers,
  batchUpdateMemberStatus,
  createMember,
  deleteMember,
  fetchMemberDetail,
  fetchMembers,
  resetMemberPassword,
  updateMember,
  updateMemberStatus,
  type MemberPayload,
  type MemberRow,
} from '../../api/system'
import { useAuthStore } from '../../stores/auth'
import FileSelector from '../../components/FileSelector.vue'

const authStore = useAuthStore()
const { t } = useI18n()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const detailVisible = ref(false)
const fileSelectorVisible = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<MemberRow[]>([])
const selectedRows = ref<MemberRow[]>([])
const detailRow = ref<MemberRow | null>(null)
const total = ref(0)
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
  status: '' as number | '',
})
const form = reactive<MemberPayload>({
  username: '',
  password: '',
  nickname: '',
  avatar: '',
  mobile: '',
  email: '',
  gender: 0,
  birthday: '',
  status: 1,
  remark: '',
})
const rules = computed<FormRules>(() => ({
  username: [{ required: true, message: t('adminUser.usernameRequired'), trigger: 'blur' }],
  password: [
    { required: true, message: t('profile.passwordRequired'), trigger: 'blur' },
    { min: 6, message: t('member.passwordMinRule'), trigger: 'blur' },
  ],
  mobile: [
    {
      pattern: /^1[3-9]\d{9}$/,
      message: t('member.invalidMobile'),
      trigger: 'blur',
    },
  ],
  email: [
    {
      type: 'email',
      message: t('profile.emailInvalid'),
      trigger: 'blur',
    },
  ],
}))
const genderMap = computed<Record<number, string>>(() => ({
  0: t('member.unknown'),
  1: t('member.male'),
  2: t('member.female'),
}))

async function loadData() {
  loading.value = true

  try {
    const data = await fetchMembers(query)
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
    username: '',
    password: '',
    nickname: '',
    avatar: '',
    mobile: '',
    email: '',
    gender: 0,
    birthday: '',
    status: 1,
    remark: '',
  })
  formRef.value?.clearValidate()
}

function openCreate() {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: MemberRow) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
    username: row.username,
    password: '',
    nickname: row.nickname,
    avatar: row.avatar,
    mobile: row.mobile,
    email: row.email,
    gender: row.gender,
    birthday: row.birthday || '',
    status: row.status,
    remark: row.remark,
  })
  dialogVisible.value = true
}

async function openDetail(row: MemberRow) {
  detailRow.value = await fetchMemberDetail(row.id)
  detailVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true

  try {
    if (editingId.value) {
      await updateMember(editingId.value, form)
      ElMessage.success(t('common.saved'))
    } else {
      await createMember(form)
      ElMessage.success(t('common.created'))
    }

    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

function selectedIds() {
  return selectedRows.value.map((row) => row.id)
}

function handleSelectionChange(selection: MemberRow[]) {
  selectedRows.value = selection
}

async function handleBatchStatus(status: number) {
  if (selectedRows.value.length === 0) {
    ElMessage.warning(t('member.selectMembersFirst'))
    return
  }

  await batchUpdateMemberStatus(selectedIds(), status)
  ElMessage.success(t('common.bulkStatusUpdated'))
  loadData()
}

async function handleBatchDelete() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning(t('member.selectMembersFirst'))
    return
  }

  await ElMessageBox.confirm(t('member.batchDeleteConfirm', { count: selectedRows.value.length }), t('log.bulkDeleteTitle'), {
    type: 'warning',
  })
  await batchDeleteMembers(selectedIds())
  ElMessage.success(t('common.bulkDeleteSuccess'))
  loadData()
}

async function handleStatus(row: MemberRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateMemberStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success(t('common.statusUpdated'))
}

async function handleResetPassword(row: MemberRow) {
  const { value } = await ElMessageBox.prompt(t('member.resetPasswordPrompt', { name: row.nickname || row.username || row.id }), t('member.resetPassword'), {
    type: 'warning',
    inputType: 'password',
    inputPlaceholder: t('adminUser.passwordMin'),
    inputValidator: (value) => {
      if (!value) {
        return t('profile.passwordRequired')
      }

      if (value.length < 6) {
        return t('profile.newPasswordMin')
      }

      return true
    },
  })
  await resetMemberPassword(row.id, value)
  ElMessage.success(t('member.passwordReset'))
}

async function handleDelete(row: MemberRow) {
  await ElMessageBox.confirm(t('member.deleteConfirm', { name: row.nickname || row.username || row.id }), t('common.deleteConfirmation'), {
    type: 'warning',
  })
  await deleteMember(row.id)
  ElMessage.success(t('configManage.deleted'))
  loadData()
}

function openAvatarSelector() {
  fileSelectorVisible.value = true
}

function handleAvatarSelected(files: Array<{ url: string }>) {
  if (files.length) {
    form.avatar = files[0].url
  }
}

onMounted(loadData)
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">{{ t('member.members') }}</div>
        <el-space>
          <el-button
            v-if="authStore.hasPermission('admin:member:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            {{ t('common.bulkEnable') }}
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:member:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            {{ t('common.bulkDisable') }}
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:member:delete')"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            {{ t('common.bulkDelete') }}
          </el-button>
          <el-button v-if="authStore.hasPermission('admin:member:create')" type="primary" @click="openCreate">{{ t('common.create') }}</el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item :label="t('common.keyword')">
        <el-input v-model="query.keyword" clearable :placeholder="t('member.accountNicknameMobileEmail')" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item :label="t('common.status')">
        <el-select v-model="query.status" clearable :placeholder="t('common.all')" style="width: 120px">
          <el-option :label="t('common.enabled')" :value="1" />
          <el-option :label="t('common.disabled')" :value="0" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">{{ t('common.search') }}</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="48" />
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column :label="t('member.avatar')" width="90">
          <template #default="{ row }">
            <el-avatar :size="42" :src="row.avatar">{{ row.nickname?.slice(0, 1) || row.username?.slice(0, 1) || 'M' }}</el-avatar>
          </template>
        </el-table-column>
        <el-table-column prop="username" :label="t('common.account')" min-width="140" show-overflow-tooltip />
        <el-table-column prop="nickname" :label="t('profile.nickname')" min-width="140" show-overflow-tooltip />
        <el-table-column prop="mobile" :label="t('profile.mobile')" min-width="140" show-overflow-tooltip />
        <el-table-column prop="email" :label="t('profile.email')" min-width="180" show-overflow-tooltip />
        <el-table-column :label="t('member.gender')" width="90">
          <template #default="{ row }">{{ genderMap[row.gender] || t('member.unknown') }}</template>
        </el-table-column>
        <el-table-column prop="birthday" :label="t('member.birthday')" min-width="120" />
        <el-table-column :label="t('common.status')" width="100">
          <template #default="{ row }">
            <el-switch
              v-if="authStore.hasPermission('admin:member:status')"
              :model-value="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatus(row)"
            />
            <el-tag v-else :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? t('common.enabled') : t('common.disabled') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="register_time" :label="t('member.registerTime')" min-width="170" />
        <el-table-column prop="last_login_time" :label="t('adminUser.lastLogin')" min-width="170" />
        <el-table-column :label="t('common.actions')" width="280" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button link type="primary" @click="openDetail(row)">{{ t('file.view') }}</el-button>
              <el-button v-if="authStore.hasPermission('admin:member:update')" link type="primary" @click="openEdit(row)">{{ t('common.edit') }}</el-button>
              <el-button v-if="authStore.hasPermission('admin:member:reset-password')" link type="primary" @click="handleResetPassword(row)">{{ t('member.resetPassword') }}</el-button>
              <el-button v-if="authStore.hasPermission('admin:member:delete')" link type="danger" @click="handleDelete(row)">{{ t('common.delete') }}</el-button>
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

    <el-dialog v-model="dialogVisible" :title="editingId ? t('member.editMember') : t('member.createMember')" width="680px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-row :gutter="14">
          <el-col :span="12">
            <el-form-item :label="t('common.account')" prop="username">
              <el-input v-model="form.username" :disabled="Boolean(editingId)" maxlength="50" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="t('profile.nickname')">
              <el-input v-model="form.nickname" maxlength="50" :placeholder="t('member.leaveNicknameBlank')" />
            </el-form-item>
          </el-col>
          <el-col v-if="!editingId" :span="12">
            <el-form-item :label="t('common.password')" prop="password">
              <el-input v-model="form.password" type="password" show-password maxlength="50" :placeholder="t('adminUser.passwordMin')" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="t('profile.mobile')" prop="mobile">
              <el-input v-model="form.mobile" maxlength="20" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="t('profile.email')" prop="email">
              <el-input v-model="form.email" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="t('member.gender')">
              <el-radio-group v-model="form.gender">
                <el-radio-button :value="0">{{ t('member.unknown') }}</el-radio-button>
                <el-radio-button :value="1">{{ t('member.male') }}</el-radio-button>
                <el-radio-button :value="2">{{ t('member.female') }}</el-radio-button>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="t('member.birthday')">
              <el-date-picker v-model="form.birthday" class="full" type="date" value-format="YYYY-MM-DD" :placeholder="t('member.selectBirthday')" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="t('common.status')">
              <el-radio-group v-model="form.status">
                <el-radio-button :value="1">{{ t('common.enabled') }}</el-radio-button>
                <el-radio-button :value="0">{{ t('common.disabled') }}</el-radio-button>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="t('member.avatar')">
              <div class="avatar-field">
                <el-avatar :size="42" :src="form.avatar">{{ form.nickname?.slice(0, 1) || 'M' }}</el-avatar>
                <el-button @click="openAvatarSelector">{{ t('member.selectAvatar') }}</el-button>
              </div>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item :label="t('configManage.remark')">
              <el-input v-model="form.remark" type="textarea" :rows="4" maxlength="500" show-word-limit />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">{{ t('common.save') }}</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="detailVisible" :title="t('member.detail')" width="720px">
      <el-descriptions v-if="detailRow" :column="2" border>
        <el-descriptions-item label="ID">{{ detailRow.id }}</el-descriptions-item>
        <el-descriptions-item :label="t('common.account')">{{ detailRow.username }}</el-descriptions-item>
        <el-descriptions-item :label="t('profile.nickname')">{{ detailRow.nickname }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.avatar')">
          <el-avatar :size="48" :src="detailRow.avatar">{{ detailRow.nickname?.slice(0, 1) || 'M' }}</el-avatar>
        </el-descriptions-item>
        <el-descriptions-item :label="t('profile.mobile')">{{ detailRow.mobile || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('profile.email')">{{ detailRow.email || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.gender')">{{ genderMap[detailRow.gender] || t('member.unknown') }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.birthday')">{{ detailRow.birthday || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('common.status')">
          <el-tag :type="detailRow.status === 1 ? 'success' : 'info'">
            {{ detailRow.status === 1 ? t('common.enabled') : t('common.disabled') }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item :label="t('member.registerIp')">{{ detailRow.register_ip || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.registerTime')">{{ detailRow.register_time || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('member.lastLoginIp')">{{ detailRow.last_login_ip || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('adminUser.lastLogin')">{{ detailRow.last_login_time || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('common.createTime')">{{ detailRow.create_time || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('configManage.remark')" :span="2">{{ detailRow.remark || '-' }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <FileSelector
      v-model="fileSelectorVisible"
      accept-type="image"
      scene="member_avatar"
      :current-url="form.avatar"
      @select="handleAvatarSelected"
    />
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}

.avatar-field {
  display: flex;
  align-items: center;
  gap: 12px;
}
</style>
