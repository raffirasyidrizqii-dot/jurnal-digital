<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Manajemen Pengguna & Hubungan Akun"
      subtitle="Pengelolaan akun guru, wali kelas, siswa, orang tua, dan hak akses sistem"
    />

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Success / Error Alert -->
      <div v-if="successMsg" class="p-4 sm:p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold">
          <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 text-xs font-bold cursor-pointer p-1"><X class="w-4 h-4" /></button>
      </div>

      <!-- DAFTAR PENGGUNA -->
      <div class="space-y-6">
        <!-- Filter Bar & Tambah User Button -->
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-3 flex-1">
            <!-- Search -->
            <div class="relative w-full sm:w-80">
              <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari nama, NIP, NIS, atau anak..."
                class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-xs sm:text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white shadow-2xs transition"
              />
            </div>

            <!-- Role Dropdown -->
            <div class="relative">
              <select
                v-model="roleFilter"
                class="h-11 px-4 rounded-xl border border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-xs sm:text-sm font-medium text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white shadow-2xs cursor-pointer transition"
              >
                <option value="Semua Peran">Semua Peran</option>
                <option value="GURU_PEMBIMBING">Wali Kelas</option>
                <option value="GURU_PENGAJAR">Guru</option>
                <option value="SUPERADMIN">Super Administrator</option>
                <option value="SEKRETARIS">Sekretaris</option>
                <option value="SISWA">Siswa</option>
                <option value="ORANG_TUA">Orang Tua</option>
              </select>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <!-- Tambah Pengguna Button -->
            <button
              type="button"
              @click="showAddDrawer = true"
              class="h-11 px-5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-bold transition flex items-center gap-2 shadow-sm hover:shadow cursor-pointer active:scale-95"
            >
              <Plus class="w-4 h-4" />
              <span>Tambah Pengguna</span>
            </button>
          </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden w-full">
          <div v-if="loadingUsers" class="py-16 text-center text-gray-400 dark:text-slate-500 text-xs">
            Memuat data pengguna...
          </div>

          <div v-else-if="filteredUsers.length === 0" class="py-16 text-center text-gray-400 dark:text-slate-500 text-xs">
            Tidak ada pengguna yang sesuai dengan filter pencarian.
          </div>

          <div v-else>
            <!-- Mobile Card List View -->
            <div class="block md:hidden space-y-3 p-3">
              <div
                v-for="(u, idx) in filteredUsers"
                :key="'mob-u-' + (u.id || idx)"
                class="p-4 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-850 shadow-2xs space-y-3"
              >
                <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-2.5">
                  <div class="min-w-0 flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 font-bold text-xs flex items-center justify-center shrink-0">
                      {{ idx + 1 }}
                    </span>
                    <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shrink-0 flex items-center justify-center shadow-2xs">
                      <img v-if="u.avatar_url || u.avatar" :src="u.avatar_url || u.avatar" :alt="u.name" class="w-full h-full object-cover" />
                      <span v-else class="text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">{{ u.name?.charAt(0) || 'U' }}</span>
                    </div>
                    <div class="min-w-0">
                      <p class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ u.name }}</p>
                      <p class="text-[11px] text-gray-400 dark:text-slate-500 font-mono truncate">{{ u.email }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-1.5 flex-wrap justify-end shrink-0">
                    <span
                      class="px-2.5 py-0.5 rounded-md text-[10px] font-bold shrink-0"
                      :class="{
                        'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50': u.role === 'SUPERADMIN',
                        'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50': u.role === 'GURU_PEMBIMBING' || u.role === 'GURU_PENGAJAR',
                        'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700': u.role === 'SEKRETARIS' || u.role === 'SISWA',
                        'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50': u.role === 'ORANG_TUA',
                      }"
                    >
                      {{ formatRoleLabel(u.role) }}
                    </span>
                    <span
                      v-if="(u.role === 'GURU_PENGAJAR' || u.role === 'GURU_PEMBIMBING') && (u.guided_class || u.guidedClass || u.is_homeroom_teacher)"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 shadow-2xs"
                      title="Merangkap sebagai Wali Kelas"
                    >
                      <GraduationCap class="w-3 h-3 text-emerald-600 dark:text-emerald-400 shrink-0" />
                      <span>Walas: {{ (u.guided_class?.name || u.guidedClass?.name || 'Binaan') }}</span>
                    </span>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs bg-gray-50/70 dark:bg-slate-900/60 p-2.5 rounded-xl border border-gray-100 dark:border-slate-800">
                  <div>
                    <span class="text-gray-400 dark:text-slate-500 text-[10px] uppercase block">NIP / NIS</span>
                    <span class="font-mono text-gray-700 dark:text-slate-300 font-semibold">{{ u.nip_nis || '-' }}</span>
                  </div>
                  <div>
                    <span class="text-gray-400 dark:text-slate-500 text-[10px] uppercase block">Kelas</span>
                    <span class="text-gray-700 dark:text-slate-300 font-medium">{{ u.class_name || (u.school_class?.name || '-') }}</span>
                  </div>
                </div>

                <!-- Child connection badge for ORANG_TUA -->
                <div v-if="u.role === 'ORANG_TUA'">
                  <span
                    v-if="u.student"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 w-full"
                  >
                    <GraduationCap class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span class="truncate">Anak: {{ u.student.name }} ({{ u.student.school_class?.name || (u.student.class_id ? 'Kelas ' + u.student.class_id : '-') }})</span>
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 w-full"
                  >
                    Belum terhubung ke anak
                  </span>
                </div>

                <!-- Quick action buttons on mobile with 44px min tap area -->
                <div class="flex items-center gap-1.5 pt-2 border-t border-gray-100 dark:border-slate-800">
                  <button
                    v-if="authStore.user?.role === 'SUPERADMIN' && u.id !== authStore.user?.id"
                    @click="handleImpersonate(u)"
                    class="flex-1 px-2.5 py-2 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1 min-h-[44px]"
                    title="Masuk sebagai akun ini"
                  >
                    <LogIn class="w-3.5 h-3.5" />
                    <span class="truncate">Login As</span>
                  </button>
                  <div class="flex items-center gap-1.5 shrink-0" :class="{ 'w-full justify-end': authStore.user?.role !== 'SUPERADMIN' || u.id === authStore.user?.id }">
                    <button
                      @click="openEditModal(u)"
                      class="p-2.5 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 rounded-xl text-xs font-medium transition min-h-[44px] min-w-[44px] flex items-center justify-center cursor-pointer"
                      title="Edit Data Pengguna"
                    >
                      <Pencil class="w-4 h-4 text-primary dark:text-blue-300" />
                    </button>
                    <button
                      @click="handleResetPassword(u)"
                      class="p-2.5 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-xl text-xs font-medium transition min-h-[44px] min-w-[44px] flex items-center justify-center cursor-pointer"
                      title="Reset Sandi"
                    >
                      <KeyRound class="w-4 h-4 text-gray-500 dark:text-slate-400" />
                    </button>
                    <button
                      @click="handleToggleActive(u)"
                      class="p-2.5 rounded-xl border text-xs transition min-h-[44px] min-w-[44px] flex items-center justify-center cursor-pointer"
                      :class="u.is_active !== false ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50' : 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800/50'"
                      title="Ubah Status Aktif"
                    >
                      <Power class="w-4 h-4" />
                    </button>
                    <button
                      v-if="authStore.user?.role === 'SUPERADMIN' && u.id !== authStore.user?.id"
                      @click="handleDeleteUser(u)"
                      class="p-2.5 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 rounded-xl text-xs font-medium transition min-h-[44px] min-w-[44px] flex items-center justify-center cursor-pointer"
                      title="Hapus Akun"
                    >
                      <Trash2 class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
              <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[800px]">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                  <th class="py-3.5 px-5 w-14 text-center">No</th>
                  <th class="py-3.5 px-5">Nama & Akun</th>
                  <th class="py-3.5 px-5">NIP / NIS</th>
                  <th class="py-3.5 px-5">Peran</th>
                  <th class="py-3.5 px-5">Kelas</th>
                  <th class="py-3.5 px-5 text-center">Aksi Cepat</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                <tr
                  v-for="(u, idx) in filteredUsers"
                  :key="u.id || idx"
                  class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition"
                >
                  <td class="py-4 px-5 text-center font-medium text-gray-400 dark:text-slate-500">
                    {{ idx + 1 }}
                  </td>
                  <td class="py-4 px-5">
                    <div class="flex items-center gap-3.5">
                      <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shrink-0 flex items-center justify-center shadow-2xs">
                        <img v-if="u.avatar_url || u.avatar" :src="u.avatar_url || u.avatar" :alt="u.name" class="w-full h-full object-cover" />
                        <span v-else class="text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">{{ u.name?.charAt(0) || 'U' }}</span>
                      </div>
                      <div class="min-w-0">
                        <p class="font-bold text-gray-900 dark:text-slate-100 text-sm">{{ u.name }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500 font-mono">{{ u.email }}</p>
                        <!-- Child connection badge for ORANG_TUA -->
                        <div v-if="u.role === 'ORANG_TUA'" class="mt-1">
                      <span
                        v-if="u.student"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50"
                      >
                        <GraduationCap class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                        Anak: {{ u.student.name }} ({{ u.student.school_class?.name || (u.student.class_id ? 'Kelas ' + u.student.class_id : '-') }})
                      </span>
                      <span
                        v-else-if="u.specialization"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 border border-blue-100 dark:border-blue-900/40"
                      >
                        {{ u.specialization }}
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50"
                      >
                        Belum terhubung ke anak
                      </span>
                    </div>
                  </div>
                </div>
              </td>
                  <td class="py-4 px-5 font-mono text-xs text-gray-600 dark:text-slate-400">
                    {{ u.nip_nis || '-' }}
                  </td>
                  <td class="py-4 px-5">
                    <div class="flex flex-wrap items-center gap-1.5">
                      <span
                        class="px-2.5 py-1 rounded-md text-[11px] font-bold"
                        :class="{
                          'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50': u.role === 'SUPERADMIN',
                          'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50': u.role === 'GURU_PEMBIMBING' || u.role === 'GURU_PENGAJAR',
                          'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700': u.role === 'SEKRETARIS' || u.role === 'SISWA',
                          'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50': u.role === 'ORANG_TUA',
                        }"
                      >
                        {{ formatRoleLabel(u.role) }}
                      </span>
                      <span
                        v-if="(u.role === 'GURU_PENGAJAR' || u.role === 'GURU_PEMBIMBING') && (u.guided_class || u.guidedClass || u.is_homeroom_teacher)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 shadow-2xs"
                        title="Merangkap sebagai Wali Kelas"
                      >
                        <GraduationCap class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span>Walas: {{ (u.guided_class?.name || u.guidedClass?.name || 'Binaan') }}</span>
                      </span>
                    </div>
                  </td>
                  <td class="py-4 px-5 text-gray-600 dark:text-slate-400 font-medium">
                    {{ u.class_name || (u.school_class?.name || '-') }}
                  </td>
                  <td class="py-4 px-5 text-center">
                    <div class="inline-flex items-center gap-2">
                      <!-- Login As (Impersonate) for Superadmin -->
                      <button
                        v-if="authStore.user?.role === 'SUPERADMIN' && u.id !== authStore.user?.id"
                        @click="handleImpersonate(u)"
                        class="px-2.5 py-1.5 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        title="Masuk sebagai pengguna ini"
                      >
                        <LogIn class="w-3.5 h-3.5" />
                        <span>Masuk As</span>
                      </button>

                      <!-- Edit Pengguna & Hubungan Siswa -->
                      <button
                        @click="openEditModal(u)"
                        class="p-2 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 rounded-lg transition cursor-pointer shadow-2xs"
                        title="Edit Data Pengguna & Hubungan Siswa"
                      >
                        <Pencil class="w-3.5 h-3.5 text-primary dark:text-blue-300" />
                      </button>

                      <!-- Reset Password Button -->
                      <button
                        @click="handleResetPassword(u)"
                        class="p-2 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg transition cursor-pointer shadow-2xs"
                        title="Reset kata sandi pengguna"
                      >
                        <KeyRound class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400" />
                      </button>

                      <!-- Toggle Active Status -->
                      <button
                        @click="handleToggleActive(u)"
                        class="p-2 rounded-lg border transition cursor-pointer shadow-2xs"
                        :class="u.is_active !== false ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/60' : 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800/50 hover:bg-red-100 dark:hover:bg-red-900/60'"
                        :title="u.is_active !== false ? 'Akun Aktif (Klik untuk nonaktifkan)' : 'Akun Nonaktif (Klik untuk aktifkan)'"
                      >
                        <Power class="w-3.5 h-3.5" />
                      </button>

                      <!-- Delete User (Superadmin) -->
                      <button
                        v-if="authStore.user?.role === 'SUPERADMIN' && u.id !== authStore.user?.id"
                        @click="handleDeleteUser(u)"
                        class="p-2 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 rounded-lg transition cursor-pointer shadow-2xs"
                        title="Hapus Pengguna"
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

        <!-- Modal Card Melayang: Tambah Pengguna Baru (Floating Pop-up / Bottom Sheet) -->
        <Teleport to="body">
          <div
            v-if="showAddDrawer"
            class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
          >
            <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl max-w-md w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe border border-gray-150 dark:border-slate-800">
              <!-- Mobile drag handle -->
              <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
              <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                <div>
                  <h3 class="text-base font-bold text-gray-900 dark:text-white">Tambah Pengguna</h3>
                  <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Daftarkan akun dan alokasi peran pengguna</p>
                </div>
                <button
                  type="button"
                  @click="showAddDrawer = false"
                  class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition cursor-pointer"
                  title="Tutup"
                >
                  <X class="w-5 h-5" />
                </button>
              </div>

            <form @submit.prevent="submitCreateUser" class="space-y-3.5 text-xs">
              <!-- Foto Profil / Avatar Siswa & Guru (Opsional) -->
              <div class="flex items-center gap-3.5 p-3 bg-gray-50/70 dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800">
                <div class="relative w-14 h-14 rounded-full overflow-hidden bg-white dark:bg-slate-800 border-2 border-dashed border-gray-300 dark:border-slate-700 flex items-center justify-center shrink-0 shadow-2xs">
                  <img v-if="newUserAvatarPreview" :src="newUserAvatarPreview" class="w-full h-full object-cover" />
                  <Camera v-else class="w-6 h-6 text-gray-400 dark:text-slate-500" />
                </div>
                <div class="flex-1 min-w-0">
                  <label class="block text-xs font-bold text-gray-800 dark:text-slate-200 mb-1">Foto Profil</label>
                  <div class="flex items-center gap-2">
                    <label class="px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 border border-gray-200 dark:border-slate-700 rounded-xl text-xs font-semibold cursor-pointer transition shadow-2xs">
                      <span>{{ newUserAvatarPreview ? 'Ganti Foto' : 'Pilih Foto Profil' }}</span>
                      <input
                        type="file"
                        accept="image/png,image/jpeg,image/jpg,image/webp"
                        @change="handleNewUserAvatarChange"
                        class="hidden"
                      />
                    </label>
                    <button
                      v-if="newUserAvatarPreview"
                      type="button"
                      @click="clearNewUserAvatar"
                      class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-medium cursor-pointer"
                    >
                      Hapus
                    </button>
                  </div>
                  <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">Format foto JPG, PNG, atau WEBP maks 5MB.</p>
                </div>
              </div>

              <!-- Nama Lengkap -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Nama Lengkap
                </label>
                <input
                  v-model="newUser.name"
                  type="text"
                  placeholder=""
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                  required
                />
              </div>

              <!-- Peran Sistem -->
              <div>
                <label class="block text-xs font-bold text-gray-800 dark:text-slate-200 mb-2">
                  Peran Pengguna *
                </label>
                <div class="grid grid-cols-2 gap-2.5">
                  <label
                    v-for="r in [
                      { value: 'GURU_PENGAJAR', label: 'Guru', desc: 'Isi KBM & materi' },
                      { value: 'SISWA', label: 'Siswa', desc: 'Akses siswa kelas' },
                      { value: 'SEKRETARIS', label: 'Sekretaris', desc: 'Presensi kelas' },
                      { value: 'ORANG_TUA', label: 'Orang Tua', desc: 'Pantau kehadiran' },
                      { value: 'SUPERADMIN', label: 'Super Administrator', desc: 'Akses sistem penuh' }
                    ]"
                    :key="r.value"
                    class="flex flex-col p-2.5 rounded-xl border cursor-pointer transition select-none"
                    :class="newUser.role === r.value ? 'border-primary bg-primary/5 dark:bg-primary/20 ring-1 ring-primary' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-850 bg-white dark:bg-slate-800'"
                  >
                    <div class="flex items-center gap-2">
                      <input
                        type="radio"
                        v-model="newUser.role"
                        :value="r.value"
                        class="text-primary focus:ring-primary w-3.5 h-3.5"
                      />
                      <span class="font-bold text-gray-900 dark:text-white text-xs">{{ r.label }}</span>
                    </div>
                    <span class="text-[10px] text-gray-400 dark:text-slate-400 mt-1 pl-5">{{ r.desc }}</span>
                  </label>
                </div>
              </div>

              <!-- Opsi Rangkap Wali Kelas untuk Guru -->
              <div v-if="newUser.role === 'GURU_PENGAJAR'" class="p-3 bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-2xl space-y-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input
                    type="checkbox"
                    v-model="newUser.is_also_walas"
                    class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer"
                  />
                  <span class="text-xs font-bold text-amber-900 dark:text-amber-300">Sekaligus Ditugaskan sebagai Wali Kelas</span>
                </label>
                <div v-if="newUser.is_also_walas" class="pt-1">
                  <label class="block text-[11px] font-semibold text-amber-800 dark:text-amber-400 mb-1">Pilih Kelas Binaan:</label>
                  <select
                    v-model="newUser.advisor_class_id"
                    class="w-full px-3 py-2 rounded-xl border border-amber-300 dark:border-amber-700 text-xs text-gray-800 dark:text-slate-100 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  >
                    <option value="">-- Pilih Kelas Perwalian --</option>
                    <option v-for="c in classesList" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
              </div>

              <!-- Hubungkan dengan Siswa (Jika Peran Orang Tua) -->
              <div v-if="newUser.role === 'ORANG_TUA'" class="p-3.5 bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/50 rounded-2xl space-y-2">
                <label class="block text-xs font-bold text-primary dark:text-blue-300 flex items-center gap-1.5">
                  <GraduationCap class="w-4 h-4 text-primary dark:text-blue-300" />
                  <span>Pilih Siswa</span>
                </label>
                <select
                  v-model="newUser.student_id"
                  @change="onStudentSelectForNewUser"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-blue-200 dark:border-blue-700 text-xs text-gray-800 dark:text-slate-100 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer"
                  required
                >
                  <option value="">-- Pilih Siswa yang Terhubung --</option>
                  <option v-for="s in studentsList" :key="s.id" :value="s.id">
                    {{ s.name }} (NIS: {{ s.nip_nis || '-' }}) - {{ s.school_class?.name || (s.class_id ? 'Kelas ' + s.class_id : 'Tanpa Kelas') }}
                  </option>
                </select>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                  Akun Orang Tua ini akan otomatis dapat memantau presensi, surat izin, dan jurnal kegiatan belajar harian siswa terpilih.
                </p>
              </div>

              <!-- Nomor Identitas (NIP / NIS) -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Nomor Identitas ({{ (newUser.role === 'SISWA' || newUser.role === 'SEKRETARIS') ? 'NIS' : 'NIP' }})
                </label>
                <input
                  v-model="newUser.nip_nis"
                  type="text"
                  :inputmode="(newUser.role === 'SISWA' || newUser.role === 'SEKRETARIS') ? 'numeric' : 'text'"
                  @input="handleNisInput(newUser)"
                  placeholder=""
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono"
                />
              </div>

              <!-- Alokasi Kelas (Khusus Siswa & Sekretaris) -->
              <div v-if="newUser.role === 'SISWA' || newUser.role === 'SEKRETARIS'">
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Alokasi Kelas
                </label>
                <select
                  v-model="newUser.class_id"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer"
                >
                  <option v-for="c in classesList" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                  <option value="">- Tidak Terikat Kelas -</option>
                </select>
              </div>

              <!-- Alamat Email -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Alamat Email
                </label>
                <input
                  v-model="newUser.email"
                  type="email"
                  placeholder=""
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                  required
                />
              </div>

              <!-- Nomor Telepon -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Nomor Telepon
                </label>
                <input
                  v-model="newUser.phone"
                  type="tel"
                  placeholder=""
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                />
              </div>

              <!-- Action Buttons: Batal & Simpan Data -->
              <div class="pt-3 grid grid-cols-2 gap-3">
                <button
                  type="button"
                  @click="showAddDrawer = false"
                  class="w-full py-2.5 px-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer text-center"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="creating"
                  class="w-full py-2.5 px-4 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer text-center"
                >
                  {{ creating ? 'Menyimpan...' : 'Simpan Data' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>

      <!-- Modal Card Melayang: Edit Pengguna (Floating Pop-up) -->
      <Teleport to="body">
        <div
          v-if="showEditDrawer"
          class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
        >
          <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl max-w-md w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe border border-gray-150 dark:border-slate-800">
            <!-- Mobile drag handle -->
            <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
              <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Edit Pengguna</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Perbarui informasi profil dan penugasan peran</p>
              </div>
              <button
                type="button"
                @click="showEditDrawer = false"
                class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition cursor-pointer"
                title="Tutup"
              >
                <X class="w-5 h-5" />
              </button>
            </div>

            <form @submit.prevent="submitUpdateUser" class="space-y-3.5 text-xs">
              <!-- Edit Foto Profil / Avatar -->
              <div class="flex items-center gap-3.5 p-3 bg-gray-50/70 dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800">
                <div class="relative w-14 h-14 rounded-full overflow-hidden bg-white dark:bg-slate-800 border-2 border-dashed border-gray-300 dark:border-slate-700 flex items-center justify-center shrink-0 shadow-2xs">
                  <img v-if="editUserAvatarPreview || editUser.avatar_url || editUser.avatar" :src="editUserAvatarPreview || editUser.avatar_url || editUser.avatar" class="w-full h-full object-cover" />
                  <Camera v-else class="w-6 h-6 text-gray-400 dark:text-slate-500" />
                </div>
                <div class="flex-1 min-w-0">
                  <label class="block text-xs font-bold text-gray-800 dark:text-slate-200 mb-1">Foto Profil</label>
                  <div class="flex items-center gap-2">
                    <label class="px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 border border-gray-200 dark:border-slate-700 rounded-xl text-xs font-semibold cursor-pointer transition shadow-2xs">
                      <span>{{ (editUserAvatarPreview || editUser.avatar_url || editUser.avatar) ? 'Ganti Foto' : 'Pilih Foto Profil' }}</span>
                      <input
                        type="file"
                        accept="image/png,image/jpeg,image/jpg,image/webp"
                        @change="handleEditUserAvatarChange"
                        class="hidden"
                      />
                    </label>
                    <button
                      v-if="editUserAvatarPreview || editUser.avatar_url || editUser.avatar"
                      type="button"
                      @click="clearEditUserAvatar"
                      class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-medium cursor-pointer"
                    >
                      Hapus
                    </button>
                  </div>
                  <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">Perbarui foto profil pengguna ini.</p>
                </div>
              </div>

              <!-- Nama Lengkap -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Nama Lengkap
                </label>
                <input
                  v-model="editUser.name"
                  type="text"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                  required
                />
              </div>

              <!-- Peran Sistem -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Peran Sistem
                </label>
                <select
                  v-model="editUser.role"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer"
                  required
                >
                  <option v-if="editUser.role === 'SUPERADMIN'" value="SUPERADMIN">Super Administrator</option>
                  <option value="GURU_PENGAJAR">Guru</option>
                  <option value="SEKRETARIS">Sekretaris</option>
                  <option value="SISWA">Siswa</option>
                  <option value="ORANG_TUA">Orang Tua</option>
                </select>
              </div>

              <!-- Penugasan Tambahan: Wali Kelas (Jika Guru) -->
              <div v-if="editUser.role === 'GURU_PENGAJAR' || editUser.role === 'GURU_PEMBIMBING'" class="p-3.5 bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-2xl space-y-2">
                <label class="block text-xs font-bold text-amber-900 dark:text-amber-300 flex items-center gap-1.5">
                  <GraduationCap class="w-4 h-4 text-amber-700 dark:text-amber-400" />
                  <span>Penugasan Tambahan: Wali Kelas</span>
                </label>
                <select
                  v-model="editUser.advisor_class_id"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-amber-300 dark:border-amber-700 text-xs text-gray-800 dark:text-slate-100 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 cursor-pointer"
                >
                  <option value="">-- Bukan Wali Kelas --</option>
                  <option v-for="c in classesList" :key="c.id" :value="String(c.id)">
                    Wali Kelas: {{ c.name }}
                  </option>
                </select>
                <p class="text-[11px] text-amber-800 dark:text-amber-400 leading-relaxed">
                  Guru ini berstatus sebagai Guru dan mendapat wewenang tambahan sebagai Wali Kelas untuk kelas binaan terpilih.
                </p>
              </div>

              <!-- Hubungkan dengan Siswa (Jika Peran Orang Tua) -->
              <div v-if="editUser.role === 'ORANG_TUA'" class="p-3.5 bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/50 rounded-2xl space-y-2">
                <label class="block text-xs font-bold text-primary dark:text-blue-300 flex items-center gap-1.5">
                  <GraduationCap class="w-4 h-4 text-primary dark:text-blue-300" />
                  <span>Pilih Siswa</span>
                </label>
                <select
                  v-model="editUser.student_id"
                  @change="onStudentSelectForEditUser"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-blue-200 dark:border-blue-700 text-xs text-gray-800 dark:text-slate-100 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer"
                  required
                >
                  <option value="">-- Pilih Siswa yang Terhubung --</option>
                  <option v-for="s in studentsList" :key="s.id" :value="s.id">
                    {{ s.name }} (NIS: {{ s.nip_nis || '-' }}) - {{ s.school_class?.name || (s.class_id ? 'Kelas ' + s.class_id : 'Tanpa Kelas') }}
                  </option>
                </select>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                  Menghubungkan akun orang tua ini dengan siswa terpilih untuk melihat data riwayat belajar dan kehadiran anaknya.
                </p>
              </div>

              <!-- Nomor Identitas (NIP / NIS) -->
              <div v-if="editUser.role !== 'ORANG_TUA'">
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Nomor Identitas ({{ (editUser.role === 'SISWA' || editUser.role === 'SEKRETARIS') ? 'NIS' : 'NIP' }})
                </label>
                <input
                  v-model="editUser.nip_nis"
                  type="text"
                  :inputmode="(editUser.role === 'SISWA' || editUser.role === 'SEKRETARIS') ? 'numeric' : 'text'"
                  @input="handleNisInput(editUser)"
                  placeholder=""
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono"
                />
              </div>

              <!-- Alokasi Kelas (Khusus Siswa & Sekretaris) -->
              <div v-if="editUser.role === 'SISWA' || editUser.role === 'SEKRETARIS'">
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Alokasi Kelas
                </label>
                <select
                  v-model="editUser.class_id"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer"
                >
                  <option v-for="c in classesList" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                  <option value="">- Tidak Terikat Kelas -</option>
                </select>
              </div>

              <!-- Alamat Email -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Alamat Email
                </label>
                <input
                  v-model="editUser.email"
                  type="email"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                  required
                />
              </div>

              <!-- Nomor Telepon -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">
                  Nomor Telepon
                </label>
                <input
                  v-model="editUser.phone"
                  type="tel"
                  placeholder=""
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-800 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                />
              </div>

              <!-- Action Buttons: Batal & Simpan Perubahan -->
              <div class="pt-3 grid grid-cols-2 gap-3">
                <button
                  type="button"
                  @click="showEditDrawer = false"
                  class="w-full py-2.5 px-4 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer text-center"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="updating"
                  class="w-full py-2.5 px-4 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer text-center"
                >
                  {{ updating ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useToastStore } from '~/stores/toast'
import {
  Search,
  Plus,
  XCircle,
  CheckCircle2,
  UploadCloud,
  KeyRound,
  LogIn,
  Power,
  X,
  Pencil,
  Trash2,
  GraduationCap,
  Camera,
} from 'lucide-vue-next'

const config = useRuntimeConfig()
const authStore = useAuthStore()
const toast = useToastStore()
const route = useRoute()

const loadingUsers = ref(false)
const showAddDrawer = ref(false)
const showEditDrawer = ref(false)
const creating = ref(false)
const updating = ref(false)
const successMsg = ref('')

const searchQuery = ref('')
const roleFilter = ref((route.query.category === 'GURU' ? 'GURU_PENGAJAR' : (route.query.role as string)) || 'Semua Peran')

const studentsList = ref<any[]>([])
const classesList = ref<any[]>([])

const users = ref<any[]>([])

const newUser = ref({
  name: '',
  role: 'GURU_PEMBIMBING',
  nip_nis: '',
  class_id: '1',
  student_id: '',
  email: '',
  phone: '',
  is_also_walas: false,
  advisor_class_id: '',
})

const editUser = ref({
  id: null as number | null,
  name: '',
  role: 'SISWA',
  nip_nis: '',
  class_id: '',
  student_id: '',
  email: '',
  phone: ''
})

const newUserAvatarPreview = ref('')
const newUserAvatarBase64 = ref('')

const handleNewUserAvatarChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Ukuran foto profil maksimal 5MB!')
      return
    }
    const reader = new FileReader()
    reader.onload = (e) => {
      const res = (e.target?.result as string) || ''
      newUserAvatarPreview.value = res
      newUserAvatarBase64.value = res
    }
    reader.readAsDataURL(file)
  }
}

