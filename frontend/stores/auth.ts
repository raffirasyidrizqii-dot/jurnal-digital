import { defineStore } from 'pinia'

export interface User {
  id: number
  name: string
  email: string
  phone: string
  nip_nis: string
  role: 'SISWA' | 'SEKRETARIS' | 'GURU_PENGAJAR' | 'GURU_PEMBIMBING' | 'ORANG_TUA' | 'SUPERADMIN'
  specialization?: string
  is_active: boolean
  is_secretary?: boolean
  is_homeroom_teacher?: boolean
  class_id?: number
  avatar?: string | null
  avatar_url?: string | null
  school_class?: {
    id: number
    name: string
  }
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase || '/api'

  function clearDashboardCache() {
    if (import.meta.client) {
      const roles = ['SISWA', 'SEKRETARIS', 'GURU_PENGAJAR', 'GURU_PEMBIMBING', 'ORANG_TUA', 'SUPERADMIN', 'default']
      roles.forEach((r) => localStorage.removeItem(`cache_dashboard_${r}`))
    }
  }

  function ensureAuth() {
    if (import.meta.client) {
      if (!token.value) {
        const savedToken = localStorage.getItem('token')
        if (savedToken) token.value = savedToken
      }
      if (!user.value) {
        const savedUser = localStorage.getItem('user')
        if (savedUser) {
          try {
            user.value = JSON.parse(savedUser)
          } catch (e) {
            console.error(e)
          }
        }
      }
    }
  }

  // Initialize from localStorage in client
  ensureAuth()

  const isAuthenticated = computed(() => {
    ensureAuth()
    return !!token.value && !!user.value
  })
  const role = computed(() => user.value?.role || 'SISWA')

  function setAuth(newUser: User, newToken: string) {
    clearDashboardCache()
    user.value = newUser
    token.value = newToken
    if (import.meta.client) {
      localStorage.setItem('token', newToken)
      localStorage.setItem('user', JSON.stringify(newUser))
    }
  }

  async function login(email: string, password: string, turnstileToken?: string) {
    const body: Record<string, any> = { email, password }
    if (turnstileToken) {
      body.turnstile_token = turnstileToken
    }
    const res = await $fetch<{ message: string; user: User; token: string }>(`${apiBase}/auth/login`, {
      method: 'POST',
      body,
    })
    setAuth(res.user, res.token)
    return res
  }

  async function logout() {
    if (token.value) {
      try {
        await $fetch(`${apiBase}/auth/logout`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${token.value}` },
        })
      } catch (e) {
        // ignore network error on logout
      }
    }
    user.value = null
    token.value = null
    if (import.meta.client) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      clearDashboardCache()
    }
    navigateTo('/login')
  }

  async function fetchUser() {
    if (!token.value && import.meta.client) {
      token.value = localStorage.getItem('token')
    }
    if (!token.value) return null
    try {
      const freshUser = await $fetch<User>(`${apiBase}/auth/me`, {
        headers: { Authorization: `Bearer ${token.value}` }
      })
      if (freshUser) {
        user.value = freshUser
        if (import.meta.client) {
          localStorage.setItem('user', JSON.stringify(freshUser))
        }
      }
      return freshUser
    } catch (e: any) {
      if (e?.status === 401) {
        await logout()
      }
      return null
    }
  }

  const isDualRole = computed(() => {
    const isTeacherDual = (
      (user.value?.role === 'GURU_PENGAJAR' || user.value?.role === 'GURU_PEMBIMBING') &&
      (!!(user.value as any)?.guided_class || !!(user.value as any)?.guidedClass || !!user.value?.is_homeroom_teacher || !!user.value?.class_id)
    )
    const isStudentDual = (
      (user.value?.role === 'SISWA' || user.value?.role === 'SEKRETARIS') &&
      (user.value?.is_secretary === true || (user.value as any)?.isSecretary === true || user.value?.role === 'SEKRETARIS')
    )
    return isTeacherDual || isStudentDual
  })

  async function switchRole(targetRole?: 'GURU_PENGAJAR' | 'GURU_PEMBIMBING' | 'SISWA' | 'SEKRETARIS') {
    if (!token.value) return
    let resolvedTarget = targetRole
    if (!resolvedTarget) {
      if (user.value?.role === 'GURU_PEMBIMBING') {
        resolvedTarget = 'GURU_PENGAJAR'
      } else if (user.value?.role === 'GURU_PENGAJAR') {
        resolvedTarget = 'GURU_PEMBIMBING'
      } else if (user.value?.role === 'SEKRETARIS') {
        resolvedTarget = 'SISWA'
      } else if (user.value?.role === 'SISWA') {
        resolvedTarget = 'SEKRETARIS'
      }
    }

    const res = await $fetch<{ message: string; user: User }>(`${apiBase}/auth/switch-role`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: { role: resolvedTarget },
    })
    clearDashboardCache()
    user.value = res.user
    if (import.meta.client) {
      localStorage.setItem('user', JSON.stringify(res.user))
      await navigateTo('/dashboard')
    }
    return res
  }

  return {
    user,
    token,
    role,
    isAuthenticated,
    isDualRole,
    apiBase,
    ensureAuth,
    setAuth,
    login,
    logout,
    fetchUser,
    switchRole,
  }
})
