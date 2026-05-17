<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { createMenu, deleteMenu, fetchMenus, updateMenu, type AdminMenuPayload } from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'
import type { AdminMenu } from '../../../types/auth'

const authStore = useAuthStore()
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
const rules: FormRules = {
  title: [{ required: true, message: '请输入菜单名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
}

const parentOptions = computed(() => [
  {
    id: 0,
    title: '顶级菜单',
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
      ElMessage.success('保存成功')
    } else {
      await createMenu(form)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row: AdminMenu) {
  await ElMessageBox.confirm(`确定删除菜单「${row.title}」吗？`, '删除确认', {
    type: 'warning',
  })
  await deleteMenu(row.id)
  ElMessage.success('删除成功')
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
          <div class="page-title">菜单管理</div>
        <el-button v-if="authStore.hasPermission('admin:menu:create')" type="primary" @click="openCreate()">新增</el-button>
      </div>
    </template>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" row-key="id" border default-expand-all height="100%">
        <el-table-column prop="title" label="名称" min-width="180" />
        <el-table-column prop="permission" label="权限标识" min-width="180" />
        <el-table-column prop="path" label="路由路径" min-width="180" />
        <el-table-column prop="component" label="组件路径" min-width="180" />
        <el-table-column prop="icon" label="图标" width="120" />
        <el-table-column prop="sort" label="排序" width="90" />
        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag>{{ row.type === 1 ? '目录' : row.type === 2 ? '菜单' : '按钮' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="显示" width="90">
          <template #default="{ row }">
            <el-tag :type="row.visible === 1 ? 'success' : 'info'">
              {{ row.visible === 1 ? '显示' : '隐藏' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:menu:create')" link type="primary" @click="openCreate(row.id)">新增</el-button>
              <el-button v-if="authStore.hasPermission('admin:menu:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button v-if="authStore.hasPermission('admin:menu:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
            </el-space>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑菜单' : '新增菜单'" width="620px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="父级菜单">
          <el-tree-select
            v-model="form.parent_id"
            :data="parentOptions"
            node-key="id"
            check-strictly
            :render-after-expand="false"
            :props="{ label: 'title', children: 'children' }"
          />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio-button :value="1">目录</el-radio-button>
            <el-radio-button :value="2">菜单</el-radio-button>
            <el-radio-button :value="3">按钮</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="名称" prop="title">
          <el-input v-model="form.title" maxlength="50" />
        </el-form-item>
        <el-form-item label="权限标识">
          <el-input v-model="form.permission" maxlength="100" placeholder="如 admin:user:list" />
        </el-form-item>
        <el-form-item label="路由路径">
          <el-input v-model="form.path" maxlength="255" placeholder="如 /permission/users" />
        </el-form-item>
        <el-form-item label="组件路径">
          <el-autocomplete
            v-model="form.component"
            class="full"
            :fetch-suggestions="queryComponents"
            maxlength="255"
            placeholder="如 system/user/index"
          />
        </el-form-item>
        <el-form-item label="图标">
          <el-select v-model="form.icon" class="full" clearable filterable allow-create placeholder="请选择图标">
            <el-option v-for="icon in iconOptions" :key="icon" :label="icon" :value="icon" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item label="显示">
          <el-radio-group v-model="form.visible">
            <el-radio-button :value="1">显示</el-radio-button>
            <el-radio-button :value="0">隐藏</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio-button :value="1">正常</el-radio-button>
            <el-radio-button :value="0">禁用</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" maxlength="255" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </el-card>
</template>

<style scoped>
.full {
  width: 100%;
}
</style>
