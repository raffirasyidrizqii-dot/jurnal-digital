<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] sm:text-xs font-semibold select-none whitespace-nowrap shrink-0 text-center justify-center leading-tight transition-colors shadow-2xs',
      badgeClass
    ]"
  >
    <span>{{ cleanedLabel }}</span>
  </span>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const props = withDefaults(
  defineProps<{
    status: string
    hasDot?: boolean
    role?: string
    context?: string
    customLabel?: string
  }>(),
  {
    hasDot: false,
    role: '',
    context: '',
    customLabel: ''
  }
)

const authStore = useAuthStore()

const badgeClass = computed(() => {
  const s = props.status?.toUpperCase() || ''
  switch (s) {
    case 'HADIR':
      return 'bg-emerald-50/90 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/60 font-bold'
    case 'SAKIT':
      return 'bg-amber-50/90 text-amber-700 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/60 font-semibold'
    case 'IZIN':
      return 'bg-blue-50/90 text-blue-700 border border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/60 font-semibold'
    case 'IZIN_KELUAR':
    case 'IZIN_KBM':
      return 'bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700 font-bold'
    case 'BELUM_PRESENSI':
    case 'BELUM_ABSEN':
      return 'bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
    case 'ALPHA':
    case 'ALFA':
      return 'bg-rose-50/90 text-rose-700 border border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/60 font-bold'
    case 'DISPENSASI':
      return 'bg-indigo-50/90 text-indigo-700 border border-indigo-200/80 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800/60 font-semibold'
    case 'TERVERIFIKASI':
    case 'APPROVED':
    case 'DISETUJUI':
    case 'SUDAH DIISI':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/60 font-bold'
    case 'PENDING':
    case 'MENUNGGU':
    case 'MENUNGGU KONFIRMASI':
    case 'MENUNGGU KONFIRMASI GURU PENGAJAR':
    case 'MENUNGGU KONFIRMASI GURU PEMBIMBING':
    case 'MENUNGGU_PEMBIMBING':
      return 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/60 font-semibold'
    case 'BELUM_KONFIRMASI':
    case 'BELUM DIISI':
    case 'REJECTED':
    case 'DITOLAK':
      return 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/60 font-semibold'
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
  }
})

const cleanedLabel = computed(() => {
  if (props.customLabel) return props.customLabel
  const s = props.status || ''
  const upper = s.toUpperCase()

  if (upper === 'APPROVED' || upper === 'DISETUJUI') return 'Disetujui'
  if (upper === 'REJECTED' || upper === 'DITOLAK') return 'Ditolak'
  if (upper === 'TERVERIFIKASI') return 'Terverifikasi'
  if (upper === 'BELUM_PRESENSI' || upper === 'BELUM_ABSEN') return 'Belum Presensi'
  if (upper === 'BELUM_KONFIRMASI') return 'Belum Dikonfirmasi'
  if (upper === 'SUDAH DIISI') return 'Sudah Diisi'
  if (upper === 'BELUM DIISI') return 'Belum Diisi'
  if (upper === 'ALPHA' || upper === 'ALFA') return 'Alfa'
  if (upper === 'SAKIT') return 'Sakit'
  if (upper === 'IZIN') return 'Izin'
  if (upper === 'IZIN_KELUAR' || upper === 'IZIN_KBM') return 'Izin Keluar'
  if (upper === 'DISPENSASI') return 'Dispensasi'
  if (upper === 'PENDING' || upper.includes('MENUNGGU')) return 'Menunggu Konfirmasi'

  return s
})
</script>
