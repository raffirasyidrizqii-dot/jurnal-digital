<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      :title="authStore.user?.role === 'GURU_PENGAJAR' ? 'Rekap Kehadiran Mata Pelajaran' : 'Rekapitulasi Presensi Semester'"
      :subtitle="authStore.user?.role === 'GURU_PENGAJAR' ? 'Laporan kumulatif kehadiran siswa pada sesi pembelajaran mapel per semester' : 'Laporan kumulatif kehadiran dan persentase kehadiran siswa per semester'"
    >
      <template #actions>
        <button
          @click="exportExcel"
          type="button"
          class="p-2 sm:px-3.5 sm:py-2 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-200 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-slate-700 transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0 min-h-[34px] min-w-[34px] sm:min-h-[40px]"
          title="Ekspor Excel"
        >
          <FileSpreadsheetIcon class="w-3.5 h-3.5 text-green-600 dark:text-green-400 shrink-0" />
          <span class="hidden sm:inline">Ekspor Excel</span>
        </button>
        <button
          @click="exportWord"
          type="button"
          class="p-2 sm:px-3.5 sm:py-2 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-200 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-slate-700 transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0 min-h-[34px] min-w-[34px] sm:min-h-[40px]"
          title="Ekspor Dokumen Word"
        >
          <FileTextIcon class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400 shrink-0" />
          <span class="hidden sm:inline">Ekspor Word</span>
        </button>
        <button
          @click="printPdf"
          type="button"
          class="p-2 sm:px-3.5 sm:py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0 min-h-[34px] min-w-[34px] sm:min-h-[40px]"
          title="Cetak PDF"
        >
          <PrinterIcon class="w-3.5 h-3.5 shrink-0" />
          <span class="hidden sm:inline">Cetak PDF</span>
        </button>
      </template>
    </AppHeader>

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Print Official Header (Hanya tampil saat dicetak) -->
      <div class="hidden print:block text-center border-b-2 border-black pb-3 mb-4">
        <h1 class="text-sm font-bold uppercase tracking-wide text-black">Pemerintah Provinsi Daerah Khusus Ibukota Jakarta</h1>
        <h2 class="text-base font-extrabold uppercase text-black">Dinas Pendidikan • {{ appConfig.school_name }}</h2>
        <p class="text-[11px] text-gray-700">{{ appConfig.school_address || 'Jl. Raden Inten II No. 71, Duren Sawit, Jakarta Timur' }} • NPSN: {{ appConfig.school_npsn || '20104567' }}</p>
        <div class="mt-2 pt-2 border-t border-black">
          <h3 class="text-sm font-bold uppercase underline text-black">Laporan Rekapitulasi Presensi Semester Siswa</h3>
          <p class="text-xs font-semibold text-black mt-0.5">
            Kelas: {{ currentClassName }} • 
            <span v-if="filterMode === 'kbm'">Mata Pelajaran: {{ selectedSubjectName }} • </span>
            Semester: {{ filterTerm === '2026-1' ? '2026/2027 Ganjil' : filterTerm }} • 
            {{ filterMode === 'kbm' ? 'Total Sesi KBM' : 'Hari Efektif' }}: {{ effectiveDays }} {{ filterMode === 'kbm' ? 'Sesi' : 'Hari' }}
          </p>
        </div>
      </div>

      <!-- Mode Switcher: Presensi Harian Kelas vs Presensi Mata Pelajaran (KBM) (Khusus Walas / Superadmin) -->
      <div v-if="authStore.user?.role !== 'GURU_PENGAJAR'" class="flex items-center gap-2 p-1.5 rounded-2xl bg-gray-100 dark:bg-slate-800/80 w-full sm:w-fit print:hidden shadow-2xs">
        <button
          type="button"
          @click="setMode('daily')"
          class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 cursor-pointer shadow-2xs"
          :class="filterMode === 'daily' ? 'bg-white dark:bg-slate-900 text-primary dark:text-blue-400 shadow-xs' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100'"
        >
          <CalendarIcon class="w-4 h-4" />
          <span>Presensi Harian Kelas</span>
        </button>
        <button
          type="button"
          @click="setMode('kbm')"
          class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 cursor-pointer shadow-2xs"
          :class="filterMode === 'kbm' ? 'bg-white dark:bg-slate-900 text-primary dark:text-blue-400 shadow-xs' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100'"
        >
          <BookOpenIcon class="w-4 h-4" />
          <span>Presensi Mata Pelajaran</span>
        </button>
      </div>

      <!-- Filter & Selectors -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs p-5 sm:p-6 flex flex-wrap items-center justify-between gap-5 print:hidden">
        <div class="flex flex-wrap items-center gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-2">Tahun Ajaran & Semester</label>
            <select
              v-model="filterTerm"
              @change="fetchData"
              class="h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-xs sm:text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white cursor-pointer shadow-2xs transition"
            >
              <option value="2026-1">2026/2027 • Semester Ganjil</option>
              <option value="2025-2">2025/2026 • Semester Genap</option>
              <option value="2025-1">2025/2026 • Semester Ganjil</option>
            </select>
          </div>

          <!-- Selector Kelas: Hanya kelas yang diajar oleh guru -->
          <div v-if="isClassLocked || (authStore.user?.role === 'GURU_PENGAJAR' && classOptions.length <= 1)">
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-2">Kelas</label>
            <div class="h-11 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 shadow-2xs flex items-center gap-2 whitespace-nowrap">
              <span>Kelas {{ currentClassName }}</span>
            </div>
          </div>
          <div v-else>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-2">Pilih Kelas</label>
            <select
              v-model="filterClass"
              @change="handleClassChange"
              class="h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-xs sm:text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white cursor-pointer shadow-2xs transition"
            >
              <option v-for="cls in classOptions" :key="cls.id" :value="String(cls.id)">
                Kelas {{ cls.name }}
              </option>
            </select>
          </div>

          <!-- Selector Mata Pelajaran (Khusus Mode KBM): Auto Detect Mapel Guru -->
          <div v-if="filterMode === 'kbm'">
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-2">Mata Pelajaran</label>
            
            <!-- Kasus 1: GURU_PENGAJAR dengan 1 Mapel (Auto Detect & Lock, Tidak bisa pilih mapel lain) -->
            <div
              v-if="authStore.user?.role === 'GURU_PENGAJAR' && teacherSubjects.length <= 1"
              class="h-11 px-4 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs sm:text-sm font-bold text-blue-900 dark:text-blue-200 shadow-2xs flex items-center gap-2 whitespace-nowrap"
            >
              <BookOpenIcon class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
              <span>{{ teacherSubjects[0]?.name || selectedSubjectName || 'Mata Pelajaran' }}</span>
              <span class="text-[10px] px-2 py-0.5 rounded-md bg-blue-200/80 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-extrabold ml-1">Auto-Detect</span>
            </div>

            <!-- Kasus 2: GURU_PENGAJAR dengan > 1 Mapel (Dropdown HANYA berisi mapel yang diampu guru ini) -->
            <select
              v-else-if="authStore.user?.role === 'GURU_PENGAJAR' && teacherSubjects.length > 1"
              v-model="filterSubject"
              @change="fetchData"
              class="h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-xs sm:text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white cursor-pointer shadow-2xs transition"
            >
              <option v-for="sb in teacherSubjects" :key="sb.id" :value="String(sb.id)">
                {{ sb.name }} ({{ sb.code || 'Mapel Anda' }})
              </option>
            </select>

            <!-- Kasus 3: Admin / Walas (Dapat memilih mapel dalam kelas atau Semua Mapel) -->
            <select
              v-else
              v-model="filterSubject"
              @change="fetchData"
              class="h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-xs sm:text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white cursor-pointer shadow-2xs transition"
            >
              <option value="">Semua Mata Pelajaran</option>
              <option v-for="sb in subjectOptions" :key="sb.id" :value="String(sb.id)">
                {{ sb.name }} ({{ sb.code || 'Mapel' }})
              </option>
            </select>
          </div>
        </div>

        <div class="w-full md:w-72">
          <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-2">Pencarian Siswa</label>
          <div class="relative">
            <SearchIcon class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Ketik nama atau NIS..."
              class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-xs sm:text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white shadow-2xs transition"
            />
          </div>
        </div>
      </div>

      <!-- Summary KPI cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 print:hidden">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
              {{ filterMode === 'kbm' ? 'Total Sesi KBM' : 'Hari Efektif' }}
            </span>
            <CalendarIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ effectiveDays }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
              {{ filterMode === 'kbm' ? 'Sesi Pertemuan' : 'Hari Pembelajaran' }}
            </span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
            {{ filterMode === 'kbm' ? 'Total sesi KBM terlaksana semester ini' : 'Total pertemuan terjadwal semester ini' }}
          </p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Rata-rata Kehadiran</span>
            <TrendingUpIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ avgAttendance }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Kehadiran Kelas</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">{{ targetNote }}</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Siswa Aktif</span>
            <UsersIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ students.length }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Siswa Terdaftar</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Rombongan belajar aktif</p>
        </div>
      </div>

      <!-- Chart Visualisasi Presensi Semester -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-7 print:hidden space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
          <div>
            <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight">Distribusi Presensi Semester</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Komposisi kehadiran, sakit, izin keluar, dan alfa seluruh siswa rombel</p>
          </div>
          <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
            <button
              v-for="t in ['pie', 'bar']"
              :key="t"
              type="button"
              @click="chartType = t as 'bar' | 'pie'"
              class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer"
              :class="chartType === t ? 'bg-primary text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-white'"
            >
              {{ t === 'pie' ? 'Ringkasan Donut' : 'Grafik Siswa' }}
            </button>
          </div>
        </div>

        <div v-if="loading" class="py-16 text-center text-slate-400 dark:text-slate-500 text-xs">
          Memuat grafik...
        </div>
        <div v-else-if="students.length === 0" class="py-14 text-center text-slate-400 dark:text-slate-500 text-xs">
          Belum ada data untuk divisualisasikan.
        </div>
        <div v-else>
          <!-- Modern Radial / Donut Visual -->
          <div v-if="chartType === 'pie'" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
            <div class="md:col-span-5 flex justify-center">
              <div class="relative w-48 h-48 sm:w-56 sm:h-56 flex items-center justify-center">
                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                  <circle cx="50" cy="50" r="40" fill="none" stroke="#e2e8f0" class="stroke-slate-200 dark:stroke-slate-700" stroke-width="8"/>
                  <circle
                    v-if="pieData.hadir > 0"
                    cx="50" cy="50" r="40" fill="none"
                    stroke="#059669"
                    stroke-width="8"
                    stroke-linecap="round"
                    :stroke-dasharray="`${pieData.hadir} ${100 - pieData.hadir}`"
                    stroke-dashoffset="0"
                    class="transition-all duration-700"
                  />
                  <circle
                    v-if="pieData.sakit > 0"
                    cx="50" cy="50" r="40" fill="none"
                    stroke="#d97706"
                    stroke-width="8"
                    stroke-linecap="round"
                    :stroke-dasharray="`${pieData.sakit} ${100 - pieData.sakit}`"
                    :stroke-dashoffset="`-${pieData.hadir}`"
                    class="transition-all duration-700"
                  />
                  <circle
                    v-if="pieData.izin > 0"
                    cx="50" cy="50" r="40" fill="none"
                    stroke="#2563eb"
                    stroke-width="8"
                    stroke-linecap="round"
                    :stroke-dasharray="`${pieData.izin} ${100 - pieData.izin}`"
                    :stroke-dashoffset="`-${pieData.hadir + pieData.sakit}`"
                    class="transition-all duration-700"
                  />
                  <circle
                    v-if="pieData.alpha > 0"
                    cx="50" cy="50" r="40" fill="none"
                    stroke="#e11d48"
                    stroke-width="8"
                    stroke-linecap="round"
                    :stroke-dasharray="`${pieData.alpha} ${100 - pieData.alpha}`"
                    :stroke-dashoffset="`-${pieData.hadir + pieData.sakit + pieData.izin}`"
                    class="transition-all duration-700"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center select-none pointer-events-none">
                  <span class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">{{ pieData.hadir.toFixed(0) }}%</span>
                  <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Kehadiran</span>
                </div>
              </div>
            </div>

            <div class="md:col-span-7 grid grid-cols-2 gap-3">
              <div v-for="leg in pieLegends" :key="leg.label" class="p-3.5 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-850 space-y-1.5">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                    {{ leg.label }}
                  </span>
                  <span class="text-xs font-mono font-extrabold text-slate-700 dark:text-slate-300">{{ leg.pct.toFixed(1) }}%</span>
                </div>
                <div class="w-full bg-slate-200/60 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                  <div class="h-1.5 rounded-full transition-all duration-500" :style="{ width: `${leg.pct}%`, background: leg.color }"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modern Bar Chart View -->
          <div v-else class="space-y-4">
            <div class="h-56 flex items-end justify-around gap-2 px-2 bg-slate-50/50 dark:bg-slate-850 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
              <div
                v-for="s in chartStudents"
                :key="s.name"
                class="flex-1 flex flex-col items-center gap-1 group"
              >
                <div class="w-full flex flex-col gap-0.5 items-center justify-end" style="height: 160px">
                  <div
                    v-if="s.alpha > 0"
                    :style="{ height: (s.alpha / effectiveDays * 100) + '%', minHeight: '4px' }"
                    class="w-3/4 rounded-t bg-rose-500 transition-all"
                    :title="`Alfa: ${s.alpha}`"
                  ></div>
                  <div
                    v-if="s.izin > 0"
                    :style="{ height: (s.izin / effectiveDays * 100) + '%', minHeight: '4px' }"
                    class="w-3/4 bg-blue-500 transition-all"
                    :title="`Izin: ${s.izin}`"
                  ></div>
                  <div
                    v-if="s.sakit > 0"
                    :style="{ height: (s.sakit / effectiveDays * 100) + '%', minHeight: '4px' }"
                    class="w-3/4 bg-amber-500 transition-all"
                    :title="`Sakit: ${s.sakit}`"
                  ></div>
                  <div
                    :style="{ height: (s.hadir / effectiveDays * 100) + '%', minHeight: '4px' }"
                    class="w-3/4 rounded-b bg-emerald-500 transition-all"
                    :title="`Hadir: ${s.hadir}`"
                  ></div>
                </div>
                <span class="text-[9px] text-slate-500 dark:text-slate-400 text-center leading-tight max-w-[48px] truncate font-medium">
                  {{ s.shortName }}
                </span>
              </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-4 pt-1">
              <span v-for="l in pieLegends" :key="l.label" class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400">
                <span class="w-3 h-1 rounded-full shrink-0" :style="{ background: l.color }"></span>
                <span>{{ l.label }}</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Table of Student Semester Recap -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-150 dark:border-slate-800 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-gray-800 dark:text-white">Daftar Kehadiran Kumulatif Siswa</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Rincian angka presensi Hadir, Sakit, Izin Keluar, ALFA per siswa</p>
          </div>
          <span class="text-xs text-gray-500 dark:text-slate-400 font-medium">{{ filteredStudents.length }} Siswa Ditampilkan</span>
        </div>

        <div v-if="loading" class="py-16 text-center text-gray-400 dark:text-slate-500 text-sm">
          Memuat rekapitulasi semester...
        </div>

        <div v-else>
          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3 p-4">
            <div
              v-for="(st, index) in filteredStudents"
              :key="st.id"
              class="bg-white dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs space-y-3"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-gray-400 dark:text-slate-500">#{{ index + 1 }}</span>
                    <span class="font-bold text-gray-900 dark:text-white text-sm">{{ st.name }}</span>
                  </div>
                  <span class="text-[11px] text-gray-500 dark:text-slate-400 font-mono">NIS: {{ st.nis }}</span>
                </div>
                <div class="text-right">
                  <span class="text-base font-extrabold text-gray-900 dark:text-white">{{ st.percentage }}</span>
                  <span class="text-[11px] text-gray-400 dark:text-slate-500 block font-medium">Kehadiran</span>
                </div>
              </div>

              <!-- Attendance Stats Grid -->
              <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="p-2 bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-800/50 rounded-xl">
                  <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block">Hadir</span>
                  <span class="font-bold text-emerald-800 dark:text-emerald-200 text-sm">{{ st.hadir }}</span>
                </div>
                <div class="p-2 bg-amber-50/70 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-800/50 rounded-xl">
                  <span class="text-[10px] text-amber-600 dark:text-amber-400 block">Sakit</span>
                  <span class="font-bold text-amber-800 dark:text-amber-200 text-sm">{{ st.sakit }}</span>
                </div>
                
                <!-- Izin Keluar (Clickable Modal Humanizer) -->
                <button
                  v-if="st.izin > 0"
                  @click="openLeaveDetail(st)"
                  type="button"
                  class="p-2 border rounded-xl bg-blue-50/70 hover:bg-blue-100 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800/50 text-center transition cursor-pointer"
                >
                  <span class="text-[10px] block font-semibold text-blue-700 dark:text-blue-400">
                    {{ filterMode === 'kbm' ? 'Izin Keluar' : 'Izin' }}
                  </span>
                  <span class="font-bold text-sm text-blue-900 dark:text-blue-200 flex items-center justify-center gap-1">
                    {{ st.izin }}x
                    <span class="text-[10px] underline font-normal text-blue-600 dark:text-blue-400">(Kapan?)</span>
                  </span>
                </button>
                <div v-else class="p-2 border border-slate-150 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-800/40 text-center">
                  <span class="text-[10px] block text-slate-400 dark:text-slate-500">
                    {{ filterMode === 'kbm' ? 'Izin Keluar' : 'Izin' }}
                  </span>
                  <span class="font-bold text-sm text-slate-400 dark:text-slate-500">0</span>
                </div>

                <div class="p-2 bg-rose-50/70 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-800/50 rounded-xl">
                  <span class="text-[10px] text-rose-600 dark:text-rose-400 block">ALFA</span>
                  <span class="font-bold text-rose-800 dark:text-rose-200 text-sm">{{ st.alpha }}</span>
                </div>
              </div>
            </div>
            <div v-if="filteredStudents.length === 0" class="py-12 text-center text-gray-400 dark:text-slate-500 text-sm">
              Tidak ada siswa yang cocok dengan pencarian.
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[850px]">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                  <th class="py-3.5 px-5 w-14 text-center whitespace-nowrap">No</th>
                  <th class="py-3.5 px-5 whitespace-nowrap">Nama Siswa</th>
                  <th class="py-3.5 px-5 whitespace-nowrap">NIS / NISN</th>
                  <th class="py-3.5 px-5 text-center text-emerald-700 dark:text-emerald-400 whitespace-nowrap">Hadir</th>
                  <th class="py-3.5 px-5 text-center text-amber-700 dark:text-amber-400 whitespace-nowrap">Sakit</th>
                  <th class="py-3.5 px-5 text-center text-blue-700 dark:text-blue-400 whitespace-nowrap">
                    {{ filterMode === 'kbm' ? 'Izin Keluar' : 'Izin' }}
                  </th>
                  <th class="py-3.5 px-5 text-center text-rose-700 dark:text-rose-400 whitespace-nowrap">ALFA</th>
                  <th class="py-3.5 px-5 text-center whitespace-nowrap">Persentase</th>
                  <th class="py-3.5 px-5 text-center whitespace-nowrap">Status Ujian</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <tr
                  v-for="(st, index) in filteredStudents"
                  :key="st.id"
                  class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition"
                >
                  <td class="py-4 px-5 text-center font-medium text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ index + 1 }}</td>
                  <td class="py-4 px-5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-slate-100">{{ st.name }}</div>
                  </td>
                  <td class="py-4 px-5 text-gray-500 dark:text-slate-400 font-mono text-xs whitespace-nowrap">{{ st.nis }}</td>
                  <td class="py-4 px-5 text-center font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">{{ st.hadir }}</td>
                  <td class="py-4 px-5 text-center font-semibold text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ st.sakit }}</td>
                  
                  <!-- Izin Keluar (Clickable Modal Humanizer) -->
                  <td class="py-4 px-5 text-center whitespace-nowrap">
                    <button
                      v-if="st.izin > 0"
                      @click="openLeaveDetail(st)"
                      type="button"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/50 dark:hover:bg-blue-900/50 border border-blue-200 dark:border-blue-800/60 text-blue-700 dark:text-blue-300 font-bold text-xs transition cursor-pointer shadow-2xs group"
                      title="Klik untuk melihat riwayat waktu dan keterangan izin siswa"
                    >
                      <span>{{ st.izin }} Keluar</span>
                      <InfoIcon class="w-3.5 h-3.5 text-blue-500 group-hover:scale-110 transition shrink-0" />
                    </button>
                    <span v-else class="font-semibold text-slate-400 dark:text-slate-500">0</span>
                  </td>

                  <td class="py-4 px-5 text-center whitespace-nowrap">
                    <span
                      v-if="st.alpha > 0"
                      class="px-2 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-700 dark:text-rose-300 font-bold"
                    >
                      {{ st.alpha }}
                    </span>
                    <span v-else class="font-semibold text-rose-600 dark:text-rose-400">{{ st.alpha }}</span>
                  </td>
                  <td class="py-4 px-5 text-center whitespace-nowrap">
                    <span class="font-bold text-gray-900 dark:text-slate-100">{{ st.percentage }}</span>
                  </td>
                  <td class="py-4 px-5 text-center whitespace-nowrap">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border"
                      :class="parseFloat(st.percentage) >= 75 ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/50'"
                    >
                      {{ parseFloat(st.percentage) >= 75 ? 'Memenuhi Syarat' : 'Di Bawah 75%' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="filteredStudents.length === 0">
                  <td colspan="9" class="py-12 text-center text-gray-400 dark:text-slate-500 text-sm">
                    Tidak ada siswa yang cocok dengan pencarian.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Area Tanda Tangan Resmi (Tampil Khusus Cetak) -->
        <div class="hidden print:flex justify-between items-start pt-8 px-6 text-xs text-gray-900">
          <div class="text-center w-56">
            <p>Mengetahui,</p>
            <p class="font-bold">Kepala {{ appConfig.school_name }}</p>
            <div class="h-20"></div>
            <p class="font-bold underline">{{ appConfig.headmaster_name || 'Kepala Sekolah' }}</p>
            <p class="text-[11px] text-gray-600 font-mono">NIP. {{ appConfig.headmaster_nip || '-' }}</p>
          </div>
          <div class="text-center w-56">
            <p>Jakarta, {{ printDate }}</p>
            <p class="font-bold">Guru Pengampu / Wali Kelas</p>
            <div class="h-20"></div>
            <p class="font-bold underline">{{ teacherOrWalasName }}</p>
            <p class="text-[11px] text-gray-600 font-mono">NIP. {{ teacherOrWalasNip }}</p>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Humanizer Detail Izin Keluar Siswa -->
    <Teleport to="body">
      <div
        v-if="isLeaveModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
        @click.self="closeLeaveModal"
      >
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full border border-gray-150 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
          <!-- Modal Header -->
          <div class="px-6 py-5 border-b border-gray-150 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-850/50">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-blue-100 dark:bg-blue-950/60 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold shrink-0">
                <ClockIcon class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                  Riwayat Izin Keluar Kelas
                </h3>
                <p class="text-xs text-gray-500 dark:text-slate-400">
                  {{ selectedStudentLeave?.name }} • NIS: {{ selectedStudentLeave?.nis || '-' }} • Kelas {{ currentClassName }}
                </p>
              </div>
            </div>
            <button
              @click="closeLeaveModal"
              type="button"
              class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer"
            >
              <XIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Modal Body (Timeline & Humanizer) -->
          <div class="p-6 overflow-y-auto space-y-4">
            <!-- Summary Banner -->
            <div class="p-4 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/50 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <InfoIcon class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                <span class="text-xs font-medium text-blue-900 dark:text-blue-200">
                  Total akumulasi izin keluar semester ini:
                </span>
              </div>
              <span class="px-2.5 py-1 rounded-lg bg-blue-200/70 dark:bg-blue-900/70 text-blue-950 dark:text-blue-100 font-extrabold text-xs">
                {{ selectedStudentLeave?.izin }} Kali Keluar
              </span>
            </div>

            <!-- Timeline of Leave Events -->
            <div v-if="selectedStudentLeave?.leave_details?.length > 0" class="space-y-3 mt-3">
              <div
                v-for="(item, idx) in selectedStudentLeave.leave_details"
                :key="idx"
                class="p-4 rounded-2xl border border-gray-150 dark:border-slate-800 bg-white dark:bg-slate-850 space-y-3 shadow-2xs hover:border-blue-300 dark:hover:border-blue-700/60 transition"
              >
                <!-- Tanggal & Jam Header -->
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-2.5">
                  <div class="flex items-center gap-2">
                    <CalendarIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ item.full_date || item.date }}</span>
                  </div>
                  <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-mono font-semibold text-slate-700 dark:text-slate-300">
                    <ClockIcon class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                    <span>{{ item.leave_time_formatted || item.leave_time }}</span>
                  </div>
                </div>

                <!-- Pelajaran & Topik -->
                <div class="flex items-start gap-2 text-xs text-gray-600 dark:text-slate-300">
                  <BookOpenIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                  <div>
                    <span class="font-bold text-gray-800 dark:text-slate-200">{{ item.subject }}</span>
                    <span v-if="item.topic && item.topic !== '-'" class="text-gray-500 dark:text-slate-400 text-[11px] block">
                      Topik Sesi: {{ item.topic }}
                    </span>
                  </div>
                </div>

                <!-- Humanizer Statement (Natural Language) -->
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80">
                  <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1 flex items-center gap-1">
                    <span>Penjelasan Izin Keluar</span>
                  </div>
                  <p class="text-xs font-medium text-gray-800 dark:text-slate-200 italic leading-relaxed">
                    "{{ item.humanized || item.note }}"
                  </p>
                </div>
              </div>
            </div>

            <!-- Fallback State -->
            <div v-else class="py-10 text-center space-y-2">
              <p class="text-xs text-gray-400 dark:text-slate-500">
                Catatan spesifik tidak tersedia pada riwayat jurnal pertemuan ini.
              </p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-gray-150 dark:border-slate-800 flex justify-end bg-slate-50/50 dark:bg-slate-850/50">
            <button
              @click="closeLeaveModal"
              type="button"
              class="px-5 py-2.5 rounded-xl bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-gray-900 font-bold text-xs transition cursor-pointer shadow-2xs"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { useToastStore } from '~/stores/toast'
