import request, { getData } from './request'
import type { ListQuery, PaginatedResult } from '@/types/api'

export interface CloudSummary {
  id: number
  user_id: number
  user_nickname: string | null
  image_url: string
  mood: number
  mood_label: string | null
  location_city: string | null
  cloud_type: string | null
  collect_date: string
  is_public: boolean
  created_at: string | null
}

export interface CloudUser {
  id: number
  nickname: string | null
  avatar: string | null
  total_days: number
}

export interface CloudDetail {
  id: number
  user_id: number
  user: CloudUser | null
  image_path: string
  image_url: string
  mood: number
  mood_label: string | null
  location_city: string | null
  location_lat: string | null
  location_lng: string | null
  note: string | null
  cloud_type: string | null
  collect_date: string
  is_public: boolean
  created_at: string | null
  updated_at: string | null
}

export interface CloudListQuery extends ListQuery {
  user_id?: number
  is_public?: boolean | ''
  mood?: number | ''
  cloud_type?: string
  collect_date?: string
}

export interface UpdateCloudPayload {
  is_public?: boolean
  note?: string | null
}

export function fetchClouds(params?: CloudListQuery) {
  return getData<PaginatedResult<CloudSummary>>(request.get('/clouds', { params }))
}

export function fetchCloud(id: number) {
  return getData<CloudDetail>(request.get(`/clouds/${id}`))
}

export function updateCloud(id: number, payload: UpdateCloudPayload) {
  return getData<CloudDetail>(request.patch(`/clouds/${id}`, payload))
}

export function deleteCloud(id: number) {
  return getData<null>(request.delete(`/clouds/${id}`))
}

export const MOOD_OPTIONS = [
  { value: 1, label: 'emo' },
  { value: 2, label: '一般' },
  { value: 3, label: '平静' },
  { value: 4, label: '开心' },
  { value: 5, label: '超开心' },
] as const

export function moodLabel(mood: number): string {
  return MOOD_OPTIONS.find((item) => item.value === mood)?.label ?? String(mood)
}
