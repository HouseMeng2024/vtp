<script setup lang="ts">
import { Close } from '@element-plus/icons-vue'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '../../stores/app'

const appStore = useAppStore()
const router = useRouter()
const route = useRoute()

function closeTag(path: string) {
  if (path === '/dashboard') {
    return
  }

  const currentIndex = appStore.visitedViews.findIndex((item) => item.path === path)
  appStore.removeVisitedView(path)

  if (route.path !== path) {
    return
  }

  const nextView = appStore.visitedViews[currentIndex] || appStore.visitedViews[currentIndex - 1]
  router.push(nextView?.path || '/dashboard')
}
</script>

<template>
  <div class="pure-tags">
    <el-scrollbar>
      <div class="pure-tags-list">
        <button
          v-for="tag in appStore.visitedViews"
          :key="tag.path"
          type="button"
          class="pure-tag"
          :class="{ active: route.path === tag.path }"
          @click="router.push(tag.path)"
        >
          <span>{{ tag.title }}</span>
          <el-icon v-if="tag.path !== '/dashboard'" @click.stop="closeTag(tag.path)">
            <Close />
          </el-icon>
        </button>
      </div>
    </el-scrollbar>
  </div>
</template>

<style scoped>
.pure-tags {
  height: 38px;
  padding: 4px 12px;
  border-bottom: 1px solid var(--el-border-color-light);
  background: var(--el-bg-color);
  z-index: 9;
}

.pure-tags-list {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: max-content;
}

.pure-tag {
  height: 28px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 10px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  background: var(--el-bg-color);
  color: var(--el-text-color-regular);
  font-size: 12px;
  white-space: nowrap;
  cursor: pointer;
}

.pure-tag.active {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: var(--el-color-white);
}
</style>
