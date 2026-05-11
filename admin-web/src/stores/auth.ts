import { defineStore } from 'pinia'
import { loginApi, logoutApi, menuTreeApi, profileApi } from '../api/auth'
import type { AdminMenu, AdminUser } from '../types/auth'

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
      this.user = data.user
      localStorage.setItem(TOKEN_KEY, data.token)
    },
    async fetchProfile() {
      this.user = await profileApi()
    },
    setUser(user: AdminUser) {
      this.user = user
    },
    async fetchMenus() {
      this.menus = await menuTreeApi()
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
