<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { batchDeleteLoginLogs, clearLoginLogs, fetchLoginLogs, type AdminLoginLogRow } from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const { t } = useI18n()
const loading = ref(false)
const rows = ref<AdminLoginLogRow[]>([])
const selectedRows = ref<AdminLoginLogRow[]>([])
const total = ref(0)
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
  status: '' as number | '',
})

async function loadData() {
  loading.value = true
  try {
    const data = await fetchLoginLogs(query)
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

function selectedIds() {
  return selectedRows.value.map((row) => row.id)
}

function handleSelectionChange(selection: AdminLoginLogRow[]) {
  selectedRows.value = selection
}

async function handleBatchDelete() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning(t('common.selectLogsFirst'))
    return
  }

  await ElMessageBox.prompt(t('log.bulkDeleteLoginConfirm', { count: selectedRows.value.length }), t('log.bulkDeleteTitle'), {
    inputPattern: /^DELETE$/,
    inputErrorMessage: t('log.enterDelete'),
    type: 'warning',
  })
  await batchDeleteLoginLogs(selectedIds())
  ElMessage.success(t('common.bulkDeleteSuccess'))
  loadData()
}

async function handleClear() {
  await ElMessageBox.prompt(t('log.clearLoginConfirm'), t('common.clearConfirmation'), {
    inputPattern: /^CLEAR$/,
    inputErrorMessage: t('log.enterClear'),
    type: 'warning',
  })
  await clearLoginLogs()
  ElMessage.success(t('log.loginCleared'))
  loadData()
}

onMounted(loadData)
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">{{ t('log.loginLogs') }}</div>
        <el-space>
          <el-button
            v-if="authStore.hasPermission('admin:login-log:delete')"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            {{ t('common.bulkDelete') }}
          </el-button>
          <el-button v-if="authStore.hasPermission('admin:login-log:clear')" type="danger" @click="handleClear">
            {{ t('common.clearLogs') }}
          </el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item :label="t('common.keyword')">
        <el-input v-model="query.keyword" clearable :placeholder="t('common.accountOrIp')" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item :label="t('common.status')">
        <el-select v-model="query.status" clearable :placeholder="t('common.all')" style="width: 120px">
          <el-option :label="t('common.success')" :value="1" />
          <el-option :label="t('common.failed')" :value="0" />
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
        <el-table-column prop="username" :label="t('common.account')" min-width="130" />
        <el-table-column prop="ip" label="IP" min-width="140" />
        <el-table-column :label="t('common.status')" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? t('common.success') : t('common.failed') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="message" :label="t('common.message')" min-width="160" />
        <el-table-column prop="user_agent" :label="t('common.userAgent')" min-width="260" show-overflow-tooltip />
        <el-table-column prop="create_time" :label="t('log.loginTime')" min-width="170" />
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
  </el-card>
</template>
