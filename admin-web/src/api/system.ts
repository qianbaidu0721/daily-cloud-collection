import request, { getData } from './request'

export interface ClearCacheResult {
  cleared_at: string
  results: Record<
    string,
    {
      command: string
      success: boolean
      output: string
    }
  >
}

export function clearCache() {
  return getData<ClearCacheResult>(request.post('/system/clear-cache'))
}
