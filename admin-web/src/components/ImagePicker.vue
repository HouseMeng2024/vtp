<script setup lang="ts">
import { computed, ref } from 'vue'
import { Delete, Plus, Refresh, ZoomIn } from '@element-plus/icons-vue'
import type { UploadFileRow } from '../api/file'
import FileSelector from './FileSelector.vue'
import { normalizeAssetUrl } from '../utils/asset'

const props = withDefaults(defineProps<{
  modelValue: string
  scene?: string
  width?: number | string
  height?: number | string
  fit?: 'fill' | 'contain' | 'cover' | 'none' | 'scale-down'
  disabled?: boolean
}>(), {
  scene: 'common',
  width: 96,
  height: 96,
  fit: 'contain',
  disabled: false,
})
const emit = defineEmits<{
  'update:modelValue': [value: string]
  change: [value: string]
}>()

const selectorVisible = ref(false)
const previewVisible = ref(false)
const previewUrls = computed(() => props.modelValue ? [normalizeAssetUrl(props.modelValue)] : [])
const pickerStyle = computed(() => ({
  width: normalizeSize(props.width),
  height: normalizeSize(props.height),
}))

function normalizeSize(value: number | string) {
  return typeof value === 'number' ? `${value}px` : value
}

function openSelector() {
  if (props.disabled) {
    return
  }

  selectorVisible.value = true
}

function handleSelected(files: UploadFileRow[]) {
  const value = files[0]?.url || ''
  emit('update:modelValue', value)
  emit('change', value)
}

function openPreview() {
  if (previewUrls.value.length === 0) {
    return
  }

  previewVisible.value = true
}

function clearImage() {
  emit('update:modelValue', '')
  emit('change', '')
}
</script>

<template>
  <div class="image-picker-control">
    <div v-if="modelValue" class="image-item" :style="pickerStyle">
      <button class="image-preview" type="button" title="预览图片" @click="openPreview">
        <el-image class="picked-image" :src="normalizeAssetUrl(modelValue)" :fit="fit" />
      </button>
      <div class="image-actions">
        <el-tooltip content="预览" placement="top">
          <button class="image-action" type="button" aria-label="预览" @click="openPreview">
            <el-icon><ZoomIn /></el-icon>
          </button>
        </el-tooltip>
        <el-tooltip v-if="!disabled" content="更换" placement="top">
          <button class="image-action" type="button" aria-label="更换" @click="openSelector">
            <el-icon><Refresh /></el-icon>
          </button>
        </el-tooltip>
        <el-tooltip v-if="!disabled" content="移除" placement="top">
          <button class="image-action danger" type="button" aria-label="移除" @click="clearImage">
            <el-icon><Delete /></el-icon>
          </button>
        </el-tooltip>
      </div>
    </div>
    <button
      v-else-if="!disabled"
      class="image-add"
      type="button"
      aria-label="选择图片"
      :style="pickerStyle"
      @click="openSelector"
    >
      <el-icon><Plus /></el-icon>
    </button>

    <FileSelector
      v-model="selectorVisible"
      accept-type="image"
      :scene="scene"
      :current-url="modelValue"
      @select="handleSelected"
    />
    <el-image-viewer
      v-if="previewVisible"
      :url-list="previewUrls"
      @close="previewVisible = false"
    />
  </div>
</template>

<style scoped>
.image-picker-control {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.image-item,
.image-add {
  position: relative;
}

.image-preview {
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  overflow: hidden;
  cursor: zoom-in;
  background: var(--el-fill-color-light);
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
}

.image-add {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  color: var(--el-text-color-secondary);
  cursor: pointer;
  background: var(--el-fill-color-lighter);
  border: 1px dashed var(--el-border-color);
  border-radius: 4px;
}

.image-add:hover {
  color: var(--el-color-primary);
  border-color: var(--el-color-primary);
}

.image-add .el-icon {
  font-size: 24px;
}

.picked-image {
  display: block;
  width: 100%;
  height: 100%;
}

.image-actions {
  position: absolute;
  top: 4px;
  right: 4px;
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.image-item:hover .image-actions,
.image-item:focus-within .image-actions {
  opacity: 1;
}

.image-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  padding: 0;
  color: var(--el-text-color-primary);
  cursor: pointer;
  background: var(--el-bg-color-overlay);
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
}

.image-action:hover {
  color: var(--el-color-primary);
  border-color: var(--el-color-primary-light-5);
}

.image-action.danger:hover {
  color: var(--el-color-danger);
  border-color: var(--el-color-danger-light-5);
}
</style>
