<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  createDictData,
  createDictType,
  deleteDictData,
  deleteDictType,
  fetchDictData,
  fetchDictTypes,
  updateDictData,
  updateDictDataStatus,
  updateDictType,
  updateDictTypeStatus,
  type DictDataPayload,
  type DictDataRow,
  type DictTypePayload,
  type DictTypeRow,
} from '../../../api/system'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const { t } = useI18n()
const typeLoading = ref(false)
const dataLoading = ref(false)
const savingType = ref(false)
const savingData = ref(false)
const typeDialogVisible = ref(false)
const dataDialogVisible = ref(false)
const editingTypeId = ref<number | null>(null)
const editingDataId = ref<number | null>(null)
const selectedType = ref<DictTypeRow | null>(null)
const typeFormRef = ref<FormInstance>()
const dataFormRef = ref<FormInstance>()
const typeRows = ref<DictTypeRow[]>([])
const dataRows = ref<DictDataRow[]>([])
const typeTotal = ref(0)
const dataTotal = ref(0)
const typeQuery = reactive({
  page: 1,
  limit: 10,
  keyword: '',
})
const dataQuery = reactive({
  page: 1,
  limit: 10,
  keyword: '',
})
const typeForm = reactive<DictTypePayload>({
  name: '',
  type: '',
  sort: 100,
  status: 1,
  remark: '',
})
const dataForm = reactive<DictDataPayload>({
  type_id: 0,
  label: '',
  value: '',
  tag_type: '',
  sort: 100,
  status: 1,
  remark: '',
})
const typeRules = computed<FormRules>(() => ({
  name: [{ required: true, message: t('dict.typeNameRequired'), trigger: 'blur' }],
  type: [{ required: true, message: t('dict.typeKeyRequired'), trigger: 'blur' }],
}))
const dataRules = computed<FormRules>(() => ({
  label: [{ required: true, message: t('dict.dataLabelRequired'), trigger: 'blur' }],
  value: [{ required: true, message: t('dict.dataValueRequired'), trigger: 'blur' }],
}))
const canCreate = computed(() => authStore.hasPermission('admin:dict:create'))
const canUpdate = computed(() => authStore.hasPermission('admin:dict:update'))
const canStatus = computed(() => authStore.hasPermission('admin:dict:status'))
const canDelete = computed(() => authStore.hasPermission('admin:dict:delete'))
const tagOptions = computed(() => [
  { label: t('dict.default'), value: '' },
  { label: t('common.success'), value: 'success' },
  { label: 'Info', value: 'info' },
  { label: 'Warning', value: 'warning' },
  { label: 'Danger', value: 'danger' },
])

async function loadTypes() {
  typeLoading.value = true
  try {
    const data = await fetchDictTypes(typeQuery)
    typeRows.value = data.data
    typeTotal.value = data.total

    if (!selectedType.value && typeRows.value.length > 0) {
      selectType(typeRows.value[0])
    }
  } finally {
    typeLoading.value = false
  }
}

async function loadData() {
  if (!selectedType.value) {
    dataRows.value = []
    dataTotal.value = 0
    return
  }

  dataLoading.value = true
  try {
    const data = await fetchDictData({
      ...dataQuery,
      type_id: selectedType.value.id,
    })
    dataRows.value = data.data
    dataTotal.value = data.total
  } finally {
    dataLoading.value = false
  }
}

function handleTypeSearch() {
  selectedType.value = null
  typeQuery.page = 1
  loadTypes()
}

function handleDataSearch() {
  dataQuery.page = 1
  loadData()
}

function selectType(row: DictTypeRow) {
  selectedType.value = row
  dataQuery.page = 1
  dataQuery.keyword = ''
  loadData()
}

function resetTypeForm() {
  editingTypeId.value = null
  Object.assign(typeForm, {
    name: '',
    type: '',
    sort: 100,
    status: 1,
    remark: '',
  })
  typeFormRef.value?.clearValidate()
}

function resetDataForm() {
  editingDataId.value = null
  Object.assign(dataForm, {
    type_id: selectedType.value?.id || 0,
    label: '',
    value: '',
    tag_type: '',
    sort: 100,
    status: 1,
    remark: '',
  })
  dataFormRef.value?.clearValidate()
}

function openCreateType() {
  resetTypeForm()
  typeDialogVisible.value = true
}

function openEditType(row: DictTypeRow) {
  resetTypeForm()
  editingTypeId.value = row.id
  Object.assign(typeForm, {
    name: row.name,
    type: row.type,
    sort: row.sort,
    status: row.status,
    remark: row.remark,
  })
  typeDialogVisible.value = true
}

function openCreateData() {
  if (!selectedType.value) {
    ElMessage.warning(t('dict.selectTypeFirst'))
    return
  }

  resetDataForm()
  dataDialogVisible.value = true
}

