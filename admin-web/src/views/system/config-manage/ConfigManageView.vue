<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  createSystemConfigGroup,
  createSystemConfigItem,
  createSystemConfigTab,
  deleteSystemConfigGroup,
  deleteSystemConfigItem,
  deleteSystemConfigTab,
  fetchSystemConfigs,
  updateSystemConfigGroup,
  updateSystemConfigItem,
  updateSystemConfigTab,
  type SystemConfigGroup,
  type SystemConfigItem,
  type SystemConfigTab,
} from '../../../api/system'
import { useAppStore } from '../../../stores/app'
import { useAuthStore } from '../../../stores/auth'
import ConfigValueControl from '../config/ConfigValueControl.vue'

const authStore = useAuthStore()
const appStore = useAppStore()
const loading = ref(false)
const groups = ref<SystemConfigGroup[]>([])
const groupManageVisible = ref(false)
const tabManageVisible = ref(false)
const groupDialogVisible = ref(false)
const tabDialogVisible = ref(false)
const itemDialogVisible = ref(false)
const editingGroupId = ref<number | null>(null)
const editingTabId = ref<number | null>(null)
const editingItemId = ref<number | null>(null)
const groupFormRef = ref<FormInstance>()
const tabFormRef = ref<FormInstance>()
const itemFormRef = ref<FormInstance>()
const optionRows = ref<Array<{ value: string; label: string }>>([])
const filter = reactive({
  group_id: undefined as number | undefined,
  tab_id: undefined as number | undefined,
  keyword: '',
})
const canCreate = computed(() => authStore.hasPermission('admin:config-manage:create'))
const canUpdate = computed(() => authStore.hasPermission('admin:config-manage:update'))
const canDelete = computed(() => authStore.hasPermission('admin:config-manage:delete'))
const typeOptions = computed(() => appStore.systemConfigOptions.types)
const optionTypes = computed(() => appStore.systemConfigOptions.option_types)

const groupForm = reactive({
  key: '',
  title: '',
  sort: 100,
  status: 1,
})
const tabForm = reactive({
  group_id: 0,
  key: '',
  title: '',
  sort: 100,
  status: 1,
})
const itemForm = reactive({
  tab_id: 0,
  key: '',
  value: '' as string | number | Array<string | number>,
  type: 'text',
  name: '',
  remark: '',
  options: '',
  sort: 100,
  status: 1,
})
const groupRules: FormRules = {
  key: [{ required: true, message: '请输入分组标识', trigger: 'blur' }],
  title: [{ required: true, message: '请输入分组名称', trigger: 'blur' }],
}
const tabRules: FormRules = {
  group_id: [{ required: true, message: '请选择配置分组', trigger: 'change' }],
  key: [{ required: true, message: '请输入 Tab 标识', trigger: 'blur' }],
  title: [{ required: true, message: '请输入 Tab 名称', trigger: 'blur' }],
}
const itemRules: FormRules = {
  tab_id: [{ required: true, message: '请选择配置 Tab', trigger: 'change' }],
  key: [{ required: true, message: '请输入配置键', trigger: 'blur' }],
  name: [{ required: true, message: '请输入配置名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择配置类型', trigger: 'change' }],
}

