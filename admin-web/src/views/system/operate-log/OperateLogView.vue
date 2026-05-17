<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { batchDeleteOperateLogs, clearOperateLogs, fetchOperateLogs, type AdminOperateLogRow } from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const { t } = useI18n()
const loading = ref(false)
const rows = ref<AdminOperateLogRow[]>([])
const selectedRows = ref<AdminOperateLogRow[]>([])
const total = ref(0)
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
  method: '',
})

async function loadData() {
  loading.value = true
  try {
    const data = await fetchOperateLogs(query)
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

function handleSelectionChange(selection: AdminOperateLogRow[]) {
  selectedRows.value = selection
}

async function handleBatchDelete() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning(t('common.selectLogsFirst'))
    return
  }

  await ElMessageBox.prompt(t('log.bulkDeleteOperateConfirm', { count: selectedRows.value.length }), t('log.bulkDeleteTitle'), {
    inputPattern: /^DELETE$/,
    inputErrorMessage: t('log.enterDelete'),
    type: 'warning',
  })
  await batchDeleteOperateLogs(selectedIds())
  ElMessage.success(t('common.bulkDeleteSuccess'))
  loadData()
}

async function handleClear() {
  await ElMessageBox.prompt(t('log.clearOperateConfirm'), t('common.clearConfirmation'), {
    inputPattern: /^CLEAR$/,
    inputErrorMessage: t('log.enterClear'),
    type: 'warning',
  })
  await clearOperateLogs()
  ElMessage.success(t('log.operateCleared'))
  loadData()
}

onMounted(loadData)
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">{{ t('log.operationLogs') }}</div>
        <el-space>
          <el-button
            v-if="authStore.hasPermission('admin:operate-log:delete')"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            {{ t('common.bulkDelete') }}
          </el-button>
          <el-button v-if="authStore.hasPermission('admin:operate-log:clear')" type="danger" @click="handleClear">
            {{ t('common.clearLogs') }}
          </el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item :label="t('common.keyword')">
        <el-input v-model="query.keyword" clearable :placeholder="t('common.accountPathIp')" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item :label="t('common.method')">
        <el-select v-model="query.method" clearable :placeholder="t('common.all')" style="width: 120px">
          <el-option label="POST" value="POST" />
          <el-option label="PUT" value="PUT" />
          <el-option label="PATCH" value="PATCH" />
          <el-option label="DELETE" value="DELETE" />
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
        <el-table-column prop="title" :label="t('common.actions')" min-width="140" />
        <el-table-column prop="method" :label="t('common.method')" width="100" />
        <el-table-column prop="path" :label="t('common.path')" min-width="220" show-overflow-tooltip />
        <el-table-column prop="params" :label="t('common.params')" min-width="220" show-overflow-tooltip />
        <el-table-column prop="response" :label="t('common.response')" min-width="220" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP" min-width="140" />
        <el-table-column prop="status_code" :label="t('common.statusCode')" width="100" />
        <el-table-column prop="duration_ms" :label="t('common.duration')" width="110" />
        <el-table-column prop="user_agent" :label="t('common.userAgent')" min-width="260" show-overflow-tooltip />
        <el-table-column prop="create_time" :label="t('common.actionTime')" min-width="170" />
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
