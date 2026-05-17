<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  Connection,
  DataLine,
  Files,
  Menu as MenuIcon,
  Refresh,
  User,
} from '@element-plus/icons-vue'
import request from '../../utils/request'
import { useAuthStore } from '../../stores/auth'

interface DashboardSummary {
  cards: {
    users: number
    roles: number
    menus: number
    files: number
    today_logins: number
    today_operates: number
  }
  login_trend: Array<{ date: string; total: number }>
  file_types: Array<{ extension: string; total: number }>
  recent_logins: Array<{ id: number; username: string; ip: string; status: number; message: string; create_time: string }>
  recent_operates: Array<{ id: number; username: string; method: string; path: string; status_code: number; duration_ms: string; create_time: string }>
  server: {
    app: string
    time: string
  }
}

const loading = ref(false)
const summary = ref<DashboardSummary | null>(null)
const authStore = useAuthStore()
const statCards = computed(() => [
  { label: '管理员', value: summary.value?.cards.users || 0, type: 'primary', icon: User },
  { label: '角色数量', value: summary.value?.cards.roles || 0, type: 'success', icon: DataLine },
  { label: '菜单节点', value: summary.value?.cards.menus || 0, type: 'warning', icon: MenuIcon },
  { label: '文件数量', value: summary.value?.cards.files || 0, type: 'danger', icon: Files },
  { label: '今日登录', value: summary.value?.cards.today_logins || 0, type: 'primary', icon: Connection },
  { label: '今日操作', value: summary.value?.cards.today_operates || 0, type: 'success', icon: Refresh },
])
const maxLoginTotal = computed(() => Math.max(...(summary.value?.login_trend.map((item) => item.total) || [1]), 1))

async function loadDashboard() {
  loading.value = true

  try {
    const response = await request.get<DashboardSummary>('/index/dashboard')
    summary.value = response.data
  } finally {
    loading.value = false
  }
}

function trendHeight(total: number) {
  return `${Math.max(10, Math.round((total / maxLoginTotal.value) * 100))}%`
}

onMounted(loadDashboard)
</script>

<template>
  <div class="dashboard-page">
    <div class="welcome-panel">
      <div>
        <div class="welcome-title">
          欢迎回来，{{ authStore.user?.nickname || authStore.user?.username || '管理员' }}
        </div>
        <div class="welcome-subtitle">控制台展示当前后台的真实运行数据。</div>
      </div>
      <el-button type="primary" :icon="Refresh" :loading="loading" @click="loadDashboard">
        刷新
      </el-button>
    </div>

    <el-row :gutter="16">
      <el-col v-for="item in statCards" :key="item.label" :xs="12" :sm="8" :lg="4">
        <el-card class="stat-card" shadow="never">
          <div class="stat-icon" :class="`is-${item.type}`">
            <el-icon><component :is="item.icon" /></el-icon>
          </div>
          <div>
            <div class="stat-label">{{ item.label }}</div>
            <div class="stat-value">{{ item.value }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16" class="dashboard-grid">
      <el-col :xs="24" :lg="14">
        <el-card shadow="never" class="panel-card">
          <template #header>
            <div class="card-header">
              <span>近 7 天登录趋势</span>
              <el-tag type="success" effect="plain">实时</el-tag>
            </div>
          </template>

          <div class="trend-chart">
            <div v-for="item in summary?.login_trend || []" :key="item.date" class="trend-item">
              <div class="trend-value">{{ item.total }}</div>
              <div class="trend-bar-wrap">
                <div class="trend-bar" :style="{ height: trendHeight(item.total) }" />
              </div>
              <div class="trend-date">{{ item.date.slice(5) }}</div>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="10">
        <el-card shadow="never" class="panel-card">
          <template #header>
            <div class="card-header">
              <span>文件类型统计</span>
              <el-icon><Files /></el-icon>
            </div>
          </template>

          <div class="file-types">
            <div v-for="item in summary?.file_types || []" :key="item.extension" class="file-type-row">
              <span>{{ item.extension || 'unknown' }}</span>
              <el-progress :percentage="Math.min(100, item.total * 5)" :show-text="false" />
              <strong>{{ item.total }}</strong>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16" class="dashboard-grid">
      <el-col :xs="24" :lg="12">
        <el-card shadow="never" class="panel-card">
          <template #header>
            <div class="card-header">
              <span>最近登录</span>
              <el-tag effect="plain">{{ summary?.server.time || '-' }}</el-tag>
            </div>
          </template>

          <el-table :data="summary?.recent_logins || []" border>
            <el-table-column prop="username" label="账号" min-width="120" />
            <el-table-column prop="ip" label="IP" min-width="130" />
            <el-table-column label="状态" width="90">
              <template #default="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'danger'">
                  {{ row.status === 1 ? '成功' : '失败' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="create_time" label="时间" min-width="170" />
          </el-table>
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="12">
        <el-card shadow="never" class="panel-card">
          <template #header>
            <div class="card-header">
              <span>最近操作</span>
              <el-tag effect="plain">写操作</el-tag>
            </div>
          </template>

          <el-table :data="summary?.recent_operates || []" border>
            <el-table-column prop="username" label="账号" min-width="120" />
            <el-table-column prop="method" label="方法" width="90" />
            <el-table-column prop="path" label="路径" min-width="180" show-overflow-tooltip />
            <el-table-column prop="status_code" label="状态" width="90" />
            <el-table-column prop="duration_ms" label="耗时" width="90" />
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.welcome-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 92px;
  padding: 20px 24px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  background: var(--el-bg-color);
}

.welcome-title {
  color: var(--el-text-color-primary);
  font-size: 20px;
  font-weight: 700;
}

.welcome-subtitle {
  margin-top: 8px;
  color: var(--el-text-color-regular);
  font-size: 13px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stat-card {
  margin-bottom: 16px;
}

.stat-card :deep(.el-card__body) {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
}

.stat-icon {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  border-radius: 8px;
  color: var(--el-color-white);
  font-size: 21px;
}

.stat-icon.is-primary {
  background: var(--el-color-primary);
}

.stat-icon.is-success {
  background: var(--el-color-success);
}

.stat-icon.is-warning {
  background: var(--el-color-warning);
}

.stat-icon.is-danger {
  background: var(--el-color-danger);
}

.stat-label {
  color: var(--el-text-color-regular);
  font-size: 13px;
}

.stat-value {
  margin-top: 5px;
  color: var(--el-text-color-primary);
  font-size: 22px;
  font-weight: 700;
}

.dashboard-grid .el-col {
  margin-bottom: 16px;
}

.panel-card {
  min-height: 300px;
}

.trend-chart {
  height: 236px;
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 14px;
  align-items: end;
  padding-top: 12px;
}

.trend-item {
  height: 100%;
  display: grid;
  grid-template-rows: 24px 1fr 22px;
  gap: 8px;
  text-align: center;
}

.trend-value,
.trend-date {
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.trend-bar-wrap {
  display: flex;
  align-items: end;
  justify-content: center;
  border-bottom: 1px solid var(--el-border-color-light);
}

.trend-bar {
  width: 34px;
  border-radius: 5px 5px 0 0;
  background: var(--el-color-primary);
}

.file-types {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-top: 8px;
}

.file-type-row {
  display: grid;
  grid-template-columns: 64px 1fr 42px;
  gap: 10px;
  align-items: center;
  color: var(--el-text-color-regular);
  font-size: 13px;
}

@media (max-width: 768px) {
  .welcome-panel {
    align-items: flex-start;
    flex-direction: column;
    gap: 16px;
  }
}
</style>
