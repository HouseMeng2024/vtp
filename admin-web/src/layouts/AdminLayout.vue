<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAppStore } from '../stores/app'
import LayoutHeader from '../components/layout/LayoutHeader.vue'
import LayoutSidebar from '../components/layout/LayoutSidebar.vue'
import LayoutTags from '../components/layout/LayoutTags.vue'
import ProjectSetting from '../components/layout/ProjectSetting.vue'

const appStore = useAppStore()
const route = useRoute()
const settingVisible = ref(false)

const showSideMenu = computed(() => appStore.projectConfig.layoutMode !== 'top')

watch(
  () => route.fullPath,
  () => {
    const title = route.meta?.title ? String(route.meta.title) : ''

    if (!title || route.path === '/login' || title === '加载中') {
      return
    }

    appStore.addVisitedView({
      path: route.path,
      title,
    })
  },
  { immediate: true },
)

watch(
  () => appStore.projectConfig,
  () => appStore.applyProjectConfig(),
  { deep: true, immediate: true },
)
</script>

<template>
  <el-container
    class="pure-layout"
    :class="{
      'is-dark': appStore.projectConfig.darkMode,
      'is-fixed-header': appStore.projectConfig.fixedHeader,
      'is-compact': appStore.projectConfig.compactMode,
      [`is-${appStore.projectConfig.layoutMode}`]: true,
    }"
  >
    <LayoutHeader
      v-if="appStore.projectConfig.layoutMode === 'mix'"
      class="pure-navbar-mix"
      @open-setting="settingVisible = true"
    />

    <el-container class="pure-body">
      <LayoutSidebar v-if="showSideMenu" />

      <el-container class="pure-container">
        <LayoutHeader
          v-if="appStore.projectConfig.layoutMode !== 'mix'"
          @open-setting="settingVisible = true"
        />

        <LayoutTags v-if="appStore.projectConfig.showTags" />

        <el-main class="pure-main" :class="{ 'no-tags': !appStore.projectConfig.showTags }">
          <RouterView v-slot="{ Component }">
            <Transition name="fade-slide" mode="out-in">
              <component :is="Component" />
            </Transition>
          </RouterView>
        </el-main>
      </el-container>
    </el-container>

    <ProjectSetting v-model="settingVisible" />
  </el-container>
</template>

<style scoped>
.pure-layout {
  color: var(--el-text-color-primary);
  height: 100vh;
  background: var(--el-bg-color-page);
  overflow: hidden;
}

.pure-body {
  width: 100%;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

.pure-container {
  width: 0;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  min-height: 0;
  overflow: hidden;
}

.pure-container :deep(.pure-navbar),
.pure-container :deep(.pure-tags) {
  flex: 0 0 auto;
}

.pure-layout.is-fixed-header :deep(.pure-navbar) {
  position: sticky;
  top: 0;
}

.pure-layout.is-fixed-header :deep(.pure-tags) {
  position: sticky;
  top: 48px;
}

.pure-layout.is-mix {
  flex-direction: column;
}

.pure-layout.is-mix .pure-body {
  height: calc(100vh - 48px);
  min-height: 0;
}

.pure-layout.is-mix :deep(.pure-sidebar) {
  height: calc(100vh - 48px);
}

.pure-layout.is-mix.is-fixed-header .pure-navbar-mix {
  position: sticky;
  top: 0;
}

.pure-main {
  flex: 1;
  min-height: 0;
  padding: 16px;
  background: var(--el-bg-color-page);
  overflow: auto;
}

.pure-main.no-tags {
  min-height: 0;
}

.pure-layout.is-compact .pure-main {
  padding: 10px;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition:
    opacity 0.18s ease,
    transform 0.18s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@media (max-width: 768px) {
  .pure-main {
    padding: 12px;
  }
}
</style>
