<template>
  <aside
    class="bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-800 flex flex-col justify-between h-screen fixed left-0 top-0 z-30 select-none transition-all duration-300 ease-in-out"
    :class="uiStore.isCollapsed ? 'w-20' : 'w-72'"
  >
    <!-- Top Section -->
    <div :class="uiStore.isCollapsed ? 'px-2.5 py-6' : 'px-4 py-6'">
      <!-- Logo Header -->
      <div
        class="flex items-center mb-6"
        :class="uiStore.isCollapsed ? 'justify-center' : 'gap-3'"
      >
        <NuxtLink
          to="/dashboard"
          class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center p-1.5 flex-shrink-0 cursor-pointer hover:bg-primary/15 transition"
          title="Jurnal Kelas - SMKN 71 Jakarta"
        >
          <img src="/images/logo-smkn71.png" alt="Logo SMKN 71" class="w-full h-full object-contain" />
        </NuxtLink>
        <div v-if="!uiStore.isCollapsed" class="min-w-0 flex-1">
          <h1 class="text-base font-bold text-gray-900 leading-tight truncate">Jurnal Kelas</h1>
          <p class="text-[10px] font-bold text-primary tracking-wider uppercase truncate">SMKN 71 Jakarta</p>
        </div>
      </div>

      <!-- User Card (Clickable to /profile) -->
      <NuxtLink
        to="/profile"
        class="border border-gray-100 hover:border-blue-100 dark:border-slate-800 rounded-2xl mb-6 transition group cursor-pointer"
        :class="[
          uiStore.isCollapsed
            ? 'p-2 flex items-center justify-center bg-gray-50 dark:bg-slate-800/60 hover:bg-blue-50 dark:hover:bg-slate-800'
            : 'p-3 flex items-center gap-2.5 bg-gray-50 dark:bg-slate-800/60 hover:bg-blue-50/60 dark:hover:bg-slate-800'
        ]"
        :title="authStore.user?.name ? `Profil: ${authStore.user.name}` : 'Buka Pengaturan Profil'"
      >
        <div class="relative w-9 h-9 rounded-full bg-blue-100 text-primary font-bold flex items-center justify-center text-xs flex-shrink-0 group-hover:ring-2 group-hover:ring-primary/20 transition overflow-hidden">
          <span>{{ userInitial }}</span>
          <img
            v-if="authStore.user?.avatar || authStore.user?.avatar_url"
            :src="authStore.user?.avatar || authStore.user?.avatar_url"
            alt="Avatar"
            class="absolute inset-0 w-full h-full object-cover"
            @error="(e: any) => e.target.style.display = 'none'"
          />
        </div>
        <div v-if="!uiStore.isCollapsed" class="min-w-0 flex-1">
          <p class="text-xs font-bold text-gray-900 dark:text-slate-100 group-hover:text-primary transition truncate" :title="authStore.user?.name">{{ authStore.user?.name || 'Pengguna' }}</p>
          <p class="text-[11px] text-gray-500 dark:text-slate-400 truncate" :title="userSubtitle">{{ userSubtitle }}</p>
        </div>
        <User v-if="!uiStore.isCollapsed" class="w-3.5 h-3.5 text-gray-400 group-hover:text-primary transition flex-shrink-0" />
      </NuxtLink>

      <!-- Navigation Menu -->
      <nav class="space-y-1">
        <NuxtLink
          v-for="item in currentMenus"
          :key="item.to"
          :to="item.to"
          class="rounded-xl transition-all duration-200"
          :class="[
            uiStore.isCollapsed
              ? 'p-2.5 flex items-center justify-center'
              : 'flex items-center gap-3 px-3.5 py-2.5 text-sm font-medium',
            isActive(item.to)
              ? 'bg-blue-50 text-primary dark:bg-blue-950/70 dark:text-blue-400 font-semibold shadow-2xs'
              : 'text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-gray-900 dark:hover:text-white'
          ]"
          :title="uiStore.isCollapsed ? item.name : undefined"
        >
          <component
            :is="resolveIcon(item.icon)"
            class="w-5 h-5 flex-shrink-0 transition-transform"
            :class="[
              isActive(item.to) ? 'text-primary dark:text-blue-400 scale-105' : 'text-gray-400 dark:text-slate-500 group-hover:text-gray-700 dark:group-hover:text-slate-200',
              uiStore.isCollapsed ? 'mx-auto' : ''
            ]"
          />
          <span v-if="!uiStore.isCollapsed" class="truncate text-[13px] font-medium leading-snug">{{ item.name }}</span>
        </NuxtLink>
      </nav>
    </div>

    <!-- Bottom Section: Academic Year & Collapse Toggle -->
    <div
      class="border-t border-gray-100 dark:border-slate-800 transition-all"
      :class="uiStore.isCollapsed ? 'p-3 flex flex-col items-center gap-2' : 'p-4'"
    >
      <div v-if="!uiStore.isCollapsed" class="mb-3 px-1">
        <p class="text-[10px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider">Tahun Ajaran</p>
        <p class="text-xs font-bold text-gray-800 dark:text-slate-200 mt-0.5 truncate">{{ appConfig.active_term }}</p>
      </div>

      <!-- Quick Collapse Button on Sidebar Footer -->
      <button
        type="button"
        @click="uiStore.toggleSidebar"
        class="w-full py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-slate-700 hover:border-blue-200 hover:text-primary text-gray-600 dark:text-slate-300 transition flex items-center justify-center shadow-2xs cursor-pointer active:scale-95"
        :title="uiStore.isCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
      >
        <component
          :is="uiStore.isCollapsed ? PanelLeftOpen : PanelLeftClose"
          class="w-4 h-4 text-gray-500 hover:text-primary shrink-0"
        />
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import {
  Book,
  Home,
  BookOpen,
  CalendarCheck,
  ClipboardCheck,
  FileText,
  Mail,
  Calendar,
  FolderOpen,
  Edit3,
  BarChart2,
  MailCheck,
  FileSpreadsheet,
  Users,
  User,
  Layers,
  ShieldAlert,
  Radio,
  Settings,
  GraduationCap,
  MessageSquare,
  PanelLeftClose,
  PanelLeftOpen,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useAppConfigStore } from '~/stores/appConfig'
