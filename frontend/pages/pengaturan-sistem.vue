<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Log & Pengaturan Sistem"
      subtitle="Konfigurasi waktu sekolah, backup data, dan riwayat audit aktivitas"
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
      <div v-if="successMsg" class="p-4 sm:p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold">
          <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-emerald-600 dark:text-emerald-300 hover:text-emerald-800 text-xs font-bold cursor-pointer p-1"><X class="w-4 h-4" /></button>
      </div>

      <div v-if="errorMsg" class="p-4 sm:p-5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold">
          <AlertCircle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
          <span>{{ errorMsg }}</span>
        </div>
        <button @click="errorMsg = ''" class="text-rose-600 dark:text-rose-300 hover:text-rose-800 text-xs font-bold cursor-pointer p-1"><X class="w-4 h-4" /></button>
      </div>

      <!-- ============================================== -->
      <!-- TAB 1: KONFIGURASI INSTANSI & SEKOLAH          -->
      <!-- ============================================== -->
      <div v-if="activeTab === 'general'" class="w-full">
        <form @submit.prevent="saveSettings" class="space-y-6 sm:space-y-8">
          <!-- CARD 1: IDENTITAS & KOP SURAT SEKOLAH -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 sm:p-7 shadow-card space-y-6">
            <div class="border-b border-gray-100 dark:border-slate-800 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary dark:bg-blue-950/60 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50 flex items-center justify-center shrink-0">
                  <Building2 class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">Identitas Sekolah & Kop Dokumen Resmi</h3>
                  <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Informasi instansi yang otomatis dicetak pada kop surat Word, rekap PDF, dan dokumen resmi sekolah</p>
                </div>
              </div>
              <!-- TOMBOL KOSONGKAN DATA -->
              <button
                type="button"
                @click="clearSchoolIdentity"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-rose-200 dark:border-rose-800/60 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-300 text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95 shrink-0 self-start sm:self-auto"
                title="Kosongkan seluruh isian identitas sekolah"
              >
                <Trash2 class="w-3.5 h-3.5" />
                <span>Kosongkan Data</span>
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-xs sm:text-sm">
              <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Nama Resmi Sekolah</label>
                <input
                  v-model="settings.school_name"
                  type="text"
                  required
                  placeholder="SMK NEGERI 71 JAKARTA"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-medium transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Nomor Pokok Sekolah Nasional</label>
                <input
                  v-model="settings.school_npsn"
                  type="text"
                  placeholder="69988123"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-mono font-medium transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">URL Logo Sekolah</label>
                <input
                  v-model="settings.school_logo_url"
                  type="text"
                  placeholder="/images/logo-smkn71.png"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-mono transition"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Alamat Lengkap Sekolah</label>
                <input
                  v-model="settings.school_address"
                  type="text"
                  placeholder="Jl. Raden Inten II No. 1, Duren Sawit, Jakarta Timur, DKI Jakarta 13440"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Nama Kepala Sekolah</label>
                <input
                  v-model="settings.headmaster_name"
                  type="text"
                  placeholder="Drs. H. Prawoto, M.M."
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">NIP Kepala Sekolah</label>
                <input
                  v-model="settings.headmaster_nip"
                  type="text"
                  placeholder="19680512 199403 1 004"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-mono transition"
                />
              </div>
            </div>
          </div>

          <!-- CARD 2: PARAMETER PRESENSI & JADWAL KBM -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 sm:p-7 shadow-card space-y-6">
            <div class="border-b border-gray-100 dark:border-slate-800 pb-4 flex items-center gap-3.5">
              <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-100 dark:border-amber-900/50 flex items-center justify-center shrink-0">
                <Clock class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">Waktu Operasional & Konfigurasi Presensi</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Aturan jam belajar sekolah, jam buka presensi pagi, batas toleransi, dan tenggat pengesahan</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-xs sm:text-sm">
              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Jam Sekolah Standar</label>
                <input
                  v-model="settings.default_school_hours"
                  type="text"
                  placeholder="06:45 - 15:00"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-mono font-bold transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Jam Buka Presensi Mandiri</label>
                <input
                  v-model="settings.attendance_open_time"
                  type="time"
                  placeholder="05:30"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-mono font-bold transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Toleransi Keterlambatan Masuk</label>
                <div class="relative">
                  <input
                    v-model="settings.attendance_late_tolerance_minutes"
                    type="number"
                    min="0"
                    max="120"
                    placeholder="30"
                    class="w-full h-11 pl-4 pr-16 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-medium transition"
                  />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Menit</span>
                </div>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Batas Waktu Koreksi Wali Kelas</label>
                <input
                  v-model="settings.walas_correction_deadline_time"
                  type="time"
                  placeholder="15:00"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-mono font-bold transition"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Slot Jam Pembelajaran Default</label>
                <input
                  v-model="settings.default_slot_hours"
                  type="text"
                  placeholder="07:30 - 09:00"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-mono transition"
                />
              </div>
            </div>
          </div>

          <!-- CARD 3: KEAMANAN, RUANGAN & MODE APLIKASI -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 sm:p-7 shadow-card space-y-6">
            <div class="border-b border-gray-100 dark:border-slate-800 pb-4 flex items-center gap-3.5">
              <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-center shrink-0">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">Keamanan Presensi, Ruangan & Akses Demo</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">PIN pembuka kunci presensi darurat, nama ruangan default, dan mode akses cepat</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-xs sm:text-sm">
              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Default PIN Buka Kunci Presensi</label>
                <input
                  v-model="settings.attendance_lock_pin"
                  type="password"
                  maxlength="6"
                  placeholder="1234"
                  autocomplete="new-password"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-mono font-bold tracking-widest transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Nama Ruangan Default</label>
                <input
                  v-model="settings.default_room_name"
                  type="text"
                  placeholder="Ruang Kelas"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm transition"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-2">Akses Cepat Demo di Halaman Login</label>
                <select
                  v-model="settings.enable_demo_login"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm font-medium transition cursor-pointer"
                >
                  <option value="true">Aktif — Akses Cepat Mode Demo</option>
                  <option value="false">Nonaktif — Mode Sekolah Resmi</option>
                </select>
              </div>
            </div>
          </div>

          <!-- BOTTOM ACTION BAR -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 sm:p-6 shadow-card flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs sm:text-sm text-gray-500 dark:text-slate-400 text-center sm:text-left">
              <span class="font-bold text-gray-700 dark:text-slate-200">Catatan:</span> Perubahan konfigurasi sistem langsung berlaku instan ke seluruh sesi pengguna dan modul presensi.
            </div>
            <button
              type="submit"
              :disabled="saving"
              class="w-full sm:w-auto px-8 py-3 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs sm:text-sm font-bold transition shadow-md hover:shadow-lg cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 shrink-0 active:scale-95 min-h-[44px]"
            >
              <Save class="w-4 h-4" />
              <span>{{ saving ? 'Menyimpan Pengaturan...' : 'Simpan Semua Pengaturan' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- ============================================== -->
      <!-- TAB 2: AUDIT LOG EXPLORER                      -->
      <!-- ============================================== -->
      <div v-else-if="activeTab === 'audit'" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="relative w-full sm:w-96">
            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchLogQuery"
              @input="filterLogs"
              type="text"
              placeholder="Cari aksi, user, peran, atau IP..."
              class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-xs sm:text-sm bg-white outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs transition"
            />
          </div>

          <button
            @click="exportLogsCsv"
            class="h-11 px-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2 shadow-2xs cursor-pointer shrink-0"
          >
            <Download class="w-4 h-4 text-gray-500 dark:text-slate-400" />
            <span>Ekspor Log CSV</span>
          </button>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-card overflow-hidden">
          <div v-if="loadingLogs" class="py-16 text-center text-gray-400 text-xs sm:text-sm">
            Memuat audit log aktivitas...
          </div>
          <div v-else>
          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3 p-4">
            <div
              v-for="(log, idx) in displayedLogs"
              :key="log.id"
              class="p-4 bg-gray-50/70 dark:bg-slate-800/60 border border-gray-150 dark:border-slate-700 rounded-xl space-y-2 text-xs"
            >
              <div class="flex items-center justify-between gap-2">
                <span class="font-bold text-gray-900 dark:text-slate-100 text-sm">{{ log.user }}</span>
                <span class="text-[11px] text-gray-400 font-mono">{{ log.time }}</span>
              </div>
              <p class="text-gray-700 dark:text-slate-300 font-medium leading-relaxed">{{ log.action }}</p>
              <div class="text-[11px] text-gray-400 font-mono">IP: {{ log.ip_address }}</div>
            </div>
            <div v-if="!displayedLogs.length" class="py-12 text-center text-gray-400 text-xs">
              Tidak ditemukan rekaman log yang cocok.
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left text-xs sm:text-sm border-collapse min-w-[750px]">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-800/60 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                  <th class="py-3.5 px-5 w-14 text-center">No</th>
                  <th class="py-3.5 px-5 whitespace-nowrap">Waktu Kejadian</th>
                  <th class="py-3.5 px-5">Pengguna & Peran</th>
                  <th class="py-3.5 px-5">Aktivitas Sistem</th>
                  <th class="py-3.5 px-5">Alamat IP</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <tr v-for="(log, idx) in displayedLogs" :key="log.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition">
                  <td class="py-3.5 px-5 text-center font-medium text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-3.5 px-5 font-mono text-xs text-gray-600 dark:text-slate-400 whitespace-nowrap">{{ log.time }}</td>
                  <td class="py-3.5 px-5 font-bold text-gray-900 dark:text-slate-100">{{ log.user }}</td>
                  <td class="py-3.5 px-5 text-gray-700 dark:text-slate-300 font-medium leading-relaxed">{{ log.action }}</td>
                  <td class="py-3.5 px-5 font-mono text-gray-400 text-xs">{{ log.ip_address }}</td>
                </tr>
                <tr v-if="!displayedLogs.length">
                  <td colspan="5" class="py-12 text-center text-gray-400">
                    Tidak ditemukan rekaman log yang cocok.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          </div>
        </div>
      </div>

      <!-- ============================================== -->
      <!-- TAB 3: CADANGAN & PEMELIHARAAN (BACKUP)        -->
      <!-- ============================================== -->
      <div v-else-if="activeTab === 'backup'" class="space-y-6 w-full">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 sm:p-7 shadow-card space-y-6">
          <div class="border-b border-gray-100 dark:border-slate-800 pb-4 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
              <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-100 dark:border-purple-900/50 flex items-center justify-center shrink-0">
                <Database class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">Cadangan Data Sistem</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Unduh snapshot data penting sistem sekolah dalam format JSON komprehensif</p>
              </div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
              Database Online
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-xs sm:text-sm">
            <div class="p-5 bg-gray-50 dark:bg-slate-800/60 rounded-2xl border border-gray-100 dark:border-slate-700 space-y-1.5">
              <p class="text-gray-400 dark:text-slate-500 text-[11px] font-bold uppercase tracking-wider">Mesin Database</p>
              <p class="text-lg font-black text-gray-900 dark:text-slate-100 uppercase">{{ health.db_connection || 'SQLITE' }}</p>
              <p class="text-xs text-gray-500 dark:text-slate-400">Ukuran Berkas: {{ health.db_size || '380 KB' }}</p>
            </div>

            <div class="p-5 bg-gray-50 dark:bg-slate-800/60 rounded-2xl border border-gray-100 dark:border-slate-700 space-y-1.5">
              <p class="text-gray-400 dark:text-slate-500 text-[11px] font-bold uppercase tracking-wider">Total Akun Terdaftar</p>
              <p class="text-lg font-black text-primary dark:text-blue-400">{{ health.total_users || 0 }} Akun</p>
              <p class="text-xs text-gray-500 dark:text-slate-400">Siswa, Guru, Walas, Admin</p>
            </div>

            <div class="p-5 bg-gray-50 dark:bg-slate-800/60 rounded-2xl border border-gray-100 dark:border-slate-700 space-y-1.5">
              <p class="text-gray-400 dark:text-slate-500 text-[11px] font-bold uppercase tracking-wider">Aktivitas Audit Log</p>
              <p class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ health.total_audit_logs || 0 }} Log</p>
              <p class="text-xs text-gray-500 dark:text-slate-400">Rekaman Terlindungi</p>
            </div>
          </div>

          <div class="p-5 bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/50 rounded-2xl space-y-2 text-xs sm:text-sm text-blue-900 dark:text-blue-200">
            <p class="font-bold flex items-center gap-1.5">
              <Info class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 inline mr-1" /><span>Informasi Pencadangan Data:</span>
            </p>
            <p class="text-blue-800 dark:text-blue-300 leading-relaxed">
              Berkas backup mencakup seluruh tabel master data: Pengguna, Rombel/Kelas, Mata Pelajaran, Jadwal Pelajaran, Semester/Tahun Ajaran, Pengaturan Sekolah, serta 200 Audit Log terkini. Disarankan melakukan pencadangan berkala setiap akhir semester.
            </p>
          </div>

          <div class="pt-2">
            <button
              @click="downloadBackupJson"
              :disabled="downloadingBackup"
              class="w-full sm:w-auto px-7 py-3 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs sm:text-sm font-bold transition shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 min-h-[44px]"
            >
              <Database class="w-4 h-4" />
              <span>{{ downloadingBackup ? 'Mengunduh...' : 'Unduh Cadangan JSON' }}</span>
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import {
  Sliders,
  FileText,
  Database,
  Search,
  Download,
  CheckCircle2,
  AlertCircle,
  Info,
  X,
  Building2,
  Clock,
  ShieldCheck,
  Save,
  Trash2,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useAppConfigStore } from '~/stores/appConfig'

