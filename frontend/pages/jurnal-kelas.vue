<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Buku Agenda KBM Kelas"
      :subtitle="'Pencatatan realisasi kegiatan pembelajaran tatap muka per jam pelajaran • ' + currentClassName"
    >
      <template #actions>
        <button
          v-if="canCreateAgenda"
          @click="openAddModal"
          type="button"
          class="px-2.5 sm:px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white transition-colors text-xs font-bold flex items-center gap-1.5 sm:gap-2 shadow-xs cursor-pointer whitespace-nowrap active:scale-[0.99] shrink-0 min-h-[34px] sm:min-h-[40px]"
          title="Isi Agenda KBM"
        >
          <Plus class="w-4 h-4 shrink-0" />
          <span class="hidden sm:inline">Isi Agenda KBM</span>
          <span class="sm:hidden text-[11px] font-bold">Agenda</span>
        </button>
      </template>
    </AppHeader>

    <main class="px-4 sm:px-8 pt-4 sm:pt-6 md:pt-6 pb-12 space-y-7 max-w-7xl mx-auto w-full">
      <!-- Success / Info Toast -->
      <div v-if="successMsg" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-semibold">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
          <span>{{ successMsg }}</span>
        </div>
        <button @click="successMsg = ''" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 text-xs font-bold cursor-pointer">
          <X class="w-3.5 h-3.5" />
        </button>
      </div>

      <!-- Filter Bar: Tanggal, Kelas Dinamis, Status, & Search -->
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
          <!-- Filter Tanggal -->
          <div class="flex items-center gap-2 px-3.5 h-11 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 text-xs sm:text-sm font-medium text-gray-700 dark:text-slate-300 shadow-2xs hover:border-primary/40 focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary transition">
            <Calendar class="w-4 h-4 text-primary shrink-0" />
            <input
              v-model="filterDate"
              type="date"
              lang="id-ID"
              @change="fetchAgendaJournals"
              class="border-none bg-transparent text-xs sm:text-sm font-semibold text-gray-800 dark:text-slate-200 focus:outline-none cursor-pointer"
            />
          </div>

          <!-- Filter Kelas: Terkunci untuk Siswa / Sekretaris, Pilihan Dinamis dari Database untuk Guru/Admin -->
          <div class="relative">
            <div
              v-if="isClassLocked"
              class="px-4 h-11 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/60 text-xs sm:text-sm font-bold text-primary dark:text-blue-300 shadow-2xs flex items-center gap-1.5 whitespace-nowrap"
            >
              <span>Kelas {{ currentClassName }}</span>
            </div>
            <select
              v-else
              v-model="selectedClassId"
              @change="onClassChange"
              class="px-4 h-11 pr-8 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-xs sm:text-sm font-semibold text-gray-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs cursor-pointer whitespace-nowrap"
            >
              <option v-for="cls in availableClasses" :key="cls.id" :value="String(cls.id)">
                Kelas: {{ cls.name }}
              </option>
            </select>
          </div>

          <!-- Filter Status Verifikasi Guru: Segmented Pills (Modern, no native grey mobile popup) -->
          <div class="inline-flex p-1 bg-slate-100/80 dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 rounded-xl gap-1 shadow-2xs">
            <button
              type="button"
              @click="filterStatus = 'ALL'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
              :class="filterStatus === 'ALL' ? 'bg-white dark:bg-slate-700 text-primary dark:text-white font-bold shadow-2xs border border-gray-200/60 dark:border-slate-600' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            >
              Semua
            </button>
            <button
              type="button"
              @click="filterStatus = 'TERVERIFIKASI'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
              :class="filterStatus === 'TERVERIFIKASI' ? 'bg-emerald-600 text-white font-extrabold shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            >
              Terverifikasi
            </button>
            <button
              type="button"
              @click="filterStatus = 'PENDING'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
              :class="filterStatus === 'PENDING' ? 'bg-amber-500 text-white font-extrabold shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            >
              Menunggu
            </button>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Search Box -->
          <div class="relative w-full sm:w-72">
            <Search class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari mapel, guru, materi..."
              class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 dark:border-slate-800 text-xs sm:text-sm text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-900 shadow-2xs"
            />
          </div>

          <!-- Ekspor CSV / Print -->
          <button
            @click="exportCsv"
            type="button"
            class="hidden sm:inline-flex items-center gap-1.5 h-11 px-5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-primary text-gray-700 dark:text-slate-300 hover:text-primary dark:hover:text-blue-400 text-xs sm:text-sm font-bold shadow-2xs transition cursor-pointer whitespace-nowrap"
          >
            <Download class="w-4 h-4" />
            <span>Ekspor Agenda</span>
          </button>
        </div>
      </div>

      <!-- 4 Stat Summary Cards (Buku Agenda Kelas) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/50">
            <BookOpen class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">Total Sesi KBM</span>
            <div class="text-base font-bold text-gray-900 dark:text-white flex items-baseline gap-1 mt-0.5">
              <span>{{ agendaList.length }}</span>
              <span class="text-[11px] font-normal text-gray-400 dark:text-slate-500">pertemuan</span>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/50">
            <CheckCircle2 class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">Terverifikasi Guru</span>
            <div class="text-base font-bold text-gray-900 dark:text-white flex items-baseline gap-1 mt-0.5">
              <span>{{ verifiedCount }}</span>
              <span class="text-[11px] font-normal text-emerald-600 dark:text-emerald-400">terkonfirmasi</span>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-900/50">
            <Clock class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">Menunggu Konfirmasi</span>
            <div class="text-base font-bold text-gray-900 dark:text-white flex items-baseline gap-1 mt-0.5">
              <span>{{ pendingCount }}</span>
              <span class="text-[11px] font-normal text-amber-600 dark:text-amber-400">sesi</span>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 p-4 shadow-2xs flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 border border-purple-100 dark:border-purple-900/50">
            <FileSpreadsheet class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">Jadwal Hari Ini</span>
            <div class="text-base font-bold text-gray-900 dark:text-white flex items-baseline gap-1 mt-0.5">
              <span>{{ schedules.length }}</span>
              <span class="text-[11px] font-normal text-gray-400 dark:text-slate-500">jam mapel</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Agenda Table -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Riwayat Agenda Pembelajaran</h3>
            <p class="text-xs text-gray-400 dark:text-slate-400 mt-0.5">Catatan pokok bahasan, jam tatap muka, dan konfirmasi paraf kehadiran guru pengajar di kelas</p>
          </div>
          <span class="text-xs text-gray-500 dark:text-slate-400 font-medium">
            Tanggal: <strong class="text-gray-900 dark:text-white">{{ formattedFilterDate }}</strong>
          </span>
        </div>

        <div v-if="loading" class="py-16 text-center text-gray-400 dark:text-slate-500 text-xs">
          Memuat data buku agenda KBM kelas...
        </div>

        <div v-else-if="filteredAgenda.length === 0" class="py-16 text-center space-y-3">
          <BookOpen class="w-10 h-10 text-gray-300 dark:text-slate-600 mx-auto" />
          <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-slate-300">Belum Ada Agenda KBM Dicatat</p>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Belum ada catatan realisasi jam pelajaran kelas pada tanggal ini.</p>
          </div>
          <button
            v-if="canCreateAgenda"
            type="button"
            @click="openAddModal"
            class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Isi Agenda KBM Sekarang</span>
          </button>
        </div>

        <div v-else>
          <!-- Mobile Card List View -->
          <div class="block md:hidden space-y-3.5 p-4">
            <div
              v-for="(agenda, idx) in filteredAgenda"
              :key="'mob-' + agenda.id"
              class="p-4 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-850 shadow-2xs space-y-3"
            >
              <div class="flex items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                  <span class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-primary dark:text-blue-300 font-bold text-xs flex items-center justify-center shrink-0">
                    {{ String(idx + 1).padStart(2, '0') }}
                  </span>
                  <div class="font-bold text-gray-900 dark:text-white text-xs">{{ agenda.time_slot }}</div>
                  <span class="text-[10px] text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-750 px-2 py-0.5 rounded-md font-medium">
                    {{ agenda.room || 'Kelas' }}
                  </span>
                </div>

                <span
                  v-if="agenda.status === 'TERVERIFIKASI'"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 shrink-0"
                >
                  <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                  <span>Terverifikasi</span>
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50 shrink-0"
                >
                  <Clock class="w-3 h-3 text-amber-600 dark:text-amber-400" />
                  <span>Menunggu</span>
                </span>
              </div>

              <div>
                <h4 class="font-bold text-primary dark:text-blue-400 text-sm">{{ agenda.subject }}</h4>
                <p class="text-xs text-gray-600 dark:text-slate-400 mt-0.5 flex items-center gap-1">
                  <User class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500 shrink-0" />
                  <span>{{ agenda.teacher }}</span>
                </p>
              </div>

              <div class="bg-gray-50/70 dark:bg-slate-800/80 p-3 rounded-xl border border-gray-100 dark:border-slate-750 space-y-1 text-xs">
                <p class="font-semibold text-gray-800 dark:text-slate-200"><span class="text-gray-400 dark:text-slate-500 font-normal">Pokok Bahasan:</span> {{ agenda.topic }}</p>
                <p class="text-gray-600 dark:text-slate-300 leading-relaxed"><span class="text-gray-400 dark:text-slate-500 font-normal">Aktivitas:</span> {{ agenda.activity }}</p>
                <p v-if="agenda.notes" class="text-gray-500 dark:text-slate-400 italic text-[11px] pt-1 border-t border-gray-200/50 dark:border-slate-700">Catatan: {{ agenda.notes }}</p>
              </div>

              <!-- Alert Siswa Tidak Berada di Jam Ini -->
              <div v-if="agenda.absent_students && agenda.absent_students.length > 0" class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-rose-700 dark:text-rose-400">
                  <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                  <span>{{ agenda.absent_students.length }} Siswa Tidak di Kelas Jam Ini:</span>
                </div>
                <div class="flex flex-wrap gap-1 pt-0.5">
                  <span v-for="s in agenda.absent_students" :key="s.id" class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-700 text-[11px] font-semibold text-rose-700 dark:text-rose-300 shadow-2xs">
                    {{ s.name }}
                  </span>
                </div>
              </div>

              <!-- Action buttons on mobile -->
              <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-slate-800">
                <button
                  v-if="canConfirmAgenda && agenda.status === 'PENDING'"
                  type="button"
                  @click="confirmAgenda(agenda)"
                  class="flex-1 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs active:scale-95 min-h-[44px]"
                >
                  <Check class="w-4 h-4" />
                  <span>Sahkan Jam Ini</span>
                </button>

                <button
                  v-if="canManageAgenda"
                  type="button"
                  @click="openEditModal(agenda)"
                  class="py-2 px-3.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-primary dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-xs font-semibold transition flex items-center justify-center gap-1 min-h-[44px]"
                  :class="{ 'flex-1': !canConfirmAgenda || agenda.status !== 'PENDING' }"
                >
                  <Pencil class="w-3.5 h-3.5" />
                  <span>Edit</span>
                </button>

                <button
                  v-if="canManageAgenda"
                  type="button"
                  @click="deleteAgenda(agenda)"
                  class="py-2 px-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 text-xs font-semibold transition flex items-center justify-center min-h-[44px] min-w-[44px]"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-150 dark:border-slate-800">
            <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[950px]">
            <thead>
              <tr class="bg-gray-50/70 dark:bg-slate-850 border-b border-gray-150 dark:border-slate-800 text-gray-500 dark:text-slate-400 uppercase text-[11px] tracking-wider font-bold">
                <th class="py-3.5 px-5 w-14 whitespace-nowrap">No</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Waktu & Ruang</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Mata Pelajaran & Guru</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Pokok Bahasan</th>
                <th class="py-3.5 px-5">Realisasi Aktivitas KBM</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Catatan Kelas</th>
                <th class="py-3.5 px-5 text-center whitespace-nowrap">Status Konfirmasi</th>
                <th class="py-3.5 px-5 text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
              <tr
                v-for="(agenda, idx) in filteredAgenda"
                :key="agenda.id"
                class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition"
              >
                <td class="py-4 px-5 font-medium text-gray-400 dark:text-slate-500 whitespace-nowrap">
                  {{ String(idx + 1).padStart(2, '0') }}
                </td>

                <td class="py-4 px-5 whitespace-nowrap">
                  <div class="font-bold text-gray-900 dark:text-slate-100">{{ agenda.time_slot }}</div>
                  <div class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 flex items-center gap-1">
                    <span>{{ agenda.room || 'Kelas' }}</span>
                  </div>
                </td>

                <td class="py-4 px-5 whitespace-nowrap">
                  <div class="font-bold text-primary dark:text-blue-400">{{ agenda.subject }}</div>
                  <div class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 flex items-center gap-1">
                    <User class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500 shrink-0" />
                    <span>{{ agenda.teacher }}</span>
                  </div>
                </td>

                <td class="py-4 px-5 font-semibold text-gray-800 dark:text-slate-200 whitespace-nowrap">
                  {{ agenda.topic }}
                </td>

                <td class="py-4 px-5 text-gray-600 dark:text-slate-300 max-w-sm">
                  <div class="line-clamp-2 leading-relaxed" :title="agenda.activity">
                    {{ agenda.activity }}
                  </div>
                </td>

                <td class="py-4 px-5 text-gray-500 dark:text-slate-400 max-w-xs">
                  <span class="truncate block text-xs" :title="agenda.notes || '-'">{{ agenda.notes || '-' }}</span>
                  <span
                    v-if="agenda.absent_students && agenda.absent_students.length > 0"
                    class="inline-flex items-center gap-1 mt-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60"
                    :title="'Tidak di kelas: ' + agenda.absent_students.map(s => s.name).join(', ')"
                  >
                    <AlertCircle class="w-3 h-3 text-rose-600 dark:text-rose-400 shrink-0" />
                    <span>{{ agenda.absent_students.length }} Siswa Tidak di Kelas</span>
                  </span>
                </td>

                <td class="py-4 px-5 text-center whitespace-nowrap">
                  <span
                    v-if="agenda.status === 'TERVERIFIKASI'"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50"
                    :title="agenda.confirmed_by ? ('Diverifikasi oleh ' + agenda.confirmed_by + ' pada ' + (agenda.confirmed_at || '')) : 'Terverifikasi'"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                    <span>Terverifikasi Guru</span>
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50"
                    title="Menunggu konfirmasi / paraf guru pengajar terkait"
                  >
                    <Clock class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                    <span>Menunggu Konfirmasi</span>
                  </span>
                </td>

                <td class="py-4 px-5 text-right whitespace-nowrap space-x-2">
                  <!-- Tombol Konfirmasi Guru / Walas jika status PENDING -->
                  <button
                    v-if="canConfirmAgenda && agenda.status === 'PENDING'"
                    type="button"
                    @click="confirmAgenda(agenda)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95"
                    title="Beri paraf dan konfirmasi realisasi jam pelajaran ini"
                  >
                    <Check class="w-3.5 h-3.5" />
                    <span>Sahkan</span>
                  </button>

                  <!-- Tombol Hapus (jika pembuat dan masih PENDING) -->
                  <button
                    v-if="agenda.created_by_id === authStore.user?.id && agenda.status === 'PENDING'"
                    type="button"
                    @click="deleteAgenda(agenda)"
                    class="inline-flex items-center gap-1.5 p-2 rounded-xl text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs font-bold transition cursor-pointer"
                    title="Hapus draf agenda KBM"
                  >
                    <Trash2 class="w-3 h-3" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      </div>

      <!-- Modal Input Agenda KBM Kelas -->
      <Teleport to="body">
        <div
          v-if="showAddModal"
          class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
        >
          <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[85vh] sm:max-h-[90vh] overflow-y-auto pb-8 sm:pb-7 pb-safe">
            <!-- Mobile drag handle -->
            <div class="sm:hidden w-10 h-1 bg-gray-300 dark:bg-slate-700 rounded-full mx-auto -mt-2 mb-3"></div>
            <div class="border-b border-gray-100 dark:border-slate-800 pb-3 flex items-start justify-between gap-3">
              <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ editingAgendaId ? 'Edit Buku Agenda KBM Kelas' : 'Pengisian Buku Agenda KBM Kelas' }}</h3>
                <p class="text-xs text-gray-400 dark:text-slate-400 mt-0.5">Input catatan realisasi pertemuan mata pelajaran dan materi pembelajaran</p>
              </div>
              <button 
                type="button" 
                @click="showAddModal = false" 
                class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer shrink-0"
                title="Tutup Modal"
              >
                <X class="w-5 h-5" />
              </button>
            </div>

          <form @submit.prevent="submitAgenda" class="space-y-4">
            <!-- Pilih Jadwal Pelajaran -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Jadwal / Jam Pelajaran & Guru
              </label>

              <!-- Input pencarian cepat di atas dropdown mapel -->
              <div class="mb-1.5">
                <input
                  v-model="searchScheduleQuery"
                  type="text"
                  placeholder="Cari mapel / guru pengajar..."
                  class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>

              <!-- Dropdown jadwal menggunakan filteredSchedules -->
              <select
                v-model="agendaForm.schedule_id"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
                required
              >
                <option value="">-- Pilih Jadwal & Jam Pelajaran --</option>
                <option v-for="sch in filteredSchedules" :key="sch.id" :value="sch.id">
                  {{ sch.start_time }} - {{ sch.end_time }} | {{ sch.subject?.name || sch.subject_name }} ({{ sch.teacher?.name || sch.teacher_name }}) - {{ sch.room || 'Kelas' }}
                </option>
              </select>
              <p v-if="filteredSchedules.length === 0" class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">
                Tidak ada jadwal KBM di hari {{ selectedDayName }}. Pastikan tanggal yang dipilih tepat.
              </p>
            </div>

            <!-- Tanggal Pembelajaran -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Tanggal Pembelajaran
              </label>
              <input
                v-model="agendaForm.date"
                type="date"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
                required
              />
            </div>

            <!-- Pokok Bahasan -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Pokok Bahasan Materi
              </label>
              <input
                v-model="agendaForm.topic"
                type="text"
                placeholder="Contoh: Implementasi State Management Pinia & Nuxt 3"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
                required
              />
            </div>

            <!-- Rangkuman Aktivitas & Realisasi KBM -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Rangkuman Aktivitas & Realisasi KBM di Kelas
              </label>
              <textarea
                v-model="agendaForm.activity"
                rows="3"
                placeholder="Rangkuman jalannya pembelajaran, penyampaian teori, praktikum laboratorium, dan tugas yang dikerjakan siswa..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
                required
              ></textarea>
            </div>

            <!-- Catatan Kelas -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Catatan Kelas dari Sekretaris
              </label>
              <input
                v-model="agendaForm.notes"
                type="text"
                placeholder="Contoh: Pembelajaran berjalan tertib di Lab Komputer 1, seluruh PC berfungsi"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-800 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
              />
            </div>

            <!-- Dokumentasi Foto KBM (1 - 2 Foto) -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5 text-xs">
                  <Camera class="w-3.5 h-3.5 text-primary" />
                  <span>Dokumentasi Foto Pembelajaran</span>
                </label>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">{{ kbmPhotos.length }} / 2 Foto</span>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div
                  v-for="(photo, pIdx) in kbmPhotos"
                  :key="pIdx"
                  class="relative h-24 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden group bg-gray-50 dark:bg-slate-800"
                >
                  <img :src="photo" class="w-full h-full object-cover" />
                  <button
                    type="button"
                    @click="removeKbmPhoto(pIdx)"
                    class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center opacity-90 hover:opacity-100 transition cursor-pointer shadow-xs"
                    title="Hapus Foto"
                  >
                    <X class="w-3.5 h-3.5" />
                  </button>
                </div>

                <label
                  v-if="kbmPhotos.length < 2"
                  class="h-24 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-700 hover:border-primary bg-gray-50/70 dark:bg-slate-800/60 hover:bg-blue-50/40 dark:hover:bg-slate-750 flex flex-col items-center justify-center gap-1 cursor-pointer transition select-none text-gray-500 dark:text-slate-400 hover:text-primary"
                >
                  <Camera class="w-4 h-4 text-gray-400 dark:text-slate-500 group-hover:text-primary" />
                  <span class="text-[10px] font-semibold">+ Tambah Foto</span>
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    @change="handleKbmPhotoUpload"
                    class="hidden"
                  />
                </label>
              </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-3 border-t border-gray-100 dark:border-slate-800">
              <button
                type="button"
                @click="showAddModal = false"
                class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs font-semibold text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition cursor-pointer whitespace-nowrap"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-xs transition disabled:opacity-50 cursor-pointer whitespace-nowrap"
              >
                {{ isSubmitting ? 'Menyimpan...' : (editingAgendaId ? 'Simpan Perubahan' : 'Kirim Agenda KBM') }}
              </button>
            </div>
          </form>
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
  BookOpen,
  CheckCircle2,
  Clock,
  Plus,
  X,
  User,
  Download,
  FileSpreadsheet,
  Check,
  Trash2,
  AlertCircle,
  Pencil,
  Camera,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useToastStore } from '~/stores/toast'