import {
  FileSpreadsheet as FileSpreadsheetIcon,
  Printer as PrinterIcon,
  Search as SearchIcon,
  Calendar as CalendarIcon,
  TrendingUp as TrendingUpIcon,
  Users as UsersIcon,
  FileText as FileTextIcon,
  BookOpen as BookOpenIcon,
  Clock as ClockIcon,
  Info as InfoIcon,
  X as XIcon,
} from 'lucide-vue-next'

const config = useRuntimeConfig()
const authStore = useAuthStore()
const appConfig = useAppConfigStore()
const toast = useToastStore()

const filterTerm = ref('2026-1')
const filterMode = ref<'daily' | 'kbm'>(authStore.user?.role === 'GURU_PENGAJAR' ? 'kbm' : 'daily')
const filterSubject = ref('')
const subjectOptions = ref<any[]>([])
const teacherSubjects = ref<any[]>([])
const selectedSubjectName = ref('')

// Modal Humanizer State
const selectedStudentLeave = ref<any>(null)
const isLeaveModalOpen = ref(false)

const openLeaveDetail = (student: any) => {
  selectedStudentLeave.value = student
  isLeaveModalOpen.value = true
}

const closeLeaveModal = () => {
  isLeaveModalOpen.value = false
  selectedStudentLeave.value = null
}

