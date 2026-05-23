import request from '../utils/request'
import type { PageResult } from './types'

export interface AdminUserRow {
  id: number
  username: string
  nickname: string
  role_ids: number[]
  mobile: string
  email: string
  status: number
  last_login_ip: string
  last_login_time: string | null
  create_time: string | null
}

export interface AdminUserPayload {
  username: string
  password?: string
  nickname: string
  mobile: string
  email: string
  status: number
  role_ids: number[]
}

export function fetchUsers(params: { page: number; limit: number; keyword?: string }) {
  return request.get<PageResult<AdminUserRow>>('/user/index', { params }).then((response) => response.data)
}

export function createUser(data: AdminUserPayload) {
  return request.post<AdminUserRow>('/user/save', data).then((response) => response.data)
}

export function updateUser(id: number, data: AdminUserPayload) {
  return request.put<AdminUserRow>(`/user/update/id/${id}`, data).then((response) => response.data)
}

export function updateUserStatus(id: number, status: number) {
  return request.patch<AdminUserRow>(`/user/status/id/${id}`, { status }).then((response) => response.data)
}

export function batchUpdateUserStatus(ids: number[], status: number) {
  return request.patch<never>('/user/batchStatus', { ids, status }).then((response) => response.data)
}

export function deleteUser(id: number) {
  return request.delete<never>(`/user/delete/id/${id}`).then((response) => response.data)
}

export function batchDeleteUsers(ids: number[]) {
  return request.delete<never>('/user/batchDelete', { data: { ids } }).then((response) => response.data)
}

export function forceLogoutUser(id: number) {
  return request.post<never>(`/user/forceLogout/id/${id}`).then((response) => response.data)
}
