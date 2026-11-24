<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Otomasi Batas Waktu Masuk (Auto-Alpha)
 * Mengubah siswa yang belum absen & belum izin menjadi ALPHA tepat saat cutoff waktu.
 */
Artisan::command('attendance:auto-alpha', function () {
    $now = Carbon::now('Asia/Jakarta');
    $today = $now->toDateString();

    // Lewati akhir pekan
    if ($now->dayOfWeekIso >= 6) {
        $this->info("Hari ini akhir pekan ({$now->translatedFormat('l')}). Otomasi Auto-Alpha dilewati.");
        return;
    }

    $activeStudents = User::whereIn('role', ['SISWA', 'SEKRETARIS'])
        ->where('is_active', true)
        ->whereNotNull('class_id')
        ->with('schoolClass')
        ->get();

    $alphaCount = 0;

    foreach ($activeStudents as $student) {
        $classId = $student->class_id;

        // Cek sesi presensi hari ini
        $session = AttendanceSession::firstOrCreate(
            ['class_id' => $classId, 'date' => $today],
            [
                'status' => 'DRAFT',
                'verification_status' => 'BELUM_KONFIRMASI',
                'school_hours' => Setting::get('default_school_hours', '06:45 - 15:00'),
                'room' => Setting::get('default_room_name', 'Ruang Kelas'),
            ]
        );

        // Cek apakah siswa sudah punya attendance record hari ini
        $record = AttendanceRecord::where('session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        if ($record && in_array($record->status, ['HADIR', 'SAKIT', 'IZIN', 'DISPENSASI'])) {
            continue; // Sudah presensi / ada izin
        }

        // Cek surat izin approved
        $hasApprovedLeave = LeaveRequest::where('student_id', $student->id)
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($hasApprovedLeave) {
            continue;
        }

        // Buat atau ubah status menjadi ALPHA
        AttendanceRecord::updateOrCreate(
            ['session_id' => $session->id, 'student_id' => $student->id],
            [
                'status' => 'ALPHA',
                'note' => "Auto-Alpha Sistem ({$now->format('H:i')} WIB) - Melewati batas waktu presensi",
            ]
        );

        $alphaCount++;

        // Notifikasi ke Siswa
        Notification::create([
            'user_id' => $student->id,
            'type' => 'PRESENSI',
            'title' => 'Status Presensi: ALPHA',
            'body' => "Anda dinyatakan ALPHA pada hari ini {$now->translatedFormat('l, d F Y')} karena tidak melakukan presensi sebelum batas waktu masuk sekolah.",
        ]);

        // Notifikasi & WA ke Orang Tua
        $parent = User::where('student_id', $student->id)->where('role', 'ORANG_TUA')->first()
            ?? User::where('nip_nis', $student->nip_nis)->where('role', 'ORANG_TUA')->first();

        if ($parent) {
            Notification::create([
                'user_id' => $parent->id,
                'type' => 'PRESENSI',
                'title' => 'Pemberitahuan Siswa ALPHA: ' . $student->name,
                'body' => "Putra/putri Anda {$student->name} tercatat ALPHA pada hari ini {$now->translatedFormat('l, d F Y')}.",
            ]);

            if ($parent->phone) {
                $className = $student->schoolClass->name ?? 'Kelas';
                $waMsg = WhatsAppService::formatTemplate('wa_template_alpha', [
                    'nama' => $student->name,
                    'kelas' => $className,
                    'tanggal' => $now->translatedFormat('l, d F Y'),
                ], "*Pemberitahuan Presensi*: Putra/putri Anda {$student->name} ({$className}) tercatat ALPHA pada {$today}.");

                WhatsAppService::send($parent->phone, $waMsg, 'ALPHA', $parent->id, $parent->name);
            }
        }
    }

    $adminId = \App\Models\User::where('role', 'SUPERADMIN')->value('id') ?? 1;

    AuditLog::create([
        'user_id' => $adminId,
        'action' => "Otomasi sistem: Auto-Alpha dijalankan. {$alphaCount} siswa tercatat ALPHA.",
        'ip_address' => '127.0.0.1',
    ]);

    $this->info("Auto-Alpha selesai: {$alphaCount} siswa diproses menjadi ALPHA.");
})->purpose('Otomasi batas waktu masuk presensi siswa ke status ALPHA');

/**
 * Otomasi Penguncian Sesi Presensi (Auto-Lock 15:00 WIB)
 * Mengunci sesi kelas yang masih berstatus DRAFT saat jam sekolah berakhir.
 */
Artisan::command('attendance:auto-lock', function () {
    $now = Carbon::now('Asia/Jakarta');
    $today = $now->toDateString();

    $draftSessions = AttendanceSession::whereDate('date', $today)
        ->where('status', 'DRAFT')
        ->with('schoolClass')
        ->get();

    $lockedCount = 0;

    foreach ($draftSessions as $session) {
        $session->update([
            'status' => 'LOCKED',
            'locked_at' => $now,
            'verification_status' => 'PENDING',
        ]);

        $lockedCount++;
        $className = $session->schoolClass->name ?? 'Kelas';

        // Notifikasi ke Wali Kelas
        if ($session->schoolClass && $session->schoolClass->advisor_id) {
            Notification::create([
                'user_id' => $session->schoolClass->advisor_id,
                'type' => 'PRESENSI',
                'title' => 'Auto-Lock Presensi: ' . $className,
                'body' => "Presensi kelas {$className} telah dikunci otomatis oleh sistem pada jam selesai sekolah ({$now->format('H:i')} WIB). Silakan periksa dan sahkan laporan presensi.",
            ]);
        }
    }

    $adminId = \App\Models\User::where('role', 'SUPERADMIN')->value('id') ?? 1;

    AuditLog::create([
        'user_id' => $adminId,
        'action' => "Otomasi sistem: Auto-Lock presensi sekolah dijalankan. {$lockedCount} sesi kelas dikunci otomatis.",
        'ip_address' => '127.0.0.1',
    ]);

    $this->info("Auto-Lock selesai: {$lockedCount} sesi kelas berhasil dikunci.");
})->purpose('Otomasi penguncian sesi presensi saat jam sekolah berakhir');

// Jadwal Cron Scheduler (Laravel Task Scheduler)
Schedule::command('attendance:auto-alpha')->weekdays()->at('08:00');
Schedule::command('attendance:auto-lock')->weekdays()->at('15:00');
