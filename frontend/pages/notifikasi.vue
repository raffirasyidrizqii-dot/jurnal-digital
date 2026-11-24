<template>
  <!-- Floating Centered Modal Card Container -->
  <div
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-5 md:p-6"
    @click.self="handleClose"
  >
    <div
      class="max-w-2xl w-full bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 flex flex-col max-h-[92vh] sm:max-h-[90vh] overflow-hidden my-0 sm:my-auto animate-in fade-in zoom-in-95 duration-150 pb-safe"
    >
      <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto my-2 sm:hidden shrink-0"></div>
      <!-- Modal Header -->
      <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between gap-3 bg-white dark:bg-slate-900 sticky top-0 z-10">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-primary dark:text-blue-300 flex items-center justify-center shrink-0 shadow-2xs">
            <BellIcon class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h2 class="text-base font-bold text-gray-900 dark:text-white truncate">Pemberitahuan</h2>
              <span
                v-if="unreadCount > 0"
                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 dark:bg-blue-950/60 text-primary dark:text-blue-300 shrink-0"
              >
                {{ unreadCount }} baru
              </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-slate-400 truncate mt-0.5">Aktivitas pembelajaran, materi, dan persetujuan</p>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <!-- Satu tombol pintar: filter 'Semua' → tandai semua; filter kategori → tandai kategori itu -->
          <button
            @click="markSmartRead"
            type="button"
            class="px-3 py-1.5 rounded-xl bg-gray-50 hover:bg-gray-100 dark:bg-slate-800 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-700 dark:text-slate-200 transition items-center gap-1.5 shadow-2xs cursor-pointer inline-flex"
            :title="categoryMarkTooltip"
          >
            <CheckCheckIcon class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
            <span>{{ smartMarkLabel }}</span>
          </button>

          <!-- Close Button -->
          <button
            @click="handleClose"
            type="button"
            class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs active:scale-95"
            title="Tutup jendela notifikasi"
          >
            <XIcon class="w-4 h-4 text-gray-500 dark:text-slate-400" />
            <span>Tutup</span>
          </button>
        </div>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="p-5 overflow-y-auto space-y-4 flex-1">
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-3">
          <div class="flex flex-wrap items-center gap-1.5">
            <button
              @click="switchFilter('ALL')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer whitespace-nowrap',
                filterType === 'ALL'
                  ? 'bg-primary text-white shadow-xs'
                  : 'bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700'
              ]"
            >
              Semua
            </button>

            <button
              @click="switchFilter('UNREAD')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap',
                filterType === 'UNREAD'
                  ? 'bg-primary text-white shadow-xs'
                  : 'bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700'
              ]"
            >
              <span>Belum Dibaca</span>
              <span
                v-if="unreadCount > 0"
                class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                :class="filterType === 'UNREAD' ? 'bg-white text-primary' : 'bg-blue-100 dark:bg-blue-950/60 text-primary dark:text-blue-300'"
              >
                {{ unreadCount }}
              </span>
            </button>

            <button
              @click="switchFilter('JURNAL')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap',
                filterType === 'JURNAL'
                  ? 'bg-primary text-white shadow-xs'
                  : 'bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700'
              ]"
            >
              <span>Jurnal</span>
              <span
                v-if="unreadJurnalCount > 0"
                class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                :class="filterType === 'JURNAL' ? 'bg-white text-primary' : 'bg-blue-100 dark:bg-blue-950/60 text-primary dark:text-blue-300'"
              >
                {{ unreadJurnalCount }}
              </span>
            </button>

            <button
              @click="switchFilter('SURAT_IZIN')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap',
                filterType === 'SURAT_IZIN'
                  ? 'bg-primary text-white shadow-xs'
                  : 'bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700'
              ]"
            >
              <span>Surat Izin</span>
              <span
                v-if="unreadSuratIzinCount > 0"
                class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                :class="filterType === 'SURAT_IZIN' ? 'bg-white text-primary' : 'bg-blue-100 dark:bg-blue-950/60 text-primary dark:text-blue-300'"
              >
                {{ unreadSuratIzinCount }}
              </span>
            </button>

            <button
              @click="switchFilter('PRESENSI')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap',
                filterType === 'PRESENSI'
                  ? 'bg-primary text-white shadow-xs'
                  : 'bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700'
              ]"
            >
              <span>Presensi</span>
              <span
                v-if="unreadPresensiCount > 0"
                class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                :class="filterType === 'PRESENSI' ? 'bg-white text-primary' : 'bg-blue-100 dark:bg-blue-950/60 text-primary dark:text-blue-300'"
              >
                {{ unreadPresensiCount }}
              </span>
            </button>
          </div>
        </div>

        <!-- List Notifikasi Items -->
        <div v-if="loading" class="py-12 text-center text-gray-400 dark:text-slate-500 text-xs">
          Memuat data notifikasi...
        </div>

        <div v-else-if="filteredNotifications.length === 0" class="py-12 text-center text-gray-400 dark:text-slate-500 text-xs">
          <BellOffIcon class="w-8 h-8 text-gray-300 dark:text-slate-600 mx-auto mb-2" />
          Tidak ada notifikasi dalam kategori ini.
        </div>

        <div v-else class="space-y-2.5">
          <div
            v-for="item in filteredNotifications"
            :key="item.id"
            @click="handleNotificationClick(item)"
            class="p-3.5 rounded-2xl border transition flex items-start gap-3.5 cursor-pointer hover:shadow-xs group"
            :class="[
              item.is_read
                ? 'bg-white dark:bg-slate-850 border-gray-150 dark:border-slate-800 hover:border-gray-200 dark:hover:border-slate-700'
                : 'bg-blue-50/40 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800/50 shadow-2xs hover:bg-blue-50/70 dark:hover:bg-blue-950/50'
            ]"
          >
            <div
              class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
              :class="[
                item.type === 'JURNAL'
                  ? 'bg-blue-50 dark:bg-blue-950/50 text-primary dark:text-blue-300'
                  : item.type === 'SURAT_IZIN'
                    ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'
                    : item.type === 'PRESENSI'
                      ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400'
                      : 'bg-blue-50 dark:bg-blue-950/50 text-primary dark:text-blue-300'
              ]"
            >
              <BookOpenIcon v-if="item.type === 'JURNAL'" class="w-4 h-4" />
              <CheckCircle2Icon v-else-if="item.type === 'SURAT_IZIN'" class="w-4 h-4" />
              <ClipboardCheck v-else-if="item.type === 'PRESENSI'" class="w-4 h-4" />
              <Megaphone v-else-if="item.type === 'PENGUMUMAN'" class="w-4 h-4" />
              <UserPlusIcon v-else-if="item.type === 'USER'" class="w-4 h-4" />
              <BellIcon v-else class="w-4 h-4" />
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <h3 class="text-xs font-bold text-gray-900 dark:text-white truncate flex items-center gap-1.5 group-hover:text-primary dark:group-hover:text-blue-400 transition-colors">
                  <span>{{ item.title }}</span>
                  <span v-if="!item.is_read" class="w-2 h-2 rounded-full bg-primary inline-block shrink-0"></span>
                </h3>
                <div class="flex items-center gap-2 shrink-0">
                  <span class="text-[11px] text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ item.time || item.time_ago }}</span>
                  <button
                    type="button"
                    @click.stop="deleteNotification(item.id)"
                    class="p-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/50 text-gray-300 dark:text-slate-600 hover:text-rose-600 dark:hover:text-rose-400 transition cursor-pointer"
                    title="Hapus notifikasi"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
              <p class="text-xs text-gray-600 dark:text-slate-300 mt-1 line-clamp-2">
                {{ item.body }}
              </p>
              <div class="mt-2.5 flex items-center justify-between">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 group-hover:bg-primary text-primary dark:text-blue-300 group-hover:text-white border border-blue-200 dark:border-blue-800/50 text-[11px] font-bold transition-all shadow-2xs">
                  <span>Buka Rincian</span>
                  <span class="transition-transform group-hover:translate-x-0.5">→</span>
                </span>
                <span v-if="!item.is_read" class="text-[10px] font-semibold text-primary dark:text-blue-300 bg-blue-50 dark:bg-blue-950/50 px-2 py-0.5 rounded-md border border-blue-200 dark:border-blue-800/50 whitespace-nowrap">
                  Belum Dibaca
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Log Audit Aktivitas Pengguna: HANYA TAMPIL UNTUK SUPERADMIN -->
        <div v-if="authStore.user?.role === 'SUPERADMIN'" class="mt-6 pt-5 border-t border-gray-150 dark:border-slate-800 space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-xs font-bold text-gray-900 dark:text-white">Log Audit Aktivitas Pengguna</h3>
              <p class="text-[11px] text-gray-400 dark:text-slate-500">Riwayat audit jejak aktivitas sistem real-time</p>
            </div>
            <button
              @click="fetchAuditLogs"
              class="text-xs text-primary dark:text-blue-400 hover:underline font-semibold flex items-center gap-1 cursor-pointer"
            >
              <RefreshCwIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': loadingAudit }" />
              <span>Segarkan</span>
            </button>
          </div>

          <div v-if="loadingAudit" class="py-6 text-center text-gray-400 dark:text-slate-500 text-xs">
            Memuat log audit...
          </div>

          <div v-else-if="auditLogs.length === 0" class="py-6 text-center text-gray-400 dark:text-slate-500 text-xs">
            Belum ada log audit aktivitas pengguna.
          </div>

          <div v-else class="space-y-2">
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2 max-h-48 overflow-y-auto">
              <div
                v-for="log in auditLogs"
                :key="log.id"
                class="p-2.5 bg-gray-50/70 dark:bg-slate-850 border border-gray-150 dark:border-slate-800 rounded-xl space-y-1 text-xs"
              >
                <div class="flex items-center justify-between">
                  <span class="font-bold text-gray-900 dark:text-white">{{ log.user }}</span>
                  <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">{{ log.time }}</span>
                </div>
                <p class="text-gray-600 dark:text-slate-300 text-[11px]">{{ log.action }}</p>
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block border border-gray-150 dark:border-slate-800 rounded-xl overflow-hidden max-h-48 overflow-y-auto text-xs">
              <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-[10px] text-gray-500 dark:text-slate-400 uppercase">
                  <tr>
                    <th class="py-2 px-3">Waktu</th>
                    <th class="py-2 px-3">Pengguna</th>
                    <th class="py-2 px-3">Aktivitas</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                  <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/50">
                    <td class="py-2 px-3 text-gray-400 dark:text-slate-500 text-[11px] whitespace-nowrap">{{ log.time }}</td>
                    <td class="py-2 px-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ log.user }}</td>
                    <td class="py-2 px-3 text-gray-600 dark:text-slate-300">{{ log.action }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50/70 dark:bg-slate-900/90 text-xs">
        <button
          v-if="notifications.length > 0"
          @click="clearAllNotifications"
          type="button"
          class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-semibold cursor-pointer"
        >
          Bersihkan Semua ({{ notifications.length }})
        </button>
        <span v-else class="text-gray-500 dark:text-slate-400 text-[11px]">
          Menampilkan 0 notifikasi
        </span>
        <span class="w-1"></span>
      </div>
    </div>
    <AppConfirmModal />
  </div>
</template>

<script setup lang="ts">
import {
  Bell as BellIcon,
  BellOff as BellOffIcon,
  CheckCheck as CheckCheckIcon,
  BookOpen as BookOpenIcon,
  CheckCircle2 as CheckCircle2Icon,
  UserPlus as UserPlusIcon,
  RefreshCw as RefreshCwIcon,
  ClipboardCheck,
  Megaphone,
  Trash2,
  X as XIcon,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { confirmDialog } from '~/utils/confirm'

definePageMeta({
  layout: false
})

const router = useRouter()
const config = useRuntimeConfig()
const authStore = useAuthStore()

const filterType = ref('ALL')
const loading = ref(false)
const loadingAudit = ref(false)

const unreadCount = ref(0)
const unreadSuratIzinCount = ref(0)
const unreadJurnalCount = ref(0)
const unreadPresensiCount = ref(0)

const notifications = ref<any[]>([])
const auditLogs = ref<any[]>([])

const filteredNotifications = computed(() => {
  if (filterType.value === 'ALL') return notifications.value
  if (filterType.value === 'UNREAD') return notifications.value.filter(n => !n.is_read)
  return notifications.value.filter(n => n.type === filterType.value)
})

function handleClose() {
  if (process.client) {
    if (window.history.length > 1) {
      router.back()
    } else {
      navigateTo('/dashboard')
    }
  }
}

const switchFilter = (type: string) => {
  filterType.value = type
}

const categoryMarkTooltip = computed(() => {
  if (filterType.value === 'SURAT_IZIN') return 'Tandai notifikasi Surat Izin telah dibaca'
  if (filterType.value === 'JURNAL') return 'Tandai notifikasi Jurnal telah dibaca'
  if (filterType.value === 'PRESENSI') return 'Tandai notifikasi Presensi telah dibaca'
  return 'Tandai notifikasi telah dibaca'
})

// Satu tombol pintar: label & aksi mengikuti filter aktif (menggantikan 2 tombol duplikat)
const smartMarkLabel = computed(() => {
  if (filterType.value === 'SURAT_IZIN') return 'Tandai Izin Dibaca'
  if (filterType.value === 'JURNAL') return 'Tandai Jurnal Dibaca'
  if (filterType.value === 'PRESENSI') return 'Tandai Presensi Dibaca'
  return 'Tandai Semua Dibaca'
})
const markSmartRead = async () => {
  await markCategoryRead()
}

const fetchNotifications = async () => {
  loading.value = true
  const token = authStore.token || (process.client ? localStorage.getItem('token') : null)
  try {
    const res: any = await $fetch(`${config.public.apiBase}/notifications`, {
      headers: { Authorization: `Bearer ${token}` },
      params: { filter: 'ALL' }
    })
    notifications.value = res.notifications || []
    unreadCount.value = res.unread_count ?? 0
    unreadSuratIzinCount.value = typeof res.unread_surat_izin_count === 'number'
      ? res.unread_surat_izin_count
      : notifications.value.filter(n => n.type === 'SURAT_IZIN' && !n.is_read).length
    unreadJurnalCount.value = typeof res.unread_jurnal_count === 'number'
      ? res.unread_jurnal_count
      : notifications.value.filter(n => n.type === 'JURNAL' && !n.is_read).length
    unreadPresensiCount.value = typeof res.unread_presensi_count === 'number'
      ? res.unread_presensi_count
      : notifications.value.filter(n => n.type === 'PRESENSI' && !n.is_read).length
  } catch (err: any) {
    // Keep empty
  } finally {
    loading.value = false
  }
}

const fetchAuditLogs = async () => {
  if (authStore.user?.role !== 'SUPERADMIN') return
  loadingAudit.value = true
  const token = authStore.token || (process.client ? localStorage.getItem('token') : null)
  try {
    const res: any = await $fetch(`${config.public.apiBase}/audit-logs`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    if (res && res.length > 0) {
      auditLogs.value = res
    }
  } catch (err: any) {
    // Keep defaults
  } finally {
    loadingAudit.value = false
  }
}

const markAllRead = async () => {
  notifications.value.forEach(n => { n.is_read = true })
  unreadCount.value = 0
  unreadSuratIzinCount.value = 0
  unreadJurnalCount.value = 0
  unreadPresensiCount.value = 0
  const token = authStore.token || (process.client ? localStorage.getItem('token') : null)
  try {
    await $fetch(`${config.public.apiBase}/notifications/read-all`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` }
    })
  } catch (err: any) {
    console.error('Error marking all read:', err)
  }
}

const markCategoryRead = async () => {
  const currentFilter = filterType.value
  const token = authStore.token || (process.client ? localStorage.getItem('token') : null)

  if (currentFilter === 'SURAT_IZIN') {
    const unreadSuratIzin = notifications.value.filter(n => n.type === 'SURAT_IZIN' && !n.is_read).length
    if (unreadSuratIzin === 0) return

    notifications.value.forEach(n => {
      if (n.type === 'SURAT_IZIN') n.is_read = true
    })
    unreadSuratIzinCount.value = 0
    unreadCount.value = Math.max(0, unreadCount.value - unreadSuratIzin)

    try {
      await $fetch(`${config.public.apiBase}/notifications/read-all`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: { type: 'SURAT_IZIN' }
      })
    } catch (err: any) {
      console.error('Error marking surat izin read:', err)
    }
  } else if (currentFilter === 'JURNAL') {
    const unreadJurnal = notifications.value.filter(n => n.type === 'JURNAL' && !n.is_read).length
    if (unreadJurnal === 0) return

    notifications.value.forEach(n => {
      if (n.type === 'JURNAL') n.is_read = true
    })
    unreadJurnalCount.value = 0
    unreadCount.value = Math.max(0, unreadCount.value - unreadJurnal)

    try {
      await $fetch(`${config.public.apiBase}/notifications/read-all`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: { type: 'JURNAL' }
      })
    } catch (err: any) {
      console.error('Error marking jurnal read:', err)
    }
  } else if (currentFilter === 'PRESENSI') {
    const unreadPresensi = notifications.value.filter(n => n.type === 'PRESENSI' && !n.is_read).length
    if (unreadPresensi === 0) return

    notifications.value.forEach(n => {
      if (n.type === 'PRESENSI') n.is_read = true
    })
    unreadPresensiCount.value = 0
    unreadCount.value = Math.max(0, unreadCount.value - unreadPresensi)

    try {
      await $fetch(`${config.public.apiBase}/notifications/read-all`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: { type: 'PRESENSI' }
      })
    } catch (err: any) {
      console.error('Error marking presensi read:', err)
    }
  } else {
    await markAllRead()
  }
}

const markOneRead = async (item: any) => {
  if (!item.is_read) {
    item.is_read = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
    if (item.type === 'SURAT_IZIN') {
      unreadSuratIzinCount.value = Math.max(0, unreadSuratIzinCount.value - 1)
    } else if (item.type === 'JURNAL') {
      unreadJurnalCount.value = Math.max(0, unreadJurnalCount.value - 1)
    } else if (item.type === 'PRESENSI') {
      unreadPresensiCount.value = Math.max(0, unreadPresensiCount.value - 1)
    }
    const token = authStore.token || (process.client ? localStorage.getItem('token') : null)
    try {
      await $fetch(`${config.public.apiBase}/notifications/${item.id}/read`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` }
      })
    } catch (err: any) {
      // Handled in state
    }
  }
}

