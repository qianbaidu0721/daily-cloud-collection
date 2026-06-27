import request, { getData } from './request'

export interface AdminUser {
  id: number
  name: string
  email: string
}

export interface LoginPayload {
  email: string
  password: string
}

export interface LoginResult {
  token: string
  admin: AdminUser
}

export function login(payload: LoginPayload) {
  return getData<LoginResult>(request.post('/auth/login', payload))
}

export function fetchMe() {
  return getData<AdminUser>(request.get('/auth/me'))
}

export function logout() {
  return getData<null>(request.post('/auth/logout'))
}