import { confirmDialog } from '~/utils/confirm'

const authStore = useAuthStore()
const toast = useToastStore()
const route = useRoute()

const filterDate = ref((route.query.date as string) || '')
const selectedClassId = ref(
  authStore.user?.role === 'GURU_PEMBIMBING' && authStore.user?.guided_class?.id
    ? String(authStore.user.guided_class.id)
    : (authStore.user?.class_id
        ? String(authStore.user.class_id)
        : (authStore.user?.guided_class?.id ? String(authStore.user.guided_class.id) : ''))
)
const filterStatus = ref('ALL')
const searchQuery = ref('')
const loading = ref(false)
const isSubmitting = ref(false)
const successMsg = ref('')
const showAddModal = ref(false)
const editingAgendaId = ref<number | null>(null)

const availableClasses = ref<any[]>([])
const schedules = ref<any[]>([])
const agendaList = ref<any[]>([])
const classStudents = ref<any[]>([])
const selectedAbsentStudents = ref<any[]>([])
const searchStudentQuery = ref('')

const filteredClassStudents = computed(() => {
  if (!searchStudentQuery.value) return classStudents.value
  const q = searchStudentQuery.value.toLowerCase()
  return classStudents.value.filter(s => s.name?.toLowerCase().includes(q) || s.nis?.includes(q))
})

