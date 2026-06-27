import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { AdminUser } from '@/api/auth'

const TOKEN_KEY = 'admin_token'
const ADMIN_KEY = 'admin_user'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const admin = ref<AdminUser | null>(readStoredAdmin())

  const isLoggedIn = computed(() => Boolean(token.value))

  function readStoredAdmin(): AdminUser | null {
    const raw = localStorage.getItem(ADMIN_KEY)
    if (!raw) {
      return null
    }
    try {
      return JSON.parse(raw) as AdminUser
    } catch {
      return null
    }
  }

  function setAuth(newToken: string, newAdmin: AdminUser) {
    token.value = newToken
    admin.value = newAdmin
    localStorage.setItem(TOKEN_KEY, newToken)
    localStorage.setItem(ADMIN_KEY, JSON.stringify(newAdmin))
  }

  function clearAuth() {
    token.value = null
    admin.value = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(ADMIN_KEY)
  }

  return {
    token,
    admin,
    isLoggedIn,
    setAuth,
    clearAuth,
  }
})