function openEditData(row: DictDataRow) {
  resetDataForm()
  editingDataId.value = row.id
  Object.assign(dataForm, {
    type_id: row.type_id,
    label: row.label,
    value: row.value,
    tag_type: row.tag_type,
    sort: row.sort,
    status: row.status,
    remark: row.remark,
  })
  dataDialogVisible.value = true
}

async function submitType() {
  await typeFormRef.value?.validate()
  savingType.value = true

  try {
    if (editingTypeId.value) {
      await updateDictType(editingTypeId.value, typeForm)
      ElMessage.success(t('common.saved'))
    } else {
      await createDictType(typeForm)
      ElMessage.success(t('common.created'))
    }

    typeDialogVisible.value = false
    await loadTypes()
  } finally {
    savingType.value = false
  }
}

async function submitData() {
  await dataFormRef.value?.validate()
  savingData.value = true

  try {
    if (editingDataId.value) {
      await updateDictData(editingDataId.value, dataForm)
      ElMessage.success(t('common.saved'))
    } else {
      await createDictData(dataForm)
      ElMessage.success(t('common.created'))
    }

    dataDialogVisible.value = false
    loadData()
  } finally {
    savingData.value = false
  }
}

async function handleTypeStatus(row: DictTypeRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateDictTypeStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success(t('common.statusUpdated'))
}

async function handleDataStatus(row: DictDataRow) {
  const nextStatus = row.status === 1 ? 0 : 1
  await updateDictDataStatus(row.id, nextStatus)
  row.status = nextStatus
  ElMessage.success(t('common.statusUpdated'))
}

async function handleDeleteType(row: DictTypeRow) {
  await ElMessageBox.confirm(t('dict.typeDeleteConfirm', { name: row.name }), t('common.deleteConfirmation'), {
    type: 'warning',
  })
  await deleteDictType(row.id)
  ElMessage.success(t('configManage.deleted'))

  if (selectedType.value?.id === row.id) {
    selectedType.value = null
  }

  loadTypes()
  loadData()
}

async function handleDeleteData(row: DictDataRow) {
  await ElMessageBox.confirm(t('dict.dataDeleteConfirm', { name: row.label }), t('common.deleteConfirmation'), {
    type: 'warning',
  })
  await deleteDictData(row.id)
  ElMessage.success(t('configManage.deleted'))
  loadData()
}

onMounted(loadTypes)
</script>

