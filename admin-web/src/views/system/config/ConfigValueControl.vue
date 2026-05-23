<script setup lang="ts">
import { computed, ref } from 'vue'
import { Delete, Plus, Refresh, View, ZoomIn } from '@element-plus/icons-vue'
import type { SystemConfigItem } from '../../../api/config'
import type { UploadFileRow } from '../../../api/file'
import FileSelector from '../../../components/FileSelector.vue'
import RichEditor from '../../../components/RichEditor.vue'
import { normalizeAssetUrl } from '../../../utils/asset'

type ConfigValue = string | number | Array<string | number>

const props = withDefaults(defineProps<{
  item: SystemConfigItem
  modelValue: ConfigValue
  disabled?: boolean
}>(), {
  disabled: false,
})
const emit = defineEmits<{
  'update:modelValue': [value: ConfigValue]
}>()

const selectorVisible = ref(false)
const selectorType = ref<'image' | 'file'>('image')
const selectorMultiple = ref(false)
const previewVisible = ref(false)
const previewUrls = ref<string[]>([])
const previewIndex = ref(0)
const colorPredefine = [
  '#409EFF',
  '#1F75CB',
  '#0EA5E9',
  '#67C23A',
  '#00A870',
  '#14B8A6',
  '#E6A23C',
  '#F59E0B',
  '#F56C6C',
  '#EF4444',
  '#FFFFFF',
  '#F3F4F6',
  '#909399',
  '#6B7280',
  '#111827',
  '#7C3AED',
  '#A855F7',
  '#EC4899',
  '#F97316',
  '#84CC16',
]
const colorPredefineClass = 'config-color-predefine'
const value = computed({
  get: () => props.modelValue,
  set: (nextValue) => emit('update:modelValue', nextValue),
})
const arrayValue = computed<Array<string | number>>({
  get: () => Array.isArray(props.modelValue) ? props.modelValue : parseArrayValue(String(props.modelValue || '')),
  set: (nextValue) => emit('update:modelValue', nextValue),
})
const singleValue = computed(() => Array.isArray(props.modelValue) ? '' : String(props.modelValue || ''))
const options = computed(() => parseOptions(props.item.options))

function parseArrayValue(raw: string): Array<string | number> {
  if (!raw) {
    return []
  }

  try {
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return raw.split(',').map((item) => item.trim()).filter(Boolean)
  }
}

function parseOptions(raw: string) {
  if (!raw) {
    return []
  }

  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) {
      return parsed.map((item) => ({
        label: String(item.label ?? item.value ?? ''),
        value: item.value ?? item.label ?? '',
      }))
    }
  } catch {
    // 兼容每行 value,label 或 value|label 的轻量写法。
  }

  return raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => {
      const separator = line.includes('|') ? '|' : ','
      const [valuePart, labelPart] = line.split(separator)
      return {
        value: (valuePart || '').trim(),
        label: (labelPart || valuePart || '').trim(),
      }
    })
}

function openSelector(type: 'image' | 'file', multiple: boolean) {
  if (props.disabled) {
    return
  }

  selectorType.value = type
  selectorMultiple.value = multiple
  selectorVisible.value = true
}

function handleSelected(files: UploadFileRow[]) {
  const urls = files.map((file) => file.url)
  emit('update:modelValue', selectorMultiple.value ? urls : (urls[0] || ''))
}

function openImagePreview(urls: Array<string | number>, index = 0) {
  const normalizedUrls = urls.map((url) => normalizeAssetUrl(String(url))).filter(Boolean)

  if (normalizedUrls.length === 0) {
    return
  }

  previewUrls.value = normalizedUrls
  previewIndex.value = Math.max(0, Math.min(index, normalizedUrls.length - 1))
  previewVisible.value = true
}

function clearImage() {
  emit('update:modelValue', '')
}

function removeImage(index: number) {
  arrayValue.value = arrayValue.value.filter((_, currentIndex) => currentIndex !== index)
}

function openFile(url: string | number) {
  window.open(normalizeAssetUrl(String(url)), '_blank')
}

function clearFile() {
  emit('update:modelValue', '')
}

function removeFile(index: number) {
  arrayValue.value = arrayValue.value.filter((_, currentIndex) => currentIndex !== index)
}

