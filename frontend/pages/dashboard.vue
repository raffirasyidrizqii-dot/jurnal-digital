<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      :title="dashboardTitle"
      :subtitle="dashboardSubtitle"
    />

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Loading State -->
      <div v-if="isLoading && !data" class="py-16 text-center text-gray-400 text-xs flex flex-col items-center justify-center gap-2">
        <div class="w-8 h-8 border-3 border-primary/30 border-t-primary rounded-full animate-spin"></div>
        <span>Memuat data dashboard...</span>
      </div>

      <!-- ============================================== -->
      <!-- ROLE: SISWA (Status Kehadiran & Jadwal KBM)   -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'SISWA'">
        <!-- Info Banner Dual Role Siswa (Sekretaris bertugas) -->
        <div v-if="authStore.user?.is_secretary" class="p-3.5 sm:p-4 rounded-2xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-900/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-primary/10 dark:bg-blue-900/40 text-primary dark:text-blue-400 flex items-center justify-center shrink-0">
              <ClipboardCheck class="w-4 h-4" />
            </div>
            <div>
              <p class="text-xs font-bold text-gray-900 dark:text-white">Anda bertugas sebagai Sekretaris {{ authStore.user?.school_class?.name || 'Kelas' }}</p>
              <p class="text-[11px] text-gray-500 dark:text-slate-400">Saat ini Anda dalam Mode Siswa. Beralih ke Mode Sekretaris untuk mengelola presensi teman sekelas atau mencatat agenda KBM.</p>
            </div>
          </div>
          <button
            type="button"
            @click="authStore.switchRole('SEKRETARIS')"
            class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-blue-100 dark:hover:bg-slate-700 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800/60 text-xs font-bold transition shadow-2xs cursor-pointer shrink-0 active:scale-95 flex items-center gap-1.5"
          >
            <span>Beralih ke Mode Sekretaris</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Kartu Status Kehadiran Siswa Hari Ini -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-blue-200 dark:border-blue-900/60 p-4 sm:p-6 shadow-2xs flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
          <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800/60">
                Status Kehadiran Siswa
              </span>
              <span class="text-xs text-gray-500 dark:text-slate-400 font-medium">
                {{ data?.today_attendance?.date_formatted || 'Hari Ini' }} • {{ authStore.user?.school_class?.name || '-' }}
              </span>
            </div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex flex-wrap items-center gap-1.5">
              <span>Status Kehadiran Anda Hari Ini:</span>
              <span
                :class="[
                  'px-2.5 py-0.5 rounded-lg text-xs font-bold inline-block',
                  data?.today_attendance?.is_leave_rejected ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60' :
                  data?.today_attendance?.is_leave_approved ? 'bg-orange-50 dark:bg-amber-950/40 text-orange-700 dark:text-amber-400 border border-orange-200 dark:border-amber-800/60' :
                  data?.today_attendance?.checked_in ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60' :
                  data?.today_attendance?.has_pending_leave ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60' :
                  data?.today_attendance?.status === 'ALPHA' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60' :
                  'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700'
                ]"
              >
                {{
                  data?.today_attendance?.is_leave_rejected ? 'Surat Izin Ditolak' :
                  data?.today_attendance?.is_leave_approved ? (data?.today_attendance?.status ? 'Izin Disetujui: ' + data?.today_attendance?.status : 'Izin Disetujui') :
                  data?.today_attendance?.checked_in ? (data?.today_attendance?.status || 'HADIR') :
                  data?.today_attendance?.has_pending_leave ? 'Menunggu Persetujuan Izin' :
                  data?.today_attendance?.status === 'ALPHA' ? 'ALFA' : 'Menunggu Jam KBM'
                }}
              </span>
            </h3>
            <p class="text-xs text-gray-500 dark:text-slate-400">
              {{ data?.today_attendance?.note || (data?.today_attendance?.checked_in ? 'Kehadiran Anda hari ini telah dicatat oleh guru di kelas.' : 'Status kehadiran dicatat oleh guru pada saat jam pelajaran berlangsung.') }}
            </p>
          </div>

          <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 w-full lg:w-auto">
            <!-- Jika Surat Izin Ditolak Wali Kelas -->
            <div
              v-if="data?.today_attendance?.is_leave_rejected"
              class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 w-full sm:w-auto"
            >
              <div class="px-4 py-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-bold shadow-2xs flex items-center justify-center sm:justify-start gap-2">
                <XCircle class="w-4 h-4 text-rose-600 dark:text-rose-400 flex-shrink-0" />
                <span>Izin Ditolak</span>
              </div>
              <NuxtLink
                to="/riwayat-kehadiran?tab=izin"
                class="px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-800/60 hover:border-rose-300 dark:hover:border-rose-700 text-rose-700 dark:text-rose-400 text-xs font-semibold transition flex items-center justify-center gap-1.5 min-h-[44px]"
              >
                <span>Lihat Alasan Penolakan</span>
              </NuxtLink>
            </div>

            <!-- Jika Surat Izin Disetujui (SAKIT / IZIN) -->
            <div
              v-else-if="data?.today_attendance?.is_leave_approved || (data?.today_attendance?.checked_in && (data?.today_attendance?.status === 'SAKIT' || data?.today_attendance?.status === 'IZIN' || data?.today_attendance?.status === 'DISPENSASI'))"
              class="px-4 py-2.5 rounded-xl bg-orange-50 dark:bg-amber-950/40 border border-orange-200 dark:border-amber-800/60 text-orange-800 dark:text-amber-300 text-xs font-bold shadow-2xs flex items-center justify-center sm:justify-start gap-2 w-full sm:w-auto min-h-[44px]"
            >
              <CheckCircle2 class="w-4 h-4 text-orange-600 dark:text-amber-400" />
              <span>Izin Disetujui: {{ data?.today_attendance?.status }}</span>
            </div>

            <!-- Jika Sudah Tercatat HADIR -->
            <div
              v-else-if="data?.today_attendance?.checked_in"
              class="px-4 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-bold shadow-2xs flex items-center justify-center sm:justify-start gap-2 w-full sm:w-auto min-h-[44px]"
            >
              <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
              <span>Tercatat Hadir</span>
            </div>

            <div
              v-else-if="data?.today_attendance?.has_pending_leave"
              class="px-4 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs font-bold shadow-2xs flex items-center justify-center sm:justify-start gap-2 w-full sm:w-auto min-h-[44px]"
            >
              <Clock class="w-4 h-4 text-amber-600 dark:text-amber-400" />
              <span>Izin Menunggu Persetujuan</span>
            </div>

            <!-- Jika Belum Absen -->
            <button
              v-else
              type="button"
              @click="handleSelfCheckIn"
              :disabled="isCheckingIn"
              class="px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-xs transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 min-h-[44px]"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>{{ isCheckingIn ? 'Mencatat...' : 'Konfirmasi Hadir Mandiri' }}</span>
            </button>
          </div>
        </div>

        <!-- 4 Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
          <StatCard title="Hadir" :value="data?.stats?.hadir_hari ?? '0 Hari'" :subtext="data?.stats?.hadir_percent ?? 'Semester Ini'" color="green" />
          <StatCard title="Sakit" :value="data?.stats?.sakit_hari ?? '0 Hari'" subtext="Surat keterangan" color="orange" />
          <StatCard title="Izin" :value="data?.stats?.izin_hari ?? '0 Hari'" subtext="Izin disetujui" color="blue" />
          <StatCard title="Alfa" :value="data?.stats?.alpha_hari ?? '0 Hari'" subtext="Tanpa keterangan" color="red" />
        </div>

        <!-- Card Khusus: Peringatan & Izin Keluar Jam Mapel (Siswa) -->
        <div v-if="data?.today_kbm_leaves?.length > 0 || data?.today_kbm_alfa?.length > 0" class="space-y-3">
          <!-- Card Peringatan Alfa Mapel (Merah) -->
          <div
            v-if="data?.today_kbm_alfa?.length > 0"
            class="p-4 sm:p-5 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800/60 shadow-2xs space-y-3"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-700 dark:text-rose-400 flex items-center justify-center shrink-0">
                  <AlertCircle class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-rose-950 dark:text-rose-200">
                    Peringatan: Tercatat Alfa di {{ data.today_kbm_alfa.length }} Jam Pelajaran
                  </h4>
                  <p class="text-[11px] text-rose-800 dark:text-rose-400">
                    Guru mata pelajaran menandai Anda tidak berada di kelas saat sesi presensi:
                  </p>
                </div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-200/80 dark:bg-rose-900/60 text-rose-900 dark:text-rose-200">
                Alfa KBM
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
              <div
                v-for="(af, aidx) in data.today_kbm_alfa"
                :key="aidx"
                class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-rose-200/80 dark:border-rose-900/40 space-y-1"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-gray-900 dark:text-white">{{ af.subject }}</span>
                  <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300">
                    ALFA
                  </span>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-slate-400">
                  {{ af.jam_ke }} • {{ af.time }} • Guru: {{ af.teacher }}
                </p>
              </div>
            </div>
          </div>

          <!-- Card Izin Keluar Mapel (Kuning / Amber) -->
          <div
            v-if="data?.today_kbm_leaves?.length > 0"
            class="p-4 sm:p-5 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-800/60 shadow-2xs space-y-3"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                  <Clock class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-amber-950 dark:text-amber-200">
                    Catatan Izin Keluar {{ data.today_kbm_leaves.length }} Jam Pelajaran
                  </h4>
                  <p class="text-[11px] text-amber-800 dark:text-amber-400">
                    Anda tercatat izin meninggalkan ruang kelas pada jam pelajaran berikut:
                  </p>
                </div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-200/80 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200">
                Izin Keluar
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
              <div
                v-for="(lv, lidx) in data.today_kbm_leaves"
                :key="lidx"
                class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-amber-200/80 dark:border-amber-900/40 space-y-1"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-gray-900 dark:text-white">{{ lv.subject }}</span>
                  <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300">
                    Keluar Pukul {{ lv.leave_time }}
                  </span>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-slate-400">
                  {{ lv.jam_ke }} • Guru: {{ lv.teacher }}
                </p>
                <p class="text-xs font-medium text-amber-900 dark:text-amber-300">
                  Keterangan: {{ lv.reason }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Widget Timeline Materi Belajar Hari Ini -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800 gap-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 flex items-center justify-center shrink-0">
                <Clock class="w-4 h-4" />
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white">Timeline Jadwal & Materi Belajar</h3>
                <p class="text-[11px] text-gray-500 dark:text-slate-400">Urutan sesi jam pelajaran, topik materi, dan status KBM</p>
              </div>
            </div>

            <!-- Date Selector Control -->
            <div class="flex items-center gap-1.5 self-start sm:self-auto flex-wrap">
              <button
                type="button"
                @click="shiftDate(-1)"
                class="px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer active:scale-95 shadow-2xs"
                title="Hari Sebelumnya"
              >
                ‹ Kemarin
              </button>
              <button
                type="button"
                @click="resetToToday"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer active:scale-95 shadow-2xs border"
                :class="isDateToday ? 'bg-primary text-white border-primary shadow-xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200'"
              >
                Hari Ini
              </button>
              <button
                type="button"
                @click="shiftDate(1)"
                class="px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer active:scale-95 shadow-2xs"
                title="Hari Berikutnya"
              >
                Besok ›
              </button>
              <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-2xs hover:border-primary/40 focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary transition">
                <Calendar class="w-3.5 h-3.5 text-primary dark:text-blue-400 shrink-0" />
                <input
                  type="date"
                  lang="id-ID"
                  v-model="timelineSelectedDate"
                  @change="onTimelineDateChange"
                  class="border-none bg-transparent text-xs font-semibold text-gray-800 dark:text-slate-200 outline-none cursor-pointer"
                />
              </div>
            </div>
          </div>

          <!-- Weekend Alert State -->
          <div v-if="data?.is_weekend" class="text-center py-10 space-y-3 bg-gray-50/70 dark:bg-slate-850/50 rounded-2xl border border-dashed border-gray-200 dark:border-slate-800">
            <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto shadow-2xs">
              <Calendar class="w-5 h-5" />
            </div>
            <div>
              <p class="text-sm font-bold text-gray-800 dark:text-slate-100">Hari Libur Akhir Pekan</p>
              <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Tidak ada kegiatan belajar-mengajar pada hari libur akhir pekan.</p>
            </div>
            <button
              type="button"
              @click="resetToToday"
              class="px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-xl transition cursor-pointer shadow-xs active:scale-95"
            >
              Kembali ke Hari Ini
            </button>
          </div>

          <!-- Timeline Items -->
          <div v-if="data?.today_timeline && data.today_timeline.length > 0" class="relative pl-6 sm:pl-8 space-y-4 before:absolute before:left-2.5 sm:before:left-3.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-gray-200 dark:before:bg-slate-800">
            <div
              v-for="(item, idx) in data.today_timeline"
              :key="idx"
              class="relative"
            >
              <!-- Timeline Node Dot -->
              <div
                class="absolute -left-6 sm:-left-8 top-2 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 ring-2 transition"
                :class="item.status_color === 'emerald' ? 'bg-emerald-500 ring-emerald-200 dark:ring-emerald-900/60' : item.status_color === 'rose' ? 'bg-rose-500 ring-rose-200 dark:ring-rose-900/60' : item.status_color === 'amber' ? 'bg-amber-500 ring-amber-200 dark:ring-amber-900/60' : item.status_color === 'blue' ? 'bg-primary ring-blue-200 dark:ring-blue-900/60' : 'bg-gray-300 dark:bg-slate-700 ring-gray-150 dark:ring-slate-800'"
              ></div>

              <div class="p-3.5 sm:p-4 rounded-xl border border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800/80 hover:shadow-xs transition space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-primary/10 dark:bg-blue-900/30 text-primary dark:text-blue-400">
                      {{ item.jam_ke }}
                    </span>
                    <span class="text-xs font-mono font-semibold text-gray-700 dark:text-slate-300">
                      {{ item.time }}
                    </span>
                    <span class="text-[11px] text-gray-400 dark:text-slate-500">
                      • {{ item.room }}
                    </span>
                  </div>

                  <div class="flex flex-wrap items-center gap-1.5">
                    <!-- Status Kehadiran Siswa di Mapel Ini -->
                    <span
                      v-if="item.student_status === 'IZIN'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs"
                    >
                      <Clock class="w-3 h-3 text-amber-600 dark:text-amber-400" />
                      <span>Izin Keluar: {{ item.student_leave_time || 'Saat KBM' }}</span>
                    </span>
                    <span
                      v-else-if="item.student_status === 'ALPHA'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/50 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700 shadow-2xs"
                    >
                      <AlertCircle class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                      <span>ALFA di Mapel Ini</span>
                    </span>
                    <span
                      v-else-if="item.student_status === 'SAKIT'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs"
                    >
                      <span>Sakit</span>
                    </span>
                    <span
                      v-else-if="item.student_status === 'HADIR'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 shadow-2xs"
                    >
                      <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                      <span>Hadir di Kelas</span>
                    </span>

                    <!-- Status Terlaksana Badge -->
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                      :class="item.status_color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50' : item.status_color === 'amber' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50' : item.status_color === 'rose' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50' : item.status_color === 'blue' ? 'bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700'"
                    >
                      <CheckCircle2 v-if="item.status === 'TERLAKSANA'" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                      <AlertCircle v-else-if="item.status === 'JAM_KOSONG'" class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                      <Clock v-else class="w-3 h-3 text-gray-400 dark:text-slate-500" />
                      <span>{{ item.status_label }}</span>
                    </span>
                  </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-1">
                  <div>
                    <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">{{ item.subject }}</h4>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5 flex flex-wrap items-center gap-1.5">
                      <span>Guru: <strong class="text-gray-700 dark:text-slate-200 font-medium">{{ item.teacher_name }}</strong></span>
                      <span v-if="item.teacher_presence" class="text-[10px] px-2 py-0.5 rounded-md font-semibold" :class="item.status === 'JAM_KOSONG' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300'">
                        {{ item.teacher_presence }}
                      </span>
                    </p>
                  </div>
                </div>

                <!-- Materi KBM -->
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900/80 border border-gray-150 dark:border-slate-700/60 text-xs">
                  <span class="font-semibold text-gray-500 dark:text-slate-400 text-[10px] uppercase block tracking-wider mb-0.5">Materi KBM:</span>
                  <p class="text-gray-800 dark:text-slate-200 font-medium leading-relaxed">
                    {{ item.topic }}
                  </p>
                  <div v-if="item.notes" class="mt-2.5 pt-2.5 border-t border-gray-100 dark:border-slate-800">
                    <span v-if="item.is_assignment" class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider block mb-0.5">Tugas / Instruksi:</span>
                    <span v-else class="text-[10px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider block mb-0.5">Catatan:</span>
                    <p class="text-[11px] text-gray-600 dark:text-slate-400 italic">{{ item.notes }}</p>
                  </div>

                  <!-- Direct Download Modul Pembelajaran jika ada file -->
                  <div v-if="item.attachment_url" class="pt-2 mt-1.5 border-t border-gray-100 dark:border-slate-800">
                    <a
                      :href="item.attachment_url"
                      target="_blank"
                      :download="item.attachment_name || 'Modul_Belajar.pdf'"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95 max-w-full"
                    >
                      <Download class="w-3.5 h-3.5 shrink-0" />
                      <span class="truncate">Unduh {{ item.attachment_name || 'Berkas Materi' }}</span>
                    </a>
                  </div>
                </div>

                <!-- Catatan Izin Keluar Jam Mapel (Jika Siswa Izin) -->
                <div v-if="item.student_status === 'IZIN'" class="p-2.5 rounded-lg bg-amber-50/80 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-center gap-2">
                  <Clock class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                  <div>
                    <span class="font-bold">Izin Keluar Pukul {{ item.student_leave_time || '-' }} WIB:</span>
                    <span class="ml-1">{{ item.student_leave_note || 'Izin meninggalkan ruang kelas saat KBM berlangsung.' }}</span>
                  </div>
                </div>

                <!-- Alert Siswa Tidak Berada di Jam Ini jika ada -->
                <div v-if="item.absent_students && item.absent_students.length > 0" class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-800 dark:text-rose-200 text-[11px] space-y-0.5">
                  <span class="font-bold flex items-center gap-1">
                    <AlertCircle class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                    Siswa tidak berada di kelas pada jam ini ({{ item.absent_students.length }} siswa):
                  </span>
                  <p class="text-rose-700 dark:text-rose-300">
                    {{ item.absent_students.map((s: any) => s.name).join(', ') }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State (Jika bukan weekend dan timeline kosong) -->
          <div v-else-if="!data?.is_weekend" class="text-center py-8 rounded-xl border border-dashed border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-850/50 space-y-1.5">
            <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 mx-auto flex items-center justify-center">
              <Calendar class="w-4 h-4" />
            </div>
            <p class="text-xs font-bold text-gray-700 dark:text-slate-200">Tidak Ada Jadwal KBM pada Tanggal Ini</p>
            <p class="text-[11px] text-gray-400 dark:text-slate-500">Jadwal pembelajaran untuk hari {{ data?.selected_day_name || 'terpilih' }} kosong atau sedang jeda KBM.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Riwayat Kehadiran Terakhir -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Riwayat Kehadiran Terakhir</h3>
              <NuxtLink to="/riwayat-kehadiran" class="text-xs text-primary dark:text-blue-400 font-semibold hover:underline">
                Lihat Semua
              </NuxtLink>
            </div>
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="rec in data?.recent_attendance || []"
                :key="rec.id"
                class="p-3 bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 rounded-xl space-y-1.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900 dark:text-white">{{ rec.date }}</span>
                  <StatusBadge :status="rec.status" />
                </div>
                <div class="text-[11px] text-gray-500 dark:text-slate-400 flex items-center justify-between">
                  <span>{{ rec.note || '-' }}</span>
                  <span class="text-gray-400 dark:text-slate-500">{{ rec.school_hours }}</span>
                </div>
              </div>
              <div v-if="!data?.recent_attendance?.length" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
                Belum ada riwayat kehadiran.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
              <table class="w-full text-left text-xs min-w-[550px]">
                <thead>
                  <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/60">
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Tanggal</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Keterangan</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Jam Sekolah</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                  <tr v-for="rec in data?.recent_attendance || []" :key="rec.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-3.5 px-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ rec.date }}</td>
                    <td class="py-3.5 px-4 whitespace-nowrap"><StatusBadge :status="rec.status" /></td>
                    <td class="py-3.5 px-4 text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ rec.note || '-' }}</td>
                    <td class="py-3.5 px-4 text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ rec.school_hours || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Jurnal Kegiatan Siswa & Materi Belajar Guru -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-3">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="studentJournalTab = 'journals'"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer',
                    studentJournalTab === 'journals'
                      ? 'bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800/50'
                      : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200'
                  ]"
                >
                  Laporan Jurnal Belajar
                </button>
                <button
                  type="button"
                  @click="studentJournalTab = 'materials'"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer',
                    studentJournalTab === 'materials'
                      ? 'bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800/50'
                      : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200'
                  ]"
                >
                  Materi dari Guru
                </button>
              </div>
              <NuxtLink to="/jurnal-pelajaran" class="text-xs text-primary dark:text-blue-400 font-semibold hover:underline">
                Lihat Selengkapnya →
              </NuxtLink>
            </div>

            <!-- Tab 1: Laporan Jurnal Belajar Siswa -->
            <div v-if="studentJournalTab === 'journals'">
              <!-- Mobile Card View -->
              <div class="block md:hidden space-y-2.5">
                <div
                  v-for="j in data?.recent_journals || []"
                  :key="j.id"
                  class="p-3 bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 rounded-xl space-y-1.5"
                >
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-primary dark:text-blue-400">{{ j.subject }}</span>
                    <span class="text-[11px] text-gray-400 dark:text-slate-500">{{ j.date }}</span>
                  </div>
                  <p class="text-xs text-gray-800 dark:text-slate-200">{{ j.activity }}</p>
                  <div class="pt-1 flex items-center justify-end">
                    <StatusBadge :status="j.status" />
                  </div>
                </div>
                <div v-if="!data?.recent_journals?.length" class="py-6 text-center text-gray-400 dark:text-slate-500 text-xs">
                  Belum ada jurnal kegiatan yang dilaporkan.
                </div>
              </div>

              <!-- Desktop Table View -->
              <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
                <table class="w-full text-left text-xs min-w-[650px]">
                  <thead>
                    <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/60 font-bold">
                      <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Tanggal</th>
                      <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Mata Pelajaran</th>
                      <th class="py-3.5 px-4 font-semibold">Kegiatan Belajar</th>
                      <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    <tr v-for="j in data?.recent_journals || []" :key="j.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                      <td class="py-3.5 px-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ j.date }}</td>
                      <td class="py-3.5 px-4 text-gray-800 dark:text-slate-200 font-medium whitespace-nowrap">{{ j.subject }}</td>
                      <td class="py-3.5 px-4 text-gray-600 dark:text-slate-400">{{ j.activity }}</td>
                      <td class="py-3.5 px-4 text-center whitespace-nowrap">
                        <StatusBadge :status="j.status" />
                      </td>
                    </tr>
                    <tr v-if="!data?.recent_journals?.length">
                      <td colspan="4" class="py-6 px-4 text-center text-gray-400 dark:text-slate-500">Belum ada jurnal kegiatan yang dilaporkan.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab 2: Materi dari Guru -->
            <div v-else>
              <!-- Mobile Card View -->
              <div class="block md:hidden space-y-2.5">
                <div
                  v-for="mat in data?.recent_materials || []"
                  :key="mat.id"
                  class="p-3 bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 rounded-xl space-y-1.5"
                >
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-gray-900 dark:text-white">{{ mat.subject }}</span>
                    <span class="text-[11px] text-gray-400 dark:text-slate-500">{{ mat.date }}</span>
                  </div>
                  <p class="text-xs text-gray-700 dark:text-slate-300 font-medium">{{ mat.topic }}</p>
                  <div class="pt-1 flex items-center justify-between text-[11px]">
                    <span class="text-gray-400 dark:text-slate-500">Guru:</span>
                    <span class="text-[11px] font-medium text-gray-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 px-2 py-0.5 rounded-md border border-gray-150 dark:border-slate-700">{{ mat.teacher || 'Guru' }}</span>
                  </div>
                </div>
                <div v-if="!data?.recent_materials?.length" class="py-6 text-center text-gray-400 dark:text-slate-500 text-xs">
                  Belum ada materi pelajaran yang diunggah guru.
                </div>
              </div>

              <!-- Desktop Table View -->
              <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
                <table class="w-full text-left text-xs min-w-[650px]">
                  <thead>
                    <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/60 font-bold">
                      <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Tanggal</th>
                      <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Mata Pelajaran</th>
                      <th class="py-3.5 px-4 font-semibold">Judul Materi</th>
                      <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Guru</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    <tr v-for="mat in data?.recent_materials || []" :key="mat.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                      <td class="py-3.5 px-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ mat.date }}</td>
                      <td class="py-3.5 px-4 text-gray-800 dark:text-slate-200 font-medium whitespace-nowrap">{{ mat.subject }}</td>
                      <td class="py-3.5 px-4 text-gray-600 dark:text-slate-400">{{ mat.topic }}</td>
                      <td class="py-3.5 px-4 text-right whitespace-nowrap">
                        <span class="text-[11px] font-medium text-gray-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 px-2 py-0.5 rounded-md border border-gray-150 dark:border-slate-700">{{ mat.teacher || 'Guru' }}</span>
                      </td>
                    </tr>
                    <tr v-if="!data?.recent_materials?.length">
                      <td colspan="4" class="py-6 px-4 text-center text-gray-400 dark:text-slate-500">Belum ada materi pelajaran yang diunggah guru.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: ORANG TUA (Pantauan Kehadiran & Jurnal)  -->
      <!-- ============================================== -->
      <!-- ROLE: ORANG TUA (Pantauan Kehadiran & Jurnal Anak) -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'ORANG_TUA'">
        <!-- Multi-Anak Switcher jika orang tua memiliki lebih dari 1 anak -->
        <div v-if="data?.children && data?.children.length > 1" class="flex flex-wrap items-center gap-2.5 p-3.5 bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/40 rounded-2xl shadow-2xs">
          <span class="text-xs font-bold text-primary dark:text-blue-400 flex items-center gap-1.5">
            <Users class="w-4 h-4 text-primary dark:text-blue-400" />
            <span>Pilih Anak yang Dipantau:</span>
          </span>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="c in data.children"
              :key="c.id"
              type="button"
              @click="switchChild(c.id)"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="data?.child?.id === c.id ? 'bg-primary text-white shadow-2xs' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700'"
            >
              {{ c.name }} ({{ c.class_name }})
            </button>
          </div>
        </div>

        <!-- Kartu Identitas Anak & Status Kehadiran Real-Time -->
        <div
          :class="[
            'bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-6 shadow-2xs space-y-4 border transition',
            data?.child?.is_leave_rejected
              ? 'border-rose-200 dark:border-rose-900/60'
              : data?.child?.is_leave_approved
              ? 'border-orange-200 dark:border-orange-900/60'
              : data?.child?.has_pending_leave
              ? 'border-amber-200 dark:border-amber-900/60'
              : data?.child?.today_status === 'HADIR'
              ? 'border-emerald-200 dark:border-emerald-900/60'
              : 'border-gray-200 dark:border-slate-800'
          ]"
        >
          <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="space-y-1.5">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                  Portal Pantauan Orang Tua / Wali Murid
                </span>
                <span class="text-xs text-gray-500 dark:text-slate-400 font-medium">
                  {{ data?.child?.date_formatted || 'Hari Ini' }}
                </span>
              </div>
              <div class="space-y-1">
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">{{ data?.child?.name || 'Siswa' }}</h3>
                <div class="flex flex-wrap items-center gap-2 pt-0.5">
                  <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">NISN: {{ data?.child?.nis || '-' }}</span>
                  <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Kelas {{ data?.child?.class_name || '-' }}</span>
                  <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Wali Kelas: {{ data?.child?.wali_kelas || '-' }}</span>
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-2 pt-1">
                <span class="text-xs text-slate-600 dark:text-slate-300">Status Kehadiran Hari Ini:</span>
                <StatusBadge :status="data?.child?.today_status || 'BELUM_PRESENSI'" />
                <span class="text-xs text-slate-500 dark:text-slate-400">— {{ data?.child?.today_note || 'Belum melakukan presensi' }}</span>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <NuxtLink
                :to="`/jurnal-pelajaran?student_id=${data?.child?.id || ''}`"
                class="px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition flex items-center gap-1.5"
              >
                <FileText class="w-4 h-4" />
                <span>Lihat Jurnal Belajar Anak</span>
              </NuxtLink>
              <NuxtLink
                :to="`/riwayat-kehadiran?student_id=${data?.child?.id || ''}`"
                class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:border-primary text-gray-700 dark:text-slate-200 text-xs font-semibold transition flex items-center gap-1.5"
              >
                <ClipboardCheck class="w-4 h-4 text-primary dark:text-blue-400" />
                <span>Detail Riwayat Kehadiran</span>
              </NuxtLink>
            </div>
          </div>

          <!-- Alert Khusus Orang Tua Jika Surat Izin Anak DITOLAK oleh Wali Kelas -->
          <div
            v-if="data?.child?.is_leave_rejected"
            class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 rounded-xl flex items-start gap-3"
          >
            <XCircle class="w-5 h-5 text-rose-600 dark:text-rose-400 flex-shrink-0 mt-0.5" />
            <div class="text-xs space-y-1">
              <p class="font-bold text-rose-900 dark:text-rose-200">
                Pemberitahuan: Permohonan Izin Siswa Ditolak oleh Wali Kelas
              </p>
              <p class="text-rose-700 dark:text-rose-300 leading-relaxed">
                Alasan Penolakan Wali Kelas: <em>"{{ data?.child?.rejection_reason || 'Tidak memenuhi ketentuan izin sekolah.' }}"</em>
              </p>
              <p class="text-rose-800 dark:text-rose-300 text-[11px] font-medium pt-0.5">
                Putra/putri Anda wajib tetap hadir ke sekolah dan melapor langsung ke Sekretaris Kelas untuk verifikasi kehadiran fisik.
              </p>
            </div>
          </div>

          <!-- Alert Khusus Orang Tua Jika Surat Izin Menunggu Persetujuan -->
          <div
            v-else-if="data?.child?.has_pending_leave"
            class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 rounded-xl flex items-center gap-2.5 text-xs text-amber-900 dark:text-amber-200"
          >
            <Clock class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" />
            <span>Permohonan surat izin putra/putri Anda hari ini sedang dalam proses review oleh Wali Kelas.</span>
          </div>

          <!-- Alert Khusus Jika Akun Orang Tua Belum Ditautkan ke Siswa -->
          <div
            v-else-if="!data?.child?.id"
            class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 rounded-xl flex items-start gap-2.5 text-xs text-amber-900 dark:text-amber-200"
          >
            <Clock class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
            <div>
              <p class="font-bold text-amber-900 dark:text-amber-200">Akun Belum Ditautkan ke Siswa</p>
              <p class="text-amber-700 dark:text-amber-300 mt-0.5 leading-relaxed">
                Akun Orang Tua ini belum terhubung ke data siswa oleh Administrator Sekolah. Silakan hubungi Wali Kelas atau pihak Tata Usaha untuk menautkan akun Anda dengan nomor induk siswa.
              </p>
            </div>
          </div>
        </div>

        <!-- 4 Stat Cards Kehadiran Anak -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
          <StatCard title="Hadir Bulan Ini" :value="data?.stats?.hadir_hari ?? '0 Hari'" :subtext="data?.stats?.hadir_percent ?? 'Bulan Ini'" color="blue" />
          <StatCard title="Sakit" :value="data?.stats?.sakit_hari ?? '0 Hari'" subtext="Disertai Surat" color="blue" />
          <StatCard title="Izin Disetujui" :value="data?.stats?.izin_hari ?? '0 Hari'" subtext="Disetujui Wali Kelas" color="blue" />
          <StatCard title="Alpha" :value="data?.stats?.alpha_hari ?? '0 Hari'" subtext="Tanpa Keterangan" color="blue" />
        </div>

        <!-- Card Khusus: Peringatan & Izin Keluar Mapel Hari Ini (Orang Tua) -->
        <div v-if="data?.today_kbm_leaves?.length > 0 || data?.today_kbm_alfa?.length > 0" class="space-y-3">
          <!-- Card Peringatan Alfa Mapel (Merah) -->
          <div
            v-if="data?.today_kbm_alfa?.length > 0"
            class="p-4 sm:p-5 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800/60 shadow-2xs space-y-3"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-700 dark:text-rose-400 flex items-center justify-center shrink-0">
                  <AlertCircle class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-rose-950 dark:text-rose-200">
                    Peringatan: Putra/Putri Anda Tercatat ALFA di Jam Pelajaran ({{ data.today_kbm_alfa.length }} Mapel)
                  </h4>
                  <p class="text-[11px] text-rose-800 dark:text-rose-400">
                    Guru mata pelajaran menandai putra/putri Anda tidak berada di kelas saat sesi presensi:
                  </p>
                </div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-200/80 dark:bg-rose-900/60 text-rose-900 dark:text-rose-200">
                Alfa KBM
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
              <div
                v-for="(af, aidx) in data.today_kbm_alfa"
                :key="aidx"
                class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-rose-200/80 dark:border-rose-900/40 space-y-1"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-gray-900 dark:text-white">{{ af.subject }}</span>
                  <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300">
                    ALFA
                  </span>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-slate-400">
                  {{ af.jam_ke }} • {{ af.time }} • Guru: {{ af.teacher }}
                </p>
              </div>
            </div>
          </div>

          <!-- Card Izin Keluar Mapel (Kuning / Amber) -->
          <div
            v-if="data?.today_kbm_leaves?.length > 0"
            class="p-4 sm:p-5 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-800/60 shadow-2xs space-y-3"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                  <Clock class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-amber-950 dark:text-amber-200">
                    Catatan Izin Keluar Jam Pelajaran Anak ({{ data.today_kbm_leaves.length }} Mapel)
                  </h4>
                  <p class="text-[11px] text-amber-800 dark:text-amber-400">
                    Putra/putri Anda tercatat izin keluar kelas pada jam pelajaran berikut:
                  </p>
                </div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-200/80 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200">
                Izin Keluar
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
              <div
                v-for="(lv, lidx) in data.today_kbm_leaves"
                :key="lidx"
                class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-amber-200/80 dark:border-amber-900/40 space-y-1"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-gray-900 dark:text-white">{{ lv.subject }}</span>
                  <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300">
                    Keluar Pukul {{ lv.leave_time }}
                  </span>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-slate-400">
                  {{ lv.jam_ke }} • Guru: {{ lv.teacher }}
                </p>
                <p class="text-xs font-medium text-amber-900 dark:text-amber-300">
                  Keterangan: {{ lv.reason }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Widget Timeline Materi Belajar Anak Hari Ini (Untuk Orang Tua) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800 gap-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 flex items-center justify-center shrink-0">
                <Clock class="w-4 h-4" />
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white">Timeline Materi Belajar Anak</h3>
                <p class="text-[11px] text-gray-500 dark:text-slate-400">Urutan jam pelajaran, materi yang dipelajari anak, dan status KBM</p>
              </div>
            </div>

            <!-- Date Selector Control -->
            <div class="flex items-center gap-1.5 self-start sm:self-auto flex-wrap">
              <button
                type="button"
                @click="shiftDate(-1)"
                class="px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer active:scale-95 shadow-2xs"
                title="Hari Sebelumnya"
              >
                ‹ Kemarin
              </button>
              <button
                type="button"
                @click="resetToToday"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer active:scale-95 shadow-2xs border"
                :class="isDateToday ? 'bg-primary text-white border-primary shadow-xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200'"
              >
                Hari Ini
              </button>
              <button
                type="button"
                @click="shiftDate(1)"
                class="px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer active:scale-95 shadow-2xs"
                title="Hari Berikutnya"
              >
                Besok ›
              </button>
              <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-2xs hover:border-primary/40 focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary transition">
                <Calendar class="w-3.5 h-3.5 text-primary dark:text-blue-400 shrink-0" />
                <input
                  type="date"
                  lang="id-ID"
                  v-model="timelineSelectedDate"
                  @change="onTimelineDateChange"
                  class="border-none bg-transparent text-xs font-semibold text-gray-800 dark:text-slate-200 outline-none cursor-pointer"
                />
              </div>
            </div>
          </div>

          <!-- Weekend Alert State -->
          <div v-if="data?.is_weekend" class="text-center py-10 space-y-3 bg-gray-50/70 dark:bg-slate-850/50 rounded-2xl border border-dashed border-gray-200 dark:border-slate-800">
            <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto shadow-2xs">
              <Calendar class="w-5 h-5" />
            </div>
            <div>
              <p class="text-sm font-bold text-gray-800 dark:text-slate-100">Hari Libur Akhir Pekan</p>
              <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Tidak ada kegiatan belajar-mengajar pada hari {{ data?.selected_date_formatted || 'akhir pekan' }}.</p>
            </div>
            <button
              type="button"
              @click="resetToToday"
              class="px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-xl transition cursor-pointer shadow-xs active:scale-95"
            >
              Kembali ke Hari Ini
            </button>
          </div>

          <!-- Timeline Items -->
          <div v-if="data?.today_timeline && data.today_timeline.length > 0" class="relative pl-6 sm:pl-8 space-y-4 before:absolute before:left-2.5 sm:before:left-3.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-gray-200 dark:before:bg-slate-800">
            <div
              v-for="(item, idx) in data.today_timeline"
              :key="idx"
              class="relative"
            >
              <!-- Timeline Node Dot -->
              <div
                class="absolute -left-6 sm:-left-8 top-2 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 ring-2 transition"
                :class="item.status === 'TERLAKSANA' ? 'bg-emerald-500 ring-emerald-200 dark:ring-emerald-900/60' : item.status === 'MENUNGGU_VERIFIKASI' ? 'bg-primary ring-blue-200 dark:ring-blue-900/60' : 'bg-gray-300 dark:bg-slate-700 ring-gray-150 dark:ring-slate-800'"
              ></div>

              <div class="p-3.5 sm:p-4 rounded-xl border border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800/80 hover:shadow-xs transition space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-primary/10 dark:bg-blue-900/30 text-primary dark:text-blue-400">
                      {{ item.jam_ke }}
                    </span>
                    <span class="text-xs font-mono font-semibold text-gray-700 dark:text-slate-300">
                      {{ item.time }}
                    </span>
                    <span class="text-[11px] text-gray-400 dark:text-slate-500">
                      • {{ item.room }}
                    </span>
                  </div>

                  <div class="flex flex-wrap items-center gap-1.5">
                    <!-- Status Kehadiran Siswa di Mapel Ini -->
                    <span
                      v-if="item.student_status === 'IZIN'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs"
                    >
                      <Clock class="w-3 h-3 text-amber-600 dark:text-amber-400" />
                      <span>Izin Keluar: {{ item.student_leave_time || 'Saat KBM' }}</span>
                    </span>
                    <span
                      v-else-if="item.student_status === 'ALPHA'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/50 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700 shadow-2xs"
                    >
                      <AlertCircle class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                      <span>ALFA di Mapel Ini</span>
                    </span>
                    <span
                      v-else-if="item.student_status === 'SAKIT'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs"
                    >
                      <span>Sakit</span>
                    </span>
                    <span
                      v-else-if="item.student_status === 'HADIR'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 shadow-2xs"
                    >
                      <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                      <span>Hadir di Kelas</span>
                    </span>

                    <!-- Status Terlaksana Badge -->
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                      :class="item.status_color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50' : item.status_color === 'amber' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50' : item.status_color === 'rose' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50' : item.status_color === 'blue' ? 'bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700'"
                    >
                      <CheckCircle2 v-if="item.status === 'TERLAKSANA'" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                      <AlertCircle v-else-if="item.status === 'JAM_KOSONG'" class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                      <Clock v-else class="w-3 h-3 text-gray-400 dark:text-slate-500" />
                      <span>{{ item.status_label }}</span>
                    </span>
                  </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-1">
                  <div>
                    <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">{{ item.subject }}</h4>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5 flex flex-wrap items-center gap-1.5">
                      <span>Guru: <strong class="text-gray-700 dark:text-slate-200 font-medium">{{ item.teacher_name }}</strong></span>
                      <span v-if="item.teacher_presence" class="text-[10px] px-2 py-0.5 rounded-md font-semibold" :class="item.status === 'JAM_KOSONG' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300'">
                        {{ item.teacher_presence }}
                      </span>
                    </p>
                  </div>
                </div>

                <!-- Materi KBM -->
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900/80 border border-gray-150 dark:border-slate-700/60 text-xs">
                  <span class="font-semibold text-gray-500 dark:text-slate-400 text-[10px] uppercase block tracking-wider mb-0.5">Materi KBM:</span>
                  <p class="text-gray-800 dark:text-slate-200 font-medium leading-relaxed">
                    {{ item.topic }}
                  </p>
                  <div v-if="item.notes" class="mt-2.5 pt-2.5 border-t border-gray-100 dark:border-slate-800">
                    <span v-if="item.is_assignment" class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider block mb-0.5">Tugas / Instruksi:</span>
                    <span v-else class="text-[10px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider block mb-0.5">Catatan:</span>
                    <p class="text-[11px] text-gray-600 dark:text-slate-400 italic">{{ item.notes }}</p>
                  </div>

                  <!-- Direct Download Modul Pembelajaran jika ada file -->
                  <div v-if="item.attachment_url" class="pt-2 mt-1.5 border-t border-gray-100 dark:border-slate-800">
                    <a
                      :href="item.attachment_url"
                      target="_blank"
                      :download="item.attachment_name || 'Modul_Belajar.pdf'"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95 max-w-full"
                    >
                      <Download class="w-3.5 h-3.5 shrink-0" />
                      <span class="truncate">Unduh {{ item.attachment_name || 'Berkas Materi' }}</span>
                    </a>
                  </div>
                </div>

                <!-- Catatan Izin Keluar Jam Mapel (Jika Siswa Izin) -->
                <div v-if="item.student_status === 'IZIN'" class="p-2.5 rounded-lg bg-amber-50/80 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-center gap-2">
                  <Clock class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                  <div>
                    <span class="font-bold">Izin Keluar Pukul {{ item.student_leave_time || '-' }} WIB:</span>
                    <span class="ml-1">{{ item.student_leave_note || 'Izin meninggalkan ruang kelas saat KBM berlangsung.' }}</span>
                  </div>
                </div>

                <!-- Alert Siswa Tidak Berada di Jam Ini jika ada -->
                <div v-if="item.absent_students && item.absent_students.length > 0" class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-800 dark:text-rose-200 text-[11px] space-y-0.5">
                  <span class="font-bold flex items-center gap-1">
                    <AlertCircle class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                    Siswa tidak berada di kelas pada jam ini ({{ item.absent_students.length }} siswa):
                  </span>
                  <p class="text-rose-700 dark:text-rose-300">
                    {{ item.absent_students.map((s: any) => s.name).join(', ') }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State (Jika bukan weekend dan timeline kosong) -->
          <div v-else-if="!data?.is_weekend" class="text-center py-8 rounded-xl border border-dashed border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-850/50 space-y-1.5">
            <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 mx-auto flex items-center justify-center">
              <Calendar class="w-4 h-4" />
            </div>
            <p class="text-xs font-bold text-gray-700 dark:text-slate-200">Tidak Ada Jadwal KBM pada Tanggal Ini</p>
            <p class="text-[11px] text-gray-400 dark:text-slate-500">Jadwal pembelajaran untuk tanggal terpilih kosong atau sedang jeda KBM.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Jurnal Kegiatan Belajar Anak di Sekolah -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Jurnal Kegiatan Belajar Anak di Sekolah</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500">Laporan aktivitas pembelajaran harian siswa dan guru di kelas</p>
              </div>
              <NuxtLink to="/jurnal-pelajaran" class="text-xs text-primary dark:text-blue-400 font-semibold hover:underline">
                Lihat Semua
              </NuxtLink>
            </div>

            <div class="space-y-3">
              <div
                v-for="j in data?.recent_journals || []"
                :key="j.id"
                class="p-3.5 rounded-xl border border-gray-100 dark:border-slate-800 bg-gray-50/60 dark:bg-slate-800/40 space-y-1.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-primary dark:text-blue-400">{{ j.subject }}</span>
                  <span class="text-[11px] text-gray-400 dark:text-slate-500">{{ j.date }}</span>
                </div>
                <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ j.activity }}</p>
                <p v-if="j.notes" class="text-[11px] text-gray-500 dark:text-slate-400">{{ j.notes }}</p>
                <div class="flex items-center justify-between pt-1 text-[10px] text-gray-400 dark:text-slate-500">
                  <span>Dilaporkan oleh: <strong class="text-gray-700 dark:text-slate-200">{{ j.student_author }}</strong></span>
                  <span>Guru: {{ j.teacher }}</span>
                </div>
              </div>
              <div v-if="!data?.recent_journals?.length" class="text-center py-8 text-xs text-gray-400 dark:text-slate-500">
                Belum ada jurnal kegiatan tercatat.
              </div>
            </div>
          </div>

          <!-- Riwayat Kehadiran & Surat Izin Anak -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Riwayat Kehadiran Harian Anak</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500">Catatan kehadiran dan surat izin siswa</p>
              </div>
              <NuxtLink to="/riwayat-kehadiran?tab=izin" class="text-xs text-primary dark:text-blue-400 font-semibold hover:underline">
                Riwayat Izin →
              </NuxtLink>
            </div>
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="rec in data?.recent_attendance || []"
                :key="rec.id"
                class="p-3 bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 rounded-xl space-y-1.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900 dark:text-white">{{ rec.date }}</span>
                  <StatusBadge :status="rec.status" />
                </div>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ rec.note || '-' }}</p>
              </div>
              <div v-if="!data?.recent_attendance?.length" class="py-6 text-center text-gray-400 dark:text-slate-500 text-xs">
                Belum ada riwayat kehadiran tercatat.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
              <table class="w-full text-left text-xs min-w-[550px]">
                <thead>
                  <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/60">
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Hari / Tanggal</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Waktu / Catatan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                  <tr v-for="rec in data?.recent_attendance || []" :key="rec.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-3.5 px-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ rec.date }}</td>
                    <td class="py-3.5 px-4 whitespace-nowrap"><StatusBadge :status="rec.status" /></td>
                    <td class="py-3.5 px-4 text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ rec.note || '-' }}</td>
                  </tr>
                  <tr v-if="!data?.recent_attendance?.length">
                    <td colspan="3" class="py-6 text-center text-gray-400 dark:text-slate-500">Belum ada riwayat kehadiran tercatat.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: SEKRETARIS (Rekap Kelas & Jurnal Kelas)  -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'SEKRETARIS'">
        <!-- Info Banner Dual Role Sekretaris -->
        <div class="p-3.5 sm:p-4 rounded-2xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-800/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-primary/10 dark:bg-blue-900/40 text-primary dark:text-blue-400 flex items-center justify-center shrink-0">
              <RefreshCw class="w-4 h-4" />
            </div>
            <div>
              <p class="text-xs font-bold text-gray-900 dark:text-white">Anda sedang dalam Mode Sekretaris Kelas</p>
              <p class="text-[11px] text-gray-500 dark:text-slate-400">Gunakan mode ini untuk mengelola presensi dan agenda kelas. Ingin melihat jadwal KBM atau konfirmasi kehadiran mandiri?</p>
            </div>
          </div>
          <button
            type="button"
            @click="authStore.switchRole('SISWA')"
            class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-blue-100 dark:hover:bg-slate-700 text-primary dark:text-blue-400 border border-blue-200 dark:border-slate-700 text-xs font-bold transition shadow-2xs cursor-pointer shrink-0 active:scale-95 flex items-center gap-1.5"
          >
            <span>Beralih ke Mode Siswa</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- 4 Stat Cards Hari Ini -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
          <StatCard title="Hadir" :value="data?.stats?.hadir_count ?? '0 Siswa'" :subtext="data?.stats?.hadir_percent ?? '0% Kehadiran'" color="blue" />
          <StatCard title="Izin" :value="data?.stats?.izin_count ?? '0 Siswa'" subtext="Disetujui Wali Kelas" color="blue" />
          <StatCard title="Sakit" :value="data?.stats?.sakit_count ?? '0 Siswa'" subtext="Disetujui Wali Kelas" color="blue" />
          <StatCard title="Alfa" :value="data?.stats?.alpha_count ?? '0 Siswa'" subtext="Tanpa Keterangan" color="blue" />
        </div>

        <!-- Presensi Kelas Hari Ini Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
            <div>
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Rekap Presensi {{ data?.class_name || 'Kelas' }} — {{ data?.session_date || 'Hari Ini' }}</h3>
              <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Sesi: {{ data?.session_status === 'LOCKED' ? 'Terkunci Resmi' : (data?.session_status === 'DRAFT' ? 'Draf Aktif' : 'Belum Dibuka') }} • {{ data?.verification_status === 'TERVERIFIKASI' ? 'Disahkan Walas' : 'Menunggu Pengesahan' }}</p>
            </div>
            <div class="flex items-center gap-2">
              <NuxtLink
                to="/jurnal-kelas"
                class="px-3 py-2 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:border-primary text-gray-700 dark:text-slate-200 hover:text-primary dark:hover:text-blue-400 rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition"
              >
                <FileText class="w-3.5 h-3.5 text-primary dark:text-blue-400" />
                <span>Agenda KBM Kelas</span>
              </NuxtLink>
              <NuxtLink
                to="/presensi"
                class="px-3.5 py-2 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition"
              >
                <ClipboardCheck class="w-4 h-4" />
                <span>Presensi Kelas</span>
              </NuxtLink>
            </div>
          </div>

          <!-- Mobile Card View -->
          <div class="block md:hidden space-y-2.5">
            <div
              v-for="item in data?.attendance_list || []"
              :key="item.student_id"
              class="p-3 bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 rounded-xl space-y-2"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-bold text-xs text-gray-900 dark:text-white">{{ item.name }}</span>
                    <span v-if="item.is_self" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800/50">
                      Anda
                    </span>
                  </div>
                  <span class="text-[11px] text-gray-400 dark:text-slate-500">NIS: {{ item.nis }}</span>
                </div>
                <StatusBadge :status="item.status" />
              </div>

              <div v-if="item.is_rejected_leave" class="text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 p-1.5 rounded-lg">
                Izin Ditolak Walas: {{ item.rejection_reason || 'Wajib verifikasi fisik di kelas' }}
              </div>

              <div class="flex items-center justify-between text-[11px] pt-1 border-t border-gray-100 dark:border-slate-800">
                <span class="text-gray-500 dark:text-slate-400 truncate max-w-[200px]">{{ item.note || '-' }}</span>
                <span v-if="item.verified" class="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-semibold gap-1 text-[11px]">
                  <CheckCircle2 class="w-3.5 h-3.5" /> Terverifikasi
                </span>
                <span v-else class="text-gray-400 dark:text-slate-500 text-[11px]">-</span>
              </div>
            </div>
            <div v-if="!data?.attendance_list?.length" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
              Belum ada data presensi kelas.
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left text-xs min-w-[720px]">
              <thead>
                <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/60 font-bold">
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap w-12">No</th>
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Nama Siswa</th>
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap">NIS</th>
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap text-center">Status Presensi</th>
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Keterangan</th>
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap text-right">Verifikasi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <tr v-for="item in data?.attendance_list || []" :key="item.student_id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                  <td class="py-3.5 px-4 text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ item.no }}</td>
                  <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                    <div class="flex items-center">
                      <span>{{ item.name }}</span>
                      <span v-if="item.is_self" class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800/50">
                        (Anda)
                      </span>
                      <span
                        v-if="item.is_rejected_leave"
                        class="ml-2 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50"
                        :title="item.rejection_reason || 'Surat izin ditolak Wali Kelas - Wajib verifikasi fisik di kelas'"
                      >
                        Izin Ditolak Walas
                      </span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ item.nis }}</td>
                  <td class="py-3.5 px-4 text-center whitespace-nowrap"><StatusBadge :status="item.status" /></td>
                  <td class="py-3.5 px-4 text-gray-500 dark:text-slate-400 whitespace-nowrap" :title="item.note || '-'">{{ item.note || '-' }}</td>
                  <td class="py-3.5 px-4 text-right whitespace-nowrap">
                    <span v-if="item.verified" class="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-semibold gap-1 text-[11px] whitespace-nowrap">
                      <CheckCircle2 class="w-3.5 h-3.5" /> Terverifikasi
                    </span>
                    <span v-else class="text-gray-400 dark:text-slate-500 text-[11px] whitespace-nowrap">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: GURU PENGAJAR                            -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'GURU_PENGAJAR'">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 sm:gap-5">
          <StatCard
            title="Kelas Diampu"
            :value="data?.stats?.kelas_diampu || '0 Kelas'"
            :subtext="authStore.user?.specialization || 'Kompetensi Mapel'"
            color="blue"
          />
          <StatCard
            title="Jam Mengajar"
            :value="data?.stats?.jam_mengajar || '0 Jam'"
            subtext="Hari ini terjadwal"
            color="blue"
          />
          <StatCard
            title="Jurnal Terisi"
            :value="data?.stats?.jurnal_terisi ?? '0/0'"
            subtext="Pertemuan KBM"
            color="green"
          />
          <StatCard
            title="Belum Diisi"
            :value="data?.stats?.jurnal_belum_diisi ?? '0 Pertemuan'"
            subtext="Pertemuan Menunggu"
            :color="(data?.stats?.jurnal_belum_diisi && data?.stats?.jurnal_belum_diisi !== '0 Pertemuan' && data?.stats?.jurnal_belum_diisi !== '0') ? 'red' : 'green'"
          />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Jadwal Mengajar Hari Ini (2 cols) -->
          <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Jadwal Mengajar Hari Ini</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500">5 pertemuan • Total 5 jam mengajar</p>
              </div>
              <NuxtLink to="/jurnal-materi" class="px-3 py-1.5 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs font-semibold">
                + Isi Jurnal Baru
              </NuxtLink>
            </div>

            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="sch in data?.schedules || []"
                :key="sch.id"
                class="p-3.5 bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 rounded-xl space-y-2"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900 dark:text-white">{{ sch.subject }}</span>
                  <span class="font-mono text-[11px] text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-850 px-2 py-0.5 rounded border border-gray-150 dark:border-slate-700">{{ sch.time }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-600 dark:text-slate-300 font-medium">Kelas: {{ sch.class }}</span>
                  <StatusBadge :status="sch.status" />
                </div>
                <div class="pt-1.5">
                  <NuxtLink
                    to="/jurnal-materi"
                    class="w-full min-h-[44px] flex items-center justify-center bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-slate-200 shadow-2xs transition"
                  >
                    Isi Jurnal
                  </NuxtLink>
                </div>
              </div>
              <div v-if="!data?.schedules?.length" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
                Tidak ada jadwal mengajar hari ini.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
              <table class="w-full text-left text-xs whitespace-nowrap min-w-[650px]">
                <thead>
                  <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/60 font-bold">
                    <th class="py-3.5 px-4 font-semibold">Jam</th>
                    <th class="py-3.5 px-4 font-semibold">Mata Pelajaran</th>
                    <th class="py-3.5 px-4 font-semibold">Kelas</th>
                    <th class="py-3.5 px-4 font-semibold">Status Jurnal</th>
                    <th class="py-3.5 px-4 font-semibold text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                  <tr v-for="sch in data?.schedules || []" :key="sch.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-3.5 px-4 font-medium text-gray-900 dark:text-white">{{ sch.time }}</td>
                    <td class="py-3.5 px-4 font-semibold text-gray-800 dark:text-slate-200">{{ sch.subject }}</td>
                    <td class="py-3.5 px-4 text-gray-600 dark:text-slate-400">{{ sch.class }}</td>
                    <td class="py-3.5 px-4"><StatusBadge :status="sch.status" /></td>
                    <td class="py-3.5 px-4 text-right">
                      <NuxtLink to="/jurnal-materi" class="inline-flex px-3 py-1 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700 rounded-lg text-[11px] font-semibold text-gray-700 dark:text-slate-200 shadow-2xs transition">
                        Isi Jurnal
                      </NuxtLink>
                    </td>
                  </tr>
                  <tr v-if="!data?.schedules?.length">
                    <td colspan="5" class="py-6 text-center text-gray-400 dark:text-slate-500">Tidak ada jadwal mengajar hari ini.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Ringkasan Presensi & Pengingat (1 col) -->
          <div class="space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs">
              <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Ringkasan Presensi Kelas</h3>
              <p class="text-xs text-gray-400 dark:text-slate-500 mb-4">Persentase kehadiran hari ini</p>
              <div class="space-y-3">
                <div v-for="cls in data?.attendance_summary || []" :key="cls.class">
                  <div class="flex justify-between text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                    <span>{{ cls.class }}</span>
                    <span>{{ cls.present_ratio }} <span class="font-bold text-gray-900 dark:text-white">{{ cls.percent }}%</span></span>
                  </div>
                  <div class="w-full bg-gray-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-2 rounded-full" :style="{ width: cls.percent + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Pengingat Pembelajaran</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50">
                  {{ (data?.reminders || []).length }} Tugas
                </span>
              </div>
              <ul class="space-y-2.5 text-xs text-gray-600 dark:text-slate-400">
                <li v-for="(rem, idx) in data?.reminders || []" :key="idx" class="bg-gray-50/70 dark:bg-slate-800/40 p-2.5 rounded-xl border border-gray-100 dark:border-slate-800 border-l-3 border-l-amber-500">
                  <div class="flex-1">
                    <p class="font-medium text-gray-800 dark:text-slate-200">{{ rem }}</p>
                    <NuxtLink
                      v-if="rem.includes('jurnal belum diisi')"
                      to="/jurnal-materi"
                      class="inline-block mt-1 text-[11px] font-bold text-primary dark:text-blue-400 hover:underline"
                    >
                      Buka Form Jurnal KBM →
                    </NuxtLink>
                    <NuxtLink
                      v-else-if="rem.includes('surat izin')"
                      to="/persetujuan-izin"
                      class="inline-block mt-1 text-[11px] font-bold text-primary dark:text-blue-400 hover:underline"
                    >
                      Buka Persetujuan Izin →
                    </NuxtLink>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: WALI KELAS (eks GURU_PEMBIMBING)         -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'GURU_PEMBIMBING'">
        <!-- 4 Stat Cards for Walas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <StatCard title="Kelas Binaan (Wali Kelas)" :value="data?.stats?.total_kelas ?? '-'" :subtext="data?.stats?.total_siswa ?? 'Semester Ini'" color="blue" />
          <StatCard title="Status Presensi Hari Ini" :value="data?.stats?.presensi_status ?? 'Belum Presensi'" subtext="Rekap Harian Kelas" color="emerald" />
          <StatCard title="Jurnal Kegiatan Terisi" :value="data?.stats?.jurnal_terisi ?? '0 Sesi'" subtext="Realisasi Hari Ini" color="blue" />
          <StatCard title="Surat Izin Menunggu" :value="data?.stats?.surat_izin_pending ?? '0 Permohonan'" subtext="Perlu Verifikasi Wali Kelas" color="amber" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Pemantauan Real-time Jurnal Kelas (2 cols) -->
          <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Pemantauan Real-time Jurnal & Kehadiran Kelas Binaan</h3>
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="cls in data?.monitored_classes || []"
                :key="cls.id"
                class="p-3 bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 rounded-xl space-y-2"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900 dark:text-white">{{ cls.class }}</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="{
                      'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50': cls.verification_status === 'TERVERIFIKASI',
                      'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50': cls.session_status === 'LOCKED' && cls.verification_status !== 'TERVERIFIKASI',
                      'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50': cls.session_status === 'DRAFT',
                      'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400': !cls.session_status || cls.session_status === 'BELUM_ADA',
                    }"
                  >
                    {{ cls.attendance_status_label || 'Belum Presensi' }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-gray-500 dark:text-slate-400">Sekretaris: {{ cls.secretary }}</span>
                  <span class="font-semibold text-gray-800 dark:text-slate-200">{{ cls.attendance_percent }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-gray-200/60 dark:border-slate-800">
                  <StatusBadge :status="cls.journal_status" />
                  <NuxtLink to="/presensi" class="text-primary dark:text-blue-400 font-bold hover:underline flex items-center gap-1">
                    <span>Lihat Presensi / ACC</span>
                    <ArrowRight class="w-3 h-3" />
                  </NuxtLink>
                </div>
              </div>
              <div v-if="!data?.monitored_classes?.length" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
                Belum ada kelas binaan.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
              <table class="w-full text-left text-xs min-w-[550px]">
                <thead>
                  <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/60 font-bold">
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Kelas</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Sekretaris</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Status Jurnal</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Status Presensi</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Kehadiran</th>
                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                  <tr v-for="cls in data?.monitored_classes || []" :key="cls.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ cls.class }}</td>
                    <td class="py-3.5 px-4 text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ cls.secretary }}</td>
                    <td class="py-3.5 px-4 whitespace-nowrap"><StatusBadge :status="cls.journal_status" /></td>
                    <td class="py-3.5 px-4 whitespace-nowrap">
                      <span
                        class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1"
                        :class="{
                          'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50': cls.verification_status === 'TERVERIFIKASI',
                          'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50': cls.session_status === 'LOCKED' && cls.verification_status !== 'TERVERIFIKASI',
                          'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50': cls.session_status === 'DRAFT',
                          'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400': !cls.session_status || cls.session_status === 'BELUM_ADA',
                        }"
                      >
                        <span class="w-1.5 h-1.5 rounded-full" :class="{
                          'bg-emerald-500': cls.verification_status === 'TERVERIFIKASI',
                          'bg-blue-500': cls.session_status === 'LOCKED' && cls.verification_status !== 'TERVERIFIKASI',
                          'bg-amber-500': cls.session_status === 'DRAFT',
                          'bg-gray-400': !cls.session_status || cls.session_status === 'BELUM_ADA',
                        }"></span>
                        <span>{{ cls.attendance_status_label || 'Belum Presensi' }}</span>
                      </span>
                    </td>
                    <td class="py-3.5 px-4 font-semibold text-gray-800 dark:text-slate-200 whitespace-nowrap">{{ cls.attendance_percent }}</td>
                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                      <NuxtLink
                        to="/presensi"
                        class="px-3 py-1.5 rounded-lg bg-primary/10 dark:bg-blue-900/30 hover:bg-primary text-primary dark:text-blue-400 hover:text-white font-bold text-[11px] transition inline-flex items-center gap-1 cursor-pointer"
                      >
                        <span>Presensi / ACC</span>
                        <ArrowRight class="w-3 h-3" />
                      </NuxtLink>
                    </td>
                  </tr>
                  <tr v-if="!data?.monitored_classes?.length">
                    <td colspan="6" class="py-6 text-center text-gray-400 dark:text-slate-500">Belum ada kelas binaan.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Monitoring Kehadiran Kelas Per Jam Pelajaran (Realtime untuk Walas) -->
            <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800 space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <Clock class="w-4 h-4 text-primary dark:text-blue-400" />
                    <span>Kehadiran Kelas per Jam Pelajaran</span>
                  </h4>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Pantau kehadiran siswa per jam mata pelajaran hari ini</p>
                </div>
                <span class="text-[11px] font-bold text-primary dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800/50 px-2.5 py-0.5 rounded-full">
                  {{ data?.periods_attendance?.length || 0 }} Jam Pelajaran
                </span>
              </div>

              <div v-if="!data?.periods_attendance?.length" class="text-center py-4 text-slate-400 dark:text-slate-500 text-xs">
                Tidak ada jadwal KBM aktif untuk hari ini.
              </div>

              <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div
                  v-for="p in data?.periods_attendance"
                  :key="p.id"
                  class="p-3.5 rounded-2xl border transition"
                  :class="p.is_current ? 'border-blue-400 dark:border-blue-500 bg-blue-50/20 dark:bg-blue-950/20 ring-1 ring-blue-300 dark:ring-blue-700' : 'border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-850'"
                >
                  <div class="flex items-center justify-between text-xs pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1">
                      <Clock class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
                      {{ p.time }}
                    </span>
                    <span
                      class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                      :class="p.is_current ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 animate-pulse' : (p.status_kbm === 'Selesai' ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300')"
                    >
                      {{ p.status_kbm }}
                    </span>
                  </div>

                  <div class="pt-2 space-y-1 text-xs">
                    <div class="font-bold text-slate-900 dark:text-white truncate">{{ p.subject }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Guru: {{ p.teacher }}</div>

                    <!-- Kehadiran Status Jam Ini -->
                    <div class="pt-1.5 flex items-center justify-between text-[11px]">
                      <span v-if="p.absent_count === 0" class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400">
                        <CheckCircle2 class="w-3 h-3" /> Hadir Lengkap
                      </span>
                      <span v-else class="inline-flex items-center gap-1 font-bold text-rose-600 dark:text-rose-400">
                        <AlertCircle class="w-3 h-3" /> {{ p.absent_count }} Siswa Tidak di Kelas
                      </span>
                      <span class="text-slate-500 dark:text-slate-400 font-mono">{{ p.present_count }} Hadir</span>
                    </div>

                    <!-- Siswa Tidak Hadir Names jika ada -->
                    <div v-if="p.absent_students && p.absent_students.length > 0" class="pt-1 flex flex-wrap gap-1">
                      <span
                        v-for="std in p.absent_students"
                        :key="std.id"
                        class="px-2 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-[10px] font-semibold border border-rose-200 dark:border-rose-800/50"
                      >
                        {{ std.name }}
                      </span>
                    </div>

                    <!-- Presensi Siswa yang Dicatat Guru Pengajar Jam Ini (Sesuai Wireframe) -->
                    <div v-if="p.student_attendance && p.student_attendance.length > 0" class="pt-2 border-t border-gray-100 dark:border-slate-800 space-y-1">
                      <span class="text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider block">Presensi Murid Jam Ini:</span>
                      <div class="flex flex-wrap gap-1.5">
                        <span
                          v-for="std in p.student_attendance"
                          :key="std.id"
                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border inline-flex items-center gap-1"
                          :class="std.status === 'HADIR' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50' : (std.status === 'IZIN' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/50' : (std.status === 'SAKIT' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/50'))"
                        >
                          {{ std.name }}: {{ std.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Persetujuan Surat Izin Cards (1 col) -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Persetujuan Surat Izin Siswa</h3>
              <span class="px-2 py-0.5 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50 rounded-full text-[10px] font-bold">
                {{ data?.pending_leaves?.length || 0 }} Baru
              </span>
            </div>

            <div class="space-y-4">
              <div
                v-for="lv in data?.pending_leaves || []"
                :key="lv.id"
                class="border border-gray-150 dark:border-slate-800 rounded-xl p-4 space-y-3 bg-gray-50/30 dark:bg-slate-800/40"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ lv.student_name }}</h4>
                    <p class="text-[10px] text-gray-400 dark:text-slate-500">{{ lv.class }}</p>
                  </div>
                  <StatusBadge :status="lv.type" />
                </div>
                <p class="text-xs text-gray-600 dark:text-slate-300 line-clamp-2">{{ lv.reason }}</p>
                <div class="flex items-center gap-2 pt-1">
                  <button
                    @click="handleQuickReview(lv.id, 'APPROVED')"
                    class="flex-1 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors cursor-pointer"
                  >
                    Setujui
                  </button>
                  <button
                    @click="handleQuickReview(lv.id, 'REJECTED')"
                    class="flex-1 py-1.5 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-750 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
                  >
                    Tolak
                  </button>
                </div>
              </div>

              <div v-if="!data?.pending_leaves?.length" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
                Tidak ada permohonan surat izin yang pending.
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ============================================== -->
      <!-- ROLE: SUPERADMIN (Pusat Pengawasan Akademik)    -->
      <!-- ============================================== -->
      <template v-else-if="authStore.user?.role === 'SUPERADMIN'">
        <!-- 5 Academic Stat Cards (Guru disatukan) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-4">
          <StatCard title="Total Guru" :value="data?.stats?.total_guru ?? '0'" subtext="Tenaga Pendidik" color="blue" />
          <StatCard title="Total Siswa" :value="data?.stats?.total_siswa ?? '0'" subtext="Terdata di Kelas" color="blue" />
          <StatCard title="Total Kelas" :value="data?.stats?.total_kelas ?? '0'" subtext="Kelas Terdaftar" color="blue" />
          <StatCard title="Rata-rata Presensi" :value="data?.stats?.kehadiran_rata_rata ?? '0%'" subtext="Hari Ini" color="blue" />
          <StatCard title="Jurnal Terisi" :value="data?.stats?.jurnal_terisi_today ?? '0'" subtext="Hari Ini" color="blue" />
        </div>

        <!-- Master Layout Superadmin -->
        <div class="grid grid-cols-1 gap-6">
          <!-- Pemantauan Seluruh Kelas (Full Width) -->
          <div class="w-full bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Pemantauan Presensi & Jurnal Kelas Hari Ini</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Pantau status pengisian presensi dan jurnal seluruh kelas</p>
              </div>

              <div class="inline-flex items-center px-3 py-1 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800/50 text-xs font-bold shrink-0 self-start sm:self-center">
                <span>Kelas Aktif</span>
              </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-2.5">
              <div
                v-for="cls in filteredClassesMonitoring"
                :key="cls.id"
                class="p-3.5 bg-gray-50/70 dark:bg-slate-800/40 border border-gray-150 dark:border-slate-800 rounded-xl space-y-2.5"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-1.5">
                    <span class="font-bold text-sm text-gray-900 dark:text-white">{{ cls.class_name }}</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                      {{ cls.major || 'UMUM' }}
                    </span>
                  </div>
                  <div class="text-right">
                    <span class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ cls.attendance_percent }}%</span>
                    <span class="block text-[10px] text-gray-400 dark:text-slate-500">{{ cls.present_ratio }}</span>
                  </div>
                </div>

                <div class="text-xs text-gray-600 dark:text-slate-300 space-y-0.5">
                  <p><span class="text-gray-400 dark:text-slate-500">Wali Kelas:</span> {{ cls.advisor_name }}</p>
                  <p><span class="text-gray-400 dark:text-slate-500">Sekretaris:</span> {{ cls.secretary_name }}</p>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-200/60 dark:border-slate-800 text-xs">
                  <div class="flex items-center gap-2">
                    <span
                      v-if="cls.verification_status === 'TERVERIFIKASI'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 inline-flex items-center gap-1"
                    >
                      <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400 shrink-0" /> Terverifikasi
                    </span>
                    <span
                      v-else-if="cls.session_status === 'LOCKED'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/50 inline-flex items-center gap-1"
                    >
                      <Lock class="w-3 h-3 text-red-600 dark:text-red-400 shrink-0" /> Terkunci
                    </span>
                    <span
                      v-else-if="cls.session_status === 'DRAFT'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50 inline-flex items-center gap-1"
                    >
                      <FileEdit class="w-3 h-3 text-amber-600 dark:text-amber-400 shrink-0" /> Aktif
                    </span>
                    <span
                      v-else
                      class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700 inline-flex items-center gap-1"
                    >
                      <Clock class="w-3 h-3 text-gray-400 dark:text-slate-500 shrink-0" /> Belum Buka
                    </span>
                    <StatusBadge :status="cls.journal_status" />
                  </div>

                  <button
                    v-if="cls.session_status === 'LOCKED'"
                    @click="handleForceUnlock(cls.id)"
                    class="px-3 py-1.5 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50 rounded-lg text-xs font-bold transition cursor-pointer min-h-[44px] inline-flex items-center gap-1"
                  >
                    Buka Kunci
                  </button>
                </div>
              </div>
              <div v-if="!filteredClassesMonitoring?.length" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
                Tidak ada data pemantauan kelas.
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
              <table class="w-full text-left text-xs border-collapse min-w-[850px]">
                <thead>
                  <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 font-bold bg-gray-50/50 dark:bg-slate-800/60">
                    <th class="py-3 px-3.5 whitespace-nowrap">Kelas</th>
                    <th class="py-3 px-3.5 whitespace-nowrap">Jurusan</th>
                    <th class="py-3 px-3.5 whitespace-nowrap">Wali Kelas</th>
                    <th class="py-3 px-3.5 whitespace-nowrap">Sekretaris</th>
                    <th class="py-3 px-3.5 whitespace-nowrap text-center">Status Sesi</th>
                    <th class="py-3 px-3.5 whitespace-nowrap text-right">Kehadiran</th>
                    <th class="py-3.5 px-3.5 whitespace-nowrap text-center">Jurnal KBM</th>
                    <th class="py-3 px-3.5 whitespace-nowrap text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                  <tr v-for="cls in filteredClassesMonitoring" :key="cls.id" class="hover:bg-gray-50/70 dark:hover:bg-slate-800/40 transition">
                    <td class="py-3 px-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ cls.class_name }}</td>
                    <td class="py-3 px-3.5 whitespace-nowrap">
                      <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800/50 whitespace-nowrap">
                        {{ cls.major || 'UMUM' }}
                      </span>
                    </td>
                    <td class="py-3 px-3.5 text-gray-600 dark:text-slate-300 whitespace-nowrap" :title="cls.advisor_name">{{ cls.advisor_name }}</td>
                    <td class="py-3 px-3.5 text-gray-600 dark:text-slate-300 whitespace-nowrap" :title="cls.secretary_name">{{ cls.secretary_name }}</td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                      <span
                        v-if="cls.verification_status === 'TERVERIFIKASI'"
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 whitespace-nowrap inline-flex items-center gap-1"
                      >
                        <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400 shrink-0" /><span>Terverifikasi Walas</span>
                      </span>
                      <span
                        v-else-if="cls.session_status === 'LOCKED'"
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/50 whitespace-nowrap inline-flex items-center gap-1"
                      >
                        <Lock class="w-3 h-3 text-red-600 dark:text-red-400 shrink-0" /><span>Terkunci</span>
                      </span>
                      <span
                        v-else-if="cls.session_status === 'DRAFT'"
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50 whitespace-nowrap inline-flex items-center gap-1"
                      >
                        <FileEdit class="w-3 h-3 text-amber-600 dark:text-amber-400 shrink-0" /><span>Aktif</span>
                      </span>
                      <span
                        v-else
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700 whitespace-nowrap inline-flex items-center gap-1"
                      >
                        <Clock class="w-3 h-3 text-gray-400 dark:text-slate-500 shrink-0" /><span>Belum Buka</span>
                      </span>
                    </td>
                    <td class="py-3 px-3 text-right font-bold text-gray-800 dark:text-slate-200 whitespace-nowrap">
                      {{ cls.attendance_percent }}%
                      <span class="block text-[10px] font-normal text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ cls.present_ratio }}</span>
                    </td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                      <StatusBadge :status="cls.journal_status" />
                    </td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                      <button
                        v-if="cls.session_status === 'LOCKED'"
                        @click="handleForceUnlock(cls.id)"
                        class="px-2.5 py-1 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50 rounded-lg text-[10px] font-bold transition cursor-pointer whitespace-nowrap inline-flex items-center gap-1"
                        title="Buka kunci sesi presensi agar dapat diperbaiki"
                      >
                        Buka Kunci
                      </button>
                      <span v-else class="text-gray-300 dark:text-slate-600 text-xs">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <!-- Live Global Audit Trail -->
          <div class="w-full space-y-6">

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-3">
              <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Aktivitas Terkini Sistem</h3>
                <NuxtLink to="/pengaturan-sistem" class="text-[11px] text-primary dark:text-blue-400 font-bold hover:underline">
                  Semua Log
                </NuxtLink>
              </div>

              <div class="space-y-3">
                <div
                  v-for="log in data?.recent_audit_logs || []"
                  :key="log.id"
                  class="text-xs border-l-2 border-primary pl-3 py-1 space-y-0.5"
                >
                  <div class="flex items-center justify-between text-[10px] text-gray-400 dark:text-slate-500">
                    <span class="font-bold text-gray-800 dark:text-slate-200">{{ log.user }}</span>
                    <span>{{ log.time }}</span>
                  </div>
                  <p class="text-gray-600 dark:text-slate-300 text-[11px] leading-tight">{{ log.action }}</p>
                  <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">IP: {{ log.ip_address }}</p>
                </div>

                <div v-if="!data?.recent_audit_logs?.length" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
                  Belum ada log aktivitas tercatat.
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script setup lang="ts">
import {
  Lock,
  FileEdit,
  Clock,
  ClipboardCheck,
  CheckCircle2,
  XCircle,
  Mail,
  FileText,
  Calendar,
  AlertCircle,
  Download,
  Users,
  RefreshCw,
  ArrowRight,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useToastStore } from '~/stores/toast'
