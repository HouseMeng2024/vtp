<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElNotification } from 'element-plus'
import {
  ArrowDown,
  Bell,
  Expand,
  Fold,
  FullScreen,
  House,
  Refresh,
  Setting,
  SwitchButton,
} from '@element-plus/icons-vue'
import { useAppStore } from '../../stores/app'
import { useAuthStore } from '../../stores/auth'
import { fetchRecentNotices, readAllNotices, readNotice, type AdminNoticeRow } from '../../api/system'
import { normalizeAssetUrl } from '../../utils/asset'
import SidebarMenu from '../SidebarMenu.vue'

defineEmits<{
  openSetting: []
}>()

const appStore = useAppStore()
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const notices = ref<AdminNoticeRow[]>([])
const unreadCount = ref(0)
const popupStorageKey = 'vtp_popped_notice_ids'

const showHeaderLogo = computed(() => {
  return appStore.projectConfig.showLogo && appStore.projectConfig.layoutMode !== 'side'
})
const showCollapse = computed(() => appStore.projectConfig.layoutMode !== 'top')
const breadcrumbItems = computed(() => {
  return route.matched
    .filter((item) => item.meta?.title && item.name !== 'admin')
    .map((item) => ({
      path: item.path.startsWith('/') ? item.path : `/${item.path}`,
      title: String(item.meta.title),
    }))
})

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}

function openProfile() {
  router.push('/profile')
}

function refreshPage() {
  router.go(0)
}

async function toggleFullscreen() {
  if (!document.fullscreenElement) {
    await document.documentElement.requestFullscreen?.()
    return
  }

  await document.exitFullscreen?.()
}

async function loadNotices() {
  const data = await fetchRecentNotices()
  notices.value = data.items
  unreadCount.value = data.unread_count
  showPopupNotices(data.items)
}

function poppedNoticeIds() {
  try {
    return JSON.parse(localStorage.getItem(popupStorageKey) || '[]') as number[]
  } catch {
    return []
  }
}

function showPopupNotices(items: AdminNoticeRow[]) {
  const poppedIds = poppedNoticeIds()
  const nextIds = [...poppedIds]

  items
    .filter((notice) => notice.popup === 1 && notice.read === 0 && !poppedIds.includes(notice.id))
    .slice(0, 3)
    .forEach((notice) => {
      ElNotification({
        title: notice.title,
        message: notice.content || '你有一条新的消息通知',
        type: (notice.type === 'danger' ? 'error' : notice.type === 'primary' ? 'info' : notice.type) as 'success' | 'warning' | 'info' | 'error',
        position: 'bottom-right',
        duration: 4500,
      })
      nextIds.push(notice.id)
    })

  localStorage.setItem(popupStorageKey, JSON.stringify(Array.from(new Set(nextIds)).slice(-100)))
}

async function markRead(row: AdminNoticeRow) {
  await readNotice(row.id)
  await loadNotices()
}

async function markAllRead() {
  await readAllNotices()
  await loadNotices()
}

onMounted(() => {
  loadNotices().catch(() => undefined)
})
</script>

