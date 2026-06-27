import request, { getData } from './request'
import type { ListQuery, PaginatedResult } from '@/types/api'

export interface UserSummary {
  id: number
  nickname: string | null
  avatar: string | null
  openid: string | null
  total_days: number
}

export interface UserDetail extends UserSummary {
  clouds_count: number
  public_clouds_count: number
  created_at: string | null
}

export interface UserCloudItem {
  id: number
  image_url: string
  mood: number
  mood_label: string | null
  location_city: string | null
  cloud_type: string | null
  collect_date: string
  is_public: boolean
}

export function fetchUsers(params?: ListQuery) {
  return getData<PaginatedResult<UserSummary>>(request.get('/users', { params }))
}

export function fetchUser(id: number) {
  return getData<UserDetail>(request.get(`/users/${id}`))
}

export function fetchUserClouds(id: number, params?: ListQuery) {
  return getData<PaginatedResult<UserCloudItem>>(request.get(`/users/${id}/clouds`, { params }))
}
