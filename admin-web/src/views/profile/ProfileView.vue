<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage, type FormInstance, type FormRules, type UploadRequestOptions } from 'element-plus'
import { changePasswordApi, updateAvatarApi, updateProfileApi } from '../../api/auth'
import { fetchRecentNotices, readAllNotices, readNotice, type AdminNoticeRow } from '../../api/system'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const { t } = useI18n()
const profileFormRef = ref<FormInstance>()
const passwordFormRef = ref<FormInstance>()
const savingProfile = ref(false)
const savingPassword = ref(false)
const uploading = ref(false)
const noticeLoading = ref(false)
const notices = ref<AdminNoticeRow[]>([])
const unreadCount = ref(0)

const profileForm = reactive({
  nickname: authStore.user?.nickname || '',
  mobile: authStore.user?.mobile || '',
  email: authStore.user?.email || '',
})
const passwordForm = reactive({
  old_password: '',
  new_password: '',
  confirm_password: '',
})
const profileRules = computed<FormRules>(() => ({
  nickname: [{ required: true, message: t('profile.nicknameRequired'), trigger: 'blur' }],
  email: [{ type: 'email', message: t('profile.emailInvalid'), trigger: 'blur' }],
}))
const passwordRules = computed<FormRules>(() => ({
  old_password: [{ required: true, message: t('profile.passwordRequired'), trigger: 'blur' }],
  new_password: [{ required: true, min: 6, message: t('profile.newPasswordMin'), trigger: 'blur' }],
  confirm_password: [{ required: true, message: t('profile.passwordConfirmRequired'), trigger: 'blur' }],
}))
const backendOrigin = import.meta.env.DEV ? 'http://127.0.0.1:8000' : ''

function avatarUrl(url = '') {
  if (!url || /^https?:\/\//i.test(url)) {
    return url
  }

  return `${backendOrigin}${url}`
}

async function submitProfile() {
  await profileFormRef.value?.validate()
  savingProfile.value = true

  try {
    const user = await updateProfileApi(profileForm)
    authStore.setUser(user)
    ElMessage.success(t('profile.saved'))
  } finally {
    savingProfile.value = false
  }
}

async function submitPassword() {
  await passwordFormRef.value?.validate()
  savingPassword.value = true

  try {
    await changePasswordApi(passwordForm)
    Object.assign(passwordForm, {
      old_password: '',
      new_password: '',
      confirm_password: '',
    })
    passwordFormRef.value?.clearValidate()
    ElMessage.success(t('profile.passwordChanged'))
    await authStore.logout()
    router.push('/login')
  } finally {
    savingPassword.value = false
  }
}

async function handleAvatarUpload(options: UploadRequestOptions) {
  const data = new FormData()
  data.append('file', options.file)
  uploading.value = true

  try {
    const user = await updateAvatarApi(data)
    authStore.setUser(user)
    ElMessage.success(t('profile.avatarUpdated'))
    options.onSuccess({})
  } catch (error) {
    options.onError(Object.assign(error instanceof Error ? error : new Error(t('profile.uploadFailed')), {
      status: 0,
      method: 'POST',
      url: '',
    }))
  } finally {
    uploading.value = false
  }
}

async function loadNotices() {
  noticeLoading.value = true

  try {
    const data = await fetchRecentNotices()
    notices.value = data.items
    unreadCount.value = data.unread_count
  } finally {
    noticeLoading.value = false
  }
}

async function markNoticeRead(row: AdminNoticeRow) {
  await readNotice(row.id)
  await loadNotices()
}

async function markAllNoticesRead() {
  await readAllNotices()
  await loadNotices()
}

onMounted(() => {
  loadNotices().catch(() => undefined)
})
</script>

