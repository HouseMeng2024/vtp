<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { captchaApi } from '../../api/auth'
import { useAppStore } from '../../stores/app'
import { useAuthStore } from '../../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const appStore = useAppStore()
const loading = ref(false)
const captchaEnabled = ref(false)
const captchaImage = ref('')
const backendOrigin = import.meta.env.DEV ? 'http://127.0.0.1:8000' : ''
const form = reactive({
  username: '',
  password: '',
  captcha_key: '',
  captcha_code: '',
})

async function loadCaptcha() {
  const data = await captchaApi()
  captchaEnabled.value = data.enabled
  form.captcha_key = data.key
  form.captcha_code = ''
  captchaImage.value = data.image
}

function logoUrl(url = '') {
  if (!url || /^https?:\/\//i.test(url) || url.startsWith('data:')) {
    return url
  }

  return `${backendOrigin}${url}`
}

async function handleLogin() {
  loading.value = true

  try {
    await authStore.login(form.username, form.password, form.captcha_key, form.captcha_code)
    router.push((route.query.redirect as string) || '/dashboard')
  } catch (error) {
    if (captchaEnabled.value) {
      await loadCaptcha()
    }
    throw error
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCaptcha()
  document.title = appStore.siteConfig.adminTitle
  appStore.loadSiteConfig().catch(() => undefined)
})
</script>

<template>
  <main class="login-page">
    <section class="login-shell">
      <div class="login-brand">
        <img v-if="appStore.siteConfig.siteLogo" class="brand-logo" :src="logoUrl(appStore.siteConfig.siteLogo)" alt="logo" />
        <div v-else class="brand-mark">{{ appStore.siteConfig.adminTitle.slice(0, 1).toUpperCase() }}</div>
        <div>
          <h1>{{ appStore.siteConfig.adminTitle }}</h1>
          <p>{{ appStore.siteConfig.siteDescription }}</p>
        </div>
      </div>

      <el-card class="login-card" shadow="never">
        <div class="login-card-header">
          <h2>后台登录</h2>
          <p>请输入管理员账号和密码</p>
        </div>

        <el-form label-position="top" @submit.prevent="handleLogin">
          <el-form-item label="账号">
            <el-input v-model="form.username" placeholder="admin" />
          </el-form-item>
          <el-form-item label="密码">
            <el-input v-model="form.password" type="password" show-password placeholder="请输入密码" />
          </el-form-item>
          <el-form-item v-if="captchaEnabled" label="验证码">
            <div class="captcha-row">
              <el-input v-model="form.captcha_code" placeholder="请输入结果" @keyup.enter="handleLogin" />
              <button class="captcha-image" type="button" @click="loadCaptcha">
                <img :src="captchaImage" alt="验证码" />
              </button>
            </div>
          </el-form-item>
          <el-button type="primary" native-type="submit" class="submit" :loading="loading">
            登录
          </el-button>
        </el-form>
      </el-card>
    </section>
  </main>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background: var(--el-bg-color-page);
}

.login-shell {
  width: min(860px, 100%);
  display: grid;
  grid-template-columns: minmax(0, 1fr) 420px;
  align-items: center;
  gap: 48px;
}

.login-brand {
  display: flex;
  align-items: center;
  gap: 16px;
}

.brand-mark {
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: var(--el-color-primary);
  color: var(--el-color-white);
  font-size: 28px;
  font-weight: 800;
}

.brand-logo {
  width: 54px;
  height: 54px;
  object-fit: contain;
}

.login-brand h1 {
  margin: 0;
  color: var(--el-text-color-primary);
  font-size: 32px;
}

.login-brand p,
.login-card-header p {
  margin: 8px 0 0;
  color: var(--el-text-color-regular);
  font-size: 14px;
}

.login-card {
  width: 100%;
}

.login-card-header {
  margin-bottom: 24px;
}

.login-card-header h2 {
  margin: 0 0 24px;
  font-size: 24px;
}

.login-card-header h2 {
  margin-bottom: 0;
}

.submit {
  width: 100%;
}

.captcha-row {
  display: grid;
  width: 100%;
  grid-template-columns: 1fr 120px;
  gap: 10px;
}

.captcha-image {
  height: 40px;
  padding: 0;
  overflow: hidden;
  border: 1px solid var(--el-border-color);
  border-radius: 4px;
  background: var(--el-fill-color-light);
  cursor: pointer;
}

.captcha-image img {
  display: block;
  width: 100%;
  height: 100%;
}

@media (max-width: 768px) {
  .login-shell {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .login-brand {
    justify-content: center;
  }
}
</style>
