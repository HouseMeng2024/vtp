<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
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
const rules: FormRules = {
  username: [{ required: true, message: '请输入账号', trigger: 'blur' }],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码至少 6 位', trigger: 'blur' },
  ],
  mobile: [
    {
      pattern: /^1[3-9]\d{9}$/,
      message: '手机号格式不正确',
      trigger: 'blur',
    },
  ],
  email: [
    {
      type: 'email',
      message: '邮箱格式不正确',
      trigger: 'blur',
    },
  ],
}
const genderMap: Record<number, string> = {
  0: '未知',
  1: '男',
  2: '女',
}

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
      ElMessage.success('保存成功')
    } else {
      await createMember(form)
      ElMessage.success('创建成功')
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
    ElMessage.warning('请先选择会员')
    return
  }

  await batchUpdateMemberStatus(selectedIds(), status)
  ElMessage.success('批量状态已更新')
  loadData()
}

async function handleBatchDelete() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择会员')
    return
  }

  await ElMessageBox.confirm(`确定删除选中的 ${selectedRows.value.length} 个会员吗？`, '批量删除确认', {
    type: 'warning',
  })
  await batchDeleteMembers(selectedIds())
  ElMessage.success('批量删除成功')
  loadData()
}

async function handleStatus(row: MemberRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateMemberStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success('状态已更新')
}

async function handleResetPassword(row: MemberRow) {
  const { value } = await ElMessageBox.prompt(`请输入会员「${row.nickname || row.username || row.id}」的新密码`, '重置密码', {
    type: 'warning',
    inputType: 'password',
    inputPlaceholder: '至少 6 位',
    inputValidator: (value) => {
      if (!value) {
        return '请输入新密码'
      }

      if (value.length < 6) {
        return '新密码至少 6 位'
      }

      return true
    },
  })
  await resetMemberPassword(row.id, value)
  ElMessage.success('密码已重置')
}

async function handleDelete(row: MemberRow) {
  await ElMessageBox.confirm(`确定删除会员「${row.nickname || row.username || row.id}」吗？`, '删除确认', {
    type: 'warning',
  })
  await deleteMember(row.id)
  ElMessage.success('删除成功')
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
        <div class="page-title">会员管理</div>
        <el-space>
          <el-button
            v-if="authStore.hasPermission('admin:member:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            批量启用
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:member:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            批量禁用
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:member:delete')"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            批量删除
          </el-button>
          <el-button v-if="authStore.hasPermission('admin:member:create')" type="primary" @click="openCreate">新增</el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="账号 / 昵称 / 手机号 / 邮箱" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" clearable placeholder="全部" style="width: 120px">
          <el-option label="正常" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="48" />
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column label="头像" width="90">
          <template #default="{ row }">
            <el-avatar :size="42" :src="row.avatar">{{ row.nickname?.slice(0, 1) || row.username?.slice(0, 1) || '会' }}</el-avatar>
          </template>
        </el-table-column>
        <el-table-column prop="username" label="账号" min-width="140" show-overflow-tooltip />
        <el-table-column prop="nickname" label="昵称" min-width="140" show-overflow-tooltip />
        <el-table-column prop="mobile" label="手机号" min-width="140" show-overflow-tooltip />
        <el-table-column prop="email" label="邮箱" min-width="180" show-overflow-tooltip />
        <el-table-column label="性别" width="90">
          <template #default="{ row }">{{ genderMap[row.gender] || '未知' }}</template>
        </el-table-column>
        <el-table-column prop="birthday" label="生日" min-width="120" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-if="authStore.hasPermission('admin:member:status')"
              :model-value="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatus(row)"
            />
            <el-tag v-else :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="register_time" label="注册时间" min-width="170" />
        <el-table-column prop="last_login_time" label="最后登录" min-width="170" />
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button link type="primary" @click="openDetail(row)">查看</el-button>
              <el-button v-if="authStore.hasPermission('admin:member:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button v-if="authStore.hasPermission('admin:member:reset-password')" link type="primary" @click="handleResetPassword(row)">重置密码</el-button>
              <el-button v-if="authStore.hasPermission('admin:member:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
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

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑会员' : '新增会员'" width="680px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-row :gutter="14">
          <el-col :span="12">
            <el-form-item label="账号" prop="username">
              <el-input v-model="form.username" :disabled="Boolean(editingId)" maxlength="50" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="昵称">
              <el-input v-model="form.nickname" maxlength="50" placeholder="留空默认使用账号" />
            </el-form-item>
          </el-col>
          <el-col v-if="!editingId" :span="12">
            <el-form-item label="密码" prop="password">
              <el-input v-model="form.password" type="password" show-password maxlength="50" placeholder="至少 6 位" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="手机号" prop="mobile">
              <el-input v-model="form.mobile" maxlength="20" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="form.email" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="性别">
              <el-radio-group v-model="form.gender">
                <el-radio-button :value="0">未知</el-radio-button>
                <el-radio-button :value="1">男</el-radio-button>
                <el-radio-button :value="2">女</el-radio-button>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="生日">
              <el-date-picker v-model="form.birthday" class="full" type="date" value-format="YYYY-MM-DD" placeholder="请选择生日" />
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
          <el-col :span="12">
            <el-form-item label="头像">
              <div class="avatar-field">
                <el-avatar :size="42" :src="form.avatar">{{ form.nickname?.slice(0, 1) || '会' }}</el-avatar>
                <el-button @click="openAvatarSelector">选择头像</el-button>
              </div>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="备注">
              <el-input v-model="form.remark" type="textarea" :rows="4" maxlength="500" show-word-limit />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="detailVisible" title="会员详情" width="720px">
      <el-descriptions v-if="detailRow" :column="2" border>
        <el-descriptions-item label="ID">{{ detailRow.id }}</el-descriptions-item>
        <el-descriptions-item label="账号">{{ detailRow.username }}</el-descriptions-item>
        <el-descriptions-item label="昵称">{{ detailRow.nickname }}</el-descriptions-item>
        <el-descriptions-item label="头像">
          <el-avatar :size="48" :src="detailRow.avatar">{{ detailRow.nickname?.slice(0, 1) || '会' }}</el-avatar>
        </el-descriptions-item>
        <el-descriptions-item label="手机号">{{ detailRow.mobile || '-' }}</el-descriptions-item>
        <el-descriptions-item label="邮箱">{{ detailRow.email || '-' }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ genderMap[detailRow.gender] || '未知' }}</el-descriptions-item>
        <el-descriptions-item label="生日">{{ detailRow.birthday || '-' }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="detailRow.status === 1 ? 'success' : 'info'">
            {{ detailRow.status === 1 ? '正常' : '禁用' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="注册 IP">{{ detailRow.register_ip || '-' }}</el-descriptions-item>
        <el-descriptions-item label="注册时间">{{ detailRow.register_time || '-' }}</el-descriptions-item>
        <el-descriptions-item label="最后登录 IP">{{ detailRow.last_login_ip || '-' }}</el-descriptions-item>
        <el-descriptions-item label="最后登录时间">{{ detailRow.last_login_time || '-' }}</el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ detailRow.create_time || '-' }}</el-descriptions-item>
        <el-descriptions-item label="备注" :span="2">{{ detailRow.remark || '-' }}</el-descriptions-item>
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
