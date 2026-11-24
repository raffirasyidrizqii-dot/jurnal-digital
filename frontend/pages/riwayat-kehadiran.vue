<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      :title="pageTitle"
      :subtitle="pageSubtitle"
    >
      <template #actions>
        <div class="flex items-center gap-1 sm:gap-2">
          <button
            v-if="activeMainTab === 'presensi'"
            @click="exportCsv"
            type="button"
            class="p-2 sm:px-3.5 sm:py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition text-xs font-semibold flex items-center gap-1.5 shadow-xs cursor-pointer shrink-0 min-h-[34px] min-w-[34px] sm:min-h-[40px]"
            title="Ekspor CSV"
          >
            <DownloadIcon class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 shrink-0" />
            <span class="hidden sm:inline">Ekspor CSV</span>
          </button>
          <button
            v-if="activeMainTab === 'izin'"
            @click="fetchLeaves"
            type="button"
            class="p-2 sm:px-3.5 sm:py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition text-xs font-semibold flex items-center gap-1.5 shadow-xs cursor-pointer shrink-0 min-h-[34px] min-w-[34px] sm:min-h-[40px]"
            title="Segarkan Data"
          >
            <RefreshCwIcon class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 shrink-0" :class="{ 'animate-spin': loadingLeaves }" />
            <span class="hidden sm:inline">Segarkan Data</span>
          </button>
        </div>
      </template>
    </AppHeader>

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Main Navigation Tabs: Presensi vs Surat Izin -->
      <div class="flex items-center gap-2 border-b border-slate-200/80 dark:border-slate-800 pb-3 mb-2">
        <button
          type="button"
          @click="activeMainTab = 'presensi'"
          class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer flex items-center gap-2 select-none shadow-xs"
          :class="activeMainTab === 'presensi' ? 'bg-primary text-white' : 'bg-blue-50/70 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:bg-blue-100 dark:hover:bg-slate-800 hover:text-primary dark:hover:text-blue-400 border border-transparent dark:border-slate-700/60'"
        >
          <CalendarCheck class="w-4 h-4" />
          <span>{{ authStore.user?.role === 'ORANG_TUA' ? 'Kehadiran Ananda' : 'Riwayat Kehadiran' }}</span>
        </button>

        <button
          type="button"
          @click="activeMainTab = 'izin'"
          class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer flex items-center gap-2 select-none shadow-xs"
          :class="activeMainTab === 'izin' ? 'bg-primary text-white' : 'bg-blue-50/70 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:bg-blue-100 dark:hover:bg-slate-800 hover:text-primary dark:hover:text-blue-400 border border-transparent dark:border-slate-700/60'"
        >
          <Mail class="w-4 h-4" />
          <span>{{ authStore.user?.role === 'ORANG_TUA' ? 'Surat Izin Ananda' : 'Surat Izin' }}</span>
        </button>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 1: REKAP PRESENSI & MODERN DIAGRAM                         -->
      <!-- ============================================================== -->
      <div v-if="activeMainTab === 'presensi'" class="space-y-6">
        <!-- Top Alert Cards: Izin Keluar Jam Mapel & Alfa Mapel Hari Ini -->
        <div v-if="todayKbmLeaves.length > 0 || todayKbmAlfa.length > 0" class="space-y-3">
          <!-- Yellow Card: Izin Keluar Jam Mapel Hari Ini -->
          <div
            v-for="(item, idx) in todayKbmLeaves"
            :key="'leave-alert-' + idx"
            class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-950 dark:text-amber-200 shadow-2xs flex items-start gap-3.5"
          >
            <div class="w-9 h-9 rounded-xl bg-amber-500/15 dark:bg-amber-500/25 flex items-center justify-center shrink-0 text-amber-600 dark:text-amber-400 font-bold">
              <ClockIcon class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2 py-0.5 rounded-md bg-amber-200/70 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 font-bold text-[11px] uppercase tracking-wider">
                  Izin Keluar Mapel Hari Ini
                </span>
                <span class="text-xs text-amber-800/80 dark:text-amber-300/80 font-medium">
                  Jam Keluar: <strong class="font-bold text-amber-900 dark:text-amber-100">{{ item.leave_time }}</strong>
                </span>
              </div>
              <p class="text-xs sm:text-sm font-bold text-amber-950 dark:text-amber-100 mt-1">
                {{ item.subject }} • {{ item.teacher }}
              </p>
              <p class="text-xs text-amber-800 dark:text-amber-300 mt-0.5">
                Keperluan / Alasan: <span class="italic font-medium">"{{ item.reason }}"</span>
              </p>
            </div>
          </div>

          <!-- Red Card: Alfa Jam Mapel Hari Ini -->
          <div
            v-for="(item, idx) in todayKbmAlfa"
            :key="'alfa-alert-' + idx"
            class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-950 dark:text-rose-200 shadow-2xs flex items-start gap-3.5"
          >
            <div class="w-9 h-9 rounded-xl bg-rose-500/15 dark:bg-rose-500/25 flex items-center justify-center shrink-0 text-rose-600 dark:text-rose-400 font-bold">
              <AlertTriangleIcon class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2 py-0.5 rounded-md bg-rose-200/70 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300 font-bold text-[11px] uppercase tracking-wider">
                  Peringatan Alfa Mapel Hari Ini
                </span>
                <span class="text-xs text-rose-800/80 dark:text-rose-300/80 font-medium">
                  {{ item.schedule_time }}
                </span>
              </div>
              <p class="text-xs sm:text-sm font-bold text-rose-950 dark:text-rose-100 mt-1">
                {{ item.subject }} • {{ item.teacher }}
              </p>
              <p class="text-xs text-rose-800 dark:text-rose-300 mt-0.5">
                {{ item.note }}
              </p>
            </div>
          </div>
        </div>

        <!-- 4 Modern Stat Cards (Presensi Masuk Harian) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <StatCard title="Hadir" :value="hadirDaysText" :subtext="hadirPctText" color="green" />
          <StatCard title="Sakit" :value="sakitDaysText" subtext="Surat keterangan" color="orange" />
          <StatCard title="Izin" :value="izinDaysText" subtext="Izin disetujui" color="blue" />
          <StatCard title="Alfa" :value="alphaDaysText" subtext="Tanpa keterangan" color="red" />
        </div>

        <!-- ============================================================== -->
        <!-- CARD REKAPITULASI PRESENSI PER JAM MATA PELAJARAN (KBM)       -->
        <!-- MENGHITUNG TOTAL JAM PELAJARAN, BUKAN HANYA ABSEN MASUK       -->
        <!-- ============================================================== -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Rekapitulasi Kehadiran Mata Pelajaran</h3>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Perhitungan total jam dan sesi mata pelajaran yang diikuti siswa di kelas pada bulan {{ formattedMonthLabel }}
              </p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shrink-0 self-start sm:self-auto">
              Total {{ kbmSummary?.total_sessions ?? 0 }} Sesi Mapel
            </span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- Jam Hadir Penuh di Kelas -->
            <div class="p-3.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 space-y-1.5">
              <span class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider block">Hadir di Kelas</span>
              <div class="flex items-baseline gap-1">
                <span class="text-xl sm:text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ kbmSummary?.hadir ?? 0 }}</span>
                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">Sesi</span>
              </div>
              <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">{{ kbmSummary?.hadir_percent ?? 100 }}% Mengikuti KBM</p>
            </div>

            <!-- Izin Keluar di Tengah Mapel (Kuning) -->
            <div class="p-3.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 space-y-1.5">
              <span class="text-[11px] font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider block">Izin Keluar Mapel</span>
              <div class="flex items-baseline gap-1">
                <span class="text-xl sm:text-2xl font-black text-amber-700 dark:text-amber-300">{{ kbmSummary?.izin_keluar ?? 0 }}</span>
                <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold">Sesi</span>
              </div>
              <p class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">Dispensasi / Keluar Kelas</p>
            </div>

            <!-- Sakit di Jam Mapel -->
            <div class="p-3.5 rounded-2xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/50 space-y-1.5">
              <span class="text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider block">Sakit saat KBM</span>
              <div class="flex items-baseline gap-1">
                <span class="text-xl sm:text-2xl font-black text-blue-700 dark:text-blue-300">{{ kbmSummary?.sakit ?? 0 }}</span>
                <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold">Sesi</span>
              </div>
              <p class="text-[10px] text-blue-600 dark:text-blue-400 font-medium">Diistirahatkan di UKS</p>
            </div>

            <!-- Tanpa Keterangan / Alfa di Jam Mapel -->
            <div class="p-3.5 rounded-2xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 space-y-1.5">
              <span class="text-[11px] font-bold text-rose-800 dark:text-rose-300 uppercase tracking-wider block">Tanpa Keterangan Mapel</span>
              <div class="flex items-baseline gap-1">
                <span class="text-xl sm:text-2xl font-black text-rose-700 dark:text-rose-300">{{ kbmSummary?.alpha ?? 0 }}</span>
                <span class="text-xs text-rose-600 dark:text-rose-400 font-semibold">Sesi</span>
              </div>
              <p class="text-[10px] text-rose-600 dark:text-rose-400 font-medium">Tidak Masuk Tanpa Izin</p>
            </div>
          </div>
        </div>

        <!-- Modern Attendance Analytics Dashboard -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-7 shadow-xs space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
              <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight">Statistik Kehadiran Bulanan</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ringkasan proporsi kehadiran siswa pada periode {{ formattedMonthLabel }}</p>
            </div>
            <div class="flex items-center gap-2">
              <MonthYearPicker v-model="selectedMonth" @change="fetchHistory" />
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <!-- Modern Segmented Donut / Radial Ring (5 Cols) -->
            <div class="lg:col-span-5 flex flex-col items-center justify-center p-4 bg-slate-50/70 dark:bg-slate-850 rounded-2xl border border-slate-100 dark:border-slate-800">
              <div class="relative w-48 h-48 sm:w-56 sm:h-56 flex items-center justify-center">
                <svg class="w-full h-full -rotate-90 drop-shadow-xs" viewBox="0 0 100 100">
                  <!-- Background Track -->
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke="currentColor"
                    class="text-slate-200 dark:text-slate-750"
                    stroke-width="9"
                  />
                  <!-- Hadir Segment -->
                  <circle
                    v-if="ringSegments.hadir > 0"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke="#059669"
                    stroke-width="9"
                    stroke-linecap="round"
                    :stroke-dasharray="`${ringSegments.hadir} ${100 - ringSegments.hadir}`"
                    :stroke-dashoffset="0"
                    class="transition-all duration-700"
                  />
                  <!-- Izin Segment -->
                  <circle
                    v-if="ringSegments.izin > 0"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke="#2563eb"
                    stroke-width="9"
                    stroke-linecap="round"
                    :stroke-dasharray="`${ringSegments.izin} ${100 - ringSegments.izin}`"
                    :stroke-dashoffset="`-${ringSegments.hadir}`"
                    class="transition-all duration-700"
                  />
                  <!-- Sakit Segment -->
                  <circle
                    v-if="ringSegments.sakit > 0"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke="#d97706"
                    stroke-width="9"
                    stroke-linecap="round"
                    :stroke-dasharray="`${ringSegments.sakit} ${100 - ringSegments.sakit}`"
                    :stroke-dashoffset="`-${ringSegments.hadir + ringSegments.izin}`"
                    class="transition-all duration-700"
                  />
                  <!-- Alfa Segment -->
                  <circle
                    v-if="ringSegments.alpha > 0"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke="#e11d48"
                    stroke-width="9"
                    stroke-linecap="round"
                    :stroke-dasharray="`${ringSegments.alpha} ${100 - ringSegments.alpha}`"
                    :stroke-dashoffset="`-${ringSegments.hadir + ringSegments.izin + ringSegments.sakit}`"
                    class="transition-all duration-700"
                  />
                </svg>

                <!-- Center Metric -->
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center select-none pointer-events-none">
                  <span class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-none">
                    {{ summary?.hadir_percent ?? 0 }}%
                  </span>
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1.5 bg-white dark:bg-slate-800 px-2.5 py-0.5 rounded-full border border-slate-200/70 dark:border-slate-700 shadow-2xs">
                    Kehadiran
                  </span>
                </div>
              </div>

              <div class="mt-4 text-center">
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Total Pembelajaran: {{ summary?.total ?? 0 }} Hari</p>
              </div>
            </div>

            <!-- Breakdown Detail Cards (7 Cols) -->
            <div class="lg:col-span-7 flex flex-col justify-between space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Hadir Card -->
                <div class="p-3.5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-900 dark:text-emerald-300">
                      Hadir
                    </span>
                    <span class="text-xs font-extrabold text-emerald-800 dark:text-emerald-200">{{ summary?.hadir ?? 0 }} Hari</span>
                  </div>
                  <div class="w-full bg-emerald-200/50 dark:bg-emerald-900/50 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-emerald-600 h-1.5 rounded-full" :style="{ width: `${ringSegments.hadir}%` }"></div>
                  </div>
                  <p class="text-[10px] text-emerald-700/80 dark:text-emerald-400/80 font-medium text-right">{{ ringSegments.hadir.toFixed(1) }}% dari total</p>
                </div>

                <!-- Izin Card -->
                <div class="p-3.5 rounded-2xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/50 space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-900 dark:text-blue-300">
                      Izin
                    </span>
                    <span class="text-xs font-extrabold text-blue-800 dark:text-blue-200">{{ summary?.izin ?? 0 }} Hari</span>
                  </div>
                  <div class="w-full bg-blue-200/50 dark:bg-blue-900/50 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-blue-600 h-1.5 rounded-full" :style="{ width: `${ringSegments.izin}%` }"></div>
                  </div>
                  <p class="text-[10px] text-blue-700/80 dark:text-blue-400/80 font-medium text-right">{{ ringSegments.izin.toFixed(1) }}% dari total</p>
                </div>

                <!-- Sakit Card -->
                <div class="p-3.5 rounded-2xl bg-amber-50/50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-amber-900 dark:text-amber-300">
                      Sakit
                    </span>
                    <span class="text-xs font-extrabold text-amber-800 dark:text-amber-200">{{ summary?.sakit ?? 0 }} Hari</span>
                  </div>
                  <div class="w-full bg-amber-200/50 dark:bg-amber-900/50 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-amber-500 h-1.5 rounded-full" :style="{ width: `${ringSegments.sakit}%` }"></div>
                  </div>
                  <p class="text-[10px] text-amber-700/80 dark:text-amber-400/80 font-medium text-right">{{ ringSegments.sakit.toFixed(1) }}% dari total</p>
                </div>

                <!-- Alfa Card -->
                <div class="p-3.5 rounded-2xl bg-rose-50/50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-rose-900 dark:text-rose-300">
                      Alfa
                    </span>
                    <span class="text-xs font-extrabold text-rose-800 dark:text-rose-200">{{ summary?.alpha ?? 0 }} Hari</span>
                  </div>
                  <div class="w-full bg-rose-200/50 dark:bg-rose-900/50 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-rose-500 h-1.5 rounded-full" :style="{ width: `${ringSegments.alpha}%` }"></div>
                  </div>
                  <p class="text-[10px] text-rose-700/80 dark:text-rose-400/80 font-medium text-right">{{ ringSegments.alpha.toFixed(1) }}% dari total</p>
                </div>
              </div>

              <!-- Weekly Attendance Modern Pill Bar Visual -->
              <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800 space-y-3">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Distribusi Kehadiran per Minggu</span>
                <div class="flex items-end justify-between gap-3 h-24 pt-2">
                  <div
                    v-for="(w, i) in weeklyData"
                    :key="i"
                    class="flex-1 flex flex-col items-center gap-1.5 group"
                  >
                    <span class="text-[11px] font-extrabold text-slate-800 dark:text-slate-200">{{ w.count }} hr</span>
                    <div class="w-full max-w-[48px] bg-slate-200 dark:bg-slate-750 rounded-xl h-14 flex items-end overflow-hidden p-0.5">
                      <div
                        class="w-full bg-gradient-to-t from-emerald-600 to-teal-400 rounded-lg transition-all duration-500 group-hover:from-emerald-500 group-hover:to-teal-300"
                        :style="{ height: `${w.height}%`, minHeight: w.count > 0 ? '8px' : '0' }"
                      ></div>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ w.week }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Riwayat Kehadiran Terperinci Table -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">Catatan Kehadiran Harian</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ filteredDetails.length }} hari tercatat pada periode ini</p>
            </div>
            <!-- Modern Segmented Status Pills (No native grey mobile popup) -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full">
              <button
                type="button"
                @click="statusFilter = 'ALL'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0 shadow-2xs border"
                :class="statusFilter === 'ALL' ? 'bg-primary text-white border-primary shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750'"
              >
                Semua
              </button>
              <button
                type="button"
                @click="statusFilter = 'HADIR'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0 shadow-2xs border"
                :class="statusFilter === 'HADIR' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750'"
              >
                Hadir
              </button>
              <button
                type="button"
                @click="statusFilter = 'SAKIT'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0 shadow-2xs border"
                :class="statusFilter === 'SAKIT' ? 'bg-amber-500 text-white border-amber-500 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750'"
              >
                Sakit
              </button>
              <button
                type="button"
                @click="statusFilter = 'IZIN'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0 shadow-2xs border"
                :class="statusFilter === 'IZIN' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750'"
              >
                Izin
              </button>
              <button
                type="button"
                @click="statusFilter = 'ALPHA'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0 shadow-2xs border"
                :class="statusFilter === 'ALPHA' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750'"
              >
                Alfa
              </button>
            </div>
          </div>

          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3">
            <div
              v-for="item in filteredDetails"
              :key="item.id"
              @click="selectRowDate(item.raw_date)"
              class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-750 p-4 shadow-2xs space-y-2.5 cursor-pointer transition active:scale-[0.99]"
              :class="{ 'border-primary ring-2 ring-primary/20 bg-blue-50/30': selectedKbmDate === item.raw_date }" 
            >
              <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-900 dark:text-white">{{ item.day_date }}</span>
                <span v-if="item.status === 'IZIN_KELUAR'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700 shadow-2xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  <span>Izin Keluar Mapel</span>
                </span>
                <StatusBadge v-else :status="item.status" />
              </div>

              <div class="text-xs text-slate-600 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-750">
                <div class="flex items-start justify-between gap-2">
                  <span class="text-slate-400 dark:text-slate-500 shrink-0">Keterangan:</span>
                  <div class="text-right">
                    <span v-if="item.status === 'IZIN_KELUAR'" class="font-bold text-amber-800 dark:text-amber-300">
                      {{ item.note || '-' }}
                    </span>
                    <span v-else class="font-medium text-slate-800 dark:text-slate-200">
                      {{ item.note || '-' }}
                    </span>
                  </div>
                </div>
                <!-- Mini Badges for Mobile -->
                <div v-if="item.kbm_leaves?.length || item.kbm_alfa?.length" class="mt-2 flex flex-col gap-1.5 pt-1 border-t border-dashed border-slate-200 dark:border-slate-700">
                  <span
                    v-for="(kl, ki) in item.kbm_leaves"
                    :key="'mkl-' + ki"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300"
                  >
                    <ClockIcon class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" />
                    <span>Izin Keluar {{ kl.leave_time }} ({{ kl.subject }}): "{{ kl.reason }}"</span>
                  </span>
                  <span
                    v-for="(ka, ai) in item.kbm_alfa"
                    :key="'mka-' + ai"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300"
                  >
                    <AlertTriangleIcon class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 shrink-0" />
                    <span>Alfa Mapel ({{ ka.subject }})</span>
                  </span>
                </div>
              </div>
            </div>
            <div v-if="filteredDetails.length === 0" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
              Tidak ada catatan presensi untuk filter ini.
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[650px]">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                  <th class="py-3.5 px-5">Hari & Tanggal</th>
                  <th class="py-3.5 px-5">Jam Belajar</th>
                  <th class="py-3.5 px-5 text-center">Status</th>
                  <th class="py-3.5 px-5">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr
                  v-for="item in filteredDetails"
                  :key="item.id"
                  @click="selectRowDate(item.raw_date)"
                  class="cursor-pointer transition hover:bg-blue-50/50 dark:hover:bg-slate-800/70"
                  :class="{ 'bg-blue-50/70 dark:bg-blue-950/40 ring-1 ring-primary/40': selectedKbmDate === item.raw_date }"
                  title="Klik baris ini untuk melihat rincian jam pelajaran pada tanggal ini"
                >
                  <td class="py-4 px-5 font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">{{ item.day_date }}</td>
                  <td class="py-4 px-5 text-slate-500 dark:text-slate-400 font-mono text-xs">{{ item.hours || '07:30 - 15:30' }}</td>
                  <td class="py-4 px-5 text-center whitespace-nowrap">
                    <span v-if="item.status === 'IZIN_KELUAR'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700 shadow-2xs">
                      <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                      <span>Izin Keluar Mapel</span>
                    </span>
                    <StatusBadge v-else :status="item.status" />
                  </td>
                  <td class="py-4 px-5 text-slate-700 dark:text-slate-300 font-medium leading-relaxed">
                    <div v-if="item.status === 'IZIN_KELUAR'" class="text-amber-900 dark:text-amber-200 font-bold flex items-center gap-1.5">
                      <ClockIcon class="w-4 h-4 text-amber-600 shrink-0" />
                      <span>{{ item.note || '-' }}</span>
                    </div>
                    <div v-else>{{ item.note || '-' }}</div>
                    <!-- Mini Badges for Desktop -->
                    <div v-if="item.kbm_leaves?.length || item.kbm_alfa?.length" class="mt-1.5 flex flex-wrap gap-1.5">
                      <span
                        v-for="(kl, ki) in item.kbm_leaves"
                        :key="'dkl-' + ki"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[11px] font-bold bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300"
                        :title="kl.reason"
                      >
                        <ClockIcon class="w-3 h-3 text-amber-600 dark:text-amber-400 shrink-0" />
                        <span>Izin Keluar {{ kl.leave_time }} ({{ kl.subject }}): "{{ kl.reason }}"</span>
                      </span>
                      <span
                        v-for="(ka, ai) in item.kbm_alfa"
                        :key="'dka-' + ai"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[11px] font-bold bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300"
                      >
                        <AlertTriangleIcon class="w-3 h-3 text-rose-600 dark:text-rose-400 shrink-0" />
                        <span>Alfa Mapel ({{ ka.subject }})</span>
                      </span>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredDetails.length === 0">
                  <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                    Tidak ada catatan presensi untuk filter ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- CARD PRESENSI MATA PELAJARAN (PER JAM KBM) DENGAN PEMILIH TANGGAL -->
        <!-- DITEMPATKAN DI BAWAH CATATAN KEHADIRAN HARIAN SESUAI PERMINTAAN  -->
        <!-- ============================================================== -->
        <div id="kbm-periods-card" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-7 shadow-xs space-y-5 transition-all">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></span>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight">
                  Presensi Mata Pelajaran
                </h3>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Pantauan status kehadiran siswa di setiap jam pelajaran pada:
                <strong class="text-primary font-bold ml-1">{{ selectedKbmDateFormatted || selectedKbmDate }}</strong>
              </p>
            </div>

            <!-- Kontrol Navigasi & Pemilih Tanggal KBM (Bisa Lihat Per Tanggal / Hari / Bulan) -->
            <div class="flex flex-wrap items-center gap-2 bg-slate-50/80 dark:bg-slate-850 p-1.5 rounded-2xl border border-slate-200/70 dark:border-slate-750">
              <button
                type="button"
                @click="changeKbmDay(-1)"
                class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition shadow-2xs cursor-pointer"
                title="Hari Sebelumnya"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>

              <div class="flex items-center gap-1.5 px-2">
                <CalendarIcon class="w-3.5 h-3.5 text-primary shrink-0" />
                <input
                  type="date"
                  v-model="selectedKbmDate"
                  @change="fetchKbmForSelectedDate"
                  class="bg-transparent text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none cursor-pointer"
                  title="Pilih Tanggal Bebas"
                />
              </div>

              <button
                type="button"
                @click="changeKbmDay(1)"
                class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition shadow-2xs cursor-pointer"
                title="Hari Berikutnya"
              >
                <ChevronRight class="w-4 h-4" />
              </button>

              <button
                type="button"
                @click="setKbmToday"
                class="px-3 py-1.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition shadow-2xs cursor-pointer ml-1"
              >
                Hari Ini
              </button>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="loadingKbm" class="py-12 flex flex-col items-center justify-center gap-3 text-slate-400">
            <RefreshCwIcon class="w-6 h-6 animate-spin text-primary" />
            <span class="text-xs font-medium">Memuat rincian jam pelajaran KBM...</span>
          </div>

          <!-- Weekend notice -->
          <div v-else-if="isKbmWeekend" class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-855/60 border border-slate-200/60 dark:border-slate-750 text-center space-y-1">
            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Libur Akhir Pekan</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Tidak ada kegiatan pembelajaran tatap muka pada hari Sabtu dan Minggu.</p>
          </div>

          <!-- Grid Sesi KBM pada Tanggal Terpilih -->
          <div v-else-if="todayKbmPeriods.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <div
              v-for="(session, sIdx) in todayKbmPeriods"
              :key="'kbm-period-' + sIdx"
              class="p-4 rounded-2xl border transition flex flex-col justify-between gap-3 shadow-2xs"
              :class="[
                session.student_status === 'IZIN'
                  ? 'bg-amber-50/70 dark:bg-amber-950/30 border-amber-300 dark:border-amber-800/70'
                  : session.student_status === 'ALPHA'
                  ? 'bg-rose-50/70 dark:bg-rose-950/30 border-rose-300 dark:border-rose-800/70'
                  : session.student_status === 'HADIR'
                  ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/50'
                  : 'bg-slate-50/60 dark:bg-slate-800/40 border-slate-200/80 dark:border-slate-750'
              ]"
            >
              <div class="space-y-2">
                <div class="flex items-center justify-between gap-2">
                  <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs font-mono">
                    {{ session.jam_ke }} • {{ session.time }}
                  </span>
                  <!-- Badge Status Siswa di Mapel Ini -->
                  <span
                    v-if="session.student_status === 'IZIN'"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500 text-white shadow-2xs"
                  >
                    <ClockIcon class="w-3.5 h-3.5" />
                    <span>Izin Keluar: {{ session.leave_time || 'Saat KBM' }}</span>
                  </span>
                  <span
                    v-else-if="session.student_status === 'ALPHA'"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-rose-600 text-white shadow-2xs"
                  >
                    <AlertTriangleIcon class="w-3.5 h-3.5" />
                    <span>ALFA di Mapel Ini</span>
                  </span>
                  <span
                    v-else-if="session.student_status === 'HADIR'"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-600 text-white shadow-2xs"
                  >
                    <CheckCircle2Icon class="w-3.5 h-3.5" />
                    <span>Hadir di Kelas</span>
                  </span>
                  <span
                    v-else-if="session.student_status === 'JAM_KOSONG'"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-gray-200 dark:bg-slate-750 text-slate-700 dark:text-slate-300"
                  >
                    <span>Jam Kosong</span>
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-200/80 dark:bg-slate-750 text-slate-600 dark:text-slate-400"
                  >
                    <ClockIcon class="w-3.5 h-3.5 text-slate-400" />
                    <span>Menunggu Sesi</span>
                  </span>
                </div>

                <div>
                  <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ session.subject }}</h4>
                  <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 flex items-center gap-1.5">
                    <span>Guru: <strong>{{ session.teacher }}</strong></span>
                    <span class="text-slate-400 dark:text-slate-500">• {{ session.room }}</span>
                  </p>
                </div>
              </div>

              <!-- Detail Keterangan jika Izin Keluar atau Alfa -->
              <div
                v-if="session.student_status === 'IZIN' && session.reason"
                class="p-2.5 rounded-xl bg-amber-100/70 dark:bg-amber-950/60 text-amber-950 dark:text-amber-200 text-xs leading-relaxed border border-amber-200 dark:border-amber-800/50"
              >
                <strong>Keterangan Izin:</strong> "{{ session.reason }}"
              </div>
              <div
                v-else-if="session.student_status === 'ALPHA'"
                class="p-2.5 rounded-xl bg-rose-100/70 dark:bg-rose-950/60 text-rose-950 dark:text-rose-200 text-xs leading-relaxed border border-rose-200 dark:border-rose-800/50"
              >
                <strong>Peringatan:</strong> Siswa dicatat tidak berada di kelas pada sesi pembelajaran ini.
              </div>
            </div>
          </div>

          <div v-else class="py-8 text-center text-xs text-slate-400 dark:text-slate-500">
            Tidak ada jadwal KBM yang tercatat pada tanggal {{ selectedKbmDateFormatted || selectedKbmDate }}.
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- CARD DAFTAR RIWAYAT IZIN KELUAR JAM PELAJARAN (DISPENSASI KBM) -->
        <!-- KHUSUS MENAMPILKAN KELUAR JAM BERAPA, MAPEL APA, & ALASANNYA   -->
        <!-- ============================================================== -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-7 shadow-xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Daftar Izin Keluar Jam Pelajaran</h3>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pantauan lengkap waktu keluar kelas, mata pelajaran yang ditinggalkan, dan alasan izin pada periode {{ formattedMonthLabel }}
              </p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 shrink-0 self-start sm:self-auto">
              {{ kbmLeaveLogs.length }} Kali Izin Keluar
            </span>
          </div>

          <!-- Empty State -->
          <div v-if="kbmLeaveLogs.length === 0" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
            <CheckCircle2Icon class="w-8 h-8 text-emerald-500 mx-auto mb-2 opacity-80" />
            <p class="font-semibold text-slate-700 dark:text-slate-300">Tidak ada riwayat izin keluar kelas pada periode ini</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Siswa mengikuti seluruh jam pelajaran di kelas dengan tertib.</p>
          </div>

          <!-- Table of KBM Leaves -->
          <div v-else class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-amber-50/50 dark:bg-slate-850 border-b border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                  <th class="py-3 px-4">Hari & Tanggal</th>
                  <th class="py-3 px-4 text-amber-800 dark:text-amber-300">Jam Keluar</th>
                  <th class="py-3 px-4">Mata Pelajaran & Guru</th>
                  <th class="py-3 px-4">Alasan & Keperluan</th>
                  <th class="py-3 px-4 text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-for="(log, idx) in kbmLeaveLogs" :key="idx" class="hover:bg-amber-50/30 dark:hover:bg-slate-800/40 transition">
                  <td class="py-3 px-4 font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">{{ log.date }}</td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1 font-bold text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-950/60 px-2.5 py-1 rounded-lg border border-amber-300 dark:border-amber-700">
                      <ClockIcon class="w-3.5 h-3.5 text-amber-600 shrink-0" />
                      <span>Pukul {{ log.leave_time }}</span>
                    </span>
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ log.subject }}</div>
                    <div class="text-[11px] text-slate-400">{{ log.teacher }} • {{ log.schedule_time }}</div>
                  </td>
                  <td class="py-3 px-4 text-slate-700 dark:text-slate-300 font-medium italic">
                    "{{ log.reason }}"
                  </td>
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                      Disetujui
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: SURAT IZIN & PERIZINAN                                  -->
      <!-- ============================================================== -->
      <div v-else class="space-y-6">
        <!-- Success Alert -->
        <div v-if="successMsg" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
          <div class="flex items-center gap-2 text-xs sm:text-sm font-medium">
            <CheckCircle2Icon class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span>{{ successMsg }}</span>
          </div>
          <button @click="successMsg = ''" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 text-sm cursor-pointer p-1"><X class="w-4 h-4" /></button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Form Input (Kiri) - Tampil untuk Siswa / Sekretaris -->
          <div
            v-if="authStore.user?.role !== 'ORANG_TUA'"
            class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 md:p-6 space-y-4"
          >
            <div class="border-b border-slate-100 dark:border-slate-800 pb-4 mb-1">
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Mail class="w-4 h-4 text-primary" />
                <span>Pengajuan Surat Izin</span>
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ leaveForm.scope === 'HOURLY' ? 'Izin jam pelajaran langsung tercatat otomatis — guru mapel diberi tahu' : 'Surat permohonan langsung diteruskan ke Wali Kelas' }}</p>
            </div>

            <!-- Banner Notifikasi: Jika siswa sudah memiliki izin yang DISETUJUI hari ini -->
            <div
              v-if="activeApprovedLeaveToday"
              class="p-3 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-300 flex items-center gap-2 shadow-2xs"
            >
              <CheckCircle2Icon class="w-4 h-4 text-primary shrink-0" />
              <span>Izin aktif disetujui: <strong>{{ activeApprovedLeaveToday.type }}</strong> ({{ activeApprovedLeaveToday.start_date }} s/d {{ activeApprovedLeaveToday.end_date }})</span>
            </div>

            <!-- Banner Kunci Form (Anti-Kecurangan: Siswa Sudah Hadir atau Melewati Batas Toleransi Jam Masuk) -->
            <div
              v-if="formLockReason"
              class="p-4 rounded-2xl border space-y-2.5 shadow-2xs"
              :class="formLockReason.type === 'HADIR' ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800 text-amber-950 dark:text-amber-200' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-950 dark:text-rose-200'"
            >
              <div class="flex items-center gap-2 font-bold text-xs sm:text-sm">
                <AlertCircle class="w-4 h-4 shrink-0" :class="formLockReason.type === 'HADIR' ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'" />
                <span>{{ formLockReason.title }}</span>
              </div>
              <p class="text-xs leading-relaxed opacity-90">
                {{ formLockReason.message }}
              </p>
              <div class="pt-1">
                <button
                  type="button"
                  @click="setTomorrow"
                  class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 border text-xs font-bold transition hover:shadow-xs flex items-center gap-1.5 cursor-pointer"
                  :class="formLockReason.type === 'HADIR' ? 'border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 hover:bg-amber-100/50' : 'border-rose-300 dark:border-rose-700 text-rose-800 dark:text-rose-200 hover:bg-rose-100/50'"
                >
                  <CalendarIcon class="w-3.5 h-3.5" />
                  <span>Ajukan Izin untuk Besok</span>
                </button>
              </div>
            </div>

            <form @submit.prevent="handleSubmitLeave" class="space-y-4">
              <!-- Informasi Pemohon -->
              <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-850 border border-slate-200/80 dark:border-slate-800 space-y-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Informasi Siswa</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs pt-1">
                  <div>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 block font-medium">Nama</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100 truncate block">{{ authStore.user?.name || 'Siswa' }}</span>
                  </div>
                  <div>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 block font-medium">NISN</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100 font-mono">{{ authStore.user?.nip_nis || '-' }}</span>
                  </div>
                  <div>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 block font-medium">Kelas</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ authStore.user?.school_class?.name || '-' }}</span>
                  </div>
                </div>
              </div>

              <!-- Ruang Lingkup Izin: Sehari Penuh VS Jam Pelajaran -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                  Cakupan Izin
                </label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    @click="setLeaveScope('FULL_DAY')"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold border transition text-center cursor-pointer flex items-center justify-center gap-1.5',
                      leaveForm.scope === 'FULL_DAY'
                        ? 'bg-primary/10 border-primary text-primary dark:text-blue-300 shadow-2xs font-extrabold'
                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50'
                    ]"
                  >
                    <span>Izin Sehari Penuh</span>
                  </button>
                  <button
                    type="button"
                    @click="setLeaveScope('HOURLY')"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold border transition text-center cursor-pointer flex items-center justify-center gap-1.5',
                      leaveForm.scope === 'HOURLY'
                        ? 'bg-amber-500/10 border-amber-500 text-amber-800 dark:text-amber-300 shadow-2xs font-extrabold'
                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50'
                    ]"
                  >
                    <ClockIcon class="w-3.5 h-3.5" />
                    <span>Izin Jam Pelajaran</span>
                  </button>
                </div>
              </div>

              <!-- Input Khusus jika Izin Jam Pelajaran -->
              <div v-if="leaveForm.scope === 'HOURLY'" class="p-3.5 sm:p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 space-y-3.5">
                <div class="flex items-center justify-between gap-2">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300 block">
                    Detail Jam & Mata Pelajaran yang Ditinggalkan
                  </span>
                  <span v-if="todayKbmPeriods.length > 0" class="text-[10px] text-amber-700 dark:text-amber-300 font-bold bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 rounded-full shrink-0">
                    {{ todayKbmPeriods.length }} Sesi Terjadwal
                  </span>
                </div>

                <!-- Auto-Detect Jadwal Pelajaran Hari Itu -->
                <div v-if="todayKbmPeriods.length > 0" class="p-3 bg-white dark:bg-slate-800/90 rounded-xl border border-amber-200/80 dark:border-amber-800/50 space-y-2.5 shadow-2xs">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                      <ClockIcon class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                      <span>Pilih Cepat Berdasarkan Jadwal Kelas</span>
                    </span>
                    <button
                      v-if="kbmStartPeriod !== null"
                      type="button"
                      @click="resetKbmSelection"
                      class="text-[10px] text-amber-700 dark:text-amber-400 hover:underline cursor-pointer"
                    >
                      Reset Pilihan
                    </button>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                      <label class="block text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Mulai Dari Jam Pelajaran
                      </label>
                      <select
                        v-model="kbmStartPeriod"
                        @change="handleKbmPeriodChange"
                        class="w-full h-9 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-medium bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/20 cursor-pointer"
                      >
                        <option :value="null">-- Pilih Jam Mulai --</option>
                        <option
                          v-for="(p, pIdx) in todayKbmPeriods"
                          :key="'start-p-' + pIdx"
                          :value="pIdx"
                        >
                          {{ p.jam_ke }}: {{ p.subject }} ({{ p.time }}) • {{ p.teacher }}
                        </option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Sampai Jam Pelajaran
                      </label>
                      <select
                        v-model="kbmEndPeriod"
                        @change="handleKbmPeriodChange"
                        :disabled="kbmStartPeriod === null"
                        class="w-full h-9 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-medium bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/20 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <option :value="null">-- Pilih Jam Selesai --</option>
                        <option
                          v-for="p in availableEndPeriods"
                          :key="'end-p-' + p.index"
                          :value="p.index"
                        >
                          {{ p.index === todayKbmPeriods.length - 1 ? 'Hingga Pulang: ' : 'Hingga Selesai ' }}{{ p.jam_ke }} • {{ p.subject }} (Pukul {{ p.endTime }})
                        </option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">
                      Rentang Jam Keluar
                    </label>
                    <input
                      v-model="leaveForm.time_range"
                      type="text"
                      placeholder="Contoh: 09:45 - 11:30"
                      class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-medium bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">
                      Mata Pelajaran
                    </label>
                    <input
                      v-model="leaveForm.subject_name"
                      type="text"
                      placeholder="Contoh: Matematika, Fisika"
                      class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-medium bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                    />
                  </div>
                </div>
              </div>

              <!-- Jenis Izin -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                  Jenis Izin
                </label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    @click="leaveForm.type = 'SAKIT'"
                    :class="[
                      'py-2.5 rounded-xl text-xs font-bold border transition text-center cursor-pointer',
                      leaveForm.type === 'SAKIT'
                        ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-300 shadow-2xs'
                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-750'
                    ]"
                  >
                    Sakit
                  </button>
                  <button
                    type="button"
                    @click="leaveForm.type = 'IZIN'"
                    :class="[
                      'py-2.5 rounded-xl text-xs font-bold border transition text-center cursor-pointer',
                      leaveForm.type === 'IZIN'
                        ? 'bg-blue-50 dark:bg-blue-950/40 border-blue-300 dark:border-blue-700 text-blue-800 dark:text-blue-300 shadow-2xs'
                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-750'
                    ]"
                  >
                    Izin
                  </button>
                </div>
              </div>

              <!-- Rentang Tanggal -->
              <!-- Kasus A: Izin Jam Pelajaran (HOURLY) -> Hanya 1 Tanggal Selesai di Hari yang Sama -->
              <div v-if="leaveForm.scope === 'HOURLY'">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                  Tanggal Izin
                </label>
                <input
                  v-model="leaveForm.start_date"
                  :min="minDate"
                  type="date"
                  lang="id-ID"
                  @input="leaveFormTouched = true"
                  class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800 shadow-2xs"
                  required
                />
                <p class="mt-1.5 text-[11px] text-amber-700 dark:text-amber-400 font-medium flex items-center gap-1">
                  <ClockIcon class="w-3.5 h-3.5 shrink-0" />
                  <span>Izin jam pelajaran otomatis berlaku dan selesai di hari yang sama.</span>
                </p>
              </div>

              <!-- Kasus B: Izin Sehari Penuh (FULL_DAY) -> Rentang Tanggal Mulai & Selesai -->
              <div v-else class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    Tanggal Mulai
                  </label>
                  <input
                    v-model="leaveForm.start_date"
                    :min="minDate"
                    type="date"
                    lang="id-ID"
                    @input="leaveFormTouched = true"
                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800 shadow-2xs"
                    required
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    Tanggal Selesai
                  </label>
                  <input
                    v-model="leaveForm.end_date"
                    :min="leaveForm.start_date || minDate"
                    type="date"
                    lang="id-ID"
                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800 shadow-2xs"
                    required
                  />
                </div>
              </div>

              <!-- Peringatan Jika Tanggal Sudah Memiliki Izin Disetujui / Pending -->
              <div
                v-if="conflictingLeave"
                class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-300 space-y-1"
              >
                <div class="flex items-center gap-1.5 font-bold text-amber-800 dark:text-amber-200">
                  <AlertCircle class="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400" />
                  <span>Tanggal Sudah Terdaftar: {{ conflictingLeave.status === 'APPROVED' ? 'Disetujui' : 'Menunggu Konfirmasi' }}</span>
                </div>
                <p class="leading-relaxed text-amber-800 dark:text-amber-300">
                  Anda sudah memiliki permohonan surat izin untuk tanggal {{ conflictingLeave.start_date }} s/d {{ conflictingLeave.end_date }}.
                </p>
              </div>

              <!-- Alasan Izin -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                  Alasan Izin
                </label>
                <textarea
                  v-model="leaveForm.reason"
                  rows="3"
                  placeholder="Contoh: Mengalami demam tinggi dan disarankan beristirahat..."
                  class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition resize-none bg-white dark:bg-slate-800 shadow-2xs"
                  required
                ></textarea>
              </div>

              <button
                type="submit"
                :disabled="submittingLeave || !!conflictingLeave || isFormLocked"
                class="w-full py-3 px-4 rounded-xl bg-primary hover:bg-primary-hover active:scale-[0.99] text-white font-bold text-xs transition shadow-xs flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer min-h-[44px]"
              >
                <SendIcon class="w-4 h-4" />
                <span>
                  {{
                    isFormLocked
                      ? (formLockReason?.type === 'HADIR' ? 'Form Dikunci (Sudah Hadir)' : 'Form Dikunci (Lewat Batas Jam)')
                      : conflictingLeave
                      ? (conflictingLeave.status === 'APPROVED' ? 'Izin Sudah Disetujui' : 'Izin Sedang Diproses')
                      : (submittingLeave ? 'Mengirimkan...' : 'Kirim Permohonan ke Wali Kelas')
                  }}
                </span>
              </button>
            </form>
          </div>

          <!-- Riwayat Surat Izin (Kanan / Full untuk Orang Tua) -->
          <div
            :class="[
              authStore.user?.role === 'ORANG_TUA' ? 'lg:col-span-12' : 'lg:col-span-7',
              'bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 md:p-6 space-y-4'
            ]"
          >
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
              <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                  {{ subTabIzin === 'walas' ? 'Daftar Surat Izin' : 'Log Izin Keluar Jam Mapel' }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ subTabIzin === 'walas' ? 'Status verifikasi permohonan izin oleh Wali Kelas' : 'Catatan riwayat izin keluar saat KBM berlangsung oleh Guru Pengajar' }}
                </p>
              </div>
              <button
                @click="subTabIzin === 'walas' ? fetchLeaves() : fetchHistory()"
                class="text-xs text-primary dark:text-blue-400 hover:underline font-bold flex items-center gap-1 cursor-pointer"
              >
                <RefreshCwIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': loadingLeaves }" /> Segarkan
              </button>
            </div>

            <!-- Sub-Nav Switcher: Surat Izin Harian (Walas) VS Log Izin Keluar Mapel -->
            <div class="flex items-center gap-2 p-1.5 bg-slate-100/80 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 rounded-2xl">
              <button
                type="button"
                @click="subTabIzin = 'walas'"
                class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5"
                :class="subTabIzin === 'walas' ? 'bg-white dark:bg-slate-700 text-primary dark:text-white shadow-2xs border border-gray-200/60 dark:border-slate-600' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
              >
                <Mail class="w-3.5 h-3.5" />
                <span>Surat Izin Harian</span>
                <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">
                  {{ leaveList.length }}
                </span>
              </button>
              <button
                type="button"
                @click="subTabIzin = 'kbm'"
                class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5"
                :class="subTabIzin === 'kbm' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs border border-gray-200/60 dark:border-slate-600' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
              >
                <ClockIcon class="w-3.5 h-3.5 text-amber-500" />
                <span>Log Izin Keluar Mapel</span>
                <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300">
                  {{ kbmLeaveLogs.length }}
                </span>
              </button>
            </div>

            <!-- SUB-TAB 1: SURAT IZIN HARIAN (WALAS) -->
            <div v-if="subTabIzin === 'walas'" class="space-y-4">
              <!-- Filter Tanggal & Status (Modern Touch UI, zero native mobile select popup) -->
              <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 p-3 rounded-2xl bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-xs shadow-2xs">
                <div class="flex items-center gap-2 flex-1 min-w-[160px] px-3 h-11 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-2xs hover:border-primary/40 focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary transition">
                  <CalendarIcon class="w-4 h-4 text-primary shrink-0" />
                  <input
                    type="date"
                    lang="id-ID"
                    v-model="leaveFilterDate"
                    class="w-full border-none bg-transparent text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none cursor-pointer"
                  />
                </div>
                <div class="flex items-center gap-1 overflow-x-auto p-1 bg-slate-100/80 dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 rounded-xl shrink-0">
                  <button
                    type="button"
                    @click="leaveFilterStatus = 'ALL'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                    :class="leaveFilterStatus === 'ALL' ? 'bg-white dark:bg-slate-700 text-primary dark:text-white font-bold shadow-2xs border border-gray-200/60 dark:border-slate-600' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                  >
                    Semua
                  </button>
                  <button
                    type="button"
                    @click="leaveFilterStatus = 'PENDING'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                    :class="leaveFilterStatus === 'PENDING' ? 'bg-amber-500 text-white font-extrabold shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                  >
                    Menunggu
                  </button>
                  <button
                    type="button"
                    @click="leaveFilterStatus = 'APPROVED'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                    :class="leaveFilterStatus === 'APPROVED' ? 'bg-emerald-600 text-white font-extrabold shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                  >
                    Disetujui
                  </button>
                  <button
                    type="button"
                    @click="leaveFilterStatus = 'REJECTED'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                    :class="leaveFilterStatus === 'REJECTED' ? 'bg-rose-600 text-white font-extrabold shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                  >
                    Ditolak
                  </button>
                </div>
                <button
                  v-if="leaveFilterDate || leaveFilterStatus !== 'ALL'"
                  type="button"
                  @click="leaveFilterDate = ''; leaveFilterStatus = 'ALL'"
                  class="text-xs text-primary dark:text-blue-400 hover:underline font-bold cursor-pointer px-2"
                >
                  Reset
                </button>
              </div>

              <div v-if="loadingLeaves" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                Memuat data permohonan...
              </div>

              <div v-else-if="filteredLeaveList.length === 0" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                Belum ada permohonan surat izin yang sesuai filter.
              </div>

              <div v-else class="space-y-3 max-h-[580px] overflow-y-auto pr-1">
                <div
                  v-for="lv in filteredLeaveList"
                  :key="lv.id"
                  class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-855 shadow-2xs space-y-2.5 transition hover:border-slate-300 dark:hover:border-slate-700"
                >
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ lv.student_name }}</span>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">NIS: {{ lv.nis }}</span>
                      </div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Tipe: <span class="font-bold text-slate-800 dark:text-slate-200">{{ lv.type }}</span> • {{ lv.start_date }} s/d {{ lv.end_date }}
                      </div>
                    </div>
                    <StatusBadge :status="lv.status" context="pembimbing" />
                  </div>

                  <div class="p-3 bg-slate-50 dark:bg-slate-800/80 rounded-xl border border-slate-100 dark:border-slate-750 text-xs text-slate-700 dark:text-slate-300">
                    <p class="italic leading-relaxed text-slate-800 dark:text-slate-200">"{{ lv.reason }}"</p>
                  </div>

                  <div v-if="lv.review_note" class="text-[11px] text-slate-600 dark:text-slate-400 font-medium pt-1 border-t border-slate-100 dark:border-slate-750">
                    Catatan Wali Kelas: "{{ lv.review_note }}"
                  </div>
                </div>
              </div>
            </div>

            <!-- SUB-TAB 2: LOG IZIN KELUAR JAM MAPEL (GURU PENGAJAR) -->
            <div v-else class="space-y-4">
              <!-- Filter & Search KBM Logs -->
              <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <div class="flex-1 flex items-center gap-2 p-2.5 rounded-2xl bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-xs shadow-2xs">
                  <SearchIcon class="w-4 h-4 text-slate-400 shrink-0 ml-1.5" />
                  <input
                    v-model="logFilterSearch"
                    type="text"
                    placeholder="Cari mata pelajaran, nama guru, atau alasan..."
                    class="w-full bg-transparent border-none text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none"
                  />
                  <button
                    v-if="logFilterSearch"
                    type="button"
                    @click="logFilterSearch = ''"
                    class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer mr-1"
                  >
                    <X class="w-3.5 h-3.5" />
                  </button>
                </div>

                <div v-if="uniqueKbmSubjects.length > 0" class="shrink-0">
                  <select
                    v-model="logFilterSubject"
                    class="w-full sm:w-auto h-full px-3 py-2.5 rounded-2xl bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 font-medium focus:outline-none cursor-pointer shadow-2xs"
                  >
                    <option value="">Semua Mapel</option>
                    <option v-for="subj in uniqueKbmSubjects" :key="subj" :value="subj">{{ subj }}</option>
                  </select>
                </div>
              </div>

              <div v-if="filteredKbmLogs.length === 0" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                {{ (logFilterSearch || logFilterSubject) ? 'Tidak ada riwayat izin keluar KBM yang cocok dengan filter.' : 'Belum ada catatan riwayat izin keluar pada jam mata pelajaran.' }}
              </div>

              <div v-else class="space-y-3 max-h-[580px] overflow-y-auto pr-1">
                <div
                  v-for="log in filteredKbmLogs"
                  :key="log.id"
                  class="p-4 rounded-2xl border border-amber-200/70 dark:border-amber-900/50 bg-amber-50/25 dark:bg-slate-850 shadow-2xs space-y-2.5 transition hover:border-amber-300 dark:hover:border-amber-700"
                >
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ log.subject }}</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">• {{ log.teacher }}</span>
                      </div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ log.date }} • Jam: <span class="font-mono text-slate-700 dark:text-slate-300">{{ log.schedule_time }}</span>
                      </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-extrabold bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-300/60 dark:border-amber-800 flex items-center gap-1 shrink-0">
                      <ClockIcon class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                      <span>Keluar: {{ log.leave_time }}</span>
                    </span>
                  </div>

                  <div class="p-3 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-100 dark:border-slate-750 text-xs text-slate-700 dark:text-slate-300">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-0.5">Keperluan / Keterangan:</span>
                    <p class="italic leading-relaxed text-slate-800 dark:text-slate-200">"{{ log.reason }}"</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import {
  CalendarCheck,
  Calendar as CalendarIcon,
  Mail,
  Clock as ClockIcon,
  AlertTriangle as AlertTriangleIcon,
  Search as SearchIcon,
  Download as DownloadIcon,
  RefreshCw as RefreshCwIcon,
  CheckCircle2 as CheckCircle2Icon,
  Send as SendIcon,
  AlertCircle,
  X,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useToastStore } from '~/stores/toast'