import { useUiStore } from '~/stores/ui'

const authStore = useAuthStore()
const toast = useToastStore()
const uiStore = useUiStore()
const data = ref<any>(null)
const isLoading = ref(true)
const isCheckingIn = ref(false)
const checkInMessage = ref('')
const selectedMajorFilter = ref('ALL')
const studentJournalTab = ref<'journals' | 'materials'>('journals')

watch(
  () => authStore.user?.role,
  (newRole, oldRole) => {
    if (newRole && newRole !== oldRole) {
      data.value = null
      loadDashboard(false)
    }
  }
)

const filteredClassesMonitoring = computed(() => {
  if (!data.value?.classes_monitoring) return []
  if (selectedMajorFilter.value === 'ALL') return data.value.classes_monitoring
  return data.value.classes_monitoring.filter((c: any) => {
    let m = c.major
    if (m && (m.toLowerCase().includes('rekayasa') || m.toLowerCase().includes('perangkat'))) {
      m = 'RPL'
    }
    return m === selectedMajorFilter.value
  })
})

const dashboardTitle = computed(() => {
  const role = authStore.user?.role
  if (role === 'SUPERADMIN') return 'Dashboard Super Administrator'
  if (role === 'SISWA') return 'Dashboard Siswa'
  if (role === 'ORANG_TUA') return 'Dashboard Orang Tua'
  if (role === 'SEKRETARIS') return 'Dashboard Presensi ' + (data.value?.class_name || 'Kelas')
  if (role === 'GURU_PENGAJAR') return 'Dashboard Guru'
  if (role === 'GURU_PEMBIMBING') return 'Dashboard Wali Kelas'
  return 'Dashboard'
})

