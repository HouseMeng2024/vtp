<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { LayoutMode, ProjectConfig } from '../../stores/app'
import { useAppStore } from '../../stores/app'

type BooleanProjectConfigKey = Exclude<keyof ProjectConfig, 'themeColor' | 'layoutMode'>

defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const appStore = useAppStore()
const { t } = useI18n()
const themeColors = ['#409eff', '#1f75cb', '#00a870', '#e6a23c', '#f56c6c', '#7c3aed']
const layoutModes = computed<Array<{ label: string; value: LayoutMode }>>(() => [
  { label: t('projectSetting.sideMenu'), value: 'side' },
  { label: t('projectSetting.topMenu'), value: 'top' },
  { label: t('projectSetting.mixedMenu'), value: 'mix' },
])

function setBooleanConfig(key: BooleanProjectConfigKey, value: string | number | boolean) {
  appStore.setProjectConfig({
    [key]: Boolean(value),
  })
}
</script>

<template>
  <el-drawer
    :model-value="modelValue"
    :title="t('common.projectSetting')"
    size="320px"
    class="project-drawer"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="setting-body">
      <div class="setting-section">
        <div class="setting-title">{{ t('projectSetting.menuLayout') }}</div>
        <div class="layout-previews">
          <button
            v-for="layout in layoutModes"
            :key="layout.value"
            type="button"
            class="layout-preview"
            :class="[`is-${layout.value}`, { active: appStore.projectConfig.layoutMode === layout.value }]"
            @click="appStore.setProjectConfig({ layoutMode: layout.value })"
          >
            <span class="preview-side" />
            <span class="preview-top" />
            <span class="preview-main" />
            <span class="preview-label">{{ layout.label }}</span>
          </button>
        </div>
      </div>

      <div class="setting-section">
        <div class="setting-title">{{ t('projectSetting.systemTheme') }}</div>
        <div class="theme-swatches">
          <button
            v-for="color in themeColors"
            :key="color"
            type="button"
            class="theme-swatch"
            :class="{ active: appStore.projectConfig.themeColor === color }"
            :style="{ backgroundColor: color }"
            @click="appStore.setProjectConfig({ themeColor: color })"
          />
        </div>
      </div>

      <div class="setting-section">
        <div class="setting-title">{{ t('projectSetting.displaySettings') }}</div>
        <div class="setting-row">
          <span>{{ t('projectSetting.darkMode') }}</span>
          <el-switch
            :model-value="appStore.projectConfig.darkMode"
            @change="setBooleanConfig('darkMode', $event)"
          />
        </div>
        <div class="setting-row">
          <span>{{ t('projectSetting.grayMode') }}</span>
          <el-switch
            :model-value="appStore.projectConfig.grayMode"
            @change="setBooleanConfig('grayMode', $event)"
          />
        </div>
        <div class="setting-row">
          <span>{{ t('projectSetting.weakMode') }}</span>
          <el-switch
            :model-value="appStore.projectConfig.weakMode"
            @change="setBooleanConfig('weakMode', $event)"
          />
        </div>
        <div class="setting-row">
          <span>{{ t('projectSetting.compactMode') }}</span>
          <el-switch
            :model-value="appStore.projectConfig.compactMode"
            @change="setBooleanConfig('compactMode', $event)"
          />
        </div>
      </div>

      <div class="setting-section">
        <div class="setting-title">{{ t('projectSetting.navigation') }}</div>
        <div class="setting-row">
          <span>{{ t('projectSetting.collapseMenu') }}</span>
          <el-switch v-model="appStore.sidebarCollapsed" />
        </div>
        <div class="setting-row">
          <span>{{ t('projectSetting.fixedHeader') }}</span>
          <el-switch
            :model-value="appStore.projectConfig.fixedHeader"
            @change="setBooleanConfig('fixedHeader', $event)"
          />
        </div>
        <div class="setting-row">
          <span>{{ t('projectSetting.showLogo') }}</span>
          <el-switch
            :model-value="appStore.projectConfig.showLogo"
            @change="setBooleanConfig('showLogo', $event)"
          />
        </div>
        <div class="setting-row">
          <span>{{ t('projectSetting.showTags') }}</span>
          <el-switch
            :model-value="appStore.projectConfig.showTags"
            @change="setBooleanConfig('showTags', $event)"
          />
        </div>
        <div class="setting-row">
          <span>{{ t('projectSetting.showBreadcrumb') }}</span>
          <el-switch
            :model-value="appStore.projectConfig.showBreadcrumb"
            @change="setBooleanConfig('showBreadcrumb', $event)"
          />
        </div>
      </div>

      <div class="setting-section">
        <div class="setting-row">
          <span>{{ t('projectSetting.layoutStyle') }}</span>
          <el-tag effect="plain">{{ t('projectSetting.classicSidebar') }}</el-tag>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="setting-footer">
        <el-button class="reset-btn" @click="appStore.resetProjectConfig()">{{ t('common.restoreDefaults') }}</el-button>
      </div>
    </template>
  </el-drawer>
</template>

<style scoped>
.setting-body {
  padding-bottom: 8px;
}

.setting-section {
  padding-bottom: 18px;
}

.setting-title {
  margin-bottom: 10px;
  color: var(--el-text-color-primary);
  font-size: 14px;
  font-weight: 700;
}

.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 0;
  border-bottom: 1px solid var(--el-border-color-light);
  color: var(--el-text-color-regular);
  font-size: 14px;
}

.layout-previews {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.layout-preview {
  position: relative;
  height: 68px;
  overflow: hidden;
  border: 2px solid var(--el-border-color);
  border-radius: 6px;
  background: var(--el-bg-color);
  cursor: pointer;
}

.layout-preview.active {
  border-color: var(--primary-color);
}

.preview-side,
.preview-top,
.preview-main {
  position: absolute;
  display: block;
}

.preview-side {
  left: 0;
  top: 0;
  width: 20px;
  height: 100%;
  background: var(--el-fill-color-darker);
}

.preview-top {
  left: 20px;
  top: 0;
  right: 0;
  height: 14px;
  background: var(--el-bg-color);
}

.preview-main {
  left: 20px;
  top: 14px;
  right: 0;
  bottom: 0;
  background: var(--el-bg-color-page);
}

.layout-preview.is-top .preview-side {
  display: none;
}

.layout-preview.is-top .preview-top {
  left: 0;
  height: 18px;
}

.layout-preview.is-top .preview-main {
  left: 0;
  top: 18px;
}

.layout-preview.is-mix .preview-side {
  top: 14px;
  width: 18px;
}

.layout-preview.is-mix .preview-top {
  left: 0;
}

.layout-preview.is-mix .preview-main {
  left: 18px;
}

.preview-label {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 3px;
  color: var(--el-text-color-secondary);
  font-size: 11px;
  text-align: center;
  pointer-events: none;
}

.theme-swatches {
  display: flex;
  align-items: center;
  gap: 10px;
}

.theme-swatch {
  width: 26px;
  height: 26px;
  border: 2px solid transparent;
  border-radius: 50%;
  cursor: pointer;
}

.theme-swatch.active {
  border-color: var(--el-text-color-primary);
  box-shadow: 0 0 0 2px var(--el-bg-color) inset;
}

.setting-footer {
  display: flex;
  padding-top: 12px;
  border-top: 1px solid var(--el-border-color-light);
}

.reset-btn {
  width: 100%;
}
</style>