function fileName(url: string | number) {
  const value = String(url || '')
  const name = value.split('/').filter(Boolean).pop() || value

  return decodeURIComponent(name)
}

function fileExtension(url: string | number) {
  const name = fileName(url)
  const extension = name.includes('.') ? name.split('.').pop() || '' : ''

  return extension ? extension.slice(0, 5).toUpperCase() : 'FILE'
}

function fileDisplayName(url: string | number) {
  const name = fileName(url)

  if (!name.includes('.')) {
    return name
  }

  return name.split('.').slice(0, -1).join('.') || name
}
</script>

<template>
  <div class="config-value-control">
    <el-input
      v-if="item.type === 'text'"
      v-model="value"
      :disabled="disabled"
      clearable
    />
    <el-input
      v-else-if="item.type === 'password'"
      v-model="value"
      type="password"
      show-password
      :disabled="disabled"
      clearable
    />
    <el-input
      v-else-if="item.type === 'textarea'"
      v-model="value"
      type="textarea"
      :rows="4"
      :disabled="disabled"
    />
    <RichEditor
      v-else-if="item.type === 'richtext'"
      v-model="value as string"
      :disabled="disabled"
      scene="setting"
    />
    <el-input-number
      v-else-if="item.type === 'number'"
      v-model="value as number"
      :min="0"
      :disabled="disabled"
    />
    <el-switch
      v-else-if="item.type === 'switch'"
      v-model="value"
      :active-value="1"
      :inactive-value="0"
      :disabled="disabled"
    />
    <el-radio-group
      v-else-if="item.type === 'radio'"
      v-model="value"
      :disabled="disabled"
    >
      <el-radio v-for="option in options" :key="String(option.value)" :value="option.value">
        {{ option.label }}
      </el-radio>
    </el-radio-group>
    <el-checkbox-group
      v-else-if="item.type === 'checkbox'"
      v-model="arrayValue"
      :disabled="disabled"
    >
      <el-checkbox v-for="option in options" :key="String(option.value)" :value="option.value">
        {{ option.label }}
      </el-checkbox>
    </el-checkbox-group>
    <el-select
      v-else-if="item.type === 'select'"
      v-model="value"
      :disabled="disabled"
      clearable
      filterable
      class="full"
    >
      <el-option v-for="option in options" :key="String(option.value)" :label="option.label" :value="option.value" />
    </el-select>
    <el-select
      v-else-if="item.type === 'select_multiple'"
      v-model="arrayValue"
      multiple
      clearable
      filterable
      collapse-tags
      :disabled="disabled"
      class="full"
    >
      <el-option v-for="option in options" :key="String(option.value)" :label="option.label" :value="option.value" />
    </el-select>
    <el-color-picker
      v-else-if="item.type === 'color'"
      v-model="value"
      :disabled="disabled"
      show-alpha
      :predefine="colorPredefine"
      :popper-class="colorPredefineClass"
    />
    <el-date-picker
      v-else-if="item.type === 'date'"
      v-model="value"
      type="date"
      value-format="YYYY-MM-DD"
      :disabled="disabled"
    />
    <el-date-picker
      v-else-if="item.type === 'daterange'"
      v-model="arrayValue"
      type="daterange"
      value-format="YYYY-MM-DD"
      start-placeholder="开始日期"
      end-placeholder="结束日期"
      :disabled="disabled"
    />
    <el-date-picker
      v-else-if="item.type === 'datetime'"
      v-model="value"
      type="datetime"
      value-format="YYYY-MM-DD HH:mm:ss"
      :disabled="disabled"
    />
    <el-date-picker
      v-else-if="item.type === 'datetimerange'"
      v-model="arrayValue"
      type="datetimerange"
      value-format="YYYY-MM-DD HH:mm:ss"
      start-placeholder="开始时间"
      end-placeholder="结束时间"
      :disabled="disabled"
    />
    <el-time-picker
      v-else-if="item.type === 'time'"
      v-model="value"
      value-format="HH:mm:ss"
      :disabled="disabled"
    />
    <el-time-picker
      v-else-if="item.type === 'timerange'"
      v-model="arrayValue"
      is-range
      value-format="HH:mm:ss"
      start-placeholder="开始时间"
      end-placeholder="结束时间"
      :disabled="disabled"
    />
    <el-slider
      v-else-if="item.type === 'slider'"
      v-model="value as number"
      :min="0"
      :max="100"
      :disabled="disabled"
    />
    <el-rate
      v-else-if="item.type === 'rate'"
      v-model="value as number"
      :disabled="disabled"
    />
    <template v-else-if="item.type === 'image'">
      <div class="image-list">
        <div v-if="singleValue" class="image-item">
          <button
            class="image-preview"
            type="button"
            title="预览图片"
            @click="openImagePreview([singleValue])"
          >
            <el-image class="config-image" :src="normalizeAssetUrl(singleValue)" fit="contain" />
          </button>
          <div class="image-actions">
            <el-tooltip content="预览" placement="top">
              <button class="image-action" type="button" aria-label="预览" @click="openImagePreview([singleValue])">
                <el-icon><ZoomIn /></el-icon>
              </button>
            </el-tooltip>
            <el-tooltip v-if="!disabled" content="更换" placement="top">
              <button class="image-action" type="button" aria-label="更换" @click="openSelector('image', false)">
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
          @click="openSelector('image', false)"
        >
          <el-icon><Plus /></el-icon>
        </button>
      </div>
    </template>
    <template v-else-if="item.type === 'images'">
      <div class="image-list">
        <div v-for="(url, index) in arrayValue" :key="`${url}-${index}`" class="image-item">
          <button
            class="image-preview"
            type="button"
            title="预览图片"
            @click="openImagePreview(arrayValue, index)"
          >
            <el-image
              class="config-image"
              :src="normalizeAssetUrl(String(url))"
              fit="contain"
            />
          </button>
          <div class="image-actions">
            <el-tooltip content="预览" placement="top">
              <button class="image-action" type="button" aria-label="预览" @click="openImagePreview(arrayValue, index)">
                <el-icon><ZoomIn /></el-icon>
              </button>
            </el-tooltip>
            <el-tooltip v-if="!disabled" content="移除" placement="top">
              <button class="image-action danger" type="button" aria-label="移除" @click="removeImage(index)">
                <el-icon><Delete /></el-icon>
              </button>
            </el-tooltip>
          </div>
        </div>
        <button
          v-if="!disabled"
          class="image-add"
          type="button"
          aria-label="选择多图"
          @click="openSelector('image', true)"
        >
          <el-icon><Plus /></el-icon>
        </button>
      </div>
    </template>
    <template v-else-if="item.type === 'file'">
      <div class="file-card-list">
        <div v-if="singleValue" class="config-file-card">
          <button class="file-main" type="button" title="打开文件" @click="openFile(singleValue)">
            <span class="file-ext">{{ fileExtension(singleValue) }}</span>
            <span class="file-text">
              <span class="file-name" :title="singleValue">{{ fileDisplayName(singleValue) }}</span>
              <span class="file-path" :title="singleValue">{{ singleValue }}</span>
            </span>
          </button>
          <div class="file-card-actions">
            <el-tooltip content="打开" placement="top">
              <button class="image-action" type="button" aria-label="打开" @click="openFile(singleValue)">
                <el-icon><View /></el-icon>
              </button>
            </el-tooltip>
            <el-tooltip v-if="!disabled" content="更换" placement="top">
              <button class="image-action" type="button" aria-label="更换" @click="openSelector('file', false)">
                <el-icon><Refresh /></el-icon>
              </button>
            </el-tooltip>
            <el-tooltip v-if="!disabled" content="移除" placement="top">
              <button class="image-action danger" type="button" aria-label="移除" @click="clearFile">
                <el-icon><Delete /></el-icon>
              </button>
            </el-tooltip>
          </div>
        </div>
        <button
          v-else-if="!disabled"
          class="file-add"
          type="button"
          aria-label="选择文件"
          @click="openSelector('file', false)"
        >
          <el-icon><Plus /></el-icon>
          <span>选择文件</span>
        </button>
      </div>
    </template>
    <template v-else-if="item.type === 'files'">
      <div class="file-card-list">
        <div v-for="(url, index) in arrayValue" :key="`${url}-${index}`" class="config-file-card">
          <button class="file-main" type="button" title="打开文件" @click="openFile(url)">
            <span class="file-ext">{{ fileExtension(url) }}</span>
            <span class="file-text">
              <span class="file-name" :title="String(url)">{{ fileDisplayName(url) }}</span>
              <span class="file-path" :title="String(url)">{{ url }}</span>
            </span>
          </button>
          <div class="file-card-actions">
            <el-tooltip content="打开" placement="top">
              <button class="image-action" type="button" aria-label="打开" @click="openFile(url)">
                <el-icon><View /></el-icon>
              </button>
            </el-tooltip>
            <el-tooltip v-if="!disabled" content="移除" placement="top">
              <button class="image-action danger" type="button" aria-label="移除" @click="removeFile(index)">
                <el-icon><Delete /></el-icon>
              </button>
            </el-tooltip>
          </div>
        </div>
        <button
          v-if="!disabled"
          class="file-add"
          type="button"
          aria-label="选择多文件"
          @click="openSelector('file', true)"
        >
          <el-icon><Plus /></el-icon>
          <span>选择文件</span>
        </button>
      </div>
    </template>
    <el-input v-else v-model="value" :disabled="disabled" clearable />

    <FileSelector
      v-model="selectorVisible"
      :accept-type="selectorType"
      :multiple="selectorMultiple"
      scene="setting"
      :current-url="String(value || '')"
      @select="handleSelected"
    />
    <el-image-viewer
      v-if="previewVisible"
      :url-list="previewUrls"
      :initial-index="previewIndex"
      @close="previewVisible = false"
    />
  </div>