const setMode = (mode: 'daily' | 'kbm') => {
  filterMode.value = mode
  fetchData()
}

const filterClass = ref(
  authStore.user?.role === 'GURU_PEMBIMBING' && authStore.user?.guided_class?.id
    ? String(authStore.user.guided_class.id)
    : (authStore.user?.class_id
        ? String(authStore.user.class_id)
        : (authStore.user?.guided_class?.id ? String(authStore.user.guided_class.id) : ''))
)

const classOptions = ref<any[]>([])

const isClassLocked = computed(() => {
  if (['SEKRETARIS', 'SISWA'].includes(authStore.user?.role || '')) return true
  if (authStore.user?.role === 'GURU_PEMBIMBING' && (authStore.user?.guided_class?.id || authStore.user?.class_id)) return true
  if (authStore.user?.role === 'GURU_PENGAJAR' && classOptions.value.length === 1) return true
  return false
})

const currentClassName = computed(() => {
  if (authStore.user?.role === 'GURU_PEMBIMBING') {
    return authStore.user?.guided_class?.name || authStore.user?.school_class?.name || 'Kelas Binaan'
  }
  const found = classOptions.value.find(c => String(c.id) === String(filterClass.value))
  if (found) return found.name
  return authStore.user?.school_class?.name || authStore.user?.guided_class?.name || 'Kelas Anda'
})