const fetchClassStudents = async (classId: string | number) => {
  try {
    const res: any = await $fetch(`${authStore.apiBase}/classes/${classId}/students`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    classStudents.value = res || []
  } catch (e) {
    classStudents.value = []
  }
}

const toggleAbsentStudent = (student: any) => {
  const idx = selectedAbsentStudents.value.findIndex(s => s.id === student.id)
  if (idx >= 0) {
    selectedAbsentStudents.value.splice(idx, 1)
  } else {
    selectedAbsentStudents.value.push({
      id: student.id,
      name: student.name,
      nis: student.nis
    })
  }
}

const isStudentAbsent = (studentId: any) => {
  return selectedAbsentStudents.value.some(s => s.id === studentId)
}

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

const agendaForm = reactive({
  schedule_id: '' as string | number,
  date: getLocalTodayYMD(),
  topic: '',
  activity: '',
  notes: ''
})

const searchScheduleQuery = ref('')

const DAYS_MAP: Record<number, string> = {
  0: 'Minggu',
  1: 'Senin',
  2: 'Selasa',
  3: 'Rabu',
  4: 'Kamis',
  5: 'Jumat',
  6: 'Sabtu'
}

// Dapatkan nama hari dari agendaForm.date
const selectedDayName = computed(() => {
  if (!agendaForm.date) return ''
  const d = new Date(agendaForm.date + 'T00:00:00')
  return DAYS_MAP[d.getDay()] || ''
})

// Computed daftar jadwal yang sesuai hari dan query pencarian:
const filteredSchedules = computed(() => {
  let list = schedules.value || []
  if (selectedDayName.value) {
    list = list.filter((s: any) => s.day === selectedDayName.value)
  }
  // Filter pencarian nama mapel atau guru
  if (searchScheduleQuery.value.trim()) {
    const q = searchScheduleQuery.value.toLowerCase()
    list = list.filter((s: any) => {
      const subjectName = (s.subject?.name || s.subject_name || '').toLowerCase()
      const teacherName = (s.teacher?.name || s.teacher_name || '').toLowerCase()
      const room = (s.room || '').toLowerCase()
      return subjectName.includes(q) || teacherName.includes(q) || room.includes(q)
    })
  }
  return list
})

watch(filteredSchedules, (newVal) => {
  if (newVal.length > 0) {
    const exists = newVal.some((s: any) => s.id === agendaForm.schedule_id)
    if (!exists) {
      agendaForm.schedule_id = newVal[0].id
    }
  } else {
    agendaForm.schedule_id = ''
  }
})

const isClassLocked = computed(() => {
  if (['SEKRETARIS', 'SISWA'].includes(authStore.user?.role || '')) return true
  if (authStore.user?.role === 'GURU_PEMBIMBING' && (authStore.user?.guided_class?.id || authStore.user?.class_id)) return true
  return false
})

const currentClassName = computed(() => {
  if (authStore.user?.role === 'GURU_PEMBIMBING') {
    return authStore.user?.guided_class?.name || authStore.user?.school_class?.name || 'Kelas Binaan'
  }
  if (['SEKRETARIS', 'SISWA'].includes(authStore.user?.role || '')) {
    return authStore.user?.school_class?.name || authStore.user?.guided_class?.name || authStore.user?.class_name || 'Kelas Anda'
  }
  const found = availableClasses.value.find(c => String(c.id) === String(selectedClassId.value))
  return found ? found.name : (authStore.user?.guided_class?.name || authStore.user?.school_class?.name || 'Pilih Kelas')
})

const canCreateAgenda = computed(() => {
  return ['SEKRETARIS', 'GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'].includes(authStore.user?.role || '')
})

const canConfirmAgenda = computed(() => {
  return ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'].includes(authStore.user?.role || '')
})

const formattedFilterDate = computed(() => {
  if (!filterDate.value) return 'Hari Ini'
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(new Date(filterDate.value))
})

const verifiedCount = computed(() => {
  return agendaList.value.filter(a => a.status === 'TERVERIFIKASI').length
})

const pendingCount = computed(() => {
  return agendaList.value.filter(a => a.status !== 'TERVERIFIKASI').length
})

const filteredAgenda = computed(() => {
  let list = agendaList.value

  if (filterStatus.value !== 'ALL') {
    list = list.filter(a => a.status === filterStatus.value)
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(a =>
      (a.subject && a.subject.toLowerCase().includes(q)) ||
      (a.teacher && a.teacher.toLowerCase().includes(q)) ||
      (a.topic && a.topic.toLowerCase().includes(q)) ||
      (a.activity && a.activity.toLowerCase().includes(q)) ||
      (a.notes && a.notes.toLowerCase().includes(q))
    )
  }

  return list
})

function getLocalTodayYMD(): string {
  const d = new Date()
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const fetchClasses = async () => {
  try {
    const res: any = await $fetch(`${authStore.apiBase}/classes`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    availableClasses.value = res || []
  } catch (e) {
    console.warn('Gagal memuat daftar kelas:', e)
  }
}

const fetchSchedules = async () => {
  try {
    const res: any = await $fetch(`${authStore.apiBase}/schedules`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
      params: {
        class_id: selectedClassId.value
      }
    })
    schedules.value = res || []
    if (schedules.value.length > 0 && !agendaForm.schedule_id) {
      agendaForm.schedule_id = schedules.value[0].id
    }
  } catch (e) {
    console.warn('Gagal memuat jadwal pelajaran:', e)
  }
}

const fetchAgendaJournals = async () => {
  loading.value = true
  try {
    const params: any = {
      type: 'class_agenda',
      class_id: selectedClassId.value,
    }
    if (filterDate.value) {
      params.date = filterDate.value
    }
    const res: any = await $fetch.raw(`${authStore.apiBase}/journals/class`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
      params
    })
    const serverDate = res.headers.get('x-server-date') || res._data?.server_date
    if (!filterDate.value && serverDate) {
      filterDate.value = serverDate
    }
    agendaList.value = res._data?.data || []
  } catch (e) {
    console.warn('Gagal memuat agenda KBM kelas:', e)
    agendaList.value = []
  } finally {
    loading.value = false
  }
}

const onClassChange = () => {
  fetchSchedules()
  fetchAgendaJournals()
}

const openAddModal = async () => {
  editingAgendaId.value = null
  agendaForm.date = filterDate.value || getLocalTodayYMD()
  if (schedules.value.length > 0 && !agendaForm.schedule_id) {
    agendaForm.schedule_id = schedules.value[0].id
  }
  selectedAbsentStudents.value = []
  kbmPhotos.value = []
  searchStudentQuery.value = ''
  showAddModal.value = true
  await fetchClassStudents(selectedClassId.value)
}

const openEditModal = async (agenda: any) => {
  editingAgendaId.value = agenda.id
  agendaForm.date = agenda.date || filterDate.value || getLocalTodayYMD()
  agendaForm.schedule_id = agenda.schedule_id || (schedules.value[0]?.id || '')
  agendaForm.topic = agenda.topic || ''
  agendaForm.activity = agenda.activity || ''
  agendaForm.notes = agenda.notes || ''
  selectedAbsentStudents.value = Array.isArray(agenda.absent_students) ? [...agenda.absent_students] : []
  kbmPhotos.value = Array.isArray(agenda.photos) ? [...agenda.photos] : []
  searchStudentQuery.value = ''
  showAddModal.value = true
  await fetchClassStudents(selectedClassId.value)
}

const submitAgenda = async () => {
  if (!agendaForm.schedule_id || !agendaForm.topic || !agendaForm.activity) {
    toast.warning('Harap lengkapi semua kolom wajib pada agenda KBM.')
    return
  }
  isSubmitting.value = true
  try {
    const method = editingAgendaId.value ? 'PUT' : 'POST'
    const endpoint = editingAgendaId.value
      ? `${authStore.apiBase}/journals/class/${editingAgendaId.value}`
      : `${authStore.apiBase}/journals/class`

    const res: any = await $fetch(endpoint, {
      method,
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        type: 'class_agenda',
        schedule_id: agendaForm.schedule_id,
        class_id: selectedClassId.value,
        date: agendaForm.date,
        topic: agendaForm.topic,
        activity: agendaForm.activity,
        notes: agendaForm.notes,
        absent_students: selectedAbsentStudents.value,
        photos: kbmPhotos.value
      }
    })
    successMsg.value = res?.message || (editingAgendaId.value ? 'Agenda KBM kelas berhasil diperbarui!' : 'Agenda KBM kelas berhasil dicatat!')
    showAddModal.value = false
    editingAgendaId.value = null
    agendaForm.topic = ''
    agendaForm.activity = ''
    agendaForm.notes = ''
    selectedAbsentStudents.value = []
    kbmPhotos.value = []
    await fetchAgendaJournals()
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal menyimpan agenda KBM.')
  } finally {
    isSubmitting.value = false
  }
}

