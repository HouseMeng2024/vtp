export interface PageResult<T> {
  data: T[]
  total: number
  per_page: number
  current_page: number
  last_page: number
}