const authStore = useAuthStore()
const appConfig = useAppConfigStore()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || '/api'

const tabs = [
  { id: 'general', label: 'Konfigurasi Sekolah', icon: Sliders },
  { id: 'audit', label: 'Audit Trail Explorer', icon: FileText },
  { id: 'backup', label: 'Cadangan & Pemeliharaan', icon: Database },
]

const activeTab = ref('general')
const successMsg = ref('')
const errorMsg = ref('')
const saving = ref(false)
const downloadingBackup = ref(false)

// Settings state (Default kosong agar dapat diuji dari state kosong)
const settings = ref({
  school_name: '',
  school_npsn: '',
  school_address: '',
  headmaster_name: '',
  headmaster_nip: '',
  default_school_hours: '06:45 - 15:00',
  attendance_open_time: '05:30',
  attendance_late_tolerance_minutes: 30,
  walas_correction_deadline_time: '15:00',
  default_room_name: 'Ruang Kelas',
  default_slot_hours: '07:30 - 09:00',
  attendance_lock_pin: '',
  school_logo_url: '',
  enable_demo_login: 'true',
})

// Audit logs state
const logs = ref<any[]>([])
const displayedLogs = ref<any[]>([])
const loadingLogs = ref(false)
const searchLogQuery = ref('')

