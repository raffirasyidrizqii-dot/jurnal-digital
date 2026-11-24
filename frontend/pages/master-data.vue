<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Master Data Akademik"
      subtitle="Manajemen data kelas, rombongan belajar, mata pelajaran, dan jadwal KBM"
    />

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Tabs Navigation -->
      <div class="flex items-center gap-6 border-b border-gray-200 dark:border-slate-800">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          @click="activeTab = tab.id"
          class="pb-3.5 text-xs sm:text-sm font-bold transition relative cursor-pointer flex items-center gap-2"
          :class="activeTab === tab.id ? 'text-primary dark:text-blue-400' : 'text-gray-500 hover:text-gray-800 dark:text-slate-400 dark:hover:text-slate-200'"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          <span>{{ tab.label }}</span>
          <span
            v-if="activeTab === tab.id"
            class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary dark:bg-blue-500 rounded-full"
          ></span>
        </button>
      </div>

      <!-- Alerts -->
      <div v-if="successMsg" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-emerald-600 dark:text-emerald-300 hover:text-emerald-800 text-xs font-bold cursor-pointer"><X class="w-3.5 h-3.5" /></button>
      </div>

      <div v-if="errorMsg" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <AlertCircle class="w-4 h-4 text-rose-600 dark:text-rose-400" />
          <span>{{ errorMsg }}</span>
        </div>
        <button @click="errorMsg = ''" class="text-rose-600 dark:text-rose-300 hover:text-rose-800 text-xs font-bold cursor-pointer"><X class="w-3.5 h-3.5" /></button>
      </div>

      <!-- ============================================== -->
      <!-- TAB 1: KELAS               -->
      <!-- ============================================== -->
      <div v-if="activeTab === 'classes'" class="space-y-4">
        <!-- Top Row: Title & Action Buttons -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Daftar Kelas</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Kelola kelas, penugasan wali kelas, dan sekretaris</p>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <button
              @click="openAddClassModal"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer whitespace-nowrap"
            >
              <Plus class="w-4 h-4" />
              <span>Tambah Kelas</span>
            </button>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden">
          <!-- Card Header: Program Keahlian & Stats -->
          <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 sm:p-4 border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-850">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-[11px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider px-1">Program Keahlian:</span>
              <div class="inline-flex items-center bg-gray-100 dark:bg-slate-800 p-1 rounded-xl text-xs select-none">
                <button
                  type="button"
                  @click="classMajorFilter = 'ALL'"
                  class="px-3 py-1.5 text-center font-bold text-xs rounded-lg transition-colors cursor-pointer whitespace-nowrap"
                  :class="classMajorFilter === 'ALL' ? 'bg-white dark:bg-slate-700 text-primary dark:text-white shadow-2xs font-bold border border-blue-200 dark:border-slate-600' : 'text-gray-500 dark:text-slate-400 hover:text-primary dark:hover:text-white'"
                >
                  Semua
                </button>
                <button
                  v-for="m in uniqueMajors"
                  :key="m"
                  type="button"
                  @click="classMajorFilter = m"
                  class="px-3 py-1.5 text-center font-bold text-xs rounded-lg transition-colors cursor-pointer whitespace-nowrap"
                  :class="classMajorFilter === m ? 'bg-white dark:bg-slate-700 text-primary dark:text-white shadow-2xs font-bold border border-blue-200 dark:border-slate-600' : 'text-gray-500 dark:text-slate-400 hover:text-primary dark:hover:text-white'"
                >
                  {{ m }}
                </button>
              </div>
            </div>

            <div class="text-xs text-gray-500 dark:text-slate-400 font-medium px-2">
              Total <span class="font-bold text-gray-900 dark:text-white">{{ filteredClasses.length }}</span> Kelas
            </div>
          </div>
          <div v-if="loadingClasses" class="py-12 text-center text-gray-400 dark:text-slate-500 text-xs">
            Memuat data kelas...
          </div>
          <div v-else>
          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3 p-4">
            <div
              v-for="(cls, idx) in filteredClasses"
              :key="cls.id"
              class="bg-white dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs space-y-3"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-400 dark:text-slate-500">#{{ idx + 1 }}</span>
                    <span class="font-bold text-gray-900 dark:text-white text-sm">{{ cls.name }}</span>
                  </div>
                  <div class="mt-1">
                    <span
                      class="px-2 py-0.5 rounded-md text-[10px] font-bold border inline-block"
                      :class="cls.name.toLowerCase().includes('industri') ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50' : 'bg-primary/10 dark:bg-blue-950/40 text-primary dark:text-blue-300 border-primary/20 dark:border-blue-800/50'"
                    >
                      {{ cls.major || 'Umum' }}{{ cls.name.toLowerCase().includes('industri') ? ' (Kelas Industri)' : '' }}
                    </span>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-primary dark:text-blue-400 text-xs">{{ cls.students_count || 0 }} Siswa</span>
                  <span class="block text-[10px] text-gray-400 dark:text-slate-500">{{ cls.schedules_count || 0 }} Mapel</span>
                </div>
              </div>

              <div class="p-3 bg-gray-50/70 dark:bg-slate-900/60 rounded-xl space-y-1.5 text-xs text-gray-600 dark:text-slate-300">
                <p><span class="text-gray-400 dark:text-slate-500">Wali Kelas:</span> <strong class="text-gray-800 dark:text-slate-200">{{ cls.advisor?.name || '-' }}</strong></p>
                <p><span class="text-gray-400 dark:text-slate-500">Sekretaris:</span> <strong class="text-gray-800 dark:text-slate-200">{{ cls.secretary?.name || '-' }}</strong></p>
              </div>

              <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-slate-800">
                <button
                  @click="editClass(cls)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer"
                  title="Edit Kelas"
                >
                  <Edit class="w-4 h-4" />
                </button>
                <button
                  @click="deleteClass(cls)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl border border-rose-200 dark:border-rose-800/50 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                  title="Hapus Kelas"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left text-xs border-collapse min-w-[850px]">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                  <th class="py-3 px-4 w-12 text-center whitespace-nowrap">No</th>
                  <th class="py-3 px-4 whitespace-nowrap">Nama Kelas</th>
                  <th class="py-3 px-4 whitespace-nowrap">Jurusan</th>
                  <th class="py-3 px-4 whitespace-nowrap">Wali Kelas</th>
                  <th class="py-3 px-4 whitespace-nowrap">Sekretaris Kelas</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Jumlah Siswa</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Total Jadwal</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <tr v-for="(cls, idx) in filteredClasses" :key="cls.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                  <td class="py-3.5 px-4 text-center font-medium text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ idx + 1 }}</td>
                  <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-slate-100 text-sm whitespace-nowrap">{{ cls.name }}</td>
                  <td class="py-3.5 px-4 whitespace-nowrap">
                    <span
                      class="px-2.5 py-1 rounded-lg text-[10px] font-bold border whitespace-nowrap"
                      :class="cls.name.toLowerCase().includes('industri') ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50' : 'bg-primary/10 dark:bg-blue-950/40 text-primary dark:text-blue-300 border-primary/20 dark:border-blue-800/50'"
                    >
                      {{ cls.major || 'Umum' }}{{ cls.name.toLowerCase().includes('industri') ? ' (Kelas Industri)' : '' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-gray-700 dark:text-slate-300 font-medium whitespace-nowrap">{{ cls.advisor?.name || '-' }}</td>
                  <td class="py-3.5 px-4 text-gray-700 dark:text-slate-300 whitespace-nowrap">{{ cls.secretary?.name || '-' }}</td>
                  <td class="py-3.5 px-4 text-center font-bold text-primary dark:text-blue-400 whitespace-nowrap">{{ cls.students_count || 0 }} Siswa</td>
                  <td class="py-3.5 px-4 text-center font-semibold text-gray-600 dark:text-slate-400 whitespace-nowrap">{{ cls.schedules_count || 0 }} Mapel</td>
                  <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                      <button
                        @click="editClass(cls)"
                        class="p-1.5 rounded-lg border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="Edit Kelas"
                      >
                        <Edit class="w-3.5 h-3.5" />
                      </button>
                      <button
                        @click="deleteClass(cls)"
                        class="p-1.5 rounded-lg border border-rose-200 dark:border-rose-800/50 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                        title="Hapus Kelas"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          </div>
        </div>
      </div>

      <!-- ============================================== -->
      <!-- TAB 2: MATA PELAJARAN (SUBJECTS)               -->
      <!-- ============================================== -->
      <div v-else-if="activeTab === 'subjects'" class="space-y-4">
        <!-- Top Row: Title & Action Button -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Master Mata Pelajaran & Pengajar</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Daftar mata pelajaran resmi beserta kode singkatan dan guru pengampunya</p>
          </div>
          <button
            @click="openAddSubjectModal"
            class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer flex-shrink-0 whitespace-nowrap"
          >
            <Plus class="w-4 h-4" />
            <span>Tambah Mapel</span>
          </button>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden">
          <!-- Card Header: Pencarian & Stats -->
          <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 sm:p-4 border-b border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-850">
            <div class="relative w-full sm:w-72">
              <Search class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
              <input
                v-model="subjectSearchQuery"
                type="text"
                placeholder="Cari mapel, kode, atau guru..."
                class="w-full pl-9 pr-3.5 py-1.5 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              />
            </div>

            <div class="text-xs text-gray-500 dark:text-slate-400 font-medium px-2">
              Menampilkan <span class="font-bold text-gray-900 dark:text-white">{{ filteredSubjects.length }}</span> dari {{ subjects.length }} Mapel
            </div>
          </div>
          <div v-if="loadingSubjects" class="py-12 text-center text-gray-400 dark:text-slate-500 text-xs">
            Memuat data mata pelajaran...
          </div>
          <div v-else>
          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3 p-4">
            <div
              v-for="(sub, idx) in filteredSubjects"
              :key="sub.id"
              class="bg-white dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs space-y-3"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-400 dark:text-slate-500">#{{ idx + 1 }}</span>
                    <span class="font-bold text-gray-900 dark:text-white text-sm">{{ sub.name }}</span>
                  </div>
                  <span class="font-mono text-xs font-bold text-primary dark:text-blue-400">{{ sub.code || '-' }}</span>
                </div>
              </div>

              <div class="p-3 bg-gray-50/70 dark:bg-slate-900/60 rounded-xl space-y-1.5 text-xs text-gray-600 dark:text-slate-300">
                <p><span class="text-gray-400 dark:text-slate-500">Guru Utama:</span> <strong class="text-gray-800 dark:text-slate-200">{{ sub.teacher?.name || '-' }}</strong></p>
                <p><span class="text-gray-400 dark:text-slate-500">Jadwal Aktif:</span> <strong class="text-gray-800 dark:text-slate-200">{{ sub.schedules_count || 0 }} Sesi KBM</strong></p>
              </div>

              <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-slate-800">
                <button
                  @click="editSubject(sub)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer"
                  title="Edit Mapel"
                >
                  <Edit class="w-4 h-4" />
                </button>
                <button
                  @click="deleteSubject(sub)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl border border-rose-200 dark:border-rose-800/50 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                  title="Hapus Mapel"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left text-xs border-collapse min-w-[850px]">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                  <th class="py-3 px-4 w-12 text-center whitespace-nowrap">No</th>
                  <th class="py-3 px-4 whitespace-nowrap">Kode</th>
                  <th class="py-3 px-4 whitespace-nowrap">Nama Mata Pelajaran</th>
                  <th class="py-3 px-4 whitespace-nowrap">Guru Pengampu</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Jadwal Aktif</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <tr v-for="(sub, idx) in filteredSubjects" :key="sub.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                  <td class="py-3.5 px-4 text-center font-medium text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ idx + 1 }}</td>
                  <td class="py-3.5 px-4 font-mono font-bold text-primary dark:text-blue-400 whitespace-nowrap">{{ sub.code || '-' }}</td>
                  <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-slate-100 whitespace-nowrap">{{ sub.name }}</td>
                  <td class="py-3.5 px-4 text-gray-700 dark:text-slate-300 font-medium whitespace-nowrap">
                    {{ sub.teacher?.name || '-' }}
                  </td>
                  <td class="py-3.5 px-4 text-center font-semibold text-gray-600 dark:text-slate-400 whitespace-nowrap">{{ sub.schedules_count || 0 }} Sesi</td>
                  <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                      <button
                        @click="editSubject(sub)"
                        class="p-1.5 rounded-lg border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="Edit Mapel"
                      >
                        <Edit class="w-3.5 h-3.5" />
                      </button>
                      <button
                        @click="deleteSubject(sub)"
                        class="p-1.5 rounded-lg border border-rose-200 dark:border-rose-800/50 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                        title="Hapus Mapel"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          </div>
        </div>
      </div>

      <!-- ============================================== -->
      <!-- TAB 3: JADWAL PELAJARAN (SCHEDULES)            -->
      <!-- ============================================== -->
      <div v-else-if="activeTab === 'schedules'" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Jadwal Mengajar Terpusat</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Plotting guru, kelas, hari, jam KBM, dan ruang belajar</p>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <select
              v-model="scheduleClassFilter"
              @change="fetchSchedules"
              class="px-3.5 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 cursor-pointer shadow-2xs"
            >
              <option value="">Semua Kelas</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <button
              @click="openAddScheduleModal"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
            >
              <Plus class="w-4 h-4" />
              <span>Tambah Jadwal Mengajar</span>
            </button>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden">
          <div v-if="loadingSchedules" class="py-12 text-center text-gray-400 dark:text-slate-500 text-xs">
            Memuat data jadwal pelajaran...
          </div>
          <div v-else>
          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3 p-4">
            <div
              v-for="(sch, idx) in schedules"
              :key="sch.id"
              class="bg-white dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs space-y-3"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <span class="font-bold text-sm text-gray-900 dark:text-white">{{ sch.subject?.name || '-' }}</span>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-xs font-bold text-primary dark:text-blue-400">{{ sch.school_class?.name || '-' }}</span>
                    <span class="text-gray-300 dark:text-slate-600">•</span>
                    <span class="text-[11px] text-gray-500 dark:text-slate-400 font-medium">{{ sch.room || 'Lab. 1' }}</span>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-xs text-gray-900 dark:text-white">{{ sch.day }}</span>
                  <span class="block text-[10px] text-gray-500 dark:text-slate-400 font-mono">{{ sch.start_time }} - {{ sch.end_time }}</span>
                </div>
              </div>

              <div class="text-xs text-gray-600 dark:text-slate-300">
                <span class="text-gray-400 dark:text-slate-500">Guru:</span> <strong class="text-gray-800 dark:text-slate-200">{{ sch.teacher?.name || '-' }}</strong>
              </div>

              <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-slate-800">
                <button
                  @click="editSchedule(sch)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer"
                  title="Edit Jadwal"
                >
                  <Edit class="w-4 h-4" />
                </button>
                <button
                  @click="deleteSchedule(sch)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl border border-rose-200 dark:border-rose-800/50 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                  title="Hapus Jadwal"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left text-xs border-collapse min-w-[850px]">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-100 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                  <th class="py-3 px-4 w-12 text-center whitespace-nowrap">No</th>
                  <th class="py-3 px-4 whitespace-nowrap">Hari & Jam</th>
                  <th class="py-3 px-4 whitespace-nowrap">Kelas</th>
                  <th class="py-3 px-4 whitespace-nowrap">Mata Pelajaran</th>
                  <th class="py-3 px-4 whitespace-nowrap">Guru Pengampu</th>
                  <th class="py-3 px-4 whitespace-nowrap">Ruang</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <tr v-for="(sch, idx) in schedules" :key="sch.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                  <td class="py-3.5 px-4 text-center font-medium text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ idx + 1 }}</td>
                  <td class="py-3.5 px-4 whitespace-nowrap">
                    <p class="font-bold text-gray-900 dark:text-white">{{ sch.day }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">{{ sch.start_time }} - {{ sch.end_time }}</p>
                  </td>
                  <td class="py-3.5 px-4 font-bold text-primary dark:text-blue-400 whitespace-nowrap">{{ sch.school_class?.name || '-' }}</td>
                  <td class="py-3.5 px-4 font-semibold text-gray-800 dark:text-slate-200 whitespace-nowrap">{{ sch.subject?.name || '-' }}</td>
                  <td class="py-3.5 px-4 text-gray-700 dark:text-slate-300 whitespace-nowrap">{{ sch.teacher?.name || '-' }}</td>
                  <td class="py-3.5 px-4 text-gray-500 dark:text-slate-400 font-medium whitespace-nowrap">{{ sch.room || 'Lab. 1' }}</td>
                  <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                      <button
                        @click="editSchedule(sch)"
                        class="p-1.5 rounded-lg border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="Edit Jadwal"
                      >
                        <Edit class="w-3.5 h-3.5" />
                      </button>
                      <button
                        @click="deleteSchedule(sch)"
                        class="p-1.5 rounded-lg border border-rose-200 dark:border-rose-800/50 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                        title="Hapus Jadwal"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          </div>
        </div>
      </div>

      <!-- ============================================== -->
      <!-- TAB 4: TAHUN AJARAN & SEMESTER (TERMS)         -->
      <!-- ============================================== -->
      <div v-else-if="activeTab === 'terms'" class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Manajemen Tahun Ajaran & Semester</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Tentukan kalender pendidikan dan semester yang aktif saat ini</p>
          </div>
          <button
            @click="openAddTermModal"
            class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer whitespace-nowrap"
          >
            <Plus class="w-4 h-4" />
            <span>Tambah Semester</span>
          </button>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden">
          <div v-if="loadingTerms" class="py-12 text-center text-gray-400 dark:text-slate-500 text-xs">
            Memuat data semester...
          </div>
          <div v-else>
          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3 p-4">
            <div
              v-for="(t, idx) in terms"
              :key="t.id"
              class="bg-white dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs space-y-3"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <span class="font-bold text-sm text-gray-900 dark:text-white">{{ t.name }}</span>
                  <p class="text-[11px] text-gray-500 dark:text-slate-400 font-mono mt-0.5">{{ t.start_date }} s.d. {{ t.end_date }}</p>
                </div>
                <span
                  v-if="t.is_active"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50"
                >
                  ● Sedang Aktif
                </span>
                <span
                  v-else
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 dark:bg-slate-800 text-gray-400 dark:text-slate-500 border border-gray-200 dark:border-slate-700"
                >
                  Arsip
                </span>
              </div>

              <div class="text-xs text-gray-600 dark:text-slate-300">
                <span class="text-gray-400 dark:text-slate-500">Hari Efektif Belajar:</span> <strong class="text-gray-800 dark:text-slate-200">{{ t.effective_days }} Hari</strong>
              </div>

              <div class="pt-2 border-t border-gray-100 dark:border-slate-800 flex items-center gap-2">
                <button
                  v-if="!t.is_active"
                  @click="activateTerm(t)"
                  class="flex-1 min-h-[38px] flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition cursor-pointer shadow-2xs"
                >
                  Set Aktif
                </button>
                <button
                  @click="openEditTermModal(t)"
                  class="flex-1 py-2 px-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 text-xs font-bold transition flex items-center justify-center gap-1 min-h-[38px]"
                >
                  <Pencil class="w-3.5 h-3.5" />
                  <span>Edit</span>
                </button>
                <button
                  @click="deleteTerm(t)"
                  class="py-2 px-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 text-xs font-bold transition flex items-center justify-center min-h-[38px] min-w-[38px]"
                  title="Hapus Semester"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left text-xs border-collapse min-w-[850px]">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-100 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                  <th class="py-3 px-4 w-12 text-center whitespace-nowrap">No</th>
                  <th class="py-3 px-4 whitespace-nowrap">Nama Tahun Ajaran & Semester</th>
                  <th class="py-3 px-4 whitespace-nowrap">Rentang Tanggal</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Hari Efektif</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <tr v-for="(t, idx) in terms" :key="t.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                  <td class="py-3.5 px-4 text-center font-medium text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ idx + 1 }}</td>
                  <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-slate-100 text-sm whitespace-nowrap">{{ t.name }}</td>
                  <td class="py-3.5 px-4 text-gray-600 dark:text-slate-400 font-mono whitespace-nowrap">{{ t.start_date }} s.d. {{ t.end_date }}</td>
                  <td class="py-3.5 px-4 text-center font-bold text-gray-800 dark:text-slate-200 whitespace-nowrap">{{ t.effective_days }} Hari</td>
                  <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <span
                      v-if="t.is_active"
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 whitespace-nowrap"
                    >
                      ● Sedang Aktif
                    </span>
                    <span
                      v-else
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 dark:bg-slate-800 text-gray-400 dark:text-slate-500 border border-gray-200 dark:border-slate-700 whitespace-nowrap"
                    >
                      Arsip
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-2">
                      <button
                        v-if="!t.is_active"
                        @click="activateTerm(t)"
                        class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 rounded-lg text-[10px] font-bold transition cursor-pointer whitespace-nowrap shadow-2xs"
                        title="Set Sebagai Semester Aktif"
                      >
                        Aktifkan
                      </button>
                      <span v-else class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200 text-[10px] font-bold rounded-md whitespace-nowrap">
                        Aktif Utama
                      </span>
                      <button
                        @click="openEditTermModal(t)"
                        class="p-1 rounded-lg text-primary dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="Edit Semester"
                      >
                        <Pencil class="w-3.5 h-3.5" />
                      </button>
                      <button
                        @click="deleteTerm(t)"
                        class="p-1 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                        title="Hapus Semester"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          </div>
        </div>
      </div>
    </main>

    <!-- MODAL FORM: KELAS -->
    <Teleport to="body">
      <div v-if="showClassModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl max-w-md w-full p-4 sm:p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto pb-safe border border-gray-150 dark:border-slate-800">
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto mb-2 sm:hidden shrink-0"></div>
          <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ editingClassId ? 'Edit Kelas' : 'Tambah Kelas' }}</h3>
            <button type="button" @click="showClassModal = false" class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition cursor-pointer" title="Tutup">
              <X class="w-5 h-5" />
            </button>
          </div>
        <form @submit.prevent="saveClass" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Nama Kelas</label>
            <input v-model="classForm.name" type="text" placeholder="Contoh: XII RPL 1" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Program Keahlian</label>
            <select v-model="classForm.major" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none font-semibold">
              <option value="RPL">Rekayasa Perangkat Lunak</option>
              <option value="DKV">Desain Komunikasi Visual</option>
              <option value="Animasi">Animasi</option>
              <option value="UMUM">Umum</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Wali Kelas</label>
            <select v-model="classForm.advisor_id" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none">
              <option value="">- Belum Ditentukan -</option>
              <option v-for="g in teachers" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Siswa Sekretaris Kelas</label>
            <select v-model="classForm.secretary_id" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none">
              <option value="">- Belum Ditentukan -</option>
              <option v-for="s in candidateSecretaries" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>

          <div class="pt-3 flex justify-end gap-2">
            <button type="button" @click="showClassModal = false" class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer">Batal</button>
            <button type="submit" :disabled="saving" class="px-5 py-2 bg-primary hover:bg-primary-hover transition-colors text-white font-bold rounded-xl cursor-pointer disabled:opacity-50">{{ saving ? 'Menyimpan...' : 'Simpan Kelas' }}</button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>

    <!-- MODAL FORM: MAPEL -->
    <Teleport to="body">
      <div v-if="showSubjectModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl max-w-md w-full p-4 sm:p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto pb-safe border border-gray-150 dark:border-slate-800">
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto mb-2 sm:hidden shrink-0"></div>
          <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ editingSubjectId ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' }}</h3>
            <button type="button" @click="showSubjectModal = false" class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition cursor-pointer" title="Tutup">
              <X class="w-5 h-5" />
            </button>
          </div>
        <form @submit.prevent="saveSubject" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Nama Mata Pelajaran</label>
            <input v-model="subjectForm.name" type="text" placeholder="Contoh: Pemrograman Web & Mobile" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Kode Singkatan</label>
            <input v-model="subjectForm.code" type="text" placeholder="Contoh: MKK, KIK, MPP, DPK, BIG" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary uppercase font-mono" />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Guru Pengampu</label>
            <select v-model="subjectForm.teacher_id" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none">
              <option value="">- Belum Ditugaskan -</option>
              <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }} ({{ t.role }})</option>
            </select>
          </div>

          <div class="pt-3 flex justify-end gap-2">
            <button type="button" @click="showSubjectModal = false" class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer">Batal</button>
            <button type="submit" :disabled="saving" class="px-5 py-2 bg-primary hover:bg-primary-hover transition-colors text-white font-bold rounded-xl cursor-pointer disabled:opacity-50">{{ saving ? 'Menyimpan...' : 'Simpan Mapel' }}</button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>

    <!-- MODAL FORM: JADWAL -->
    <Teleport to="body">
      <div v-if="showScheduleModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl max-w-md w-full p-4 sm:p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto pb-safe border border-gray-150 dark:border-slate-800">
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto mb-2 sm:hidden shrink-0"></div>
          <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ editingScheduleId ? 'Edit Jadwal Mengajar' : 'Tambah Jadwal Mengajar' }}</h3>
            <button type="button" @click="showScheduleModal = false" class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition cursor-pointer" title="Tutup">
              <X class="w-5 h-5" />
            </button>
          </div>
        <form @submit.prevent="saveSchedule" class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Pilih Kelas</label>
            <select v-model="scheduleForm.class_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none">
              <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Mata Pelajaran</label>
            <select v-model="scheduleForm.subject_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none">
              <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Guru Pengampu</label>
            <select v-model="scheduleForm.teacher_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none">
              <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Hari</label>
              <select v-model="scheduleForm.day" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none">
                <option value="Senin">Senin</option>
                <option value="Selasa">Selasa</option>
                <option value="Rabu">Rabu</option>
                <option value="Kamis">Kamis</option>
                <option value="Jumat">Jumat</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Ruangan</label>
              <input v-model="scheduleForm.room" type="text" placeholder="Ruang Kelas" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Jam Mulai</label>
              <input v-model="scheduleForm.start_time" type="time" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Jam Selesai</label>
              <input v-model="scheduleForm.end_time" type="time" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none" />
            </div>
          </div>
          <div class="pt-3 flex justify-end gap-2">
            <button type="button" @click="showScheduleModal = false" class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer">Batal</button>
            <button type="submit" :disabled="saving" class="px-5 py-2 bg-primary hover:bg-primary-hover transition-colors text-white font-bold rounded-xl cursor-pointer disabled:opacity-50">{{ saving ? 'Menyimpan...' : 'Simpan Jadwal' }}</button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>

    <!-- MODAL FORM: SEMESTER -->
    <Teleport to="body">
      <div v-if="showTermModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl max-w-md w-full p-4 sm:p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto pb-safe border border-gray-150 dark:border-slate-800">
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto mb-2 sm:hidden shrink-0"></div>
          <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ editingTermId ? 'Edit Semester & Tahun Ajaran' : 'Tambah Semester & Tahun Ajaran' }}</h3>
            <button type="button" @click="showTermModal = false" class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition cursor-pointer" title="Tutup">
              <X class="w-5 h-5" />
            </button>
          </div>
        <form @submit.prevent="saveTerm" class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Nama Semester</label>
            <input v-model="termForm.name" type="text" placeholder="Contoh: 2026/2027 - Genap" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Tanggal Mulai</label>
              <input v-model="termForm.start_date" type="date" lang="id-ID" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Tanggal Selesai</label>
              <input v-model="termForm.end_date" type="date" lang="id-ID" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs outline-none" />
            </div>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Jumlah Hari Efektif Belajar</label>
            <input v-model.number="termForm.effective_days" type="number" min="30" max="250" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 outline-none" />
          </div>
          <div class="flex items-center gap-2 pt-1">
            <input v-model="termForm.is_active" type="checkbox" id="termActive" class="rounded text-primary" />
            <label for="termActive" class="font-semibold text-gray-700 dark:text-slate-300 cursor-pointer">Jadikan semester aktif saat ini</label>
          </div>
          <div class="pt-3 flex justify-end gap-2">
            <button type="button" @click="showTermModal = false" class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer">Batal</button>
            <button type="submit" :disabled="saving" class="px-5 py-2 bg-primary hover:bg-primary-hover transition-colors text-white font-bold rounded-xl cursor-pointer disabled:opacity-50">{{ saving ? 'Menyimpan...' : (editingTermId ? 'Perbarui Semester' : 'Simpan Semester') }}</button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import {
  Layers,
  BookOpen,
  Calendar,
  Clock,
  Plus,
  Edit,
  Pencil,
  Trash2,
  CheckCircle2,
  AlertCircle,
  X,
  Search,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || '/api'

const tabs = [
  { id: 'classes', label: 'Kelas', icon: Layers },
  { id: 'subjects', label: 'Mata Pelajaran', icon: BookOpen },
  { id: 'schedules', label: 'Jadwal Mengajar', icon: Clock },
  { id: 'terms', label: 'Tahun Ajaran & Semester', icon: Calendar },
]

const activeTab = ref('classes')
const successMsg = ref('')
const errorMsg = ref('')
const saving = ref(false)

const classMajorFilter = ref('ALL')
const subjectSearchQuery = ref('')

// Data states
const classes = ref<any[]>([])
const subjects = ref<any[]>([])
const schedules = ref<any[]>([])
const terms = ref<any[]>([])
const teachers = ref<any[]>([])
const candidateSecretaries = ref<any[]>([])

const uniqueMajors = computed(() => {
  const set = new Set<string>()
  classes.value.forEach((c: any) => {
    let m = c.major
    if (m && (m.toLowerCase().includes('rekayasa') || m.toLowerCase().includes('perangkat'))) {
      m = 'RPL'
    }
    if (m) set.add(m)
  })
  return set.size ? Array.from(set) : ['RPL', 'DKV', 'Animasi']
})

const filteredClasses = computed(() => {
  if (classMajorFilter.value === 'ALL') return classes.value
  return classes.value.filter(c => {
    let m = c.major
    if (m && (m.toLowerCase().includes('rekayasa') || m.toLowerCase().includes('perangkat'))) {
      m = 'RPL'
    }
    return m === classMajorFilter.value
  })
})

const filteredSubjects = computed(() => {
  if (!subjectSearchQuery.value.trim()) return subjects.value
  const q = subjectSearchQuery.value.toLowerCase().trim()
  return subjects.value.filter(s =>
    (s.name && s.name.toLowerCase().includes(q)) ||
    (s.code && s.code.toLowerCase().includes(q)) ||
    (s.teacher?.name && s.teacher.name.toLowerCase().includes(q))
  )
})

const loadingClasses = ref(false)
const loadingSubjects = ref(false)
const loadingSchedules = ref(false)
const loadingTerms = ref(false)

// Modals
const showClassModal = ref(false)
const editingClassId = ref<number | null>(null)
const classForm = ref({ name: '', major: 'RPL', advisor_id: '', secretary_id: '' })

const showSubjectModal = ref(false)
const editingSubjectId = ref<number | null>(null)
const subjectForm = ref({ name: '', code: '', major: '', teacher_id: '' })

const showScheduleModal = ref(false)
const editingScheduleId = ref<number | null>(null)
const scheduleClassFilter = ref('')
const scheduleForm = ref({
  class_id: 1,
  subject_id: 1,
  teacher_id: 1,
  day: 'Senin',
  start_time: '07:30',
  end_time: '09:00',
  room: 'Lab. 1',
})

const showTermModal = ref(false)
const editingTermId = ref<number | null>(null)
const termForm = ref({
  name: '',
  start_date: '2026-07-15',
  end_date: '2026-12-20',
  effective_days: 108,
  is_active: false,
})

// Fetch methods
async function fetchClasses() {
  loadingClasses.value = true
  try {
    const res: any = await $fetch(`${apiBase}/master/classes`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    classes.value = res || []
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal memuat data kelas'
  } finally {
    loadingClasses.value = false
  }
}

async function fetchSubjects() {
  loadingSubjects.value = true
  try {
    const res: any = await $fetch(`${apiBase}/master/subjects`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    subjects.value = res || []
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal memuat mata pelajaran'
  } finally {
    loadingSubjects.value = false
  }
}

async function fetchSchedules() {
  loadingSchedules.value = true
  try {
    const url = scheduleClassFilter.value
      ? `${apiBase}/master/schedules?class_id=${scheduleClassFilter.value}`
      : `${apiBase}/master/schedules`
    const res: any = await $fetch(url, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    schedules.value = res || []
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal memuat jadwal pelajaran'
  } finally {
    loadingSchedules.value = false
  }
}

async function fetchTerms() {
  loadingTerms.value = true
  try {
    const res: any = await $fetch(`${apiBase}/master/terms`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    terms.value = res || []
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal memuat data semester'
  } finally {
    loadingTerms.value = false
  }
}

async function fetchUsersHelpers() {
  try {
    const res: any = await $fetch(`${apiBase}/users?per_page=100`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const list = res.data?.data || res.data || res || []
    teachers.value = list.filter((u: any) => u.role === 'GURU_PENGAJAR' || u.role === 'GURU_PEMBIMBING')
    candidateSecretaries.value = list.filter((u: any) => u.role === 'SEKRETARIS' || u.role === 'SISWA')
  } catch (e) {
    console.warn(e)
  }
}

// Handlers for Classes
function openAddClassModal() {
  editingClassId.value = null
  classForm.value = { name: '', major: 'RPL', advisor_id: '', secretary_id: '' }
  showClassModal.value = true
}

function editClass(cls: any) {
  editingClassId.value = cls.id
  classForm.value = {
    name: cls.name,
    major: cls.major || 'RPL',
    advisor_id: cls.advisor_id || '',
    secretary_id: cls.secretary_id || '',
  }
  showClassModal.value = true
}

async function saveClass() {
  saving.value = true
  try {
    const method = editingClassId.value ? 'PUT' : 'POST'
    const url = editingClassId.value ? `${apiBase}/master/classes/${editingClassId.value}` : `${apiBase}/master/classes`
    const res: any = await $fetch(url, {
      method,
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: classForm.value,
    })
    successMsg.value = res.message || 'Data kelas berhasil disimpan!'
    showClassModal.value = false
    fetchClasses()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menyimpan kelas.'
  } finally {
    saving.value = false
  }
}

async function deleteClass(cls: any) {
  const ok = await confirmDialog({
    title: 'Hapus Kelas?',
    message: `Hapus kelas ${cls.name}? Siswa di dalamnya akan dilepaskan keterikatannya.`,
    confirmText: 'Ya, Hapus Kelas',
    cancelText: 'Batal',
    type: 'danger',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${apiBase}/master/classes/${cls.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    successMsg.value = res.message || 'Kelas berhasil dihapus!'
    fetchClasses()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menghapus kelas.'
  }
}

// Handlers for Subjects
function openAddSubjectModal() {
  editingSubjectId.value = null
  subjectForm.value = { name: '', code: '', major: '', teacher_id: '' }
  showSubjectModal.value = true
}

function editSubject(sub: any) {
  editingSubjectId.value = sub.id
  subjectForm.value = {
    name: sub.name,
    code: sub.code || '',
    major: sub.major || '',
    teacher_id: sub.teacher_id || '',
  }
  showSubjectModal.value = true
}

async function saveSubject() {
  saving.value = true
  try {
    const method = editingSubjectId.value ? 'PUT' : 'POST'
    const url = editingSubjectId.value ? `${apiBase}/master/subjects/${editingSubjectId.value}` : `${apiBase}/master/subjects`
    const res: any = await $fetch(url, {
      method,
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: subjectForm.value,
    })
    successMsg.value = res.message || 'Mata pelajaran berhasil disimpan!'
    showSubjectModal.value = false
    fetchSubjects()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menyimpan mapel.'
  } finally {
    saving.value = false
  }
}

async function deleteSubject(sub: any) {
  const ok = await confirmDialog({
    title: 'Hapus Mata Pelajaran?',
    message: `Apakah Anda yakin ingin menghapus mata pelajaran "${sub.name}"?`,
    confirmText: 'Ya, Hapus Mapel',
    cancelText: 'Batal',
    type: 'danger',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${apiBase}/master/subjects/${sub.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    successMsg.value = res.message || 'Mapel berhasil dihapus!'
    fetchSubjects()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menghapus mapel.'
  }
}

// Handlers for Schedules
function openAddScheduleModal() {
  editingScheduleId.value = null
  scheduleForm.value = {
    class_id: classes.value[0]?.id || 1,
    subject_id: subjects.value[0]?.id || 1,
    teacher_id: teachers.value[0]?.id || 1,
    day: 'Senin',
    start_time: '07:30',
    end_time: '09:00',
    room: 'Lab. 1',
  }
  showScheduleModal.value = true
}

function editSchedule(sch: any) {
  editingScheduleId.value = sch.id
  scheduleForm.value = {
    class_id: sch.class_id,
    subject_id: sch.subject_id,
    teacher_id: sch.teacher_id,
    day: sch.day,
    start_time: sch.start_time,
    end_time: sch.end_time,
    room: sch.room,
  }
  showScheduleModal.value = true
}

async function saveSchedule() {
  saving.value = true
  try {
    const method = editingScheduleId.value ? 'PUT' : 'POST'
    const url = editingScheduleId.value ? `${apiBase}/master/schedules/${editingScheduleId.value}` : `${apiBase}/master/schedules`
    const res: any = await $fetch(url, {
      method,
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: scheduleForm.value,
    })
    successMsg.value = res.message || 'Jadwal mengajar berhasil disimpan!'
    showScheduleModal.value = false
    fetchSchedules()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menyimpan jadwal.'
  } finally {
    saving.value = false
  }
}

async function deleteSchedule(sch: any) {
  const ok = await confirmDialog({
    title: 'Hapus Jadwal Mengajar?',
    message: 'Apakah Anda yakin ingin menghapus jadwal mengajar ini dari sistem?',
    confirmText: 'Ya, Hapus Jadwal',
    cancelText: 'Batal',
    type: 'danger',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${apiBase}/master/schedules/${sch.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    successMsg.value = res.message || 'Jadwal berhasil dihapus!'
    fetchSchedules()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menghapus jadwal.'
  }
}

// Handlers for Terms
function openAddTermModal() {
  editingTermId.value = null
  termForm.value = {
    name: '',
    start_date: '2026-07-15',
    end_date: '2026-12-20',
    effective_days: 108,
    is_active: false,
  }
  showTermModal.value = true
}

function openEditTermModal(t: any) {
  editingTermId.value = t.id
  termForm.value = {
    name: t.name || '',
    start_date: t.start_date ? String(t.start_date).slice(0, 10) : '',
    end_date: t.end_date ? String(t.end_date).slice(0, 10) : '',
    effective_days: t.effective_days || 108,
    is_active: !!t.is_active,
  }
  showTermModal.value = true
}

async function saveTerm() {
  saving.value = true
  try {
    const url = editingTermId.value ? `${apiBase}/master/terms/${editingTermId.value}` : `${apiBase}/master/terms`
    const method = editingTermId.value ? 'PUT' : 'POST'
    const res: any = await $fetch(url, {
      method,
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: termForm.value,
    })
    successMsg.value = res.message || (editingTermId.value ? 'Semester berhasil diperbarui!' : 'Semester baru berhasil dibuat!')
    showTermModal.value = false
    fetchTerms()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menyimpan semester.'
  } finally {
    saving.value = false
  }
}

async function deleteTerm(t: any) {
  if (t.is_active) {
    await confirmDialog({
      title: 'Semester Aktif Tidak Dapat Dihapus',
      message: `Semester "${t.name}" saat ini sedang berstatus AKTIF. Sistem melarang penghapusan semester aktif agar tidak merusak integritas presensi dan jurnal. Silakan aktifkan semester lain terlebih dahulu sebelum menghapus semester ini.`,
      confirmText: 'Mengerti',
      cancelText: '',
      type: 'danger',
    })
    return
  }
  const ok = await confirmDialog({
    title: 'Hapus Semester & Tahun Ajaran',
    message: `Apakah Anda yakin ingin menghapus semester "${t.name}"? Data ini akan dihapus permanen.`,
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal',
    type: 'danger',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${apiBase}/master/terms/${t.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    successMsg.value = res.message || 'Semester berhasil dihapus!'
    fetchTerms()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menghapus semester.'
  }
}

async function activateTerm(t: any) {
  const ok = await confirmDialog({
    title: 'Aktifkan Semester?',
    message: `Jadikan "${t.name}" sebagai semester aktif sistem saat ini? Semua transaksi presensi dan jurnal akan mengacu ke semester ini.`,
    confirmText: 'Ya, Aktifkan',
    cancelText: 'Batal',
    type: 'primary',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${apiBase}/master/terms/${t.id}/activate`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    successMsg.value = res.message || 'Semester aktif berhasil diubah!'
    fetchTerms()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal mengaktifkan semester.'
  }
}

onMounted(() => {
  fetchClasses()
  fetchSubjects()
  fetchSchedules()
  fetchTerms()
  fetchUsersHelpers()
})
</script>