const teacherOrWalasName = computed(() => {
  if (authStore.user?.role === 'GURU_PENGAJAR') {
    return authStore.user?.name || 'Guru Pengampu'
  }
  if (authStore.user?.role === 'GURU_PEMBIMBING') {
    return authStore.user?.name || 'Wali Kelas'
  }
  const cls = classOptions.value.find(c => String(c.id) === String(filterClass.value))
  return cls?.advisor?.name || (authStore.user?.name || 'Guru Pengampu')
})

const teacherOrWalasNip = computed(() => {
  if (authStore.user?.role === 'GURU_PENGAJAR') {
    return authStore.user?.nip_nis || '-'
  }
  if (authStore.user?.role === 'GURU_PEMBIMBING') {
    return authStore.user?.nip_nis || '-'
  }
  const cls = classOptions.value.find(c => String(c.id) === String(filterClass.value))
  return cls?.advisor?.nip_nis || (authStore.user?.nip_nis || '-')
})

const printDate = computed(() => {
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(new Date())
})

const searchQuery = ref('')
const loading = ref(false)
const chartType = ref<'bar' | 'pie'>('bar')

const effectiveDays = ref(0)
const avgAttendance = ref('0%')
const predicate = ref('Sangat Baik')
const targetNote = ref('Target sekolah di atas 90% tercapai')
const predicateBadgeClass = ref('text-green-700 bg-green-50')
const students = ref<any[]>([])

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value
  const q = searchQuery.value.toLowerCase()
  return students.value.filter(
    s => s.name.toLowerCase().includes(q) || (s.nis && s.nis.includes(q))
  )
})