const clearNewUserAvatar = () => {
  newUserAvatarPreview.value = ''
  newUserAvatarBase64.value = ''
}

const editUserAvatarPreview = ref('')
const editUserAvatarBase64 = ref('')
const editUserRemoveAvatar = ref(false)

const handleEditUserAvatarChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Ukuran foto profil maksimal 5MB!')
      return
    }
    const reader = new FileReader()
    reader.onload = (e) => {
      const res = (e.target?.result as string) || ''
      editUserAvatarPreview.value = res
      editUserAvatarBase64.value = res
      editUserRemoveAvatar.value = false
    }
    reader.readAsDataURL(file)
  }
}

const clearEditUserAvatar = () => {
  editUserAvatarPreview.value = ''
  editUserAvatarBase64.value = ''
  editUserRemoveAvatar.value = true
  if (editUser.value) {
    (editUser.value as any).avatar = null
    (editUser.value as any).avatar_url = null
  }
}

const formatRoleLabel = (role: string) => {
  switch (role) {
    case 'SUPERADMIN': return 'Super Administrator'
    case 'GURU_PENGAJAR': return 'Guru'
    case 'GURU_PEMBIMBING': return 'Wali Kelas'
    case 'ORANG_TUA': return 'Orang Tua'
    case 'SEKRETARIS': return 'Sekretaris'
    case 'SISWA': return 'Siswa'
    default: return role
  }
}

