import { useAuthStore } from '~/stores/auth'

const ROLE_ALLOWED_ROUTES: Record<string, string[]> = {
  SISWA: [
    '/dashboard',
    '/profile',
    '/jurnal-pelajaran',
    '/riwayat-kehadiran',
    '/notifikasi',
  ],
  SEKRETARIS: [
    '/dashboard',
    '/profile',
    '/presensi',
    '/jurnal-kelas',
    '/notifikasi',
  ],
  GURU_PENGAJAR: [
    '/dashboard',
    '/profile',
    '/jurnal-materi',
    '/rekap-semester',
    '/notifikasi',
  ],
  GURU_PEMBIMBING: [
    '/dashboard',
    '/profile',
    '/presensi',
    '/jurnal-materi',
    '/persetujuan-izin',
    '/manajemen-murid',
    '/rekap-semester',
    '/notifikasi',
  ],
  ORANG_TUA: [
    '/dashboard',
    '/profile',
    '/jurnal-pelajaran',
    '/riwayat-kehadiran',
    '/notifikasi',
  ],
  SUPERADMIN: [
    '/dashboard',
    '/profile',
    '/master-data',
    '/manajemen-murid',
    '/manajemen-user',
    '/wa-gateway',
    '/pengaturan-sistem',
    '/presensi',
    '/jurnal-kelas',
    '/jurnal-materi',
    '/jurnal-pelajaran',
    '/persetujuan-izin',
    '/rekap-semester',
    '/riwayat-kehadiran',
    '/notifikasi',
  ],
}

export default defineNuxtRouteMiddleware((to, from) => {
  // Public pages
  if (to.path === '/' || to.path === '/login' || to.path.startsWith('/forgot-password')) {
    return
  }

  // Client-side execution
  if (process.client) {
    const authStore = useAuthStore()

    // 1. Check Authentication
    if (!authStore.isAuthenticated) {
      const token = localStorage.getItem('token')
      const user = localStorage.getItem('user')
      if (token && user) {
        authStore.token = token
        authStore.user = JSON.parse(user)
      } else {
        return navigateTo('/login')
      }
    }

    // 2. Redirect root path to dashboard & unified routes
    if (to.path === '/') {
      return navigateTo('/dashboard')
    }
    if (to.path === '/laporan-izin') {
      return navigateTo('/persetujuan-izin')
    }
    if (to.path === '/laporan-jurnal') {
      return navigateTo('/dashboard')
    }
    if (to.path === '/surat-izin') {
      return navigateTo('/riwayat-kehadiran?tab=izin')
    }
    if (to.path === '/presensi-override') {
      return navigateTo('/presensi')
    }
    if (to.path === '/manajemen-guru') {
      return navigateTo('/manajemen-user?category=GURU')
    }
    if (to.path === '/rekap-harian') {
      return navigateTo('/jurnal-kelas')
    }
    if (to.path === '/broadcast') {
      return navigateTo('/wa-gateway')
    }

    // 3. Check Role-Based Access Control (RBAC)
    const userRole = authStore.user?.role
    // Superadmin has full unrestricted access to all routes
    if (userRole === 'SUPERADMIN') {
      return
    }

    if (userRole && ROLE_ALLOWED_ROUTES[userRole]) {
      const allowed = [...ROLE_ALLOWED_ROUTES[userRole]]
      if (authStore.isDualRole) {
        allowed.push('/persetujuan-izin', '/manajemen-murid', '/jurnal-kelas', '/jurnal-materi')
      }
      const isAllowed = allowed.some((prefix) => to.path === prefix || to.path.startsWith(prefix + '/'))
      if (!isAllowed) {
        return navigateTo('/dashboard')
      }
    }
  }
})