// Show only first 10 students in chart to avoid clutter
const chartStudents = computed(() => {
  return students.value.slice(0, 10).map(s => ({
    ...s,
    shortName: s.name.split(' ')[0],
  }))
})

const totalHadir = computed(() => students.value.reduce((a, s) => a + (parseInt(s.hadir) || 0), 0))
const totalSakit = computed(() => students.value.reduce((a, s) => a + (parseInt(s.sakit) || 0), 0))
const totalIzin = computed(() => students.value.reduce((a, s) => a + (parseInt(s.izin) || 0), 0))
const totalAlpha = computed(() => students.value.reduce((a, s) => a + (parseInt(s.alpha) || 0), 0))
const totalAll = computed(() => totalHadir.value + totalSakit.value + totalIzin.value + totalAlpha.value || 1)

const pieData = computed(() => ({
  hadir: (totalHadir.value / totalAll.value) * 100,
  sakit: (totalSakit.value / totalAll.value) * 100,
  izin: (totalIzin.value / totalAll.value) * 100,
  alpha: (totalAlpha.value / totalAll.value) * 100,
}))

const pieLegends = computed(() => [
  { label: 'Hadir', color: '#10b981', pct: pieData.value.hadir },
  { label: 'Sakit', color: '#f59e0b', pct: pieData.value.sakit },
  { label: 'Izin Keluar', color: '#3b82f6', pct: pieData.value.izin },
  { label: 'ALFA', color: '#f43f5e', pct: pieData.value.alpha },
])

