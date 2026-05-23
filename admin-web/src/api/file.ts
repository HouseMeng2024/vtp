import request from '../utils/request'
import type { PageResult } from './types'

export interface UploadFileRow {
  id: number
  disk: string
  path: string
  url: string
  original_name: string
  mime_type: string
  extension: string
  category: string
  scene: string
  size: number
  sha1: string
  uploader_id: number
  create_time: string | null
}

export function fetchUploadFiles(params: { page: number; limit: number; keyword?: string; extension?: string; category?: string; scene?: string }) {
  return request.get<PageResult<UploadFileRow>>('/file/index', { params }).then((response) => response.data)
}

export function uploadFile(data: FormData) {
  return request.post<UploadFileRow>('/file/upload', data).then((response) => response.data)
}

export function deleteUploadFile(id: number) {
  return request.delete<never>(`/file/delete/id/${id}`).then((response) => response.data)
}

export function fetchUploadFileDeleteInfo(id: number) {
  return request.get<{ reference_count: number; will_delete_physical: number }>(`/file/deleteInfo/id/${id}`).then((response) => response.data)
}

export function batchDeleteUploadFiles(ids: number[]) {
  return request.delete<never>('/file/batchDelete', { data: { ids } }).then((response) => response.data)
}

export function renameUploadFile(id: number, name: string) {
  return request.patch<UploadFileRow>(`/file/rename/id/${id}`, { name }).then((response) => response.data)
}
