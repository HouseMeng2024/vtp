<script setup lang="ts">
import { computed } from 'vue'
import { House } from '@element-plus/icons-vue'
import { useRoute } from 'vue-router'
import { useAppStore } from '../../stores/app'
import { useAuthStore } from '../../stores/auth'
import { normalizeAssetUrl } from '../../utils/asset'
import SidebarMenu from '../SidebarMenu.vue'

const appStore = useAppStore()
const authStore = useAuthStore()
const route = useRoute()
const sideWidth = computed(() => (appStore.sidebarCollapsed ? '64px' : '220px'))
</script>

<template>
  <el-aside :width="sideWidth" class="pure-sidebar">
    <div
      v-if="appStore.projectConfig.showLogo && appStore.projectConfig.layoutMode === 'side'"
      class="pure-logo"
      :class="{ collapsed: appStore.sidebarCollapsed }"
    >
      <img
        v-if="appStore.siteConfig.siteLogo"
        class="pure-logo-image"
        :src="normalizeAssetUrl(appStore.siteConfig.siteLogo)"
        alt="logo"
      />
      <div v-else class="pure-logo-mark">
        {{ appStore.siteConfig.adminTitle.slice(0, 1).toUpperCase() }}
      </div>
      <span v-show="!appStore.sidebarCollapsed">{{ appStore.siteConfig.adminTitle }}</span>
    </div>

    <el-menu
      router
      unique-opened
      :collapse="appStore.sidebarCollapsed"
      :default-active="route.path"
    >
      <el-menu-item index="/dashboard">
        <el-icon><House /></el-icon>
        <span>控制台</span>
      </el-menu-item>
      <SidebarMenu :menus="authStore.menus" />
    </el-menu>
  </el-aside>
</template>

<style scoped>
.pure-sidebar {
  overflow: hidden;
  background: var(--el-bg-color);
  border-right: 1px solid var(--el-border-color-light);
  box-shadow: var(--el-box-shadow-light);
  transition: width 0.25s ease;
  z-index: 20;
}

.pure-logo {
  height: 56px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 16px;
  color: var(--el-text-color-primary);
  font-size: 16px;
  font-weight: 700;
  white-space: nowrap;
  border-bottom: 1px solid var(--el-border-color-light);
}

.pure-logo.collapsed {
  justify-content: center;
  padding: 0;
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

.el-menu {
  border-right: 0;
}

.pure-sidebar :deep(.el-menu-item),
.pure-sidebar :deep(.el-sub-menu__title) {
  height: 48px;
}

.pure-sidebar :deep(.el-menu-item.is-active) {
  color: var(--el-color-primary);
}
</style>
