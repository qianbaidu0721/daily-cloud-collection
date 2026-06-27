import request, { getData } from './request'

export interface CloudTypeItem {
  id: number
  name: string
  code: string
  description: string | null
  icon: string | null
  sort: number
  is_active: boolean
  created_at: string | null
  updated_at: string | null
}

export interface CloudTypePayload {
  name: string
  code: string
  description?: string | null
  icon?: string | null
  sort?: number
  is_active?: boolean
}

export function fetchCloudTypes() {
  return getData<{ list: CloudTypeItem[] }>(request.get('/cloud-types'))
}

export function createCloudType(payload: CloudTypePayload) {
  return getData<CloudTypeItem>(request.post('/cloud-types', payload))
}

export function updateCloudType(id: number, payload: Partial<CloudTypePayload>) {
  return getData<CloudTypeItem>(request.put(`/cloud-types/${id}`, payload))
}

export function deleteCloudType(id: number) {
  return getData<null>(request.delete(`/cloud-types/${id}`))
}
