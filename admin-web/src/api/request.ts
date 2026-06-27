import axios, { type AxiosInstance, type InternalAxiosRequestConfig } from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'
import { useAuthStore } from '@/stores/auth'

export interface ApiResponse<T = unknown> {
  code: number
  msg: string
  data: T
}

const request: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE,
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

request.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

request.interceptors.response.use(
  (response) => {
    const payload = response.data as ApiResponse

    if (payload.code !== 0) {
      ElMessage.error(payload.msg || '请求失败')
      return Promise.reject(new Error(payload.msg || '请求失败'))
    }

    return response
  },
  (error) => {
    const status = error.response?.status
    const payload = error.response?.data as ApiResponse | undefined

    if (status === 401) {
      const auth = useAuthStore()
      auth.clearAuth()
      router.push({ name: 'Login' })
      ElMessage.warning(payload?.msg || '登录已过期，请重新登录')
    } else {
      ElMessage.error(payload?.msg || error.message || '网络错误')
    }

    return Promise.reject(error)
  },
)

export default request

export async function getData<T>(promise: Promise<{ data: ApiResponse<T> }>): Promise<T> {
  const { data } = await promise
  return data.data as T
}
