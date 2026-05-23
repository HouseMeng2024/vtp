<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Document, Picture } from '@element-plus/icons-vue'
import { ElMessage, type UploadRequestOptions } from 'element-plus'
import {
  fetchUploadFiles,
  uploadFile,
  type UploadFileRow,
} from '../api/file'
import { useAppStore } from '../stores/app'
import { normalizeAssetUrl } from '../utils/asset'

const props = withDefaults(defineProps<{
  modelValue: boolean
  acceptType?: 'image' | 'file'
  multiple?: boolean
  limit?: number
  scene?: string
  currentUrl?: string
}>(), {
  acceptType: 'file',
  multiple: false,
  limit: 20,
  scene: 'default',
  currentUrl: '',
})
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  select: [files: UploadFileRow[]]
}>()
const appStore = useAppStore()
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})
const loading = ref(false)
const uploading = ref(false)
const rows = ref<UploadFileRow[]>([])
const total = ref(0)
const selectedRows = ref<UploadFileRow[]>([])
const query = reactive({
  page: 1,
  limit: props.limit,
  keyword: '',
  category: '',
  scene: props.scene,
})
const categoryOptions = computed(() => appStore.uploadFileOptions.categories)
const imageExtensions = computed(() => appStore.uploadFileOptions.image_extensions)
const uploadAccept = computed(() => props.acceptType === 'image'
  ? appStore.uploadFileOptions.image_accept
  : appStore.uploadFileOptions.accept)

watch(visible, (value) => {
  if (value) {
    query.page = 1
    query.category = props.acceptType === 'image' ? 'image' : ''
    query.scene = props.scene
    selectedRows.value = []
    loadData()
  }
})

function fileUrl(row: UploadFileRow) {
  return normalizeAssetUrl(row.url)
}

function isImage(row: UploadFileRow) {
  return row.category === 'image' || imageExtensions.value.includes(row.extension.toLowerCase())
}

function formatSize(size: number) {
  if (size < 1024) {
    return `${size} B`
  }

  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(2)} KB`
  }

  return `${(size / 1024 / 1024).toFixed(2)} MB`
}

async function loadData() {
  loading.value = true

  try {
    const data = await fetchUploadFiles(query)
    rows.value = data.data
    total.value = data.total
    syncCurrentSelection()
  } finally {
    loading.value = false
  }
}

function normalizeUrl(url = '') {
  return url.replace(/^https?:\/\/[^/]+/i, '')
}

function syncCurrentSelection() {
  if (!props.currentUrl || props.multiple) {
    return
  }

  const current = normalizeUrl(props.currentUrl)
  const selected = rows.value.find((row) => normalizeUrl(row.url) === current || normalizeUrl(fileUrl(row)) === current)

  if (selected) {
    selectedRows.value = [selected]
  }
}

function handleSearch() {
  query.page = 1
  loadData()
}

function isSelected(row: UploadFileRow) {
  return selectedRows.value.some((item) => item.id === row.id)
}

function toggleSelect(row: UploadFileRow) {
  if (!props.multiple) {
    selectedRows.value = [row]
    return
  }

  selectedRows.value = isSelected(row)
    ? selectedRows.value.filter((item) => item.id !== row.id)
    : [...selectedRows.value, row]
}

async function handleUpload(options: UploadRequestOptions) {
  const data = new FormData()
  data.append('file', options.file)
  data.append('scene', props.scene)
  uploading.value = true

  try {
    const file = await uploadFile(data)
    ElMessage.success('上传成功')
    options.onSuccess({})
    await loadData()
    selectedRows.value = props.multiple ? [...selectedRows.value, file] : [file]
  } catch (error) {
    options.onError(Object.assign(error instanceof Error ? error : new Error('上传失败'), {
      status: 0,
      method: 'POST',
      url: '',
    }))
  } finally {
    uploading.value = false
  }
}

function confirmSelect() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请选择文件')
    return
  }

  emit('select', selectedRows.value)
  visible.value = false
}
</script>

<template>
  <el-dialog v-model="visible" :title="acceptType === 'image' ? '选择图片' : '选择文件'" width="860px">
    <div class="selector-toolbar">
      <el-form inline @submit.prevent>
        <el-form-item label="关键词">
          <el-input v-model="query.keyword" clearable placeholder="文件名 / 路径" @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item v-if="acceptType !== 'image'" label="分类">
          <el-select v-model="query.category" clearable placeholder="全部分类" style="width: 130px" @change="handleSearch">
            <el-option v-for="item in categoryOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
        </el-form-item>
      </el-form>
      <el-upload :show-file-list="false" :http-request="handleUpload" :accept="uploadAccept" :disabled="uploading">
        <el-button type="primary" :loading="uploading">上传</el-button>
      </el-upload>
    </div>

    <div v-loading="loading" class="selector-grid">
      <el-empty v-if="!loading && rows.length === 0" description="暂无文件" />
      <button
        v-for="row in rows"
        :key="row.id"
        class="selector-card"
        :class="{ active: isSelected(row) }"
        type="button"
        @click="toggleSelect(row)"
      >
        <el-image v-if="isImage(row)" :src="fileUrl(row)" fit="cover" lazy>
          <template #error>
            <div class="selector-placeholder">
              <el-icon><Picture /></el-icon>
            </div>
          </template>
        </el-image>
        <div v-else class="selector-placeholder">
          <el-icon><Document /></el-icon>
          <span>{{ row.extension || 'file' }}</span>
        </div>
        <div class="selector-name" :title="row.original_name">{{ row.original_name }}</div>
        <div class="selector-meta">{{ row.extension || '-' }} · {{ formatSize(row.size) }}</div>
      </button>
    </div>

    <el-pagination
      v-model:current-page="query.page"
      v-model:page-size="query.limit"
      class="selector-pagination"
      layout="total, prev, pager, next"
      :total="total"
      @current-change="loadData"
    />

    <template #footer>
      <el-button @click="visible = false">取消</el-button>
      <el-button type="primary" @click="confirmSelect">确定</el-button>
    </template>
  </el-dialog>
</template>

<style scoped>
.selector-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.selector-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
  min-height: 260px;
}

.selector-card {
  display: block;
  width: 100%;
  padding: 0;
  overflow: hidden;
  border: 1px solid var(--el-border-color-light);
  border-radius: 6px;
  background: var(--el-bg-color);
  cursor: pointer;
  text-align: left;
}

.selector-card.active {
  border-color: var(--el-color-primary);
  box-shadow: 0 0 0 1px var(--el-color-primary) inset;
}

.selector-card :deep(.el-image),
.selector-placeholder {
  width: 100%;
  height: 108px;
  background: var(--el-fill-color-light);
}

.selector-placeholder {
  display: grid;
  place-items: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.selector-placeholder .el-icon {
  font-size: 30px;
}

.selector-name {
  margin: 8px 10px 0;
  overflow: hidden;
  color: var(--el-text-color-primary);
  font-size: 13px;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.selector-meta {
  margin: 5px 10px 10px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.selector-pagination {
  justify-content: flex-end;
  margin-top: 14px;
}
</style>