import { useUiStore } from '~/stores/ui'
import { ROLE_MENUS, ROLE_LABELS } from '~/config/access'

const route = useRoute()
const authStore = useAuthStore()
const appConfig = useAppConfigStore()
const uiStore = useUiStore()

onMounted(() => {
  if (!appConfig.loaded) {
    appConfig.fetchConfig()
  }
})

const currentMenus = computed(() => {
  const role = authStore.user?.role || 'SISWA'
  return ROLE_MENUS[role] || ROLE_MENUS.SISWA
})

const userInitial = computed(() => {
  return authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U'
})

const userSubtitle = computed(() => {
  if (authStore.user?.role === 'SUPERADMIN') {
    return 'Super Administrator'
  }
  if (authStore.user?.role === 'SISWA') {
    return authStore.user.school_class?.name ? `Siswa ${authStore.user.school_class.name}` : 'Siswa'
  }
  if (authStore.user?.role === 'SEKRETARIS') {
    return authStore.user.school_class?.name ? `Sekretaris ${authStore.user.school_class.name}` : 'Sekretaris'
  }
  if (authStore.user?.role === 'GURU_PEMBIMBING') {
    const cName = authStore.user.guided_class?.name || authStore.user.school_class?.name
    return cName ? `Wali Kelas ${cName}` : 'Wali Kelas'
  }
  if (authStore.user?.role === 'ORANG_TUA') {
    return authStore.user.specialization || 'Orang Tua'
  }
  return ROLE_LABELS[authStore.user?.role || ''] || 'Guru'
})

function isActive(path: string) {
  if (path === '/dashboard') return route.path === '/dashboard' || route.path === '/'
  if (path === '/riwayat-kehadiran') return route.path.startsWith('/riwayat-kehadiran') || route.path.startsWith('/surat-izin')
  if (path === '/persetujuan-izin') return route.path.startsWith('/persetujuan-izin') || route.path.startsWith('/laporan-izin')
  if (path === '/manajemen-user') return route.path.startsWith('/manajemen-user') || route.path.startsWith('/manajemen-guru')
  if (path === '/rekap-semester') return route.path.startsWith('/rekap-semester')
  return route.path.startsWith(path)
}

function resolveIcon(iconName: string) {
  const map: Record<string, any> = {
    Home,
    BookOpen,
    CalendarCheck,
    ClipboardCheck,
    FileText,
    Mail,
    Calendar,
    FolderOpen,
    Edit3,
    BarChart2,
    MailCheck,
    FileSpreadsheet,
    Users,
    Layers,
    ShieldAlert,
    Radio,
    Settings,
    GraduationCap,
    MessageSquare,
  }
  return map[iconName] || Home
}
</script>