// Health state
const health = ref<any>({})

function clearSchoolIdentity() {
  settings.value.school_name = ''
  settings.value.school_npsn = ''
  settings.value.school_logo_url = ''
  settings.value.school_address = ''
  settings.value.headmaster_name = ''
  settings.value.headmaster_nip = ''
}

async function fetchSettings() {
  try {
    const res: any = await $fetch(`${apiBase}/system/settings`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    if (res) {
      settings.value = { ...settings.value, ...res }
    }
  } catch (e) {
    console.warn(e)
  }
}

async function saveSettings() {
  saving.value = true
  successMsg.value = ''
  errorMsg.value = ''
  try {
    const res: any = await $fetch(`${apiBase}/system/settings`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: settings.value,
    })
    await appConfig.fetchConfig(true)
    successMsg.value = res.message || 'Konfigurasi sekolah berhasil disimpan!'
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menyimpan pengaturan.'
  } finally {
    saving.value = false
  }
}

async function fetchLogs() {
  loadingLogs.value = true
  try {
    const res: any = await $fetch(`${apiBase}/audit-logs?limit=150`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    logs.value = res || []
    filterLogs()
  } catch (e) {
    console.warn(e)
  } finally {
    loadingLogs.value = false
  }
}

function filterLogs() {
  const q = searchLogQuery.value.toLowerCase().trim()
  if (!q) {
    displayedLogs.value = [...logs.value]
    return
  }
  displayedLogs.value = logs.value.filter(l =>
    (l.action && l.action.toLowerCase().includes(q)) ||
    (l.user && l.user.toLowerCase().includes(q)) ||
    (l.ip_address && l.ip_address.includes(q))
  )
}

function exportLogsCsv() {
  if (!displayedLogs.value.length) return
  const headers = ['No', 'Waktu', 'Pengguna', 'Aksi', 'Alamat IP']
  const rows = displayedLogs.value.map((l, idx) => [
    idx + 1,
    `"${l.time}"`,
    `"${l.user}"`,
    `"${l.action.replace(/"/g, '""')}"`,
    `"${l.ip_address}"`
  ])
  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `Audit_Logs_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

async function fetchHealth() {
  try {
    const res: any = await $fetch(`${apiBase}/system/health`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    health.value = res || {}
  } catch (e) {
    console.warn(e)
  }
}

async function downloadBackupJson() {
  downloadingBackup.value = true
  try {
    const res: any = await $fetch(`${apiBase}/system/backup`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(res, null, 2))
    const downloadAnchor = document.createElement('a')
    downloadAnchor.setAttribute('href', dataStr)
    downloadAnchor.setAttribute('download', `Backup_Jurnal_Kelas_${new Date().toISOString().split('T')[0]}.json`)
    document.body.appendChild(downloadAnchor)
    downloadAnchor.click()
    downloadAnchor.remove()
    successMsg.value = 'Cadangan data sistem berhasil diunduh!'
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal mengunduh cadangan data.'
  } finally {
    downloadingBackup.value = false
  }
}

onMounted(() => {
  fetchSettings()
  fetchLogs()
  fetchHealth()
})
</script>
