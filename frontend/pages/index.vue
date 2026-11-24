<template>
  <div class="min-h-screen flex flex-col font-sans antialiased bg-slate-50/40 dark:bg-slate-950 text-slate-800 dark:text-slate-100 selection:bg-blue-600 selection:text-white relative">
    <!-- Ambient Background Gradients -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-gradient-to-b from-blue-100/40 via-indigo-50/20 to-transparent dark:from-blue-950/20 dark:via-indigo-950/10 dark:to-transparent pointer-events-none -z-10 blur-3xl"></div>

    <!-- MAIN NAVBAR (Fixed di paling atas agar tidak hilang saat scroll) -->
    <header class="fixed top-0 left-0 right-0 z-50 h-16 sm:h-20 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 select-none shadow-xs transition-all">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">
        <!-- Logo & Title -->
        <NuxtLink to="/" class="flex items-center gap-3.5 group cursor-pointer">
          <img
            src="/images/logo-smkn71.png"
            alt="Logo SMKN 71"
            class="w-9 h-9 sm:w-11 sm:h-11 object-contain transition-transform group-hover:scale-105"
          />
          <div>
            <span class="font-extrabold text-sm sm:text-base tracking-tight block text-slate-900 dark:text-white group-hover:text-primary transition-colors">
              SMKN 71 JAKARTA
            </span>
            <p class="text-[11px] font-bold text-primary dark:text-blue-400">
              Jurnal Kelas Digital
            </p>
          </div>
        </NuxtLink>

        <!-- Navigation Links -->
        <nav class="hidden md:flex items-center gap-1 text-sm font-semibold">
          <button
            v-for="link in navLinks"
            :key="link.id"
            type="button"
            @click="scrollToSection(link.id)"
            class="px-3.5 py-1.5 rounded-xl transition-all cursor-pointer text-xs lg:text-sm"
            :class="[
              activeSection === link.id
                ? 'text-primary dark:text-blue-400 font-bold bg-blue-50/80 dark:bg-blue-950/50'
                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800'
            ]"
          >
            {{ link.name }}
          </button>
        </nav>

        <!-- Right Controls: Single Entry Point + Dark Mode Switch -->
        <div class="flex items-center gap-2 sm:gap-3">
          <button
            type="button"
            @click="uiStore.toggleDark"
            class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition cursor-pointer shadow-2xs"
            :title="uiStore.isDark ? 'Mode Terang' : 'Mode Gelap'"
          >
            <Sun v-if="uiStore.isDark" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4 text-slate-600 dark:text-slate-300" />
          </button>

          <NuxtLink
            v-if="authStore.isAuthenticated"
            to="/dashboard"
            class="px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95"
          >
            <span>Buka Dashboard</span>
            <ChevronRight class="w-4 h-4" />
          </NuxtLink>
          <NuxtLink
            v-else
            to="/login"
            class="px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95"
          >
            <span>Masuk Akun</span>
            <ChevronRight class="w-4 h-4" />
          </NuxtLink>

          <!-- Mobile Hamburger -->
          <button
            type="button"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
            class="md:hidden p-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
            aria-label="Toggle Menu"
          >
            <X v-if="isMobileMenuOpen" class="w-5 h-5" />
            <Menu v-else class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Mobile Dropdown -->
      <div
        v-if="isMobileMenuOpen"
        class="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-4 py-3 space-y-1 shadow-xl animate-fade-in"
      >
        <button
          v-for="link in navLinks"
          :key="link.id"
          type="button"
          @click="scrollToSection(link.id)"
          class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-colors cursor-pointer"
          :class="[
            activeSection === link.id
              ? 'text-primary dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 font-bold'
              : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'
          ]"
        >
          {{ link.name }}
        </button>
      </div>
    </header>

    <!-- HERO SECTION -->
    <section id="tentang" class="pt-24 sm:pt-32 lg:pt-36 pb-12 sm:pb-20 border-b border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 relative overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
          <!-- Left Column -->
          <div class="lg:col-span-6 space-y-6 text-center lg:text-left">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight text-slate-900 dark:text-white">
              Jurnal Kelas Digital <br class="hidden sm:inline" />
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-700 via-indigo-600 to-blue-600 dark:from-blue-400 dark:via-indigo-300 dark:to-blue-300">
                Terpadu & Paperless
              </span>
            </h1>

            <p class="text-sm sm:text-base leading-relaxed text-slate-600 dark:text-slate-300 max-w-xl mx-auto lg:mx-0">
              Pencatatan agenda materi harian guru, presensi siswa per jam pelajaran, dan arsip dokumen pembelajaran digital yang terpantau langsung oleh Wali Kelas dan Orang Tua.
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
              <button
                type="button"
                @click="scrollToSection('format-jurnal')"
                class="w-full sm:w-auto px-6 py-3 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-bold transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer active:scale-95"
              >
                <span>Lihat Format Jurnal</span>
                <ChevronRight class="w-4 h-4" />
              </button>

              <button
                type="button"
                @click="scrollToSection('alur')"
                class="w-full sm:w-auto px-6 py-3 rounded-xl border border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs sm:text-sm font-bold cursor-pointer transition-all shadow-2xs active:scale-95"
              >
                Alur Operasional
              </button>
            </div>

            <!-- Key Indicators (Compact & Sleek) -->
            <div class="pt-6 border-t border-slate-100 dark:border-slate-800 grid grid-cols-3 gap-3 text-left max-w-md mx-auto lg:mx-0">
              <div class="p-3 rounded-2xl bg-slate-50/70 dark:bg-slate-850 border border-slate-150 dark:border-slate-800 space-y-0.5">
                <span class="text-lg sm:text-xl font-extrabold block text-slate-900 dark:text-white">10 Sesi</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Jam Pelajaran</span>
              </div>
              <div class="p-3 rounded-2xl bg-slate-50/70 dark:bg-slate-850 border border-slate-150 dark:border-slate-800 space-y-0.5">
                <span class="text-lg sm:text-xl font-extrabold block text-slate-900 dark:text-white">3 Jurusan</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">RPL, DKV, Animasi</span>
              </div>
              <div class="p-3 rounded-2xl bg-slate-50/70 dark:bg-slate-850 border border-slate-150 dark:border-slate-800 space-y-0.5">
                <span class="text-lg sm:text-xl font-extrabold block text-emerald-600 dark:text-emerald-400">100%</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Arsip Digital</span>
              </div>
            </div>
          </div>

          <!-- Right Column: Interactive Mockup Sheet with Hover Animation -->
          <div class="lg:col-span-6 relative">
            <!-- Modern Tablet Card -->
            <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800 p-4 sm:p-6 shadow-md bg-white dark:bg-slate-900 text-left hover-lift transition-all">
              <!-- Journal Sheet Header -->
              <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                  <span class="text-[11px] font-bold uppercase tracking-wider text-primary dark:text-blue-400">Agenda KBM Harian</span>
                  <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Kelas XI RPL 1</h3>
                </div>
                <div class="text-xs font-mono font-bold px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                  Semester Ganjil 2026/2027
                </div>
              </div>

              <!-- Journal Rows (Hover-Aware Interactive Animation) -->
              <div class="py-4 space-y-3 text-xs sm:text-sm">
                <!-- Sesi 1: Selesai -->
                <div
                  @mouseenter="hoveredSessionId = 1"
                  class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer space-y-1.5"
                  :class="hoveredSessionId === 1 ? 'border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/30 shadow-xs -translate-y-0.5' : 'border-slate-150 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-850'"
                >
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-900 dark:text-white">07:30 - 09:45 • Jam 1 - 3</span>
                    <span class="text-emerald-700 dark:text-emerald-300 font-bold flex items-center gap-1 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800/50">
                      <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                      Terverifikasi
                    </span>
                  </div>
                  <div class="font-bold text-sm text-slate-900 dark:text-white">
                    Pemrograman Web — Nurhayatul Fadilla, S.Pd
                  </div>
                  <p class="text-xs text-slate-600 dark:text-slate-300">
                    Materi: Implementasi REST API Laravel dan arsitektur token JWT.
                  </p>
                  <div class="text-xs text-slate-500 dark:text-slate-400 pt-1 flex gap-3 font-semibold">
                    <span>Hadir: <strong class="text-slate-900 dark:text-white">36</strong></span>
                    <span>Sakit: <strong class="text-slate-900 dark:text-white">0</strong></span>
                    <span>Izin: <strong class="text-slate-900 dark:text-white">0</strong></span>
                    <span>Alfa: <strong class="text-slate-900 dark:text-white">0</strong></span>
                  </div>
                </div>

                <!-- Sesi 2: Sedang Berlangsung -->
                <div
                  @mouseenter="hoveredSessionId = 2"
                  class="p-4 rounded-2xl border-2 transition-all duration-200 cursor-pointer space-y-2"
                  :class="hoveredSessionId === 2 ? 'border-primary bg-blue-50/50 dark:bg-blue-950/40 shadow-xs -translate-y-0.5' : 'border-blue-200 dark:border-blue-900/50 bg-blue-50/20 dark:bg-blue-950/20'"
                >
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-primary dark:text-blue-400">10:00 - 12:15 • Jam 4 - 6</span>
                    <span class="text-primary dark:text-blue-300 font-bold bg-blue-100/80 dark:bg-blue-950/80 px-2.5 py-0.5 rounded-md">
                      Sedang Aktif
                    </span>
                  </div>
                  <div class="font-bold text-sm text-slate-900 dark:text-white">
                    Basis Data Relasional — Faradillah Aryani, S.T
                  </div>
                  <p class="text-xs text-slate-600 dark:text-slate-300">
                    Materi: Optimasi indexing query dan perancangan skema relasi foreign key.
                  </p>
                  <div class="text-xs text-slate-500 dark:text-slate-400 pt-0.5 flex gap-3 font-semibold">
                    <span>Hadir: <strong class="text-slate-900 dark:text-white">35</strong></span>
                    <span class="text-amber-700 dark:text-amber-400 font-bold">Izin: 1</span>
                    <span>Alfa: <strong class="text-slate-900 dark:text-white">0</strong></span>
                  </div>
                </div>

                <!-- Sesi 3: Mendatang -->
                <div
                  @mouseenter="hoveredSessionId = 3"
                  class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer space-y-1.5"
                  :class="hoveredSessionId === 3 ? 'border-primary/60 bg-blue-50/30 dark:bg-blue-950/30 shadow-xs -translate-y-0.5' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-850'"
                >
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-900 dark:text-white">13:00 - 15:15 • Jam 7 - 9</span>
                    <span class="text-slate-600 dark:text-slate-300 font-bold bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 rounded-md border border-slate-200 dark:border-slate-700">
                      Sesi Mendatang
                    </span>
                  </div>
                  <div class="font-bold text-sm text-slate-900 dark:text-white">
                    Kreativitas &amp; Inovasi Kejuruan — Drs. Budi Santoso
                  </div>
                  <p class="text-xs text-slate-600 dark:text-slate-300">
                    Materi: Prototyping antarmuka sistem dan pengujian kelayakan produk.
                  </p>
                  <div class="text-xs text-slate-500 dark:text-slate-400 pt-1 flex items-center justify-between font-semibold">
                    <span>Lab iMac R.409</span>
                    <span class="text-primary dark:text-blue-400 font-bold">36 Siswa Terdaftar</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FORMAT JURNAL SECTION -->
    <section id="format-jurnal" class="py-12 sm:py-20 border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto text-center space-y-2 mb-12">
          <p class="text-xs font-bold text-primary dark:text-blue-400 uppercase tracking-wider">
            Format Standar
          </p>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Komponen Jurnal Digital
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            Standar isian administrasi pembelajaran di SMKN 71 Jakarta.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
          <!-- Item 01 -->
          <div class="p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-3 shadow-xs hover-lift transition-all">
            <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-primary dark:text-blue-400 flex items-center justify-center font-extrabold text-sm border border-blue-100 dark:border-blue-900/40">
              <Clock class="w-5 h-5 text-primary dark:text-blue-400" />
            </div>
            <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Identitas Jam KBM</h4>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
              Tanggal, sesi jam pelajaran, ruangan, mata pelajaran, dan identitas guru pengampu.
            </p>
          </div>

          <!-- Item 02 -->
          <div class="p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-3 shadow-xs hover-lift transition-all">
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-extrabold text-sm border border-indigo-100 dark:border-indigo-900/40">
              <BookOpen class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
            </div>
            <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Materi &amp; Capaian</h4>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
              Pokok bahasan yang diajarkan, tugas praktikum, dan catatan progres belajar siswa.
            </p>
          </div>

          <!-- Item 03 -->
          <div class="p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-3 shadow-xs hover-lift transition-all">
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-extrabold text-sm border border-emerald-100 dark:border-emerald-900/40">
              <UserCheck class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Presensi per Jam</h4>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
              Pemantauan kehadiran per sesi untuk memastikan siswa tetap berada di kelas hingga tuntas.
            </p>
          </div>

          <!-- Item 04 -->
          <div class="p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-3 shadow-xs hover-lift transition-all">
            <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center font-extrabold text-sm border border-amber-100 dark:border-amber-905/40">
              <ShieldCheck class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Verifikasi &amp; Paraf</h4>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
              Pengesahan digital oleh Guru dan validasi surat izin oleh Wali Kelas.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ALUR OPERASIONAL -->
    <section id="alur" class="py-12 sm:py-20 border-b border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto text-center space-y-2 mb-12">
          <p class="text-xs font-bold text-primary dark:text-blue-400 uppercase tracking-wider">
            Alur Terintegrasi
          </p>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Kolaborasi 3 Peran Utama
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            Proses pengisian terstruktur dari kelas hingga pemantauan wali murid.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Langkah 1: Sekretaris Kelas -->
          <div class="p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-850 flex flex-col justify-between shadow-xs hover-lift transition-all space-y-4">
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold text-primary dark:text-blue-400 uppercase tracking-wider bg-blue-50 dark:bg-blue-950/60 px-2.5 py-1 rounded-lg">Langkah 1</span>
                <span class="text-xs font-mono text-slate-400 dark:text-slate-500">Petugas Rombel</span>
              </div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">Sekretaris Kelas</h3>
              <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                Mencatat daftar hadir siswa (hadir, sakit, izin, atau alfa) dan mengunci rekap harian kelas.
              </p>
            </div>
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
              <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                Presensi &amp; Agenda Harian
              </span>
            </div>
          </div>

          <!-- Langkah 2: Guru -->
          <div class="p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-850 flex flex-col justify-between shadow-xs hover-lift transition-all space-y-4">
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider bg-indigo-50 dark:bg-indigo-950/60 px-2.5 py-1 rounded-lg">Langkah 2</span>
                <span class="text-xs font-mono text-slate-400 dark:text-slate-500">Pendidik</span>
              </div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">Guru</h3>
              <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                Mengisi materi pembelajaran KBM, modul pelajaran, dan paraf kehadiran jam mengajar.
              </p>
            </div>
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
              <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                Jurnal Materi &amp; Evaluasi
              </span>
            </div>
          </div>

          <!-- Langkah 3: Wali Kelas -->
          <div class="p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-850 flex flex-col justify-between shadow-xs hover-lift transition-all space-y-4">
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 rounded-lg">Langkah 3</span>
                <span class="text-xs font-mono text-slate-400 dark:text-slate-500">Pembimbing</span>
              </div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">Wali Kelas</h3>
              <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                Memvalidasi permohonan surat izin, memantau rekap absensi rombel, dan koordinasi dengan orang tua.
              </p>
            </div>
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
              <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                Persetujuan &amp; Rekapitulasi
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- JURUSAN SMKN 71 JAKARTA -->
    <section id="jurusan" class="py-12 sm:py-20 border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto text-center space-y-2 mb-12">
          <p class="text-xs font-bold text-primary dark:text-blue-400 uppercase tracking-wider">
            Konsentrasi Keahlian
          </p>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            3 Program Keahlian
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            Penerapan Jurnal Kelas Digital mencakup seluruh konsentrasi di SMKN 71 Jakarta.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
          <!-- RPL -->
          <div class="p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-4 shadow-xs hover-lift transition-all group">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/50 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center p-2.5 group-hover:scale-105 transition-transform">
              <img
                src="/images/logo-rpl.png"
                alt="Logo RPL"
                class="w-full h-full object-contain"
              />
            </div>
            <div>
              <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Rekayasa Perangkat Lunak</h3>
              <p class="text-xs sm:text-sm mt-1.5 leading-relaxed text-slate-600 dark:text-slate-300">
                Pembelajaran pemrograman web, aplikasi mobile, basis data relasional, dan cloud computing.
              </p>
            </div>
          </div>

          <!-- Animasi -->
          <div class="p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-4 shadow-xs hover-lift transition-all group">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/40 flex items-center justify-center p-2.5 group-hover:scale-105 transition-transform">
              <img
                src="/images/logo-animasi.png"
                alt="Logo Animasi"
                class="w-full h-full object-contain"
              />
            </div>
            <div>
              <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Animasi</h3>
              <p class="text-xs sm:text-sm mt-1.5 leading-relaxed text-slate-600 dark:text-slate-300">
                Pembelajaran 3D character modeling, rigging, visual effects, storyboard, dan animasi 2D.
              </p>
            </div>
          </div>

          <!-- DKV -->
          <div class="p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-4 shadow-xs hover-lift transition-all group">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-100 dark:border-amber-900/40 flex items-center justify-center p-2.5 group-hover:scale-105 transition-transform">
              <img
                src="/images/logo-dkv.png"
                alt="Logo DKV"
                class="w-full h-full object-contain"
              />
            </div>
            <div>
              <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Desain Komunikasi Visual</h3>
              <p class="text-xs sm:text-sm mt-1.5 leading-relaxed text-slate-600 dark:text-slate-300">
                Pembelajaran perancangan visual, tipografi digital, fotografi studio, ilustrasi, dan video editing.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- KONTAK SEKOLAH -->
    <section id="kontak" class="py-10 sm:py-14 border-t border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs sm:text-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
          <!-- Identitas Sekolah -->
          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <img
                src="/images/logo-smkn71.png"
                alt="SMKN 71"
                class="w-10 h-10 sm:w-11 sm:h-11 object-contain"
              />
              <div>
                <h4 class="font-extrabold text-base text-slate-900 dark:text-white">SMKN 71 JAKARTA</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold">Jurnal Kelas Digital</p>
              </div>
            </div>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
              Sistem resmi pencatatan agenda pembelajaran harian dan presensi siswa SMKN 71 Jakarta.
            </p>
          </div>

          <!-- Alamat Lengkap -->
          <div class="space-y-2">
            <h5 class="font-bold uppercase text-xs tracking-wider text-slate-900 dark:text-white">
              Alamat Sekolah
            </h5>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
              Jl. Raden Inten II No. 1, Duren Sawit, Kec. Duren Sawit, Kota Jakarta Timur, DKI Jakarta 13440
            </p>
            <p class="text-xs text-slate-600 dark:text-slate-300 pt-1">
              Telepon: <span class="font-bold text-slate-900 dark:text-white">(021) 8660-8451</span> • Email: <span class="text-primary dark:text-blue-400 font-bold">info@smkn71jakarta.sch.id</span>
            </p>
          </div>

          <!-- Tautan Navigasi Halaman -->
          <div class="space-y-2">
            <h5 class="font-bold uppercase text-xs tracking-wider text-slate-900 dark:text-white">
              Navigasi Halaman
            </h5>
            <ul class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300 font-medium">
              <li>
                <button type="button" @click="scrollToSection('tentang')" class="hover:text-primary dark:hover:text-blue-400 transition-colors cursor-pointer">
                  › Tentang Jurnal Kelas
                </button>
              </li>
              <li>
                <button type="button" @click="scrollToSection('format-jurnal')" class="hover:text-primary dark:hover:text-blue-400 transition-colors cursor-pointer">
                  › Format Jurnal
                </button>
              </li>
              <li>
                <button type="button" @click="scrollToSection('alur')" class="hover:text-primary dark:hover:text-blue-400 transition-colors cursor-pointer">
                  › Alur Pengelolaan
                </button>
              </li>
              <li>
                <button type="button" @click="scrollToSection('jurusan')" class="hover:text-primary dark:hover:text-blue-400 transition-colors cursor-pointer">
                  › 3 Jurusan
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER COPYRIGHT -->
    <footer class="py-5 text-xs border-t border-slate-200/80 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 select-none">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center sm:text-left text-xs">
        © {{ new Date().getFullYear() }} <span class="font-bold text-slate-800 dark:text-slate-200">SMKN 71 JAKARTA</span>. Hak Cipta Dilindungi.
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import {
  ChevronRight,
  Menu,
  X,
  CheckCircle2,
  Clock,
  BookOpen,
  UserCheck,
  ShieldCheck,
  Sun,
  Moon,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useUiStore } from '~/stores/ui'