const dashboardSubtitle = computed(() => {
  const role = authStore.user?.role
  if (role === 'GURU_PENGAJAR') return 'Jadwal Pembelajaran & Pengisian Jurnal KBM Hari Ini'
  if (role === 'GURU_PEMBIMBING') return 'Monitoring Rombel & Rekapitulasi Presensi Binaan'
  if (role === 'SUPERADMIN') return 'Monitoring Terpadu Presensi & KBM SMKN 71 Jakarta'
  if (role === 'SISWA') return 'Status Presensi Mandiri & Agenda Belajar'
  if (role === 'ORANG_TUA') return 'Pantauan Kehadiran & Aktivitas Belajar Ananda'
  if (role === 'SEKRETARIS') return 'Pengelolaan Presensi Harian Siswa di Kelas'
  return ''
})

function getAuthToken(): string | null {
  if (authStore.token) return authStore.token
  if (process.client) {
    const saved = localStorage.getItem('token')
    if (saved) {
      authStore.token = saved
      return saved
    }
  }
  return null
}

function getTodayIsoString() {
  const d = new Date()
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const timelineSelectedDate = ref('')
const serverTodayDate = ref('')

const isDateToday = computed(() => {
  if (!timelineSelectedDate.value) return true
  const todayStr = serverTodayDate.value || data.value?.server_today || data.value?.selected_date
  return !todayStr || timelineSelectedDate.value === todayStr
})

function shiftDate(days: number) {
  const base = timelineSelectedDate.value || data.value?.selected_date || serverTodayDate.value || getTodayIsoString()
  const parts = base.split('-').map(Number)
  const current = new Date(parts[0], parts[1] - 1, parts[2])
  current.setDate(current.getDate() + days)
  const year = current.getFullYear()
  const month = String(current.getMonth() + 1).padStart(2, '0')
  const day = String(current.getDate()).padStart(2, '0')
  timelineSelectedDate.value = `${year}-${month}-${day}`
  loadDashboard(false)
}

function resetToToday() {
  timelineSelectedDate.value = ''
  loadDashboard(false)
}

function onTimelineDateChange() {
  loadDashboard(false)
}

const selectedChildId = ref<number | null>(null)

function switchChild(childId: number) {
  selectedChildId.value = childId
  loadDashboard(false, childId)
}

async function loadDashboard(silent = false, childId?: number) {
  let token = getAuthToken()
  if (!token) {
    await new Promise(r => setTimeout(r, 100))
    token = getAuthToken()
  }
  if (!token) return

  if (!silent && !data.value) {
    isLoading.value = true
  }

  try {
    const params: any = { _t: Date.now() }
    const cId = childId || selectedChildId.value
    if (cId) {
      params.student_id = cId
    }
    if (timelineSelectedDate.value) {
      params.date = timelineSelectedDate.value
    }
    const res = await $fetch<any>(`${authStore.apiBase}/dashboard`, {
      headers: { Authorization: `Bearer ${token}` },
      params
    })
    data.value = res
    if (res?.selected_date) {
      serverTodayDate.value = res.server_today || res.selected_date
      if (!timelineSelectedDate.value) {
        timelineSelectedDate.value = res.selected_date
      }
    }
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

async function handleSelfCheckIn() {
  isCheckingIn.value = true
  checkInMessage.value = ''
  try {
    const res: any = await $fetch(`${authStore.apiBase}/attendance/check-in`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {}
    })
    checkInMessage.value = res?.message || 'Presensi Mandiri berhasil dicatat!'
    toast.success(checkInMessage.value)
    await loadDashboard(true)
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal melakukan presensi mandiri.')
  } finally {
    isCheckingIn.value = false
  }
}

async function handleForceUnlock(classId: number) {
  const ok = await confirmDialog({
    title: 'Buka Kunci Presensi?',
    message: 'Buka kunci sesi presensi kelas ini kembali ke status DRAFT agar dapat diedit kembali oleh pengurus kelas?',
    confirmText: 'Buka Kunci Presensi',
    cancelText: 'Batal',
    type: 'warning',
  })
  if (!ok) return
  try {
    const today = getLocalTodayYMD()
    await $fetch(`${authStore.apiBase}/attendance/force-unlock`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: { class_id: classId, date: today }
    })
    toast.success('Sesi presensi berhasil dibuka kembali.')
    loadDashboard()
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal membuka kunci sesi presensi.')
  }
}

async function handleQuickReview(id: number, status: 'APPROVED' | 'REJECTED') {
  try {
    await $fetch(`${authStore.apiBase}/leave-requests/${id}/review`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: { status, review_note: status === 'APPROVED' ? 'Disetujui oleh Wali Kelas' : 'Ditolak oleh Wali Kelas' }
    })
    loadDashboard()
  } catch (e) {
    toast.error('Gagal memperbarui status izin.')
  }
}

let refreshTimer: any = null

function handleVisibilityOrFocus() {
  if (typeof document !== 'undefined' && document.visibilityState === 'visible') {
    loadDashboard(true)
  }
}

onMounted(() => {
  loadDashboard()
  if (process.client) {
    window.addEventListener('focus', handleVisibilityOrFocus)
    document.addEventListener('visibilitychange', handleVisibilityOrFocus)
    // Sinkronisasi realtime tiap 15 detik
    refreshTimer = setInterval(() => {
      if (document.visibilityState === 'visible') {
        loadDashboard(true)
      }
    }, 15000)
  }
})

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer)
  if (process.client) {
    window.removeEventListener('focus', handleVisibilityOrFocus)
    document.removeEventListener('visibilitychange', handleVisibilityOrFocus)
  }
})
</script>