const filteredUsers = computed(() => {
  return users.value.filter(u => {
    const isTeacher = ['GURU_PENGAJAR', 'GURU_PEMBIMBING'].includes(u.role)
    const isWalas = isTeacher && (u.role === 'GURU_PEMBIMBING' || !!u.guided_class || !!u.guidedClass || !!u.is_homeroom_teacher)

    let matchRole = false
    if (roleFilter.value === 'Semua Peran') {
      matchRole = true
    } else if (roleFilter.value === 'GURU') {
      matchRole = isTeacher
    } else if (roleFilter.value === 'GURU_PEMBIMBING') {
      matchRole = isWalas
    } else if (roleFilter.value === 'GURU_PENGAJAR') {
      matchRole = isTeacher
    } else {
      matchRole = u.role === roleFilter.value
    }
    const q = searchQuery.value.toLowerCase()
    const matchSearch = !q ||
      u.name.toLowerCase().includes(q) ||
      (u.nip_nis && u.nip_nis.includes(q)) ||
      (u.email && u.email.toLowerCase().includes(q)) ||
      (u.student?.name && u.student.name.toLowerCase().includes(q)) ||
      (u.specialization && u.specialization.toLowerCase().includes(q))
    return matchRole && matchSearch
  })
})

const fetchStudents = async () => {
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users?category=SISWA&per_page=200`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const rawData = res.data?.data || res.data || []
    if (Array.isArray(rawData)) {
      studentsList.value = rawData
    }
  } catch (err: any) {
    console.error('Failed to fetch students', err)
  }
}

const fetchClasses = async () => {
  try {
    const res: any = await $fetch(`${config.public.apiBase}/classes`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    if (Array.isArray(res)) {
      classesList.value = res
    }
  } catch (err: any) {
    console.error('Failed to fetch classes', err)
  }
}

function onStudentSelectForNewUser() {
  if (!newUser.value.student_id) return
  const found = studentsList.value.find((s: any) => String(s.id) === String(newUser.value.student_id))
  if (found) {
    if (found.class_id) newUser.value.class_id = String(found.class_id)
    if (found.nip_nis) newUser.value.nip_nis = found.nip_nis
    if (!newUser.value.name || newUser.value.name.startsWith('Orang Tua')) {
      newUser.value.name = `Orang Tua: ${found.name}`
    }
  }
}

function handleNisInput(target: any) {
  if (target.role === 'SISWA' || target.role === 'SEKRETARIS') {
    target.nip_nis = (target.nip_nis || '').replace(/\D/g, '')
  }
}

function onStudentSelectForEditUser() {
  if (!editUser.value.student_id) {
    editUser.value.nip_nis = ''
    return
  }
  const found = studentsList.value.find((s: any) => String(s.id) === String(editUser.value.student_id))
  if (found) {
    if (found.class_id) editUser.value.class_id = String(found.class_id)
    if (found.nip_nis) editUser.value.nip_nis = found.nip_nis
  }
}

function openEditModal(u: any) {
  editUserAvatarPreview.value = ''
  editUserAvatarBase64.value = ''
  editUserRemoveAvatar.value = false
  const advClassId = u.guided_class?.id || u.guidedClass?.id
  editUser.value = {
    id: u.id,
    name: u.name,
    role: u.role,
    nip_nis: u.nip_nis || '',
    class_id: u.class_id ? String(u.class_id) : '',
    student_id: u.student_id ? String(u.student_id) : (u.student?.id ? String(u.student.id) : ''),
    advisor_class_id: advClassId ? String(advClassId) : (u.role === 'GURU_PEMBIMBING' && u.class_id ? String(u.class_id) : ''),
    email: u.email,
    phone: u.phone || '',
    avatar: u.avatar || null,
    avatar_url: u.avatar_url || null,
  } as any
  showEditDrawer.value = true
}

const fetchUsers = async () => {
  loadingUsers.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users?per_page=100`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const rawData = res.data?.data || res.data || []
    if (Array.isArray(rawData) && rawData.length > 0) {
      let mapped = rawData.map((u: any) => ({
        ...u,
        class_name: u.school_class?.name || (u.class_id ? `Kelas ${u.class_id}` : '-')
      }))

      if (authStore.user && !mapped.some((u: any) => u.id === authStore.user?.id || u.email === authStore.user?.email)) {
        mapped.unshift({
          id: authStore.user.id,
          name: authStore.user.name,
          nip_nis: authStore.user.nip_nis || '-',
          role: authStore.user.role,
          class_name: authStore.user.school_class?.name || '-'
        })
      }

      users.value = mapped
    } else {
      users.value = []
    }
  } catch (err: any) {
    console.error('Error fetching users:', err)
    users.value = []
  } finally {
    loadingUsers.value = false
  }
}

