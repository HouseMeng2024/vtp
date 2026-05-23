<script setup lang="ts">
import { nextTick, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  batchDeleteRoles,
  batchUpdateRoleStatus,
  createRole,
  deleteRole,
  fetchRoleMenus,
  fetchRoles,
  updateRole,
  updateRoleMenus,
  updateRoleStatus,
  type AdminRolePayload,
  type AdminRoleRow,
} from '../../../api/role'
import { fetchMenus } from '../../../api/menu'
import { useAuthStore } from '../../../stores/auth'
import type { AdminMenu } from '../../../types/auth'

const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const permissionSaving = ref(false)
const dialogVisible = ref(false)
const permissionVisible = ref(false)
const editingId = ref<number | null>(null)
const permissionRole = ref<AdminRoleRow | null>(null)
const formRef = ref<FormInstance>()
const menuTreeRef = ref()
const rows = ref<AdminRoleRow[]>([])
const selectedRows = ref<AdminRoleRow[]>([])
const menuRows = ref<AdminMenu[]>([])
const checkedMenuIds = ref<number[]>([])
const total = ref(0)
const query = reactive({
  page: 1,
  limit: 20,
  keyword: '',
})
const form = reactive<AdminRolePayload>({
  name: '',
  code: '',
  sort: 100,
  status: 1,
  data_scope: 'all',
  remark: '',
})
const rules: FormRules = {
  name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入角色标识', trigger: 'blur' }],
}

async function loadData() {
  loading.value = true
  try {
    const data = await fetchRoles(query)
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
    name: '',
    code: '',
    sort: 100,
    status: 1,
    data_scope: 'all',
    remark: '',
  })
  formRef.value?.clearValidate()
}

function openCreate() {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: AdminRoleRow) {
  resetForm()
  editingId.value = row.id
  Object.assign(form, {
    name: row.name,
    code: row.code,
    sort: row.sort,
    status: row.status,
    data_scope: row.data_scope || 'all',
    remark: row.remark,
  })
  dialogVisible.value = true
}

async function submitForm() {
  await formRef.value?.validate()
  saving.value = true

  try {
    if (editingId.value) {
      await updateRole(editingId.value, form)
      ElMessage.success('保存成功')
    } else {
      await createRole(form)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function handleStatusChange(row: AdminRoleRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateRoleStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success('状态已更新')
}

async function handleDelete(row: AdminRoleRow) {
  await ElMessageBox.confirm(`确定删除角色「${row.name}」吗？`, '删除确认', {
    type: 'warning',
  })
  await deleteRole(row.id)
  ElMessage.success('删除成功')
  loadData()
}

function selectedIds() {
  return selectedRows.value.map((row) => row.id)
}

function handleSelectionChange(selection: AdminRoleRow[]) {
  selectedRows.value = selection
}

async function handleBatchStatus(status: number) {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择角色')
    return
  }

  await batchUpdateRoleStatus(selectedIds(), status)
  ElMessage.success('批量状态已更新')
  loadData()
}

async function handleBatchDelete() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择角色')
    return
  }

  await ElMessageBox.confirm(`确定删除选中的 ${selectedRows.value.length} 个角色吗？`, '批量删除确认', {
    type: 'warning',
  })
  await batchDeleteRoles(selectedIds())
  ElMessage.success('批量删除成功')
  loadData()
}

async function openPermission(row: AdminRoleRow) {
  permissionRole.value = row
  permissionVisible.value = true

  if (menuRows.value.length === 0) {
    menuRows.value = await fetchMenus()
  }

  const data = await fetchRoleMenus(row.id)
  checkedMenuIds.value = data.menu_ids
  await nextTick()
  menuTreeRef.value?.setCheckedKeys(leafCheckedIds(menuRows.value, checkedMenuIds.value))
}

async function submitPermission() {
  if (!permissionRole.value) {
    return
  }

  permissionSaving.value = true
  try {
    const checked = menuTreeRef.value?.getCheckedKeys(false) || []
    const halfChecked = menuTreeRef.value?.getHalfCheckedKeys() || []
    const menuIds = Array.from(new Set([...checked, ...halfChecked].map((id) => Number(id))))
    await updateRoleMenus(permissionRole.value.id, menuIds)
    ElMessage.success('权限已保存')
    permissionVisible.value = false
  } finally {
    permissionSaving.value = false
  }
}

