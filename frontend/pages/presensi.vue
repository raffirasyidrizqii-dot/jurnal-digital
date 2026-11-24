<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Monitoring & Rekap Presensi Kelas"
      :subtitle="dateFormatted + ' • ' + displayClassName"
    />

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- 6 Summary Metric Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
          <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Siswa</p>
          <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ summary.total }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
          <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Belum Absen</p>
          <p class="text-2xl font-extrabold text-slate-600 dark:text-slate-300 mt-1">{{ summary.belumAbsen }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-900/40 rounded-2xl p-4 shadow-xs bg-emerald-50/20 dark:bg-emerald-950/20">
          <p class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Hadir</p>
          <p class="text-2xl font-extrabold text-emerald-700 dark:text-emerald-400 mt-1">{{ summary.hadir }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-amber-100 dark:border-amber-900/40 rounded-2xl p-4 shadow-xs bg-amber-50/20 dark:bg-amber-950/20">
          <p class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Sakit</p>
          <p class="text-2xl font-extrabold text-amber-700 dark:text-amber-400 mt-1">{{ summary.sakit }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 rounded-2xl p-4 shadow-xs bg-blue-50/20 dark:bg-blue-950/20">
          <p class="text-[11px] font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Izin</p>
          <p class="text-2xl font-extrabold text-blue-700 dark:text-blue-400 mt-1">{{ summary.izin }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-rose-100 dark:border-rose-900/40 rounded-2xl p-4 shadow-xs bg-rose-50/20 dark:bg-rose-950/20">
          <p class="text-[11px] font-semibold text-rose-700 dark:text-rose-400 uppercase tracking-wider">Alfa</p>
          <p class="text-2xl font-extrabold text-rose-700 dark:text-rose-400 mt-1">{{ summary.alpha }}</p>
        </div>
      </div>

      <!-- Action Bar: Class Selector, Date Selector, Ekspor Word/Excel, Refresh & Search -->
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
        <!-- Class Selector & Date Selector & Quick Nav -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Class Selector: Dropdown for Guru Pengajar & Superadmin, Locked Badge for Walas / Sekretaris -->
          <div v-if="canSelectClass && allClasses.length > 0" class="relative">
            <select
              v-model="selectedClassId"
              @change="onClassChanged"
              class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-primary/40 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 shadow-2xs focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer min-h-[38px] transition"
            >
              <option v-for="cls in allClasses" :key="cls.id" :value="String(cls.id)">
                Kelas {{ cls.name }}
              </option>
            </select>
          </div>
          <div v-else class="px-3.5 py-2 bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/40 text-primary dark:text-blue-400 font-bold text-xs rounded-xl shadow-2xs flex items-center gap-2 whitespace-nowrap min-h-[38px]">
            <span>{{ displayClassName }}</span>
          </div>

          <div class="flex items-center gap-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-primary/40 px-3.5 py-2 rounded-xl shadow-2xs min-h-[38px] transition focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary">
            <Calendar class="w-4 h-4 text-primary dark:text-blue-400 shrink-0" />
            <input
              type="date"
              lang="id-ID"
              v-model="selectedDate"
              @change="onDateChanged"
              class="text-xs font-semibold text-slate-800 dark:text-slate-200 bg-transparent focus:outline-none cursor-pointer"
            />
          </div>
          <button
            type="button"
            @click="setToday"
            class="px-3 py-2 bg-primary/10 dark:bg-blue-900/30 hover:bg-primary/15 dark:hover:bg-blue-900/50 text-primary dark:text-blue-400 border border-primary/20 dark:border-blue-800/40 text-xs font-bold rounded-xl transition cursor-pointer min-h-[38px] active:scale-95 shadow-2xs"
          >
            Hari Ini
          </button>
        </div>

        <!-- Export Buttons & Refresh & Search -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <!-- Search -->
          <div class="relative flex-1 sm:w-60">
            <Search class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama siswa..."
              class="w-full pl-9 pr-3 py-2.5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs min-h-[44px]"
            />
          </div>

          <!-- Ekspor Excel -->
          <button
            type="button"
            @click="exportExcel"
            :disabled="isLoading || students.length === 0"
            class="px-2.5 sm:px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer transition disabled:opacity-50 min-h-[44px] shrink-0"
            title="Ekspor rekap harian ke Excel"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span class="hidden sm:inline">Ekspor Excel</span>
            <span class="sm:hidden text-xs">Excel</span>
          </button>

          <!-- Ekspor Word -->
          <button
            type="button"
            @click="exportWord"
            :disabled="isLoading || students.length === 0"
            class="px-2.5 sm:px-3.5 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs font-semibold shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer transition disabled:opacity-50 min-h-[44px] shrink-0"
            title="Ekspor agenda presensi ke Word"
          >
            <FileText class="w-4 h-4" />
            <span class="hidden sm:inline">Ekspor Word</span>
            <span class="sm:hidden text-xs">Word</span>
          </button>

          <!-- Refresh -->
          <button
            type="button"
            @click="() => loadAttendance(false)"
            :disabled="isRefreshing"
            class="p-2.5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-xl text-xs transition cursor-pointer flex items-center justify-center gap-1.5 shadow-2xs min-h-[44px] min-w-[44px] shrink-0"
            title="Segarkan data presensi"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin text-primary dark:text-blue-400': isRefreshing }" />
          </button>
        </div>
      </div>

      <!-- Weekend Alert Banner -->
      <div v-if="isWeekendSelected" class="p-4 sm:p-5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-amber-900 dark:text-amber-200 flex items-start gap-3.5 shadow-2xs">
        <AlertCircle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
        <div class="text-xs space-y-1">
          <div class="flex items-center gap-2">
            <span class="font-extrabold text-sm sm:text-base text-amber-900 dark:text-amber-200">Hari Libur Akhir Pekan</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] bg-amber-200/90 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 font-extrabold uppercase">Sekolah Libur</span>
          </div>
          <p class="text-amber-800 dark:text-amber-300 leading-relaxed text-xs">
            Hari belajar mengajar sekolah hanya berlangsung hari <strong>Senin hingga Jumat</strong>. Seluruh pencatatan dan pengesahan presensi kelas dinonaktifkan pada hari Sabtu dan Minggu.
          </p>
        </div>
      </div>

      <!-- Main Layout: Table + Verification Panel -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Student Table (3 cols) -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs flex flex-col justify-between">
          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3">
            <div v-if="isLoading && students.length === 0" v-for="i in 6" :key="'skel-m-' + i" class="p-4 rounded-2xl border border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-850/50 animate-pulse space-y-3">
              <div class="h-4 w-32 bg-gray-200 dark:bg-slate-700 rounded"></div>
              <div class="h-10 w-full bg-gray-200 dark:bg-slate-700 rounded-xl"></div>
            </div>

            <div v-else-if="filteredStudents.length > 0" v-for="student in filteredStudents" :key="'mob-' + student.student_id" class="p-3.5 sm:p-4 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-850 shadow-2xs space-y-3">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 font-bold text-xs flex items-center justify-center shrink-0">{{ student.no }}</span>
                    <span class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ student.name }}</span>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-slate-400 font-mono mt-0.5 ml-8">NIS: {{ student.nis }}</p>
                </div>
                <span
                  v-if="student.status === 'BELUM_ABSEN'"
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700 shrink-0"
                >
                  Belum Absen
                </span>
                <span
                  v-else-if="student.is_rejected_leave"
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 shrink-0"
                >
                  Izin Ditolak
                </span>
              </div>

              <!-- Status Display for Walas (Read-Only) vs Segmented Buttons for Sekretaris/Superadmin -->
              <div v-if="isWalas" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-750 rounded-2xl">
                <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">Status Kehadiran:</span>
                <span
                  class="px-3 py-1 rounded-xl text-xs font-bold"
                  :class="{
                    'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50': student.status === 'HADIR',
                    'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50': student.status === 'SAKIT',
                    'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50': student.status === 'IZIN',
                    'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50': student.status === 'ALPHA',
                    'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700': student.status === 'BELUM_ABSEN' || !student.status,
                  }"
                >
                  {{ student.status === 'BELUM_ABSEN' || !student.status ? 'Belum Absen' : student.status }}
                </span>
              </div>
              <div v-else class="grid grid-cols-4 gap-1.5 p-1.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-750 rounded-2xl shadow-2xs" :class="{ 'opacity-60 pointer-events-none': isLockedForUser }">
                <button
                  type="button"
                  @click="setStudentStatus(student, 'HADIR')"
                  :disabled="isLockedForUser"
                  class="py-2.5 rounded-xl text-xs font-bold transition-all text-center min-h-[44px] flex items-center justify-center touch-manipulation active:scale-95 cursor-pointer"
                  :class="student.status === 'HADIR' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-600 font-extrabold' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 shadow-2xs'"
                >
                  Hadir
                </button>
                <button
                  type="button"
                  @click="setStudentStatus(student, 'SAKIT')"
                  :disabled="isLockedForUser"
                  class="py-2.5 rounded-xl text-xs font-bold transition-all text-center min-h-[44px] flex items-center justify-center touch-manipulation active:scale-95 cursor-pointer"
                  :class="student.status === 'SAKIT' ? 'bg-amber-500 text-white shadow-sm ring-1 ring-amber-500 font-extrabold' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 shadow-2xs'"
                >
                  Sakit
                </button>
                <button
                  type="button"
                  @click="setStudentStatus(student, 'IZIN')"
                  :disabled="isLockedForUser"
                  class="py-2.5 rounded-xl text-xs font-bold transition-all text-center min-h-[44px] flex items-center justify-center touch-manipulation active:scale-95 cursor-pointer"
                  :class="student.status === 'IZIN' ? 'bg-blue-600 text-white shadow-sm ring-1 ring-blue-600 font-extrabold' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 shadow-2xs'"
                >
                  Izin
                </button>
                <button
                  type="button"
                  @click="setStudentStatus(student, 'ALPHA')"
                  :disabled="isLockedForUser"
                  class="py-2.5 rounded-xl text-xs font-bold transition-all text-center min-h-[44px] flex items-center justify-center touch-manipulation active:scale-95 cursor-pointer"
                  :class="student.status === 'ALPHA' ? 'bg-rose-600 text-white shadow-sm ring-1 ring-rose-600 font-extrabold' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 shadow-2xs'"
                >
                  ALFA
                </button>
              </div>

              <!-- Note input -->
              <input
                v-model="student.note"
                :disabled="isLockedForUser"
                :readonly="isWalas"
                type="text"
                placeholder="Catatan kehadiran..."
                class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary bg-gray-50/50 dark:bg-slate-800/60 text-gray-800 dark:text-slate-200"
                :class="{ 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-slate-800': isLockedForUser }"
              />
            </div>

            <div v-else class="py-12 text-center text-gray-500 dark:text-slate-400">
              <div v-if="loadError" class="space-y-3">
                <div class="inline-flex p-3 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 rounded-full mb-1">
                  <AlertCircle class="w-6 h-6" />
                </div>
                <p class="text-xs text-red-600 dark:text-red-400 font-semibold max-w-sm mx-auto">{{ loadError }}</p>
                <div>
                  <button
                    type="button"
                    @click="() => loadAttendance(false)"
                    class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-xl shadow-2xs transition cursor-pointer min-h-[44px]"
                  >
                    Coba Lagi
                  </button>
                </div>
              </div>
              <div v-else class="text-xs text-gray-400 dark:text-slate-500 py-6">
                Tidak ada siswa yang cocok dengan pencarian "{{ searchQuery }}".
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left text-xs min-w-[850px]">
              <thead>
                <tr class="text-gray-400 dark:text-slate-400 uppercase text-[10px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/60 font-bold">
                  <th class="py-3.5 px-4 font-semibold w-12 whitespace-nowrap">No</th>
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap min-w-[220px]">Nama Siswa</th>
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap w-32">NIS</th>
                  <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap min-w-[320px]">Status Kehadiran</th>
                  <th class="py-3.5 px-4 font-semibold whitespace-nowrap min-w-[200px]">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <!-- Skeleton rows during initial loading -->
                <tr v-if="isLoading && students.length === 0" v-for="i in 8" :key="'skel-' + i" class="animate-pulse">
                  <td class="py-3.5 px-4 whitespace-nowrap"><div class="h-3 w-4 bg-gray-200 dark:bg-slate-700 rounded"></div></td>
                  <td class="py-3.5 px-4 whitespace-nowrap"><div class="h-3.5 w-36 bg-gray-200 dark:bg-slate-700 rounded"></div></td>
                  <td class="py-3.5 px-4 whitespace-nowrap"><div class="h-3 w-16 bg-gray-200 dark:bg-slate-700 rounded"></div></td>
                  <td class="py-3.5 px-4 whitespace-nowrap"><div class="h-7 w-48 bg-gray-200 dark:bg-slate-700 rounded-xl mx-auto"></div></td>
                  <td class="py-3.5 px-4 whitespace-nowrap"><div class="h-7 w-full bg-gray-100 dark:bg-slate-800 rounded-lg"></div></td>
                </tr>

                <!-- Actual Student Rows -->
                <tr v-else-if="filteredStudents.length > 0" v-for="student in filteredStudents" :key="student.student_id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                  <td class="py-3.5 px-4 text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ student.no }}</td>
                  <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <span>{{ student.name }}</span>
                      <span
                        v-if="student.is_rejected_leave"
                        class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50"
                        title="Surat izin ditolak Wali Kelas - Wajib verifikasi fisik di kelas"
                      >
                        Izin Ditolak Walas
                      </span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ student.nis }}</td>
                  <!-- Status Kehadiran: Read-Only Badge for Walas vs Segmented Buttons for Sekretaris/Superadmin -->
                  <td class="py-3.5 px-4 whitespace-nowrap text-center">
                    <div v-if="isWalas" class="inline-flex items-center justify-center">
                      <span
                        class="px-3 py-1 rounded-xl text-xs font-bold"
                        :class="{
                          'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50': student.status === 'HADIR',
                          'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50': student.status === 'SAKIT',
                          'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50': student.status === 'IZIN',
                          'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50': student.status === 'ALPHA',
                          'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700': student.status === 'BELUM_ABSEN' || !student.status,
                        }"
                      >
                        {{ student.status === 'BELUM_ABSEN' || !student.status ? 'Belum Absen' : student.status }}
                      </span>
                    </div>
                    <div v-else class="inline-flex items-center gap-1.5">
                      <span
                        v-if="student.status === 'BELUM_ABSEN'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700 shrink-0"
                        title="Siswa belum tercatat presensi"
                      >
                        Belum Absen
                      </span>
                      <div class="inline-flex p-1 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-750 rounded-xl gap-1 shadow-2xs" :class="{ 'opacity-60 pointer-events-none': isLockedForUser }">
                      <button
                        type="button"
                        @click="setStudentStatus(student, 'HADIR')"
                        :disabled="isLockedForUser"
                        :class="[
                          'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all whitespace-nowrap',
                          isLockedForUser ? 'cursor-not-allowed' : 'cursor-pointer',
                          student.status === 'HADIR' ? 'bg-emerald-600 text-white shadow-2xs ring-1 ring-emerald-600' : 'bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750'
                        ]"
                      >
                        Hadir
                      </button>
                      <button
                        type="button"
                        @click="setStudentStatus(student, 'SAKIT')"
                        :disabled="isLockedForUser"
                        :class="[
                          'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all whitespace-nowrap',
                          isLockedForUser ? 'cursor-not-allowed' : 'cursor-pointer',
                          student.status === 'SAKIT' ? 'bg-amber-500 text-white shadow-2xs ring-1 ring-amber-500' : 'bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750'
                        ]"
                      >
                        Sakit
                      </button>
                      <button
                        type="button"
                        @click="setStudentStatus(student, 'IZIN')"
                        :disabled="isLockedForUser"
                        :class="[
                          'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all whitespace-nowrap',
                          isLockedForUser ? 'cursor-not-allowed' : 'cursor-pointer',
                          student.status === 'IZIN' ? 'bg-blue-600 text-white shadow-2xs ring-1 ring-blue-600' : 'bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750'
                        ]"
                      >
                        Izin
                      </button>
                      <button
                        type="button"
                        @click="setStudentStatus(student, 'ALPHA')"
                        :disabled="isLockedForUser"
                        :class="[
                          'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all whitespace-nowrap',
                          isLockedForUser ? 'cursor-not-allowed' : 'cursor-pointer',
                          student.status === 'ALPHA' ? 'bg-rose-600 text-white shadow-2xs ring-1 ring-rose-600' : 'bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750'
                        ]"
                      >
                        ALFA
                      </button>
                    </div>
                    </div>
                  </td>
                  <!-- Note Input -->
                  <td class="py-3.5 px-4">
                    <input
                      v-model="student.note"
                      :disabled="isLockedForUser"
                      :readonly="isWalas"
                      type="text"
                      placeholder="Catatan..."
                      class="w-full px-3 py-1.5 text-xs border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-200"
                      :class="{ 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-slate-800': isLockedForUser }"
                    />
                  </td>
                </tr>

                <!-- Error or Empty Search -->
                <tr v-else>
                  <td colspan="5" class="py-12 px-4 text-center text-gray-500 dark:text-slate-400">
                    <div v-if="loadError" class="space-y-3">
                      <div class="inline-flex p-3 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 rounded-full mb-1">
                        <AlertCircle class="w-6 h-6" />
                      </div>
                      <p class="text-xs text-red-600 dark:text-red-400 font-semibold max-w-sm mx-auto">{{ loadError }}</p>
                      <div>
                        <button
                          type="button"
                          @click="() => loadAttendance(false)"
                          class="px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-xl shadow-2xs transition cursor-pointer"
                        >
                          Coba Lagi
                        </button>
                      </div>
                    </div>
                    <div v-else class="text-xs text-gray-400 dark:text-slate-500 py-6">
                      Tidak ada siswa yang cocok dengan pencarian "{{ searchQuery }}".
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Summary Footer -->
          <div class="flex flex-col sm:flex-row items-center justify-between pt-4 border-t border-gray-100 dark:border-slate-800 text-xs text-gray-500 dark:text-slate-400 gap-2">
            <span>Menampilkan {{ filteredStudents.length }} dari {{ students.length }} siswa terdaftar</span>
            <span class="text-[11px] text-gray-400 dark:text-slate-500 font-medium">Data presensi tersinkronisasi otomatis dengan server</span>
          </div>
        </div>

        <!-- Verification Panel (1 col, Sticky floating) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-6 h-fit sticky top-6 self-start">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white pb-3 border-b border-gray-100 dark:border-slate-800">Verifikasi Status</h3>

          <div class="space-y-3 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-gray-600 dark:text-slate-300">Sekretaris Kelas</span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                :class="sessionStatus === 'LOCKED' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300'"
              >
                {{ sessionStatus === 'LOCKED' ? 'Selesai Dikunci' : 'Draf Belum Dikunci' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600 dark:text-slate-300">Wali Kelas</span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                :class="verificationStatus === 'TERVERIFIKASI' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50'"
              >
                {{ verificationStatus === 'TERVERIFIKASI' ? 'Disahkan Walas' : 'Menunggu Pengesahan' }}
              </span>
            </div>
            <p v-if="sessionStatus === 'LOCKED'" class="text-[11px] text-gray-500 dark:text-slate-400 italic mt-2 pt-3.5 border-t border-gray-100 dark:border-slate-800">
              Presensi hari ini sudah pernah dikunci. Anda masih dapat mengubah data kehadiran lalu menekan "Kunci Ulang Presensi" dengan memasukkan password Anda.
            </p>
          </div>

          <!-- Aksi untuk Wali Kelas (Guru Pembimbing) / Superadmin: Pengesahan -->
          <div v-if="isWalas || authStore.user?.role === 'SUPERADMIN'" class="space-y-3 mt-2 pt-4 border-t border-gray-100 dark:border-slate-800">
            <div v-if="isWeekendSelected" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-amber-800 dark:text-amber-200 text-xs font-semibold">
              Sekolah libur akhir pekan. Pengesahan presensi harian dinonaktifkan.
            </div>
            <div v-else-if="verificationStatus !== 'TERVERIFIKASI'">
              <div v-if="sessionStatus !== 'LOCKED'" class="p-3 mb-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-amber-800 dark:text-amber-200 text-xs">
                Sesi presensi masih berstatus <strong>DRAFT</strong> belum dikunci oleh Sekretaris.
              </div>
              <button
                type="button"
                @click="handleConfirmTeacher"
                :disabled="isSubmitting || isWeekendSelected"
                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl text-xs font-semibold shadow-2xs transition-colors cursor-pointer flex items-center justify-center gap-2"
              >
                <Check class="w-4 h-4" />
                <span>Sahkan Presensi</span>
              </button>
            </div>
            <div v-else class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-200 text-xs font-semibold flex items-center gap-2">
              <Check class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
              <span>Presensi harian telah disahkan resmi oleh Wali Kelas.</span>
            </div>

            <!-- Hak Superadmin: Force Unlock Sesi Terkunci Langsung di Halaman Presensi -->
            <div v-if="authStore.user?.role === 'SUPERADMIN' && sessionStatus === 'LOCKED'" class="mt-2 pt-3.5 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                @click="handleForceUnlockSuperadmin"
                :disabled="isSubmitting"
                class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px]"
                title="Buka status sesi presensi kembali ke draft"
              >
                <ShieldAlert class="w-4 h-4" />
                <span>Buka Kunci Presensi</span>
              </button>
            </div>
          </div>

          <!-- Panel Informasi untuk Guru Pengajar -->
          <div v-else-if="authStore.user?.role === 'GURU_PENGAJAR'" class="space-y-3 mt-2 pt-4 border-t border-slate-100 dark:border-slate-800">
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-xs space-y-3">
              <p class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                <ClipboardCheck class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                <span>Mode Guru Pengajar</span>
              </p>
              <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                Presensi harian sekolah dicatat oleh <strong>Sekretaris</strong> dan disahkan oleh <strong>Wali Kelas</strong>. Catatan kehadiran mengajar Anda per jam pelajaran dapat diisi di dalam <strong>Jurnal KBM</strong>.
              </p>
              <NuxtLink
                to="/jurnal-materi"
                class="w-full py-2.5 bg-primary hover:bg-primary-hover text-white font-bold rounded-xl text-xs transition shadow-2xs flex items-center justify-center gap-1.5 min-h-[44px]"
              >
                <span>Buka Jurnal KBM</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </NuxtLink>
            </div>
          </div>

          <!-- PIN / Password Lock Input (Hanya untuk Sekretaris) -->
          <template v-else>
            <div v-if="isWeekendSelected" class="p-3 mt-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-amber-800 dark:text-amber-200 text-xs font-semibold">
              Sekolah libur akhir pekan. Presensi harian tidak dapat dicatat atau dikunci.
            </div>
            <div v-else class="space-y-2 mt-2 pt-4 border-t border-slate-100 dark:border-slate-800">
              <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                PIN Penguncian Presensi
              </label>
              <input
                v-model="pin"
                type="password"
                placeholder="••••••••••••"
                class="w-full px-3.5 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-2xs"
              />
              <p class="text-[10px] text-slate-400 dark:text-slate-500">
                Gunakan kata sandi akun Anda atau PIN master darurat sekolah untuk mengunci/membuka presensi.
              </p>
            </div>

            <!-- Buttons -->
            <div class="space-y-2 mt-2 pt-3.5">
              <button
                type="button"
                @click="handleLock"
                :disabled="isSubmitting || isWeekendSelected"
                class="w-full py-3 bg-primary hover:bg-primary-hover disabled:opacity-50 text-white rounded-xl text-xs font-semibold shadow-2xs transition-colors cursor-pointer flex items-center justify-center gap-2"
              >
                <Lock class="w-3.5 h-3.5" />
                <span>{{ sessionStatus === 'LOCKED' ? 'Kunci Ulang Presensi' : 'Simpan & Kunci Presensi' }}</span>
              </button>

              <button
                type="button"
                @click="handleDraft"
                :disabled="isSubmitting || isLockedForUser || isWeekendSelected"
                class="w-full py-2.5 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-750 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition-colors disabled:opacity-50"
                :class="(isLockedForUser || isWeekendSelected) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
              >
                Simpan Draft
              </button>
            </div>
          </template>

          <!-- Feedback Toast -->
          <div v-if="toastMessage" class="p-3 rounded-xl text-xs" :class="toastError ? 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/50' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50'">
            {{ toastMessage }}
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import {
  Check,
  CheckCircle2,
  Save,
  Search,
  Lock,
  RefreshCw,
  AlertCircle,
  Calendar,
  FileSpreadsheet,
  FileText,
  ArrowRight,
  ShieldAlert,
  ClipboardCheck,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useAppConfigStore } from '~/stores/appConfig'
import { confirmDialog } from '~/utils/confirm'

const config = useRuntimeConfig()
const route = useRoute()
const authStore = useAuthStore()
const appConfig = useAppConfigStore()

function getLocalDateString(d: Date = new Date()) {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function formatReadableDate(dateStr: string) {
  try {
    const [y, m, d] = dateStr.split('-').map(Number)
    const dateObj = new Date(y, m - 1, d)
    return new Intl.DateTimeFormat('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).format(dateObj)
  } catch {
    return dateStr
  }
}

const sessionClassName = ref('')
const selectedClassId = ref<string>((route.query.class_id as string) || '')
const allClasses = ref<any[]>([])

const isWalas = computed(() => {
  if (['SISWA', 'SEKRETARIS', 'ORANG_TUA'].includes(authStore.user?.role || '')) return false
  return authStore.user?.role === 'GURU_PEMBIMBING' || !!authStore.user?.guided_class?.id || (authStore.user?.role === 'GURU_PENGAJAR' && !!authStore.user?.class_id)
})

const canSelectClass = computed(() => {
  // Hanya Superadmin yang berhak memilih kelas di Presensi Harian. Walas khusus kelasnya sendiri.
  return authStore.user?.role === 'SUPERADMIN'
})

const targetClassId = computed(() => {
  // Wali Kelas strictly locked to their guided class (tidak ada pilihan kelas lain)
  if (isWalas.value) {
    return String(authStore.user?.guided_class?.id || authStore.user?.class_id || (allClasses.value[0]?.id ? String(allClasses.value[0].id) : '1'))
  }
  // Siswa & Sekretaris strictly locked to their class
  if (authStore.user?.role === 'SEKRETARIS' || authStore.user?.role === 'SISWA') {
    return String(authStore.user?.class_id || (allClasses.value[0]?.id ? String(allClasses.value[0].id) : '1'))
  }
  if (authStore.user?.role === 'SUPERADMIN') {
    if (selectedClassId.value) return String(selectedClassId.value)
    if (route.query.class_id) return String(route.query.class_id)
  }
  return String(authStore.user?.class_id || (allClasses.value[0]?.id ? String(allClasses.value[0].id) : '1'))
})

const displayClassName = computed(() => {
  if (sessionClassName.value) return sessionClassName.value
  const found = allClasses.value.find(c => String(c.id) === String(targetClassId.value))
  if (found) return found.name
  return authStore.user?.guided_class?.name || authStore.user?.school_class?.name || 'Kelas'
})

const selectedDate = ref((route.query.date as string) || '')

const targetDate = computed(() => {
  return selectedDate.value || ''
})

const isWeekendSelected = computed(() => {
  if (!selectedDate.value) return false
  const [y, m, d] = selectedDate.value.split('-').map(Number)
  const dt = new Date(y, m - 1, d)
  const day = dt.getDay()
  return day === 0 || day === 6 // 0 is Sunday, 6 is Saturday
})

const selectedDayName = computed(() => {
  if (!selectedDate.value) return ''
  const [y, m, d] = selectedDate.value.split('-').map(Number)
  const dt = new Date(y, m - 1, d)
  const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
  return days[dt.getDay()] || ''
})

const dateFormatted = ref(selectedDate.value ? formatReadableDate(selectedDate.value) : '')

function onDateChanged() {
  if (selectedDate.value) {
    dateFormatted.value = formatReadableDate(selectedDate.value)
  }
  loadAttendance(false)
}

function onClassChanged() {
  students.value = []
  sessionClassName.value = ''
  loadAttendance(false)
}

function setToday() {
  selectedDate.value = ''
  loadAttendance(false)
}

async function fetchClasses() {
  try {
    const res: any = await $fetch(`${config.public.apiBase}/classes`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    if (Array.isArray(res) && res.length > 0) {
      allClasses.value = res
      if (!selectedClassId.value) {
        if (authStore.user?.role === 'GURU_PEMBIMBING') {
          selectedClassId.value = String(authStore.user.guided_class?.id || authStore.user.class_id || res[0]?.id || '')
        } else if (canSelectClass.value) {
          selectedClassId.value = String(authStore.user?.class_id || res[0]?.id || '')
        }
      }
    }
  } catch (e) {
    console.error('Error fetching classes:', e)
  }
}
const searchQuery = ref('')
const pin = ref('')
const sessionStatus = ref('DRAFT')
const verificationStatus = ref('BELUM_KONFIRMASI')
const isLockedForUser = computed(() => {
  // Jika hari libur akhir pekan (Sabtu/Minggu), seluruh pengisian presensi dikunci (kecuali superadmin)
  if (isWeekendSelected.value && authStore.user?.role !== 'SUPERADMIN') return true
  // Walas dilarang mengedit/mengubah status presensi murid (hanya bertugas ACC pengesahan)
  if (isWalas.value) return true
  if (authStore.user?.role === 'SISWA') return true
  if (sessionStatus.value === 'LOCKED') {
    return authStore.user?.role !== 'SUPERADMIN'
  }
  return !['SEKRETARIS', 'SUPERADMIN'].includes(authStore.user?.role || '')
})
const isSubmitting = ref(false)
const isLoading = ref(true)
const isRefreshing = ref(false)
const loadError = ref('')
const toastMessage = ref('')
const toastError = ref(false)

const students = ref<any[]>([])

const summary = computed(() => {
  const total = students.value.length
  const belumAbsen = students.value.filter(s => s.status === 'BELUM_ABSEN' || !s.status).length
  const hadir = students.value.filter(s => s.status === 'HADIR').length
  const sakit = students.value.filter(s => s.status === 'SAKIT').length
  const izin = students.value.filter(s => s.status === 'IZIN').length
  const alpha = students.value.filter(s => s.status === 'ALPHA').length
  return { total, belumAbsen, hadir, sakit, izin, alpha }
})

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value
  const q = searchQuery.value.toLowerCase()
  return students.value.filter(s => s.name?.toLowerCase().includes(q) || s.nis?.includes(q))
})

function setStudentStatus(student: any, status: string) {
  student.status = status
}

function exportExcel() {
  if (!students.value || students.value.length === 0) {
    showToast('Tidak ada data siswa untuk diekspor.', true)
    return
  }

  const dateStr = selectedDate.value
  const className = displayClassName.value

  const headers = ['No', 'Nama Siswa', 'NIS / NISN', 'Status Kehadiran', 'Keterangan']
  const rows = students.value.map((s, idx) => [
    idx + 1,
    `"${(s.name || '').replace(/"/g, '""')}"`,
    `"${(s.nis || '').replace(/"/g, '""')}"`,
    `"${s.status === 'BELUM_ABSEN' ? 'Belum Absen' : (s.status || '-')}"`,
    `"${(s.note || '').replace(/"/g, '""')}"`
  ])

  const metaRows = [
    `"AGENDA PRESENSI KELAS - ${appConfig.school_name || 'SMK NEGERI 71 JAKARTA'}"`,
    `"Kelas: ${className}"`,
    `"Tanggal: ${formatReadableDate(dateStr)}"`,
    `"Total: ${summary.value.total}; Hadir: ${summary.value.hadir}; Sakit: ${summary.value.sakit}; Izin: ${summary.value.izin}; Alfa: ${summary.value.alpha}; Belum Absen: ${summary.value.belumAbsen}"`,
    ''
  ]

  const csvRows = ['sep=;', ...metaRows, headers.join(';'), ...rows.map(e => e.join(';'))].join('\r\n')
  const blob = new Blob(['\uFEFF' + csvRows], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `agenda_presensi_${className.replace(/\s+/g, '_')}_${dateStr}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
  showToast('File Excel agenda presensi berhasil diunduh.')
}

function exportWord() {
  if (!students.value || students.value.length === 0) {
    showToast('Tidak ada data siswa untuk diekspor.', true)
    return
  }

  const dateStr = selectedDate.value
  const className = displayClassName.value
  const formattedDate = formatReadableDate(dateStr)

  const rowsHtml = students.value.map((s, idx) => `
    <tr>
      <td style="border:1px solid #ddd; padding:8px; text-align:center;">${idx + 1}</td>
      <td style="border:1px solid #ddd; padding:8px;">${s.name || '-'}</td>
      <td style="border:1px solid #ddd; padding:8px; text-align:center;">${s.nis || '-'}</td>
      <td style="border:1px solid #ddd; padding:8px; text-align:center; font-weight:bold;">${s.status === 'BELUM_ABSEN' ? 'Belum Absen' : (s.status || '-')}</td>
      <td style="border:1px solid #ddd; padding:8px;">${s.note || '-'}</td>
    </tr>
  `).join('')

  const wordHtml = `
    <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
    <head>
      <meta charset='utf-8'>
      <title>Agenda Presensi Kelas</title>
      <style>
        body { font-family: 'Calibri', 'Arial', sans-serif; font-size: 11pt; color: #333; line-height: 1.4; }
        .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop h2 { margin: 0; font-size: 16pt; color: #0f47ad; }
        .kop h3 { margin: 5px 0 0 0; font-size: 13pt; }
        .meta-table { width: 100%; margin-bottom: 15px; }
        .meta-table td { padding: 4px 0; font-size: 10.5pt; }
        .summary-box { background: #f0f4ff; border: 1px solid #c7d7fe; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th { background: #0f47ad; color: #fff; border: 1px solid #0f47ad; padding: 8px; font-size: 10pt; text-align: center; }
        .ttd-table { width: 100%; margin-top: 30px; }
        .ttd-table td { text-align: center; width: 50%; padding-top: 10px; font-size: 11pt; }
      </style>
    </head>
    <body>
      <div class="kop">
        <h2>${appConfig.school_name || 'SMK NEGERI 71 JAKARTA'}</h2>
        <h3>AGENDA & REKAPITULASI PRESENSI HARIAN KELAS</h3>
        <p style="margin:2px 0 0 0; font-size:9pt; color:#666;">${appConfig.school_address || 'Jl. Raden Inten II No. 71, Duren Sawit, Jakarta Timur'} • NPSN: ${appConfig.school_npsn || '20104567'}</p>
      </div>

      <table class="meta-table">
        <tr>
          <td style="width:15%;"><strong>Kelas</strong></td>
          <td style="width:35%;">: ${className}</td>
          <td style="width:20%;"><strong>Hari / Tanggal</strong></td>
          <td style="width:30%;">: ${formattedDate}</td>
        </tr>
        <tr>
          <td><strong>Jam Sekolah</strong></td>
          <td>: ${appConfig.default_school_hours || '06:45 - 15:00'} WIB</td>
          <td><strong>Status Verifikasi</strong></td>
          <td>: ${verificationStatus.value === 'TERVERIFIKASI' ? 'Disahkan Wali Kelas' : 'Menunggu Konfirmasi'}</td>
        </tr>
      </table>

      <div class="summary-box">
        <strong>Ringkasan Kehadiran:</strong> 
        Total: ${summary.value.total} Siswa | 
        Hadir: ${summary.value.hadir} | 
        Sakit: ${summary.value.sakit} | 
        Izin: ${summary.value.izin} | 
        Alfa: ${summary.value.alpha} | 
        Belum Absen: ${summary.value.belumAbsen}
      </div>

      <table class="data-table">
        <thead>
          <tr>
            <th style="width:5%;">No</th>
            <th style="width:35%;">Nama Lengkap Siswa</th>
            <th style="width:20%;">NIS / NISN</th>
            <th style="width:15%;">Status</th>
            <th style="width:25%;">Keterangan / Waktu</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml}
        </tbody>
      </table>

      <table class="ttd-table">
        <tr>
          <td>Mengetahui,<br><strong>Wali Kelas</strong><br><br><br><br>( .................................................... )</td>
          <td>Jakarta, ${formattedDate}<br><strong>Sekretaris Kelas</strong><br><br><br><br>( ${authStore.user?.name || '....................................................'} )</td>
        </tr>
      </table>
    </body>
    </html>
  `

  const blob = new Blob(['\uFEFF' + wordHtml], { type: 'application/msword;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `agenda_presensi_${className.replace(/\s+/g, '_')}_${dateStr}.doc`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
  showToast('File Word agenda presensi berhasil diunduh.')
}

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

async function loadAttendance(silent = false) {
  if (!silent && !students.value.length) {
    isLoading.value = true
  } else {
    isRefreshing.value = true
  }
  loadError.value = ''

  let token = getAuthToken()
  if (!token) {
    // Jeda sejenak mengantisipasi Pinia store hydrating dari localStorage
    await new Promise(r => setTimeout(r, 100))
    token = getAuthToken()
  }

  if (!token) {
    isLoading.value = false
    isRefreshing.value = false
    loadError.value = 'Sesi autentikasi belum siap. Silakan klik tombol Coba Lagi.'
    return
  }

  try {
    const params: any = {
      class_id: targetClassId.value,
      _t: Date.now()
    }
    if (selectedDate.value) {
      params.date = selectedDate.value
    }
    const res = await $fetch<any>(`${authStore.apiBase}/attendance/today`, {
      headers: { Authorization: `Bearer ${token}` },
      params
    })

    if (!selectedDate.value && (res.server_date || res.session?.date)) {
      selectedDate.value = res.server_date || res.session?.date
    }

    students.value = (res.students || []).map((s: any) => ({
      ...s,
      note: (s.note === 'Menunggu pencatatan presensi kelas') ? '' : (s.note || '')
    }))

    if (res.session) {
      sessionStatus.value = res.session.status || 'DRAFT'
      verificationStatus.value = res.session.verification_status || 'BELUM_KONFIRMASI'
      if (res.session.class_name) {
        sessionClassName.value = res.session.class_name
      }
      if (res.session.date) {
        dateFormatted.value = formatReadableDate(res.session.date)
      } else if (selectedDate.value) {
        dateFormatted.value = formatReadableDate(selectedDate.value)
      }
    } else if (selectedDate.value) {
      dateFormatted.value = formatReadableDate(selectedDate.value)
    }
  } catch (e: any) {
    console.error('Attendance fetch error:', e)
    if (!students.value.length) {
      loadError.value = e?.data?.message || 'Gagal memuat data presensi dari server. Periksa koneksi Anda.'
    }
  } finally {
    isLoading.value = false
    isRefreshing.value = false
  }
}

async function handleDraft() {
  if (isWeekendSelected.value) {
    toastError.value = true
    toastMessage.value = 'Presensi tidak dapat dicatat pada hari libur akhir pekan (Sabtu/Minggu).'
    return
  }
  isSubmitting.value = true
  toastMessage.value = ''
  toastError.value = false
  const token = getAuthToken()
  try {
    await $fetch(`${authStore.apiBase}/attendance/draft`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: {
        class_id: Number(targetClassId.value) || 1,
        date: selectedDate.value,
        records: students.value.map(s => ({ student_id: s.student_id, status: s.status, note: s.note }))
      }
    })
    toastMessage.value = 'Draft presensi berhasil disimpan!'
    sessionStatus.value = 'DRAFT'
    loadAttendance(true)
  } catch (e: any) {
    toastError.value = true
    toastMessage.value = e?.data?.message || 'Gagal menyimpan draft presensi.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleLock() {
  if (isWeekendSelected.value) {
    toastError.value = true
    toastMessage.value = 'Presensi tidak dapat dikunci pada hari libur akhir pekan (Sabtu/Minggu).'
    return
  }
  if (!pin.value) {
    toastError.value = true
    toastMessage.value = 'Silakan masukkan password akun Anda untuk mengunci.'
    return
  }
  const ok = await confirmDialog({
    title: 'Kunci Presensi Kelas',
    message: `Kunci presensi kelas ${displayClassName.value} tanggal ${dateFormatted.value}? Setelah dikunci, data akan diserahkan kepada Wali Kelas untuk diverifikasi resmi.`,
    confirmText: 'Ya, Kunci Sekarang',
    type: 'primary'
  })
  if (!ok) return

  isSubmitting.value = true
  toastMessage.value = ''
  toastError.value = false
  const token = getAuthToken()
  try {
    await $fetch(`${authStore.apiBase}/attendance/lock`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: {
        class_id: Number(targetClassId.value) || 1,
        date: selectedDate.value,
        pin: pin.value,
        records: students.value.map(s => ({ student_id: s.student_id, status: s.status, note: s.note }))
      }
    })
    toastMessage.value = 'Presensi berhasil dikunci & terverifikasi tingkat sekretaris!'
    sessionStatus.value = 'LOCKED'
    verificationStatus.value = 'PENDING'
    pin.value = ''
    loadAttendance(true)
  } catch (e: any) {
    toastError.value = true
    toastMessage.value = e?.data?.message || 'Gagal mengunci presensi. Periksa PIN/password Anda.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleConfirmTeacher() {
  if (isWeekendSelected.value) {
    toastError.value = true
    toastMessage.value = 'Presensi tidak dapat disahkan pada hari libur akhir pekan (Sabtu/Minggu).'
    return
  }
  const ok = await confirmDialog({
    title: 'Sahkan Presensi Harian',
    message: `Sahkan dan validasi rekap presensi kelas ${displayClassName.value} tanggal ${dateFormatted.value}?`,
    confirmText: 'Ya, Sahkan Presensi',
    type: 'primary'
  })
  if (!ok) return

  isSubmitting.value = true
  toastMessage.value = ''
  toastError.value = false
  const token = getAuthToken()
  try {
    const res: any = await $fetch(`${authStore.apiBase}/attendance/confirm`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: {
        class_id: Number(targetClassId.value) || 1,
        date: targetDate.value,
      }
    })
    toastMessage.value = res.message || 'Presensi harian berhasil disahkan!'
    verificationStatus.value = 'TERVERIFIKASI'
    loadAttendance(true)
  } catch (e: any) {
    toastError.value = true
    toastMessage.value = e?.data?.message || 'Gagal mengesahkan presensi.'
  } finally {
    isSubmitting.value = false
  }
}


async function handleForceUnlockSuperadmin() {
  const ok = await confirmDialog({
    title: 'Buka Kunci Presensi',
    message: 'Buka kunci sesi presensi ini kembali ke status DRAFT agar dapat diedit ulang oleh Sekretaris?',
    confirmText: 'Ya, Buka Kunci',
    type: 'danger'
  })
  if (!ok) return
  isSubmitting.value = true
  toastMessage.value = ''
  toastError.value = false
  try {
    const token = getAuthToken()
    const res: any = await $fetch(`${authStore.apiBase}/attendance/force-unlock`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: {
        class_id: Number(targetClassId.value) || 1,
        date: selectedDate.value,
      }
    })
    toastMessage.value = res?.message || 'Sesi presensi berhasil dibuka kembali ke status DRAFT!'
    sessionStatus.value = 'DRAFT'
    verificationStatus.value = 'BELUM_KONFIRMASI'
    await loadAttendance(true)
  } catch (e: any) {
    toastError.value = true
    toastMessage.value = e?.data?.message || 'Gagal membuka kunci sesi presensi.'
  } finally {
    isSubmitting.value = false
  }
}

let presensiPollTimer: any = null

function handlePresensiFocus() {
  if (typeof document !== 'undefined' && document.visibilityState === 'visible' && !isSubmitting.value && !isDrafting.value) {
    loadAttendance(true)
  }
}

onMounted(async () => {
  if (process.client) {
    try {
      await authStore.fetchUser()
    } catch (e) {
      // ignore
    }
  }
  if (!appConfig.loaded) {
    await appConfig.fetchConfig()
  }
  await fetchClasses()
  loadAttendance()
  if (process.client) {
    window.addEventListener('focus', handlePresensiFocus)
    document.addEventListener('visibilitychange', handlePresensiFocus)
    // Sinkronisasi realtime presensi tiap 15 detik jika tidak sedang menyunting
    presensiPollTimer = setInterval(() => {
      if (document.visibilityState === 'visible' && !isSubmitting.value && !isDrafting.value) {
        loadAttendance(true)
      }
    }, 15000)
  }
})

onUnmounted(() => {
  if (presensiPollTimer) clearInterval(presensiPollTimer)
  if (process.client) {
    window.removeEventListener('focus', handlePresensiFocus)
    document.removeEventListener('visibilitychange', handlePresensiFocus)
  }
})
</script>
