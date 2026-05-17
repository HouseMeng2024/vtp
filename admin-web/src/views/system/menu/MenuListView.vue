<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { createMenu, deleteMenu, fetchMenus, updateMenu, type AdminMenuPayload } from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'
import type { AdminMenu } from '../../../types/auth'

const authStore = useAuthStore()
const { t } = useI18n()
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const rows = ref<AdminMenu[]>([])
const iconOptions = [
  'House',
  'Setting',
  'User',
  'UserFilled',
  'Menu',
  'Tools',
  'FolderOpened',
  'Tickets',
  'Document',
  'DocumentChecked',
  'Key',
  'Operation',
  'DataAnalysis',
]
const componentOptions = [
  'dashboard/DashboardView',
  'system/user/index',
  'system/role/index',
  'system/menu/index',
  'system/config/index',
  'system/config-manage/index',
  'system/file/index',
  'system/dict/index',
  'system/tool/index',
  'system/login-log/index',
  'system/operate-log/index',
]
const form = reactive<AdminMenuPayload>({
  parent_id: 0,
  type: 2,
  title: '',
  permission: '',
  path: '',
  component: '',
  icon: '',
  sort: 100,
  visible: 1,
  status: 1,
  remark: '',
})
const rules = computed<FormRules>(() => ({
  title: [{ required: true, message: t('menu.nameRequired'), trigger: 'blur' }],
  type: [{ required: true, message: t('menu.typeRequired'), trigger: 'change' }],
}))

const parentOptions = computed(() => [
  {
    id: 0,
    title: t('menu.topMenu'),
    children: rows.value,
  },
])

async function loadData() {
  loading.value = true
  try {
    rows.value = await fetchMenus()
  } finally {
    loading.value = false
  }
}

function resetForm(parentId = 0) {
  editingId.value = null
  Object.assign(form, {
    parent_id: parentId,
    type: 2,
    title: '',
    permission: '',
    path: '',
    component: '',
    icon: '',
    sort: 100,
    visible: 1,
    status: 1,
    remark: '',
  })
  formRef.value?.clearValidate()
}

function openCreate(parentId = 0) {
  resetForm(parentId)
  dialogVisible.value = true
}

function openEdit(row: AdminMenu) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
    parent_id: row.parent_id,
    type: row.type,
    title: row.title,
    permission: row.permission,
    path: row.path,
    component: row.component,
    icon: row.icon,
    sort: row.sort,
    visible: row.visible,
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
      await updateMenu(editingId.value, form)
      ElMessage.success(t('common.saved'))
    } else {
      await createMenu(form)
      ElMessage.success(t('common.created'))
    }

    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row: AdminMenu) {
  await ElMessageBox.confirm(t('menu.deleteConfirm', { name: row.title }), t('common.deleteConfirmation'), {
    type: 'warning',
  })
  await deleteMenu(row.id)
  ElMessage.success(t('configManage.deleted'))
  loadData()
}

function queryComponents(query: string, callback: (items: { value: string }[]) => void) {
  callback(componentOptions.filter((item) => item.includes(query)).map((value) => ({ value })))
}

onMounted(loadData)
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
        <div class="page-toolbar">
          <div class="page-title">{{ t('menu.menuManagement') }}</div>
        <el-button v-if="authStore.hasPermission('admin:menu:create')" type="primary" @click="openCreate()">{{ t('common.create') }}</el-button>
      </div>
    </template>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" row-key="id" border default-expand-all height="100%">
        <el-table-column prop="title" :label="t('configManage.groupName')" min-width="180" />
        <el-table-column prop="permission" :label="t('menu.permission')" min-width="180" />
        <el-table-column prop="path" :label="t('menu.routePath')" min-width="180" />
        <el-table-column prop="component" :label="t('menu.componentPath')" min-width="180" />
        <el-table-column prop="icon" :label="t('menu.icon')" width="120" />
        <el-table-column prop="sort" :label="t('configManage.sort')" width="90" />
        <el-table-column :label="t('file.type')" width="100">
          <template #default="{ row }">
            <el-tag>{{ row.type === 1 ? t('menu.directory') : row.type === 2 ? t('menu.menu') : t('menu.button') }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('menu.show')" width="90">
          <template #default="{ row }">
            <el-tag :type="row.visible === 1 ? 'success' : 'info'">
              {{ row.visible === 1 ? t('menu.show') : t('menu.hide') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('common.status')" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? t('common.enabled') : t('common.disabled') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="180" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:menu:create')" link type="primary" @click="openCreate(row.id)">{{ t('common.create') }}</el-button>
              <el-button v-if="authStore.hasPermission('admin:menu:update')" link type="primary" @click="openEdit(row)">{{ t('common.edit') }}</el-button>
              <el-button v-if="authStore.hasPermission('admin:menu:delete')" link type="danger" @click="handleDelete(row)">{{ t('common.delete') }}</el-button>
            </el-space>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogVisible" :title="editingId ? t('menu.editMenu') : t('menu.createMenu')" width="620px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item :label="t('menu.parentMenu')">
          <el-tree-select
            v-model="form.parent_id"
            :data="parentOptions"
            node-key="id"
            check-strictly
            :render-after-expand="false"
            :props="{ label: 'title', children: 'children' }"
          />
        </el-form-item>
        <el-form-item :label="t('file.type')" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio-button :value="1">{{ t('menu.directory') }}</el-radio-button>
            <el-radio-button :value="2">{{ t('menu.menu') }}</el-radio-button>
            <el-radio-button :value="3">{{ t('menu.button') }}</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item :label="t('configManage.groupName')" prop="title">
          <el-input v-model="form.title" maxlength="50" />
        </el-form-item>
        <el-form-item :label="t('menu.permission')">
          <el-input v-model="form.permission" maxlength="100" placeholder="e.g. admin:user:list" />
        </el-form-item>
        <el-form-item :label="t('menu.routePath')">
          <el-input v-model="form.path" maxlength="255" placeholder="e.g. /permission/users" />
        </el-form-item>
        <el-form-item :label="t('menu.componentPath')">
          <el-autocomplete
            v-model="form.component"
            class="full"
            :fetch-suggestions="queryComponents"
            maxlength="255"
            placeholder="e.g. system/user/index"
          />
        </el-form-item>
        <el-form-item :label="t('menu.icon')">
          <el-select v-model="form.icon" class="full" clearable filterable allow-create :placeholder="t('menu.selectIcon')">
            <el-option v-for="icon in iconOptions" :key="icon" :label="icon" :value="icon" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('configManage.sort')">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item :label="t('menu.show')">
          <el-radio-group v-model="form.visible">
            <el-radio-button :value="1">{{ t('menu.show') }}</el-radio-button>
            <el-radio-button :value="0">{{ t('menu.hide') }}</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item :label="t('common.status')">
          <el-radio-group v-model="form.status">
            <el-radio-button :value="1">{{ t('common.enabled') }}</el-radio-button>
            <el-radio-button :value="0">{{ t('common.disabled') }}</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item :label="t('configManage.remark')">
          <el-input v-model="form.remark" type="textarea" maxlength="255" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">{{ t('common.save') }}</el-button>
      </template>
    </el-dialog>
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}
</style>