</template>

<style scoped>
.config-value-control {
  width: 100%;
}

.full {
  width: 100%;
}

.image-list,
.file-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.image-item,
.image-add {
  position: relative;
  width: 96px;
  height: 96px;
}

.image-preview {
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  overflow: hidden;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  background: var(--el-fill-color-light);
  cursor: zoom-in;
}

.image-add {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border: 1px dashed var(--el-border-color);
  border-radius: 4px;
  color: var(--el-text-color-secondary);
  background: var(--el-fill-color-lighter);
  cursor: pointer;
}

.image-add:hover {
  color: var(--el-color-primary);
  border-color: var(--el-color-primary);
}

.image-add .el-icon {
  font-size: 24px;
}

.config-image {
  display: block;
  width: 96px;
  height: 96px;
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
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  color: var(--el-text-color-primary);
  background: var(--el-bg-color-overlay);
  cursor: pointer;
}

.image-action:hover {
  color: var(--el-color-primary);
  border-color: var(--el-color-primary-light-5);
}

.image-action.danger:hover {
  color: var(--el-color-danger);
  border-color: var(--el-color-danger-light-5);
}

.file-card-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.config-file-card,
.file-add {
  position: relative;
  width: 260px;
  height: 74px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  background: var(--el-bg-color);
}

