<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { clearSystemCache, fetchSystemTools, type SystemToolOverview } from '../../../api/systemTool'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const loading = ref(false)
const clearingType = ref('')
const overview = ref<SystemToolOverview | null>(null)

async function loadData() {
  loading.value = true
  try {
    overview.value = await fetchSystemTools()
  } finally {
    loading.value = false
  }
}

async function handleClearCache(type: string, label: string, confirm = '') {
  if (confirm) {
    await ElMessageBox.prompt(`确定清理「${label}」吗？请输入 ${confirm} 确认。`, '清理确认', {
      inputPattern: new RegExp(`^${confirm}$`),
      inputErrorMessage: `请输入 ${confirm}`,
      type: 'warning',
    })
  } else {
    await ElMessageBox.confirm(`确定清理「${label}」吗？`, '清理确认', {
      type: 'warning',
    })
  }

  clearingType.value = type
  try {
    await clearSystemCache(type)
    ElMessage.success('缓存已清理')
    loadData()
  } finally {
    clearingType.value = ''
  }
}

onMounted(loadData)
</script>

<template>
  <el-card v-loading="loading" class="page-card cache-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">缓存管理</div>
      </div>
    </template>

    <el-descriptions :column="1" border>
      <el-descriptions-item label="缓存驱动">{{ overview?.cache.driver || '-' }}</el-descriptions-item>
      <el-descriptions-item label="缓存目录">{{ overview?.cache.path || '-' }}</el-descriptions-item>
      <el-descriptions-item label="模板临时目录">{{ overview?.cache.temp_path || '-' }}</el-descriptions-item>
      <el-descriptions-item label="前台内容缓存数">{{ overview?.cache.index_content_count ?? 0 }}</el-descriptions-item>
    </el-descriptions>

    <div class="cache-actions">
      <div
        v-for="item in overview?.cache.types || []"
        :key="item.value"
        class="cache-action"
      >
        <div>
          <div class="cache-action-title">{{ item.label }}</div>
          <div class="cache-action-desc">{{ item.description }}</div>
        </div>
        <el-button
          v-if="authStore.hasPermission('admin:cache:clear') || authStore.hasPermission('admin:tool:cache-clear')"
          :type="item.value === 'all' ? 'primary' : 'default'"
          :loading="clearingType === item.value"
          @click="handleClearCache(item.value, item.label, item.confirm)"
        >
          清理
        </el-button>
      </div>
    </div>
  </el-card>
</template>

<style scoped>
.cache-page-card {
  min-height: 0;
}

.cache-actions {
  display: grid;
  gap: 10px;
  margin-top: 14px;
}

.cache-action {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 14px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 6px;
  background: var(--el-fill-color-lighter);
}

.cache-action-title {
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.cache-action-desc {
  margin-top: 4px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}
</style>