function leafCheckedIds(nodes: AdminMenu[], checkedIds: number[]) {
  const checkedSet = new Set(checkedIds.map((id) => Number(id)))
  const result: number[] = []

  function walk(node: AdminMenu): boolean {
    const children = node.children || []

    if (children.length === 0) {
      if (checkedSet.has(node.id)) {
        result.push(node.id)
        return true
      }

      return false
    }

    const hasCheckedChild = children.map(walk).some(Boolean)

    if (!hasCheckedChild && checkedSet.has(node.id)) {
      result.push(node.id)
      return true
    }

    return hasCheckedChild || checkedSet.has(node.id)
  }

  nodes.forEach(walk)

  return result
}

onMounted(loadData)
</script>

<template>
  <el-card class="page-card table-page-card" shadow="never">
    <template #header>
      <div class="page-toolbar">
        <div class="page-title">角色管理</div>
        <el-space>
          <el-button
            v-if="authStore.hasPermission('admin:role:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            批量启用
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:role:status')"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            批量禁用
          </el-button>
          <el-button
            v-if="authStore.hasPermission('admin:role:delete')"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            批量删除
          </el-button>
          <el-button v-if="authStore.hasPermission('admin:role:create')" type="primary" @click="openCreate">新增</el-button>
        </el-space>
      </div>
    </template>

    <el-form class="page-search" inline @submit.prevent>
      <el-form-item label="关键词">
        <el-input v-model="query.keyword" clearable placeholder="角色名称 / 标识" @keyup.enter="handleSearch" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </el-form-item>
    </el-form>

    <div class="table-scroll">
      <el-table v-loading="loading" :data="rows" border height="100%" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="48" />
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="name" label="角色名称" min-width="150" />
        <el-table-column prop="code" label="角色标识" min-width="160" />
        <el-table-column label="数据权限" width="120">
          <template #default="{ row }">
            <el-tag :type="row.data_scope === 'all' ? 'primary' : 'warning'">
              {{ row.data_scope === 'all' ? '全部数据' : '仅本人数据' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="100" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="remark" label="备注" min-width="180" />
        <el-table-column prop="create_time" label="创建时间" min-width="170" />
        <el-table-column label="操作" width="210" fixed="right">
          <template #default="{ row }">
            <el-space class="table-actions">
              <el-button v-if="authStore.hasPermission('admin:role:update')" link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button v-if="authStore.hasPermission('admin:role:permission')" link type="primary" @click="openPermission(row)">权限</el-button>
              <el-button v-if="authStore.hasPermission('admin:role:status')" link type="primary" @click="handleStatusChange(row)">
                {{ row.status === 1 ? '禁用' : '启用' }}
              </el-button>
              <el-button v-if="authStore.hasPermission('admin:role:delete')" link type="danger" @click="handleDelete(row)">删除</el-button>
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

    <el-dialog v-model="dialogVisible" :title="editingId ? '编辑角色' : '新增角色'" width="520px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="角色名称" prop="name">
          <el-input v-model="form.name" maxlength="50" />
        </el-form-item>
        <el-form-item label="角色标识" prop="code">
          <el-input v-model="form.code" maxlength="50" placeholder="如 editor" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio-button :value="1">正常</el-radio-button>
            <el-radio-button :value="0">禁用</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="数据权限">
          <el-radio-group v-model="form.data_scope">
            <el-radio-button value="all">全部数据</el-radio-button>
            <el-radio-button value="self">仅本人数据</el-radio-button>
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

    <el-dialog v-model="permissionVisible" :title="`分配权限：${permissionRole?.name || ''}`" width="560px">
      <el-tree
        ref="menuTreeRef"
        :data="menuRows"
        node-key="id"
        show-checkbox
        default-expand-all
        :props="{ label: 'title', children: 'children' }"
      />
      <template #footer>
        <el-button @click="permissionVisible = false">取消</el-button>
        <el-button type="primary" :loading="permissionSaving" @click="submitPermission">保存</el-button>
      </template>
    </el-dialog>
  </el-card>
</template>
