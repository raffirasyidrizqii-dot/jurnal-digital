<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      :title="authStore.user?.role === 'ORANG_TUA' ? 'Jurnal Belajar Ananda' : 'Jurnal Belajar Siswa'"
      :subtitle="authStore.user?.role === 'ORANG_TUA' ? 'Pantauan aktivitas pembelajaran harian ananda di kelas dan materi dari guru' : 'Catatan materi dan refleksi kegiatan pembelajaran di kelas'"
    >
      <template #actions>
        <button
          v-if="isStudentOrSekre"
          type="button"
          @click="showAddActivityModal = true"
          class="px-2.5 sm:px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 sm:gap-2 cursor-pointer whitespace-nowrap shrink-0 min-h-[34px] sm:min-h-[40px]"
          title="Tambah Catatan Belajar"
        >
          <Plus class="w-4 h-4 shrink-0" />
          <span class="hidden sm:inline">Tambah Catatan Belajar</span>
          <span class="sm:hidden text-[11px] font-bold">Catat</span>
        </button>
      </template>
    </AppHeader>

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Success Toast -->
      <div v-if="successMsg" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 text-xs font-bold cursor-pointer"><X class="w-3.5 h-3.5" /></button>
      </div>

      <!-- Filter Bar & Schedule Pop-up Button -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
          <MonthYearPicker v-model="selectedMonth" @change="fetchAll" />

          <div class="relative w-full sm:w-80">
            <Search class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="search"
              type="text"
              placeholder="Cari materi atau mata pelajaran..."
              class="w-full h-11 pl-10 pr-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs"
            />
          </div>
        </div>

        <button
          type="button"
          @click="showScheduleModal = true"
          class="inline-flex items-center justify-center gap-2 px-4 h-11 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-primary text-slate-700 dark:text-slate-200 hover:text-primary dark:hover:text-blue-400 rounded-xl text-xs sm:text-sm font-semibold shadow-2xs hover:shadow-xs transition cursor-pointer shrink-0 whitespace-nowrap"
        >
          <Calendar class="w-4 h-4 text-primary dark:text-blue-400" />
          <span>Jadwal Pelajaran</span>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-bold bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50">
            {{ allSchedules.length }} Mapel
          </span>
        </button>
      </div>

      <!-- Catatan Kegiatan Belajar Siswa di Sekolah -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
              {{ isStudentOrSekre ? 'Catatan Belajar Siswa' : (authStore.user?.role === 'ORANG_TUA' ? 'Catatan Belajar Ananda' : 'Catatan Kegiatan Belajar') }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Refleksi materi dan kegiatan pembelajaran yang telah dilaporkan siswa
            </p>
          </div>

          <div v-if="isStudentOrSekre" class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
              <User class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" />
              <span>Catatan Pribadi</span>
            </span>
          </div>
        </div>

        <div v-if="filteredActivities.length === 0" class="py-8 text-center text-xs text-gray-400 dark:text-slate-500">
          Belum ada catatan jurnal kegiatan siswa untuk periode ini.
        </div>

        <div v-else>
          <!-- Mobile Card List for Activities -->
          <div class="block md:hidden space-y-3">
            <div
              v-for="act in filteredActivities"
              :key="'mob-act-' + act.id"
              class="p-4 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-850 shadow-2xs space-y-3"
            >
              <div class="flex items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-2.5">
                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ act.date_time }}</span>
                <StatusBadge :status="act.status" />
              </div>
              <div>
                <h4 class="font-bold text-primary dark:text-blue-400 text-sm">{{ act.subject }}</h4>
                <p v-if="!isStudentOrSekre" class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Siswa: <span class="font-semibold text-gray-700 dark:text-slate-200">{{ act.created_by }}</span></p>
              </div>
              <div class="bg-gray-50/70 dark:bg-slate-800/40 p-3 rounded-xl border border-gray-100 dark:border-slate-750 space-y-1 text-xs">
                <p class="text-gray-800 dark:text-slate-200 leading-relaxed"><span class="text-gray-400 dark:text-slate-500">Kegiatan:</span> {{ act.activity }}</p>
                <p v-if="act.notes" class="text-gray-500 dark:text-slate-400 italic text-[11px] pt-1 border-t border-gray-200/50 dark:border-slate-700/60">Catatan: {{ act.notes }}</p>
              </div>
              <button
                type="button"
                @click="selectedActivity = act"
                class="w-full py-2.5 px-3 rounded-xl border border-primary/20 dark:border-blue-800/40 text-primary dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-primary hover:text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs active:scale-95 min-h-[44px]"
              >
                <BookOpen class="w-3.5 h-3.5" />
                <span>Baca Rincian Lengkap</span>
              </button>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left text-xs min-w-[780px]">
            <thead>
              <tr class="bg-slate-50/75 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase text-[11px] font-bold tracking-wider">
                <th class="py-3.5 px-5 font-bold whitespace-nowrap">Tanggal / Jam</th>
                <th v-if="!isStudentOrSekre" class="py-3.5 px-5 font-bold whitespace-nowrap">Siswa Pelapor</th>
                <th class="py-3.5 px-5 font-bold whitespace-nowrap">Mata Pelajaran</th>
                <th class="py-3.5 px-5 font-bold">Kegiatan yang Dikerjakan di Sekolah</th>
                <th class="py-3.5 px-5 font-bold">Hasil / Catatan</th>
                <th class="py-3.5 px-5 font-bold text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="act in filteredActivities" :key="act.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                <td class="py-4 px-5 font-bold text-slate-900 dark:text-white whitespace-nowrap">{{ act.date_time }}</td>
                <td v-if="!isStudentOrSekre" class="py-4 px-5 font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">{{ act.created_by }}</td>
                <td class="py-4 px-5 font-bold text-primary dark:text-blue-400 whitespace-nowrap">{{ act.subject }}</td>
                <td class="py-4 px-5 text-slate-700 dark:text-slate-300 max-w-xs">
                  <div class="line-clamp-2 leading-relaxed" :title="act.activity">
                    {{ act.activity }}
                  </div>
                </td>
                <td class="py-4 px-5 text-slate-500 dark:text-slate-400 max-w-xs">
                  <div class="line-clamp-2 leading-relaxed" :title="act.notes || ''">
                    {{ act.notes || '-' }}
                  </div>
                </td>
                <td class="py-4 px-5 text-right whitespace-nowrap">
                  <button
                    type="button"
                    @click="selectedActivity = act"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-primary/20 dark:border-blue-800/40 text-primary dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-primary hover:text-white text-xs font-bold transition-all shadow-2xs hover:shadow-xs cursor-pointer whitespace-nowrap active:scale-95"
                    title="Buka form rincian lengkap catatan kegiatan belajar"
                  >
                    <BookOpen class="w-3.5 h-3.5" />
                    <span>Baca Selengkapnya</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      </div>

      <!-- BAGIAN 2: Daftar Materi & Modul Pembelajaran dari Guru -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Daftar Materi & Modul Pembelajaran Guru</h3>
            <p class="text-xs text-gray-400 dark:text-slate-500">Berkas modul digital dan instruksi tugas yang diunggah Guru</p>
          </div>
          <button
            v-if="search"
            @click="search = ''"
            class="text-xs text-primary dark:text-blue-400 hover:underline font-semibold cursor-pointer whitespace-nowrap"
          >
            Reset Pencarian
          </button>
        </div>

        <!-- Mobile Card List for Materials -->
        <div class="block md:hidden space-y-3">
          <div
            v-for="item in filteredMaterials"
            :key="'mob-mat-' + item.id"
            class="p-4 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-850 shadow-2xs space-y-3"
          >
            <div class="flex items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-2.5">
              <span class="text-xs font-bold text-gray-900 dark:text-white">{{ item.date_time }}</span>
              <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                {{ item.attachments_count }} lampiran
              </span>
            </div>
            <div>
              <h4 class="font-bold text-primary dark:text-blue-400 text-sm">{{ item.subject }}</h4>
              <p class="text-xs text-gray-600 dark:text-slate-400 mt-0.5">Guru: <span class="font-semibold text-gray-800 dark:text-slate-200">{{ item.teacher }}</span></p>
            </div>
            <div class="bg-gray-50/70 dark:bg-slate-800/40 p-3 rounded-xl border border-gray-100 dark:border-slate-750 text-xs text-gray-700 dark:text-slate-300">
              {{ item.topic }}
            </div>
            <button
              type="button"
              @click="selectedMaterial = item"
              class="w-full py-2.5 px-3 rounded-xl border border-primary/30 dark:border-blue-800/40 text-primary dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-primary hover:text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs active:scale-95 min-h-[44px]"
            >
              <FileText class="w-3.5 h-3.5" />
              <span>Buka Modul & Tugas</span>
            </button>
          </div>
          <div v-if="filteredMaterials.length === 0" class="py-8 text-center text-xs text-gray-400 dark:text-slate-500">
            Tidak ada materi pelajaran untuk periode ini.
          </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
          <table class="w-full text-left text-xs min-w-[780px]">
            <thead>
              <tr class="bg-slate-50/75 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase text-[11px] font-bold tracking-wider">
                <th class="py-3.5 px-5 font-bold whitespace-nowrap">Waktu</th>
                <th class="py-3.5 px-5 font-bold whitespace-nowrap">Guru</th>
                <th class="py-3.5 px-5 font-bold whitespace-nowrap">Mata Pelajaran</th>
                <th class="py-3.5 px-5 font-bold whitespace-nowrap">Bahasan & Rencana Belajar</th>
                <th class="py-3.5 px-5 font-bold text-center whitespace-nowrap">Lampiran</th>
                <th class="py-3.5 px-5 font-bold text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="item in filteredMaterials" :key="item.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                <td class="py-4 px-5 font-bold text-slate-900 dark:text-white whitespace-nowrap">{{ item.date_time }}</td>
                <td class="py-4 px-5 text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ item.teacher }}</td>
                <td class="py-4 px-5 font-bold text-slate-900 dark:text-white whitespace-nowrap">{{ item.subject }}</td>
                <td class="py-4 px-5 text-slate-600 dark:text-slate-400 max-w-xs">{{ item.topic }}</td>
                <td class="py-4 px-5 text-center">
                  <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                    {{ item.attachments_count }} file
                  </span>
                </td>
                <td class="py-4 px-5 text-right">
                  <button
                    type="button"
                    @click="selectedMaterial = item"
                    class="px-3 py-1.5 rounded-lg border border-amber-500/70 dark:border-amber-700/60 text-amber-700 dark:text-amber-300 bg-amber-50/30 dark:bg-amber-950/30 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-xs font-semibold transition-colors cursor-pointer whitespace-nowrap"
                  >
                    Baca Materi
                  </button>
                </td>
              </tr>
              <tr v-if="filteredMaterials.length === 0">
                <td colspan="6" class="py-8 text-center text-xs text-slate-400 dark:text-slate-500">
                  Tidak ada materi pelajaran untuk periode ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Lapor Jurnal Kegiatan Hari Ini (Oleh Siswa) -->
      <Teleport to="body">
        <div
          v-if="showAddActivityModal"
          class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
        >
          <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl p-5 sm:p-7 max-w-lg w-full shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
            <!-- Mobile drag handle -->
            <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
            <div class="pb-3 border-b border-gray-100 dark:border-slate-800 flex items-start justify-between gap-3">
              <div>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 dark:bg-blue-900/50 text-primary dark:text-blue-300">
                  Jurnal Kegiatan Siswa
                </span>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mt-1">Laporkan Kegiatan Belajar Hari Ini</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400">Laporan ini otomatis terhubung dan terpantau oleh Wali Kelas serta Orang Tua</p>
              </div>
              <button 
                type="button" 
                @click="showAddActivityModal = false" 
                class="text-gray-400 dark:text-slate-400 hover:text-gray-600 dark:hover:text-slate-200 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer shrink-0"
                title="Tutup Modal"
              >
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Weekend Alert in Modal -->
            <div v-if="isTodayWeekend" class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-amber-900 dark:text-amber-200 flex items-start gap-2.5">
              <AlertCircle class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
              <div class="text-xs space-y-0.5">
                <span class="font-bold text-amber-800 dark:text-amber-300">Hari Libur Akhir Pekan</span>
                <p class="text-[11px] text-amber-700 dark:text-amber-300 leading-relaxed">
                  Kegiatan belajar tatap muka di sekolah hanya berlangsung hari Senin hingga Jumat. Pelaporan jurnal kegiatan mandiri dinonaktifkan pada hari libur.
                </p>
              </div>
            </div>

          <form @submit.prevent="submitStudentActivity" class="space-y-4 text-xs">
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider text-xs">
                  Mata Pelajaran
                </label>
                <span v-if="serverDay" class="text-[11px] font-semibold text-primary dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded-lg border border-blue-200 dark:border-blue-900/50">
                  Hari {{ serverDay }}
                </span>
              </div>
              <select
                v-model="activityForm.schedule_id"
                :disabled="isTodayWeekend"
                class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs sm:text-sm text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:opacity-60 disabled:bg-gray-100 dark:disabled:bg-slate-800/50"
                required
              >
                <option value="" disabled>{{ isTodayWeekend ? '-- Libur Akhir Pekan --' : '-- Pilih Jadwal Pelajaran --' }}</option>
                <option v-for="sch in todaySchedules" :key="sch.id" :value="sch.id">
                  {{ sch.subject }} ({{ sch.time }}) — {{ sch.teacher }}
                </option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5 text-xs">
                Kegiatan Pembelajaran
              </label>
              <textarea
                v-model="activityForm.activity"
                :disabled="isTodayWeekend"
                rows="3"
                required
                placeholder="Contoh: Mempelajari dan mempraktikkan pembuatan REST API menggunakan Laravel serta menghubungkannya ke halaman Nuxt..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none disabled:opacity-60 disabled:bg-gray-100 dark:disabled:bg-slate-800/50"
              ></textarea>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5 text-xs">
                Catatan Hasil Belajar
              </label>
              <input
                v-model="activityForm.notes"
                :disabled="isTodayWeekend"
                type="text"
                placeholder="Contoh: Tugas praktikum modul 3 selesai dikerjakan dan diunggah ke repositori"
                class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs sm:text-sm text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:opacity-60 disabled:bg-gray-100 dark:disabled:bg-slate-800/50"
              />
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end gap-2">
              <button
                type="button"
                @click="showAddActivityModal = false"
                class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 font-semibold cursor-pointer"
              >
                {{ isTodayWeekend ? 'Tutup' : 'Batal' }}
              </button>
              <button
                type="submit"
                :disabled="submittingActivity || isTodayWeekend"
                class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold shadow-2xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ submittingActivity ? 'Menyimpan...' : 'Simpan Jurnal Kegiatan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
      </Teleport>

      <!-- Detail Modal Dialog Jurnal Kegiatan Belajar (Form Baca Selengkapnya) -->
      <Teleport to="body">
        <div v-if="selectedActivity" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl p-5 sm:p-7 max-w-xl w-full shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] flex flex-col pb-8 sm:pb-7 pb-safe">
          <!-- Mobile drag handle -->
          <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
          <!-- Modal Header -->
          <div class="flex items-start justify-between pb-3.5 border-b border-gray-100 dark:border-slate-800 shrink-0">
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 dark:bg-blue-900/40 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50">
                  {{ selectedActivity.subject }}
                </span>
                <span class="text-[10px] font-mono text-gray-400 dark:text-slate-500">ID: #{{ selectedActivity.id }}</span>
              </div>
              <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white mt-1">Form Catatan Kegiatan Belajar</h3>
              <p class="text-xs text-gray-500 dark:text-slate-400">
                Dipantau langsung oleh Wali Kelas dan Orang Tua
              </p>
            </div>
            <button
              type="button"
              @click="selectedActivity = null"
              class="text-gray-400 dark:text-slate-400 hover:text-gray-600 dark:hover:text-slate-200 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Modal Scrollable Body -->
          <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs text-gray-700 dark:text-slate-200">
            <!-- Metadata Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="p-3 rounded-2xl bg-gray-50 dark:bg-slate-800/60 border border-gray-150 dark:border-slate-750 space-y-0.5">
                <span class="text-[10px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wider block">Siswa Pelapor</span>
                <div class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white flex items-center gap-1.5">
                  <User class="w-3.5 h-3.5 text-primary dark:text-blue-400" />
                  <span>{{ selectedActivity.created_by }}</span>
                </div>
              </div>

              <div class="p-3 rounded-2xl bg-gray-50 dark:bg-slate-800/60 border border-gray-150 dark:border-slate-750 space-y-0.5">
                <span class="text-[10px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wider block">Waktu Pelaporan</span>
                <div class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white flex items-center gap-1.5">
                  <Clock class="w-3.5 h-3.5 text-primary dark:text-blue-400" />
                  <span>{{ selectedActivity.date_time }}</span>
                </div>
              </div>
            </div>

            <!-- Field 1: Kegiatan yang Dikerjakan (Lengkap) -->
            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <label class="font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                  <BookOpen class="w-3.5 h-3.5 text-primary dark:text-blue-400" />
                  <span>Kegiatan Belajar yang Dikerjakan di Sekolah</span>
                </label>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 font-medium">Uraian Lengkap</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700 rounded-2xl p-4 text-xs sm:text-sm text-gray-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap select-text shadow-2xs">
                {{ selectedActivity.activity }}
              </div>
            </div>

            <!-- Field 2: Hasil / Catatan Progres Belajar (Lengkap) -->
            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <label class="font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                  <FileText class="w-3.5 h-3.5 text-primary dark:text-blue-400" />
                  <span>Hasil / Catatan Progres Belajar</span>
                </label>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 font-medium">Catatan Siswa</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700 rounded-2xl p-4 text-xs sm:text-sm text-gray-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap select-text shadow-2xs">
                {{ selectedActivity.notes || 'Tidak ada catatan progres tambahan yang dicantumkan.' }}
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end shrink-0">
            <button
              type="button"
              @click="selectedActivity = null"
              class="px-5 py-2.5 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-800 dark:text-slate-200 rounded-xl font-bold text-xs transition cursor-pointer shadow-2xs active:scale-95"
            >
              Tutup Rincian
            </button>
          </div>
        </div>
      </div>
      </Teleport>

      <!-- Detail Modal Dialog -->
      <Teleport to="body">
        <div v-if="selectedMaterial" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl p-5 sm:p-8 max-w-lg w-full shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
          <!-- Mobile drag handle -->
          <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
          <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
            <div>
              <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 dark:bg-blue-900/40 text-primary dark:text-blue-300">
                {{ selectedMaterial.subject }}
              </span>
              <h3 class="text-base font-bold text-gray-900 dark:text-white mt-1">{{ selectedMaterial.topic }}</h3>
            </div>
            <button @click="selectedMaterial = null" class="text-gray-400 dark:text-slate-400 hover:text-gray-600 dark:hover:text-slate-200">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3 text-xs text-gray-700 dark:text-slate-300">
            <div>
              <span class="font-bold text-gray-400 dark:text-slate-400 uppercase text-[10px] block mb-1">Rencana Belajar</span>
              <p class="bg-gray-50 dark:bg-slate-800/60 p-3 rounded-xl border border-gray-100 dark:border-slate-750 text-gray-700 dark:text-slate-200">{{ selectedMaterial.plan || 'Mempelajari arsitektur dan komponen aplikasi.' }}</p>
            </div>
            <div>
              <span class="font-bold text-gray-400 dark:text-slate-400 uppercase text-[10px] block mb-1">Catatan / Tugas Siswa</span>
              <p class="bg-gray-50 dark:bg-slate-800/60 p-3 rounded-xl border border-gray-100 dark:border-slate-750 text-gray-700 dark:text-slate-200">{{ selectedMaterial.assignment || 'Tugas mandiri sesuai materi pelajaran.' }}</p>
            </div>
            <div class="pt-2">
              <span class="font-bold text-gray-400 dark:text-slate-400 uppercase text-[10px] block mb-2">Lampiran Dokumentasi</span>
              <div class="flex items-center gap-2">
                <a
                  :href="selectedMaterial.attachment_url || '#'"
                  :download="selectedMaterial.attachment_name || 'Modul_Belajar_Kelas.pdf'"
                  target="_blank"
                  class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/40 text-primary dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/60 rounded-xl font-semibold border border-blue-200 dark:border-blue-800/50 text-xs whitespace-nowrap"
                >
                  <Download class="w-4 h-4" />
                  <span>{{ selectedMaterial.attachment_name || 'Modul_Belajar_Kelas.pdf' }}</span>
                </a>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end">
            <button
              @click="selectedMaterial = null"
              class="px-4 py-2 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-xl font-semibold text-xs cursor-pointer"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
      </Teleport>

      <!-- Modal / Card Pop-up Jadwal Pelajaran (Scrollable) -->
      <Teleport to="body">
        <div
          v-if="showScheduleModal"
          class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
        >
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
          <!-- Mobile drag handle -->
          <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
          <div class="flex items-center justify-between pb-3.5 border-b border-gray-100 dark:border-slate-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 dark:bg-blue-900/40 text-primary dark:text-blue-300 whitespace-nowrap">
                  Jadwal Mingguan
                </span>
                <span class="text-xs text-gray-400 dark:text-slate-500">{{ todayFormatted }}</span>
              </div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white mt-1">Jadwal Pembelajaran Kelas</h3>
              <p class="text-xs text-gray-500 dark:text-slate-400">Pilih hari untuk melihat susunan mata pelajaran dan materi</p>
            </div>
            <button
              @click="showScheduleModal = false"
              class="text-gray-400 dark:text-slate-400 hover:text-gray-600 dark:hover:text-slate-200 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Day Tabs: Senin s/d Jumat -->
          <div class="flex items-center gap-1.5 p-1 bg-gray-100 dark:bg-slate-800 rounded-2xl select-none">
            <button
              v-for="d in daysList"
              :key="d"
              type="button"
              @click="selectedScheduleDay = d"
              class="flex-1 py-1.5 rounded-xl text-xs font-bold transition text-center cursor-pointer"
              :class="selectedScheduleDay === d ? 'bg-white dark:bg-slate-700 text-primary dark:text-blue-300 shadow-2xs font-extrabold' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'"
            >
              {{ d }}
            </button>
          </div>

          <div v-if="displayedSchedules.length > 0" class="max-h-[360px] overflow-y-auto space-y-2.5 pr-1">
            <div
              v-for="sch in displayedSchedules"
              :key="sch.id"
              class="p-3.5 rounded-2xl border border-gray-150 dark:border-slate-800 bg-gray-50/70 dark:bg-slate-850 hover:bg-white dark:hover:bg-slate-800 hover:border-gray-300 dark:hover:border-slate-700 hover:shadow-xs transition duration-150 flex items-center justify-between gap-3"
            >
              <div class="min-w-0 space-y-1">
                <div class="flex items-center gap-2 text-xs">
                  <span class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5 text-primary dark:text-blue-400" />
                    {{ sch.time }}
                  </span>
                  <span class="text-[10px] px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-semibold border border-emerald-200 dark:border-emerald-800/50">
                    {{ sch.room || 'Lab. 1' }}
                  </span>
                  <span class="text-[11px] text-gray-400 dark:text-slate-500 font-medium">
                    • {{ sch.day }}
                  </span>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm truncate">{{ sch.subject }}</h4>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 flex items-center gap-1 truncate">
                  <User class="w-3 h-3 text-gray-400 dark:text-slate-500 shrink-0" />
                  <span>{{ sch.teacher }}</span>
                </p>
              </div>

              <button
                type="button"
                @click="selectScheduleAndFilter(sch.subject)"
                class="px-2.5 py-1.5 rounded-xl border border-blue-200 dark:border-blue-800/50 bg-blue-50 dark:bg-blue-950/40 hover:bg-primary text-primary dark:text-blue-300 hover:text-white text-xs font-semibold transition shrink-0 cursor-pointer shadow-2xs whitespace-nowrap"
              >
                Lihat Materi
              </button>
            </div>
          </div>
          <div v-else class="text-center py-8 bg-gray-50/70 dark:bg-slate-850 rounded-2xl border border-dashed border-gray-200 dark:border-slate-800 text-xs text-gray-400 dark:text-slate-500">
            Tidak ada jadwal pembelajaran pada hari {{ selectedScheduleDay }}.
          </div>

          <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-xs">
            <span class="text-gray-400 dark:text-slate-500 text-[11px]">{{ displayedSchedules.length }} jadwal hari {{ selectedScheduleDay }}</span>
            <button
              @click="showScheduleModal = false"
              class="px-4 py-2 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-xl font-semibold transition cursor-pointer"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
      </Teleport>
    </main>
  </div>
