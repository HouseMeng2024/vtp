<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
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
} from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const { t } = useI18n()
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
  await ElMessageBox.prompt(t('tool.clearCacheConfirm'), t('common.clearConfirmation'), {
    inputPattern: /^CLEAR$/,
    inputErrorMessage: t('log.enterClear'),
    type: 'warning',
  })
  await clearSystemCache()
  ElMessage.success(t('tool.cacheCleared'))
  loadData()
}

async function handleCreateBackup() {
  backupLoading.value = true
  try {
    await createDatabaseBackup()
    ElMessage.success(t('tool.databaseBackupCreated'))
    loadData()
  } finally {
    backupLoading.value = false
  }
}

async function handleRestore(row: SystemToolBackup) {
  await ElMessageBox.prompt(t('tool.restoreConfirm', { name: row.name }), t('tool.restoreConfirmation'), {
    inputPattern: /^RESTORE$/,
    inputErrorMessage: t('tool.enterRestore'),
    type: 'warning',
  })
  await restoreDatabaseBackup(row.name)
  ElMessage.success(t('tool.databaseRestored'))
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
  await ElMessageBox.confirm(t('tool.deleteBackupConfirm', { name: row.name }), t('common.deleteConfirmation'), {
    type: 'warning',
  })
  await deleteDatabaseBackup(row.name)
  ElMessage.success(t('tool.backupDeleted'))
  loadData()
}

onMounted(loadData)
</script>

<template>
  <div v-loading="loading" class="tool-page">
    <el-card class="page-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">{{ t('tool.cacheManagement') }}</div>
          <el-button
            v-if="authStore.hasPermission('admin:tool:cache-clear')"
            type="primary"
            @click="handleClearCache"
          >
            {{ t('tool.clearCache') }}
          </el-button>
        </div>
      </template>

      <el-descriptions :column="1" border>
        <el-descriptions-item :label="t('tool.cacheDriver')">{{ overview?.cache.driver || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('tool.cachePath')">{{ overview?.cache.path || '-' }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <el-card class="page-card table-page-card backup-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">{{ t('tool.databaseBackups') }}</div>
          <el-button
            v-if="authStore.hasPermission('admin:tool:backup-create')"
            type="primary"
            :loading="backupLoading"
            @click="handleCreateBackup"
          >
            {{ t('tool.createBackup') }}
          </el-button>
        </div>
      </template>

      <div class="table-scroll">
        <el-table :data="overview?.backups || []" border height="100%">
          <el-table-column prop="name" :label="t('tool.backupFile')" min-width="240" />
          <el-table-column :label="t('tool.size')" width="120">
            <template #default="{ row }">{{ formatSize(row.size) }}</template>
          </el-table-column>
          <el-table-column prop="create_time" :label="t('common.createTime')" min-width="170" />
          <el-table-column :label="t('common.actions')" width="210" fixed="right">
            <template #default="{ row }">
              <el-space class="table-actions">
                <el-button
                  v-if="authStore.hasPermission('admin:tool:backup-download')"
                  link
                  type="primary"
                  @click="handleDownload(row)"
                >
                  {{ t('tool.download') }}
                </el-button>
                <el-button
                  v-if="authStore.hasPermission('admin:tool:backup-restore')"
                  link
                  type="primary"
                  @click="handleRestore(row)"
                >
                  {{ t('tool.restore') }}
                </el-button>
                <el-button
                  v-if="authStore.hasPermission('admin:tool:backup-delete')"
                  link
                  type="danger"
                  @click="handleDelete(row)"
                >
                  {{ t('common.delete') }}
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
