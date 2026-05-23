<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  createArticle,
  deleteArticle,
  fetchArticleDetail,
  fetchArticles,
  updateArticle,
  updateArticleStatus,
  type ArticlePayload,
  type ArticleRow,
} from '../../../api/article'
import { fetchContentCategories, type ContentCategoryRow } from '../../../api/contentCategory'
import FileSelector from '../../../components/FileSelector.vue'
import RichEditor from '../../../components/RichEditor.vue'
import { useAuthStore } from '../../../stores/auth'
import { normalizeAssetUrl } from '../../../utils/asset'

const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const drawerVisible = ref(false)
const fileSelectorVisible = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<ArticleRow[]>([])
const categoryRows = ref<ContentCategoryRow[]>([])
const total = ref(0)
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
  category_id: '' as number | '',
  status: '' as number | '',
})
const form = reactive<ArticlePayload>({
  category_id: 0,
  title: '',
  subtitle: '',
  cover: '',
  summary: '',
  content: '',
  author: '',
  source: '',
  source_url: '',
  keywords: '',
  description: '',
  views: 0,
  sort: 100,
  status: 1,
  publish_time: '',
})
const rules: FormRules = {
  title: [{ required: true, message: '请输入文章标题', trigger: 'blur' }],
}
const statusMap: Record<number, { label: string; type: 'info' | 'success' }> = {
  0: { label: '下架', type: 'info' },
  1: { label: '已发布', type: 'success' },
}
const categoryOptions = computed(() => flattenCategories(categoryRows.value))
const categoryNameMap = computed(() => Object.fromEntries(categoryOptions.value.map((item) => [item.id, item.name])))

function flattenCategories(tree: ContentCategoryRow[], level = 0): Array<ContentCategoryRow & { level: number }> {
  return tree.flatMap((item) => [
    { ...item, level },
    ...flattenCategories(item.children || [], level + 1),
  ])
}

async function loadCategories() {
  categoryRows.value = await fetchContentCategories({ type: 'article' })
}

async function loadData() {
  loading.value = true
  try {
    const data = await fetchArticles(query)
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

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    category_id: 0,
    title: '',
    subtitle: '',
    cover: '',
    summary: '',
    content: '',
    author: '',
    source: '',
    source_url: '',
    keywords: '',
    description: '',
    views: 0,
    sort: 100,
    status: 1,
    publish_time: '',
  })
  formRef.value?.clearValidate()
}

function openCreate() {
  resetForm()
  drawerVisible.value = true
}

