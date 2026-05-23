import request from '../utils/request'
import type { SiteConfigPayload } from '../types/auth'

export interface SystemConfigItem {
  id: number
  group_id: number
  tab_id: number
  group: string
  key: string
  value: string
  type: string
  name: string
  remark: string
  options: string
  sort: number
  is_system: number
  status: number
  create_time: string | null
  update_time: string | null
}

export interface SystemConfigTab {
  id: number
  group_id: number
  key: string
  title: string
  sort: number
  is_system: number
  status: number
  items: SystemConfigItem[]
  create_time: string | null
  update_time: string | null
}

export interface SystemConfigGroup {
  id: number
  key: string
  title: string
  sort: number
  is_system: number
  status: number
  tabs: SystemConfigTab[]
  create_time: string | null
  update_time: string | null
}

export interface SystemConfigGroupPayload {
  key: string
  title: string
  sort: number
  status: number
}

export interface SystemConfigTabPayload {
  group_id: number
  key: string
  title: string
  sort: number
  status: number
}

export interface SystemConfigItemPayload {
  tab_id: number
  key: string
  value: string | number | Array<string | number>
  type: string
  name: string
  remark: string
  options: string
  sort: number
  status: number
}

export function fetchSystemConfigs() {
  return request.get<SystemConfigGroup[]>('/config/index').then((response) => response.data)
}

export function fetchSiteConfig() {
  return request.get<SiteConfigPayload>('/config/site').then((response) => response.data)
}

export function updateSystemConfigs(data: Record<string, string | number | Array<string | number>>) {
  return request.put<SystemConfigGroup[]>('/config/save', data).then((response) => response.data)
}

export function createSystemConfigGroup(data: SystemConfigGroupPayload) {
  return request.post<SystemConfigGroup[]>('/config/createGroup', data).then((response) => response.data)
}

export function updateSystemConfigGroup(id: number, data: SystemConfigGroupPayload) {
  return request.put<SystemConfigGroup[]>('/config/updateGroup', data, { params: { id } }).then((response) => response.data)
}

export function deleteSystemConfigGroup(id: number) {
  return request.delete<SystemConfigGroup[]>('/config/deleteGroup', { params: { id } }).then((response) => response.data)
}

export function createSystemConfigTab(data: SystemConfigTabPayload) {
  return request.post<SystemConfigGroup[]>('/config/createTab', data).then((response) => response.data)
}

export function updateSystemConfigTab(id: number, data: SystemConfigTabPayload) {
  return request.put<SystemConfigGroup[]>('/config/updateTab', data, { params: { id } }).then((response) => response.data)
}

export function deleteSystemConfigTab(id: number) {
  return request.delete<SystemConfigGroup[]>('/config/deleteTab', { params: { id } }).then((response) => response.data)
}

export function createSystemConfigItem(data: SystemConfigItemPayload) {
  return request.post<SystemConfigGroup[]>('/config/createItem', data).then((response) => response.data)
}

export function updateSystemConfigItem(id: number, data: SystemConfigItemPayload) {
  return request.put<SystemConfigGroup[]>('/config/updateItem', data, { params: { id } }).then((response) => response.data)
}

export function deleteSystemConfigItem(id: number) {
  return request.delete<SystemConfigGroup[]>('/config/deleteItem', { params: { id } }).then((response) => response.data)
}
