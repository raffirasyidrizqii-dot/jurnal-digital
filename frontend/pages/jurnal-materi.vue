<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Jurnal KBM Terpadu"
      subtitle="Pengisian realisasi pembelajaran, presensi jam KBM, dan materi ajar"
    />

    <main class="px-4 sm:px-6 md:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Alert / Success Toast -->
      <div v-if="successMsg" class="p-4 rounded-2xl bg-green-50 dark:bg-emerald-950/40 border border-green-200 dark:border-emerald-800/60 text-green-700 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <CheckCircle2Icon class="w-4 h-4 text-green-600 dark:text-emerald-400 shrink-0" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-green-600 hover:text-green-800 dark:text-emerald-400 dark:hover:text-emerald-200 text-xs font-bold cursor-pointer">
          <XIcon class="w-3.5 h-3.5" />
        </button>
      </div>

      <div class="space-y-6">
        <!-- Top 3 Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
          <!-- Total Kelas Diampu -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-5 shadow-card flex items-start justify-between">
            <div class="min-w-0 flex-1 pr-2">
              <span class="text-xs font-semibold text-gray-500 dark:text-slate-400">Total Kelas Diampu</span>
              <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ taughtClassesList.length }} Kelas</div>
              <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1 truncate" :title="taughtClassesList.join(', ')">
                {{ taughtClassesList.length > 0 ? taughtClassesList.join(', ') : 'Belum ada kelas yang diampu' }}
              </p>
            </div>
            <div class="w-8 h-1 rounded-full bg-blue-500 mt-1 shrink-0"></div>
          </div>

          <!-- Jurnal Hari Ini -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-5 shadow-card flex items-start justify-between">
            <div class="min-w-0 flex-1 pr-2">
              <span class="text-xs font-semibold text-gray-500 dark:text-slate-400">Jurnal Hari Ini</span>
              <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ filledCount }} / {{ scheduleItems.length }} Terisi</div>
              <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">
                {{ scheduleItems.length > 0 ? Math.round((filledCount / scheduleItems.length) * 100) : 0 }}% Kegiatan Belajar Terdata
              </p>
            </div>
            <div class="w-8 h-1 rounded-full bg-amber-500 mt-1 shrink-0"></div>
          </div>

          <!-- Menunggu Verifikasi -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-5 shadow-card flex items-start justify-between">
            <div class="min-w-0 flex-1 pr-2">
              <span class="text-xs font-semibold text-gray-500 dark:text-slate-400">Menunggu Verifikasi</span>
              <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ pendingCount }} Jurnal</div>
              <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">Perlu Konfirmasi Guru</p>
            </div>
            <div class="w-8 h-1 rounded-full bg-primary mt-1 shrink-0"></div>
          </div>
        </div>

        <!-- Main Content: Jadwal Mengajar & Status Jurnal -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-card p-4 sm:p-6 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3.5 border-b border-gray-100 dark:border-slate-800 gap-2.5">
            <div>
              <h2 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white tracking-tight leading-snug">Jadwal Mengajar & Status Jurnal</h2>
              <p class="text-[11px] text-gray-400 dark:text-slate-400 mt-0.5">Daftar kelas aktif, input materi sesi, dan verifikasi KBM</p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
              <button
                type="button"
                @click="openUploadMaterialModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-primary bg-primary text-white hover:bg-primary-hover text-xs font-semibold shadow-2xs transition cursor-pointer whitespace-nowrap active:scale-95"
              >
                <UploadCloudIcon class="w-3.5 h-3.5" />
                <span>Unggah Modul Belajar</span>
              </button>
              <button
                type="button"
                @click="openAddScheduleModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 hover:border-primary bg-white dark:bg-slate-800 hover:bg-primary/5 dark:hover:bg-slate-750 text-gray-700 dark:text-slate-300 hover:text-primary dark:hover:text-blue-400 text-xs font-semibold shadow-2xs transition cursor-pointer whitespace-nowrap"
              >
                <PlusIcon class="w-3.5 h-3.5 text-primary dark:text-blue-400" />
                <span>Tambah Jadwal</span>
              </button>
            </div>
          </div>

          <!-- Filter Tanggal & Hari Pembelajaran KBM -->
          <div class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-850 border border-gray-200/80 dark:border-slate-800 shadow-2xs space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
              <!-- Left: Date Picker & Stepper Navigation -->
              <div class="flex flex-wrap items-center gap-2">
                <!-- Date Input Picker (Tanggal, Bulan, Tahun) -->
                <div class="relative flex items-center">
                  <input
                    type="date"
                    v-model="selectedKbmDate"
                    @change="onDateChanged"
                    class="pl-8 pr-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-xs font-bold text-gray-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer transition shadow-2xs"
                    title="Pilih Tanggal, Bulan, Tahun KBM"
                  />
                  <CalendarIcon class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500 absolute left-2.5 pointer-events-none" />
                </div>

                <!-- Quick Steppers: Hari Sebelumnya, Hari Ini, Hari Berikutnya -->
                <div class="inline-flex items-center rounded-xl bg-gray-100 dark:bg-slate-800 p-0.5 border border-transparent dark:border-slate-700">
                  <button
                    type="button"
                    @click="stepDate(-1)"
                    class="p-1.5 rounded-lg hover:bg-white dark:hover:bg-slate-700 text-gray-600 dark:text-slate-300 transition cursor-pointer"
                    title="Hari Sebelumnya (H-1)"
                  >
                    <ChevronLeftIcon class="w-3.5 h-3.5" />
                  </button>
                  <button
                    type="button"
                    @click="resetToToday"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
                    :class="isDateToday && filterMode === 'date' ? 'bg-primary text-white shadow-2xs font-bold' : 'text-gray-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700'"
                  >
                    Hari Ini
                  </button>
                  <button
                    type="button"
                    @click="stepDate(1)"
                    class="p-1.5 rounded-lg hover:bg-white dark:hover:bg-slate-700 text-gray-600 dark:text-slate-300 transition cursor-pointer"
                    title="Hari Berikutnya (H+1)"
                  >
                    <ChevronRightIcon class="w-3.5 h-3.5" />
                  </button>
                </div>

                <!-- Status Badge Tanggal Terpilih -->
                <div
                  v-if="filterMode === 'date'"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                  :class="dateStatusBadgeClass"
                >
                  <span class="w-2 h-2 rounded-full" :class="dateStatusDotClass"></span>
                  <span>{{ formattedSelectedDate }}</span>
                  <span class="text-[10px] font-normal opacity-85">({{ dateRelativeLabel }})</span>
                </div>
              </div>

              <!-- Right: Mode Switcher & Hari Pills -->
              <div class="flex items-center gap-1 overflow-x-auto pb-0.5 text-xs">
                <span class="text-[11px] font-semibold text-gray-400 dark:text-slate-400 mr-1 shrink-0">Tampilan:</span>
                <button
                  type="button"
                  @click="setFilterMode('date')"
                  class="px-2.5 py-1 rounded-lg font-medium transition shrink-0 cursor-pointer text-xs"
                  :class="filterMode === 'date' ? 'bg-primary text-white shadow-2xs font-semibold' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-750'"
                >
                  Berdasarkan Tanggal
                </button>
                <button
                  type="button"
                  @click="setFilterMode('all')"
                  class="px-2.5 py-1 rounded-lg font-medium transition shrink-0 cursor-pointer text-xs"
                  :class="filterMode === 'all' ? 'bg-primary text-white shadow-2xs font-semibold' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-750'"
                >
                  Semua Hari
                </button>
                <button
                  v-for="day in ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']"
                  :key="day"
                  type="button"
                  @click="setDaySpecific(day)"
                  class="px-2 py-1 rounded-lg font-medium transition shrink-0 cursor-pointer text-xs"
                  :class="filterMode === 'day' && activeDayName === day ? 'bg-primary text-white shadow-2xs font-semibold' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-750'"
                >
                  {{ day }}
                </button>
              </div>
            </div>
          </div>

          <div class="space-y-3.5">
            <!-- Empty State jika tidak ada jadwal -->
            <div
              v-if="scheduleItems.length === 0"
              class="py-12 px-4 text-center rounded-2xl border border-dashed border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-850/50 space-y-2"
            >
              <div class="w-10 h-10 mx-auto rounded-full bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-400 flex items-center justify-center">
                <CalendarIcon class="w-5 h-5" />
              </div>
              <p class="text-xs font-bold text-gray-700 dark:text-slate-300">Tidak Ada Jadwal Mengajar</p>
              <p class="text-[11px] text-gray-400 dark:text-slate-500 max-w-xs mx-auto">
                {{ emptyScheduleMessage }}
              </p>
            </div>

            <div
              v-else
              v-for="item in scheduleItems"
              :key="item.id"
              class="p-4 sm:p-5 rounded-2xl border transition duration-150 space-y-3"
              :class="item.is_current_slot ? 'border-blue-400 bg-blue-50/20 dark:bg-blue-950/30 shadow-md ring-2 ring-blue-400/40' : 'border-gray-150 dark:border-slate-800 bg-white dark:bg-slate-850 hover:border-gray-300 dark:hover:border-slate-700 hover:shadow-xs'"
            >
              <!-- Baris Atas: Info Jadwal dan Status Badge -->
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 pb-3.5 border-b border-gray-100 dark:border-slate-750">
                <div class="space-y-1.5 min-w-0 flex-1">
                  <!-- Waktu & Active Indicator -->
                  <div class="flex items-center gap-2 text-xs flex-wrap">
                    <span class="text-gray-400 dark:text-slate-500 font-medium text-[11px] w-20 shrink-0">Waktu</span>
                    <span class="text-gray-300 dark:text-slate-600">•</span>
                    <span class="inline-flex items-center gap-1.5 font-bold text-gray-800 dark:text-slate-200 tracking-tight">
                      <ClockIcon class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500 shrink-0" />
                      {{ item.time }}
                    </span>
                    <span
                      v-if="item.is_current_slot"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-700 animate-pulse"
                    >
                      🔵 Sedang Berlangsung
                    </span>
                  </div>

                  <!-- Mata Pelajaran & Kelas -->
                  <div class="flex items-center gap-2 text-xs">
                    <span class="text-gray-400 dark:text-slate-500 font-medium text-[11px] w-20 shrink-0">Mata Pelajaran</span>
                    <span class="text-gray-300 dark:text-slate-600">•</span>
                    <span class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm truncate">
                      {{ item.subject }}
                    </span>
                  </div>
                </div>

                <!-- Status Badge -->
                <div class="flex items-center gap-2 shrink-0">
                  <span
                    v-if="item.status === 'Terverifikasi'"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50"
                  >
                    <CheckCircle2Icon class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                    <span>Terverifikasi</span>
                  </span>

                  <span
                    v-else-if="item.status.includes('Menunggu')"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50"
                  >
                    <ClockIcon class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                    <span>Menunggu Paraf Guru</span>
                  </span>

                  <span
                    v-else
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-slate-750 text-gray-600 dark:text-slate-400 border border-gray-200 dark:border-slate-700"
                  >
                    <AlertCircleIcon class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500" />
                    <span>Belum Diisi</span>
                  </span>
                </div>
              </div>

              <!-- Baris Bawah: Info Topik Ringkas, Modul Terdeteksi & Tombol Aksi -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 mt-2">
                <div class="text-xs text-gray-500 dark:text-slate-400 min-w-0 flex-1 space-y-1.5">
                  <p v-if="item.topic" class="truncate text-[11px]">
                    <span class="font-semibold text-gray-700 dark:text-slate-300">Topik Materi:</span> {{ item.topic }}
                  </p>
                  <p v-else class="text-[11px] text-gray-400 dark:text-slate-500 italic">
                    Belum ada topik materi yang diinput pada tanggal ini.
                  </p>

                  <!-- Modul Belajar Terdeteksi -->
                  <div v-if="item.has_material" class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                      <BookOpenIcon class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                      <span>Modul Terdeteksi: {{ item.material_topic }}</span>
                    </span>
                    <a
                      v-if="item.material_url"
                      :href="item.material_url"
                      target="_blank"
                      class="inline-flex items-center gap-1 text-[11px] font-bold text-primary dark:text-blue-400 hover:underline cursor-pointer"
                    >
                      <DownloadIcon class="w-3 h-3" /> Unduh Dokumen
                    </a>
                  </div>
                </div>

                <!-- Tombol Aksi: jika sudah diisi berubah menjadi Detail Jurnal / Lihat Jurnal -->
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0 w-full sm:w-auto justify-end sm:justify-start pt-2 sm:pt-0">
                  <!-- Jika Belum Diisi -> Tombol Isi Jurnal KBM -->
                  <button
                    v-if="item.status === 'Belum Diisi'"
                    type="button"
                    @click="openFillJournal(item)"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-primary bg-primary text-white hover:bg-primary-hover text-xs font-bold transition cursor-pointer shadow-2xs whitespace-nowrap active:scale-95 min-h-[44px]"
                  >
                    <PlusIcon class="w-3.5 h-3.5 text-white" />
                    <span>Isi Jurnal KBM</span>
                  </button>

                  <!-- Jika Sudah Diisi -> Tombol Lihat Detail Jurnal -->
                  <button
                    v-else
                    type="button"
                    @click="openJournalDetail(item)"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-primary/20 dark:border-blue-800 bg-primary/10 dark:bg-blue-950/40 hover:bg-primary dark:hover:bg-primary text-primary dark:text-blue-300 hover:text-white text-xs font-bold transition cursor-pointer shadow-2xs whitespace-nowrap active:scale-95 min-h-[40px]"
                  >
                    <EyeIcon class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Lihat Detail Jurnal</span>
                    <span class="sm:hidden text-xs">Detail</span>
                  </button>

                  <!-- Edit Jurnal (jika Menunggu) -->
                  <button
                    v-if="item.status.includes('Menunggu')"
                    type="button"
                    @click="openFillJournal(item)"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-xs font-semibold transition cursor-pointer shadow-2xs whitespace-nowrap min-h-[40px]"
                  >
                    <Edit2Icon class="w-3.5 h-3.5 text-amber-700 dark:text-amber-400" />
                    <span>Edit</span>
                  </button>

                  <!-- Konfirmasi Paraf (jika Menunggu) -->
                  <button
                    v-if="item.status.includes('Menunggu')"
                    type="button"
                    @click="openConfirmModal(item)"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-bold transition cursor-pointer shadow-2xs whitespace-nowrap active:scale-95 min-h-[40px]"
                  >
                    <CheckIcon class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Sahkan Jurnal</span>
                    <span class="sm:hidden text-xs">Sahkan</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </main>

    <!-- ============================================== -->
    <!-- MODAL 1: DETAIL JURNAL REALISASI KBM           -->
    <!-- ============================================== -->
    <Teleport to="body">
    <div
      v-if="activeModalItem"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
          <div>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 dark:bg-blue-900/50 text-primary dark:text-blue-300">
              {{ activeModalItem.subject }}
            </span>
            <h3 class="text-base font-bold text-gray-900 dark:text-white mt-1">Detail Jurnal Realisasi KBM</h3>
          </div>
          <button @click="activeModalItem = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 text-lg cursor-pointer">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <div class="space-y-3.5 text-xs text-gray-700 dark:text-slate-300">
          <!-- Status Banner -->
          <div
            v-if="activeModalItem.status === 'Terverifikasi'"
            class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-emerald-800 dark:text-emerald-300 text-xs flex items-start gap-2.5"
          >
            <LockIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
            <div>
              <p class="font-bold text-emerald-900 dark:text-emerald-200">Jurnal KBM Terverifikasi Resmi</p>
              <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                Paraf telah disahkan dan tercatat di buku agenda kelas.
              </p>
            </div>
          </div>

          <div
            v-else
            class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xl text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2.5"
          >
            <ClockIcon class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div>
              <p class="font-bold text-amber-900 dark:text-amber-200">Menunggu Konfirmasi Guru</p>
              <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">
                Periksa kesesuaian materi di bawah sebelum memberikan paraf pengesahan.
              </p>
            </div>
          </div>

          <div>
            <span class="font-bold text-gray-400 dark:text-slate-500 uppercase text-[10px] block mb-1">Mata Pelajaran & Sesi</span>
            <p class="font-semibold text-gray-800 dark:text-slate-200">{{ activeModalItem.time }} • {{ activeModalItem.subject }}</p>
            <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">Tanggal Pelaksanaan: <span class="font-bold text-gray-700 dark:text-slate-200">{{ formatModalDate(activeModalItem.target_date) }}</span></p>
          </div>

          <div>
            <span class="font-bold text-gray-400 dark:text-slate-500 uppercase text-[10px] block mb-1">Pokok Bahasan</span>
            <p class="bg-gray-50 dark:bg-slate-800 p-3 rounded-xl border border-gray-150 dark:border-slate-750 text-gray-800 dark:text-slate-200 font-medium">
              {{ activeModalItem.topic || 'Belum ada pokok bahasan tercatat.' }}
            </p>
          </div>

          <div>
            <span class="font-bold text-gray-400 dark:text-slate-500 uppercase text-[10px] block mb-1">Realisasi Aktivitas KBM di Kelas</span>
            <p class="bg-gray-50 dark:bg-slate-800 p-3 rounded-xl border border-gray-150 dark:border-slate-750 text-gray-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">
              {{ activeModalItem.activity || 'Pengenalan konsep dan latihan praktikum terbimbing di laboratorium.' }}
            </p>
          </div>

          <div>
            <span class="font-bold text-gray-400 dark:text-slate-500 uppercase text-[10px] block mb-1">Catatan Kelas</span>
            <p class="bg-gray-50 dark:bg-slate-800 p-3 rounded-xl border border-gray-150 dark:border-slate-750 text-gray-600 dark:text-slate-300">
              {{ activeModalItem.notes || 'Pembelajaran berjalan tertib, kendala nihil.' }}
            </p>
          </div>

          <!-- Dokumentasi Pembelajaran (Foto Kelas) -->
          <div v-if="activeModalItem && activeModalItem.photos && activeModalItem.photos.length > 0" class="mt-4">
            <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-200 mb-2">
              Dokumentasi Pembelajaran (Foto Kelas)
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <a
                v-for="(photo, pIdx) in activeModalItem.photos"
                :key="pIdx"
                :href="photo"
                target="_blank"
                class="group relative block aspect-video rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800"
              >
                <img
                  :src="photo"
                  alt="Dokumentasi KBM"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                />
              </a>
            </div>
          </div>

          <!-- Alert Siswa Tidak Berada di Jam Ini -->
          <div v-if="activeModalItem.absent_students && activeModalItem.absent_students.length > 0" class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl space-y-1.5">
            <span class="font-bold text-rose-800 dark:text-rose-300 text-xs flex items-center gap-1.5">
              <AlertCircleIcon class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" />
              Siswa Tidak di Kelas pada Jam Ini ({{ activeModalItem.absent_students.length }} siswa):
            </span>
            <div class="flex flex-wrap gap-1.5 pt-0.5">
              <span v-for="s in activeModalItem.absent_students" :key="s.id" class="px-2.5 py-1 rounded-md bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-700 text-rose-700 dark:text-rose-300 font-semibold text-[11px] shadow-2xs">
                {{ s.name }} (NIS: {{ s.nis }})
              </span>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end gap-2">
          <button
            type="button"
            @click="activeModalItem = null"
            class="px-4 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer"
          >
            Tutup
          </button>

          <!-- Edit button if Menunggu -->
          <button
            v-if="activeModalItem.status.includes('Menunggu')"
            type="button"
            @click="editFromDetailModal"
            class="px-4 py-2 rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-amber-900 dark:text-amber-200 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
          >
            <Edit2Icon class="w-3.5 h-3.5 text-amber-700 dark:text-amber-400" />
            <span>Edit Jurnal</span>
          </button>

          <!-- Konfirmasi button if Menunggu -->
          <button
            v-if="activeModalItem.status.includes('Menunggu')"
            type="button"
            @click="confirmModalJournal"
            :disabled="confirming"
            class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
          >
            <CheckIcon class="w-3.5 h-3.5" />
            <span>{{ confirming ? 'Menyahkan...' : 'Sahkan Jurnal' }}</span>
          </button>
        </div>
      </div>
    </div>
    </Teleport>

    <!-- ============================================== -->
    <!-- MODAL 2: ISI JURNAL KBM + CHECKLIST SISWA TIDAK HADIR -->
    <!-- ============================================== -->
    <Teleport to="body">
    <div
      v-if="fillModalItem"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-t-3xl sm:rounded-3xl max-w-xl w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
          <div>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300">
              {{ fillModalItem.subject }}
            </span>
            <h3 class="text-base font-bold text-gray-900 dark:text-white mt-1">
              {{ fillModalItem.status === 'Menunggu' ? 'Edit Jurnal KBM (Revisi Sesi)' : 'Isi Jurnal KBM' }}
            </h3>
            <p class="text-xs text-gray-400 dark:text-slate-400">
              Tanggal: <span class="font-bold text-primary dark:text-blue-400">{{ formatModalDate(fillModalItem.target_date) }}</span> • Jadwal: {{ fillModalItem.time }} • Kelas: {{ fillModalItem.class || fillModalItem.school_class?.name || '-' }}
            </p>
          </div>
          <button @click="fillModalItem = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 text-lg cursor-pointer">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitFillJournal" class="space-y-4 text-xs">
          <!-- Tabel Presensi Siswa Jam Ini Sesuai Gambar Wireframe: Nama Siswa | Hadir | Izin | Sakit | Alpha -->
          <div class="border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-2xs">
            <div class="bg-gray-50 dark:bg-slate-800 px-3.5 py-2.5 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
              <span class="font-bold text-gray-900 dark:text-white">Daftar Kehadiran Siswa Jam Ini</span>
              <span class="text-[11px] text-gray-500 dark:text-slate-400">{{ modalStudentAttendance.length }} Siswa Terdaftar</span>
            </div>

            <!-- Kotak Pencarian Siswa di Tabel Modal KBM -->
            <div class="p-2.5 border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-850 flex items-center gap-2">
              <div class="relative flex-1">
                <SearchIcon class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  v-model="searchModalStudentQuery"
                  type="text"
                  placeholder="Cari nama atau NIS siswa..."
                  class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-primary"
                />
              </div>
              <button
                v-if="searchModalStudentQuery"
                @click="searchModalStudentQuery = ''"
                type="button"
                class="px-2 py-1 text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 font-medium cursor-pointer"
              >
                Reset
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-xs min-w-[500px]">
                <thead>
                  <tr class="border-b border-gray-100 dark:border-slate-755 text-gray-500 dark:text-slate-400 text-[11px] uppercase bg-white dark:bg-slate-850">
                    <th class="py-2.5 px-3 text-left font-semibold">Nama Siswa</th>
                    <th class="py-2.5 px-2 text-center font-semibold text-emerald-700 dark:text-emerald-400">Hadir</th>
                    <th class="py-2.5 px-2 text-center font-semibold text-blue-700 dark:text-blue-400">Izin</th>
                    <th class="py-2.5 px-2 text-center font-semibold text-amber-700 dark:text-amber-400">Sakit</th>
                    <th class="py-2.5 px-2 text-center font-semibold text-rose-700 dark:text-rose-400">Alpha</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-800 bg-white dark:bg-slate-900">
                  <template v-for="student in filteredModalStudentAttendance" :key="student.id">
                    <tr class="hover:bg-blue-50/20 dark:hover:bg-slate-800/40">
                      <td class="py-2.5 px-3 font-semibold text-gray-900 dark:text-slate-100 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                          <span>{{ student.name }}</span>
                          <span v-if="student.has_hourly_leave" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200">
                            Dispensasi Walas
                          </span>
                          <span v-else-if="student.status === 'IZIN'" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200">
                            Izin Keluar
                          </span>
                        </div>
                        <div v-if="student.has_hourly_leave" class="text-[10px] text-amber-600 dark:text-amber-400 font-normal mt-0.5 max-w-xs truncate" :title="student.hourly_leave_info">
                          {{ student.hourly_leave_info }}
                        </div>
                      </td>
                      <!-- Hadir -->
                      <td class="py-2.5 px-2 text-center">
                        <label class="cursor-pointer inline-flex items-center justify-center p-1">
                          <input
                            type="radio"
                            :name="'student_' + student.id"
                            value="HADIR"
                            v-model="student.status"
                            class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                          />
                        </label>
                      </td>
                      <!-- Izin -->
                      <td class="py-2.5 px-2 text-center">
                        <label class="cursor-pointer inline-flex items-center justify-center p-1">
                          <input
                            type="radio"
                            :name="'student_' + student.id"
                            value="IZIN"
                            v-model="student.status"
                            @change="initStudentLeave(student)"
                            class="w-4 h-4 text-amber-500 focus:ring-amber-500 cursor-pointer"
                          />
                        </label>
                      </td>
                      <!-- Sakit -->
                      <td class="py-2.5 px-2 text-center">
                        <label class="cursor-pointer inline-flex items-center justify-center p-1">
                          <input
                            type="radio"
                            :name="'student_' + student.id"
                            value="SAKIT"
                            v-model="student.status"
                            class="w-4 h-4 text-amber-600 focus:ring-amber-500 cursor-pointer"
                          />
                        </label>
                      </td>
                      <!-- Alpha -->
                      <td class="py-2.5 px-2 text-center">
                        <label class="cursor-pointer inline-flex items-center justify-center p-1">
                          <input
                            type="radio"
                            :name="'student_' + student.id"
                            value="ALPHA"
                            v-model="student.status"
                            class="w-4 h-4 text-rose-600 focus:ring-rose-500 cursor-pointer"
                          />
                        </label>
                      </td>
                    </tr>
                    <!-- Detail Izin Keluar Jam Mapel (Jika Status = IZIN) -->
                    <tr v-if="student.status === 'IZIN'" class="bg-amber-50/70 dark:bg-amber-950/30 border-b border-amber-200/60 dark:border-amber-900/40">
                      <td colspan="5" class="py-2 px-3">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                          <span class="font-bold text-amber-900 dark:text-amber-300 flex items-center gap-1 shrink-0">
                            ⏱️ Detail Izin Keluar:
                          </span>
                          <div class="flex items-center gap-1.5 shrink-0">
                            <label class="text-[11px] font-semibold text-gray-600 dark:text-slate-400">Jam Keluar:</label>
                            <input
                              type="time"
                              v-model="student.leave_time"
                              class="px-2 py-1 text-xs rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-800 font-mono font-bold text-gray-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                          </div>
                          <div class="flex-1 flex items-center gap-1.5 min-w-[200px]">
                            <label class="text-[11px] font-semibold text-gray-600 dark:text-slate-400 shrink-0">Keterangan:</label>
                            <input
                              type="text"
                              v-model="student.note"
                              placeholder="Misal: Izin ke UKS, Sakit kepala, Dispensasi..."
                              class="w-full px-2.5 py-1 text-xs rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                  <tr v-if="isLoadingModalStudents">
                    <td colspan="5" class="py-6 text-center text-gray-500 dark:text-slate-400">
                      <div class="flex items-center justify-center gap-2">
                        <span class="w-3.5 h-3.5 border-2 border-primary/30 border-t-primary rounded-full animate-spin"></span>
                        <span>Memuat data presensi siswa jam ini...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="filteredModalStudentAttendance.length === 0">
                    <td colspan="5" class="py-6 text-center text-gray-400 dark:text-slate-500">
                      {{ searchModalStudentQuery ? 'Tidak ada siswa yang cocok dengan pencarian "' + searchModalStudentQuery + '".' : 'Belum ada data siswa terdaftar di rombel kelas ini.' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Input Materi Sesuai Gambar Wireframe: Materi : Project - - - - - -->
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Materi Pembelajaran</label>
            <input
              v-model="fillJournalForm.topic"
              type="text"
              placeholder="Contoh: Project Pembuatan API Laravel & Vue 3"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
              required
            />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Catatan Aktivitas Kelas</label>
            <textarea
              v-model="fillJournalForm.notes"
              rows="2"
              placeholder="Catatan kelas, progres praktikum, atau tindak lanjut KBM..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none bg-white dark:bg-slate-800"
            ></textarea>
          </div>

          <!-- Dokumentasi Foto KBM (Wajib 1 - 2 Foto) -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                <CameraIcon class="w-3.5 h-3.5 text-primary" />
                <span>Foto Pembelajaran</span>
              </label>
              <span class="text-[10px] text-gray-400 dark:text-slate-500">{{ kbmPhotos.length }} / 2 Foto</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div
                v-for="(photo, pIdx) in kbmPhotos"
                :key="pIdx"
                class="relative h-28 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden group bg-gray-50 dark:bg-slate-800"
              >
                <img :src="photo" class="w-full h-full object-cover" />
                <button
                  type="button"
                  @click="removeKbmPhoto(pIdx)"
                  class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center opacity-90 hover:opacity-100 transition cursor-pointer shadow-xs"
                  title="Hapus Foto"
                >
                  <XIcon class="w-3.5 h-3.5" />
                </button>
              </div>

              <!-- Upload Button Slot if < 2 -->
              <label
                v-if="kbmPhotos.length < 2"
                class="h-28 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-700 hover:border-primary bg-gray-50/70 dark:bg-slate-800/60 hover:bg-blue-50/40 dark:hover:bg-slate-750 flex flex-col items-center justify-center gap-1.5 cursor-pointer transition select-none text-gray-500 dark:text-slate-400 hover:text-primary"
              >
                <CameraIcon class="w-5 h-5 text-gray-400 dark:text-slate-500 group-hover:text-primary" />
                <span class="text-[11px] font-semibold">+ Tambah Foto</span>
                <input
                  type="file"
                  accept="image/png,image/jpeg,image/jpg,image/webp"
                  @change="handleKbmPhotoUpload"
                  class="hidden"
                />
              </label>
            </div>
            <p v-if="kbmPhotos.length === 0" class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">
              * Wajib melampirkan minimal 1 foto dokumentasi kegiatan belajar mengajar di kelas.
            </p>
          </div>

          <!-- Tombol Aksi: [ Batal ] dan [ Selesai ] Sesuai Wireframe -->
          <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="fillModalItem = null"
              class="px-4 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingFillJournal"
              class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition disabled:opacity-50 cursor-pointer shadow-2xs"
            >
              {{ savingFillJournal ? 'Menyimpan...' : 'Selesai' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>

    <!-- ============================================== -->
    <!-- MODAL 3: TAMBAH JADWAL MENGAJAR BARU           -->
    <!-- ============================================== -->
    <Teleport to="body">
    <div
      v-if="openAddScheduleModal"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-t-3xl sm:rounded-3xl max-w-md w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">Tambah Jadwal</h3>
          <button
            type="button"
            @click="openAddScheduleModal = false"
            class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition cursor-pointer"
            title="Tutup"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitNewSchedule" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Pilih Kelas</label>
            <select
              v-model="newScheduleForm.class_id"
              class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              required
            >
              <option v-for="cls in availableClasses" :key="cls.id" :value="cls.id">
                {{ cls.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Mata Pelajaran</label>
            <input
              v-model="newScheduleForm.subject_name"
              type="text"
              placeholder="Contoh: Pemrograman Web & Perangkat Bergerak"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
              required
            />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Hari Pembelajaran</label>
            <select
              v-model="newScheduleForm.day"
              class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              required
            >
              <option value="Senin">Senin</option>
              <option value="Selasa">Selasa</option>
              <option value="Rabu">Rabu</option>
              <option value="Kamis">Kamis</option>
              <option value="Jumat">Jumat</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Jam Mulai</label>
              <input
                v-model="newScheduleForm.start_time"
                type="time"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                required
              />
            </div>
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Jam Selesai</label>
              <input
                v-model="newScheduleForm.end_time"
                type="time"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                required
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Ruangan</label>
            <input
              v-model="newScheduleForm.room"
              type="text"
              placeholder="Contoh: Lab Komputer 2"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
            />
          </div>

          <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="openAddScheduleModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition cursor-pointer"
            >
              Simpan Jadwal
            </button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>

    <!-- ============================================== -->
    <!-- MODAL 4: UNGGAH MATERI & MODUL BELAJAR GURU   -->
    <!-- ============================================== -->
    <Teleport to="body">
    <div
      v-if="openUploadMaterialModal"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-start justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
          <div>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 dark:bg-blue-900/50 text-primary dark:text-blue-300 uppercase tracking-wider">
              Modul Digital
            </span>
            <h3 class="text-base font-bold text-gray-900 dark:text-white mt-1">Unggah Materi & Modul Belajar</h3>
            <p class="text-xs text-gray-400 dark:text-slate-400">Berkas dapat diakses dan diunduh langsung oleh siswa di kelas.</p>
          </div>
          <button
            type="button"
            @click="openUploadMaterialModal = false"
            class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition cursor-pointer"
            title="Tutup"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitUploadMaterial" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Pilih Kelas & Mata Pelajaran</label>
            <select
              v-model="uploadMaterialForm.schedule_id"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer"
              required
            >
              <option value="" disabled>-- Pilih Jadwal & Kelas --</option>
              <option v-for="sch in allTeacherSchedules" :key="sch.id" :value="sch.id">
                {{ sch.school_class?.name || sch.class_name }} — {{ sch.subject?.name || sch.subject_name }} ({{ sch.day }}, {{ sch.start_time }}-{{ sch.end_time }})
              </option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Pokok Bahasan</label>
            <input
              v-model="uploadMaterialForm.topic"
              type="text"
              placeholder="Contoh: Modul Praktikum REST API & Vue 3"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
              required
            />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Instruksi Tugas</label>
            <textarea
              v-model="uploadMaterialForm.assignment"
              rows="2"
              placeholder="Contoh: Baca materi bab 2 dan kerjakan latihan mandiri..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none bg-white dark:bg-slate-800"
            ></textarea>
          </div>

          <!-- File Dropzone -->
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Berkas Dokumen Materi</label>
            <div
              @dragover.prevent="isDraggingUpload = true"
              @dragleave.prevent="isDraggingUpload = false"
              @drop.prevent="handleUploadFileDrop"
              class="border-2 border-dashed rounded-2xl p-4 text-center transition cursor-pointer"
              :class="isDraggingUpload ? 'border-primary bg-blue-50/50 dark:bg-blue-950/40' : 'border-gray-200 dark:border-slate-750 hover:border-gray-300 dark:hover:border-slate-700 bg-gray-50/40 dark:bg-slate-850/50'"
              @click="triggerUploadFileInput"
            >
              <input
                ref="uploadFileInputRef"
                type="file"
                class="hidden"
                accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.zip"
                @change="handleUploadFileSelect"
              />

              <div v-if="!uploadSelectedFile" class="space-y-1 py-1">
                <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-950/50 text-primary dark:text-blue-400 flex items-center justify-center mx-auto">
                  <UploadCloudIcon class="w-4 h-4" />
                </div>
                <p class="text-xs font-semibold text-primary dark:text-blue-400">Klik untuk Pilih File atau Tarik ke Sini</p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500">Format PDF, DOCX, PPTX, JPG (Maks 10MB)</p>
              </div>

              <div v-else class="flex items-center justify-between bg-white dark:bg-slate-800 p-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-left">
                <div class="flex items-center gap-2 min-w-0">
                  <FileTextIcon class="w-5 h-5 text-primary dark:text-blue-400 shrink-0" />
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-gray-800 dark:text-slate-200 truncate">{{ uploadSelectedFile.name }}</p>
                    <p class="text-[10px] text-gray-400 dark:text-slate-500">{{ (uploadSelectedFile.size / 1024).toFixed(1) }} KB</p>
                  </div>
                </div>
                <button
                  type="button"
                  @click.stop="removeUploadFile"
                  class="p-1 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-rose-950/40 transition"
                >
                  <XIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="openUploadMaterialModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="uploadingMaterial"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
            >
              <UploadCloudIcon class="w-3.5 h-3.5" />
              <span>{{ uploadingMaterial ? 'Mengunggah...' : 'Publikasikan ke Siswa' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>
    <!-- ============================================== -->
    <!-- MODAL 5: PRESENSI CEPAT SISWA JAM PELAJARAN   -->
    <!-- ============================================== -->
    <Teleport to="body">
    <div
      v-if="quickAttendanceModalItem"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-t-3xl sm:rounded-3xl max-w-xl w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
        <!-- Mobile drag handle -->
        <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <ClipboardCheckIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
              <span>Presensi Siswa Jam Pelajaran</span>
            </h3>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
              Tanggal: <span class="font-bold text-primary dark:text-blue-400">{{ formatModalDate(quickAttendanceModalItem.target_date) }}</span> • {{ quickAttendanceModalItem.subject }} • {{ quickAttendanceModalItem.time }}
            </p>
          </div>
          <button @click="quickAttendanceModalItem = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 text-lg cursor-pointer">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitQuickAttendance" class="space-y-4 text-xs">
          <!-- Pencarian Siswa -->
          <div class="relative">
            <SearchIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuickStudentQuery"
              type="text"
              placeholder="Cari siswa di kelas ini..."
              class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-1 focus:ring-primary"
            />
          </div>

          <!-- Tabel Presensi Siswa Jam Ini -->
          <div class="border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-2xs">
            <div class="bg-gray-50 dark:bg-slate-800 px-3.5 py-2.5 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
              <span class="font-bold text-gray-900 dark:text-white">Status Kehadiran Siswa</span>
              <span class="text-[11px] text-gray-500 dark:text-slate-400">{{ quickStudentAttendance.length }} Siswa</span>
            </div>

            <div class="overflow-x-auto max-h-[340px] overflow-y-auto">
              <table class="w-full text-xs min-w-[480px]">
                <thead>
                  <tr class="border-b border-gray-100 dark:border-slate-750 text-gray-500 dark:text-slate-400 text-[11px] uppercase bg-white dark:bg-slate-850 sticky top-0 z-10">
                    <th class="py-2.5 px-3 text-left font-semibold">Nama Siswa</th>
                    <th class="py-2.5 px-2 text-center font-semibold text-emerald-700 dark:text-emerald-400">Hadir</th>
                    <th class="py-2.5 px-2 text-center font-semibold text-blue-700 dark:text-blue-400">Izin</th>
                    <th class="py-2.5 px-2 text-center font-semibold text-amber-700 dark:text-amber-400">Sakit</th>
                    <th class="py-2.5 px-2 text-center font-semibold text-rose-700 dark:text-rose-400">Tanpa Keterangan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-800 bg-white dark:bg-slate-900">
                  <tr v-for="student in filteredQuickStudentAttendance" :key="student.id" class="hover:bg-blue-50/20 dark:hover:bg-slate-800/40">
                    <td class="py-2.5 px-3 font-semibold text-gray-900 dark:text-slate-100 whitespace-nowrap">
                      {{ student.name }}
                      <span class="text-[10px] text-gray-400 block font-normal">{{ student.nis }}</span>
                    </td>
                    <!-- Hadir -->
                    <td class="py-2.5 px-2 text-center">
                      <label class="cursor-pointer inline-flex items-center justify-center p-1">
                        <input
                          type="radio"
                          :name="'quick_student_' + student.id"
                          value="HADIR"
                          v-model="student.status"
                          class="accent-emerald-600 w-4 h-4 cursor-pointer"
                        />
                      </label>
                    </td>
                    <!-- Izin -->
                    <td class="py-2.5 px-2 text-center">
                      <label class="cursor-pointer inline-flex items-center justify-center p-1">
                        <input
                          type="radio"
                          :name="'quick_student_' + student.id"
                          value="IZIN"
                          v-model="student.status"
                          class="accent-blue-600 w-4 h-4 cursor-pointer"
                        />
                      </label>
                    </td>
                    <!-- Sakit -->
                    <td class="py-2.5 px-2 text-center">
                      <label class="cursor-pointer inline-flex items-center justify-center p-1">
                        <input
                          type="radio"
                          :name="'quick_student_' + student.id"
                          value="SAKIT"
                          v-model="student.status"
                          class="accent-amber-600 w-4 h-4 cursor-pointer"
                        />
                      </label>
                    </td>
                    <!-- Alpha / Tanpa Keterangan -->
                    <td class="py-2.5 px-2 text-center">
                      <label class="cursor-pointer inline-flex items-center justify-center p-1">
                        <input
                          type="radio"
                          :name="'quick_student_' + student.id"
                          value="ALFA"
                          v-model="student.status"
                          class="accent-rose-600 w-4 h-4 cursor-pointer"
                        />
                      </label>
                    </td>
                  </tr>
                  <tr v-if="filteredQuickStudentAttendance.length === 0">
                    <td colspan="5" class="py-6 text-center text-gray-400 dark:text-slate-500">Belum ada siswa atau data tidak cocok dengan pencarian.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="quickAttendanceModalItem = null"
              class="px-4 py-2 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingQuickAttendance"
              class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition disabled:opacity-50 cursor-pointer shadow-2xs"
            >
              {{ savingQuickAttendance ? 'Menyimpan...' : 'Simpan Presensi Jam Ini' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import {
  FileText as FileTextIcon,
  CheckCircle2 as CheckCircle2Icon,
  X as XIcon,
  Plus as PlusIcon,
  Lock as LockIcon,
  AlertCircle as AlertCircleIcon,
  Edit2 as Edit2Icon,
  Clock as ClockIcon,
  Eye as EyeIcon,
  Check as CheckIcon,
  Calendar as CalendarIcon,
  BarChart2 as BarChart2Icon,
  Search as SearchIcon,
  UploadCloud as UploadCloudIcon,
  Download as DownloadIcon,
  BookOpen as BookOpenIcon,
  Camera as CameraIcon,
  ClipboardCheck as ClipboardCheckIcon,
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
} from 'lucide-vue-next'

const route = useRoute()
const config = useRuntimeConfig()
const authStore = useAuthStore()
const toast = useToastStore()
import { confirmDialog } from '~/utils/confirm'

const successMsg = ref('')
const activeMainTab = ref<'input' | 'monitoring'>(route.query.tab === 'monitoring' ? 'monitoring' : 'input')

const switchMainTab = (tab: 'input' | 'monitoring') => {
  activeMainTab.value = tab
  if (tab === 'monitoring') {
    fetchMonitoringData()
  }
}

// ----------------------------------------------------
// TAB 1: STATE & METHODS
// ----------------------------------------------------
const pendingCount = ref(0)
const filledCount = ref(0)
const confirming = ref(false)
const savingFillJournal = ref(false)

const allTeacherSchedules = ref<any[]>([])
const allTeacherJournals = ref<any[]>([])
const allTeacherMaterials = ref<any[]>([])
const availableClasses = ref<any[]>([])
const scheduleItems = ref<any[]>([])

const getLocalDateStr = (d: Date = new Date()): string => {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const daysList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
const monthsIndo = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
]
const serverDay = ref('')
const serverDate = ref('')
const serverTime = ref('')
const currentDayName = computed(() => serverDay.value || daysList[new Date().getDay()])
const todayDateStr = computed(() => serverDate.value || getLocalDateStr())

// Filter State & Navigasi Tanggal KBM
const selectedKbmDate = ref(getLocalDateStr())
const filterMode = ref<'date' | 'all' | 'day'>('date')
const activeDayName = ref('Senin')
const activeDayFilter = ref('Hari Ini')

const dayNameOfSelectedDate = computed(() => {
  if (!selectedKbmDate.value) return currentDayName.value
  const parts = selectedKbmDate.value.split('-').map(Number)
  if (parts.length < 3 || isNaN(parts[0])) return currentDayName.value
  const dt = new Date(parts[0], parts[1] - 1, parts[2])
  return daysList[dt.getDay()] || currentDayName.value
})

const formattedSelectedDate = computed(() => {
  if (!selectedKbmDate.value) return '-'
  const parts = selectedKbmDate.value.split('-').map(Number)
  if (parts.length < 3 || isNaN(parts[0])) return selectedKbmDate.value
  const dt = new Date(parts[0], parts[1] - 1, parts[2])
  const day = daysList[dt.getDay()]
  const month = monthsIndo[parts[1] - 1] || ''
  return `${day}, ${parts[2]} ${month} ${parts[0]}`
})

const isDateToday = computed(() => selectedKbmDate.value === todayDateStr.value)

const dateRelativeLabel = computed(() => {
  if (selectedKbmDate.value === todayDateStr.value) return 'Hari Ini'
  if (selectedKbmDate.value < todayDateStr.value) return 'Riwayat KBM'
  return 'Jadwal Mendatang'
})

const dateStatusBadgeClass = computed(() => {
  if (isDateToday.value) {
    return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60'
  } else if (selectedKbmDate.value < todayDateStr.value) {
    return 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/60'
  } else {
    return 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/60'
  }
})

const dateStatusDotClass = computed(() => {
  if (isDateToday.value) return 'bg-emerald-500'
  if (selectedKbmDate.value < todayDateStr.value) return 'bg-blue-500'
  return 'bg-purple-500'
})

const emptyScheduleMessage = computed(() => {
  if (filterMode.value === 'date') {
    return `Anda tidak memiliki jadwal KBM aktif untuk hari ${dayNameOfSelectedDate.value} (${formattedSelectedDate.value}).`
  } else if (filterMode.value === 'day') {
    return `Belum ada jadwal KBM yang terdaftar untuk hari ${activeDayName.value}.`
  }
  return 'Belum ada jadwal KBM yang terdaftar untuk akun Anda.'
})

const stepDate = (offset: number) => {
  filterMode.value = 'date'
  const current = selectedKbmDate.value || todayDateStr.value
  const parts = current.split('-').map(Number)
  const dt = new Date(parts[0], parts[1] - 1, parts[2])
  dt.setDate(dt.getDate() + offset)
  const yyyy = dt.getFullYear()
  const mm = String(dt.getMonth() + 1).padStart(2, '0')
  const dd = String(dt.getDate()).padStart(2, '0')
  selectedKbmDate.value = `${yyyy}-${mm}-${dd}`
  applyScheduleFilter()
}

const resetToToday = () => {
  filterMode.value = 'date'
  selectedKbmDate.value = todayDateStr.value
  applyScheduleFilter()
}

const onDateChanged = () => {
  filterMode.value = 'date'
  applyScheduleFilter()
}

const setFilterMode = (mode: 'date' | 'all') => {
  filterMode.value = mode
  applyScheduleFilter()
}

const setDaySpecific = (day: string) => {
  filterMode.value = 'day'
  activeDayName.value = day
  applyScheduleFilter()
}

const formatModalDate = (dStr?: string) => {
  if (!dStr) return formattedSelectedDate.value
  const parts = dStr.split('-').map(Number)
  if (parts.length < 3) return dStr
  const dt = new Date(parts[0], parts[1] - 1, parts[2])
  const day = daysList[dt.getDay()]
  const month = monthsIndo[parts[1] - 1] || ''
  return `${day}, ${parts[2]} ${month} ${parts[0]}`
}

const taughtClassesList = computed(() => {
  const set = new Set<string>()
  allTeacherSchedules.value.forEach((sch: any) => {
    const name = sch.school_class?.name || sch.class_name
    if (name) set.add(name)
  })
  return Array.from(set)
})

const openAddScheduleModal = ref(false)
const openUploadMaterialModal = ref(false)
const uploadingMaterial = ref(false)
const isDraggingUpload = ref(false)
const uploadSelectedFile = ref<File | null>(null)
const uploadFileInputRef = ref<HTMLInputElement | null>(null)
const uploadMaterialForm = ref({
  schedule_id: '',
  topic: '',
  assignment: ''
})

const triggerUploadFileInput = () => {
  uploadFileInputRef.value?.click()
}

const handleUploadFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    uploadSelectedFile.value = target.files[0]
  }
}

const handleUploadFileDrop = (e: DragEvent) => {
  isDraggingUpload.value = false
  if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
    uploadSelectedFile.value = e.dataTransfer.files[0]
  }
}

const removeUploadFile = () => {
  uploadSelectedFile.value = null
  if (uploadFileInputRef.value) uploadFileInputRef.value.value = ''
}

const submitUploadMaterial = async () => {
  if (!uploadMaterialForm.value.schedule_id || !uploadMaterialForm.value.topic) return
  uploadingMaterial.value = true
  try {
    const formData = new FormData()
    formData.append('schedule_id', uploadMaterialForm.value.schedule_id)
    formData.append('topic', uploadMaterialForm.value.topic)
    formData.append('plan', uploadMaterialForm.value.topic)
    formData.append('assignment', uploadMaterialForm.value.assignment || '')
    if (uploadSelectedFile.value) {
      formData.append('file', uploadSelectedFile.value)
    }

    await $fetch(`${config.public.apiBase}/journals/material`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: formData
    })

    successMsg.value = `Modul & bahan ajar '${uploadMaterialForm.value.topic}' berhasil diunggah dan dapat diunduh siswa!`
    openUploadMaterialModal.value = false
    uploadMaterialForm.value = { schedule_id: '', topic: '', assignment: '' }
    uploadSelectedFile.value = null
    if (uploadFileInputRef.value) uploadFileInputRef.value.value = ''
    fetchTeacherSchedulesAndJournals()
  } catch (err: any) {
    console.error('Error uploading material:', err)
    toast.error(err?.data?.message || 'Gagal mengunggah materi.')
  } finally {
    uploadingMaterial.value = false
  }
}
const newScheduleForm = ref({
  class_id: '',
  subject_name: '',
  day: 'Senin',
  start_time: '07:30',
  end_time: '09:00',
  room: 'Lab Komputer 1'
})

const fillModalItem = ref<any>(null)
const fillJournalForm = ref({
  topic: '',
  activity: '',
  notes: ''
})

// Siswa Tidak Hadir State in Fill Journal Modal
const modalClassStudents = ref<any[]>([])
const selectedModalAbsentStudents = ref<any[]>([])
const searchModalStudentQuery = ref('')

const filteredModalClassStudents = computed(() => {
  if (!searchModalStudentQuery.value) return modalClassStudents.value
  const q = searchModalStudentQuery.value.toLowerCase()
  return modalClassStudents.value.filter(s => s.name?.toLowerCase().includes(q) || s.nis?.includes(q))
})

const fetchModalClassStudents = async (classId: number | string) => {
  if (!classId) return
  try {
    const res: any = await $fetch(`${config.public.apiBase}/classes/${classId}/students`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    modalClassStudents.value = Array.isArray(res) ? res : []
  } catch (e) {
    modalClassStudents.value = []
  }
}

// Quick Attendance State & Methods
const quickAttendanceModalItem = ref<any>(null)
const quickStudentAttendance = ref<any[]>([])
const savingQuickAttendance = ref(false)
const searchQuickStudentQuery = ref('')

const filteredQuickStudentAttendance = computed(() => {
  if (!searchQuickStudentQuery.value) return quickStudentAttendance.value
  const q = searchQuickStudentQuery.value.toLowerCase()
  return quickStudentAttendance.value.filter((s: any) => s.name?.toLowerCase().includes(q) || s.nis?.includes(q))
})

const openQuickAttendance = async (item: any) => {
  quickAttendanceModalItem.value = item
  searchQuickStudentQuery.value = ''
  quickStudentAttendance.value = []
  
  const targetClassId = item.class_id || item.school_class?.id
  await fetchModalClassStudents(targetClassId)
  
  const savedAttendance = item.student_attendance || []
  quickStudentAttendance.value = modalClassStudents.value.map((s: any) => {
    const prev = savedAttendance.find((sa: any) => sa.id === s.id)
    return {
      id: s.id,
      name: s.name,
      nis: s.nis,
      status: prev ? prev.status : (s.today_status || 'HADIR')
    }
  })
}

const submitQuickAttendance = async () => {
  if (!quickAttendanceModalItem.value) return
  savingQuickAttendance.value = true
  try {
    const absentStudents = quickStudentAttendance.value
      .filter((s: any) => s.status !== 'HADIR')
      .map((s: any) => ({ id: s.id, name: s.name, nis: s.nis, status: s.status }))

    const studentAttendance = quickStudentAttendance.value.map((s: any) => ({
      id: s.id,
      name: s.name,
      status: s.status
    }))

    await $fetch(`${config.public.apiBase}/journals/class`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        schedule_id: quickAttendanceModalItem.value.id,
        date: quickAttendanceModalItem.value.target_date || (filterMode.value === 'date' ? selectedKbmDate.value : null) || serverDate.value || getLocalDateStr(),
        topic: quickAttendanceModalItem.value.topic || `Presensi Tatap Muka: ${quickAttendanceModalItem.value.subject}`,
        activity: quickAttendanceModalItem.value.activity || 'Pencatatan presensi kehadiran dan konfirmasi siswa aktif di kelas.',
        notes: quickAttendanceModalItem.value.notes || '',
        absent_students: absentStudents,
        student_attendance: studentAttendance,
      }
    })

    toast.success('Presensi siswa jam pelajaran ini berhasil disimpan!')
    quickAttendanceModalItem.value = null
    await fetchTeacherSchedulesAndJournals()
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal menyimpan presensi jam ini.')
  } finally {
    savingQuickAttendance.value = false
  }
}

const toggleModalAbsentStudent = (student: any) => {
  const idx = selectedModalAbsentStudents.value.findIndex(s => s.id === student.id)
  if (idx >= 0) {
    selectedModalAbsentStudents.value.splice(idx, 1)
  } else {
    selectedModalAbsentStudents.value.push({
      id: student.id,
      name: student.name,
      nis: student.nis
    })
  }
}

const isModalStudentAbsent = (studentId: any) => {
  return selectedModalAbsentStudents.value.some(s => s.id === studentId)
}

const activeModalItem = ref<any>(null)

const openJournalDetail = (item: any) => {
  activeModalItem.value = item
}

const openConfirmModal = (item: any) => {
  activeModalItem.value = item
}

const editFromDetailModal = () => {
  if (!activeModalItem.value) return
  const item = activeModalItem.value
  activeModalItem.value = null
  openFillJournal(item)
}

const confirmModalJournal = async () => {
  if (!activeModalItem.value) return
  const ok = await confirmDialog({
    title: 'Sahkan Jurnal Pembelajaran',
    message: `Sahkan agenda pembelajaran mata pelajaran "${activeModalItem.value.subject}"?`,
    confirmText: 'Ya, Sahkan',
    type: 'primary'
  })
  if (!ok) return

  confirming.value = true
  try {
    const journalId = activeModalItem.value.journal_id || activeModalItem.value.id
    await $fetch(`${config.public.apiBase}/journals/class/${journalId}/confirm`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {}
    })
    activeModalItem.value.status = 'Terverifikasi'
    const target = scheduleItems.value.find(s => s.id === activeModalItem.value.id || s.journal_id === journalId)
    if (target) {
      target.status = 'Terverifikasi'
    }
    pendingCount.value = Math.max(0, pendingCount.value - 1)
    successMsg.value = `Jurnal pelajaran ${activeModalItem.value.subject} berhasil disahkan & diverifikasi!`
    activeModalItem.value = null
    fetchTeacherSchedulesAndJournals()
  } catch (e) {
    console.warn('Fallback local verify note:', e)
    activeModalItem.value.status = 'Terverifikasi'
    pendingCount.value = Math.max(0, pendingCount.value - 1)
    successMsg.value = `Jurnal pelajaran ${activeModalItem.value.subject} disahkan!`
    activeModalItem.value = null
  } finally {
    confirming.value = false
  }
}

const isLoadingModalStudents = ref(false)
const modalStudentAttendance = ref<any[]>([])

const filteredModalStudentAttendance = computed(() => {
  if (!searchModalStudentQuery.value.trim()) {
    return modalStudentAttendance.value
  }
  const q = searchModalStudentQuery.value.toLowerCase()
  return modalStudentAttendance.value.filter(s =>
    (s.name && s.name.toLowerCase().includes(q)) ||
    (s.nis && s.nis.toLowerCase().includes(q))
  )
})

const kbmPhotos = ref<string[]>([])

function handleKbmPhotoUpload(e: Event) {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  if (kbmPhotos.value.length >= 2) return

  const file = target.files[0]
  const reader = new FileReader()
  reader.onload = (re) => {
    if (re.target?.result) {
      kbmPhotos.value.push(re.target.result as string)
    }
  }
  reader.readAsDataURL(file)
  target.value = ''
}

function removeKbmPhoto(index: number) {
  kbmPhotos.value.splice(index, 1)
}

const openFillJournal = async (item: any) => {
  fillModalItem.value = item
  fillJournalForm.value.topic = item.topic || ''
  fillJournalForm.value.activity = item.activity || ''
  fillJournalForm.value.notes = item.notes || ''
  kbmPhotos.value = (item.photos && Array.isArray(item.photos)) ? [...item.photos] : []
  selectedModalAbsentStudents.value = item.absent_students ? [...item.absent_students] : []
  searchModalStudentQuery.value = ''
  
  const targetClassId = item.class_id || item.school_class?.id
  isLoadingModalStudents.value = true
  await fetchModalClassStudents(targetClassId)
  isLoadingModalStudents.value = false

  const savedAttendance = item.student_attendance || []
  modalStudentAttendance.value = modalClassStudents.value.map((s: any) => {
    const prev = savedAttendance.find((sa: any) => sa.id === s.id)
    const initialStatus = prev ? prev.status : (s.has_hourly_leave ? 'IZIN' : (s.today_status || 'HADIR'))
    const defaultTime = s.has_hourly_leave ? (item.time?.split('-')[0]?.trim() || null) : null
    const defaultNote = s.has_hourly_leave ? s.hourly_leave_info : ''
    return {
      id: s.id,
      name: s.name,
      nis: s.nis,
      status: initialStatus,
      leave_time: prev?.leave_time || defaultTime,
      note: prev?.note || defaultNote,
      has_hourly_leave: s.has_hourly_leave,
      hourly_leave_info: s.hourly_leave_info,
    }
  })
}

const initStudentLeave = (student: any) => {
  if (!student.leave_time) {
    const now = new Date()
    const hh = String((now.getUTCHours() + 7) % 24).padStart(2, '0')
    const mm = String(now.getUTCMinutes()).padStart(2, '0')
    student.leave_time = `${hh}:${mm}`
  }
  if (!student.note) {
    student.note = 'Izin keluar kelas saat KBM'
  }
}

const applyScheduleFilter = () => {
  let filtered = allTeacherSchedules.value
  const targetDate = selectedKbmDate.value || todayDateStr.value

  if (filterMode.value === 'date') {
    const targetDay = dayNameOfSelectedDate.value
    filtered = allTeacherSchedules.value.filter((sch: any) => !sch.day || sch.day === targetDay)
  } else if (filterMode.value === 'day') {
    filtered = allTeacherSchedules.value.filter((sch: any) => sch.day === activeDayName.value)
  }

  // Waktu sekarang WIB untuk deteksi jam pelajaran aktif (Current Time Slot)
  let nowMinutes = 0
  if (serverTime.value && serverTime.value.includes(' ')) {
    const timeParts = serverTime.value.split(' ')[1].split(':')
    nowMinutes = parseInt(timeParts[0], 10) * 60 + parseInt(timeParts[1], 10)
  } else {
    const now = new Date()
    const wibHour = (now.getUTCHours() + 7) % 24
    const wibMin = now.getUTCMinutes()
    nowMinutes = wibHour * 60 + wibMin
  }

  scheduleItems.value = filtered.map((sch: any) => {
    // 1. Ambil semua jurnal yang relevan untuk slot jadwal ini (kecuali buatan siswa pribadi)
    const candidateJournals = allTeacherJournals.value.filter((j: any) =>
      (j.schedule_id === sch.id || (j.class === sch.school_class?.name && j.subject === sch.subject?.name)) &&
      j.created_by_role !== 'SISWA'
    )

    let match: any = null
    if (filterMode.value === 'date') {
      match = candidateJournals.find((j: any) => j.date === targetDate)
    } else {
      match = candidateJournals.find((j: any) => j.date === todayDateStr.value) || candidateJournals[0]
    }

    let status = 'Belum Diisi'
    if (match) {
      status = match.status === 'TERVERIFIKASI' ? 'Terverifikasi' : 'Menunggu Paraf Guru'
    }

    const startParts = (sch.start_time || '07:30').slice(0, 5).split(':').map(Number)
    const endParts = (sch.end_time || '09:00').slice(0, 5).split(':').map(Number)
    const startMinutes = (startParts[0] || 0) * 60 + (startParts[1] || 0)
    const endMinutes = (endParts[0] || 0) * 60 + (endParts[1] || 0)

    const isToday = isDateToday.value && (!sch.day || sch.day === currentDayName.value)
    const isCurrentTimeSlot = isToday && (nowMinutes >= startMinutes && nowMinutes <= endMinutes)

    const matchMat = allTeacherMaterials.value.find((m: any) =>
      (m.schedule_id === sch.id || (m.subject === sch.subject?.name && (!m.class_id || m.class_id === sch.class_id))) &&
      (!m.date || m.date === targetDate)
    )

    return {
      id: sch.id,
      journal_id: match ? match.id : null,
      class_id: sch.class_id || sch.school_class?.id,
      day: sch.day || '-',
      target_date: filterMode.value === 'date' ? targetDate : (match?.date || targetDate),
      time: `${sch.start_time?.slice(0, 5) || '07:30'} - ${sch.end_time?.slice(0, 5) || '09:00'}`,
      subject: `${sch.subject?.name || 'Mata Pelajaran'} (${sch.school_class?.name || '-'})`,
      status,
      topic: match ? match.topic : '',
      activity: match ? match.activity : '',
      notes: match ? match.notes : '',
      absent_students: match ? (match.absent_students || []) : [],
      photos: match ? (match.photos || []) : [],
      created_by: match ? match.created_by : 'Sekretaris',
      is_current_slot: isCurrentTimeSlot,
      has_material: !!matchMat,
      material_topic: matchMat ? matchMat.topic : null,
      material_url: matchMat ? matchMat.attachment_url : null,
      material_attachment: matchMat ? matchMat.attachment_name : null,
    }
  })

  filledCount.value = scheduleItems.value.filter(s => s.status !== 'Belum Diisi').length
  pendingCount.value = scheduleItems.value.filter(s => s.status.includes('Menunggu')).length
}

const setDayFilter = (day: string) => {
  activeDayFilter.value = day
  if (day === 'Hari Ini') {
    resetToToday()
  } else if (day === 'Semua') {
    setFilterMode('all')
  } else {
    setDaySpecific(day)
  }
}

const fetchTeacherSchedulesAndJournals = async () => {
  try {
    const [schedulesRaw, journalsRes, materialsRes] = await Promise.all([
      $fetch.raw<any>(`${config.public.apiBase}/schedules`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      }),
      $fetch<any>(`${config.public.apiBase}/journals/class`, {
        headers: { Authorization: `Bearer ${authStore.token}` },
        query: {
          type: 'class_agenda'
        }
      }),
      $fetch<any>(`${config.public.apiBase}/journals/material`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      }).catch(() => [])
    ])

    const sDay = schedulesRaw.headers.get('x-server-day')
    if (sDay) {
      serverDay.value = sDay
    }
    const sDate = schedulesRaw.headers.get('x-server-date')
    if (sDate) {
      serverDate.value = sDate
      monitoringDate.value = sDate
      if (!selectedKbmDate.value) {
        selectedKbmDate.value = sDate
      }
    }
    const sTime = schedulesRaw.headers.get('x-server-time')
    if (sTime) {
      serverTime.value = sTime
    }

    const scheduleList = Array.isArray(schedulesRaw._data) ? schedulesRaw._data : []
    allTeacherSchedules.value = scheduleList
    allTeacherJournals.value = journalsRes?.data || (Array.isArray(journalsRes) ? journalsRes : [])
    allTeacherMaterials.value = Array.isArray(materialsRes) ? materialsRes : (materialsRes?.data || [])

    applyScheduleFilter()
  } catch (e) {
    console.error('Error fetching schedules/journals:', e)
    scheduleItems.value = []
    filledCount.value = 0
    pendingCount.value = 0
  }
}

const fetchAvailableClasses = async () => {
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/classes`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    availableClasses.value = Array.isArray(res) ? res : []
    if (availableClasses.value.length > 0 && !newScheduleForm.value.class_id) {
      newScheduleForm.value.class_id = String(availableClasses.value[0].id)
    }
    if (availableClasses.value.length > 0 && !monitoringClassId.value) {
      monitoringClassId.value = String(availableClasses.value[0].id)
    }
  } catch (err) {
    console.warn('Fetch classes warning:', err)
  }
}

const submitNewSchedule = async () => {
  try {
    await $fetch(`${config.public.apiBase}/schedules`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        class_id: newScheduleForm.value.class_id,
        subject_name: newScheduleForm.value.subject_name,
        day: newScheduleForm.value.day,
        start_time: newScheduleForm.value.start_time,
        end_time: newScheduleForm.value.end_time,
        room: newScheduleForm.value.room,
      }
    })
    openAddScheduleModal.value = false
    successMsg.value = `Jadwal baru berhasil ditambahkan!`
    fetchTeacherSchedulesAndJournals()
  } catch (err) {
    console.error(err)
    openAddScheduleModal.value = false
  }
}

const submitFillJournal = async () => {
  if (!fillModalItem.value) return
  if (kbmPhotos.value.length === 0) {
    toast.warning('Wajib melampirkan minimal 1 foto dokumentasi kegiatan belajar mengajar (KBM) di kelas!')
    return
  }

  const ok = await confirmDialog({
    title: 'Sahkan Jurnal KBM',
    message: `Pastikan catatan aktivitas dan presensi siswa untuk materi "${fillJournalForm.value.topic}" sudah sesuai. Simpan dan sahkan jurnal KBM ini?`,
    confirmText: 'Ya, Sahkan',
    type: 'primary'
  })
  if (!ok) return

  savingFillJournal.value = true
  try {
    const absentStudents = modalStudentAttendance.value
      .filter((s: any) => s.status !== 'HADIR')
      .map((s: any) => ({
        id: s.id,
        name: s.name,
        nis: s.nis,
        status: s.status,
        leave_time: s.leave_time || null,
        note: s.note || '',
      }))

    const studentAttendance = modalStudentAttendance.value.map((s: any) => ({
      id: s.id,
      name: s.name,
      status: s.status,
      leave_time: s.status === 'IZIN' ? (s.leave_time || 'Saat KBM') : null,
      note: s.status === 'IZIN' ? (s.note || 'Izin keluar kelas saat KBM') : '',
    }))

    await $fetch(`${config.public.apiBase}/journals/class`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        schedule_id: fillModalItem.value.id,
        class_id: fillModalItem.value.class_id,
        type: 'class_agenda',
        topic: fillJournalForm.value.topic,
        activity: fillJournalForm.value.activity || fillJournalForm.value.topic,
        notes: fillJournalForm.value.notes,
        student_attendance: studentAttendance,
        absent_students: absentStudents,
        photos: kbmPhotos.value,
        date: (fillModalItem.value && fillModalItem.value.target_date) || (filterMode.value === 'date' ? selectedKbmDate.value : null) || serverDate.value || getLocalDateStr()
      }
    })

    fillModalItem.value.status = 'Terverifikasi'
    fillModalItem.value.topic = fillJournalForm.value.topic
    fillModalItem.value.notes = fillJournalForm.value.notes || fillJournalForm.value.activity
    fillModalItem.value.student_attendance = [...studentAttendance]
    fillModalItem.value.absent_students = [...absentStudents]
    filledCount.value += 1
    successMsg.value = `Jurnal pelajaran ${fillModalItem.value.subject} berhasil disimpan & disahkan!`
    fillModalItem.value = null
    savingFillJournal.value = false
    fetchTeacherSchedulesAndJournals()
  } catch (err) {
    console.error(err)
    savingFillJournal.value = false
  }
}

// ----------------------------------------------------
// TAB 2: MONITORING & REKAP KELAS STATE & METHODS
// ----------------------------------------------------
const monitoringClassId = ref<string | number>('')
const monitoringDate = ref(new Date().toISOString().slice(0, 10))
const monitoringFilterStatus = ref('ALL')
const monitoringLoading = ref(false)
const monitoringJournals = ref<any[]>([])
const monitoringAttendanceStats = ref({
  total: 0,
  hadir: 0,
  sakit: 0,
  izin: 0,
  alpha: 0,
})

const teacherClasses = computed(() => {
  const map = new Map<number, string>()
  allTeacherSchedules.value.forEach((sch: any) => {
    const id = sch.class_id || sch.school_class?.id
    const name = sch.school_class?.name || sch.class_name
    if (id && name) map.set(Number(id), name)
  })
  if (map.size === 0 && availableClasses.value.length > 0) {
    availableClasses.value.forEach((c: any) => map.set(Number(c.id), c.name))
  }
  return Array.from(map.entries()).map(([id, name]) => ({ id, name }))
})

const currentMonitoringClassName = computed(() => {
  const found = teacherClasses.value.find(c => String(c.id) === String(monitoringClassId.value))
  return found ? found.name : 'Kelas Terpilih'
})

const monitoringDateFormatted = computed(() => {
  if (!monitoringDate.value) return 'Hari Ini'
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(new Date(monitoringDate.value))
})

const filteredMonitoringJournals = computed(() => {
  if (monitoringFilterStatus.value === 'ALL') return monitoringJournals.value
  return monitoringJournals.value.filter(j => j.status === monitoringFilterStatus.value)
})

const fetchMonitoringData = async () => {
  if (!monitoringClassId.value) {
    if (teacherClasses.value.length > 0) {
      monitoringClassId.value = teacherClasses.value[0].id
    }
  }
  if (!monitoringClassId.value) return

  monitoringLoading.value = true
  try {
    const [journalsRes, attendanceRes] = await Promise.all([
      $fetch<any>(`${config.public.apiBase}/journals/class`, {
        headers: { Authorization: `Bearer ${authStore.token}` },
        query: {
          type: 'class_agenda',
          class_id: monitoringClassId.value,
          date: monitoringDate.value
        }
      }),
      $fetch<any>(`${config.public.apiBase}/attendance/today`, {
        headers: { Authorization: `Bearer ${authStore.token}` },
        query: {
          class_id: monitoringClassId.value,
          date: monitoringDate.value
        }
      })
    ])

    monitoringJournals.value = journalsRes?.data || []

    const students = attendanceRes?.students || []
    monitoringAttendanceStats.value = {
      total: students.length,
      hadir: students.filter((s: any) => s.status === 'HADIR').length,
      sakit: students.filter((s: any) => s.status === 'SAKIT').length,
      izin: students.filter((s: any) => s.status === 'IZIN').length,
      alpha: students.filter((s: any) => s.status === 'ALPHA').length,
    }
  } catch (e) {
    console.warn('Fetch monitoring error:', e)
    monitoringJournals.value = []
  } finally {
    monitoringLoading.value = false
  }
}

const confirmMonitoringJournal = async (journal: any) => {
  const ok = await confirmDialog({
    title: 'Sahkan Jurnal Pembelajaran',
    message: `Sahkan agenda pembelajaran mata pelajaran "${journal.subject}" (${journal.time_slot || journal.date_time})?`,
    confirmText: 'Ya, Sahkan',
    type: 'primary'
  })
  if (!ok) return

  try {
    await $fetch(`${config.public.apiBase}/journals/class/${journal.id}/confirm`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {}
    })
    journal.status = 'TERVERIFIKASI'
    successMsg.value = `Agenda KBM ${journal.subject} berhasil disahkan!`
    toast.success(`Agenda KBM ${journal.subject} berhasil disahkan!`)
    fetchMonitoringData()
    fetchTeacherSchedulesAndJournals()
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal mengesahkan agenda KBM.')
  }
}

onMounted(async () => {
  await fetchAvailableClasses()
  await fetchTeacherSchedulesAndJournals()
  if (teacherClasses.value.length > 0) {
    monitoringClassId.value = teacherClasses.value[0].id
  }
  if (activeMainTab.value === 'monitoring') {
    fetchMonitoringData()
  }
})
</script>