<template>
  <div class="dict-page">
    <el-card class="dict-type-card table-page-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">{{ t('dict.types') }}</div>
          <el-button v-if="canCreate" type="primary" @click="openCreateType">{{ t('common.create') }}</el-button>
        </div>
      </template>

      <el-form class="page-search" inline @submit.prevent>
        <el-form-item>
          <el-input v-model="typeQuery.keyword" clearable :placeholder="t('dict.nameKey')" @keyup.enter="handleTypeSearch" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleTypeSearch">{{ t('common.search') }}</el-button>
        </el-form-item>
      </el-form>

      <div class="table-scroll">
        <el-table
          v-loading="typeLoading"
          :data="typeRows"
          row-key="id"
          highlight-current-row
          border
          height="100%"
          @row-click="selectType"
        >
          <el-table-column prop="name" :label="t('dict.name')" min-width="130" />
          <el-table-column prop="type" :label="t('dict.key')" min-width="150" />
          <el-table-column :label="t('common.status')" width="86">
            <template #default="{ row }">
              <el-tag :type="row.status === 1 ? 'success' : 'info'">
                {{ row.status === 1 ? t('common.enabled') : t('common.disabled') }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column :label="t('common.actions')" width="170" fixed="right">
            <template #default="{ row }">
              <el-space class="table-actions">
                <el-button v-if="canUpdate" link type="primary" @click.stop="openEditType(row)">{{ t('common.edit') }}</el-button>
                <el-button v-if="canStatus" link type="primary" @click.stop="handleTypeStatus(row)">
                  {{ row.status === 1 ? t('common.disabled') : t('common.enable') }}
                </el-button>
                <el-button v-if="canDelete" link type="danger" @click.stop="handleDeleteType(row)">{{ t('common.delete') }}</el-button>
              </el-space>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <el-pagination
        v-model:current-page="typeQuery.page"
        v-model:page-size="typeQuery.limit"
        class="page-pagination"
        layout="total, sizes, prev, pager, next, jumper"
        :total="typeTotal"
        :page-sizes="[10, 20, 50, 100]"
        @size-change="loadTypes"
        @current-change="loadTypes"
      />
    </el-card>

    <el-card class="dict-data-card table-page-card" shadow="never">
      <template #header>
        <div class="page-toolbar">
          <div class="page-title">
            {{ t('dict.data') }}
            <span v-if="selectedType" class="dict-subtitle">{{ selectedType.name }}</span>
          </div>
          <el-button v-if="canCreate" type="primary" :disabled="!selectedType" @click="openCreateData">{{ t('common.create') }}</el-button>
        </div>
      </template>

      <el-form class="page-search" inline @submit.prevent>
        <el-form-item>
          <el-input v-model="dataQuery.keyword" clearable :placeholder="t('dict.labelValue')" @keyup.enter="handleDataSearch" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :disabled="!selectedType" @click="handleDataSearch">{{ t('common.search') }}</el-button>
        </el-form-item>
      </el-form>

      <div class="table-scroll">
        <el-table v-loading="dataLoading" :data="dataRows" row-key="id" border height="100%">
          <el-table-column prop="label" :label="t('dict.label')" min-width="140" />
          <el-table-column prop="value" :label="t('dict.value')" min-width="120" />
          <el-table-column :label="t('dict.tagStyle')" width="110">
            <template #default="{ row }">
              <el-tag :type="row.tag_type || undefined">{{ row.tag_type || 'default' }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="sort" :label="t('configManage.sort')" width="90" />
          <el-table-column :label="t('common.status')" width="90">
            <template #default="{ row }">
              <el-tag :type="row.status === 1 ? 'success' : 'info'">
                {{ row.status === 1 ? t('common.enabled') : t('common.disabled') }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="remark" :label="t('configManage.remark')" min-width="160" />
          <el-table-column :label="t('common.actions')" width="170" fixed="right">
            <template #default="{ row }">
              <el-space class="table-actions">
                <el-button v-if="canUpdate" link type="primary" @click="openEditData(row)">{{ t('common.edit') }}</el-button>
                <el-button v-if="canStatus" link type="primary" @click="handleDataStatus(row)">
                  {{ row.status === 1 ? t('common.disabled') : t('common.enable') }}
                </el-button>
                <el-button v-if="canDelete" link type="danger" @click="handleDeleteData(row)">{{ t('common.delete') }}</el-button>
              </el-space>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <el-pagination
        v-model:current-page="dataQuery.page"
        v-model:page-size="dataQuery.limit"
        class="page-pagination"
        layout="total, sizes, prev, pager, next, jumper"
        :total="dataTotal"
        :page-sizes="[10, 20, 50, 100]"
        @size-change="loadData"
        @current-change="loadData"
      />
    </el-card>

    <el-dialog v-model="typeDialogVisible" :title="editingTypeId ? t('dict.editType') : t('dict.createType')" width="520px">
      <el-form ref="typeFormRef" :model="typeForm" :rules="typeRules" label-width="90px">
        <el-form-item :label="t('dict.name')" prop="name">
          <el-input v-model="typeForm.name" maxlength="50" />
        </el-form-item>
        <el-form-item :label="t('dict.key')" prop="type">
          <el-input v-model="typeForm.type" maxlength="100" placeholder="e.g. order_status" />
        </el-form-item>
        <el-form-item :label="t('configManage.sort')">
          <el-input-number v-model="typeForm.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item :label="t('common.status')">
          <el-radio-group v-model="typeForm.status">
            <el-radio-button :value="1">{{ t('common.enabled') }}</el-radio-button>
            <el-radio-button :value="0">{{ t('common.disabled') }}</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item :label="t('configManage.remark')">
          <el-input v-model="typeForm.remark" type="textarea" maxlength="255" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="typeDialogVisible = false">{{ t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="savingType" @click="submitType">{{ t('common.save') }}</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="dataDialogVisible" :title="editingDataId ? t('dict.editData') : t('dict.createData')" width="520px">
      <el-form ref="dataFormRef" :model="dataForm" :rules="dataRules" label-width="90px">
        <el-form-item :label="t('dict.label')" prop="label">
          <el-input v-model="dataForm.label" maxlength="100" />
        </el-form-item>
        <el-form-item :label="t('dict.value')" prop="value">
          <el-input v-model="dataForm.value" maxlength="100" />
        </el-form-item>
        <el-form-item :label="t('dict.tagStyle')">
          <el-select v-model="dataForm.tag_type" class="full">
            <el-option v-for="item in tagOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('configManage.sort')">
          <el-input-number v-model="dataForm.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item :label="t('common.status')">
          <el-radio-group v-model="dataForm.status">
            <el-radio-button :value="1">{{ t('common.enabled') }}</el-radio-button>
            <el-radio-button :value="0">{{ t('common.disabled') }}</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item :label="t('configManage.remark')">
          <el-input v-model="dataForm.remark" type="textarea" maxlength="255" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dataDialogVisible = false">{{ t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="savingData" @click="submitData">{{ t('common.save') }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.dict-page {
  height: 100%;
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(420px, 0.9fr) minmax(560px, 1.3fr);
  gap: 14px;
}

.dict-type-card,
.dict-data-card {
  min-width: 0;
}

.dict-subtitle {
  margin-left: 8px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
  font-weight: 400;
}

.full {
  width: 100%;
}

@media (max-width: 1200px) {
  .dict-page {
    grid-template-columns: 1fr;
  }
}
</style>
