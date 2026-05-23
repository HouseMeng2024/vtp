<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  createBanner,
  deleteBanner,
  fetchBannerOptions,
  fetchBanners,
  updateBanner,
  updateBannerStatus,
  type BannerOptions,
  type BannerPayload,
  type BannerRow,
} from '../../../api/banner'
import FileSelector from '../../../components/FileSelector.vue'
import { useAuthStore } from '../../../stores/auth'
import { normalizeAssetUrl } from '../../../utils/asset'

const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const fileSelectorVisible = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<BannerRow[]>([])
const total = ref(0)
const options = ref<BannerOptions>({
  positions: [
    { label: '首页幻灯', value: 'home' },
    { label: '文章页', value: 'article' },
    { label: '侧边栏', value: 'sidebar' },
  ],
  links: [],
})
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
  position: '',
  status: '' as number | '',
})
const form = reactive<BannerPayload>({
  position: 'home',
  title: '',
  subtitle: '',
  image: '',
  link_url: '',
  target: '_self',
  start_time: '',
  end_time: '',
  sort: 100,
  status: 1,
  remark: '',
})
const rules: FormRules = {
  title: [{ required: true, message: '请输入幻灯标题', trigger: 'blur' }],
  image: [{ required: true, message: '请选择幻灯图片', trigger: 'change' }],
}

async function loadData() {
  loading.value = true
  try {
    const data = await fetchBanners(query)
    rows.value = data.data
    total.value = data.total
  } finally {
    loading.value = false
  }
}

async function loadOptions() {
  options.value = await fetchBannerOptions()
}

function handleSearch() {
  query.page = 1
  loadData()
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    position: 'home',
    title: '',
    subtitle: '',
    image: '',
    link_url: '',
    target: '_self',
    start_time: '',
    end_time: '',
    sort: 100,
    status: 1,
    remark: '',
  })
  formRef.value?.clearValidate()
}

function openCreate() {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: BannerRow) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
    position: row.position,
    title: row.title,
    subtitle: row.subtitle,
    image: row.image,
    link_url: row.link_url,
    target: row.target,
    start_time: row.start_time || '',
    end_time: row.end_time || '',
    sort: row.sort,
    status: row.status,
    remark: row.remark,
  })
  dialogVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true
  try {
    if (editingId.value) {
      await updateBanner(editingId.value, form)
      ElMessage.success('保存成功')
    } else {
      await createBanner(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleStatus(row: BannerRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateBannerStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success('状态已更新')
}

async function handleDelete(row: BannerRow) {
  await ElMessageBox.confirm(`确定删除幻灯「${row.title}」吗？`, '删除确认', { type: 'warning' })
  await deleteBanner(row.id)
  ElMessage.success('删除成功')
  loadData()
}

function handleImageSelected(files: Array<{ url: string }>) {
  if (files.length) {
    form.image = files[0].url
  }
}

onMounted(() => {
  loadData()
  loadOptions()
})
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">幻灯管理</div>
        <el-button v-if="authStore.hasPermission('admin:banner:create')" type="primary" @click="openCreate">新增</el-button>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="标题" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item label="位置">
        <el-select v-model="query.position" clearable placeholder="全部位置" style="width: 140px" @change="handleSearch">
          <el-option v-for="item in options.positions" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
      </el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" clearable placeholder="全部" style="width: 120px">
          <el-option label="正常" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%">
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column label="图片" width="140">
          <template #default="{ row }">
            <el-image class="banner-thumb" :src="normalizeAssetUrl(row.image)" fit="cover" :preview-src-list="[normalizeAssetUrl(row.image)]" preview-teleported />
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="180" show-overflow-tooltip />
        <el-table-column prop="position" label="位置" width="120" />
        <el-table-column prop="link_url" label="链接" min-width="200" show-overflow-tooltip />
        <el-table-column prop="sort" label="排序" width="90" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-if="authStore.hasPermission('admin:banner:status')"
              :model-value="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatus(row)"
            />
            <el-tag v-else :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '正常' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="start_time" label="开始时间" min-width="170" />
        <el-table-column prop="end_time" label="结束时间" min-width="170" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:banner:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button v-if="authStore.hasPermission('admin:banner:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
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

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑幻灯' : '新增幻灯'" width="760px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-row :gutter="14">
          <el-col :span="12">
            <el-form-item label="标题" prop="title">
              <el-input v-model="form.title" maxlength="150" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="位置">
              <el-select v-model="form.position" class="full" filterable allow-create default-first-option>
                <el-option v-for="item in options.positions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="副标题">
              <el-input v-model="form.subtitle" maxlength="200" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="图片" prop="image">
              <button class="image-picker" type="button" @click="fileSelectorVisible = true">
                <el-image v-if="form.image" :src="normalizeAssetUrl(form.image)" fit="cover" />
                <el-icon v-else><Plus /></el-icon>
              </button>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="链接">
              <el-select
                v-model="form.link_url"
                class="full"
                filterable
                allow-create
                default-first-option
                placeholder="选择内部页面或输入自定义链接"
              >
                <el-option v-for="item in options.links" :key="item.url" :label="item.label" :value="item.url">
                  <span>{{ item.label }}</span>
                  <span class="link-option-url">{{ item.url }}</span>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="打开方式">
              <el-radio-group v-model="form.target">
                <el-radio-button value="_self">当前窗口</el-radio-button>
                <el-radio-button value="_blank">新窗口</el-radio-button>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="排序">
              <el-input-number v-model="form.sort" :min="0" :max="99999" class="full" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="开始时间">
              <el-date-picker v-model="form.start_time" class="full" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="结束时间">
              <el-date-picker v-model="form.end_time" class="full" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态">
              <el-radio-group v-model="form.status">
                <el-radio-button :value="1">正常</el-radio-button>
                <el-radio-button :value="0">禁用</el-radio-button>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="备注">
              <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="255" show-word-limit />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>

    <FileSelector v-model="fileSelectorVisible" accept-type="image" scene="banner" :current-url="form.image" @select="handleImageSelected" />
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}

.banner-thumb {
  width: 96px;
  height: 48px;
  border-radius: 4px;
}

.image-picker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 220px;
  height: 110px;
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

.link-option-url {
  float: right;
  margin-left: 18px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}
</style>
