import request from '../utils/request'
import type { PageResult } from './types'

export interface AdminLoginLogRow {
  id: number
  user_id: number
  username: string
  ip: string
  user_agent: string
  status: number
  message: string
  create_time: string | null
}

export interface AdminOperateLogRow {
  id: number
  user_id: number
  username: string
  title: string
  method: string
  path: string
  params: string
  response: string
  ip: string
  user_agent: string
  status_code: number
  duration_ms: string
  create_time: string | null
}

export function fetchLoginLogs(params: { page: number; limit: number; keyword?: string; status?: number | '' }) {
  return request.get<PageResult<AdminLoginLogRow>>('/log/login', { params }).then((response) => response.data)
}

export function fetchOperateLogs(params: { page: number; limit: number; keyword?: string; method?: string }) {
  return request.get<PageResult<AdminOperateLogRow>>('/log/operate', { params }).then((response) => response.data)
}

export function batchDeleteLoginLogs(ids: number[]) {
  return request.delete<never>('/log/batchDeleteLogin', { data: { ids } }).then((response) => response.data)
}

export function clearLoginLogs() {
  return request.delete<never>('/log/clearLogin').then((response) => response.data)
}

export function batchDeleteOperateLogs(ids: number[]) {
  return request.delete<never>('/log/batchDeleteOperate', { data: { ids } }).then((response) => response.data)
}

export function clearOperateLogs() {
  return request.delete<never>('/log/clearOperate').then((response) => response.data)
}
