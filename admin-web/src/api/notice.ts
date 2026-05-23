import request from '../utils/request'
import type { PageResult } from './types'

export interface AdminNoticeRow {
  id: number
  title: string
  content: string
  type: string
  scope_type: 'all' | 'role' | 'user'
  scope_ids: number[]
  popup: number
  status: number
  read: number
  create_time: string | null
}

export interface AdminNoticePayload {
  title: string
  content: string
  type: string
  scope_type: 'all' | 'role' | 'user'
  scope_ids: number[]
  popup: number
  status: number
}

export interface NoticeResult {
  items: AdminNoticeRow[]
  unread_count: number
}

export function fetchRecentNotices() {
  return request.get<NoticeResult>('/notice/recent').then((response) => response.data)
}

export function fetchNoticePage(params: { page: number; limit: number; keyword?: string; status?: number | '' }) {
  return request.get<PageResult<AdminNoticeRow>>('/notice/index', { params }).then((response) => response.data)
}

export function createNotice(data: AdminNoticePayload) {
  return request.post<AdminNoticeRow>('/notice/save', data).then((response) => response.data)
}

export function updateNotice(id: number, data: AdminNoticePayload) {
  return request.put<AdminNoticeRow>(`/notice/update/id/${id}`, data).then((response) => response.data)
}

export function updateNoticeStatus(id: number, status: number) {
  return request.patch<AdminNoticeRow>(`/notice/status/id/${id}`, { status }).then((response) => response.data)
}

export function deleteNotice(id: number) {
  return request.delete<never>(`/notice/delete/id/${id}`).then((response) => response.data)
}

export function readNotice(id: number) {
  return request.post<never>(`/notice/read/id/${id}`).then((response) => response.data)
}

export function readAllNotices() {
  return request.post<never>('/notice/readAll').then((response) => response.data)
}
