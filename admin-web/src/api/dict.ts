import request from '../utils/request'
import type { PageResult } from './types'

export interface DictTypeRow {
  id: number
  name: string
  type: string
  sort: number
  status: number
  remark: string
  create_time: string | null
}

export interface DictTypePayload {
  name: string
  type: string
  sort: number
  status: number
  remark: string
}

export interface DictTypeOption {
  id: number
  name: string
  type: string
}

export interface DictOption {
  label: string
  value: string
  tag_type?: string
  sort?: number
}

export interface DictDataRow {
  id: number
  type_id: number
  label: string
  value: string
  tag_type: string
  sort: number
  status: number
  remark: string
  create_time: string | null
}

export interface DictDataPayload {
  type_id: number
  label: string
  value: string
  tag_type: string
  sort: number
  status: number
  remark: string
}

export function fetchDictTypes(params: { page: number; limit: number; keyword?: string }) {
  return request.get<PageResult<DictTypeRow>>('/dict/types', { params }).then((response) => response.data)
}

export function fetchDictTypeOptions() {
  return request.get<DictTypeOption[]>('/dict/typeOptions').then((response) => response.data)
}

export function fetchDictOptions(type: string) {
  return request.get<DictOption[]>('/dict/options', { params: { type } }).then((response) => response.data)
}

export function createDictType(data: DictTypePayload) {
  return request.post<DictTypeRow>('/dict/saveType', data).then((response) => response.data)
}

export function updateDictType(id: number, data: DictTypePayload) {
  return request.put<DictTypeRow>(`/dict/updateType/id/${id}`, data).then((response) => response.data)
}

export function updateDictTypeStatus(id: number, status: number) {
  return request.patch<DictTypeRow>(`/dict/typeStatus/id/${id}`, { status }).then((response) => response.data)
}

export function deleteDictType(id: number) {
  return request.delete<never>(`/dict/deleteType/id/${id}`).then((response) => response.data)
}

export function fetchDictData(params: { page: number; limit: number; type_id?: number; keyword?: string }) {
  return request.get<PageResult<DictDataRow>>('/dict/data', { params }).then((response) => response.data)
}

export function createDictData(data: DictDataPayload) {
  return request.post<DictDataRow>('/dict/saveData', data).then((response) => response.data)
}

export function updateDictData(id: number, data: DictDataPayload) {
  return request.put<DictDataRow>(`/dict/updateData/id/${id}`, data).then((response) => response.data)
}

export function updateDictDataStatus(id: number, status: number) {
  return request.patch<DictDataRow>(`/dict/dataStatus/id/${id}`, { status }).then((response) => response.data)
}

export function deleteDictData(id: number) {
  return request.delete<never>(`/dict/deleteData/id/${id}`).then((response) => response.data)
}
