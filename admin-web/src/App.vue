<script setup lang="ts">
import { computed, watchEffect } from 'vue'
import en from 'element-plus/es/locale/lang/en'
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import 'dayjs/locale/zh-cn'
import { useAppStore } from './stores/app'

const appStore = useAppStore()
const elementLocale = computed(() => (appStore.locale === 'zh-cn' ? zhCn : en))

watchEffect(() => {
  document.documentElement.lang = appStore.locale
})
</script>

<template>
  <el-config-provider :locale="elementLocale">
    <RouterView />
  </el-config-provider>
</template>
