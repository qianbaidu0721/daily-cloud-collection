export interface PaginationMeta {
  total: number
  page: number
  per_page: number
  last_page: number
}

export interface PaginatedResult<T> {
  list: T[]
  pagination: PaginationMeta
}

export interface ListQuery {
  page?: number
  per_page?: number
  keyword?: string
}