const submitCreateUser = async () => {
  creating.value = true
  successMsg.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`
      },
      body: {
        name: newUser.value.name,
        role: newUser.value.role,
        email: newUser.value.email,
        phone: newUser.value.phone,
        nip_nis: newUser.value.nip_nis,
        class_id: newUser.value.class_id ? Number(newUser.value.class_id) : null,
        student_id: (newUser.value.role === 'ORANG_TUA' && newUser.value.student_id) ? Number(newUser.value.student_id) : null,
        advisor_class_id: (newUser.value.role === 'GURU_PENGAJAR' && newUser.value.is_also_walas && newUser.value.advisor_class_id)
          ? Number(newUser.value.advisor_class_id)
          : null,
        avatar_base64: newUserAvatarBase64.value || null,
      }
    })

    toast.success(res?.message || `Pengguna baru "${newUser.value.name}" berhasil didaftarkan!`)
    successMsg.value = `Pengguna baru "${newUser.value.name}" berhasil didaftarkan!`
    showAddDrawer.value = false
    newUserAvatarPreview.value = ''
    newUserAvatarBase64.value = ''
    newUser.value = {
      name: '',
      role: 'ORANG_TUA',
      nip_nis: '',
      class_id: '1',
      student_id: '',
      email: '',
      phone: ''
    }
    await fetchUsers()
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal mendaftarkan pengguna baru.')
  } finally {
    creating.value = false
  }
}

const submitUpdateUser = async () => {
  if (!editUser.value.id) return
  updating.value = true
  try {
    const isTeacher = ['GURU_PENGAJAR', 'GURU_PEMBIMBING'].includes(editUser.value.role)
    const res: any = await $fetch(`${config.public.apiBase}/users/${editUser.value.id}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${authStore.token}`
      },
      body: {
        name: editUser.value.name,
        role: editUser.value.role,
        email: editUser.value.email,
        phone: editUser.value.phone,
        nip_nis: editUser.value.nip_nis,
        class_id: editUser.value.class_id ? Number(editUser.value.class_id) : null,
        student_id: (editUser.value.role === 'ORANG_TUA' && editUser.value.student_id) ? Number(editUser.value.student_id) : null,
        advisor_class_id: isTeacher
          ? (editUser.value.advisor_class_id ? Number(editUser.value.advisor_class_id) : null)
          : null,
        avatar_base64: editUserAvatarBase64.value || null,
        remove_avatar: editUserRemoveAvatar.value || false,
      }
    })

    toast.success(res?.message || 'Data pengguna berhasil diperbarui.')
    successMsg.value = `Data pengguna "${editUser.value.name}" berhasil diperbarui!`
    showEditDrawer.value = false
    editUserAvatarPreview.value = ''
    editUserAvatarBase64.value = ''
    editUserRemoveAvatar.value = false
    if (editUser.value.id === authStore.user?.id) {
      await authStore.fetchUser()
    }
    await fetchUsers()
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal memperbarui data pengguna.')
  } finally {
    updating.value = false
  }
}

async function handleDeleteUser(user: any) {
  const ok = await confirmDialog({
    title: 'Hapus Akun Pengguna?',
    message: `Apakah Anda yakin ingin menghapus akun ${user.name} (${formatRoleLabel(user.role)})? Tindakan ini tidak dapat dibatalkan.`,
    confirmText: 'Ya, Hapus Akun',
    cancelText: 'Batal',
    type: 'danger',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users/${user.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    toast.success(res?.message || 'Pengguna berhasil dihapus.')
    successMsg.value = `Pengguna "${user.name}" berhasil dihapus.`
    await fetchUsers()
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal menghapus pengguna.')
  }
}

async function handleImpersonate(user: any) {
  const ok = await confirmDialog({
    title: 'Masuk Sebagai Pengguna?',
    message: `Apakah Anda yakin ingin masuk sebagai ${user.name} (${formatRoleLabel(user.role)})? Anda akan beralih peran ke akun tersebut.`,
    confirmText: 'Ya, Masuk Sebagai Pengguna',
    cancelText: 'Batal',
    type: 'primary',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users/${user.id}/impersonate`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    if (res.token && res.user) {
      authStore.token = res.token
      authStore.user = res.user
      localStorage.setItem('token', res.token)
      localStorage.setItem('user', JSON.stringify(res.user))
      toast.success(`Berhasil masuk sebagai ${user.name}! Mengalihkan ke dashboard...`)
      navigateTo('/dashboard')
    }
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal melakukan impersonasi.')
  }
}