<template>
  <el-header class="pure-navbar">
    <div class="pure-navbar-left">
      <div v-if="showHeaderLogo" class="pure-header-logo">
        <img
          v-if="appStore.siteConfig.siteLogo"
          class="pure-logo-image"
          :src="normalizeAssetUrl(appStore.siteConfig.siteLogo)"
          alt="logo"
        />
        <div v-else class="pure-logo-mark">
          {{ appStore.siteConfig.adminTitle.slice(0, 1).toUpperCase() }}
        </div>
        <span>{{ appStore.siteConfig.adminTitle }}</span>
      </div>

      <el-tooltip v-if="showCollapse" content="折叠菜单" placement="bottom">
        <button class="pure-icon-btn" type="button" aria-label="折叠菜单" @click="appStore.toggleSidebar()">
          <el-icon>
            <Expand v-if="appStore.sidebarCollapsed" />
            <Fold v-else />
          </el-icon>
        </button>
      </el-tooltip>

      <el-breadcrumb
        v-if="appStore.projectConfig.showBreadcrumb"
        separator="/"
        class="pure-breadcrumb"
      >
        <el-breadcrumb-item :to="{ path: '/dashboard' }">
          <el-icon><House /></el-icon>
        </el-breadcrumb-item>
        <el-breadcrumb-item v-for="item in breadcrumbItems" :key="item.path">
          {{ item.title }}
        </el-breadcrumb-item>
      </el-breadcrumb>
    </div>

    <div v-if="appStore.projectConfig.layoutMode === 'top'" class="pure-navbar-center">
      <el-menu mode="horizontal" router :default-active="route.path" class="pure-top-menu">
        <el-menu-item index="/dashboard">
          <el-icon><House /></el-icon>
          <span>控制台</span>
        </el-menu-item>
        <SidebarMenu :menus="authStore.menus" />
      </el-menu>
    </div>

    <div class="pure-navbar-right">
      <el-tooltip content="刷新" placement="bottom">
        <button class="pure-icon-btn" type="button" aria-label="刷新" @click="refreshPage">
          <el-icon><Refresh /></el-icon>
        </button>
      </el-tooltip>
      <el-tooltip content="全屏" placement="bottom">
        <button class="pure-icon-btn" type="button" aria-label="全屏" @click="toggleFullscreen">
          <el-icon><FullScreen /></el-icon>
        </button>
      </el-tooltip>
      <el-popover placement="bottom-end" width="340" trigger="click" @show="loadNotices">
        <template #reference>
          <button class="pure-icon-btn" type="button" aria-label="消息">
            <el-badge class="pure-badge" :is-dot="unreadCount > 0">
              <el-icon><Bell /></el-icon>
            </el-badge>
          </button>
        </template>
        <div class="notice-panel">
          <div class="notice-header">
            <span>消息通知</span>
            <el-button v-if="unreadCount > 0" link type="primary" @click="markAllRead">全部已读</el-button>
          </div>
          <el-empty v-if="notices.length === 0" description="暂无消息" />
          <div v-for="notice in notices" v-else :key="notice.id" class="notice-item">
            <div class="notice-title">
              <el-tag :type="notice.type || 'info'" size="small">{{ notice.read === 0 ? '未读' : '已读' }}</el-tag>
              <span>{{ notice.title }}</span>
            </div>
            <div class="notice-content">{{ notice.content }}</div>
            <div class="notice-footer">
              <span>{{ notice.create_time }}</span>
              <el-button v-if="notice.read === 0" link type="primary" @click="markRead(notice)">标记已读</el-button>
            </div>
          </div>
        </div>
      </el-popover>
      <el-tooltip content="项目配置" placement="bottom">
        <button class="pure-icon-btn" type="button" aria-label="项目配置" @click="$emit('openSetting')">
          <el-icon><Setting /></el-icon>
        </button>
      </el-tooltip>

      <el-dropdown trigger="click">
        <button class="pure-user" type="button">
          <el-avatar v-if="authStore.user?.avatar" :size="28" :src="normalizeAssetUrl(authStore.user.avatar)" />
          <el-avatar v-else :size="28">
            {{ (authStore.user?.nickname || authStore.user?.username || 'A').slice(0, 1) }}
          </el-avatar>
          <span>{{ authStore.user?.nickname || authStore.user?.username }}</span>
          <el-icon><ArrowDown /></el-icon>
        </button>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item @click="openProfile">个人中心</el-dropdown-item>
            <el-dropdown-item divided @click="handleLogout">
              <el-icon><SwitchButton /></el-icon>
              退出登录
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </el-header>
</template>

<style scoped>
.pure-navbar {
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px 0 8px;
  border-bottom: 1px solid var(--el-border-color-light);
  background: var(--el-bg-color);
  box-shadow: var(--el-box-shadow-lighter);
  z-index: 10;
}

.pure-navbar-left,
.pure-navbar-center,
.pure-navbar-right,
.pure-user,
.pure-header-logo {
  display: flex;
  align-items: center;
}

.pure-navbar-left {
  gap: 12px;
  min-width: 0;
}

.pure-navbar-right {
  gap: 4px;
}

.pure-navbar-center {
  flex: 1;
  min-width: 0;
  padding: 0 16px;
}

.pure-header-logo {
  height: 48px;
  gap: 10px;
  padding-right: 12px;
  color: var(--el-text-color-primary);
  font-size: 15px;
  font-weight: 700;
  white-space: nowrap;
}

.pure-logo-mark {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  border-radius: 8px;
  background: var(--primary-color);
  color: var(--el-color-white);
  font-size: 18px;
  font-weight: 800;
}

.pure-logo-image {
  width: 30px;
  height: 30px;
  object-fit: contain;
  flex: 0 0 auto;
}

.pure-top-menu {
  width: 100%;
  height: 48px;
  border-bottom: 0;
  background: transparent;
}

.pure-top-menu :deep(.el-menu-item),
.pure-top-menu :deep(.el-sub-menu__title) {
  height: 48px;
}

.pure-icon-btn,
.pure-user {
  border: 0;
  background: transparent;
  color: var(--el-text-color-primary);
  cursor: pointer;
}

.pure-icon-btn {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  font-size: 18px;
}

.pure-icon-btn:hover,
.pure-user:hover {
  background: var(--el-fill-color-light);
}

.pure-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.pure-badge :deep(.el-badge__content.is-fixed.is-dot) {
  top: 3px;
  right: 4px;
}

.notice-panel {
  max-height: 420px;
  overflow: auto;
}

.notice-header,
.notice-footer,
.notice-title {
  display: flex;
  align-items: center;
}

.notice-header {
  justify-content: space-between;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--el-border-color-light);
  font-weight: 700;
}

.notice-item {
  padding: 10px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.notice-title {
  gap: 8px;
  font-weight: 600;
}

.notice-content {
  margin-top: 6px;
  color: var(--el-text-color-regular);
  font-size: 13px;
  line-height: 1.5;
}

.notice-footer {
  justify-content: space-between;
  margin-top: 6px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.pure-breadcrumb :deep(.el-breadcrumb__item) {
  display: inline-flex;
  align-items: center;
}

.pure-user {
  height: 36px;
  gap: 8px;
  padding: 0 8px;
  border-radius: 6px;
  font-size: 14px;
}

@media (max-width: 768px) {
  .pure-breadcrumb,
  .pure-navbar-right .pure-icon-btn {
    display: none;
  }
}
</style>
