<script setup lang="ts">
import {
  Grid,
  Menu as MenuIcon,
  Setting,
  User,
  UserFilled,
} from '@element-plus/icons-vue'
import type { Component } from 'vue'
import type { AdminMenu } from '../types/auth'

defineProps<{
  menus: AdminMenu[]
}>()

const iconMap: Record<string, Component> = {
  Grid,
  Menu: MenuIcon,
  Setting,
  User,
  UserFilled,
}

function resolveIcon(icon: string) {
  return iconMap[icon] || MenuIcon
}
</script>

<template>
  <template v-for="menu in menus" :key="menu.id">
    <el-sub-menu v-if="menu.children.length" :index="menu.path || String(menu.id)">
      <template #title>
        <el-icon><component :is="resolveIcon(menu.icon)" /></el-icon>
        <span>{{ menu.title }}</span>
      </template>
      <SidebarMenu :menus="menu.children" />
    </el-sub-menu>

    <el-menu-item v-else :index="menu.path">
      <el-icon><component :is="resolveIcon(menu.icon)" /></el-icon>
      <span>{{ menu.title }}</span>
    </el-menu-item>
  </template>
</template>
