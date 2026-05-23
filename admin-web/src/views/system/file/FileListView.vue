<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Document, MoreFilled, Picture } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  batchDeleteUploadFiles,
  deleteUploadFile,
  fetchUploadFileDeleteInfo,
  fetchUploadFiles,
  renameUploadFile,
  type UploadFileRow,
} from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'
import { useAppStore } from '../../../stores/app'
import FileSelector from '../../../components/FileSelector.vue'
import { normalizeAssetUrl } from '../../../utils/asset'

const authStore = useAuthStore()
const appStore = useAppStore()
const loading = ref(false)
const selectorVisible = ref(false)
const previewVisible = ref(false)
const previewUrl = ref('')
const rows = ref<UploadFileRow[]>([])
const selectedIds = ref<number[]>([])
const total = ref(0)
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
  extension: '',
  category: '',
  scene: '',
})
const canUpload = computed(() => authStore.hasPermission('admin:file:upload'))
const canDelete = computed(() => authStore.hasPermission('admin:file:delete'))
const canUpdate = computed(() => authStore.hasPermission('admin:file:update'))
const extensionOptions = computed(() => appStore.uploadFileOptions.extensions)
const imageExtensions = computed(() => appStore.uploadFileOptions.image_extensions)
const categoryOptions = computed(() => appStore.uploadFileOptions.categories)