const allTabs = computed(() => groups.value.flatMap((group) => group.tabs.map((tab) => ({
  ...tab,
  group_title: group.title,
}))))
const tabOptions = computed(() => {
  if (filter.group_id === undefined) {
    return allTabs.value
  }

  return allTabs.value.filter((tab) => tab.group_id === filter.group_id)
})
const allItems = computed(() => groups.value.flatMap((group) => {
  return group.tabs.flatMap((tab) => tab.items.map((item) => ({
    ...item,
    group_title: group.title,
    tab_title: tab.title,
  })))
}))
const filteredItems = computed(() => {
  const keyword = filter.keyword.trim().toLowerCase()

  return allItems.value.filter((item) => {
    const matchGroup = filter.group_id === undefined || item.group_id === filter.group_id
    const matchTab = filter.tab_id === undefined || item.tab_id === filter.tab_id
    const matchKeyword = !keyword
      || item.name.toLowerCase().includes(keyword)
      || item.key.toLowerCase().includes(keyword)
      || item.remark.toLowerCase().includes(keyword)

    return matchGroup && matchTab && matchKeyword
  })
})
const defaultValueItem = computed<SystemConfigItem>(() => ({
  id: editingItemId.value || 0,
  group_id: 0,
  tab_id: itemForm.tab_id,
  group: '',
  key: itemForm.key || 'default_value',
  value: Array.isArray(itemForm.value) ? JSON.stringify(itemForm.value) : String(itemForm.value ?? ''),
  type: itemForm.type,
  name: itemForm.name || '默认值',
  remark: '',
  options: optionTypes.value.includes(itemForm.type) ? stringifyOptions() : itemForm.options,
  sort: itemForm.sort,
  is_system: 0,
  status: itemForm.status,
  create_time: null,
  update_time: null,
}))

async function loadData() {
  loading.value = true
  try {
    groups.value = await fetchSystemConfigs()

    if (filter.group_id && !groups.value.some((group) => group.id === filter.group_id)) {
      filter.group_id = undefined
    }

    if (filter.tab_id && !allTabs.value.some((tab) => tab.id === filter.tab_id)) {
      filter.tab_id = undefined
    }
  } finally {
    loading.value = false
  }
}

function applyGroups(nextGroups: SystemConfigGroup[]) {
  groups.value = nextGroups
}

function typeLabel(type: string) {
  return typeOptions.value.find((item) => item.value === type)?.label || type
}

function parseOptions(raw: string) {
  if (!raw) {
    return []
  }

  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) {
      return parsed.map((item) => ({
        value: String(item.value ?? ''),
        label: String(item.label ?? item.value ?? ''),
      }))
    }
  } catch {
    // 兼容旧的每行 value,label 写法。
  }

  return raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => {
      const separator = line.includes('|') ? '|' : ','
      const [value, label] = line.split(separator)
      return {
        value: (value || '').trim(),
        label: (label || value || '').trim(),
      }
    })
}

function stringifyOptions() {
  const rows = optionRows.value
    .map((item) => ({
      value: item.value.trim(),
      label: item.label.trim(),
    }))
    .filter((item) => item.value && item.label)

  return JSON.stringify(rows, null, 2)
}

function addOptionRow() {
  optionRows.value.push({ value: '', label: '' })
}

function removeOptionRow(index: number) {
  optionRows.value.splice(index, 1)
}

function validateOptions() {
  if (!optionTypes.value.includes(itemForm.type)) {
    itemForm.options = ''
    return true
  }

  const rows = optionRows.value
    .map((item) => ({
      value: item.value.trim(),
      label: item.label.trim(),
    }))
    .filter((item) => item.value || item.label)

  if (rows.length === 0) {
    ElMessage.warning('请至少添加一个选项')
    return false
  }

  if (rows.some((item) => !item.value || !item.label)) {
    ElMessage.warning('选项值和选项名称都不能为空')
    return false
  }

  const values = rows.map((item) => item.value)
  if (new Set(values).size !== values.length) {
    ElMessage.warning('选项值不能重复')
    return false
  }

  itemForm.options = stringifyOptions()
  return true
}

function handleGroupChange() {
  if (filter.tab_id && !tabOptions.value.some((tab) => tab.id === filter.tab_id)) {
    filter.tab_id = undefined
  }
}

function openCreateGroup() {
  editingGroupId.value = null
  Object.assign(groupForm, { key: '', title: '', sort: 100, status: 1 })
  groupFormRef.value?.clearValidate()
  groupDialogVisible.value = true
}

function openEditGroup(group: SystemConfigGroup) {
  editingGroupId.value = group.id
  Object.assign(groupForm, {
    key: group.key,
    title: group.title,
    sort: group.sort,
    status: group.status,
  })
  groupFormRef.value?.clearValidate()
  groupDialogVisible.value = true
}

