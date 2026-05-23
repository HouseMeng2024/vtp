import request from '../utils/request'

export interface ContentCategoryRow {
  id: number
  parent_id: number
  type: string
  name: string
  slug: string
  cover: string
  description: string
  sort: number
  status: number
  children?: ContentCategoryRow[]
}

export type ContentCategoryPayload = Omit<ContentCategoryRow, 'id' | 'children'>

export function fetchContentCategories(params?: { keyword?: string; type?: string }) {
  return request.get<ContentCategoryRow[]>('/content_category/index', { params }).then((response) => response.data)
}

export function createContentCategory(data: ContentCategoryPayload) {
  return request.post<ContentCategoryRow>('/content_category/save', data).then((response) => response.data)
}

export function updateContentCategory(id: number, data: ContentCategoryPayload) {
  return request.put<ContentCategoryRow>(`/content_category/update/id/${id}`, data).then((response) => response.data)
}

export function updateContentCategoryStatus(id: number, status: number) {
  return request.patch<ContentCategoryRow>(`/content_category/status/id/${id}`, { status }).then((response) => response.data)
}

export function deleteContentCategory(id: number) {
  return request.delete<never>(`/content_category/delete/id/${id}`).then((response) => response.data)
}

