import request from '../utils/request'
import type { PageResult } from './types'

export interface ArticleRow {
  id: number
  category_id: number
  title: string
  subtitle: string
  cover: string
  summary: string
  content?: string
  author: string
  source: string
  source_url?: string
  keywords?: string
  description?: string
  views: number
  sort: number
  status: number
  publish_time: string | null
  create_time: string | null
  update_time: string | null
}

export interface ArticlePayload {
  category_id: number
  title: string
  subtitle: string
  cover: string
  summary: string
  content: string
  author: string
  source: string
  source_url: string
  keywords: string
  description: string
  views: number
  sort: number
  status: number
  publish_time: string
}

export function fetchArticles(params: {
  page: number
  limit: number
  keyword?: string
  category_id?: number | ''
  status?: number | ''
}) {
  return request.get<PageResult<ArticleRow>>('/article/index', { params }).then((response) => response.data)
}

export function fetchArticleDetail(id: number) {
  return request.get<ArticleRow>(`/article/detail/id/${id}`).then((response) => response.data)
}

export function createArticle(data: ArticlePayload) {
  return request.post<ArticleRow>('/article/save', data).then((response) => response.data)
}

export function updateArticle(id: number, data: ArticlePayload) {
  return request.put<ArticleRow>(`/article/update/id/${id}`, data).then((response) => response.data)
}

export function updateArticleStatus(id: number, status: number) {
  return request.patch<ArticleRow>(`/article/status/id/${id}`, { status }).then((response) => response.data)
}

export function deleteArticle(id: number) {
  return request.delete<never>(`/article/delete/id/${id}`).then((response) => response.data)
}