async function openEdit(row: ArticleRow) {
  resetForm()
  editingId.value = row.id
  const detail = await fetchArticleDetail(row.id)
  Object.assign(form, {
    category_id: detail.category_id,
    title: detail.title,
    subtitle: detail.subtitle,
    cover: detail.cover,
    summary: detail.summary,
    content: detail.content || '',
    author: detail.author,
    source: detail.source,
    source_url: detail.source_url || '',
    keywords: detail.keywords || '',
    description: detail.description || '',
    views: detail.views,
    sort: detail.sort,
    status: detail.status,
    publish_time: detail.publish_time || '',
  })
  drawerVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true
  try {
    if (editingId.value) {
      await updateArticle(editingId.value, form)
      ElMessage.success('保存成功')
    } else {
      await createArticle(form)
      ElMessage.success('创建成功')
    }
    drawerVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleStatus(row: ArticleRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  const data = await updateArticleStatus(row.id, nextStatus)
  row.status = data.status
  row.publish_time = data.publish_time
  ElMessage.success('状态已更新')
}

async function handleDelete(row: ArticleRow) {
  await ElMessageBox.confirm(`确定删除文章「${row.title}」吗？`, '删除确认', { type: 'warning' })
  await deleteArticle(row.id)
  ElMessage.success('删除成功')
  loadData()
}

function handleCoverSelected(files: Array<{ url: string }>) {
  if (files.length) {
    form.cover = files[0].url
  }
}

onMounted(() => {
  loadCategories()
  loadData()
})
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">文章管理</div>
        <el-button v-if="authStore.hasPermission('admin:article:create')" type="primary" @click="openCreate">新增</el-button>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="标题 / 作者" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item label="分类">
        <el-select v-model="query.category_id" clearable placeholder="全部分类" style="width: 160px">
          <el-option v-for="item in categoryOptions" :key="item.id" :label="`${'　'.repeat(item.level)}${item.name}`" :value="item.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" clearable placeholder="全部" style="width: 120px">
          <el-option label="已发布" :value="1" />
          <el-option label="下架" :value="0" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%">
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column label="封面" width="96">
          <template #default="{ row }">
            <el-image v-if="row.cover" class="thumb" :src="normalizeAssetUrl(row.cover)" fit="cover" :preview-src-list="[normalizeAssetUrl(row.cover)]" preview-teleported />
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="220" show-overflow-tooltip />
        <el-table-column label="分类" min-width="120">
          <template #default="{ row }">{{ categoryNameMap[row.category_id] || '-' }}</template>
        </el-table-column>
        <el-table-column prop="author" label="作者" width="120" show-overflow-tooltip />
        <el-table-column prop="views" label="浏览" width="90" />
        <el-table-column prop="sort" label="排序" width="90" />
        <el-table-column label="状态" width="110">
          <template #default="{ row }">
            <el-switch
              v-if="authStore.hasPermission('admin:article:status')"
              :model-value="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatus(row)"
            />
            <el-tag v-else :type="statusMap[row.status]?.type || 'info'">{{ statusMap[row.status]?.label || '-' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="publish_time" label="发布时间" min-width="170" />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:article:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button v-if="authStore.hasPermission('admin:article:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
            </el-space>
          </template>
        </el-table-column>
      </el-table>
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

    <el-drawer v-model="drawerVisible" :title="editingId ? '编辑文章' : '新增文章'" size="860px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-row :gutter="14">
          <el-col :span="12">
            <el-form-item label="标题" prop="title">
              <el-input v-model="form.title" maxlength="200" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="分类">
              <el-select v-model="form.category_id" class="full" clearable>
                <el-option label="未分类" :value="0" />
                <el-option v-for="item in categoryOptions" :key="item.id" :label="`${'　'.repeat(item.level)}${item.name}`" :value="item.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="副标题">
              <el-input v-model="form.subtitle" maxlength="200" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="作者">
              <el-input v-model="form.author" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="摘要">
              <el-input v-model="form.summary" type="textarea" :rows="3" maxlength="500" show-word-limit />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="封面">
              <button class="image-picker" type="button" @click="fileSelectorVisible = true">
                <el-image v-if="form.cover" :src="normalizeAssetUrl(form.cover)" fit="cover" />
                <el-icon v-else><Plus /></el-icon>
              </button>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="正文">
              <RichEditor v-model="form.content" scene="article" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="来源">
              <el-input v-model="form.source" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="来源链接">
              <el-input v-model="form.source_url" maxlength="500" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="关键词">
              <el-input v-model="form.keywords" maxlength="255" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="发布时间">
              <el-date-picker v-model="form.publish_time" class="full" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="浏览量">
              <el-input-number v-model="form.views" :min="0" class="full" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="排序">
              <el-input-number v-model="form.sort" :min="0" :max="99999" class="full" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态">
              <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="发布" inactive-text="下架" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="SEO描述">
              <el-input v-model="form.description" type="textarea" :rows="3" maxlength="500" show-word-limit />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="drawerVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </template>
    </el-drawer>

    <FileSelector v-model="fileSelectorVisible" accept-type="image" scene="article_cover" :current-url="form.cover" @select="handleCoverSelected" />
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}

.thumb {
  width: 48px;
  height: 48px;
  border-radius: 4px;
}

.image-picker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 140px;
  height: 90px;
  overflow: hidden;
  color: var(--el-text-color-secondary);
  cursor: pointer;
  background: var(--el-fill-color-lighter);
  border: 1px dashed var(--el-border-color);
  border-radius: 6px;
}

.image-picker .el-image {
  width: 100%;
  height: 100%;
}
</style>