</template>

<script setup lang="ts">
import {
  Calendar,
  Search,
  X,
  Download,
  Clock,
  User,
  Plus,
  CheckCircle2,
  BookOpen,
  FileText,
  AlertCircle,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useToastStore } from '~/stores/toast'

const authStore = useAuthStore()
const toast = useToastStore()
const route = useRoute()
const isStudentOrSekre = computed(() => ['SISWA', 'SEKRETARIS'].includes(authStore.user?.role || ''))
const search = ref('')
const now = new Date()
const currentYearMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const selectedMonth = ref(currentYearMonth)
const materials = ref<any[]>([])
const activities = ref<any[]>([])
const selectedMaterial = ref<any>(null)
const selectedActivity = ref<any>(null)
const showScheduleModal = ref(false)
const showAddActivityModal = ref(false)
const submittingActivity = ref(false)
const successMsg = ref('')

const activityForm = ref({
  schedule_id: '' as string | number,
  activity: '',
  notes: ''
})

const daysList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
const selectedScheduleDay = ref('Senin')
const allSchedules = ref<any[]>([])
const todaySchedules = ref<any[]>([])

const serverDay = ref('')
const serverDate = ref('')
const isTodayWeekend = ref(false)

const todayFormatted = computed(() => {
  if (serverDate.value) {
    const parts = serverDate.value.split('-').map(Number)
    const d = new Date(parts[0], parts[1] - 1, parts[2])
    return new Intl.DateTimeFormat('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).format(d)
  }
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(new Date())
})

const displayedSchedules = computed(() => {
  return allSchedules.value.filter(s => (s.day || 'Senin') === selectedScheduleDay.value)
})

const filteredMaterials = computed(() => {
  if (!search.value) return materials.value
  const q = search.value.toLowerCase()
  return materials.value.filter(m =>
    (m.topic && m.topic.toLowerCase().includes(q)) ||
    (m.subject && m.subject.toLowerCase().includes(q)) ||
    (m.teacher && m.teacher.toLowerCase().includes(q))
  )
})

const filteredActivities = computed(() => {
  if (!search.value) return activities.value
  const q = search.value.toLowerCase()
  return activities.value.filter(a =>
    (a.activity && a.activity.toLowerCase().includes(q)) ||
    (a.subject && a.subject.toLowerCase().includes(q)) ||
    (a.created_by && a.created_by.toLowerCase().includes(q))
  )
})

const filterBySubject = (subjectName: string) => {
  search.value = subjectName.split('&')[0].trim()
}

const selectScheduleAndFilter = (subjectName: string) => {
  filterBySubject(subjectName)
  showScheduleModal.value = false
}

const fetchSchedules = async () => {
  try {
    const rawRes = await $fetch.raw<any[]>(`${authStore.apiBase}/schedules`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const res = rawRes._data || []

    // Ambil hari dan tanggal aktual dari server header (WIB)
    const sDay = rawRes.headers.get('x-server-day') || ''
    const sDate = rawRes.headers.get('x-server-date') || ''
    const sWeekend = rawRes.headers.get('x-is-weekend') === '1'

    if (sDay) {
      serverDay.value = sDay
      serverDate.value = sDate
      isTodayWeekend.value = sWeekend
      if (!selectedScheduleDay.value || selectedScheduleDay.value === 'Senin') {
        selectedScheduleDay.value = sDay
      }
    } else {
      const d = new Date()
      const dayMap: Record<number, string> = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat' }
      isTodayWeekend.value = d.getDay() === 0 || d.getDay() === 6
      serverDay.value = dayMap[d.getDay()] || 'Senin'
      if (!selectedScheduleDay.value || selectedScheduleDay.value === 'Senin') {
        selectedScheduleDay.value = serverDay.value
      }
    }

    if (res && res.length > 0) {
      allSchedules.value = res.map((s: any) => ({
        id: s.id,
        time: `${s.start_time || '07:30'} - ${s.end_time || '09:00'}`,
        subject: s.subject?.name || 'Mata Pelajaran',
        teacher: s.teacher?.name || 'Guru',
        room: s.room || 'Lab. 1',
        day: s.day || 'Senin'
      }))

      // Ambil sesi KBM sesuai hari berjalan di server
      if (!isTodayWeekend.value) {
        const curDayName = serverDay.value || 'Senin'
        todaySchedules.value = allSchedules.value.filter(s => (s.day || 'Senin') === curDayName)
        if (!activityForm.value.schedule_id && todaySchedules.value.length > 0) {
          activityForm.value.schedule_id = todaySchedules.value[0]?.id
        }
      } else {
        todaySchedules.value = []
        activityForm.value.schedule_id = ''
      }
    }
  } catch (e) {
    console.warn('API schedules note:', e)
  }
}

watch(showAddActivityModal, (isOpen) => {
  if (isOpen) {
    fetchSchedules()
  }
})

const fetchMaterials = async () => {
  try {
    const res = await $fetch<any[]>(`${authStore.apiBase}/journals/material?month=${selectedMonth.value}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    materials.value = res && res.length > 0 ? res : []
  } catch (e) {
    console.warn('API materials note:', e)
    materials.value = []
  }
}

const fetchActivities = async () => {
  try {
    const studentQuery = route.query.student_id ? `&student_id=${route.query.student_id}` : ''
    const res = await $fetch<any>(`${authStore.apiBase}/journals/class?type=personal&month=${selectedMonth.value}${studentQuery}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    activities.value = res?.data || []
  } catch (e) {
    console.warn('API activities note:', e)
    activities.value = []
  }
}

const fetchAll = () => {
  fetchMaterials()
  fetchActivities()
}

const submitStudentActivity = async () => {
  if (isTodayWeekend.value) {
    toast.error('Jurnal belajar tidak dapat dilaporkan pada hari libur akhir pekan.')
    return
  }
  submittingActivity.value = true
  successMsg.value = ''
  try {
    const res: any = await $fetch(`${authStore.apiBase}/journals/class`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        schedule_id: activityForm.value.schedule_id,
        activity: activityForm.value.activity,
        notes: activityForm.value.notes,
        type: 'personal'
      }
    })
    successMsg.value = res?.message || 'Catatan kegiatan belajar berhasil dilaporkan dan otomatis terpantau oleh Orang Tua serta Wali Kelas!'
    activityForm.value.activity = ''
    activityForm.value.notes = ''
    showAddActivityModal.value = false
    await fetchActivities()
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal menyimpan catatan kegiatan.')
  } finally {
    submittingActivity.value = false
  }
}

onMounted(() => {
  fetchSchedules()
  fetchAll()
})
</script>
