<script setup lang="ts">
import { computed, ref } from 'vue'
import type { SystemConfigItem, UploadFileRow } from '../../../api/system'
import FileSelector from '../../../components/FileSelector.vue'

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
const backendOrigin = import.meta.env.DEV ? 'http://127.0.0.1:8000' : ''
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

function fileUrl(url: string | number) {
  const valueUrl = String(url || '')

  if (!valueUrl || /^https?:\/\//i.test(valueUrl) || valueUrl.startsWith('data:')) {
    return valueUrl
  }

  return `${backendOrigin}${valueUrl}`
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
      <el-input v-model="value" :disabled="disabled" placeholder="请选择图片">
        <template #append>
          <el-button :disabled="disabled" @click="openSelector('image', false)">选择</el-button>
        </template>
      </el-input>
      <el-image v-if="value" class="config-image" :src="fileUrl(String(value))" fit="contain" />
    </template>
    <template v-else-if="item.type === 'images'">
      <div class="file-list">
        <el-image
          v-for="url in arrayValue"
          :key="String(url)"
          class="config-image"
          :src="fileUrl(url)"
          fit="contain"
        />
      </div>
      <el-button :disabled="disabled" @click="openSelector('image', true)">选择多图</el-button>
    </template>
    <template v-else-if="item.type === 'file'">
      <el-input v-model="value" :disabled="disabled" placeholder="请选择文件">
        <template #append>
          <el-button :disabled="disabled" @click="openSelector('file', false)">选择</el-button>
        </template>
      </el-input>
    </template>
    <template v-else-if="item.type === 'files'">
      <el-tag v-for="url in arrayValue" :key="String(url)" class="file-tag" effect="plain">
        {{ url }}
      </el-tag>
      <div>
        <el-button :disabled="disabled" @click="openSelector('file', true)">选择多文件</el-button>
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
  </div>
</template>

<style scoped>
.config-value-control {
  width: 100%;
}

.full {
  width: 100%;
}

.file-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 8px;
}

.config-image {
  display: inline-flex;
  width: 96px;
  height: 96px;
  margin-top: 10px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  background: var(--el-fill-color-light);
}

.file-tag {
  max-width: 100%;
  margin: 0 6px 6px 0;
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
