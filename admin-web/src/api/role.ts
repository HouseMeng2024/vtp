import request from '../utils/request'
import type { PageResult } from './types'

export interface AdminRoleRow {
  id: number
  name: string
  code: string
  sort: number
  status: number
  data_scope: string
  remark: string
  create_time: string | null
}

export interface AdminRolePayload {
  name: string
  code: string
  sort: number
  status: number
  data_scope: string
  remark: string
}

export function fetchRoles(params: { page: number; limit: number; keyword?: string }) {
  return request.get<PageResult<AdminRoleRow>>('/role/index', { params }).then((response) => response.data)
}

export function createRole(data: AdminRolePayload) {
  return request.post<AdminRoleRow>('/role/save', data).then((response) => response.data)
}

export function updateRole(id: number, data: AdminRolePayload) {
  return request.put<AdminRoleRow>(`/role/update/id/${id}`, data).then((response) => response.data)
}

export function updateRoleStatus(id: number, status: number) {
  return request.patch<AdminRoleRow>(`/role/status/id/${id}`, { status }).then((response) => response.data)
}

export function batchUpdateRoleStatus(ids: number[], status: number) {
  return request.patch<never>('/role/batchStatus', { ids, status }).then((response) => response.data)
}

export function deleteRole(id: number) {
  return request.delete<never>(`/role/delete/id/${id}`).then((response) => response.data)
}

export function batchDeleteRoles(ids: number[]) {
  return request.delete<never>('/role/batchDelete', { data: { ids } }).then((response) => response.data)
}

export function fetchRoleOptions() {
  return request.get<Pick<AdminRoleRow, 'id' | 'name' | 'code'>[]>('/role/options').then((response) => response.data)
}

export function fetchRoleMenus(id: number) {
  return request.get<{ menu_ids: number[] }>(`/role/menus/id/${id}`).then((response) => response.data)
}

export function updateRoleMenus(id: number, menuIds: number[]) {
  return request.put<never>(`/role/saveMenus/id/${id}`, { menu_ids: menuIds }).then((response) => response.data)
}
