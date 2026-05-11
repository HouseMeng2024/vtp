import request from '../utils/request'
import type { AdminMenu, AdminUser, LoginResult } from '../types/auth'

export interface CaptchaResult {
  enabled: boolean
  key: string
  image: string
}

export function loginApi(data: { username: string; password: string; captcha_key?: string; captcha_code?: string }) {
  return request.post<LoginResult>('/auth/login', data).then((response) => response.data)
}

export function captchaApi() {
  return request.get<CaptchaResult>('/auth/captcha').then((response) => response.data)
}

export function profileApi() {
  return request.get<AdminUser>('/auth/profile').then((response) => response.data)
}

export function updateProfileApi(data: { nickname: string; mobile: string; email: string }) {
  return request.put<AdminUser>('/auth/updateProfile', data).then((response) => response.data)
}

export function changePasswordApi(data: { old_password: string; new_password: string; confirm_password: string }) {
  return request.put<never>('/auth/changePassword', data).then((response) => response.data)
}

export function updateAvatarApi(data: FormData) {
  return request.post<AdminUser>('/auth/avatar', data).then((response) => response.data)
}

export function logoutApi() {
  return request.post<never>('/auth/logout').then((response) => response.data)
}

export function menuTreeApi() {
  return request.get<AdminMenu[]>('/auth/menus').then((response) => response.data)
}
