# Jurnal Kelas Digital

Aplikasi pencatatan jurnal kegiatan belajar mengajar (KBM), presensi kelas harian, dan rekapitulasi kehadiran semester berbasis web. Dibangun menggunakan arsitektur monorepo dengan **Laravel 11** sebagai REST API backend dan **Nuxt 3** sebagai frontend interface.

---

## Tech Stack

- **Backend**: Laravel 11, PHP 8.3+, SQLite / MySQL, Laravel Sanctum
- **Frontend**: Nuxt 3, Vue 3 (Composition API), Tailwind CSS, Pinia, Lucide Vue Next
- **Format Laporan**: HTML-to-Docx/Word, CSV/Excel Export, Native Print to PDF

---

## Fitur Utama

1. **Jurnal KBM & Agenda Mengajar**
   - Pencatatan materi pembelajaran, jam pelajaran, dan capaian KBM harian per rombel.
   - Pencatatan siswa yang izin keluar saat jam pelajaran beserta waktu dan alasan.
   - Verifikasi jurnal otomatis maupun manual oleh guru pengajar.

2. **Presensi Harian & Presensi Mapel**
   - Presensi harian kelas oleh Wali Kelas atau Sekretaris.
   - Rekap presensi mata pelajaran per pertemuan untuk guru pengajar.
   - Filter rombel otomatis: guru pengajar hanya dapat mengakses kelas dan mata pelajaran yang diampunya.

3. **Rekapitulasi Semester & Export Laporan**
   - Rekap kumulatif Hadir, Sakit, Izin, dan Alfa per semester.
   - Indikator otomatis ambang batas kehadiran minimal ujian (75%).
   - Riwayat detail siswa izin keluar kelas (*timeline leave history*).
   - Cetak laporan resmi bertanda tangan digital serta ekspor ke format Excel dan Word (.doc).

4. **Pengajuan & Persetujuan Surat Izin**
   - Pengajuan izin (Sakit / Izin) oleh siswa atau orang tua.
   - Panel review dan persetujuan izin oleh wali kelas.

5. **Multi-Role Access Control (RBAC)**
   - **Superadmin**: Manajemen master data (kelas, mapel, jadwal, user, setting sekolah).
   - **Wali Kelas**: Presensi harian rombel binaan, rekap kelas, dan persetujuan izin.
   - **Guru Pengajar**: Pengisian jurnal materi, absensi mapel, dan rekap mapel semester.
   - **Sekretaris Kelas**: Pengisian jurnal dan presensi harian rombel.
   - **Siswa**: Melihat riwayat presensi pribadi, jadwal pelajaran, dan pengajuan izin.
   - **Orang Tua**: Pemantauan kehadiran dan status izin anak.

---

## Akun Demo untuk Pengujian

Semua akun demo di bawah menggunakan password default: `password123`

| Role | Email Login | Password | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@demo.sch.id` | `password123` | Master data, manajemen user, jadwal, dan sistem |
| **Wali Kelas** | `walas@demo.sch.id` | `password123` | Presensi harian rombel, rekap semester, approval izin |
| **Guru Pengajar** | `guru1@demo.sch.id` | `password123` | Jurnal materi mapel, presensi KBM, rekap nilai/hadir |
| **Sekretaris** | `sekretaris1@demo.sch.id` | `password123` | Input jurnal KBM & presensi rombel |
| **Siswa** | `siswa1@demo.sch.id` | `password123` | Presensi mandiri, riwayat jadwal, surat izin |
| **Orang Tua** | `ortu.siswa1@demo.sch.id` | `password123` | Monitoring kehadiran anak |

*Catatan: Seluruh data nama guru, siswa, NIS/NIP, dan sekolah pada database bawaan repo ini telah disamarkan menggunakan data dummy demi kepatuhan privasi (UU PDP).*

---

## Panduan Instalasi Lokal

### Prasyarat
- PHP 8.2 atau lebih baru + Composer
- Node.js 18.x / 20.x + npm
- SQLite3 (atau MySQL)

### 1. Setup Backend (Laravel)

```bash
cd backend

# Install dependensi PHP
composer install

# Salin environment file
cp .env.example .env

# Generate Application Key
php artisan key:generate

# Storage link untuk file upload
php artisan storage:link

# Jalankan server API (port 8000)
php artisan serve
```

*Jika menggunakan SQLite bawaan, file database sudah tersedia di `backend/database/database.sqlite` lengkap dengan data dummy akun demo.*

### 2. Setup Frontend (Nuxt 3)

```bash
cd frontend

# Install dependensi Node
npm install

# Salin environment file
cp .env.example .env

# Jalankan server development (port 3000)
npm run dev
```

Buka browser dan akses aplikasi melalui `http://localhost:3000`.

---

## Struktur Direktori

```
jurnal-kelas-digital/
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/Api/  # Endpoint presensi, jurnal, rekap, user
│   │   └── Models/                # Eloquent models
│   ├── config/
│   ├── database/                  # Migrations, seeders, database.sqlite
│   ├── routes/api.php             # Route definitions
│   └── .env.example
├── frontend/
│   ├── components/                # Komponen UI reusable
│   ├── layouts/                   # Layout dashboard & auth
│   ├── pages/                     # Routing halaman Nuxt (jurnal, rekap, presensi)
│   ├── stores/                    # State management (Pinia)
│   └── .env.example
└── README.md
```

---

## Lisensi

Proyek ini dirilis di bawah lisensi [MIT](LICENSE).