const deleteNotification = async (id: number) => {
  const ok = await confirmDialog({
    title: 'Hapus Notifikasi',
    message: 'Apakah Anda yakin ingin menghapus notifikasi ini dari riwayat?',
    confirmText: 'Ya, Hapus',
    type: 'danger'
  })
  if (!ok) return

  const token = authStore.token || (process.client ? localStorage.getItem('token') : null)
  try {
    await $fetch(`${config.public.apiBase}/notifications/${id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` }
    })
    const idx = notifications.value.findIndex(n => n.id === id)
    if (idx !== -1) {
      const item = notifications.value[idx]
      if (!item.is_read) {
        unreadCount.value = Math.max(0, unreadCount.value - 1)
        if (item.type === 'SURAT_IZIN') unreadSuratIzinCount.value = Math.max(0, unreadSuratIzinCount.value - 1)
        if (item.type === 'JURNAL') unreadJurnalCount.value = Math.max(0, unreadJurnalCount.value - 1)
        if (item.type === 'PRESENSI') unreadPresensiCount.value = Math.max(0, unreadPresensiCount.value - 1)
      }
      notifications.value.splice(idx, 1)
    }
  } catch (e) {
    console.error('Error deleting notification:', e)
  }
}

const clearAllNotifications = async () => {
  const ok = await confirmDialog({
    title: 'Hapus Seluruh Notifikasi',
    message: 'Apakah Anda yakin ingin menghapus seluruh riwayat notifikasi Anda?',
    confirmText: 'Ya, Hapus Semua',
    type: 'danger'
  })
  if (!ok) return
  const token = authStore.token || (process.client ? localStorage.getItem('token') : null)
  try {
    await $fetch(`${config.public.apiBase}/notifications/clear-all`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` }
    })
    notifications.value = []
    unreadCount.value = 0
    unreadSuratIzinCount.value = 0
    unreadJurnalCount.value = 0
    unreadPresensiCount.value = 0
  } catch (e) {
    console.error('Error clearing notifications:', e)
  }
}

const handleNotificationClick = async (item: any) => {
  await markOneRead(item)

  const role = authStore.user?.role
  const type = item.type
  const title = (item.title || '').toLowerCase()
  const body = (item.body || '').toLowerCase()

  if (type === 'SURAT_IZIN' || title.includes('surat izin') || body.includes('surat izin')) {
    if (role === 'GURU_PEMBIMBING') {
      navigateTo('/persetujuan-izin')
    } else if (role === 'SISWA' || role === 'ORANG_TUA' || role === 'SEKRETARIS') {
      navigateTo('/riwayat-kehadiran?tab=izin')
    } else {
      navigateTo('/persetujuan-izin?tab=rekap')
    }
  } else if (type === 'JURNAL' || title.includes('jurnal') || body.includes('materi')) {
    if (role === 'SISWA' || role === 'ORANG_TUA') {
      navigateTo('/jurnal-pelajaran')
    } else if (role === 'GURU_PENGAJAR') {
      navigateTo('/jurnal-materi')
    } else if (role === 'SEKRETARIS') {
      if (title.includes('materi') || body.includes('materi') || title.includes('tugas') || body.includes('tugas')) {
        navigateTo('/jurnal-pelajaran')
      } else {
        navigateTo('/jurnal-kelas')
      }
    } else {
      navigateTo('/dashboard')
    }
  } else if (type === 'PRESENSI' || title.includes('presensi') || body.includes('presensi')) {
    if (role === 'SISWA' || role === 'ORANG_TUA') {
      navigateTo('/riwayat-kehadiran')
    } else if (role === 'SEKRETARIS' || role === 'GURU_PENGAJAR' || role === 'GURU_PEMBIMBING' || role === 'SUPERADMIN') {
      navigateTo('/presensi')
    } else {
      navigateTo('/dashboard')
    }
  } else if (type === 'USER') {
    navigateTo(role === 'GURU_PEMBIMBING' ? '/manajemen-murid' : (role === 'SUPERADMIN' ? '/manajemen-user' : '/profile'))
  } else {
    navigateTo('/dashboard')
  }
}

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    handleClose()
  }
}

onMounted(() => {
  fetchNotifications()
  if (authStore.user?.role === 'SUPERADMIN') {
    fetchAuditLogs()
  }
  if (process.client) {
    window.addEventListener('keydown', handleKeydown)
  }
})

onUnmounted(() => {
  if (process.client) {
    window.removeEventListener('keydown', handleKeydown)
  }
})
</script>