async function submitGroup() {
  await groupFormRef.value?.validate()
  const nextGroups = editingGroupId.value
    ? await updateSystemConfigGroup(editingGroupId.value, groupForm)
    : await createSystemConfigGroup(groupForm)
  applyGroups(nextGroups)
  groupDialogVisible.value = false
  ElMessage.success('保存成功')
}

async function handleDeleteGroup(group: SystemConfigGroup) {
  await ElMessageBox.confirm(`确定删除配置分组「${group.title}」吗？分组下的自定义标签和配置项也会删除。`, '删除确认', {
    type: 'warning',
  })
  applyGroups(await deleteSystemConfigGroup(group.id))
  ElMessage.success('删除成功')
}

function openCreateTab() {
  editingTabId.value = null
  Object.assign(tabForm, {
    group_id: filter.group_id || groups.value[0]?.id || 0,
    key: '',
    title: '',
    sort: 100,
    status: 1,
  })
  tabFormRef.value?.clearValidate()
  tabDialogVisible.value = true
}

function openEditTab(tab: SystemConfigTab) {
  editingTabId.value = tab.id
  Object.assign(tabForm, {
    group_id: tab.group_id,
    key: tab.key,
    title: tab.title,
    sort: tab.sort,
    status: tab.status,
  })
  tabFormRef.value?.clearValidate()
  tabDialogVisible.value = true
}

async function submitTab() {
  await tabFormRef.value?.validate()
  const nextGroups = editingTabId.value
    ? await updateSystemConfigTab(editingTabId.value, tabForm)
    : await createSystemConfigTab(tabForm)
  applyGroups(nextGroups)
  tabDialogVisible.value = false
  ElMessage.success('保存成功')
}

async function handleDeleteTab(tab: SystemConfigTab) {
  await ElMessageBox.confirm(`确定删除配置标签「${tab.title}」吗？标签下的自定义配置项也会删除。`, '删除确认', {
    type: 'warning',
  })
  applyGroups(await deleteSystemConfigTab(tab.id))
  ElMessage.success('删除成功')
}

function openCreateItem() {
  const firstTabId = filter.tab_id || tabOptions.value[0]?.id || allTabs.value[0]?.id || 0

  if (!firstTabId) {
    ElMessage.warning('请先创建配置 Tab')
    return
  }

  editingItemId.value = null
  optionRows.value = []
  Object.assign(itemForm, {
    tab_id: firstTabId,
    key: '',
    value: '',
    type: 'text',
    name: '',
    remark: '',
    options: '',
    sort: 100,
    status: 1,
  })
  itemFormRef.value?.clearValidate()
  itemDialogVisible.value = true
}

function openEditItem(item: SystemConfigItem) {
  editingItemId.value = item.id
  optionRows.value = parseOptions(item.options)
  Object.assign(itemForm, {
    tab_id: item.tab_id,
    key: item.key,
    value: item.value,
    type: item.type,
    name: item.name,
    remark: item.remark,
    options: item.options,
    sort: item.sort,
    status: item.status,
  })
  itemFormRef.value?.clearValidate()
  itemDialogVisible.value = true
}

async function submitItem() {
  await itemFormRef.value?.validate()
  if (!validateOptions()) {
    return
  }

  const nextGroups = editingItemId.value
    ? await updateSystemConfigItem(editingItemId.value, itemForm)
    : await createSystemConfigItem(itemForm)
  applyGroups(nextGroups)
  itemDialogVisible.value = false
  ElMessage.success('保存成功')
}