.file-main {
  display: flex;
  align-items: center;
  width: 100%;
  height: 100%;
  min-width: 0;
  padding: 10px 42px 10px 12px;
  border: 0;
  color: var(--el-text-color-primary);
  background: transparent;
  cursor: pointer;
}

.file-ext {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 40px;
  margin-right: 8px;
  border: 1px solid var(--el-color-primary-light-5);
  border-radius: 4px;
  color: var(--el-color-primary);
  background: var(--el-color-primary-light-9);
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
}

.file-text {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.file-name {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 14px;
  font-weight: 500;
}

.file-path {
  max-width: 100%;
  overflow: hidden;
  color: var(--el-text-color-secondary);
  font-size: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-card-actions {
  position: absolute;
  top: 4px;
  right: 4px;
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.config-file-card:hover .file-card-actions,
.config-file-card:focus-within .file-card-actions {
  opacity: 1;
}

.file-add {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 0;
  border-style: dashed;
  color: var(--el-text-color-secondary);
  cursor: pointer;
}

.file-add:hover {
  color: var(--el-color-primary);
  border-color: var(--el-color-primary);
}

.file-add .el-icon {
  font-size: 20px;
}
</style>

<style>
.config-color-predefine .el-color-predefine__colors {
  display: grid;
  grid-template-columns: repeat(10, 20px);
  gap: 8px;
  width: max-content;
}

.config-color-predefine .el-color-predefine__color-selector {
  margin: 0;
}
</style>