const confirmAgenda = async (agenda: any) => {
  const ok = await confirmDialog({
    title: 'Sahkan Agenda Pembelajaran',
    message: `Sahkan agenda pembelajaran kelas untuk mata pelajaran "${agenda.subject}" (${agenda.time_slot})?`,
    confirmText: 'Ya, Sahkan',
    type: 'primary'
  })
  if (!ok) return

  try {
    const res: any = await $fetch(`${authStore.apiBase}/journals/class/${agenda.id}/confirm`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {}
    })
    successMsg.value = res?.message || 'Agenda KBM berhasil disahkan & diverifikasi!'
    await fetchAgendaJournals()
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal mengonfirmasi agenda KBM.')
  }
}

const deleteAgenda = async (agenda: any) => {
  const ok = await confirmDialog({
    title: 'Hapus Draf Agenda KBM',
    message: `Apakah Anda yakin ingin menghapus agenda "${agenda.topic}"?`,
    confirmText: 'Ya, Hapus',
    type: 'danger'
  })
  if (!ok) return
  try {
    await $fetch(`${authStore.apiBase}/journals/class/${agenda.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    successMsg.value = 'Agenda KBM berhasil dihapus.'
    await fetchAgendaJournals()
  } catch (err: any) {
    toast.error(err?.data?.message || 'Gagal menghapus agenda.')
  }
}

const exportCsv = () => {
  if (agendaList.value.length === 0) {
    toast.warning('Belum ada agenda KBM pada tanggal ini untuk diekspor.')
    return
  }
  const headers = ['No', 'Waktu', 'Ruang', 'Mata Pelajaran', 'Guru', 'Pokok Bahasan', 'Aktivitas KBM', 'Catatan', 'Status Verifikasi']
  const rows = filteredAgenda.value.map((a, idx) => [
    idx + 1,
    `"${a.time_slot || '-'}"`,
    `"${a.room || '-'}"`,
    `"${a.subject || '-'}"`,
    `"${a.teacher || '-'}"`,
    `"${a.topic || '-'}"`,
    `"${(a.activity || '').replace(/"/g, '""')}"`,
    `"${(a.notes || '-').replace(/"/g, '""')}"`,
    `"${a.status}"`
  ])
  const csvContent = 'data:text/csv;charset=utf-8,\uFEFF'
    + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `Agenda_KBM_Kelas_${filterDate.value}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

onMounted(async () => {
  if (authStore.user?.role === 'GURU_PENGAJAR') {
    return navigateTo('/jurnal-materi?tab=monitoring')
  }
  await fetchClasses()
  await fetchSchedules()
  await fetchAgendaJournals()
})
</script>
