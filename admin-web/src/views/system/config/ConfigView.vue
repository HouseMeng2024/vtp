<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { fetchSystemConfigs, updateSystemConfigs, type SystemConfigGroup, type UploadFileRow } from '../../../api/system'
import { useAppStore } from '../../../stores/app'
import { useAuthStore } from '../../../stores/auth'
import FileSelector from '../../../components/FileSelector.vue'

const authStore = useAuthStore()
const appStore = useAppStore()
const loading = ref(false)
const saving = ref(false)
const activeGroup = ref('')
const groups = ref<SystemConfigGroup[]>([])
const form = reactive<Record<string, string | number>>({})
const selectorVisible = ref(false)
const selectingKey = ref('')
const canUpdate = computed(() => authStore.hasPermission('admin:config:update'))
const backendOrigin = import.meta.env.DEV ? 'http://127.0.0.1:8000' : ''

async function loadData() {
  loading.value = true
  try {
    groups.value = await fetchSystemConfigs()
    activeGroup.value = groups.value[0]?.group || ''
    Object.keys(form).forEach((key) => delete form[key])

    for (const group of groups.value) {
      for (const item of group.items) {
        form[item.key] = ['number', 'switch'].includes(item.type) ? Number(item.value || 0) : item.value || ''
      }
    }
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  saving.value = true
  try {
    groups.value = await updateSystemConfigs(form)
    await appStore.loadSiteConfig()
    ElMessage.success('保存成功')
  } finally {
    saving.value = false
  }
}

function imageUrl(value: string | number) {
  const url = String(value || '')

  if (/^https?:\/\//i.test(url) || url.startsWith('data:')) {
    return url
  }

  return `${backendOrigin}${url}`
}

function openImageSelector(key: string) {
  selectingKey.value = key
  selectorVisible.value = true
}

function handleImageSelected(files: UploadFileRow[]) {
  const file = files[0]

  if (!file || !selectingKey.value) {
    return
  }

  form[selectingKey.value] = file.url
}

onMounted(loadData)
</script>

<template>
  <el-card class="page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">项目配置</div>
        <el-button v-if="canUpdate" type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </div>
    </template>

    <el-skeleton v-if="loading" :rows="8" animated />
    <el-tabs v-else v-model="activeGroup">
      <el-tab-pane v-for="group in groups" :key="group.group" :label="group.title" :name="group.group">
        <el-form class="config-form" label-width="130px">
          <el-form-item v-for="item in group.items" :key="item.key" :label="item.name">
            <el-input-number
              v-if="item.type === 'number'"
              v-model="form[item.key] as number"
              :min="0"
              :disabled="!canUpdate"
            />
            <el-switch
              v-else-if="item.type === 'switch'"
              v-model="form[item.key]"
              :active-value="1"
              :inactive-value="0"
              :disabled="!canUpdate"
            />
            <el-input
              v-else-if="item.type === 'image'"
              v-model="form[item.key]"
              :disabled="!canUpdate"
              placeholder="请输入图片 URL，或上传图片"
            >
              <template #append>
                <el-button v-if="canUpdate" @click="openImageSelector(item.key)">选择</el-button>
                <span v-else>选择</span>
              </template>
            </el-input>
            <el-input
              v-else-if="item.type === 'textarea'"
              v-model="form[item.key]"
              type="textarea"
              :rows="4"
              :disabled="!canUpdate"
            />
            <el-input v-else v-model="form[item.key]" :disabled="!canUpdate" />
            <el-image
              v-if="item.type === 'image' && form[item.key]"
              class="config-image"
              :src="imageUrl(form[item.key])"
              fit="contain"
            />
            <div v-if="item.remark" class="config-remark">{{ item.remark }}</div>
          </el-form-item>
        </el-form>
      </el-tab-pane>
    </el-tabs>
    <FileSelector
      v-model="selectorVisible"
      accept-type="image"
      scene="setting"
      :current-url="String(form[selectingKey] || '')"
      @select="handleImageSelected"
    />
  </el-card>
</template>

<style scoped>
.config-form {
  max-width: 760px;
  padding-top: 12px;
}

.config-remark {
  width: 100%;
  margin-top: 6px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
  line-height: 1.5;
}

.config-image {
  display: block;
  width: 96px;
  height: 96px;
  margin-top: 10px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  background: var(--el-fill-color-light);
}
</style>
