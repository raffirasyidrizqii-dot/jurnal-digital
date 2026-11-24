<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Persetujuan & Rekapitulasi Izin Siswa"
      :subtitle="pageSubtitle"
    >
      <template #actions>
        <button
          @click="exportCsv"
          type="button"
          class="hidden sm:inline-flex px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 font-semibold transition items-center gap-1.5 shadow-2xs cursor-pointer shrink-0 min-h-[36px] sm:min-h-[40px]"
          title="Ekspor Rekap CSV"
        >
          <FileSpreadsheetIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
          <span>Ekspor Excel/CSV</span>
        </button>
        <button
          @click="printPdf"
          type="button"
          class="hidden md:inline-flex px-3 py-2 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 font-semibold transition items-center gap-1.5 shadow-2xs cursor-pointer shrink-0 min-h-[36px] sm:min-h-[40px]"
          title="Cetak Rekap"
        >
          <PrinterIcon class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400 shrink-0" />
          <span>Cetak</span>
        </button>
        <button
          @click="fetchLeaves"
          type="button"
          class="p-2 sm:px-3.5 sm:py-2 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 font-semibold transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0 min-h-[34px] min-w-[34px] sm:min-h-[40px]"
          title="Segarkan Data"
        >
          <RefreshCwIcon class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400 shrink-0" :class="{ 'animate-spin': loading }" />
          <span class="hidden sm:inline">Segarkan</span>
        </button>
      </template>
    </AppHeader>

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- 4 Stat Metric Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 print:hidden">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-5 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Menunggu Verifikasi</p>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ countPending }} Pengajuan</h3>
            <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-0.5">Butuh Keputusan Walas</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50 flex items-center justify-center font-bold shrink-0">
            <MailCheckIcon class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-5 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Izin Disetujui</p>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ countApproved }} Siswa</h3>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">Tercatat di Presensi</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50 flex items-center justify-center font-bold shrink-0">
            <CheckCircle2Icon class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-5 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Izin Ditolak</p>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ countRejected }} Permohonan</h3>
            <p class="text-[11px] text-rose-600 dark:text-rose-400 font-medium mt-0.5">Wajib Hadir Fisik</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 flex items-center justify-center font-bold shrink-0">
            <XCircleIcon class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-5 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Total Permohonan</p>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ allLeaves.length }} Total</h3>
            <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-0.5">Semester Berjalan</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-900/50 flex items-center justify-center font-bold shrink-0">
            <FileTextIcon class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Alert / Toast -->
      <div v-if="successMsg" class="p-4 rounded-xl bg-green-50 dark:bg-emerald-950/40 border border-green-200 dark:border-emerald-800/60 text-green-700 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs sm:text-sm font-medium">
          <CheckCircle2Icon class="w-5 h-5 text-green-600 dark:text-emerald-400 shrink-0" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-green-500 hover:text-green-700 dark:text-emerald-400 dark:hover:text-emerald-200 text-sm cursor-pointer p-1"><X class="w-4 h-4" /></button>
      </div>

      <!-- Navigation Tabs (Antrean Verifikasi vs Rekap & Laporan Lengkap) -->
      <div class="flex items-center gap-4 border-b border-gray-200 dark:border-slate-800 print:hidden">
        <button
          type="button"
          @click="activeTab = 'pending'"
          class="pb-3 text-xs sm:text-sm font-bold transition relative cursor-pointer flex items-center gap-2"
          :class="activeTab === 'pending' ? 'text-primary dark:text-blue-400' : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-white'"
        >
          <MailCheckIcon class="w-4 h-4" />
          <span>Antrean Persetujuan</span>
          <span
            v-if="countPending > 0"
            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
          >
            {{ countPending }}
          </span>
          <span
            v-if="activeTab === 'pending'"
            class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full"
          ></span>
        </button>

        <button
          type="button"
          @click="activeTab = 'rekap'"
          class="pb-3 text-xs sm:text-sm font-bold transition relative cursor-pointer flex items-center gap-2"
          :class="activeTab === 'rekap' ? 'text-primary dark:text-blue-400' : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-white'"
        >
          <FileSpreadsheetIcon class="w-4 h-4" />
          <span>Rekap & Laporan Lengkap</span>
          <span
            v-if="activeTab === 'rekap'"
            class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full"
          ></span>
        </button>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 1: ANTREAN PERSETUJUAN (SPLIT VIEW)                       -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'pending'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri: Daftar Pengajuan Menunggu -->
        <div class="lg:col-span-5 space-y-3">
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs p-4 sm:p-5">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Menunggu Tindakan</h2>
                <p class="text-[11px] text-gray-400 dark:text-slate-400">Pilih permohonan untuk memeriksa dan memverifikasi</p>
              </div>
              <span class="text-xs bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-bold px-2.5 py-0.5 rounded-full border border-amber-200 dark:border-amber-800">
                {{ pendingLeaves.length }} Baru
              </span>
            </div>

            <!-- Filter Tanggal Harian Cepat -->
            <div class="flex items-center gap-2 mb-3 px-3 py-2 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 text-xs shadow-2xs hover:border-primary/40 focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary transition">
              <CalendarIcon class="w-4 h-4 text-primary shrink-0" />
              <input
                type="date"
                lang="id-ID"
                v-model="filterDate"
                class="w-full bg-transparent text-xs text-gray-800 dark:text-slate-100 font-semibold focus:outline-none cursor-pointer"
              />
              <button
                v-if="filterDate"
                type="button"
                @click="filterDate = ''"
                class="text-xs text-primary dark:text-blue-400 font-bold hover:underline shrink-0 cursor-pointer px-1.5"
              >
                Reset
              </button>
            </div>

            <div v-if="loading" class="py-12 text-center text-gray-400 dark:text-slate-500 text-xs">
              Memuat daftar permohonan...
            </div>

            <div v-else-if="filteredPendingLeaves.length === 0" class="py-12 text-center text-gray-400 dark:text-slate-500 text-xs space-y-1">
              <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2">
                <CheckCircle2Icon class="w-4 h-4" />
              </div>
              <p class="font-bold text-gray-700 dark:text-slate-300">Semua Permohonan Telah Diproses</p>
              <p class="text-[11px]">Tidak ada permohonan surat izin yang menunggu persetujuan saat ini.</p>
            </div>

            <div v-else class="space-y-2.5 max-h-[620px] overflow-y-auto pr-1">
              <div
                v-for="item in filteredPendingLeaves"
                :key="item.id"
                @click="selectItem(item)"
                class="p-3.5 rounded-xl border cursor-pointer transition text-left space-y-2"
                :class="[
                  selectedItem?.id === item.id
                    ? 'border-primary dark:border-blue-600 bg-blue-50/20 dark:bg-blue-950/30 ring-1 ring-primary/30 dark:ring-blue-600/40 shadow-xs'
                    : 'border-gray-150 dark:border-slate-800 bg-white dark:bg-slate-850 hover:border-gray-300 dark:hover:border-slate-700 hover:bg-gray-50/50 dark:hover:bg-slate-800'
                ]"
              >
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white">{{ item.student_name }}</h3>
                    <p class="text-[11px] text-gray-400 dark:text-slate-400 mt-0.5">{{ item.class }} • NIS: {{ item.nis }}</p>
                  </div>
                  <span
                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                    :class="item.type === 'SAKIT' ? 'bg-orange-50 dark:bg-amber-950/40 text-orange-700 dark:text-amber-300 border border-orange-200 dark:border-amber-800/50' : 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50'"
                  >
                    {{ item.type }}
                  </span>
                </div>

                <p class="text-xs text-gray-600 dark:text-slate-300 line-clamp-2 leading-relaxed">{{ item.reason }}</p>

                <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-slate-400 pt-3 mt-2 border-t border-gray-100 dark:border-slate-750">
                  <span class="font-medium text-gray-700 dark:text-slate-300">{{ item.start_date }} s/d {{ item.end_date }}</span>
                  <span class="text-primary dark:text-blue-400 font-bold">Review →</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Detail & Form Eksekusi Review -->
        <div id="leaveDetailSection" class="lg:col-span-7 scroll-mt-20">
          <div v-if="selectedItem" class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs p-5 md:p-6 space-y-5">
            <!-- Header Detail -->
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-4">
              <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-primary dark:text-blue-300 bg-primary/10 dark:bg-blue-950/50 px-2.5 py-0.5 rounded-lg">
                  Detail Permohonan Izin Siswa
                </span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mt-1.5">{{ selectedItem.student_name }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ selectedItem.class }} — NIS: {{ selectedItem.nis }}</p>
              </div>
              <StatusBadge :status="selectedItem.status" context="pembimbing" />
            </div>

            <!-- Rincian Data -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 text-xs bg-gray-50/70 dark:bg-slate-850 p-4 rounded-xl border border-gray-150 dark:border-slate-800">
              <div>
                <span class="text-gray-400 dark:text-slate-500 font-medium block">Jenis Izin</span>
                <p class="font-bold text-gray-900 dark:text-white mt-0.5 uppercase">{{ selectedItem.type }}</p>
              </div>
              <div>
                <span class="text-gray-400 dark:text-slate-500 font-medium block">Rentang Waktu</span>
                <p class="font-bold text-gray-900 dark:text-white mt-0.5">{{ selectedItem.start_date }} s/d {{ selectedItem.end_date }}</p>
              </div>
              <div>
                <span class="text-gray-400 dark:text-slate-500 font-medium block">Tujuan</span>
                <p class="font-semibold text-gray-700 dark:text-slate-300 mt-0.5">{{ selectedItem.recipient || 'Wali Kelas' }}</p>
              </div>
            </div>

            <!-- Alasan / Penjelasan -->
            <div>
              <h4 class="text-xs font-bold text-gray-800 dark:text-slate-200 uppercase tracking-wider mb-1.5">Alasan Permohonan</h4>
              <div class="p-3.5 rounded-xl bg-gray-50/50 dark:bg-slate-850/60 border border-gray-200 dark:border-slate-800 text-xs text-gray-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                {{ selectedItem.reason }}
              </div>
            </div>

            <!-- Input Catatan Reviewer (jika masih PENDING) -->
            <div v-if="selectedItem.status === 'PENDING'" class="space-y-3 pt-2 border-t border-gray-100 dark:border-slate-800">
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider">
                    Catatan Reviewer / Alasan Penolakan Wali Kelas
                  </label>
                  <span class="text-[10px] text-gray-400 dark:text-slate-500">Pilih opsi cepat atau ketik sendiri</span>
                </div>

                <!-- Quick Presets Alasan Penolakan -->
                <div class="flex flex-wrap gap-1.5 mb-2">
                  <button
                    type="button"
                    @click="reviewNote = 'Keterangan izin belum lengkap atau membutuhkan verifikasi lebih lanjut.'"
                    class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-750 text-[11px] text-gray-600 dark:text-slate-300 transition cursor-pointer"
                  >
                    Keterangan Belum Lengkap
                  </button>
                  <button
                    type="button"
                    @click="reviewNote = 'Format pengajuan surat izin tidak sesuai dengan SOP resmi sekolah.'"
                    class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-750 text-[11px] text-gray-600 dark:text-slate-300 transition cursor-pointer"
                  >
                    Format Tidak Sesuai
                  </button>
                  <button
                    type="button"
                    @click="reviewNote = 'Terdapat agenda asesmen sumatif / ujian wajib di sekolah yang harus dihadiri.'"
                    class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-750 text-[11px] text-gray-600 dark:text-slate-300 transition cursor-pointer"
                  >
                    Ada Ujian/Asesmen Wajib
                  </button>
                </div>

                <textarea
                  v-model="reviewNote"
                  rows="2"
                  placeholder="Catatan persetujuan atau penolakan..."
                  class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500"
                ></textarea>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center gap-3 pt-1">
                <button
                  type="button"
                  @click="submitReview('APPROVED')"
                  :disabled="processingReview"
                  class="flex-1 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 min-h-[44px]"
                >
                  <CheckIcon class="w-4 h-4" />
                  <span>Setujui Permohonan Izin</span>
                </button>

                <button
                  type="button"
                  @click="submitReview('REJECTED')"
                  :disabled="processingReview"
                  class="flex-1 py-3 px-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 font-bold text-xs shadow-2xs transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 min-h-[44px]"
                >
                  <XIcon class="w-4 h-4" />
                  <span>Tolak Permohonan</span>
                </button>
              </div>
            </div>

            <!-- Review History Info jika sudah diverifikasi -->
            <div v-else class="p-3.5 rounded-xl border border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-850 space-y-1 text-xs">
              <span class="font-bold text-gray-700 dark:text-slate-300 block">Status Putusan:</span>
              <p class="text-gray-600 dark:text-slate-400">
                Surat izin telah <strong class="text-gray-800 dark:text-slate-200">{{ selectedItem.status === 'APPROVED' ? 'Disetujui' : 'Ditolak' }}</strong>
                <span v-if="selectedItem.reviewer_name"> oleh {{ selectedItem.reviewer_name }}</span>.
              </p>
              <p v-if="selectedItem.review_note" class="text-gray-600 dark:text-slate-300 italic pt-1 border-t border-gray-200/60 dark:border-slate-750">
                Catatan: {{ selectedItem.review_note }}
              </p>
            </div>
          </div>

          <div v-else class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-12 text-center text-gray-400 dark:text-slate-500 text-xs">
            Pilih salah satu permohonan surat izin di sebelah kiri untuk melihat detail.
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: REKAP & LAPORAN LENGKAP                                -->
      <!-- ============================================================== -->
      <div v-else class="space-y-4">
        <!-- Print Official Header for Surat Izin -->
        <div class="hidden print:block text-center border-b-2 border-black pb-3 mb-4">
          <h1 class="text-sm font-bold uppercase tracking-wide text-black">Pemerintah Provinsi Daerah Khusus Ibukota Jakarta</h1>
          <h2 class="text-base font-extrabold uppercase text-black">Dinas Pendidikan • {{ appConfig.school_name }}</h2>
          <p class="text-[11px] text-gray-700">{{ appConfig.school_address || 'Jl. Raden Inten II No. 71, Duren Sawit, Jakarta Timur' }} • NPSN: {{ appConfig.school_npsn || '20104567' }}</p>
          <div class="mt-2 pt-2 border-t border-black">
            <h3 class="text-sm font-bold uppercase underline text-black">Rekapitulasi Permohonan Surat Izin Belajar Siswa</h3>
            <p class="text-xs font-semibold text-black mt-0.5">Kelas: {{ currentClassName }} • Tahun Ajaran 2026/2027</p>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 sm:p-6 shadow-2xs flex flex-wrap items-center justify-between gap-4 print:hidden">
          <div class="flex flex-wrap items-center gap-3 flex-1">
            <!-- Filter Tanggal -->
            <div class="flex items-center gap-2 min-w-[160px]">
              <span class="text-gray-500 text-xs font-semibold">Tanggal:</span>
              <input
                type="date"
                lang="id-ID"
                v-model="rekapFilterDate"
                class="h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs sm:text-sm bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs"
              />
            </div>

            <!-- Filter Status -->
            <div>
              <select
                v-model="rekapFilterStatus"
                class="h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs sm:text-sm font-semibold bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer shadow-2xs"
              >
                <option value="ALL">Semua Status</option>
                <option value="PENDING">Menunggu Verifikasi</option>
                <option value="APPROVED">Disetujui</option>
                <option value="REJECTED">Ditolak</option>
              </select>
            </div>

            <!-- Filter Tipe -->
            <div>
              <select
                v-model="rekapFilterType"
                class="h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs sm:text-sm font-semibold bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer shadow-2xs"
              >
                <option value="ALL">Semua Jenis Izin</option>
                <option value="SAKIT">Sakit</option>
                <option value="IZIN">Izin Keperluan</option>
              </select>
            </div>

            <button
              v-if="rekapFilterDate || rekapFilterStatus !== 'ALL' || rekapFilterType !== 'ALL' || rekapSearchQuery"
              type="button"
              @click="resetRekapFilters"
              class="text-xs sm:text-sm text-primary font-semibold hover:underline cursor-pointer"
            >
              Reset Filter
            </button>
          </div>

          <!-- Search Input -->
          <div class="relative w-full sm:w-72">
            <SearchIcon class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="rekapSearchQuery"
              type="text"
              placeholder="Cari nama atau NIS siswa..."
              class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs sm:text-sm text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800 shadow-2xs transition"
            />
          </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden">
          <div class="p-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Rekapitulasi Surat Izin</h3>
            <span class="text-[11px] text-gray-500 dark:text-slate-400 font-medium">Kelas: {{ currentClassName }}</span>
          </div>

          <div v-if="loading" class="py-16 text-center text-gray-400 dark:text-slate-500 text-xs">
            Memuat rekapitulasi surat izin...
          </div>

          <div v-else-if="rekapFilteredList.length === 0" class="py-16 text-center text-gray-400 dark:text-slate-500 text-xs">
            Tidak ada permohonan surat izin yang sesuai kriteria filter.
          </div>

          <div v-else>
            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-3 p-4">
              <div
                v-for="(item, idx) in rekapFilteredList"
                :key="item.id"
                class="p-4 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-850 shadow-2xs space-y-2.5"
              >
                <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-2">
                  <div>
                    <div class="flex items-center gap-1.5">
                      <span class="text-xs font-bold text-gray-400 dark:text-slate-500">#{{ idx + 1 }}</span>
                      <span class="font-bold text-gray-900 dark:text-white text-sm">{{ item.student_name }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-mono mt-0.5">{{ item.class }} • NIS: {{ item.nis }}</p>
                  </div>
                  <span
                    class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase shrink-0"
                    :class="item.type === 'SAKIT' ? 'bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800/50' : 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50'"
                  >
                    {{ item.type }}
                  </span>
                </div>

                <div class="p-2.5 bg-gray-50/70 dark:bg-slate-900/60 rounded-xl space-y-1 text-xs text-gray-700 dark:text-slate-300">
                  <div class="flex justify-between">
                    <span class="text-gray-400 dark:text-slate-500">Tanggal:</span>
                    <span class="font-semibold">{{ item.start_date }} s/d {{ item.end_date }}</span>
                  </div>
                  <div>
                    <span class="text-gray-400 dark:text-slate-500 block">Alasan:</span>
                    <p class="text-gray-800 dark:text-slate-200 italic">{{ item.reason }}</p>
                  </div>
                </div>

                <div class="flex items-center justify-between pt-3 mt-2 border-t border-gray-100 dark:border-slate-800">
                  <StatusBadge :status="item.status" context="pembimbing" />
                  <span v-if="item.review_note" class="text-[11px] text-gray-500 dark:text-slate-400 truncate max-w-[160px]" :title="item.review_note">
                    Catatan: {{ item.review_note }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
              <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[850px]">
                <thead>
                  <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                    <th class="py-3.5 px-5 w-14 text-center whitespace-nowrap">No</th>
                    <th class="py-3.5 px-5 whitespace-nowrap">Nama Siswa</th>
                    <th class="py-3.5 px-5 whitespace-nowrap">NIS / Kelas</th>
                    <th class="py-3.5 px-5 text-center whitespace-nowrap">Jenis</th>
                    <th class="py-3.5 px-5 whitespace-nowrap">Rentang Tanggal</th>
                    <th class="py-3.5 px-5">Alasan</th>
                    <th class="py-3.5 px-5 text-center whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-5 whitespace-nowrap">Catatan Putusan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                  <tr v-for="(item, idx) in rekapFilteredList" :key="item.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-4 px-5 text-center text-gray-400 dark:text-slate-500 font-medium whitespace-nowrap">{{ idx + 1 }}</td>
                    <td class="py-4 px-5 font-bold text-gray-900 dark:text-slate-100 whitespace-nowrap">{{ item.student_name }}</td>
                    <td class="py-4 px-5 text-gray-600 dark:text-slate-400 whitespace-nowrap font-mono text-xs">{{ item.nis }} • {{ item.class }}</td>
                    <td class="py-4 px-5 text-center whitespace-nowrap">
                      <span
                        class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase whitespace-nowrap"
                        :class="item.type === 'SAKIT' ? 'bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800/50' : 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50'"
                      >
                        {{ item.type }}
                      </span>
                    </td>
                    <td class="py-4 px-5 text-gray-700 dark:text-slate-300 whitespace-nowrap">{{ item.start_date }} s/d {{ item.end_date }}</td>
                    <td class="py-4 px-5 text-gray-600 dark:text-slate-400 max-w-xs truncate" :title="item.reason">{{ item.reason }}</td>
                    <td class="py-4 px-5 text-center whitespace-nowrap">
                      <StatusBadge :status="item.status" context="pembimbing" />
                    </td>
                    <td class="py-4 px-5 text-gray-500 dark:text-slate-400 max-w-xs truncate" :title="item.review_note || '-'">
                      {{ item.review_note || '-' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Signature Area on Print -->
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
                <p class="font-bold">Wali Kelas</p>
                <div class="h-20"></div>
                <p class="font-bold underline">{{ authStore.user?.name || 'Wali Kelas' }}</p>
                <p class="text-[11px] text-gray-600 font-mono">NIP. {{ authStore.user?.nip_nis || '-' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useToastStore } from '~/stores/toast'
import { confirmDialog } from '~/utils/confirm'
import {
  RefreshCw as RefreshCwIcon,
  CheckCircle2 as CheckCircle2Icon,
  XCircle as XCircleIcon,
  FileText as FileTextIcon,
  FileSpreadsheet as FileSpreadsheetIcon,
  Printer as PrinterIcon,
  Search as SearchIcon,
  MailCheck as MailCheckIcon,
  Check as CheckIcon,
  X as XIcon,
  X,
  Calendar as CalendarIcon,
} from 'lucide-vue-next'

const config = useRuntimeConfig()
const authStore = useAuthStore()
const appConfig = useAppConfigStore()
const toast = useToastStore()
const route = useRoute()

const activeTab = ref<'pending' | 'rekap'>((route.query.tab as string) === 'rekap' ? 'rekap' : 'pending')
const loading = ref(false)
const processingReview = ref(false)
const allLeaves = ref<any[]>([])
const selectedItem = ref<any | null>(null)
const reviewNote = ref('')
const successMsg = ref('')

const filterDate = ref('')
const rekapFilterDate = ref('')
const rekapFilterStatus = ref('ALL')
const rekapFilterType = ref('ALL')
const rekapSearchQuery = ref('')

const currentClassName = computed(() => {
  return authStore.user?.guided_class?.name || authStore.user?.school_class?.name || 'Kelas Binaan'
})

const printDate = computed(() => {
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(new Date())
})

const pageSubtitle = computed(() => {
  return `Pengesahan permohonan izin belajar murid binaan dan rekapitulasi data izin • ${currentClassName.value}`
})

const pendingLeaves = computed(() => {
  return allLeaves.value.filter(l => l.status === 'PENDING')
})

const countPending = computed(() => pendingLeaves.value.length)
const countApproved = computed(() => allLeaves.value.filter(l => l.status === 'APPROVED').length)
const countRejected = computed(() => allLeaves.value.filter(l => l.status === 'REJECTED').length)

const filteredPendingLeaves = computed(() => {
  if (!filterDate.value) return pendingLeaves.value
  return pendingLeaves.value.filter(l => {
    return (filterDate.value >= l.start_date && filterDate.value <= l.end_date) || l.start_date === filterDate.value
  })
})

const rekapFilteredList = computed(() => {
  return allLeaves.value.filter(item => {
    if (rekapFilterStatus.value !== 'ALL' && item.status !== rekapFilterStatus.value) return false
    if (rekapFilterType.value !== 'ALL' && item.type !== rekapFilterType.value) return false
    if (rekapFilterDate.value) {
      const d = rekapFilterDate.value
      const inRange = (d >= item.start_date && d <= item.end_date) || item.start_date === d
      if (!inRange) return false
    }
    if (rekapSearchQuery.value) {
      const q = rekapSearchQuery.value.toLowerCase()
      const matchName = (item.student_name || '').toLowerCase().includes(q)
      const matchNis = (item.nis || '').toLowerCase().includes(q)
      const matchReason = (item.reason || '').toLowerCase().includes(q)
      if (!matchName && !matchNis && !matchReason) return false
    }
    return true
  })
})

function resetRekapFilters() {
  rekapFilterDate.value = ''
  rekapFilterStatus.value = 'ALL'
  rekapFilterType.value = 'ALL'
  rekapSearchQuery.value = ''
}

const fetchLeaves = async () => {
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/leave-requests`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    allLeaves.value = Array.isArray(res) ? res : []
    const firstPending = allLeaves.value.find(l => l.status === 'PENDING')
    if (allLeaves.value.length > 0 && !selectedItem.value) {
      selectedItem.value = firstPending || allLeaves.value[0]
    } else if (selectedItem.value) {
      selectedItem.value = allLeaves.value.find(l => l.id === selectedItem.value.id) || firstPending || allLeaves.value[0]
    }
  } catch (err: any) {
    console.error('Error fetching leave requests:', err)
  } finally {
    loading.value = false
  }
}

function selectItem(item: any) {
  selectedItem.value = item
  if (import.meta.client && window.innerWidth < 1024) {
    const el = document.getElementById('leaveDetailSection')
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }
}

const submitReview = async (status: 'APPROVED' | 'REJECTED') => {
  if (!selectedItem.value) return
  if (status === 'REJECTED' && !reviewNote.value.trim()) {
    toast.warning('Mohon cantumkan alasan penolakan izin terlebih dahulu agar siswa mengetahuinya.')
    return
  }

  const ok = await confirmDialog({
    title: status === 'APPROVED' ? 'Setujui Permohonan Izin' : 'Tolak Permohonan Izin',
    message: status === 'APPROVED'
      ? `Setujui permohonan surat izin untuk ${selectedItem.value.student_name} (${selectedItem.value.type})? Status presensi harian siswa otomatis disesuaikan.`
      : `Tolak permohonan surat izin untuk ${selectedItem.value.student_name}? Siswa akan diminta untuk tetap hadir fisik di sekolah.`,
    confirmText: status === 'APPROVED' ? 'Ya, Setujui' : 'Ya, Tolak Permohonan',
    type: status === 'APPROVED' ? 'primary' : 'danger'
  })
  if (!ok) return

  processingReview.value = true
  successMsg.value = ''
  try {
    await $fetch(`${config.public.apiBase}/leave-requests/${selectedItem.value.id}/review`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        status,
        review_note: reviewNote.value
      }
    })
    successMsg.value = `Permohonan izin ${selectedItem.value.student_name} berhasil di-${status === 'APPROVED' ? 'setujui' : 'tolak'}!`
    toast.success(`Permohonan izin ${selectedItem.value.student_name} berhasil di-${status === 'APPROVED' ? 'setujui' : 'tolak'}!`)
    reviewNote.value = ''
    await fetchLeaves()
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal memproses permohonan izin.')
  } finally {
    processingReview.value = false
  }
}

const exportCsv = () => {
  const headers = ['No', 'Nama Siswa', 'NIS', 'Kelas', 'Jenis Izin', 'Tanggal Mulai', 'Tanggal Selesai', 'Alasan', 'Status', 'Catatan Review']
  const rows = rekapFilteredList.value.map((item, idx) => [
    idx + 1,
    `"${item.student_name}"`,
    `"${item.nis}"`,
    `"${item.class}"`,
    item.type,
    item.start_date,
    item.end_date,
    `"${(item.reason || '').replace(/"/g, '""')}"`,
    item.status,
    `"${(item.review_note || '').replace(/"/g, '""')}"`
  ])
  const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `rekapitulasi_surat_izin_${currentClassName.value.replace(/\s+/g, '_')}_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const printPdf = () => {
  window.print()
}

let leavesPollTimer: any = null

function handleLeavesFocus() {
  if (typeof document !== 'undefined' && document.visibilityState === 'visible' && !processingId.value) {
    fetchLeaves()
  }
}

onMounted(async () => {
  if (!appConfig.loaded) {
    await appConfig.fetchConfig()
  }
  fetchLeaves()
  if (process.client) {
    window.addEventListener('focus', handleLeavesFocus)
    document.addEventListener('visibilitychange', handleLeavesFocus)
    leavesPollTimer = setInterval(() => {
      if (document.visibilityState === 'visible' && !processingId.value) {
        fetchLeaves()
      }
    }, 15000)
  }
})

onUnmounted(() => {
  if (leavesPollTimer) clearInterval(leavesPollTimer)
  if (process.client) {
    window.removeEventListener('focus', handleLeavesFocus)
    document.removeEventListener('visibilitychange', handleLeavesFocus)
  }
})
</script>
