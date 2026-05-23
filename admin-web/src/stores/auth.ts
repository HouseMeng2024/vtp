import { defineStore } from 'pinia'
import { contextApi, loginApi, logoutApi, menuTreeApi } from '../api/auth'
import { useAppStore } from './app'
import type { AdminMenu, AdminUser, BackendContext } from '../types/auth'

const TOKEN_KEY = 'admin_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem(TOKEN_KEY) || '',
    user: null as AdminUser | null,
    menus: [] as AdminMenu[],
  }),
  getters: {
    isLoggedIn: (state) => Boolean(state.token),
    hasPermission: (state) => {
      return (permission: string) => {
        const permissions = state.user?.permissions || []

        return permissions.includes('*') || permissions.includes(permission)
      }
    },
  },
  actions: {
    async login(username: string, password: string, captchaKey = '', captchaCode = '') {
      const data = await loginApi({
        username,
        password,
        captcha_key: captchaKey,
        captcha_code: captchaCode,
      })
      this.token = data.token
      localStorage.setItem(TOKEN_KEY, data.token)
      this.applyContext(data)
    },
    async fetchContext() {
      this.applyContext(await contextApi())
    },
    setUser(user: AdminUser) {
      this.user = user
    },
    async fetchMenus() {
      this.menus = await menuTreeApi()
    },
    applyContext(context: BackendContext) {
      this.user = context.user
      this.menus = context.menus
      useAppStore().setSiteConfig(context.site_config)
      useAppStore().setBackendOptions(context.config_options, context.file_options)
    },
    async logout() {
      if (this.token) {
        await logoutApi().catch(() => undefined)
      }

      this.token = ''
      this.user = null
      this.menus = []
      localStorage.removeItem(TOKEN_KEY)
    },
  },
})