async function loadData() {
  loading.value = true
  try {
    const data = await fetchUploadFiles(query)
    rows.value = data.data
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  query.page = 1
  loadData()
}

function isSelected(id: number) {
  return selectedIds.value.includes(id)
}

function toggleSelected(id: number, checked: boolean) {
  selectedIds.value = checked
    ? Array.from(new Set([...selectedIds.value, id]))
    : selectedIds.value.filter((item) => item !== id)
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

function isImage(row: UploadFileRow) {
  return row.category === 'image' || imageExtensions.value.includes(row.extension.toLowerCase())
}

function fileUrl(row: UploadFileRow) {
  return normalizeAssetUrl(row.url)
}

function handleUploaded() {
  loadData()
}

function previewImage(row: UploadFileRow) {
  previewUrl.value = fileUrl(row)
  previewVisible.value = true
}

function openFile(row: UploadFileRow) {
  window.open(fileUrl(row), '_blank')
}

async function handleDelete(row: UploadFileRow) {
  const info = await fetchUploadFileDeleteInfo(row.id)
  const message = info.reference_count > 0
    ? `文件「${row.original_name}」还有 ${info.reference_count} 条记录引用同一个物理文件，本次只删除当前记录，不会删除物理文件。`
    : `文件「${row.original_name}」没有其他记录引用，删除后会同时删除物理文件。`

  await ElMessageBox.confirm(message, '删除确认', {
    type: 'warning',
  })
  await deleteUploadFile(row.id)
  ElMessage.success('删除成功')
  loadData()
}

async function handleBatchDelete() {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先选择文件')
    return
  }

  await ElMessageBox.confirm(`确定删除选中的 ${selectedIds.value.length} 个文件吗？`, '批量删除确认', {
    type: 'warning',
  })
  await batchDeleteUploadFiles(selectedIds.value)
  selectedIds.value = []
  ElMessage.success('批量删除成功')
  loadData()
}

async function handleRename(row: UploadFileRow) {
  const result = await ElMessageBox.prompt('请输入新的文件显示名称', '重命名', {
    inputValue: row.original_name,
    inputPattern: /.+/,
    inputErrorMessage: '请输入文件名',
  })
  await renameUploadFile(row.id, result.value)
  ElMessage.success('重命名成功')
  loadData()
}

async function copyLink(row: UploadFileRow) {
  await navigator.clipboard.writeText(fileUrl(row))
  ElMessage.success('链接已复制')
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <el-card class="page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">文件管理</div>
        <el-space>
          <el-button v-if="canDelete" type="danger" :disabled="selectedIds.length === 0" @click="handleBatchDelete">
            批量删除
          </el-button>
          <el-button v-if="canUpload" type="primary" @click="selectorVisible = true">上传 / 选择文件</el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="文件名 / 路径" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item label="类型">
        <el-select v-model="query.extension" clearable placeholder="全部类型" style="width: 140px" @change="handleSearch">
          <el-option v-for="item in extensionOptions" :key="item" :label="item" :value="item" />
        </el-select>
      </el-form-item>
      <el-form-item label="分类">
        <el-select v-model="query.category" clearable placeholder="全部分类" style="width: 140px" @change="handleSearch">
          <el-option v-for="item in categoryOptions" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
      </el-form-item>
      <el-form-item label="场景">
        <el-input v-model="query.scene" clearable placeholder="如 default / setting" style="width: 170px" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </el-form-item>
    </el-form>

    <div v-loading="loading" class="file-grid">
      <el-empty v-if="!loading && rows.length === 0" description="暂无文件" />
      <div v-for="row in rows" :key="row.id" class="file-card">
        <el-checkbox
          class="file-check"
          :model-value="isSelected(row.id)"
          @change="(checked: string | number | boolean) => toggleSelected(row.id, Boolean(checked))"
        />
        <button class="file-preview" type="button" @click="isImage(row) ? previewImage(row) : openFile(row)">
          <el-image
            v-if="isImage(row)"
            :src="fileUrl(row)"
            fit="cover"
            lazy
          >
            <template #error>
              <div class="file-placeholder">
                <el-icon><Picture /></el-icon>
              </div>
            </template>
          </el-image>
          <div v-else class="file-placeholder">
            <el-icon><Document /></el-icon>
            <span>{{ row.extension || 'file' }}</span>
          </div>
        </button>
        <div class="file-info">
          <div class="file-name" :title="row.original_name">{{ row.original_name }}</div>
          <div class="file-meta">{{ row.category || 'other' }} · {{ row.scene || 'default' }} · {{ row.extension || '-' }} · {{ formatSize(row.size) }}</div>
          <div class="file-time">{{ row.create_time }}</div>
        </div>
        <div class="file-actions">
          <el-button link type="primary" tag="a" :href="fileUrl(row)" target="_blank">查看</el-button>
          <el-button link type="primary" tag="a" :href="fileUrl(row)" :download="row.original_name">下载</el-button>
          <el-dropdown trigger="click">
            <button class="file-more" type="button" aria-label="更多操作">
              <el-icon><MoreFilled /></el-icon>
            </button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="copyLink(row)">复制链接</el-dropdown-item>
                <el-dropdown-item v-if="canUpdate" @click="handleRename(row)">重命名</el-dropdown-item>
                <el-dropdown-item v-if="canDelete" divided @click="handleDelete(row)">
                  <span class="danger-text">删除</span>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>
    </div>

    <el-pagination
      v-model:current-page="query.page"
      v-model:page-size="query.limit"
      class="page-pagination"
      layout="total, sizes, prev, pager, next, jumper"
      :total="total"
      :page-sizes="[10, 20, 50, 100]"
      @size-change="loadData"
      @current-change="loadData"
    />
    <FileSelector v-model="selectorVisible" accept-type="file" multiple scene="default" @select="handleUploaded" />
    <el-image-viewer
      v-if="previewVisible"
      :url-list="[previewUrl]"
      @close="previewVisible = false"
    />
  </el-card>
</template>

<style scoped>
.file-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 14px;
  min-height: 180px;
}

.file-card {
  position: relative;
  overflow: hidden;
  border: 1px solid var(--el-border-color-light);
  border-radius: 6px;
  background: var(--el-bg-color);
}

.file-check {
  position: absolute;
  z-index: 1;
  top: 8px;
  left: 8px;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--el-bg-color-overlay);
}

.file-preview {
  display: block;
  width: 100%;
  padding: 0;
  height: 150px;
  overflow: hidden;
  border: 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
  background: var(--el-fill-color-light);
  cursor: pointer;
}

.file-preview :deep(.el-image) {
  width: 100%;
  height: 100%;
}

.file-placeholder {
  display: grid;
  width: 100%;
  height: 100%;
  place-items: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.file-placeholder .el-icon {
  font-size: 34px;
}

.file-info {
  padding: 10px 12px 8px;
}

.file-name {
  overflow: hidden;
  color: var(--el-text-color-primary);
  font-size: 14px;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-meta,
.file-time {
  margin-top: 6px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.file-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 0 12px 12px;
}

.file-more {
  width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 4px;
  background: transparent;
  color: var(--el-color-primary);
  cursor: pointer;
}

.file-more:hover {
  background: var(--el-fill-color-light);
}

.danger-text {
  color: var(--el-color-danger);
}
</style>
