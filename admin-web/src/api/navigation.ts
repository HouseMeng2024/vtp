import request from '../utils/request'

export interface NavigationRow {
  id: number
  parent_id: number
  group: string
  title: string
  url: string
  target: string
  icon: string
  sort: number
  status: number
  remark: string
  children?: NavigationRow[]
}

export type NavigationPayload = Omit<NavigationRow, 'id' | 'children'>

export interface NavigationOptions {
  groups: Array<{
    label: string
    value: string
  }>
  links: Array<{
    label: string
    url: string
  }>
}

export function fetchNavigations(params?: { keyword?: string; group?: string }) {
  return request.get<NavigationRow[]>('/navigation/index', { params }).then((response) => response.data)
}

export function fetchNavigationOptions() {
  return request.get<NavigationOptions>('/navigation/options').then((response) => response.data)
}

export function createNavigation(data: NavigationPayload) {
  return request.post<NavigationRow>('/navigation/save', data).then((response) => response.data)
}

export function updateNavigation(id: number, data: NavigationPayload) {
  return request.put<NavigationRow>(`/navigation/update/id/${id}`, data).then((response) => response.data)
}

export function updateNavigationStatus(id: number, status: number) {
  return request.patch<NavigationRow>(`/navigation/status/id/${id}`, { status }).then((response) => response.data)
}

export function deleteNavigation(id: number) {
  return request.delete<never>(`/navigation/delete/id/${id}`).then((response) => response.data)
}
