<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="WhatsApp Gateway & Siaran Pengumuman"
      subtitle="Monitoring status server WhatsApp Baileys dan siaran pengumuman massal"
    />

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Alerts -->
      <div v-if="successMsg" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-200 text-xs font-bold cursor-pointer"><X class="w-3.5 h-3.5" /></button>
      </div>

      <div v-if="errorMsg" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <AlertCircle class="w-4 h-4 text-rose-600 dark:text-rose-400 flex-shrink-0" />
          <span>{{ errorMsg }}</span>
        </div>
        <button @click="errorMsg = ''" class="text-rose-700 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-200 text-xs font-bold cursor-pointer"><X class="w-3.5 h-3.5" /></button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT (5 COLS): KONEKSI WHATSAPP GATEWAY -->
        <div class="lg:col-span-5 space-y-6">
          <!-- Connection Status Card -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                  <MessageSquare class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="text-sm font-bold text-gray-900 dark:text-white">Status Server WA</h3>
                  <p class="text-[11px] text-gray-400 dark:text-slate-500">Node Baileys Engine</p>
                </div>
              </div>

              <!-- Status Badge -->
              <span
                class="px-3 py-1 rounded-full text-xs font-bold"
                :class="waStatus === 'connected' ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border border-gray-200 dark:border-slate-700'"
              >
                {{ waStatus === 'connected' ? 'Terhubung' : 'Offline / Standby' }}
              </span>
            </div>

            <!-- Bot Details or Pairing Form -->
            <div class="space-y-4 text-xs">
              <div v-if="waStatus === 'connected'" class="p-4 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-100 dark:border-emerald-900/40 space-y-2">
                <div class="flex justify-between text-gray-700 dark:text-slate-300">
                  <span class="font-medium">Nomor Bot:</span>
                  <span class="font-bold text-gray-900 dark:text-white font-mono">{{ waNumber || '6281234567890' }}</span>
                </div>
                <div class="flex justify-between text-gray-700 dark:text-slate-300">
                  <span class="font-medium">Koneksi Sesi:</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">Aktif & Siap Kirim</span>
                </div>
                <div class="pt-2">
                  <button
                    type="button"
                    @click="handleLogoutWa"
                    class="w-full py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-300 font-bold rounded-xl border border-rose-200 dark:border-rose-800/50 transition text-center cursor-pointer"
                  >
                    Putuskan Sambungan
                  </button>
                </div>
              </div>

              <div v-else class="space-y-3">
                <p class="text-gray-500 dark:text-slate-400 text-[11px] leading-relaxed">
                  Hubungkan nomor WhatsApp resmi sekolah untuk otomatisasi notifikasi presensi harian dan siaran pengumuman kepada wali murid dan guru.
                </p>

                <div>
                  <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Nomor WhatsApp Server</label>
                  <div class="flex gap-2">
                    <input
                      v-model="pairPhone"
                      type="text"
                      placeholder="Contoh: 6281234567890"
                      class="flex-1 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    />
                    <button
                      type="button"
                      @click="handleRequestPairing"
                      :disabled="pairingLoading"
                      class="px-3.5 py-2 bg-primary hover:bg-primary-hover text-white font-bold rounded-xl transition text-xs cursor-pointer disabled:opacity-50"
                    >
                      {{ pairingLoading ? '...' : 'Pairing Code' }}
                    </button>
                  </div>
                </div>

                <!-- Display Pairing Code if requested -->
                <div v-if="pairingCode" class="p-4 bg-gray-50 dark:bg-slate-850 rounded-xl border border-gray-200 dark:border-slate-800 text-center space-y-1">
                  <p class="text-[11px] text-gray-500 dark:text-slate-400 font-medium">Masukkan Kode Pairing ini di WhatsApp HP:</p>
                  <p class="text-xl font-mono font-black text-primary tracking-widest">{{ pairingCode }}</p>
                </div>
              </div>

              <!-- Button Refresh Status -->
              <button
                type="button"
                @click="checkWaStatus"
                class="w-full py-2.5 bg-gray-50 hover:bg-gray-100 dark:bg-slate-850 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 font-semibold rounded-xl border border-gray-200 dark:border-slate-700 transition flex items-center justify-center gap-2 cursor-pointer"
              >
                <RefreshCw class="w-4 h-4 text-gray-500 dark:text-slate-400" :class="{ 'animate-spin': checkingStatus }" />
                <span>Cek Ulang Status Koneksi</span>
              </button>
            </div>
          </div>

          <!-- Uji Coba Kirim Pesan WA Card -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-slate-800 pb-3 flex items-center gap-2">
              <Send class="w-4 h-4 text-primary" />
              <span>Uji Coba Kirim Pesan Cepat</span>
            </h3>

            <form @submit.prevent="handleTestSend" class="space-y-3 text-xs">
              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Nomor Tujuan Uji Coba</label>
                <input
                  v-model="testNumber"
                  type="text"
                  required
                  placeholder="Contoh: 6281299998888"
                  class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 dark:text-slate-300 mb-1">Pesan Uji Coba</label>
                <textarea
                  v-model="testMessage"
                  rows="2"
                  required
                  placeholder="Halo, ini adalah pesan uji coba dari sistem Jurnal Kelas Digital."
                  class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                ></textarea>
              </div>

              <button
                type="submit"
                :disabled="sendingTest"
                class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition cursor-pointer disabled:opacity-50"
              >
                {{ sendingTest ? 'Mengirim...' : 'Kirim Pesan Uji Coba' }}
              </button>
            </form>
          </div>

          <!-- Log Pengiriman Pesan WhatsApp (wa_logs) -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
              <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Log Pengiriman WhatsApp</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Riwayat pesan WhatsApp yang dikirimkan sistem secara otomatis</p>
              </div>
              <button
                type="button"
                @click="fetchWaLogs"
                class="px-2.5 py-1 text-xs text-primary dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 border border-blue-200 dark:border-blue-800/50 rounded-lg font-semibold flex items-center gap-1 cursor-pointer transition shadow-2xs"
              >
                <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loadingWaLogs }" />
                <span>Segarkan</span>
              </button>
            </div>

            <div v-if="loadingWaLogs" class="py-8 text-center text-xs text-gray-400 dark:text-slate-500">
              Memuat log pengiriman WhatsApp...
            </div>
            <div v-else-if="!waLogsList.length" class="py-8 text-center text-xs text-gray-400 dark:text-slate-500">
              Belum ada riwayat pengiriman pesan WhatsApp.
            </div>
            <div v-else class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
              <div
                v-for="l in waLogsList"
                :key="l.id"
                class="p-3 bg-gray-50/70 dark:bg-slate-850 rounded-xl border border-gray-150 dark:border-slate-800 text-xs space-y-1.5"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="l.type === 'ALPHA' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50' : (l.type === 'IZIN' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700')">
                      {{ l.type }}
                    </span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ l.recipient_name || l.recipient_phone }}</span>
                    <span class="text-[10px] font-mono text-gray-400 dark:text-slate-500">({{ l.recipient_phone }})</span>
                  </div>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="l.status === 'TERKIRIM' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50'">
                    {{ l.status }}
                  </span>
                </div>
                <p class="text-[11px] text-gray-600 dark:text-slate-300 whitespace-pre-line leading-relaxed bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-gray-100 dark:border-slate-800 font-mono">
                  {{ l.message }}
                </p>
                <div class="text-[10px] text-gray-400 dark:text-slate-500 font-mono flex items-center justify-between pt-1">
                  <span>Waktu: {{ l.created_at ? new Date(l.created_at).toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</span>
                  <span v-if="l.response_payload" class="truncate max-w-[200px]" :title="l.response_payload">Respon: {{ l.response_payload }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT (7 COLS): SIARAN PENGUMUMAN SIMPEL -->
        <div class="lg:col-span-7 space-y-6">
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-4">
              <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <Radio class="w-4 h-4 text-primary" />
                  <span>Buat Siaran Pengumuman Cepat</span>
                </h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Kirimkan edaran atau pengumuman massal dalam satu klik</p>
              </div>
            </div>

            <form @submit.prevent="handleSendBroadcast" class="space-y-4 text-xs">
              <!-- Target Audiens -->
              <div>
                <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1.5">Target Audiens Penerima</label>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                  <button
                    type="button"
                    @click="broadcastForm.target_role = 'ALL'"
                    class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                    :class="broadcastForm.target_role === 'ALL' ? 'border-primary bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'"
                  >
                    Semua Pengguna
                  </button>
                  <button
                    type="button"
                    @click="broadcastForm.target_role = 'GURU'"
                    class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                    :class="broadcastForm.target_role === 'GURU' ? 'border-primary bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'"
                  >
                    Guru
                  </button>
                  <button
                    type="button"
                    @click="broadcastForm.target_role = 'SISWA'"
                    class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                    :class="broadcastForm.target_role === 'SISWA' ? 'border-primary bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'"
                  >
                    Siswa
                  </button>
                  <button
                    type="button"
                    @click="broadcastForm.target_role = 'ORANG_TUA'"
                    class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                    :class="broadcastForm.target_role === 'ORANG_TUA' ? 'border-primary bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'"
                  >
                    Orang Tua
                  </button>
                  <button
                    type="button"
                    @click="broadcastForm.target_role = 'SISWA_WALI'"
                    class="p-2.5 rounded-xl border text-center transition cursor-pointer col-span-2 sm:col-span-1"
                    :class="broadcastForm.target_role === 'SISWA_WALI' ? 'border-primary bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'"
                  >
                    Siswa & Orang Tua
                  </button>
                </div>
              </div>

              <!-- Saluran Pengiriman -->
              <div>
                <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1.5">Saluran Pengiriman</label>
                <div class="grid grid-cols-3 gap-2">
                  <button
                    type="button"
                    @click="broadcastForm.channel = 'ALL'"
                    class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                    :class="broadcastForm.channel === 'ALL' ? 'border-primary bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'"
                  >
                    Web + WhatsApp
                  </button>
                  <button
                    type="button"
                    @click="broadcastForm.channel = 'IN_APP'"
                    class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                    :class="broadcastForm.channel === 'IN_APP' ? 'border-primary bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'"
                  >
                    Notifikasi Web
                  </button>
                  <button
                    type="button"
                    @click="broadcastForm.channel = 'WHATSAPP'"
                    class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                    :class="broadcastForm.channel === 'WHATSAPP' ? 'border-primary bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'"
                  >
                    WhatsApp Bot
                  </button>
                </div>
              </div>

              <!-- Judul Siaran -->
              <div>
                <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Judul Pengumuman</label>
                <input
                  v-model="broadcastForm.title"
                  type="text"
                  required
                  placeholder="Contoh: Pemberitahuan Libur Penilaian Akhir Semester"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                />
              </div>

              <!-- Isi Pesan -->
              <div>
                <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Isi Pesan Siaran</label>
                <textarea
                  v-model="broadcastForm.message"
                  rows="4"
                  required
                  placeholder="Tuliskan detail pengumuman yang ingin disampaikan secara lengkap..."
                  class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                ></textarea>
              </div>

              <div class="pt-2 flex justify-end">
                <button
                  type="submit"
                  :disabled="broadcasting"
                  class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-bold rounded-xl transition flex items-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
                >
                  <Send class="w-4 h-4" />
                  <span>{{ broadcasting ? 'Mengirim Siaran...' : 'Kirim Siaran Sekarang' }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Riwayat Siaran Terakhir -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-slate-800 pb-3">Riwayat Siaran Terakhir</h3>

            <div v-if="loadingBroadcasts" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
              Memuat riwayat siaran...
            </div>

            <div v-else-if="!broadcastHistory.length" class="text-center py-6 text-gray-400 dark:text-slate-500 text-xs">
              Belum ada siaran pengumuman yang dikirim.
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="b in broadcastHistory"
                :key="b.id"
                class="p-3.5 bg-gray-50 dark:bg-slate-850 rounded-xl border border-gray-150 dark:border-slate-800 space-y-1.5 text-xs"
              >
                <div class="flex items-center justify-between">
                  <h4 class="font-bold text-gray-900 dark:text-white">{{ b.title }}</h4>
                  <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">{{ b.created_at ? new Date(b.created_at).toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</span>
                </div>
                <p class="text-gray-600 dark:text-slate-300 text-[11px] leading-relaxed">{{ b.message }}</p>
                <div class="flex items-center gap-3 text-[10px] text-gray-500 dark:text-slate-400 pt-1 border-t border-gray-200 dark:border-slate-800">
                  <span>Target: <strong class="text-gray-700 dark:text-slate-200">{{ b.target_role }}</strong></span>
                  <span>•</span>
                  <span>Saluran: <strong class="text-gray-700 dark:text-slate-200">{{ b.channel }}</strong></span>
                  <span>•</span>
                  <span>Penerima: <strong class="text-emerald-600 dark:text-emerald-400">{{ b.recipients_count || 0 }} Akun</strong></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Kustomisasi Template Pesan Otomatis WhatsApp -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 sm:p-6 shadow-2xs space-y-4">
            <div class="border-b border-gray-100 dark:border-slate-800 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Template Pesan Otomatis</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Kustomisasi susunan kata notifikasi otomatis ke siswa & orang tua</p>
              </div>
            </div>

            <form @submit.prevent="handleSaveTemplates" class="space-y-4 text-xs">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="font-semibold text-gray-700 dark:text-slate-300">Template Pesan Alfa Siswa</label>
                  <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">{nama}, {kelas}, {tanggal}</span>
                </div>
                <textarea
                  v-model="waTemplates.wa_template_alpha"
                  rows="4"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-xs font-mono text-gray-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                  required
                ></textarea>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="font-semibold text-gray-700 dark:text-slate-300">Template Status Surat Izin</label>
                  <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">{nama}, {kelas}, {jenis_izin}, {tanggal}, {status}, {catatan}</span>
                </div>
                <textarea
                  v-model="waTemplates.wa_template_izin"
                  rows="4"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-xs font-mono text-gray-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                  required
                ></textarea>
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="savingTemplates"
                  class="px-5 py-2 bg-primary hover:bg-primary-hover text-white font-bold rounded-xl text-xs transition shadow-xs cursor-pointer disabled:opacity-50"
                >
                  {{ savingTemplates ? 'Menyimpan...' : 'Simpan Template WhatsApp' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import {
  MessageSquare,
  Radio,
  Send,
  RefreshCw,
  CheckCircle2,
  AlertCircle,
  X,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { confirmDialog } from '~/utils/confirm'

const authStore = useAuthStore()

const successMsg = ref('')
const errorMsg = ref('')

// WA States
const waStatus = ref('disconnected')
const waNumber = ref('')
const pairPhone = ref('6281234567890')
const pairingCode = ref('')
const pairingLoading = ref(false)
const checkingStatus = ref(false)

// Test Message States
const testNumber = ref('')
const testMessage = ref('Halo, ini uji coba pesan WhatsApp dari sistem Jurnal Kelas Digital.')
const sendingTest = ref(false)

// Broadcast States
const broadcasting = ref(false)
const loadingBroadcasts = ref(false)
const broadcastHistory = ref<any[]>([])

// Template & Logs States
const waTemplates = ref({
  wa_template_alpha: '',
  wa_template_izin: '',
})
const savingTemplates = ref(false)
const waLogsList = ref<any[]>([])
const loadingWaLogs = ref(false)

const broadcastForm = ref({
  title: '',
  message: '',
  target_role: 'ALL',
  channel: 'ALL',
})

async function checkWaStatus() {
  if (!authStore.token) return
  checkingStatus.value = true
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/wa-gateway/status`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const isConn = res?.connected === true || res?.device_status === 'connected' || res?.data?.status === 'connected'
    waStatus.value = isConn ? 'connected' : 'disconnected'

    const phoneVal = res?.phone || res?.number || res?.data?.phone || (res?.user?.id ? res.user.id.split('@')[0] : '') || (res?.data?.user?.id ? res.data.user.id.split('@')[0] : '')
    waNumber.value = phoneVal ? phoneVal.split(':')[0] : ''

    if (isConn) {
      pairingCode.value = ''
    } else if (res?.pairing_code || res?.data?.pairingCode) {
      pairingCode.value = res.pairing_code || res.data.pairingCode
    }
  } catch (e) {
    waStatus.value = 'disconnected'
  } finally {
    checkingStatus.value = false
  }
}

async function handleRequestPairing() {
  if (!pairPhone.value) {
    errorMsg.value = 'Masukkan nomor HP untuk pairing WhatsApp.'
    return
  }
  pairingLoading.value = true
  errorMsg.value = ''
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/wa-gateway/pairing-code`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: { phone: pairPhone.value }
    })
    const code = res.code || res.pairing_code || res.data?.code
    if (code) {
      pairingCode.value = code
      successMsg.value = 'Kode pairing berhasil digenerate. Masukkan di WhatsApp HP Anda (Perangkat Tertaut).'
    } else {
      errorMsg.value = res.message || 'Gagal menghasilkan kode pairing.'
    }
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal meminta kode pairing.'
  } finally {
    pairingLoading.value = false
  }
}

async function handleLogoutWa() {
  const ok = await confirmDialog({
    title: 'Putuskan Bot WhatsApp?',
    message: 'Apakah Anda yakin ingin memutuskan bot WhatsApp? Sistem tidak akan dapat mengirim notifikasi otomatis hingga dipasangkan kembali.',
    confirmText: 'Ya, Putuskan',
    cancelText: 'Batal',
    type: 'warning',
  })
  if (!ok) return
  try {
    await $fetch(`${authStore.apiBase}/wa-gateway/logout`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    waStatus.value = 'disconnected'
    pairingCode.value = ''
    successMsg.value = 'Koneksi WhatsApp berhasil diputuskan.'
    await checkWaStatus()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal memutuskan WhatsApp.'
  }
}

async function handleTestSend() {
  if (!testNumber.value) {
    errorMsg.value = 'Masukkan nomor tujuan tes.'
    successMsg.value = ''
    return
  }
  sendingTest.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/wa-gateway/send-test`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        phone: testNumber.value,
        message: testMessage.value,
      }
    })
    if (res?.status === true) {
      successMsg.value = res.message || 'Pesan uji coba WhatsApp berhasil terkirim ke target!'
      errorMsg.value = ''
    } else {
      errorMsg.value = res?.message || 'Pengiriman pesan WhatsApp gagal.'
      successMsg.value = ''
    }
  } catch (e: any) {
    errorMsg.value = e?.data?.message || e?.message || 'Gagal mengirim pesan uji coba ke WhatsApp.'
    successMsg.value = ''
  } finally {
    sendingTest.value = false
  }
}

async function fetchBroadcasts() {
  if (!authStore.token) return
  loadingBroadcasts.value = true
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/system/broadcasts`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    broadcastHistory.value = res || []
  } catch (e) {
    console.warn(e)
  } finally {
    loadingBroadcasts.value = false
  }
}

async function handleSendBroadcast() {
  if (!broadcastForm.value.title || !broadcastForm.value.message) {
    errorMsg.value = 'Judul dan pesan siaran wajib diisi.'
    return
  }
  broadcasting.value = true
  errorMsg.value = ''
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/system/broadcasts`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: broadcastForm.value
    })
    successMsg.value = res.message || 'Siaran pengumuman berhasil disebarkan.'
    broadcastForm.value.title = ''
    broadcastForm.value.message = ''
    fetchBroadcasts()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menyebarkan siaran pengumuman.'
  } finally {
    broadcasting.value = false
  }
}

async function fetchWaTemplates() {
  if (!authStore.token) return
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/wa-gateway/templates`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    if (res) {
      waTemplates.value = {
        wa_template_alpha: res.wa_template_alpha || '',
        wa_template_izin: res.wa_template_izin || '',
      }
    }
  } catch (e) {
    console.warn(e)
  }
}

async function handleSaveTemplates() {
  savingTemplates.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/wa-gateway/templates`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: waTemplates.value
    })
    successMsg.value = res.message || 'Template WhatsApp berhasil disimpan!'
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menyimpan template WhatsApp.'
  } finally {
    savingTemplates.value = false
  }
}

async function fetchWaLogs() {
  if (!authStore.token) return
  loadingWaLogs.value = true
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/wa-gateway/logs`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    waLogsList.value = res || []
  } catch (e) {
    console.warn(e)
  } finally {
    loadingWaLogs.value = false
  }
}

onMounted(() => {
  if (authStore.user?.role !== 'SUPERADMIN') {
    navigateTo('/dashboard')
    return
  }
  checkWaStatus()
  fetchBroadcasts()
  fetchWaTemplates()
  fetchWaLogs()
})
</script>
