import request from '../utils/request'
import type { PageResult } from './types'

export interface MemberRow {
  id: number
  username: string
  nickname: string
  avatar: string
  mobile: string
  email: string
  gender: number
  birthday: string | null
  status: number
  register_ip: string
  register_time: string | null
  last_login_ip: string
  last_login_time: string | null
  remark: string
  create_time: string | null
}

export interface MemberPayload {
  username: string
  password?: string
  nickname: string
  avatar: string
  mobile: string
  email: string
  gender: number
  birthday: string
  status: number
  remark: string
}

export function fetchMembers(params: { page: number; limit: number; keyword?: string; status?: number | '' }) {
  return request.get<PageResult<MemberRow>>('/member/index', { params }).then((response) => response.data)
}

export function fetchMemberDetail(id: number) {
  return request.get<MemberRow>(`/member/detail/id/${id}`).then((response) => response.data)
}

export function createMember(data: MemberPayload) {
  return request.post<MemberRow>('/member/save', data).then((response) => response.data)
}

export function updateMember(id: number, data: MemberPayload) {
  return request.put<MemberRow>(`/member/update/id/${id}`, data).then((response) => response.data)
}

export function updateMemberStatus(id: number, status: number) {
  return request.patch<MemberRow>(`/member/status/id/${id}`, { status }).then((response) => response.data)
}

export function batchUpdateMemberStatus(ids: number[], status: number) {
  return request.patch<never>('/member/batchStatus', { ids, status }).then((response) => response.data)
}

export function resetMemberPassword(id: number, password: string) {
  return request.post<never>(`/member/resetPassword/id/${id}`, { password }).then((response) => response.data)
}

export function deleteMember(id: number) {
  return request.delete<never>(`/member/delete/id/${id}`).then((response) => response.data)
}

export function batchDeleteMembers(ids: number[]) {
  return request.delete<never>('/member/batchDelete', { data: { ids } }).then((response) => response.data)
}
