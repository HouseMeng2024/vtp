<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const status = computed(() => String(route.meta.status || '404'))
const title = computed(() => status.value === '403' ? '无权限访问' : '页面不存在')
const description = computed(() => status.value === '403'
  ? '当前账号没有访问该页面或操作的权限。'
  : '访问的页面不存在，或已经被移动。')

function goHome() {
  router.push('/dashboard')
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="error-page">
    <div class="error-code">{{ status }}</div>
    <h1>{{ title }}</h1>
    <p>{{ description }}</p>
    <el-space>
      <el-button type="primary" @click="goHome">返回控制台</el-button>
      <el-button @click="goBack">返回上一页</el-button>
    </el-space>
  </div>
</template>

<style scoped>
.error-page {
  min-height: calc(100vh - 120px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.error-code {
  color: var(--el-color-primary);
  font-size: 96px;
  font-weight: 800;
  line-height: 1;
}

.error-page h1 {
  margin: 18px 0 8px;
  color: var(--el-text-color-primary);
  font-size: 24px;
}

.error-page p {
  margin: 0 0 24px;
  color: var(--el-text-color-secondary);
  font-size: 14px;
}
</style>
