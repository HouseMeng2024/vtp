import request from '../utils/request'

export interface SystemToolBackup {
  name: string
  size: number
  create_time: string
}

export interface SystemToolOverview {
  cache: {
    driver: string
    path: string
  }
  backups: SystemToolBackup[]
}

export function fetchSystemTools() {
  return request.get<SystemToolOverview>('/system_tool/index').then((response) => response.data)
}

export function clearSystemCache() {
  return request.delete<never>('/system_tool/clearCache').then((response) => response.data)
}

export function fetchDatabaseBackups() {
  return request.get<SystemToolBackup[]>('/system_tool/backups').then((response) => response.data)
}

export function createDatabaseBackup() {
  return request.post<SystemToolBackup>('/system_tool/createBackup').then((response) => response.data)
}

export function restoreDatabaseBackup(name: string) {
  return request.post<never>('/system_tool/restoreBackup', { name }).then((response) => response.data)
}

export function deleteDatabaseBackup(name: string) {
  return request.delete<never>('/system_tool/deleteBackup', { data: { name } }).then((response) => response.data)
}

export function downloadDatabaseBackup(name: string) {
  return request.get<Blob>('/system_tool/downloadBackup', {
    params: { name },
    responseType: 'blob',
  }).then((response) => response.data)
}
