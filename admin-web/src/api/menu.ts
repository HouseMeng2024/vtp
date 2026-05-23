import request from '../utils/request'
import type { AdminMenu } from '../types/auth'

export interface AdminMenuPayload {
  parent_id: number
  type: number
  title: string
  permission: string
  path: string
  component: string
  icon: string
  sort: number
  visible: number
  status: number
  remark: string
}

export function fetchMenus() {
  return request.get<AdminMenu[]>('/menu/index').then((response) => response.data)
}

export function createMenu(data: AdminMenuPayload) {
  return request.post<AdminMenu>('/menu/save', data).then((response) => response.data)
}

export function updateMenu(id: number, data: AdminMenuPayload) {
  return request.put<AdminMenu>(`/menu/update/id/${id}`, data).then((response) => response.data)
}

export function deleteMenu(id: number) {
  return request.delete<never>(`/menu/delete/id/${id}`).then((response) => response.data)
}
