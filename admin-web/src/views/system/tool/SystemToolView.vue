<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  clearSystemCache,
  createDatabaseBackup,
  deleteDatabaseBackup,
  downloadDatabaseBackup,
  fetchSystemTools,
  restoreDatabaseBackup,
  type SystemToolBackup,
  type SystemToolOverview,
} from '../../../api/systemTool'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const loading = ref(false)
const backupLoading = ref(false)
const overview = ref<SystemToolOverview | null>(null)

async function loadData() {
  loading.value = true
  try {
    overview.value = await fetchSystemTools()
  } finally {
    loading.value = false
  }
}

function formatSize(size: number) {
  if (size < 1024) {
    return `${size} B`
  }

  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(2)} KB`
  }

  return `${(size / 1024 / 1024).toFixed(2)} MB`
}

async function handleClearCache() {
  await ElMessageBox.prompt('确定清理系统缓存吗？请输入 CLEAR 确认。', '清理确认', {
    inputPattern: /^CLEAR$/,
    inputErrorMessage: '请输入 CLEAR',
    type: 'warning',
  })
  await clearSystemCache()
  ElMessage.success('缓存已清理')
  loadData()
}

async function handleCreateBackup() {
  backupLoading.value = true
  try {
    await createDatabaseBackup()
    ElMessage.success('数据库备份已创建')
    loadData()
  } finally {
    backupLoading.value = false
  }
}

async function handleRestore(row: SystemToolBackup) {
  await ElMessageBox.prompt(`确定使用「${row.name}」恢复当前数据库吗？请输入 RESTORE 确认。`, '恢复确认', {
    inputPattern: /^RESTORE$/,
    inputErrorMessage: '请输入 RESTORE',
    type: 'warning',
  })
  await restoreDatabaseBackup(row.name)
  ElMessage.success('数据库已恢复')
  loadData()
}

async function handleDownload(row: SystemToolBackup) {
  const blob = await downloadDatabaseBackup(row.name)
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = row.name
  link.click()
  URL.revokeObjectURL(url)
}

async function handleDelete(row: SystemToolBackup) {
  await ElMessageBox.confirm(`确定删除备份「${row.name}」吗？`, '删除确认', {
    type: 'warning',
  })
  await deleteDatabaseBackup(row.name)
  ElMessage.success('备份已删除')
  loadData()
}

onMounted(loadData)
</script>

<template>
  <div v-loading="loading" class="tool-page">
    <el-card class="page-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">缓存管理</div>
          <el-button
            v-if="authStore.hasPermission('admin:tool:cache-clear')"
            type="primary"
            @click="handleClearCache"
          >
            清理缓存
          </el-button>
        </div>
      </template>

      <el-descriptions :column="1" border>
        <el-descriptions-item label="缓存驱动">{{ overview?.cache.driver || '-' }}</el-descriptions-item>
        <el-descriptions-item label="缓存目录">{{ overview?.cache.path || '-' }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <el-card class="page-card table-page-card backup-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">数据库备份</div>
          <el-button
            v-if="authStore.hasPermission('admin:tool:backup-create')"
            type="primary"
            :loading="backupLoading"
            @click="handleCreateBackup"
          >
            创建备份
          </el-button>
        </div>
      </template>

      <div class="table-scroll">
        <el-table :data="overview?.backups || []" border height="100%">
          <el-table-column prop="name" label="备份文件" min-width="240" />
          <el-table-column label="大小" width="120">
            <template #default="{ row }">{{ formatSize(row.size) }}</template>
          </el-table-column>
          <el-table-column prop="create_time" label="创建时间" min-width="170" />
          <el-table-column label="操作" width="210" fixed="right">
            <template #default="{ row }">
              <el-space class="table-actions">
                <el-button
                  v-if="authStore.hasPermission('admin:tool:backup-download')"
                  link
                  type="primary"
                  @click="handleDownload(row)"
                >
                  下载
                </el-button>
                <el-button
                  v-if="authStore.hasPermission('admin:tool:backup-restore')"
                  link
                  type="primary"
                  @click="handleRestore(row)"
                >
                  恢复
                </el-button>
                <el-button
                  v-if="authStore.hasPermission('admin:tool:backup-delete')"
                  link
                  type="danger"
                  @click="handleDelete(row)"
                >
                  删除
                </el-button>
              </el-space>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-card>
  </div>
</template>

<style scoped>
.tool-page {
  display: grid;
  height: 100%;
  min-height: 0;
  grid-template-rows: auto minmax(0, 1fr);
  gap: 14px;
}

.backup-card {
  min-height: 0;
}
</style>
