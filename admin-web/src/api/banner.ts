import request from '../utils/request'
import type { PageResult } from './types'

export interface BannerRow {
  id: number
  position: string
  title: string
  subtitle: string
  image: string
  link_url: string
  target: string
  start_time: string | null
  end_time: string | null
  sort: number
  status: number
  remark: string
  create_time: string | null
  update_time: string | null
}

export interface BannerPayload {
  position: string
  title: string
  subtitle: string
  image: string
  link_url: string
  target: string
  start_time: string
  end_time: string
  sort: number
  status: number
  remark: string
}

export interface BannerOptions {
  positions: Array<{
    label: string
    value: string
  }>
  links: Array<{
    label: string
    url: string
  }>
}

export function fetchBanners(params: { page: number; limit: number; keyword?: string; position?: string; status?: number | '' }) {
  return request.get<PageResult<BannerRow>>('/banner/index', { params }).then((response) => response.data)
}

export function fetchBannerOptions() {
  return request.get<BannerOptions>('/banner/options').then((response) => response.data)
}

export function createBanner(data: BannerPayload) {
  return request.post<BannerRow>('/banner/save', data).then((response) => response.data)
}

export function updateBanner(id: number, data: BannerPayload) {
  return request.put<BannerRow>(`/banner/update/id/${id}`, data).then((response) => response.data)
}

export function updateBannerStatus(id: number, status: number) {
  return request.patch<BannerRow>(`/banner/status/id/${id}`, { status }).then((response) => response.data)
}

export function deleteBanner(id: number) {
  return request.delete<never>(`/banner/delete/id/${id}`).then((response) => response.data)
}