const handleClassChange = () => {
  fetchData()
}

const fetchData = async () => {
  loading.value = true
  try {
    const params: any = {
      class_id: filterClass.value,
      term: filterTerm.value,
      mode: filterMode.value,
    }
    if (filterMode.value === 'kbm' && filterSubject.value) {
      params.subject_id = filterSubject.value
    }
    const res: any = await $fetch(`${config.public.apiBase}/attendance/semester-recap`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
      params
    })
    effectiveDays.value = res.effective_days
    avgAttendance.value = res.average_attendance
    if (res.predicate) predicate.value = res.predicate
    if (res.target_note) targetNote.value = res.target_note
    if (res.predicate_badge_class) predicateBadgeClass.value = res.predicate_badge_class
    students.value = res.students || []
    if (Array.isArray(res.subjects)) {
      subjectOptions.value = res.subjects
    }
    if (res.selected_subject) {
      selectedSubjectName.value = res.selected_subject
    }
    if (res.selected_subject_id && !filterSubject.value) {
      filterSubject.value = String(res.selected_subject_id)
    }
  } catch (err: any) {
    console.error('Error fetching semester recap:', err)
  } finally {
    loading.value = false
  }
}

const exportExcel = () => {
  if (!filteredStudents.value || filteredStudents.value.length === 0) {
    toast.warning('Tidak ada data siswa untuk diekspor.')
    return
  }

  const headers = ['No', 'Nama Siswa', 'NIS / NISN', 'Hadir', 'Sakit', filterMode.value === 'kbm' ? 'Izin Keluar' : 'Izin', 'ALFA', 'Persentase']
  const rows = filteredStudents.value.map((s, idx) => [
    idx + 1,
    `"${(s.name || '').replace(/"/g, '""')}"`,
    `"${(s.nis || '').replace(/"/g, '""')}"`,
    s.hadir,
    s.sakit,
    s.izin,
    s.alpha,
    `"${s.percentage}"`
  ])

  const csvRows = ['sep=;', headers.join(';'), ...rows.map(e => e.join(';'))].join('\r\n')
  const blob = new Blob(['\uFEFF' + csvRows], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `rekap_presensi_${filterMode.value}_${filterTerm.value}_kelas_${filterClass.value}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const exportWord = () => {
  if (!filteredStudents.value || filteredStudents.value.length === 0) {
    toast.warning('Tidak ada data siswa untuk diekspor.')
    return
  }

  const className = currentClassName.value
  const termName = filterTerm.value === '2026-1' ? '2026/2027 Ganjil' : filterTerm.value

  const rowsHtml = filteredStudents.value.map((s, idx) => `
    <tr>
      <td style="border:1px solid #ddd; padding:8px; text-align:center;">${idx + 1}</td>
      <td style="border:1px solid #ddd; padding:8px;">${s.name || '-'}</td>
      <td style="border:1px solid #ddd; padding:8px; text-align:center;">${s.nis || '-'}</td>
      <td style="border:1px solid #ddd; padding:8px; text-align:center;">${s.hadir}</td>
      <td style="border:1px solid #ddd; padding:8px; text-align:center;">${s.sakit}</td>
      <td style="border:1px solid #ddd; padding:8px; text-align:center;">${s.izin}</td>
      <td style="border:1px solid #ddd; padding:8px; text-align:center;">${s.alpha}</td>
      <td style="border:1px solid #ddd; padding:8px; text-align:center; font-weight:bold;">${s.percentage}</td>
    </tr>
  `).join('')

  const wordHtml = `
    <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
    <head>
      <meta charset='utf-8'>
      <title>Rekapitulasi Presensi Semester</title>
      <style>
        body { font-family: 'Calibri', 'Arial', sans-serif; font-size: 11pt; color: #333; line-height: 1.4; }
        .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop h2 { margin: 0; font-size: 16pt; color: #0f47ad; }
        .kop h3 { margin: 5px 0 0 0; font-size: 13pt; }
        .meta-table { width: 100%; margin-bottom: 15px; }
        .meta-table td { padding: 4px 0; font-size: 10.5pt; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th { background: #0f47ad; color: #fff; border: 1px solid #0f47ad; padding: 8px; font-size: 10pt; text-align: center; }
        .ttd-table { width: 100%; margin-top: 40px; }
        .ttd-table td { text-align: center; width: 50%; padding-top: 10px; font-size: 11pt; }
      </style>
    </head>
    <body>
      <div class="kop">
        <h2>${appConfig.school_name || 'SMK NEGERI 71 JAKARTA'}</h2>
        <h3>LAPORAN REKAPITULASI PRESENSI SEMESTER SISWA</h3>
        <p style="margin:2px 0 0 0; font-size:9pt; color:#666;">${appConfig.school_address || 'Jl. Raden Inten II No. 71, Duren Sawit, Jakarta Timur'} • NPSN: ${appConfig.school_npsn || '20104567'}</p>
      </div>

      <table class="meta-table">
        <tr>
          <td style="width:15%;"><strong>Kelas</strong></td>
          <td style="width:35%;">: ${className}</td>
          <td style="width:20%;"><strong>Tahun / Semester</strong></td>
          <td style="width:30%;">: ${termName}</td>
        </tr>
        <tr>
          <td><strong>${filterMode.value === 'kbm' ? 'Total Sesi KBM' : 'Hari Efektif'}</strong></td>
          <td>: ${effectiveDays.value} ${filterMode.value === 'kbm' ? 'Sesi' : 'Hari'}</td>
          <td><strong>Rata-rata Kehadiran</strong></td>
          <td>: ${avgAttendance.value}</td>
        </tr>
      </table>

      <table class="data-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Siswa</th>
            <th>NIS / NISN</th>
            <th>Hadir</th>
            <th>Sakit</th>
            <th>${filterMode.value === 'kbm' ? 'Izin Keluar' : 'Izin'}</th>
            <th>Alfa</th>
            <th>Persentase</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml}
        </tbody>
      </table>

      <table class="ttd-table">
        <tr>
          <td>
            Mengetahui,<br>
            <strong>Kepala ${appConfig.school_name || 'SMK Negeri 71 Jakarta'}</strong><br><br><br><br>
            <strong><u>${appConfig.headmaster_name || 'Dr. H. Bambang Soeprapto, M.Pd'}</u></strong><br>
            NIP. ${appConfig.headmaster_nip || '197505101999031002'}
          </td>
          <td>
            Jakarta, ${printDate.value}<br>
            <strong>Guru Pengampu / Wali Kelas</strong><br><br><br><br>
            <strong><u>${teacherOrWalasName.value}</u></strong><br>
            NIP. ${teacherOrWalasNip.value}
          </td>
        </tr>
      </table>
    </body>
    </html>
  `

  const blob = new Blob(['\uFEFF' + wordHtml], { type: 'application/msword;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `rekap_presensi_${className.replace(/\s+/g, '_')}_${filterTerm.value}.doc`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
  toast.success('File Word rekap presensi semester berhasil diunduh.')
}

const printPdf = () => {
  window.print()
}

const fetchClasses = async () => {
  try {
    if (authStore.user?.role === 'GURU_PENGAJAR') {
      // Guru Pengajar: Ambil jadwal mengajar untuk mengekstrak kelas dan mata pelajaran yang diampu
      const schedulesRes: any = await $fetch(`${config.public.apiBase}/schedules`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      })
      if (Array.isArray(schedulesRes)) {
        const classMap = new Map()
        const subjectMap = new Map()
        for (const sch of schedulesRes) {
          if (sch.school_class) {
            classMap.set(String(sch.school_class.id), sch.school_class)
          }
          if (sch.subject) {
            subjectMap.set(String(sch.subject.id), sch.subject)
          }
        }
        classOptions.value = Array.from(classMap.values())
        teacherSubjects.value = Array.from(subjectMap.values())

        // Auto detect subject:
        if (teacherSubjects.value.length > 0) {
          if (!filterSubject.value || !subjectMap.has(filterSubject.value)) {
            filterSubject.value = String(teacherSubjects.value[0].id)
          }
          selectedSubjectName.value = teacherSubjects.value[0].name
        }

        // Set default class if current filterClass not in teacher's classes:
        if (classOptions.value.length > 0) {
          if (!filterClass.value || !classMap.has(filterClass.value)) {
            filterClass.value = String(classOptions.value[0].id)
          }
        }
      }
    } else {
      // Mode Walas / Superadmin: Ambil daftar seluruh kelas
      const res: any = await $fetch(`${config.public.apiBase}/classes`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      })
      if (Array.isArray(res) && res.length > 0) {
        classOptions.value = res
        const myClassId = authStore.user?.class_id || authStore.user?.guided_class?.id
        if (authStore.user?.role === 'GURU_PEMBIMBING' && myClassId) {
          filterClass.value = String(myClassId)
        } else if (!classOptions.value.some(c => String(c.id) === String(filterClass.value))) {
          filterClass.value = String(res[0].id)
        }
      }
    }
  } catch (e) {
    console.error('Error fetching classes:', e)
  }
}

onMounted(async () => {
  if (!appConfig.loaded) {
    await appConfig.fetchConfig()
  }
  await fetchClasses()
  fetchData()
})
</script>
