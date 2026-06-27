import request, { getData } from './request'

export interface OverviewStats {
  users_total: number
  clouds_total: number
  clouds_today: number
  public_clouds_total: number
}

export interface TrendItem {
  date: string
  count: number
}

export function fetchOverview() {
  return getData<OverviewStats>(request.get('/dashboard/overview'))
}

export function fetchTrends(days = 7) {
  return getData<{ items: TrendItem[] }>(request.get('/dashboard/trends', { params: { days } }))
}