definePageMeta({
  layout: false
})

const uiStore = useUiStore()
const authStore = useAuthStore()
const isMobileMenuOpen = ref(false)
const activeSection = ref('tentang')

// Mouse Hover state (animasi merespons kursor mouse)
const hoveredSessionId = ref(2)

const navLinks = [
  { id: 'tentang', name: 'Tentang' },
  { id: 'format-jurnal', name: 'Format Jurnal' },
  { id: 'alur', name: 'Alur Sistem' },
  { id: 'jurusan', name: 'Jurusan' },
  { id: 'kontak', name: 'Kontak' },
]

function scrollToSection(id: string) {
  activeSection.value = id
  if (isMobileMenuOpen.value) {
    isMobileMenuOpen.value = false
  }

  if (typeof window !== 'undefined') {
    if (id === 'tentang') {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      })
      return
    }
    const el = document.getElementById(id)
    if (el) {
      const navOffset = 85
      const targetTop = el.getBoundingClientRect().top + window.pageYOffset - navOffset
      window.scrollTo({
        top: targetTop,
        behavior: 'smooth'
      })
    }
  }
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    const handleScroll = () => {
      if (window.scrollY < 80) {
        activeSection.value = 'tentang'
        return
      }
      const sectionIds = ['tentang', 'format-jurnal', 'alur', 'jurusan', 'kontak']
      const scrollPos = window.scrollY + 100

      for (let i = sectionIds.length - 1; i >= 0; i--) {
        const el = document.getElementById(sectionIds[i])
        if (el) {
          const top = el.offsetTop - 90
          if (scrollPos >= top) {
            activeSection.value = sectionIds[i]
            break
          }
        }
      }
    }

    window.addEventListener('scroll', handleScroll, { passive: true })
    handleScroll()
  }
})

useHead({
  title: 'Jurnal Kelas Digital - SMKN 71 JAKARTA',
  meta: [
    {
      name: 'description',
      content: 'Aplikasi resmi Jurnal Kelas Digital SMKN 71 JAKARTA. Pencatatan agenda materi pembelajaran harian KBM guru, rekapitulasi kehadiran per jam, dan pelaporan belajar siswa.'
    }
  ]
})
</script>

<style scoped>
html {
  scroll-behavior: smooth;
}
</style>
