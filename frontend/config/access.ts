export interface MenuItem {
  name: string
  to: string
  icon: string
}

export const ROLE_MENUS: Record<string, MenuItem[]> = {
  SISWA: [
    { name: 'Dashboard', to: '/dashboard', icon: 'Home' },
    { name: 'Jurnal Belajar', to: '/jurnal-pelajaran', icon: 'BookOpen' },
    { name: 'Kehadiran & Izin', to: '/riwayat-kehadiran', icon: 'CalendarCheck' },
  ],
  SEKRETARIS: [
    { name: 'Dashboard', to: '/dashboard', icon: 'Home' },
    { name: 'Presensi Kelas', to: '/presensi', icon: 'ClipboardCheck' },
    { name: 'Agenda KBM Kelas', to: '/jurnal-kelas', icon: 'BookOpen' },
  ],
  GURU_PENGAJAR: [
    { name: 'Dashboard', to: '/dashboard', icon: 'Home' },
    { name: 'Jurnal & Presensi KBM', to: '/jurnal-materi', icon: 'BookOpen' },
    { name: 'Rekap Kehadiran Mapel', to: '/rekap-semester', icon: 'BarChart2' },
  ],
  GURU_PEMBIMBING: [
    { name: 'Dashboard Wali Kelas', to: '/dashboard', icon: 'Home' },
    { name: 'Presensi Kelas', to: '/presensi', icon: 'ClipboardCheck' },
    { name: 'Persetujuan Izin', to: '/persetujuan-izin', icon: 'MailCheck' },
    { name: 'Murid Kelas', to: '/manajemen-murid', icon: 'GraduationCap' },
    { name: 'Rekap Presensi', to: '/rekap-semester', icon: 'BarChart2' },
  ],
  ORANG_TUA: [
    { name: 'Pantauan Anak', to: '/dashboard', icon: 'Home' },
    { name: 'Jurnal Kegiatan Anak', to: '/jurnal-pelajaran', icon: 'BookOpen' },
    { name: 'Kehadiran & Izin Anak', to: '/riwayat-kehadiran', icon: 'CalendarCheck' },
  ],
  SUPERADMIN: [
    { name: 'Dashboard', to: '/dashboard', icon: 'Home' },
    { name: 'Manajemen Pengguna', to: '/manajemen-user', icon: 'Users' },
    { name: 'Master Data & Jadwal', to: '/master-data', icon: 'Layers' },
    { name: 'WhatsApp Gateway', to: '/wa-gateway', icon: 'MessageSquare' },
    { name: 'Pengaturan Sistem', to: '/pengaturan-sistem', icon: 'Settings' },
  ],
}

export const ROLE_LABELS: Record<string, string> = {
  SISWA: 'Siswa',
  SEKRETARIS: 'Sekretaris',
  GURU_PENGAJAR: 'Guru',
  GURU_PEMBIMBING: 'Wali Kelas',
  ORANG_TUA: 'Orang Tua',
  SUPERADMIN: 'Super Administrator',
}
