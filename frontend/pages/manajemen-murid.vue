<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      :title="pageTitle"
      subtitle="Daftar peserta didik dan kelengkapan data kontak rombel"
    />

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- 3 Stat Cards Kelas -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Murid Terdaftar</p>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ students.length }} Siswa</h3>
            <p class="text-[11px] text-gray-500 dark:text-slate-400 font-medium mt-0.5">Kelas {{ className }}</p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
            <GraduationCap class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Sekretaris Kelas</p>
            <h3 class="text-base font-bold text-gray-900 dark:text-white mt-1 truncate max-w-[200px]" :title="secretaryNames">
              {{ secretaryNames }}
            </h3>
            <p class="text-[11px] font-semibold mt-0.5" :class="secretaries.length >= 2 ? 'text-amber-600 dark:text-amber-400' : 'text-primary dark:text-blue-400'">
              {{ secretaries.length }} / 2 Kuota Maksimal Kelas
            </p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center font-bold shrink-0">
            <UserCheck class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-5 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Siswa Reguler</p>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ regularStudentCount }} Siswa</h3>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">Akun Aktif Presensi</p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center font-bold">
            <Users class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <div v-if="successMsg" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 text-xs font-bold cursor-pointer"><X class="w-3.5 h-3.5" /></button>
      </div>

      <div v-if="errorMsg" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <AlertCircle class="w-4 h-4 text-rose-600 dark:text-rose-400 flex-shrink-0" />
          <span>{{ errorMsg }}</span>
        </div>
        <button @click="errorMsg = ''" class="text-rose-700 dark:text-rose-300 hover:text-rose-900 text-xs font-bold cursor-pointer"><X class="w-3.5 h-3.5" /></button>
      </div>

      <!-- Action & Search Bar -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <!-- Search -->
          <div class="relative w-full sm:w-80">
            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="Cari nama atau NISN murid..."
              class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs sm:text-sm text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800 shadow-2xs transition"
            />
          </div>

          <!-- Role Filter -->
          <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-800 p-1 rounded-xl text-xs sm:text-sm font-semibold overflow-x-auto max-w-full h-11">
            <button
              type="button"
              @click="setRoleFilter('ALL')"
              class="px-3.5 py-1.5 rounded-lg transition cursor-pointer whitespace-nowrap shrink-0 text-xs"
              :class="selectedRoleFilter === 'ALL' ? 'bg-white dark:bg-slate-700 text-primary dark:text-white shadow-2xs font-bold border border-blue-200 dark:border-slate-600' : 'text-gray-500 dark:text-slate-400 hover:text-primary dark:hover:text-white'"
            >
              Semua Murid
            </button>
            <button
              type="button"
              @click="setRoleFilter('SEKRETARIS')"
              class="px-3.5 py-1.5 rounded-lg transition cursor-pointer whitespace-nowrap shrink-0 text-xs"
              :class="selectedRoleFilter === 'SEKRETARIS' ? 'bg-white dark:bg-slate-700 text-primary dark:text-white shadow-2xs font-bold border border-blue-200 dark:border-slate-600' : 'text-gray-500 dark:text-slate-400 hover:text-primary dark:hover:text-white'"
            >
              Sekretaris Kelas • {{ secretaries.length }}
            </button>
            <button
              type="button"
              @click="setRoleFilter('SISWA')"
              class="px-3.5 py-1.5 rounded-lg transition cursor-pointer whitespace-nowrap shrink-0 text-xs"
              :class="selectedRoleFilter === 'SISWA' ? 'bg-white dark:bg-slate-700 text-primary dark:text-white shadow-2xs font-bold border border-blue-200 dark:border-slate-600' : 'text-gray-500 dark:text-slate-400 hover:text-primary dark:hover:text-white'"
            >
              Siswa Reguler
            </button>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Tambah Murid Button -->
          <button
            type="button"
            @click="openAddModal"
            class="h-11 px-5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-bold transition flex items-center gap-2 shadow-sm hover:shadow cursor-pointer active:scale-95"
          >
            <Plus class="w-4 h-4" />
            <span>Tambah Murid</span>
          </button>
        </div>
      </div>

      <!-- Table Section -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden w-full">
        <div v-if="loading" class="py-16 text-center text-gray-400 dark:text-slate-500 text-xs">
          Memuat data murid kelas...
        </div>

        <div v-else-if="!filteredStudents.length" class="py-16 text-center text-gray-400 dark:text-slate-500 text-xs">
          Belum ada murid yang sesuai dengan filter pencarian.
        </div>

        <div v-else>
        <!-- Mobile Card List View -->
        <div class="block md:hidden space-y-3.5 p-4">
          <div
            v-for="(s, idx) in filteredStudents"
            :key="s.id"
            class="bg-white dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs space-y-3"
          >
            <!-- Header: Nama, No, Role Badge -->
            <div class="flex items-start justify-between gap-2">
              <div class="space-y-0.5">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-xs font-bold text-gray-400 dark:text-slate-500">#{{ idx + 1 }}</span>
                  <span class="font-bold text-gray-900 dark:text-white text-sm">{{ s.name }}</span>
                  <span
                    v-if="isSecretaryStudent(s)"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50"
                  >
                    Sekretaris
                  </span>
                </div>
                <div class="text-[11px] text-gray-500 dark:text-slate-400 font-mono">NISN: {{ s.nip_nis || '-' }}</div>
              </div>

              <!-- Status Akun Pill -->
              <button
                type="button"
                @click="handleToggleActive(s)"
                class="px-2.5 py-1 rounded-full text-[10px] font-bold transition cursor-pointer shrink-0"
                :class="s.is_active ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700'"
              >
                {{ s.is_active ? '● Aktif' : '○ Nonaktif' }}
              </button>
            </div>

            <!-- Kontak & Akun Ortu -->
            <div class="p-3 bg-gray-50/70 dark:bg-slate-900/60 rounded-xl space-y-2 text-xs">
              <div class="flex items-center justify-between text-gray-700 dark:text-slate-300">
                <span class="text-gray-400 dark:text-slate-500 text-[11px]">Email:</span>
                <span class="font-medium truncate max-w-[200px]">{{ s.email }}</span>
              </div>
              <div class="flex items-center justify-between text-gray-700 dark:text-slate-300">
                <span class="text-gray-400 dark:text-slate-500 text-[11px]">No WA:</span>
                <span class="font-medium">{{ s.phone || '-' }}</span>
              </div>
              <div class="pt-1.5 border-t border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                <span class="text-gray-400 dark:text-slate-500 text-[11px]">Akun Ortu:</span>
                <div v-if="s.parent_user" class="flex items-center gap-1.5">
                  <span class="text-emerald-700 dark:text-emerald-300 font-bold text-[11px]">{{ s.parent_user.name }}</span>
                  <button
                    type="button"
                    @click="openParentModal(s)"
                    class="text-primary hover:text-primary-hover text-[11px] font-semibold underline p-1 cursor-pointer"
                  >
                    Ubah
                  </button>
                </div>
                <div v-else>
                  <button
                    type="button"
                    @click="openParentModal(s)"
                    class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 text-[11px] font-bold cursor-pointer"
                  >
                    + Buat Akun Ortu
                  </button>
                </div>
              </div>
            </div>

            <!-- Action Buttons: Role Button & CRUD -->
            <div class="flex items-center gap-2 pt-1 border-t border-gray-100 dark:border-slate-800">
              <button
                type="button"
                @click="handleInitiateToggleRole(s)"
                class="flex-1 min-h-[44px] px-3 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
                :class="isSecretaryStudent(s) ? 'bg-blue-100 dark:bg-blue-900/40 text-primary dark:text-blue-300' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-primary dark:hover:text-blue-300'"
              >
                <UserCheck class="w-4 h-4 shrink-0" />
                <span>{{ isSecretaryStudent(s) ? 'Sekretaris' : 'Jadikan Sekretaris' }}</span>
              </button>

              <div class="flex items-center gap-1">
                <!-- Edit -->
                <button
                  type="button"
                  @click="openEditModal(s)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl text-gray-600 dark:text-slate-400 transition cursor-pointer"
                  title="Edit Data Siswa"
                >
                  <Edit3 class="w-4 h-4" />
                </button>

                <!-- Reset Password -->
                <button
                  type="button"
                  @click="handleResetPassword(s)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-xl text-amber-600 dark:text-amber-400 transition cursor-pointer"
                  title="Reset Kata Sandi"
                >
                  <KeyRound class="w-4 h-4" />
                </button>

                <!-- Delete -->
                <button
                  type="button"
                  @click="handleDelete(s)"
                  class="min-h-[44px] min-w-[44px] flex items-center justify-center hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl text-rose-600 dark:text-rose-400 transition cursor-pointer"
                  title="Hapus Siswa"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
          <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[850px]">
            <thead>
              <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                <th class="py-3.5 px-5 w-14 text-center whitespace-nowrap">No</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Nama Siswa & NISN</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Kontak Siswa</th>
                <th class="py-3.5 px-5 text-center whitespace-nowrap">Peran Kelas</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Akun Orang Tua Murid</th>
                <th class="py-3.5 px-5 text-center whitespace-nowrap">Status Akun</th>
                <th class="py-3.5 px-5 text-center whitespace-nowrap">Aksi Cepat</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
              <tr v-for="(s, idx) in filteredStudents" :key="s.id" class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition">
                <td class="py-4 px-5 text-center text-gray-400 dark:text-slate-500 font-medium whitespace-nowrap">{{ idx + 1 }}</td>

                <!-- Nama Siswa & NISN -->
                <td class="py-4 px-5 whitespace-nowrap">
                  <div class="font-bold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                    <span>{{ s.name }}</span>
                    <span
                      v-if="isSecretaryStudent(s)"
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 whitespace-nowrap"
                    >
                      Sekretaris
                    </span>
                  </div>
                  <div class="text-[11px] text-gray-500 dark:text-slate-400 font-mono mt-0.5">NISN: {{ s.nip_nis || '-' }}</div>
                </td>

                <!-- Kontak Siswa -->
                <td class="py-4 px-5 whitespace-nowrap">
                  <div class="text-gray-800 dark:text-slate-200 font-medium">{{ s.email }}</div>
                  <div class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">{{ s.phone ? `WA: ${s.phone}` : 'No HP: -' }}</div>
                </td>

                <!-- Peran Kelas: Siswa vs Sekretaris (Maksimal 2, Minimal 1) -->
                <td class="py-4 px-5 text-center whitespace-nowrap">
                  <button
                    type="button"
                    @click="handleInitiateToggleRole(s)"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 mx-auto cursor-pointer whitespace-nowrap shadow-2xs"
                    :class="isSecretaryStudent(s) ? 'bg-blue-100 dark:bg-blue-900/40 hover:bg-blue-200 dark:hover:bg-blue-900/60 text-primary dark:text-blue-300 font-bold' : 'bg-gray-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-primary dark:hover:text-blue-300 text-gray-600 dark:text-slate-300'"
                    :title="isSecretaryStudent(s) ? 'Klik untuk kembalikan ke Siswa Biasa' : 'Klik untuk jadikan Sekretaris Kelas'"
                  >
                    <UserCheck class="w-3.5 h-3.5" />
                    <span>{{ isSecretaryStudent(s) ? 'Sekretaris' : 'Jadikan Sekretaris' }}</span>
                  </button>
                </td>

                <!-- Akun Orang Tua Murid (Wali Kelas yang Mengelola) -->
                <td class="py-4 px-5 whitespace-nowrap">
                  <div v-if="s.parent_user" class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 px-2 py-0.5 rounded-full">
                        <Check class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                        <span>Terhubung ✓</span>
                      </span>
                      <button
                        type="button"
                        @click="openParentModal(s)"
                        class="text-primary hover:text-primary-hover text-[11px] font-semibold underline cursor-pointer"
                        title="Edit data akun orang tua"
                      >
                        Ubah
                      </button>
                    </div>
                    <div class="font-bold text-gray-800 dark:text-slate-200 text-xs">{{ s.parent_user.name }}</div>
                    <div class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">{{ s.parent_user.phone || s.parent_user.email }}</div>
                  </div>

                  <div v-else class="flex items-center gap-2">
                    <span class="text-gray-400 dark:text-slate-500 text-xs">Belum Ada</span>
                    <button
                      type="button"
                      @click="openParentModal(s)"
                      class="px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 hover:bg-primary text-primary dark:text-blue-300 hover:text-white border border-blue-200 dark:border-blue-800/50 text-xs font-bold transition cursor-pointer shadow-2xs"
                    >
                      + Buat Akun Ortu
                    </button>
                  </div>
                </td>

                <!-- Status Akun -->
                <td class="py-4 px-5 text-center whitespace-nowrap">
                  <button
                    type="button"
                    @click="handleToggleActive(s)"
                    class="px-3 py-1 rounded-full text-[11px] font-bold transition cursor-pointer whitespace-nowrap"
                    :class="s.is_active ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/60' : 'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700 hover:bg-gray-200 dark:hover:bg-slate-700'"
                  >
                    {{ s.is_active ? '● Aktif' : '○ Nonaktif' }}
                  </button>
                </td>

                <!-- Aksi Cepat -->
                <td class="py-4 px-5 text-center whitespace-nowrap">
                  <div class="inline-flex items-center gap-2">
                    <!-- Edit -->
                    <button
                      type="button"
                      @click="openEditModal(s)"
                      class="p-2 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-white transition cursor-pointer"
                      title="Edit Data Siswa"
                    >
                      <Edit3 class="w-4 h-4" />
                    </button>

                    <!-- Reset Password -->
                    <button
                      type="button"
                      @click="handleResetPassword(s)"
                      class="p-2 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-xl text-amber-600 dark:text-amber-400 transition cursor-pointer"
                      title="Reset Kata Sandi ke password123"
                    >
                      <KeyRound class="w-4 h-4" />
                    </button>

                    <!-- Delete -->
                    <button
                      type="button"
                      @click="handleDelete(s)"
                      class="p-2 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl text-rose-600 dark:text-rose-400 transition cursor-pointer"
                      title="Hapus Siswa dari Kelas"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        </div>
      </div>
    </main>

    <!-- MODAL KONFIRMASI PENGANGKATAN / PERGANTIAN SEKRETARIS KELAS -->
    <Teleport to="body">
      <div
        v-if="showSecretaryModal"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
      >
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-2xl w-full max-w-md shadow-xl overflow-hidden border border-gray-150 dark:border-slate-800 animate-in fade-in duration-150 max-h-[90vh] flex flex-col pb-safe">
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto my-2 sm:hidden shrink-0"></div>
          <div class="p-5 border-b border-gray-150 dark:border-slate-800 flex items-start justify-between gap-3">
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">
                Pengaturan Sekretaris Kelas
              </h3>
              <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Aturan: Minimal 1 orang, Maksimal 2 orang per kelas</p>
            </div>
            <button 
              type="button" 
              @click="showSecretaryModal = false" 
              class="text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer shrink-0"
              title="Tutup Modal"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

        <div class="p-5 space-y-4 text-xs flex-1 overflow-y-auto">
          <!-- Kasus 1: Kuota penuh (2 sekretaris) -> Wajib memilih siapa yang digantikan -->
          <div v-if="secretaries.length >= 2" class="space-y-3">
            <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 rounded-xl text-amber-800 dark:text-amber-300 space-y-1">
              <div class="font-bold flex items-center gap-1.5">
                <AlertCircle class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                <span>Batas Kuota Sekretaris Tercapai</span>
              </div>
              <p class="text-[11px] leading-relaxed">
                Kelas <strong>{{ className }}</strong> saat ini telah memiliki 2 sekretaris aktif. Untuk mengangkat <strong>{{ targetSecretaryStudent?.name }}</strong>, silakan pilih salah satu sekretaris yang akan digantikan:
              </p>
            </div>

            <label class="block font-bold text-gray-700 dark:text-slate-300">Pilih Sekretaris yang Digantikan</label>
            <div class="space-y-2">
              <label
                v-for="sec in secretaries"
                :key="sec.id"
                class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                :class="selectedReplacementId === sec.id ? 'border-primary bg-blue-50/50 dark:bg-blue-950/30 ring-1 ring-primary' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800/60'"
              >
                <input
                  type="radio"
                  name="replacement_sec"
                  :value="sec.id"
                  v-model="selectedReplacementId"
                  class="text-primary focus:ring-primary"
                />
                <div class="flex-1">
                  <div class="font-bold text-gray-900 dark:text-white">{{ sec.name }}</div>
                  <div class="text-[10px] text-gray-500 dark:text-slate-400">NISN: {{ sec.nip_nis || '-' }} • Akan dikembalikan menjadi Siswa Biasa</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Kasus 2: Kelas memiliki 1 sekretaris -> Pilihan: tambah jadi ke-2 atau gantikan yang lama -->
          <div v-else-if="secretaries.length === 1" class="space-y-3">
            <p class="text-gray-600 dark:text-slate-300 leading-relaxed">
              Kelas saat ini memiliki 1 orang sekretaris aktif (<strong>{{ secretaries[0]?.name }}</strong>).
              Silakan pilih opsi penugasan untuk <strong>{{ targetSecretaryStudent?.name }}</strong>:
            </p>

            <div class="space-y-2">
              <label
                class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                :class="secretaryActionType === 'add_second' ? 'border-primary bg-blue-50/50 dark:bg-blue-950/30 ring-1 ring-primary' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800/60'"
              >
                <input
                  type="radio"
                  name="sec_action"
                  value="add_second"
                  v-model="secretaryActionType"
                  class="mt-0.5 text-primary focus:ring-primary"
                />
                <div>
                  <div class="font-bold text-gray-900 dark:text-white">Tambahkan sebagai Sekretaris ke-2</div>
                  <div class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">
                    Kelas akan memiliki 2 orang sekretaris: {{ secretaries[0]?.name }} dan {{ targetSecretaryStudent?.name }}.
                  </div>
                </div>
              </label>

              <label
                class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                :class="secretaryActionType === 'replace' ? 'border-primary bg-blue-50/50 dark:bg-blue-950/30 ring-1 ring-primary' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800/60'"
              >
                <input
                  type="radio"
                  name="sec_action"
                  value="replace"
                  v-model="secretaryActionType"
                  class="mt-0.5 text-primary focus:ring-primary"
                />
                <div>
                  <div class="font-bold text-gray-900 dark:text-white">Gantikan Sekretaris Lama</div>
                  <div class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">
                    <strong>{{ secretaries[0]?.name }}</strong> akan dikembalikan menjadi Siswa Biasa, dan <strong>{{ targetSecretaryStudent?.name }}</strong> menjadi sekretaris tunggal.
                  </div>
                </div>
              </label>
            </div>
          </div>

          <div class="pt-3 border-t border-gray-150 dark:border-slate-800 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="showSecretaryModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 font-semibold cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              @click="confirmSecretaryAssignment"
              :disabled="savingSecretary"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <span>{{ savingSecretary ? 'Menyimpan...' : 'Konfirmasi Penugasan' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    </Teleport>

    <!-- MODAL KELOLA AKUN ORANG TUA / WALI MURID (WALI KELAS YANG MENGELOLA) -->
    <Teleport to="body">
      <div
        v-if="showParentModal"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
      >
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-2xl w-full max-w-md shadow-xl overflow-hidden border border-gray-150 dark:border-slate-800 animate-in fade-in duration-150 max-h-[90vh] flex flex-col pb-safe">
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto my-2 sm:hidden shrink-0"></div>
          <div class="p-5 border-b border-gray-150 dark:border-slate-800 flex items-start justify-between gap-3">
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">
                Integrasi Akun Orang Tua Murid
              </h3>
              <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                Murid: <strong>{{ targetParentStudent?.name }}</strong> (NISN: {{ targetParentStudent?.nip_nis || '-' }})
              </p>
            </div>
            <button 
              type="button" 
              @click="showParentModal = false" 
              class="text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer shrink-0"
              title="Tutup Modal"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

        <form @submit.prevent="submitParentForm" class="p-5 space-y-4 text-xs flex-1 overflow-y-auto">
          <div>
            <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Nama Orang Tua / Wali</label>
            <input
              v-model="parentForm.name"
              type="text"
              required
              placeholder=""
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Nomor WhatsApp Orang Tua</label>
            <input
              v-model="parentForm.phone"
              type="text"
              required
              placeholder=""
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">Digunakan untuk menerima notifikasi presensi kehadiran, surat izin, dan OTP WhatsApp.</p>
          </div>

          <div>
            <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Email Akun Orang Tua</label>
            <input
              v-model="parentForm.email"
              type="email"
              placeholder="ortu.siswa@sekolah.sch.id"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Kata Sandi Baru</label>
            <input
              v-model="parentForm.password"
              type="password"
              placeholder="Kosongkan jika tidak ingin diubah"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>

          <div class="pt-3 border-t border-gray-150 dark:border-slate-800 flex items-center justify-between gap-2">
            <div>
              <button
                v-if="targetParentStudent?.parent_user"
                type="button"
                @click="handleDisconnectParent"
                :disabled="savingParent"
                class="px-3 py-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 text-xs font-bold transition cursor-pointer"
              >
                Putuskan Hubungan
              </button>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="showParentModal = false"
                class="px-4 py-2 rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 font-semibold cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="savingParent"
                class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <span>{{ savingParent ? 'Menyimpan...' : 'Simpan & Hubungkan Akun Ortu' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
    </Teleport>

    <!-- MODAL TAMBAH / EDIT MURID -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
      >
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-2xl w-full max-w-md shadow-xl overflow-hidden border border-gray-150 dark:border-slate-800 animate-in fade-in duration-150 max-h-[90vh] flex flex-col pb-safe">
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto my-2 sm:hidden shrink-0"></div>
          <div class="p-5 border-b border-gray-150 dark:border-slate-800 flex items-start justify-between gap-3">
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">
                {{ isEditing ? 'Edit Data Murid' : 'Tambah Murid' }}
              </h3>
              <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Kelas: {{ className }}</p>
            </div>
            <button 
              type="button" 
              @click="showModal = false" 
              class="text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer shrink-0"
              title="Tutup Modal"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

        <form @submit.prevent="handleSubmit" class="p-5 space-y-4 text-xs flex-1 overflow-y-auto">
          <!-- Peran Siswa -->
          <div>
            <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1.5">Peran Siswa di Kelas</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="form.role = 'SISWA'"
                class="p-3 rounded-xl border text-left transition cursor-pointer"
                :class="form.role === 'SISWA' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 font-bold' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300'"
              >
                <div class="font-bold">Siswa Biasa</div>
                <div class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">Murid kelas reguler</div>
              </button>

              <button
                type="button"
                @click="form.role = 'SEKRETARIS'"
                class="p-3 rounded-xl border text-left transition cursor-pointer"
                :class="form.role === 'SEKRETARIS' ? 'border-primary bg-blue-50/50 dark:bg-blue-950/40 text-primary dark:text-blue-300 font-bold shadow-2xs ring-1 ring-primary/20' : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-500 dark:text-slate-400 hover:border-gray-300 dark:hover:border-slate-600 hover:text-gray-700 dark:hover:text-slate-200'"
              >
                <div class="font-bold">Sekretaris Kelas</div>
                <div class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">Maks. 2 orang per kelas</div>
              </button>
            </div>
          </div>

          <!-- Nama Siswa -->
          <div>
            <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Nama Siswa</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder=""
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>

          <!-- NIS & No WA -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">NIS</label>
              <input
                v-model="form.nip_nis"
                type="text"
                inputmode="numeric"
                @input="form.nip_nis = form.nip_nis.replace(/\D/g, '')"
                required
                placeholder="Nomor Induk Siswa"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">No. WhatsApp Siswa</label>
              <input
                v-model="form.phone"
                type="text"
                placeholder=""
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              />
            </div>
          </div>

          <!-- Email -->
          <div>
            <label class="block font-bold text-gray-700 dark:text-slate-300 mb-1">Email Siswa</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder=""
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>

          <div class="pt-3 border-t border-gray-150 dark:border-slate-800 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 font-semibold cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Perbarui Murid' : 'Simpan Murid') }}</span>
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
  GraduationCap,
  Users,
  UserCheck,
  Search,
  Plus,
  Edit3,
  KeyRound,
  Trash2,
  CheckCircle2,
  AlertCircle,
  X,
  UploadCloud,
  Download,
  FileCode,
  Check,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useToastStore } from '~/stores/toast'

const authStore = useAuthStore()
const toast = useToastStore()

const students = ref<any[]>([])
const loading = ref(true)
const saving = ref(false)

const searchQuery = ref('')
const selectedRoleFilter = ref('ALL')

const successMsg = ref('')
const errorMsg = ref('')

const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)

const form = ref({
  name: '',
  email: '',
  nip_nis: '',
  phone: '',
  role: 'SISWA',
})

// State Modal Pengaturan Sekretaris
const showSecretaryModal = ref(false)
const targetSecretaryStudent = ref<any>(null)
const selectedReplacementId = ref<number | null>(null)
const secretaryActionType = ref<'add_second' | 'replace'>('add_second')
const savingSecretary = ref(false)

// State Modal Integrasi Orang Tua Murid
const showParentModal = ref(false)
const targetParentStudent = ref<any>(null)
const savingParent = ref(false)
const parentForm = reactive({
  name: '',
  phone: '',
  email: '',
  password: ''
})

const className = computed(() => {
  return authStore.user?.guided_class?.name || authStore.user?.school_class?.name || 'Binaan Anda'
})

const pageTitle = computed(() => {
  return `Manajemen Murid ${className.value.startsWith('Kelas') ? className.value : 'Kelas ' + className.value}`
})

const isSecretaryStudent = (s: any) => s.role === 'SEKRETARIS' || s.is_secretary === true

const secretaries = computed(() => students.value.filter(s => isSecretaryStudent(s)))

const secretaryNames = computed(() => {
  if (secretaries.value.length === 0) return 'Belum Ditentukan'
  return secretaries.value.map(s => s.name).join(', ')
})

const regularStudentCount = computed(() => students.value.filter(s => !isSecretaryStudent(s)).length)

const filteredStudents = computed(() => {
  return students.value.filter(s => {
    if (selectedRoleFilter.value === 'SEKRETARIS' && !isSecretaryStudent(s)) {
      return false
    }
    if (selectedRoleFilter.value === 'SISWA' && isSecretaryStudent(s)) {
      return false
    }
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase()
      const matchName = s.name?.toLowerCase().includes(q)
      const matchNis = s.nip_nis?.toLowerCase().includes(q)
      const matchEmail = s.email?.toLowerCase().includes(q)
      const matchParent = s.parent_user?.name?.toLowerCase().includes(q)
      return matchName || matchNis || matchEmail || matchParent
    }
    return true
  })
})

async function fetchClassStudents() {
  if (!authStore.token) return
  loading.value = true
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/users`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    students.value = res.data || []
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal memuat data murid kelas.'
  } finally {
    loading.value = false
  }
}

let searchTimer: any = null
function handleSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    // client-side reactive filter
  }, 200)
}

function setRoleFilter(role: string) {
  selectedRoleFilter.value = role
}

function openAddModal() {
  isEditing.value = false
  editingId.value = null
  form.value = {
    name: '',
    email: '',
    nip_nis: '',
    phone: '',
    role: 'SISWA',
  }
  showModal.value = true
}

function openEditModal(student: any) {
  isEditing.value = true
  editingId.value = student.id
  form.value = {
    name: student.name,
    email: student.email,
    nip_nis: student.nip_nis || '',
    phone: student.phone || '',
    role: student.role,
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function handleSubmit() {
  saving.value = true
  errorMsg.value = ''
  try {
    const url = isEditing.value
      ? `${authStore.apiBase}/users/${editingId.value}`
      : `${authStore.apiBase}/users`
    const method = isEditing.value ? 'PUT' : 'POST'

    const res = await $fetch<any>(url, {
      method,
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: form.value,
    })

    successMsg.value = res.message || 'Data murid berhasil disimpan.'
    closeModal()
    fetchClassStudents()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menyimpan data murid.'
  } finally {
    saving.value = false
  }
}

// ==========================================
// PENGATURAN SEKRETARIS KELAS (MIN 1, MAX 2)
// ==========================================
async function handleInitiateToggleRole(student: any) {
  if (isSecretaryStudent(student)) {
    // Upaya menurunkan menjadi SISWA Biasa: Cek batas minimal 1 sekretaris
    if (secretaries.value.length <= 1) {
      toast.error('Kelas wajib memiliki minimal 1 orang sekretaris aktif untuk pengisian presensi dan agenda KBM kelas! Angkat sekretaris pengganti terlebih dahulu sebelum menurunkan siswa ini.')
      return
    }

    const ok = await confirmDialog({
      title: 'Kembalikan ke Siswa Biasa?',
      message: `Ubah peran "${student.name}" menjadi Siswa Biasa? Kelas masih memiliki 1 orang sekretaris lainnya.`,
      confirmText: 'Ya, Kembalikan ke Siswa Biasa',
      cancelText: 'Batal',
      type: 'warning',
    })
    if (!ok) return

    try {
      await $fetch(`${authStore.apiBase}/users/${student.id}`, {
        method: 'PUT',
        headers: { Authorization: `Bearer ${authStore.token}` },
        body: { role: 'SISWA' },
      })
      successMsg.value = `Peran ${student.name} berhasil diubah menjadi Siswa Biasa.`
      fetchClassStudents()
    } catch (e: any) {
      errorMsg.value = e?.data?.message || 'Gagal mengubah peran siswa.'
    }
    return
  }

  // Upaya mengangkat SISWA menjadi SEKRETARIS:
  targetSecretaryStudent.value = student

  if (secretaries.value.length >= 2) {
    // Kuota penuh -> Wajib pilih pengganti
    selectedReplacementId.value = secretaries.value[0]?.id || null
    showSecretaryModal.value = true
    return
  }

  if (secretaries.value.length === 1) {
    // Sudah ada 1 -> Berikan opsi: jadikan ke-2 atau gantikan yang lama
    secretaryActionType.value = 'add_second'
    selectedReplacementId.value = secretaries.value[0]?.id || null
    showSecretaryModal.value = true
    return
  }

  // Jika belum ada sekretaris (0 sekretaris):
  const ok = await confirmDialog({
    title: 'Angkat Sekretaris Kelas?',
    message: `Angkat "${student.name}" sebagai Sekretaris Kelas ${className.value}? Siswa akan mendapatkan akses mengelola presensi harian dan agenda KBM kelas.`,
    confirmText: 'Angkat Jadi Sekretaris',
    cancelText: 'Batal',
    type: 'primary',
  })
  if (!ok) return

  try {
    await $fetch(`${authStore.apiBase}/users/${student.id}`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: { role: 'SEKRETARIS' },
    })
    successMsg.value = `Peran ${student.name} berhasil diangkat sebagai Sekretaris Kelas.`
    fetchClassStudents()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal mengangkat sekretaris kelas.'
  }
}

async function confirmSecretaryAssignment() {
  if (!targetSecretaryStudent.value) return
  savingSecretary.value = true
  try {
    const payload: any = { role: 'SEKRETARIS' }

    if (secretaries.value.length >= 2) {
      payload.replace_secretary_id = selectedReplacementId.value
    } else if (secretaries.value.length === 1 && secretaryActionType.value === 'replace') {
      payload.replace_secretary_id = secretaries.value[0]?.id
    }

    const res: any = await $fetch(`${authStore.apiBase}/users/${targetSecretaryStudent.value.id}`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: payload,
    })

    successMsg.value = res?.message || `Sekretaris kelas berhasil diperbarui.`
    showSecretaryModal.value = false
    await fetchClassStudents()
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal memperbarui sekretaris kelas.')
  } finally {
    savingSecretary.value = false
  }
}

// ==========================================
// INTEGRASI AKUN ORANG TUA OLEH WALI KELAS
// ==========================================
function openParentModal(student: any) {
  targetParentStudent.value = student
  parentForm.name = student.parent_user?.name || ('Bapak/Ibu ' + student.name)
  parentForm.phone = student.parent_user?.phone || student.phone || ''
  parentForm.email = student.parent_user?.email || ''
  parentForm.password = ''
  showParentModal.value = true
}

async function submitParentForm() {
  if (!targetParentStudent.value) return
  if (!parentForm.name || !parentForm.phone) {
    toast.warning('Nama dan Nomor WhatsApp Orang Tua wajib diisi.')
    return
  }
  savingParent.value = true
  try {
    const res: any = await $fetch(`${authStore.apiBase}/users/${targetParentStudent.value.id}`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        parent_name: parentForm.name,
        parent_phone: parentForm.phone,
        parent_email: parentForm.email,
        parent_password: parentForm.password || undefined,
      }
    })

    successMsg.value = res?.message || 'Akun Orang Tua berhasil diintegrasikan!'
    showParentModal.value = false
    await fetchClassStudents()
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal mengintegrasikan akun orang tua.')
  } finally {
    savingParent.value = false
  }
}

async function handleDisconnectParent() {
  if (!targetParentStudent.value) return
  const ok = await confirmDialog({
    title: 'Putuskan Hubungan Orang Tua?',
    message: `Putuskan integrasi akun Orang Tua dari murid "${targetParentStudent.value.name}"? Akun Orang Tua tidak akan lagi menerima notifikasi atau memantau presensi murid ini.`,
    confirmText: 'Ya, Putuskan Hubungan',
    cancelText: 'Batal',
    type: 'danger',
  })
  if (!ok) return

  savingParent.value = true
  try {
    const res: any = await $fetch(`${authStore.apiBase}/users/${targetParentStudent.value.id}`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: { disconnect_parent: true }
    })
    toast.success(res?.message || 'Integrasi akun Orang Tua berhasil diputus.')
    showParentModal.value = false
    await fetchClassStudents()
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal memutuskan hubungan akun orang tua.')
  } finally {
    savingParent.value = false
  }
}

async function handleToggleActive(student: any) {
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/users/${student.id}/toggle-active`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    student.is_active = res.is_active
    successMsg.value = `Status akun ${student.name} berhasil diubah.`
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal memperbarui status akun.'
  }
}

async function handleResetPassword(student: any) {
  const ok = await confirmDialog({
    title: 'Reset Sandi Murid?',
    message: `Reset kata sandi murid "${student.name}" menjadi default ("password123")?`,
    confirmText: 'Ya, Reset Sandi',
    cancelText: 'Batal',
    type: 'warning',
  })
  if (!ok) return
  try {
    const res = await $fetch<any>(`${authStore.apiBase}/users/${student.id}/reset-password`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    successMsg.value = res.message || `Kata sandi ${student.name} berhasil direset ke password123.`
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal mereset kata sandi.'
  }
}

async function handleDelete(student: any) {
  const ok = await confirmDialog({
    title: 'Hapus Murid Dari Kelas?',
    message: `Apakah Anda yakin ingin menghapus murid "${student.name}" dari kelas ini?`,
    confirmText: 'Ya, Hapus Murid',
    cancelText: 'Batal',
    type: 'danger',
  })
  if (!ok) return
  try {
    await $fetch(`${authStore.apiBase}/users/${student.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    successMsg.value = `Murid ${student.name} berhasil dihapus dari kelas.`
    fetchClassStudents()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal menghapus data murid.'
  }
}

onMounted(() => {
  fetchClassStudents()
})
</script>
