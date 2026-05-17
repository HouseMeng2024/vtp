<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import {
  fetchSystemConfigs,
  updateSystemConfigs,
  type SystemConfigGroup,
} from '../../../api/system'
import { useAppStore } from '../../../stores/app'
import { useAuthStore } from '../../../stores/auth'
import ConfigValueControl from './ConfigValueControl.vue'

const authStore = useAuthStore()
const appStore = useAppStore()
const { t } = useI18n()
const loading = ref(false)
const saving = ref(false)
const groups = ref<SystemConfigGroup[]>([])
const activeGroupId = ref(0)
const activeTabId = ref('')
const form = reactive<Record<string, string | number | Array<string | number>>>({})
const canUpdate = computed(() => authStore.hasPermission('admin:config:update'))

const activeGroup = computed(() => groups.value.find((item) => item.id === activeGroupId.value))
const activeTab = computed(() => activeGroup.value?.tabs.find((item) => String(item.id) === activeTabId.value))
const formKey = (id: number) => String(id)

async function loadData() {
  loading.value = true
  try {
    applyGroups(await fetchSystemConfigs())
  } finally {
    loading.value = false
  }
}

function applyGroups(nextGroups: SystemConfigGroup[]) {
  groups.value = nextGroups.filter((group) => group.status === 1)

  if (!groups.value.some((item) => item.id === activeGroupId.value)) {
    activeGroupId.value = groups.value[0]?.id || 0
  }

  const group = activeGroup.value
  if (!group?.tabs.some((item) => String(item.id) === activeTabId.value && item.status === 1)) {
    activeTabId.value = group?.tabs.find((item) => item.status === 1) ? String(group.tabs.find((item) => item.status === 1)?.id) : ''
  }

  Object.keys(form).forEach((key) => delete form[key])
  for (const groupItem of groups.value) {
    for (const tab of groupItem.tabs.filter((item) => item.status === 1)) {
      for (const item of tab.items.filter((config) => config.status === 1)) {
        form[formKey(item.id)] = normalizeFormValue(item.type, item.value)
      }
    }
  }
}

function normalizeFormValue(type: string, rawValue: string) {
  if (['number', 'switch', 'slider', 'rate'].includes(type)) {
    return Number(rawValue || 0)
  }

  if (['checkbox', 'select_multiple', 'daterange', 'datetimerange', 'timerange', 'images', 'files'].includes(type)) {
    try {
      const parsed = JSON.parse(rawValue || '[]')
      return Array.isArray(parsed) ? parsed : []
    } catch {
      return rawValue ? rawValue.split(',').map((item) => item.trim()).filter(Boolean) : []
    }
  }

  return rawValue || ''
}

async function submitForm() {
  if (!activeTab.value) {
    return
  }

  const payload = activeTab.value.items
    .filter((item) => item.status === 1)
    .reduce<Record<string, string | number | Array<string | number>>>((result, item) => {
      result[formKey(item.id)] = form[formKey(item.id)]
      return result
    }, {})

  saving.value = true
  try {
    applyGroups(await updateSystemConfigs(payload))
    await appStore.loadSiteConfig()
    ElMessage.success(t('common.saved'))
  } finally {
    saving.value = false
  }
}

function selectGroup(group: SystemConfigGroup) {
  activeGroupId.value = group.id
  activeTabId.value = group.tabs.find((item) => item.status === 1) ? String(group.tabs.find((item) => item.status === 1)?.id) : ''
}

onMounted(loadData)
</script>

<template>
  <el-card class="page-card config-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">{{ t('common.projectSetting') }}</div>
        <el-button v-if="canUpdate" type="primary" :loading="saving" @click="submitForm">{{ t('common.save') }}</el-button>
      </div>
    </template>

    <el-skeleton v-if="loading" :rows="8" animated />
    <div v-else class="config-layout">
      <aside class="config-groups">
        <button
          v-for="group in groups"
          :key="group.id"
          type="button"
          class="group-item"
          :class="{ active: group.id === activeGroupId }"
          @click="selectGroup(group)"
        >
          {{ group.title }}
        </button>
      </aside>

      <section class="config-main">
        <el-empty v-if="!activeGroup" :description="t('config.noGroups')" />
        <el-tabs v-else v-model="activeTabId">
          <el-tab-pane
            v-for="tab in activeGroup.tabs.filter((item) => item.status === 1)"
            :key="tab.id"
            :label="tab.title"
            :name="String(tab.id)"
          >
            <el-form class="config-form" label-width="130px">
              <el-empty v-if="tab.items.filter((item) => item.status === 1).length === 0" :description="t('config.noItems')" />
              <el-form-item
                v-for="item in tab.items.filter((config) => config.status === 1)"
                :key="item.id"
              >
                <template #label>
                  <div class="config-label">
                    <span class="config-label-name">{{ item.name }}</span>
                    <span class="config-label-key">{{ item.key }}</span>
                  </div>
                </template>
                <div class="config-control">
                  <ConfigValueControl v-model="form[formKey(item.id)]" :item="item" :disabled="!canUpdate" />
                  <div v-if="item.remark" class="config-remark">{{ item.remark }}</div>
                </div>
              </el-form-item>
            </el-form>
          </el-tab-pane>
        </el-tabs>
      </section>
    </div>
  </el-card>
</template>

<style scoped>
.config-page-card {
  height: 100%;
}

.config-layout {
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: 18px;
  min-height: 520px;
}

.config-groups {
  overflow: auto;
  border-right: 1px solid var(--el-border-color-light);
  padding-right: 12px;
}

.group-item {
  display: flex;
  align-items: center;
  width: 100%;
  min-height: 40px;
  padding: 0 12px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: var(--el-text-color-regular);
  cursor: pointer;
}

.group-item.active {
  background: var(--el-color-primary-light-9);
  color: var(--el-color-primary);
}

.config-main {
  min-width: 0;
}

.config-form {
  max-width: 820px;
  padding-top: 12px;
}

.config-control {
  flex: 1;
  min-width: 0;
}

.config-label {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  line-height: 1.35;
}

.config-label-name {
  color: var(--el-text-color-primary);
}

.config-label-key {
  max-width: 120px;
  overflow: hidden;
  color: var(--el-text-color-secondary);
  font-family: var(--el-font-family);
  font-size: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.config-remark {
  width: 100%;
  margin-top: 6px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
  line-height: 1.5;
}

@media (max-width: 900px) {
  .config-layout {
    grid-template-columns: 1fr;
  }

  .config-groups {
    display: flex;
    gap: 8px;
    border-right: 0;
    border-bottom: 1px solid var(--el-border-color-light);
    padding: 0 0 12px;
  }

  .group-item {
    width: auto;
    min-width: 120px;
  }
}
</style>