<template>
  <div class="profile-page">
    <el-card class="page-card profile-card" shadow="never">
      <template #header>
        <div class="page-title">{{ t('common.profile') }}</div>
      </template>

      <div class="profile-summary">
        <el-upload
          class="avatar-upload"
          :show-file-list="false"
          accept=".jpg,.jpeg,.png,.gif,.webp"
          :http-request="handleAvatarUpload"
          :disabled="uploading"
        >
          <el-avatar :size="72" :src="avatarUrl(authStore.user?.avatar)">
            {{ (authStore.user?.nickname || authStore.user?.username || 'A').slice(0, 1) }}
          </el-avatar>
          <div class="avatar-upload-info">
            <div class="profile-name">{{ authStore.user?.nickname || authStore.user?.username }}</div>
            <div class="profile-meta">{{ authStore.user?.username }}</div>
            <el-button link type="primary" :loading="uploading">{{ t('profile.changeAvatar') }}</el-button>
          </div>
        </el-upload>
      </div>

      <el-tabs>
        <el-tab-pane :label="t('profile.info')">
          <el-form ref="profileFormRef" class="profile-form" :model="profileForm" :rules="profileRules" label-width="90px">
            <el-form-item :label="t('profile.nickname')" prop="nickname">
              <el-input v-model="profileForm.nickname" maxlength="50" />
            </el-form-item>
            <el-form-item :label="t('profile.mobile')">
              <el-input v-model="profileForm.mobile" maxlength="20" />
            </el-form-item>
            <el-form-item :label="t('profile.email')" prop="email">
              <el-input v-model="profileForm.email" maxlength="100" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="savingProfile" @click="submitProfile">{{ t('profile.saveProfile') }}</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane :label="t('profile.changePassword')">
          <el-form ref="passwordFormRef" class="profile-form" :model="passwordForm" :rules="passwordRules" label-width="90px">
            <el-form-item :label="t('profile.currentPassword')" prop="old_password">
              <el-input v-model="passwordForm.old_password" type="password" show-password />
            </el-form-item>
            <el-form-item :label="t('profile.newPassword')" prop="new_password">
              <el-input v-model="passwordForm.new_password" type="password" show-password />
            </el-form-item>
            <el-form-item :label="t('profile.confirmPassword')" prop="confirm_password">
              <el-input v-model="passwordForm.confirm_password" type="password" show-password />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="savingPassword" @click="submitPassword">{{ t('profile.changePassword') }}</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane :label="`${t('profile.messages')}${unreadCount > 0 ? `（${unreadCount}）` : ''}`">
          <div class="notice-toolbar">
            <span>{{ t('profile.recentMessages') }}</span>
            <el-button v-if="unreadCount > 0" link type="primary" @click="markAllNoticesRead">{{ t('common.allRead') }}</el-button>
          </div>
          <div v-loading="noticeLoading" class="profile-notices">
            <el-empty v-if="notices.length === 0" :description="t('common.noMessage')" />
            <div v-for="notice in notices" v-else :key="notice.id" class="profile-notice-item">
              <div class="profile-notice-title">
                <el-tag :type="notice.type || 'info'" size="small">{{ notice.read === 0 ? t('common.unread') : t('common.read') }}</el-tag>
                <span>{{ notice.title }}</span>
              </div>
              <div class="profile-notice-content">{{ notice.content }}</div>
              <div class="profile-notice-footer">
                <span>{{ notice.create_time }}</span>
                <el-button v-if="notice.read === 0" link type="primary" @click="markNoticeRead(notice)">{{ t('common.markRead') }}</el-button>
              </div>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<style scoped>
.profile-page {
  max-width: 860px;
}

.profile-summary {
  display: flex;
  align-items: center;
  gap: 18px;
  margin-bottom: 18px;
}

.avatar-upload :deep(.el-upload) {
  display: flex;
  align-items: center;
  gap: 18px;
  cursor: pointer;
}

.avatar-upload-info {
  text-align: left;
}

.profile-name {
  color: var(--el-text-color-primary);
  font-size: 18px;
  font-weight: 700;
}

.profile-meta {
  margin-top: 4px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.profile-form {
  max-width: 520px;
  padding-top: 12px;
}

.notice-toolbar,
.profile-notice-title,
.profile-notice-footer {
  display: flex;
  align-items: center;
}

.notice-toolbar {
  justify-content: space-between;
  margin-bottom: 12px;
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.profile-notices {
  min-height: 180px;
}

.profile-notice-item {
  padding: 14px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.profile-notice-title {
  gap: 8px;
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.profile-notice-content {
  margin-top: 8px;
  color: var(--el-text-color-regular);
  line-height: 1.6;
}

.profile-notice-footer {
  justify-content: space-between;
  margin-top: 8px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}
</style>
