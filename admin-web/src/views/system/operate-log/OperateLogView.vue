<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { batchDeleteOperateLogs, clearOperateLogs, fetchOperateLogs, type AdminOperateLogRow } from '../../../api/log'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
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
    ElMessage.warning('请先选择日志')
    return
  }

  await ElMessageBox.prompt(`确定删除选中的 ${selectedRows.value.length} 条操作日志吗？请输入 DELETE 确认。`, '批量删除确认', {
    inputPattern: /^DELETE$/,
    inputErrorMessage: '请输入 DELETE',
    type: 'warning',
  })
  await batchDeleteOperateLogs(selectedIds())
  ElMessage.success('批量删除成功')
  loadData()
}

async function handleClear() {
  await ElMessageBox.prompt('确定清空全部操作日志吗？请输入 CLEAR 确认。', '清空确认', {
    inputPattern: /^CLEAR$/,
    inputErrorMessage: '请输入 CLEAR',
    type: 'warning',
  })
  await clearOperateLogs()
  ElMessage.success('操作日志已清空')
  loadData()
}

onMounted(loadData)
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">操作日志</div>
        <el-space>
          <el-button
            v-if="authStore.hasPermission('admin:operate-log:delete')"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            批量删除
          </el-button>
          <el-button v-if="authStore.hasPermission('admin:operate-log:clear')" type="danger" @click="handleClear">
            清空日志
          </el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="账号 / 路径 / IP" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item label="方法">
        <el-select v-model="query.method" clearable placeholder="全部" style="width: 120px">
          <el-option label="POST" value="POST" />
          <el-option label="PUT" value="PUT" />
          <el-option label="PATCH" value="PATCH" />
          <el-option label="DELETE" value="DELETE" />
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
        <el-table-column prop="username" label="账号" min-width="130" />
        <el-table-column prop="title" label="操作" min-width="140" />
        <el-table-column prop="method" label="方法" width="100" />
        <el-table-column prop="path" label="路径" min-width="220" show-overflow-tooltip />
        <el-table-column prop="params" label="参数" min-width="220" show-overflow-tooltip />
        <el-table-column prop="response" label="响应" min-width="220" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP" min-width="140" />
        <el-table-column prop="status_code" label="状态码" width="100" />
        <el-table-column prop="duration_ms" label="耗时(ms)" width="110" />
        <el-table-column prop="user_agent" label="User-Agent" min-width="260" show-overflow-tooltip />
        <el-table-column prop="create_time" label="操作时间" min-width="170" />
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