async function handleResetPassword(user: any) {
  const ok = await confirmDialog({
    title: 'Reset Kata Sandi?',
    message: `Reset kata sandi pengguna ${user.name} ke kata sandi default ("password123")?`,
    confirmText: 'Ya, Reset Sandi',
    cancelText: 'Batal',
    type: 'warning',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users/${user.id}/reset-password`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    toast.success(res.message || 'Kata sandi berhasil direset.')
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal mereset kata sandi.')
  }
}

async function handleToggleActive(user: any) {
  const action = user.is_active !== false ? 'menonaktifkan' : 'mengaktifkan'
  const ok = await confirmDialog({
    title: `${user.is_active !== false ? 'Nonaktifkan' : 'Aktifkan'} Pengguna?`,
    message: `Apakah Anda yakin ingin ${action} akun ${user.name}?`,
    confirmText: `Ya, ${user.is_active !== false ? 'Nonaktifkan' : 'Aktifkan'}`,
    cancelText: 'Batal',
    type: user.is_active !== false ? 'danger' : 'primary',
  })
  if (!ok) return
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users/${user.id}/toggle-active`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    user.is_active = res.is_active
    successMsg.value = res.message || `Status akun ${user.name} berhasil diperbarui.`
  } catch (e: any) {
    toast.error(e?.data?.message || 'Gagal mengubah status akun.')
  }
}

onMounted(() => {
  if (authStore.user?.role === 'GURU_PEMBIMBING') {
    return navigateTo('/manajemen-murid')
  }
  fetchUsers()
  fetchStudents()
  fetchClasses()
})
</script>
