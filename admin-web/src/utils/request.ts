import axios from 'axios'
import { ElMessage } from 'element-plus'
import type { ApiResponse } from '../types/auth'
import { getStoredLocale, t } from '../plugins/i18n'
import { finishProgress, startProgress } from './progress'

const request = axios.create({
  baseURL: '/admin',
  timeout: 10000,
})

request.interceptors.request.use((config) => {
  startProgress()
  const token = localStorage.getItem('admin_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  config.headers['think-lang'] = getStoredLocale()

  return config
})

request.interceptors.response.use(
  (response) => {
    finishProgress()
    const result = response.data as ApiResponse<unknown>

    if (result.code !== 0) {
      if (result.code === 401) {
        localStorage.removeItem('admin_token')
        location.href = `/login?redirect=${encodeURIComponent(location.pathname + location.search)}`
        return Promise.reject(new Error(result.message || t('error.unauthorized')))
      }

      if (result.code === 403) {
        location.href = '/403'
        return Promise.reject(new Error(result.message || t('error.forbidden')))
      }

      ElMessage.error(result.message || t('error.requestFailed'))
      return Promise.reject(new Error(result.message || t('error.requestFailed')))
    }

    response.data = result.data
    return response
  },
  (error) => {
    finishProgress()
    if (error?.response?.status === 401) {
      localStorage.removeItem('admin_token')
      location.href = '/login'
      return Promise.reject(error)
    }

    if (error?.response?.status === 403) {
      location.href = '/403'
      return Promise.reject(error)
    }

    ElMessage.error(error?.response?.data?.message || error.message || t('error.requestFailed'))
    return Promise.reject(error)
  },
)

export default request