const config = useRuntimeConfig()
const authStore = useAuthStore()
const toast = useToastStore()
const route = useRoute()

const getLocalDateStr = (d: Date = new Date()): string => {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Tab state: default to 'presensi', or 'izin' if passed via query ?tab=izin
const activeMainTab = ref<'presensi' | 'izin'>((route.query.tab as string) === 'izin' ? 'izin' : 'presensi')
// Sub-tab di dalam tab Izin: 'walas' (Surat Izin Harian) vs 'kbm' (Log Izin Keluar Jam Mapel)
const subTabIzin = ref<'walas' | 'kbm'>('walas')

const summary = ref<any>(null)
const details = ref<any[]>([])
const weeklyRaw = ref<any[]>([])
const todayStatus = ref<string | null>(null)
const isPastCutoff = ref<boolean>(false)
const cutoffTime = ref<string>('07:15')
const todayKbmLeaves = ref<any[]>([])
const todayKbmAlfa = ref<any[]>([])
const todayKbmPeriods = ref<any[]>([])
const serverTodayStr = ref('')
const selectedKbmDate = ref('')
const selectedKbmDateFormatted = ref('')
const isKbmWeekend = ref(false)
const loadingKbm = ref(false)

const changeKbmDay = (offset: number) => {
  const current = selectedKbmDate.value || serverTodayStr.value || getLocalDateStr()
  const [y, m, dNum] = current.split('-').map(Number)
  const dt = new Date(y, m - 1, dNum)
  dt.setDate(dt.getDate() + offset)
  const yyyy = dt.getFullYear()
  const mm = String(dt.getMonth() + 1).padStart(2, '0')
  const dd = String(dt.getDate()).padStart(2, '0')
  selectedKbmDate.value = `${yyyy}-${mm}-${dd}`
  fetchKbmForSelectedDate()
}

const setKbmToday = () => {
  selectedKbmDate.value = ''
  fetchKbmForSelectedDate()
}

const selectRowDate = (rawDate: string) => {
  if (!rawDate) return
  selectedKbmDate.value = rawDate
  fetchKbmForSelectedDate()
  nextTick(() => {
    const el = document.getElementById('kbm-periods-card')
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  })
}

const fetchKbmForSelectedDate = async () => {
  loadingKbm.value = true
  try {
    const studentQuery = route.query.student_id ? `&student_id=${route.query.student_id}` : ''
    const kbmParam = selectedKbmDate.value ? `&kbm_date=${selectedKbmDate.value}` : ''
    const res = await $fetch<any>(`${authStore.apiBase}/attendance/history?month=${selectedMonth.value}${studentQuery}${kbmParam}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    todayKbmPeriods.value = res.today_kbm_periods || []
    selectedKbmDateFormatted.value = res.selected_kbm_date_formatted || ''
    isKbmWeekend.value = !!res.is_kbm_weekend
    if (res.server_date) {
      serverTodayStr.value = res.server_date
      if (!leaveFormTouched.value) {
        leaveForm.value.start_date = res.server_date
        leaveForm.value.end_date = res.server_date
      }
    }
    if (!selectedKbmDate.value && res.selected_kbm_date) {
      selectedKbmDate.value = res.selected_kbm_date
    }
  } catch (e) {
    console.error('Failed to fetch KBM for date:', e)
  } finally {
    loadingKbm.value = false
  }
}
const kbmLeaveLogs = ref<any[]>([])
const kbmSummary = ref<any>(null)
const logFilterSearch = ref('')
const logFilterSubject = ref('')

const uniqueKbmSubjects = computed(() => {
  const subjects = kbmLeaveLogs.value.map(l => l.subject).filter(Boolean)
  return Array.from(new Set(subjects))
})

const now = new Date()
const currentYearMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const selectedMonth = ref(currentYearMonth)
const statusFilter = ref('ALL')

// Leave requests state
const leaveList = ref<any[]>([])
const loadingLeaves = ref(false)
const submittingLeave = ref(false)
const successMsg = ref('')
const leaveFilterDate = ref('')
const leaveFilterStatus = ref('ALL')

const initialDate = getLocalDateStr()

const leaveForm = ref({
  type: 'SAKIT',
  scope: 'FULL_DAY', // 'FULL_DAY' or 'HOURLY'
  time_range: '',
  subject_name: '',
  start_date: initialDate,
  end_date: initialDate,
  recipient: 'Wali Kelas',
  reason: ''
})

const leaveFormTouched = ref(false)

const kbmStartPeriod = ref<number | null>(null)
const kbmEndPeriod = ref<number | null>(null)

const availableEndPeriods = computed(() => {
  if (kbmStartPeriod.value === null || !todayKbmPeriods.value.length) return []
  return todayKbmPeriods.value
    .map((p, idx) => {
      const parts = (p.time || '').split('-')
      const endTime = parts[1] ? parts[1].trim() : ''
      return {
        index: idx,
        jam_ke: p.jam_ke,
        subject: p.subject,
        teacher: p.teacher,
        endTime
      }
    })
    .filter(p => p.index >= (kbmStartPeriod.value as number))
})

const handleKbmPeriodChange = () => {
  if (kbmStartPeriod.value === null) return
  if (kbmEndPeriod.value === null || kbmEndPeriod.value < kbmStartPeriod.value) {
    kbmEndPeriod.value = kbmStartPeriod.value
  }

  const startP = todayKbmPeriods.value[kbmStartPeriod.value]
  const endP = todayKbmPeriods.value[kbmEndPeriod.value]
  if (!startP || !endP) return

  const startTime = (startP.time || '').split('-')[0]?.trim() || ''
  const endTime = (endP.time || '').split('-')[1]?.trim() || ''
  if (startTime && endTime) {
    leaveForm.value.time_range = `${startTime} - ${endTime}`
  }

  const selectedList = todayKbmPeriods.value.slice(kbmStartPeriod.value, kbmEndPeriod.value + 1)
  const uniqueSubjects = Array.from(new Set(selectedList.map((p: any) => p.subject).filter(Boolean)))
  leaveForm.value.subject_name = uniqueSubjects.join(', ')
}

const resetKbmSelection = () => {
  kbmStartPeriod.value = null
  kbmEndPeriod.value = null
  leaveForm.value.time_range = ''
  leaveForm.value.subject_name = ''
}

const setLeaveScope = (scope: 'FULL_DAY' | 'HOURLY') => {
  leaveForm.value.scope = scope
  if (scope === 'HOURLY') {
    const today = todayStr.value
    leaveForm.value.start_date = today
    leaveForm.value.end_date = today
    resetKbmSelection()
  }
}

watch(() => leaveForm.value.start_date, (newDate) => {
  if (leaveForm.value.scope === 'HOURLY') {
    leaveForm.value.end_date = newDate
    resetKbmSelection()
    if (newDate && newDate !== selectedKbmDate.value) {
      selectedKbmDate.value = newDate
      fetchKbmForSelectedDate()
    }
  } else if (leaveForm.value.end_date && leaveForm.value.end_date < newDate) {
    leaveForm.value.end_date = newDate
  }
})

const pageTitle = computed(() => {
  if (authStore.user?.role === 'ORANG_TUA') {
    return activeMainTab.value === 'izin' ? 'Surat Izin Ananda' : 'Kehadiran Ananda'
  }
  return activeMainTab.value === 'izin' ? 'Surat Izin' : 'Kehadiran Siswa'
})

const pageSubtitle = computed(() => {
  if (authStore.user?.role === 'ORANG_TUA') {
    return 'Pantauan kehadiran harian ananda di sekolah dan riwayat permohonan izin'
  }
  return 'Laporan rekap kehadiran pribadi dan formulir permohonan izin ke Wali Kelas'
})

const hadirDaysText = computed(() => `${summary.value?.hadir ?? 0} Hari`)
const hadirPctText = computed(() => `${summary.value?.hadir_percent ?? 0}% Kehadiran`)
const sakitDaysText = computed(() => `${summary.value?.sakit ?? 0} Hari`)
const izinDaysText = computed(() => `${summary.value?.izin ?? 0} Hari`)
const alphaDaysText = computed(() => `${summary.value?.alpha ?? 0} Hari`)

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
]

const formattedMonthLabel = computed(() => {
  if (!selectedMonth.value) return 'Bulan Ini'
  const [y, m] = selectedMonth.value.split('-').map(Number)
  const name = monthNames[m - 1] || 'Bulan'
  return `${name} ${y}`
})

const filteredDetails = computed(() => {
  if (statusFilter.value === 'ALL') return details.value
  return details.value.filter(d => d.status === statusFilter.value)
})

const filteredKbmLogs = computed(() => {
  let list = kbmLeaveLogs.value
  if (logFilterSubject.value) {
    list = list.filter(item => item.subject === logFilterSubject.value)
  }
  if (logFilterSearch.value.trim()) {
    const q = logFilterSearch.value.toLowerCase()
    list = list.filter(item =>
      (item.subject && item.subject.toLowerCase().includes(q)) ||
      (item.teacher && item.teacher.toLowerCase().includes(q)) ||
      (item.date && item.date.toLowerCase().includes(q)) ||
      (item.reason && item.reason.toLowerCase().includes(q))
    )
  }
  return list
})

// Segment percentages for the modern circular chart
const ringSegments = computed(() => {
  const total = (summary.value?.hadir ?? 0) + (summary.value?.izin ?? 0) + (summary.value?.sakit ?? 0) + (summary.value?.alpha ?? 0)
  if (total === 0) {
    return { hadir: 100, izin: 0, sakit: 0, alpha: 0 }
  }
  return {
    hadir: ((summary.value?.hadir ?? 0) / total) * 100,
    izin: ((summary.value?.izin ?? 0) / total) * 100,
    sakit: ((summary.value?.sakit ?? 0) / total) * 100,
    alpha: ((summary.value?.alpha ?? 0) / total) * 100,
  }
})

const weeklyData = computed(() => {
  if (!weeklyRaw.value || weeklyRaw.value.length === 0) {
    return [
      { week: 'Minggu 1', height: 0, count: 0 },
      { week: 'Minggu 2', height: 0, count: 0 },
      { week: 'Minggu 3', height: 0, count: 0 },
      { week: 'Minggu 4', height: 0, count: 0 },
    ]
  }
  return weeklyRaw.value.map(w => ({
    week: w.week,
    count: w.count ?? 0,
    height: Math.min(100, Math.round(((w.count ?? 0) / 5) * 100))
  }))
})

const todayStr = computed(() => serverTodayStr.value || getLocalDateStr())

// Tanggal minimal di date picker (Proactive Locking UX)
const minDate = computed(() => {
  // Akun Orang Tua bebas mengunggah surat izin susulan (misal sakit kemarin)
  if (authStore.user?.role === 'ORANG_TUA') {
    return undefined
  }
  // Izin jam pelajaran (Dispensasi KBM) diperbolehkan hari ini meski sudah hadir
  if (leaveForm.value.scope === 'HOURLY') {
    return todayStr.value
  }
  // Jika siswa dan izin seharian terkunci hari ini (sudah hadir atau lewat cutoff 07:15)
  if (todayStatus.value === 'HADIR' || isPastCutoff.value) {
    const d = new Date()
    d.setDate(d.getDate() + 1)
    return getLocalDateStr(d)
  }
  return todayStr.value
})

// Logika Penguncian Form Izin (Anti-Kecurangan)
const formLockReason = computed(() => {
  if (!leaveForm.value.start_date) return null
  const sDate = leaveForm.value.start_date
  const today = todayStr.value

  // Izin jam pelajaran (Dispensasi KBM) TIDAK DIKUNCI karena diperuntukkan bagi siswa yang hadir di sekolah
  if (leaveForm.value.scope === 'HOURLY') {
    return null
  }

  // Pengecekan hanya jika memilih tanggal hari ini
  if (sDate === today) {
    // Kondisi 1: Siswa sudah tercatat HADIR di sekolah hari ini
    if (todayStatus.value === 'HADIR') {
      return {
        type: 'HADIR',
        title: 'Formulir Dikunci: Anda Sudah Hadir di Sekolah',
        message: 'Status kehadiran Anda hari ini telah tercatat HADIR di sekolah. Pengajuan surat izin harian tidak dapat dilakukan. Jika ada keperluan keluar kelas atau pulang awal, silakan pilih opsi "Izin Jam Pelajaran" atau lapor langsung ke Guru Pengajar / Wali Kelas.'
      }
    }

    // Kondisi 2: Siswa belum hadir dan jam sudah melewati batas masuk sekolah (pukul 07:15 WIB)
    // HANYA UNTUK SISWA DAN SEKRETARIS. Akun ORANG_TUA tidak dibatasi jam 07:15!
    if (authStore.user?.role !== 'ORANG_TUA' && isPastCutoff.value) {
      return {
        type: 'LATE',
        title: 'Batas Waktu Pengajuan Mandiri Berakhir',
        message: `Batas waktu pengajuan izin mandiri hari ini telah berakhir (pukul ${cutoffTime.value} WIB). Status Anda telah tercatat ALFA. Untuk izin mendadak, Orang Tua dapat mengajukan surat izin resmi melalui aplikasi ini atau menghubungi Wali Kelas.`
      }
    }
  }

  return null
})

const isFormLocked = computed(() => !!formLockReason.value)

// Tombol bantuan: Cepat ajukan untuk besok
const setTomorrow = () => {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  const tomorrowStr = getLocalDateStr(d)
  leaveForm.value.start_date = tomorrowStr
  leaveForm.value.end_date = tomorrowStr
}

const activeApprovedLeaveToday = computed(() => {
  return leaveList.value.find(lv =>
    lv.status === 'APPROVED' &&
    lv.start_date <= todayStr.value &&
    lv.end_date >= todayStr.value
  )
})

const conflictingLeave = computed(() => {
  if (!leaveForm.value.start_date || !leaveForm.value.end_date) return null
  const s1 = leaveForm.value.start_date
  const e1 = leaveForm.value.end_date
  return leaveList.value.find(lv => {
    if (lv.status !== 'APPROVED' && lv.status !== 'PENDING') return false
    const s2 = lv.start_date
    const e2 = lv.end_date
    const datesOverlap = (s1 <= e2 && e1 >= s2)
    if (!datesOverlap) return false
    // Jika keduanya izin jam pelajaran (HOURLY), izinkan pengajuan jam mapel berbeda
    const isThisHourly = leaveForm.value.scope === 'HOURLY'
    const isOtherHourly = (lv.reason && lv.reason.includes('[Dispensasi Jam'))
    if (isThisHourly && isOtherHourly) return false
    return true
  })
})

const filteredLeaveList = computed(() => {
  return leaveList.value.filter(lv => {
    const matchStatus = leaveFilterStatus.value === 'ALL' || lv.status === leaveFilterStatus.value
    let matchDate = true
    if (leaveFilterDate.value) {
      matchDate = (leaveFilterDate.value >= lv.start_date && leaveFilterDate.value <= lv.end_date) || lv.start_date === leaveFilterDate.value
    }
    return matchStatus && matchDate
  })
})

const fetchHistory = async () => {
  try {
    const studentQuery = route.query.student_id ? `&student_id=${route.query.student_id}` : ''
    const kbmParam = selectedKbmDate.value ? `&kbm_date=${selectedKbmDate.value}` : ''
    const res = await $fetch<any>(`${authStore.apiBase}/attendance/history?month=${selectedMonth.value}${studentQuery}${kbmParam}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    summary.value = res.summary
    kbmSummary.value = res.kbm_summary || null
    details.value = res.details || []
    weeklyRaw.value = res.weekly || []
    todayStatus.value = res.today_status || null
    isPastCutoff.value = !!res.is_past_cutoff
    cutoffTime.value = res.cutoff_time || '07:15'
    todayKbmLeaves.value = res.today_kbm_leaves || []
    todayKbmAlfa.value = res.today_kbm_alfa || []
    todayKbmPeriods.value = res.today_kbm_periods || []
    selectedKbmDateFormatted.value = res.selected_kbm_date_formatted || ''
    isKbmWeekend.value = !!res.is_kbm_weekend
    kbmLeaveLogs.value = res.kbm_leave_logs || []
    if (res.server_date) {
      serverTodayStr.value = res.server_date
      if (!leaveFormTouched.value) {
        leaveForm.value.start_date = res.server_date
        leaveForm.value.end_date = res.server_date
      }
    }
    if (!selectedKbmDate.value && res.selected_kbm_date) {
      selectedKbmDate.value = res.selected_kbm_date
    }
  } catch (e) {
    console.error(e)
  }
}

const fetchLeaves = async () => {
  loadingLeaves.value = true
  try {
    const studentQuery = route.query.student_id ? `?student_id=${route.query.student_id}` : ''
    const res: any = await $fetch(`${authStore.apiBase}/leave-requests${studentQuery}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    leaveList.value = res || []
  } catch (err: any) {
    console.error('Error fetching leaves:', err)
  } finally {
    loadingLeaves.value = false
  }
}

const handleSubmitLeave = async () => {
  if (conflictingLeave.value) {
    toast.error('Anda sudah memiliki surat izin pada tanggal tersebut.')
    return
  }

  submittingLeave.value = true
  successMsg.value = ''
  try {
    let studentIdNum = Number(authStore.user?.id || 1)
    if (authStore.user?.role === 'ORANG_TUA') {
      const childId = route.query.student_id || authStore.user?.student_id
      if (childId) {
        studentIdNum = Number(childId)
      }
    }
    await $fetch(`${authStore.apiBase}/leave-requests`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        ...leaveForm.value,
        leave_scope: leaveForm.value.scope,
        time_range: leaveForm.value.time_range,
        subject_name: leaveForm.value.subject_name,
        student_id: studentIdNum
      }
    })
    successMsg.value = leaveForm.value.scope === 'HOURLY'
      ? 'Izin jam pelajaran otomatis disetujui & tercatat. Guru mapel yang sesinya ditinggal telah diberi tahu.'
      : 'Permohonan surat izin berhasil dikirimkan ke Wali Kelas.'
    leaveForm.value.reason = ''
    leaveForm.value.time_range = ''
    leaveForm.value.subject_name = ''
    resetKbmSelection()
    await fetchLeaves()
    await fetchHistory()
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal mengajukan surat izin')
  } finally {
    submittingLeave.value = false
  }
}

const exportCsv = () => {
  if (!filteredDetails.value || filteredDetails.value.length === 0) {
    toast.warning('Tidak ada data kehadiran untuk diekspor.')
    return
  }
  const headers = ['No', 'Hari/Tanggal', 'Jam Belajar', 'Status', 'Keterangan']
  const rows = filteredDetails.value.map((item, idx) => [
    idx + 1,
    `"${item.day_date}"`,
    `"${item.hours || '07:30 - 15:30'}"`,
    item.status,
    `"${(item.note || '-').replace(/"/g, '""')}"`
  ])
  const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `riwayat_kehadiran_${authStore.user?.name || 'siswa'}_${selectedMonth.value}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

onMounted(() => {
  fetchHistory()
  fetchLeaves()
})
</script>