async function handleDeleteItem(item: SystemConfigItem) {
  await ElMessageBox.confirm(`确定删除配置项「${item.name}」吗？`, '删除确认', {
    type: 'warning',
  })
  applyGroups(await deleteSystemConfigItem(item.id))
  ElMessage.success('删除成功')
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <el-card v-loading="loading" class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">配置管理</div>
        <div class="toolbar-actions">
          <el-button @click="groupManageVisible = true">分组管理</el-button>
          <el-button @click="tabManageVisible = true">Tab 管理</el-button>
          <el-button v-if="canCreate" type="primary" @click="openCreateItem">新增配置项</el-button>
        </div>
      </div>
    </template>

    <div class="filter-bar">
      <el-select v-model="filter.group_id" clearable placeholder="全部分组" style="width: 180px" @change="handleGroupChange">
        <el-option v-for="group in groups" :key="group.id" :label="group.title" :value="group.id" />
      </el-select>
      <el-select v-model="filter.tab_id" clearable placeholder="全部 Tab" style="width: 180px">
        <el-option
          v-for="tab in tabOptions"
          :key="tab.id"
          :label="`${tab.group_title} / ${tab.title}`"
          :value="tab.id"
        />
      </el-select>
      <el-input v-model="filter.keyword" clearable placeholder="搜索名称 / 键 / 备注" style="width: 240px" />
    </div>

    <el-table :data="filteredItems" border height="100%">
      <el-table-column prop="name" label="配置名称" min-width="130" />
      <el-table-column prop="key" label="配置键" min-width="150" />
      <el-table-column prop="group_title" label="分组" min-width="120" />
      <el-table-column prop="tab_title" label="Tab" min-width="120" />
      <el-table-column label="类型" width="120">
        <template #default="{ row }">{{ typeLabel(row.type) }}</template>
      </el-table-column>
      <el-table-column prop="sort" label="排序" width="70" />
      <el-table-column label="系统" width="75">
        <template #default="{ row }">
          <el-tag :type="row.is_system === 1 ? 'warning' : 'info'" effect="plain">
            {{ row.is_system === 1 ? '是' : '否' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="75">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="remark" label="备注" min-width="180" show-overflow-tooltip />
      <el-table-column prop="create_time" label="创建时间" min-width="160" />
      <el-table-column prop="update_time" label="修改时间" min-width="160" />
      <el-table-column label="操作" width="130" fixed="right">
        <template #default="{ row }">
          <el-button v-if="canUpdate" link type="primary" @click="openEditItem(row)">编辑</el-button>
          <el-button v-if="canDelete && row.is_system !== 1" link type="danger" @click="handleDeleteItem(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="groupManageVisible" title="分组管理" width="980px">
      <div class="dialog-toolbar">
        <el-button v-if="canCreate" type="primary" @click="openCreateGroup">新增分组</el-button>
      </div>
      <el-table :data="groups" border height="420px">
        <el-table-column prop="title" label="分组名称" min-width="140" />
        <el-table-column prop="key" label="标识" min-width="130" />
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="系统" width="80">
          <template #default="{ row }">
            <el-tag :type="row.is_system === 1 ? 'warning' : 'info'" effect="plain">
              {{ row.is_system === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="创建时间" min-width="160" />
        <el-table-column prop="update_time" label="修改时间" min-width="160" />
        <el-table-column label="操作" width="130" fixed="right">
          <template #default="{ row }">
            <el-button v-if="canUpdate" link type="primary" @click="openEditGroup(row)">编辑</el-button>
            <el-button v-if="canDelete && row.is_system !== 1" link type="danger" @click="handleDeleteGroup(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>

    <el-dialog v-model="tabManageVisible" title="Tab 管理" width="1040px">
      <div class="dialog-toolbar">
        <el-button v-if="canCreate" type="primary" @click="openCreateTab">新增 Tab</el-button>
      </div>
      <el-table :data="allTabs" border height="420px">
        <el-table-column prop="title" label="Tab 名称" min-width="130" />
        <el-table-column prop="key" label="标识" min-width="120" />
        <el-table-column prop="group_title" label="所属分组" min-width="130" />
        <el-table-column prop="sort" label="排序" width="70" />
        <el-table-column label="系统" width="75">
          <template #default="{ row }">
            <el-tag :type="row.is_system === 1 ? 'warning' : 'info'" effect="plain">
              {{ row.is_system === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="75">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="创建时间" min-width="150" />
        <el-table-column prop="update_time" label="修改时间" min-width="150" />
        <el-table-column label="操作" width="130" fixed="right">
          <template #default="{ row }">
            <el-button v-if="canUpdate" link type="primary" @click="openEditTab(row)">编辑</el-button>
            <el-button v-if="canDelete && row.is_system !== 1" link type="danger" @click="handleDeleteTab(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>

    <el-dialog v-model="groupDialogVisible" :title="editingGroupId ? '编辑分组' : '新增分组'" width="460px">
      <el-form ref="groupFormRef" :model="groupForm" :rules="groupRules" label-width="100px">
        <el-form-item label="分组标识" prop="key">
          <el-input v-model="groupForm.key" :disabled="Boolean(editingGroupId)" placeholder="如 frontend / shop" />
        </el-form-item>
        <el-form-item label="分组名称" prop="title">
          <el-input v-model="groupForm.title" placeholder="如 前台配置 / 商城设置" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="groupForm.sort" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="groupForm.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="groupDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitGroup">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="tabDialogVisible" :title="editingTabId ? '编辑 Tab' : '新增 Tab'" width="460px">
      <el-form ref="tabFormRef" :model="tabForm" :rules="tabRules" label-width="100px">
        <el-form-item label="所属分组" prop="group_id">
          <el-select v-model="tabForm.group_id" :disabled="Boolean(editingTabId)" style="width: 100%">
            <el-option v-for="group in groups" :key="group.id" :label="group.title" :value="group.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="Tab 标识" prop="key">
          <el-input v-model="tabForm.key" :disabled="Boolean(editingTabId)" placeholder="如 website / other" />
        </el-form-item>
        <el-form-item label="Tab 名称" prop="title">
          <el-input v-model="tabForm.title" placeholder="如 网站信息 / 其他设置" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="tabForm.sort" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="tabForm.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="tabDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitTab">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="itemDialogVisible" :title="editingItemId ? '编辑配置项' : '新增配置项'" width="560px">
      <el-form ref="itemFormRef" :model="itemForm" :rules="itemRules" label-width="100px">
        <el-form-item label="所属 Tab" prop="tab_id">
          <el-select v-model="itemForm.tab_id" :disabled="Boolean(editingItemId)" filterable style="width: 100%">
            <el-option
              v-for="tab in allTabs"
              :key="tab.id"
              :label="`${tab.group_title} / ${tab.title}`"
              :value="tab.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="配置键" prop="key">
          <el-input v-model="itemForm.key" :disabled="Boolean(editingItemId)" placeholder="如 site_title" />
        </el-form-item>
        <el-form-item label="配置名称" prop="name">
          <el-input v-model="itemForm.name" placeholder="如 网站标题" />
        </el-form-item>
        <el-form-item label="配置类型" prop="type">
          <el-select v-model="itemForm.type" :disabled="Boolean(editingItemId)" style="width: 100%">
            <el-option v-for="item in typeOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item v-if="optionTypes.includes(itemForm.type)" label="选项配置">
          <div class="option-editor">
            <div v-for="(option, index) in optionRows" :key="index" class="option-row">
              <el-input v-model="option.value" placeholder="选项值" />
              <el-input v-model="option.label" placeholder="选项名称" />
              <el-button type="danger" plain @click="removeOptionRow(index)">删除</el-button>
            </div>
            <el-button @click="addOptionRow">添加选项</el-button>
          </div>
        </el-form-item>
        <el-form-item label="默认值">
          <ConfigValueControl v-model="itemForm.value" :item="defaultValueItem" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="itemForm.remark" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="itemForm.sort" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="itemForm.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="itemDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitItem">保存</el-button>
      </template>
    </el-dialog>
  </el-card>
</template>

<style scoped>
.toolbar-actions,
.filter-bar,
.dialog-toolbar {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.filter-bar {
  margin-bottom: 12px;
}

.dialog-toolbar {
  margin-bottom: 12px;
}

.option-editor {
  width: 100%;
}

.option-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
  gap: 8px;
  margin-bottom: 8px;
}

@media (max-width: 720px) {
  .filter-bar {
    display: grid;
    grid-template-columns: 1fr;
  }

  .filter-bar :deep(.el-select),
  .filter-bar :deep(.el-input) {
    width: 100% !important;
  }

  .option-row {
    grid-template-columns: 1fr;
  }
}
</style>
