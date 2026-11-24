<template>
  <header class="bg-white md:bg-transparent dark:bg-slate-900 md:dark:bg-transparent border-b md:border-b-0 border-gray-200 dark:border-slate-800 px-3 sm:px-4 md:px-8 py-2.5 sm:py-3 md:py-0 md:h-20 select-none sticky md:static top-0 z-30 shadow-2xs md:shadow-none flex items-center justify-between min-w-0 pt-safe">
    <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1 mr-2 sm:mr-3">
      <button
        v-if="showBack"
        @click="handleBack"
        type="button"
        class="flex items-center gap-1.5 px-2.5 sm:px-3 py-2 sm:py-2.5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:border-gray-300 dark:hover:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-xl text-xs sm:text-sm font-semibold text-gray-700 dark:text-slate-200 transition shadow-2xs shrink-0 cursor-pointer active:scale-95 min-h-[40px] sm:min-h-[44px]"
      >
        <ArrowLeft class="w-4 h-4 md:w-5 md:h-5" />
        <span class="hidden sm:inline">Kembali</span>
      </button>

      <div v-else class="md:hidden w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 flex items-center justify-center p-1 shrink-0 shadow-xs">
        <img src="/images/logo-smkn71.png" alt="Logo SMKN 71" class="w-full h-full object-contain" />
      </div>

      <div class="min-w-0 flex-1">
        <h2 class="text-xs sm:text-lg md:text-2xl font-bold text-gray-900 dark:text-white tracking-tight leading-snug line-clamp-2 sm:line-clamp-none sm:truncate" :title="title">{{ title }}</h2>
        <p v-if="subtitle" class="hidden sm:block text-xs md:text-sm text-gray-500 dark:text-slate-400 mt-0.5 truncate leading-normal">{{ subtitle }}</p>
      </div>
    </div>

    <div class="flex items-center gap-1 sm:gap-2 md:gap-3 shrink-0 max-w-[60%] sm:max-w-none justify-end">
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <slot name="actions" />
      </div>

      <!-- Dual Role Switcher (Guru ↔ Wali Kelas & Siswa ↔ Sekretaris) -->
      <button
        v-if="authStore.isDualRole"
        type="button"
        @click="toggleDualRole"
        :disabled="isSwitchingRole"
        class="inline-flex items-center gap-1.5 px-2 sm:px-3 py-1.5 rounded-xl border border-primary/30 dark:border-blue-500/40 bg-primary/10 dark:bg-blue-950/40 hover:bg-primary hover:text-white text-primary dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white text-xs font-bold transition shadow-2xs cursor-pointer min-h-[36px] sm:min-h-[40px] shrink-0 active:scale-95 disabled:opacity-50"
        :title="dualRoleTooltip"
      >
        <RefreshCw class="w-3.5 h-3.5 shrink-0" :class="{ 'animate-spin': isSwitchingRole }" />
        <span class="hidden sm:inline">{{ dualRoleLabel }}</span>
        <span class="sm:hidden text-[11px]">{{ dualRoleShortLabel }}</span>
      </button>

      <!-- Dark Mode Toggle Button -->
      <button
        type="button"
        @click="uiStore.toggleDark"
        class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 flex items-center justify-center text-gray-600 dark:text-amber-400 hover:text-gray-900 dark:hover:text-amber-300 hover:border-gray-300 dark:hover:border-slate-600 transition shadow-2xs min-h-[34px] min-w-[34px] sm:min-h-[44px] sm:min-w-[44px] active:scale-95 shrink-0 cursor-pointer"
        :title="uiStore.isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
      >
        <Sun v-if="uiStore.isDark" class="w-4 h-4 md:w-5 md:h-5 text-amber-400 transition-transform duration-300" />
        <Moon v-else class="w-4 h-4 md:w-5 md:h-5 text-gray-600 transition-transform duration-300" />
      </button>

      <!-- Notification Bell -->
      <NuxtLink
        v-if="!hideNotifications"
        to="/notifikasi"
        class="relative w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 flex items-center justify-center text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-slate-600 transition shadow-2xs min-h-[34px] min-w-[34px] sm:min-h-[44px] sm:min-w-[44px] active:scale-95 shrink-0"
        title="Pemberitahuan"
      >
        <Bell class="w-4 h-4 md:w-5 md:h-5" />
        <span
          v-if="unreadCount > 0"
          class="absolute -top-1 -right-1 w-4 h-4 md:w-5 md:h-5 bg-primary text-white text-[9px] md:text-[11px] font-bold rounded-full flex items-center justify-center ring-2 ring-white dark:ring-slate-900"
        >
          {{ unreadCount }}
        </span>
      </NuxtLink>
    </div>
  </header>
</template>

<script setup lang="ts">
import {
  Bell,
  LogOut,
  ArrowLeft,
  Book,
  User,
  RefreshCw,
  Sun,
  Moon,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useUiStore } from '~/stores/ui'

const props = withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    showBack?: boolean
    backTo?: string
    hideNotifications?: boolean
    hideLogout?: boolean
  }>(),
  {
    title: 'Jurnal Kelas',
    subtitle: '',
    showBack: false,
    backTo: '',
    hideNotifications: false,
    hideLogout: false,
  }
)

const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUiStore()
const unreadCount = ref(0)
const isSwitchingRole = ref(false)

const dualRoleLabel = computed(() => {
  if (authStore.user?.role === 'GURU_PEMBIMBING') return 'Mode: Wali Kelas'
  if (authStore.user?.role === 'GURU_PENGAJAR') return 'Mode: Guru'
  if (authStore.user?.role === 'SEKRETARIS') return 'Mode: Sekretaris'
  if (authStore.user?.role === 'SISWA') return 'Mode: Siswa'
  return 'Ganti Peran'
})

const dualRoleShortLabel = computed(() => {
  if (authStore.user?.role === 'GURU_PEMBIMBING') return 'Walas'
  if (authStore.user?.role === 'GURU_PENGAJAR') return 'Guru'
  if (authStore.user?.role === 'SEKRETARIS') return 'Sekre'
  if (authStore.user?.role === 'SISWA') return 'Siswa'
  return 'Peran'
})

const dualRoleTooltip = computed(() => {
  if (authStore.user?.role === 'GURU_PEMBIMBING') return 'Klik untuk beralih ke Mode Guru Pengajar'
  if (authStore.user?.role === 'GURU_PENGAJAR') return 'Klik untuk beralih ke Mode Wali Kelas'
  if (authStore.user?.role === 'SEKRETARIS') return 'Klik untuk beralih ke Mode Siswa (Aktivitas Belajar Mandiri)'
  if (authStore.user?.role === 'SISWA') return 'Klik untuk beralih ke Mode Sekretaris (Kelola Presensi & Agenda Kelas)'
  return 'Beralih Peran'
})

const toggleDualRole = async () => {
  isSwitchingRole.value = true
  try {
    await authStore.switchRole()
  } catch (e) {
    console.error('Failed to switch role:', e)
  } finally {
    isSwitchingRole.value = false
  }
}

onMounted(async () => {
  if (authStore.token) {
    try {
      const res = await $fetch<{ unread_count: number }>(`${authStore.apiBase}/notifications`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      })
      if (res && typeof res.unread_count === 'number') {
        unreadCount.value = res.unread_count
      }
    } catch (e) {
      // fallback
    }
  }
})

function handleBack() {
  if (props.backTo) {
    navigateTo(props.backTo)
  } else {
    router.back()
  }
}

async function handleLogout() {
  await authStore.logout()
}
</script>
