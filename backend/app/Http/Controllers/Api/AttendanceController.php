<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Schedule;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\LeaveRequest;
use App\Models\ClassJournal;
use App\Models\Setting;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function getToday(Request $request)
    {
        $user = $request->user();
        $date = $request->filled('date') ? $request->input('date') : Carbon::today('Asia/Jakarta')->toDateString();
        if ($user && in_array($user->role, ['SEKRETARIS', 'SISWA'])) {
            $classId = $user->class_id;
        } elseif ($user && $user->role === 'ORANG_TUA') {
            $child = $request->student_id ? User::find($request->student_id) : ($user->student_id ? User::find($user->student_id) : User::where('nip_nis', $user->nip_nis)->first());
            $classId = $child?->class_id ?? $user->class_id;
        } elseif ($user && $user->role === 'GURU_PEMBIMBING') {
            $walasClassId = \App\Models\SchoolClass::where('advisor_id', $user->id)->value('id') ?? $user->class_id;
            // Wali Kelas strictly locked to their own guided class (tidak ada pilihan kelas lain)
            $classId = $walasClassId ?: $user->class_id;
        } elseif ($user && $user->role === 'GURU_PENGAJAR') {
            // Guru Pengajar: HANYA berhak mengakses kelas-kelas yang ada dalam jadwal mengajarnya
            $scheduleClassIds = \App\Models\Schedule::where('teacher_id', $user->id)->pluck('class_id')->filter()->unique();
            $taughtClassIds = $user->taughtClasses()->pluck('classes.id');
            $allTaughtIds = $scheduleClassIds->concat($taughtClassIds)->filter()->unique()->values()->toArray();

            if (!empty($allTaughtIds)) {
                $requestedClassId = (int) $request->input('class_id', $allTaughtIds[0]);
                if (!in_array($requestedClassId, $allTaughtIds)) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki hak akses rekapitulasi kelas ini karena bukan kelas yang Anda ampu.'
                    ], 403);
                }
                $classId = $requestedClassId;
            } else {
                $classId = null;
            }
        } else {
            if ($user && $user->role === 'SUPERADMIN' && !$request->filled('class_id')) {
                return response()->json(['message' => 'Silakan pilih kelas terlebih dahulu.'], 422);
            }
            $classId = $request->input('class_id', $user->class_id ?? SchoolClass::value('id'));
        }

        if (!$classId) {
            return response()->json([
                'session' => null,
                'students' => [],
                'status' => 'EMPTY',
                'message' => 'Kelas rombel belum ditentukan.',
            ]);
        }

        $session = AttendanceSession::where('class_id', $classId)
            ->whereDate('date', $date)
            ->with(['records.student'])
            ->first();

        $latestSession = null;

        $students = User::where('class_id', $classId)
            ->whereIn('role', ['SISWA', 'SEKRETARIS'])
            ->orderBy('name')
            ->get();

        $recordsMap = [];
        if ($session && $session->records->count() > 0) {
            foreach ($session->records as $r) {
                $recordsMap[$r->student_id] = [
                    'status' => $r->status,
                    'note' => $r->note,
                    'attachment_path' => $r->attachment_path,
                ];
            }
        }

        // Otomatis sinkronisasi surat izin siswa yang telah disetujui Wali Kelas untuk tanggal ini
        $leaveRequests = LeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        foreach ($leaveRequests as $lr) {
            $recordsMap[$lr->student_id] = [
                'status' => in_array($lr->type, ['SAKIT', 'IZIN', 'DISPENSASI']) ? $lr->type : 'IZIN',
                'note' => $lr->reason ?: 'Surat izin disetujui Wali Kelas',
                'attachment_path' => $lr->attachment_path,
                'is_rejected_leave' => false,
            ];
        }

        // Cek surat izin siswa yang DITOLAK Wali Kelas untuk tanggal ini
        $rejectedLeaves = LeaveRequest::where('status', 'REJECTED')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        foreach ($rejectedLeaves as $rl) {
            $existing = $recordsMap[$rl->student_id] ?? null;
            // Jika belum ada record verifikasi kelas fisik (atau hanya presensi mandiri HP), statusnya ALPHA (Ditolak)
            if (!$existing || str_contains($existing['note'] ?? '', 'Presensi Mandiri Pukul')) {
                $recordsMap[$rl->student_id] = [
                    'status' => 'ALPHA',
                    'note' => 'Izin Ditolak Walas: ' . ($rl->review_note ?: 'Tidak memenuhi syarat') . ' (Wajib Verifikasi di Kelas)',
                    'attachment_path' => null,
                    'is_rejected_leave' => true,
                ];
            } else {
                $recordsMap[$rl->student_id]['is_rejected_leave'] = true;
            }
        }

        $now = Carbon::now('Asia/Jakarta');
        $isToday = ($date === $now->toDateString());
        $isPast = ($date < $now->toDateString());

        // Auto-Alfa jika waktu server hari ini telah melewati batas toleransi jam masuk pagi (default: 06:45 + 30 menit = 07:15 WIB)
        $schoolHours = Setting::get('default_school_hours', '06:45 - 15:00');
        $hoursParts = explode('-', $schoolHours);
        $startTime = trim($hoursParts[0] ?? '06:45');
        $toleranceMinutes = (int) Setting::get('attendance_late_tolerance_minutes', '30');
        try {
            $startCarbon = Carbon::createFromTimeString($startTime, 'Asia/Jakarta');
            $cutoffCarbon = $startCarbon->copy()->addMinutes($toleranceMinutes);
            $isPastCutoffToday = $isToday && $now->greaterThan($cutoffCarbon);
        } catch (\Exception $e) {
            $isPastCutoffToday = false;
        }

        // Auto-lock hanya untuk sesi presensi tanggal lampau yang belum terkunci
        if ($session && $isPast) {
            if ($session->status !== 'LOCKED') {
                $session->update([
                    'status' => 'LOCKED',
                    'locked_at' => $session->locked_at ?: $now,
                ]);
            }
        }

        $list = $students->map(function ($std, $idx) use ($recordsMap, $isToday, $isPast, $isPastCutoffToday, $session) {
            $rec = $recordsMap[$std->id] ?? null;
            if ($rec) {
                $status = $rec['status'];
                $note = $rec['note'] ?? null;
                if ($note === 'Menunggu pencatatan presensi kelas') {
                    $note = null;
                }
            } else {
                // Default presensi: Jika belum ada catatan presensi / izin
                // Jika tanggal lampau, sesi terkunci, ATAU sudah melewati batas jam masuk hari ini (07:15 WIB): otomatis ALFA
                if ($isPast || ($session && $session->status === 'LOCKED') || ($isToday && $isPastCutoffToday)) {
                    $status = 'ALPHA';
                    $note = 'Tidak hadir / Lewat batas waktu presensi';
                } else {
                    $status = 'BELUM_ABSEN';
                    $note = null;
                }
            }

            return [
                'no' => $idx + 1,
                'student_id' => $std->id,
                'name' => $std->name,
                'nis' => $std->nip_nis ?? '-',
                'status' => $status,
                'note' => $note,
                'attachment' => $rec['attachment_path'] ?? null,
                'is_rejected_leave' => !empty($rec['is_rejected_leave']),
            ];
        });

        // Calculate summary counts
        $total = $list->count();
        $belumAbsen = $list->where('status', 'BELUM_ABSEN')->count();
        $hadir = $list->where('status', 'HADIR')->count();
        $sakit = $list->where('status', 'SAKIT')->count();
        $izin = $list->where('status', 'IZIN')->count();
        $alpha = $list->where('status', 'ALPHA')->count();

        return response()->json([
            'session' => [
                'id' => $session->id ?? ($latestSession->id ?? null),
                'date' => $session ? $session->date : $date,
                'is_weekend' => Carbon::parse($date)->isWeekend(),
                'status' => $session ? $session->status : ($latestSession ? $latestSession->status : 'DRAFT'),
                'verification_status' => $session ? $session->verification_status : ($latestSession ? $latestSession->verification_status : 'BELUM_KONFIRMASI'),
                'school_hours' => $session->school_hours ?? ($latestSession->school_hours ?? Setting::get('default_school_hours', '06:45 - 15:00')),
                'room' => $session->room ?? ($latestSession->room ?? Setting::get('default_room_name', 'Ruang Kelas')),
                'class_id' => $classId,
                'class_name' => \App\Models\SchoolClass::find($classId)->name ?? 'Kelas Industri',
                'is_past_cutoff' => $isPastCutoffToday,
                'cutoff_time' => isset($cutoffCarbon) ? $cutoffCarbon->format('H:i') : '07:15',
            ],
            'server_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_day' => Carbon::now('Asia/Jakarta')->translatedFormat('l'),
            'server_time' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
            'summary' => [
                'total' => $total,
                'belum_absen' => $belumAbsen,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpha' => $alpha,
            ],
            'students' => $list,
        ])
        ->header('X-Server-Date', Carbon::today('Asia/Jakarta')->toDateString())
        ->header('X-Server-Day', Carbon::now('Asia/Jakarta')->translatedFormat('l'))
        ->header('X-Server-Time', Carbon::now('Asia/Jakarta')->toDateTimeString())
        ->header('Access-Control-Expose-Headers', 'X-Server-Date, X-Server-Day, X-Server-Time');
    }

    /**
     * Presensi Mandiri Siswa (Self Check-In) agar Sekretaris tidak perlu menginput satu per satu.
     */
    public function selfCheckIn(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['SISWA', 'SEKRETARIS'])) {
            return response()->json(['message' => 'Fitur Presensi Mandiri dikhususkan untuk Siswa.'], 403);
        }

        $today = Carbon::today()->toDateString();
        $classId = $user->class_id;
        if (!$classId) {
            return response()->json(['message' => 'Akun Anda belum terdaftar dalam kelas rombel.'], 422);
        }

        // Cek apakah siswa sedang dalam masa Surat Izin yang telah disetujui Wali Kelas
        $approvedLeave = LeaveRequest::where('student_id', $user->id)
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if ($approvedLeave) {
            return response()->json([
                'message' => 'Status kehadiran Anda hari ini telah tercatat sebagai ' . $approvedLeave->type . ' sesuai surat izin yang telah disetujui Wali Kelas. Anda tidak dapat melakukan presensi mandiri.',
                'status' => $approvedLeave->type,
            ], 422);
        }

        // Cek apakah siswa memiliki surat izin yang DITOLAK Wali Kelas untuk hari ini
        $rejectedLeave = LeaveRequest::where('student_id', $user->id)
            ->where('status', 'REJECTED')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if ($rejectedLeave) {
            return response()->json([
                'message' => 'Surat izin Anda hari ini telah DITOLAK oleh Wali Kelas (' . ($rejectedLeave->review_note ?: 'Alasan izin tidak diterima') . '). Anda tidak dapat melakukan presensi mandiri dari HP. Kehadiran fisik Anda wajib diverifikasi langsung oleh Sekretaris Kelas atau Wali Kelas di sekolah.',
                'status' => 'DITOLAK',
                'is_leave_rejected' => true,
            ], 422);
        }

        $now = Carbon::now('Asia/Jakarta');
        $timeStr = $now->format('H:i');

        // 1. Celah 1: Validasi Hari Libur Akhir Pekan
        if ($now->isWeekend()) {
            return response()->json([
                'message' => 'Hari ini adalah hari libur akhir pekan (' . $now->translatedFormat('l') . '). Presensi mandiri tidak aktif pada hari libur.',
                'status' => 'LIBUR',
            ], 422);
        }

        $schoolHours = Setting::get('default_school_hours', '06:45 - 15:00');
        $hoursParts = explode('-', $schoolHours);
        $startTime = trim($hoursParts[0] ?? '06:45');
        $endTime = trim($hoursParts[1] ?? '15:00');

        $toleranceMinutes = (int) Setting::get('attendance_late_tolerance_minutes', '30');
        $startCarbon = Carbon::createFromTimeString($startTime, 'Asia/Jakarta');
        $cutoffCarbon = $startCarbon->copy()->addMinutes($toleranceMinutes);
        $cutoffTime = $cutoffCarbon->format('H:i');

        // 2. Jam Buka Presensi Mandiri (Bisa diakses mulai pukul 05:30 WIB)
        $openTimeStr = Setting::get('attendance_open_time', '05:30');
        $earliestCarbon = Carbon::createFromTimeString($openTimeStr, 'Asia/Jakarta');
        if ($now->lessThan($earliestCarbon)) {
            $earliestTime = $earliestCarbon->format('H:i');
            return response()->json([
                'message' => "Presensi mandiri belum dibuka. Sesi presensi dapat diakses mulai pukul {$earliestTime} WIB (waktu server: {$timeStr} WIB).",
                'status' => 'BELUM_DIBUKA',
            ], 422);
        }

        if ($now->greaterThan($cutoffCarbon)) {
            return response()->json([
                'message' => "Batas toleransi presensi mandiri ({$cutoffTime} WIB) telah berakhir (waktu server: {$timeStr} WIB). Silakan verifikasi fisik kehadiran Anda ke Wali Kelas atau Sekretaris Kelas di sekolah.",
                'status' => 'ALPHA',
                'is_late' => true,
            ], 422);
        }

        $isLate = $now->greaterThan($startCarbon);

        $session = AttendanceSession::where('class_id', $classId)
            ->whereDate('date', $today)
            ->first();

        // 3. Celah 3: Blokir Siswa Jika Sesi Presensi Kelas Sudah Terkunci (LOCKED)
        if ($session && $session->status === 'LOCKED') {
            return response()->json([
                'message' => 'Sesi presensi kelas hari ini telah dikunci resmi oleh Pengurus Kelas / Wali Kelas. Anda tidak dapat melakukan presensi mandiri dari HP. Silakan hubungi Wali Kelas.',
                'status' => 'TERKUNCI',
            ], 403);
        }

        $defaultRoom = Setting::get('default_room_name', 'Ruang Kelas');
        $defaultHours = Setting::get('default_school_hours', '06:45 - 15:00');

        if (!$session) {
            $session = AttendanceSession::create([
                'class_id' => $classId,
                'date' => $today,
                'status' => 'DRAFT',
                'verification_status' => 'BELUM_KONFIRMASI',
                'school_hours' => $defaultHours,
                'room' => $defaultRoom,
            ]);
        }

        $customNote = trim((string) $request->input('note', ''));
        $prefix = $isLate ? "Presensi Mandiri - TERLAMBAT ({$timeStr} WIB)" : "Presensi Mandiri Pukul {$timeStr} WIB";
        $note = $customNote !== '' ? "{$prefix} - {$customNote}" : $prefix;

        $record = AttendanceRecord::updateOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $user->id,
            ],
            [
                'status' => 'HADIR',
                'note' => $note,
            ]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => "Melakukan Presensi Mandiri (HADIR) pukul {$timeStr} WIB",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Presensi Mandiri berhasil! Kehadiran Anda tercatat pukul {$timeStr} WIB.",
            'record' => [
                'id' => $record->id,
                'status' => $record->status,
                'note' => $record->note,
                'date' => Carbon::parse($today)->translatedFormat('l, d M Y'),
                'time' => $timeStr . ' WIB',
            ],
        ]);
    }

    public function saveDraft(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'GURU_PEMBIMBING') {
            return response()->json(['message' => 'Wali Kelas bertugas mengesahkan (ACC) presensi, pencatatan presensi dilakukan oleh Sekretaris.'], 403);
        }

        if (!in_array($user->role, ['SEKRETARIS', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Hanya Sekretaris atau Superadmin yang berhak menyimpan perubahan rekap presensi.'], 403);
        }

        if (Carbon::parse($request->date)->isWeekend()) {
            return response()->json([
                'message' => 'Presensi kelas harian tidak dapat dicatat pada hari libur akhir pekan (Sabtu/Minggu). Hari belajar hanya berlangsung Senin hingga Jumat.',
            ], 422);
        }

        if ($user->role === 'SEKRETARIS' || $user->role === 'SISWA') {
            $classId = $user->class_id;
        } else {
            $classId = $request->class_id ?: $user->class_id;
        }

        if (!$classId) {
            return response()->json(['message' => 'Kelas (class_id) tidak valid atau belum ditentukan.'], 422);
        }

        $request->validate([
            'date' => 'required|date',
            'records' => 'required|array',
        ]);

        $session = AttendanceSession::where('class_id', $classId)
            ->whereDate('date', $request->date)
            ->first();

        $defaultRoom = Setting::get('default_room_name', 'Ruang Kelas');
        $defaultHours = Setting::get('default_school_hours', '06:45 - 15:00');

        if (!$session) {
            $session = AttendanceSession::create([
                'class_id' => $classId,
                'date' => $request->date,
                'status' => 'DRAFT',
                'verification_status' => 'BELUM_KONFIRMASI',
                'school_hours' => $defaultHours,
                'room' => $defaultRoom,
            ]);
        }

        if ($session->status === 'LOCKED') {
            $isWalasUser = ($user->role === 'GURU_PEMBIMBING')
                || \App\Models\SchoolClass::where('advisor_id', $user->id)->exists();

            if (!in_array($user->role, ['GURU_PEMBIMBING', 'SUPERADMIN']) && !$isWalasUser) {
                return response()->json(['message' => 'Presensi telah dikunci. Perubahan hanya dapat dilakukan oleh Wali Kelas atau Superadmin.'], 403);
            }

            // Batas waktu koreksi Walas: keesokan harinya pukul setting (default 15:00 WIB)
            if ($user->role !== 'SUPERADMIN') {
                $deadlineTime = Setting::get('walas_correction_deadline_time', '15:00');
                $dParts = explode(':', $deadlineTime);
                $dHour = (int) ($dParts[0] ?? 15);
                $dMin = (int) ($dParts[1] ?? 0);
                $sessionDate = Carbon::parse($session->date, 'Asia/Jakarta')->startOfDay();
                $cutoff = $sessionDate->copy()->addDay()->setTime($dHour, $dMin, 0);
                if (Carbon::now('Asia/Jakarta')->greaterThan($cutoff)) {
                    return response()->json([
                        'message' => "Batas waktu koreksi presensi oleh Wali Kelas telah berakhir (maksimal keesokan harinya pukul {$deadlineTime} WIB). Data telah permanen dan tidak dapat diubah.",
                    ], 403);
                }
            }
        }

        foreach ($request->records as $r) {
            $studentId = $r['student_id'] ?? ($r['id'] ?? null);
            if (!$studentId) continue;
            AttendanceRecord::updateOrCreate(
                ['session_id' => $session->id, 'student_id' => $studentId],
                [
                    'status' => $r['status'] ?? 'HADIR',
                    'note' => $r['note'] ?? null,
                ]
            );
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Menyimpan rekap presensi kelas tanggal ' . $request->date,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Data rekap presensi berhasil disimpan']);
    }

    public function forceUnlock(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['SUPERADMIN', 'GURU_PEMBIMBING'])) {
            return response()->json(['message' => 'Hanya Wali Kelas atau Superadmin yang berhak membuka kunci sesi presensi.'], 403);
        }

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
        ]);

        if ($user->role !== 'SUPERADMIN') {
            $walasClassId = \App\Models\SchoolClass::where('advisor_id', $user->id)->value('id') ?? $user->class_id;
            if (!$walasClassId || (int)$request->class_id !== (int)$walasClassId) {
                return response()->json(['message' => 'Wali Kelas hanya berhak mengelola sesi presensi untuk kelas binaan sendiri.'], 403);
            }
        }

        $session = AttendanceSession::where('class_id', $request->class_id)
            ->whereDate('date', $request->date)
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Sesi presensi tidak ditemukan pada tanggal tersebut.'], 404);
        }

        $session->update([
            'status' => 'DRAFT',
            'verification_status' => 'BELUM_KONFIRMASI',
            'locked_at' => null,
            'locked_by_id' => null,
        ]);

        $schoolClass = SchoolClass::find($request->class_id);
        $className = $schoolClass->name ?? 'Kelas';

        AuditLog::create([
            'user_id' => $user->id,
            'action' => "Force Unlock presensi kelas {$className} tanggal {$request->date} kembali ke DRAFT",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Sesi presensi {$className} tanggal {$request->date} berhasil dibuka kembali (status DRAFT).",
            'session' => $session,
        ]);
    }

    public function lockSession(Request $request)
    {
        $user = $request->user();
        $isWalas = ($user->role === 'GURU_PEMBIMBING')
            || \App\Models\SchoolClass::where('advisor_id', $user->id)->exists();

        if (!in_array($user->role, ['SEKRETARIS', 'SUPERADMIN']) && !$isWalas) {
            return response()->json(['message' => 'Hanya Wali Kelas, Sekretaris Kelas, atau Superadmin yang berhak mengunci presensi.'], 403);
        }

        if (in_array($user->role, ['SEKRETARIS', 'SISWA'])) {
            $classId = $user->class_id;
        } elseif ($isWalas && $user->role !== 'SUPERADMIN') {
            $guidedClassId = SchoolClass::where('advisor_id', $user->id)->value('id') ?? $user->class_id;
            $classId = $request->class_id ?: $guidedClassId;
            if ($guidedClassId && (int)$classId !== (int)$guidedClassId) {
                return response()->json(['message' => 'Anda hanya berhak mengunci presensi untuk kelas binaan Anda.'], 403);
            }
        } else {
            $classId = $request->class_id ?: $user->class_id;
        }

        if (!$classId) {
            return response()->json(['message' => 'Kelas (class_id) wajib ditentukan.'], 422);
        }

        $request->validate([
            'date' => 'required|date',
            'pin' => 'required',
            'records' => 'required|array',
        ]);

        if (Carbon::parse($request->date)->isWeekend()) {
            return response()->json([
                'message' => 'Presensi kelas harian tidak dapat dikunci pada hari libur akhir pekan (Sabtu/Minggu). Hari belajar hanya berlangsung Senin hingga Jumat.',
            ], 422);
        }

        $schoolPin = Setting::get('attendance_lock_pin', '1234');
        $isSchoolPinValid = !empty($schoolPin) && (string)$request->pin === (string)$schoolPin;
        $isUserPasswordValid = Hash::check($request->pin, $user->password);

        if (!$isSchoolPinValid && !$isUserPasswordValid) {
            return response()->json(['message' => 'PIN presensi atau kata sandi akun Anda tidak valid.'], 422);
        }

        $session = AttendanceSession::where('class_id', $classId)
            ->whereDate('date', $request->date)
            ->first();

        $defaultRoom = Setting::get('default_room_name', 'Ruang Kelas');
        $defaultHours = Setting::get('default_school_hours', '06:45 - 15:00');

        if (!$session) {
            $session = AttendanceSession::create([
                'class_id' => $classId,
                'date' => $request->date,
                'school_hours' => $defaultHours,
                'room' => $defaultRoom,
            ]);
        } elseif ($session->status === 'LOCKED') {
            if (in_array($user->role, ['SEKRETARIS', 'SISWA'])) {
                return response()->json(['message' => 'Sesi presensi ini telah dikunci resmi. Hanya Wali Kelas atau Administrator yang berhak mengubah.'], 403);
            }
            if ($user->role !== 'SUPERADMIN') {
                $deadlineTime = Setting::get('walas_correction_deadline_time', '15:00');
                $dParts = explode(':', $deadlineTime);
                $dHour = (int) ($dParts[0] ?? 15);
                $dMin = (int) ($dParts[1] ?? 0);
                $sessionDate = Carbon::parse($session->date, 'Asia/Jakarta')->startOfDay();
                $cutoff = $sessionDate->copy()->addDay()->setTime($dHour, $dMin, 0);
                if (Carbon::now('Asia/Jakarta')->greaterThan($cutoff)) {
                    return response()->json([
                        'message' => "Batas waktu koreksi presensi oleh Wali Kelas telah berakhir (maksimal keesokan harinya pukul {$deadlineTime} WIB). Data telah permanen dan tidak dapat diubah.",
                    ], 403);
                }
            }
        }

        foreach ($request->records as $r) {
            $studentId = $r['student_id'] ?? ($r['id'] ?? null);
            if (!$studentId) continue;
            AttendanceRecord::updateOrCreate(
                ['session_id' => $session->id, 'student_id' => $studentId],
                [
                    'status' => $r['status'] ?? 'HADIR',
                    'note' => $r['note'] ?? null,
                ]
            );
        }

        $session->update([
            'status' => 'LOCKED',
            'locked_by_id' => $user->id,
            'locked_at' => Carbon::now(),
            'verification_status' => 'PENDING',
        ]);

        // Kirim notifikasi ke Guru Pengajar / Wali Kelas bahwa presensi kelas telah dikunci
        $schoolClass = SchoolClass::find($classId);
        $className = $schoolClass->name ?? 'Kelas';

        $teacherIds = Schedule::where('class_id', $classId)->pluck('teacher_id')->toArray();
        if ($schoolClass && $schoolClass->advisor_id) {
            $teacherIds[] = $schoolClass->advisor_id;
        }
        $teacherIds = array_unique(array_filter($teacherIds));

        foreach ($teacherIds as $tid) {
            Notification::create([
                'user_id' => $tid,
                'type' => 'PRESENSI',
                'title' => 'Rekap Presensi Kelas ' . $className . ' Disimpan',
                'body' => $user->name . ' telah memfinalisasi rekap presensi harian ' . $className . ' tanggal ' . $request->date . '.',
            ]);
        }

        // Kirim juga notifikasi ke siswa jika statusnya bukan HADIR (Sakit/Izin/Alpha) serta ke Orang Tua
        if (is_array($request->records)) {
            foreach ($request->records as $r) {
                $st = $r['status'] ?? 'HADIR';
                $sId = $r['student_id'] ?? ($r['id'] ?? null);
                if ($sId && in_array($st, ['SAKIT', 'IZIN', 'ALPHA'])) {
                    Notification::create([
                        'user_id' => $sId,
                        'type' => 'PRESENSI',
                        'title' => 'Status Presensi Harian: ' . $st,
                        'body' => 'Presensi Anda pada tanggal ' . $request->date . ' tercatat: ' . $st . (!empty($r['note']) ? ' (' . $r['note'] . ')' : '') . '.',
                    ]);

                    $parent = User::where('student_id', $sId)->where('role', 'ORANG_TUA')->first();
                    if ($parent) {
                        $studentUser = User::find($sId);
                        Notification::create([
                            'user_id' => $parent->id,
                            'type' => 'PRESENSI',
                            'title' => 'Laporan Presensi Ananda: ' . $st,
                            'body' => 'Presensi ananda ' . ($studentUser->name ?? 'siswa') . ' pada tanggal ' . $request->date . ' tercatat: ' . $st . (!empty($r['note']) ? ' (' . $r['note'] . ')' : '') . '.',
                        ]);

                        // Otomasi WhatsApp ke Orang Tua jika siswa dinyatakan ALPHA
                        if ($st === 'ALPHA' && $parent->phone) {
                            $waMessage = \App\Services\WhatsAppService::formatTemplate('wa_template_alpha', [
                                'nama' => $studentUser->name ?? 'Siswa',
                                'kelas' => $className,
                                'tanggal' => Carbon::parse($request->date)->translatedFormat('l, d F Y'),
                            ], "*Pemberitahuan Presensi*: Putra/putri Anda " . ($studentUser->name ?? 'siswa') . " ({$className}) tercatat ALPHA pada {$request->date}.");

                            \App\Services\WhatsAppService::send(
                                $parent->phone,
                                $waMessage,
                                'ALPHA',
                                $parent->id,
                                $parent->name
                            );
                        }
                    }
                }
            }
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Mengunci presensi kelas tanggal ' . $request->date,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Presensi berhasil disimpan dan dikunci',
            'session' => $session,
        ]);
    }

    public function confirmSession(Request $request)
    {
        $user = $request->user();
        $isWalas = ($user->role === 'GURU_PEMBIMBING')
            || \App\Models\SchoolClass::where('advisor_id', $user->id)->exists();
        if (!$isWalas && $user->role !== 'SUPERADMIN') {
            return response()->json(['message' => 'Hanya Wali Kelas (Guru Pembimbing) atau Superadmin yang berhak mengesahkan presensi harian kelas ini.'], 403);
        }

        if ($user->role !== 'SUPERADMIN') {
            $walasClassId = \App\Models\SchoolClass::where('advisor_id', $user->id)->value('id') ?? $user->class_id;
            if (!$walasClassId) {
                return response()->json(['message' => 'Anda belum terdaftar sebagai Wali Kelas pada rombel manapun.'], 403);
            }
            if ($request->filled('class_id') && (int)$request->input('class_id') !== (int)$walasClassId) {
                return response()->json(['message' => 'Wali Kelas hanya berhak mengesahkan presensi untuk kelas binaan sendiri.'], 403);
            }
            $classId = $walasClassId;
        } else {
            $classId = $request->input('class_id');
        }

        if (!$classId) {
            return response()->json(['message' => 'Parameter kelas (class_id) wajib disertakan.'], 422);
        }
        $date = $request->input('date', Carbon::today()->toDateString());

        if (Carbon::parse($date)->isWeekend()) {
            return response()->json(['message' => 'Presensi kelas harian tidak dapat disahkan pada hari libur akhir pekan (Sabtu/Minggu).'], 422);
        }

        $session = AttendanceSession::where('class_id', $classId)
            ->whereDate('date', $date)
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Sesi presensi belum tersedia untuk tanggal ini.'], 422);
        }

        $session->update([
            'verification_status' => 'TERVERIFIKASI',
        ]);

        $schoolClass = SchoolClass::find($classId);
        $className = $schoolClass->name ?? 'Kelas';
        if ($session->locked_by_id) {
            Notification::create([
                'user_id' => $session->locked_by_id,
                'type' => 'PRESENSI',
                'title' => 'Presensi Disahkan oleh Guru / Wali Kelas',
                'body' => $user->name . ' telah mengesahkan dan memverifikasi presensi harian kelas ' . $className . ' tanggal ' . $date . '.',
            ]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Mengesahkan presensi harian kelas ID #' . $classId . ' tanggal ' . $date,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Presensi harian kelas berhasil disahkan & terverifikasi!',
            'session' => $session,
        ]);
    }

    public function studentHistory(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        // Jika login sebagai ORANG_TUA, tampilkan riwayat kehadiran anaknya
        $targetStudentId = $user->id;
        $classId = $user->class_id;
        if ($user->role === 'ORANG_TUA') {
            $child = null;
            if ($request->filled('student_id')) {
                $child = User::find($request->input('student_id'));
            }
            if (!$child && $user->student_id) {
                $child = User::find($user->student_id);
            }
            if (!$child && $user->nip_nis) {
                $child = User::where('nip_nis', $user->nip_nis)
                    ->whereIn('role', ['SISWA', 'SEKRETARIS'])
                    ->first();
            }
            if (!$child && $user->class_id) {
                $child = User::where('class_id', $user->class_id)
                    ->whereIn('role', ['SISWA', 'SEKRETARIS'])
                    ->first();
            }
            if (!$child) {
                return response()->json([
                    'summary' => [
                        'total' => 0,
                        'hadir' => 0,
                        'hadir_percent' => 100,
                        'sakit' => 0,
                        'izin' => 0,
                        'alpha' => 0,
                    ],
                    'weekly' => [],
                    'details' => [],
                    'message' => 'Akun Orang Tua ini belum terhubung ke data murid manapun.',
                ]);
            }
            $targetStudentId = $child->id;
            $classId = $child->class_id;
        }

        // Cari class_id yang benar jika user belum memiliki class_id yang tepat
        if (!$classId) {
            $latestRecord = AttendanceRecord::where('student_id', $targetStudentId)->latest('id')->first();
            if ($latestRecord && $latestRecord->session) {
                $classId = $latestRecord->session->class_id;
            } elseif ($user->class_id) {
                $classId = $user->class_id;
            }
        }

        if (!$classId) {
            return response()->json([
                'summary' => [
                    'total' => 0,
                    'hadir' => 0,
                    'hadir_percent' => 0,
                    'sakit' => 0,
                    'izin' => 0,
                    'alpha' => 0,
                ],
                'weekly' => [],
                'details' => [],
            ]);
        }

        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();
        $nowJakarta = Carbon::now('Asia/Jakarta');

        // Status kehadiran siswa hari ini (untuk validasi lock form izin)
        $todayRecord = AttendanceRecord::where('student_id', $targetStudentId)
            ->where(function ($q) use ($todayStr) {
                $q->whereHas('session', function ($sq) use ($todayStr) {
                    $sq->whereDate('date', $todayStr);
                })->orWhereDate('created_at', $todayStr);
            })
            ->latest('id')
            ->first();

        $todayStatus = $todayRecord ? $todayRecord->status : null;
        if (!$todayStatus) {
            $todayApprovedLeave = LeaveRequest::where('student_id', $targetStudentId)
                ->where('status', 'APPROVED')
                ->whereDate('start_date', '<=', $todayStr)
                ->whereDate('end_date', '>=', $todayStr)
                ->first();
            if ($todayApprovedLeave) {
                $todayStatus = $todayApprovedLeave->type;
            }
        }

        // Batas toleransi jam masuk (default 06:45 + 30 min = 07:15 WIB)
        $schoolHours = Setting::get('default_school_hours', '06:45 - 15:00');
        $hoursParts = explode('-', $schoolHours);
        $startTime = trim($hoursParts[0] ?? '06:45');
        $toleranceMinutes = (int) Setting::get('attendance_late_tolerance_minutes', '30');
        $cutoffCarbon = Carbon::createFromTimeString($startTime, 'Asia/Jakarta')->addMinutes($toleranceMinutes);
        $cutoffTimeStr = $cutoffCarbon->format('H:i');
        $isPastCutoffToday = $nowJakarta->greaterThan($cutoffCarbon);

        // Ambil data jurnal KBM bulan ini untuk integrasi badge izin/alfa mapel di tabel harian
        $monthJournals = ClassJournal::where('class_id', $classId)
            ->where('date', 'like', $month . '%')
            ->with(['schedule.subject', 'schedule.teacher'])
            ->get();

        // Riwayat lengkap log izin keluar jam mapel (untuk Sub-tab Log Izin Keluar Mapel)
        $allClassJournals = ClassJournal::where('class_id', $classId)
            ->whereNotNull('schedule_id')
            ->with(['schedule.subject', 'schedule.teacher'])
            ->orderBy('date', 'desc')
            ->get();

        $kbmLeaveLogs = [];
        foreach ($allClassJournals as $cj) {
            $notes = $cj->notes;
            if ($notes && str_starts_with(trim($notes), '{')) {
                $dec = json_decode($notes, true);
                if (is_array($dec) && !empty($dec['student_attendance'])) {
                    foreach ($dec['student_attendance'] as $sa) {
                        if (($sa['id'] ?? null) == $targetStudentId && strtoupper($sa['status'] ?? '') === 'IZIN') {
                            $kbmLeaveLogs[] = [
                                'id' => $cj->id . '_' . ($sa['id'] ?? ''),
                                'journal_id' => $cj->id,
                                'date' => Carbon::parse($cj->date)->translatedFormat('l, d M Y'),
                                'raw_date' => Carbon::parse($cj->date)->toDateString(),
                                'subject' => $cj->schedule?->subject?->name ?? 'Mata Pelajaran',
                                'teacher' => $cj->schedule?->teacher?->name ?? 'Guru Pengajar',
                                'schedule_time' => ($cj->schedule?->start_time ? substr($cj->schedule->start_time, 0, 5) : '07:30') . ' - ' . ($cj->schedule?->end_time ? substr($cj->schedule->end_time, 0, 5) : '09:00'),
                                'leave_time' => $sa['leave_time'] ?? 'Saat KBM',
                                'reason' => $sa['note'] ?? 'Izin keluar kelas saat KBM',
                            ];
                            break;
                        }
                    }
                }
            }
        }

        // Alert cards hari ini (Izin Keluar & Alfa Mapel hari ini)
        $todayKbmLeaves = [];
        $todayKbmAlfa = [];
        $todayJournals = $monthJournals->filter(fn($j) => Carbon::parse($j->date)->toDateString() === $todayStr);
        foreach ($todayJournals as $cj) {
            $notes = $cj->notes;
            if ($notes && str_starts_with(trim($notes), '{')) {
                $dec = json_decode($notes, true);
                if (is_array($dec) && !empty($dec['student_attendance'])) {
                    foreach ($dec['student_attendance'] as $sa) {
                        if (($sa['id'] ?? null) == $targetStudentId) {
                            $st = strtoupper($sa['status'] ?? '');
                            if ($st === 'IZIN') {
                                $todayKbmLeaves[] = [
                                    'journal_id' => $cj->id,
                                    'subject' => $cj->schedule?->subject?->name ?? 'Mata Pelajaran',
                                    'teacher' => $cj->schedule?->teacher?->name ?? 'Guru Pengajar',
                                    'schedule_time' => ($cj->schedule?->start_time ? substr($cj->schedule->start_time, 0, 5) : '07:30') . ' - ' . ($cj->schedule?->end_time ? substr($cj->schedule->end_time, 0, 5) : '09:00'),
                                    'leave_time' => $sa['leave_time'] ?? 'Saat KBM',
                                    'reason' => $sa['note'] ?? 'Izin keluar kelas saat KBM',
                                ];
                            } elseif (in_array($st, ['ALPHA', 'ALFA'])) {
                                $todayKbmAlfa[] = [
                                    'journal_id' => $cj->id,
                                    'subject' => $cj->schedule?->subject?->name ?? 'Mata Pelajaran',
                                    'teacher' => $cj->schedule?->teacher?->name ?? 'Guru Pengajar',
                                    'schedule_time' => ($cj->schedule?->start_time ? substr($cj->schedule->start_time, 0, 5) : '07:30') . ' - ' . ($cj->schedule?->end_time ? substr($cj->schedule->end_time, 0, 5) : '09:00'),
                                    'note' => 'Tercatat Alfa di jam mata pelajaran oleh guru',
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Ambil semua session kelas pada bulan tersebut yang sudah lewat atau hari ini (tidak mengambil sesi masa depan)
        $sessions = AttendanceSession::where('class_id', $classId)
            ->where('date', 'like', $month . '%')
            ->whereDate('date', '<=', $todayStr)
            ->orderBy('date', 'desc')
            ->get();

        $sessionIds = $sessions->pluck('id');
        $records = AttendanceRecord::whereIn('session_id', $sessionIds)
            ->where('student_id', $targetStudentId)
            ->get()
            ->keyBy('session_id');

        // Ambil seluruh permohonan surat izin siswa yang relevan dengan bulan ini
        $leaves = LeaveRequest::where('student_id', $targetStudentId)
            ->where(function ($q) use ($month) {
                $q->where('start_date', 'like', $month . '%')
                  ->orWhere('end_date', 'like', $month . '%');
            })
            ->latest('id')
            ->get();

        $detailedList = [];
        $hadir = 0;
        $sakit = 0;
        $izin = 0;
        $alpha = 0;
        $isParent = ($user->role === 'ORANG_TUA');

        foreach ($sessions as $session) {
            $dateStr = Carbon::parse($session->date)->toDateString();
            $rec = $records->get($session->id);

            // Cek apakah ada surat izin untuk tanggal session ini
            $leaveForDate = $leaves->first(function ($l) use ($dateStr) {
                $start = Carbon::parse($l->start_date)->toDateString();
                $end = Carbon::parse($l->end_date)->toDateString();
                return $dateStr >= $start && $dateStr <= $end;
            });

            $status = 'ALPHA';
            $note = '-';

            if ($leaveForDate && $leaveForDate->status === 'APPROVED') {
                $status = ($leaveForDate->type === 'SAKIT') ? 'SAKIT' : 'IZIN';
                $note = $isParent
                    ? ($status === 'SAKIT' ? 'Sakit' : 'Izin')
                    : ('Surat Izin ' . $leaveForDate->type . ' disetujui Wali Kelas: ' . ($leaveForDate->reason ?: '-'));
            } elseif ($leaveForDate && $leaveForDate->status === 'REJECTED') {
                // Izin ditolak! Hanya berstatus HADIR jika diverifikasi langsung di kelas oleh Sekretaris / Wali Kelas
                $verifiedInClass = false;
                if ($rec && $rec->status === 'HADIR' && !str_contains($rec->note ?? '', 'Presensi Mandiri Pukul')) {
                    $verifiedInClass = true;
                }

                if ($verifiedInClass) {
                    $status = 'HADIR';
                    $note = $isParent ? 'Hadir' : ($rec->note ?: 'Diverifikasi Hadir di Kelas oleh Sekretaris Kelas');
                } else {
                    $status = 'DITOLAK';
                    $reason = $leaveForDate->review_note ? ": {$leaveForDate->review_note}" : '';
                    $note = $isParent ? 'Izin Ditolak' : "Surat Izin Ditolak Wali Kelas{$reason} • Wajib Verifikasi di Kelas";
                }
            } elseif ($leaveForDate && $leaveForDate->status === 'PENDING') {
                $status = 'PENDING';
                $note = $isParent ? 'Menunggu Konfirmasi' : 'Surat Izin diajukan, menunggu persetujuan Wali Kelas';
            } elseif ($rec) {
                $status = $rec->status;
                if ($isParent) {
                    if (str_contains($rec->note ?? '', 'TERLAMBAT')) $note = $rec->note;
                    elseif ($status === 'HADIR') $note = 'Hadir';
                    elseif ($status === 'SAKIT') $note = 'Sakit';
                    elseif ($status === 'IZIN') $note = 'Izin';
                    elseif ($status === 'ALPHA') $note = 'Alpa';
                    else $note = $status;
                } else {
                    $note = $rec->note ?: 'Hadir';
                }
            } else {
                if ($session->status === 'DRAFT' && $dateStr === $todayStr) {
                    $status = 'BELUM_PRESENSI';
                    $note = $isParent ? 'Belum Presensi' : 'Belum tercatat presensi hari ini';
                } else {
                    $status = 'ALPHA';
                    $note = $isParent ? 'Alpa' : 'Tidak hadir tanpa keterangan';
                }
            }

            if ($status === 'HADIR') $hadir++;
            elseif ($status === 'SAKIT') $sakit++;
            elseif ($status === 'IZIN') $izin++;
            elseif (in_array($status, ['ALPHA', 'DITOLAK'])) $alpha++;

            // Cari catatan izin keluar KBM dan alfa mapel pada tanggal ini
            $rowJournals = $monthJournals->filter(function ($j) use ($dateStr) {
                return Carbon::parse($j->date)->toDateString() === $dateStr;
            });
            $rowKbmLeaves = [];
            $rowKbmAlfa = [];
            foreach ($rowJournals as $cj) {
                $notes = $cj->notes;
                if ($notes && str_starts_with(trim($notes), '{')) {
                    $dec = json_decode($notes, true);
                    if (is_array($dec) && !empty($dec['student_attendance'])) {
                        foreach ($dec['student_attendance'] as $sa) {
                            if (($sa['id'] ?? null) == $targetStudentId) {
                                $st = strtoupper($sa['status'] ?? '');
                                if ($st === 'IZIN') {
                                    $rowKbmLeaves[] = [
                                        'subject' => $cj->schedule?->subject?->name ?? 'Mata Pelajaran',
                                        'teacher' => $cj->schedule?->teacher?->name ?? 'Guru Pengajar',
                                        'schedule_time' => ($cj->schedule?->start_time ? substr($cj->schedule->start_time, 0, 5) : '07:30') . ' - ' . ($cj->schedule?->end_time ? substr($cj->schedule->end_time, 0, 5) : '09:00'),
                                        'leave_time' => $sa['leave_time'] ?? 'Saat KBM',
                                        'reason' => $sa['note'] ?? 'Izin keluar kelas saat KBM',
                                    ];
                                } elseif (in_array($st, ['ALPHA', 'ALFA'])) {
                                    $rowKbmAlfa[] = [
                                        'subject' => $cj->schedule?->subject?->name ?? 'Mata Pelajaran',
                                        'teacher' => $cj->schedule?->teacher?->name ?? 'Guru Pengajar',
                                        'schedule_time' => ($cj->schedule?->start_time ? substr($cj->schedule->start_time, 0, 5) : '07:30') . ' - ' . ($cj->schedule?->end_time ? substr($cj->schedule->end_time, 0, 5) : '09:00'),
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            $displayStatus = $status;
            if (!empty($rowKbmLeaves)) {
                $displayStatus = 'IZIN_KELUAR';
                $leaveTimes = collect($rowKbmLeaves)->pluck('leave_time')->filter()->implode(', ');
                $firstSub = $rowKbmLeaves[0]['subject'] ?? '';
                $firstReason = $rowKbmLeaves[0]['reason'] ?? '';
                $timeText = $leaveTimes ? "Pukul {$leaveTimes}" : "Saat KBM";
                $kbmNote = "Izin Keluar {$timeText} ({$firstSub}: {$firstReason})";
                $note = ($status === 'HADIR') ? "Hadir di sekolah • {$kbmNote}" : ($note . " • {$kbmNote}");
            }

            $detailedList[] = [
                'id' => $rec ? $rec->id : ('session_' . $session->id),
                'raw_date' => $dateStr,
                'day_date' => Carbon::parse($session->date)->translatedFormat('l, d M Y'),
                'room' => $session->room ?? Setting::get('default_room_name', 'Ruang Kelas'),
                'subject' => 'Pembelajaran Kelas',
                'hours' => $session->school_hours ?? Setting::get('default_school_hours', '06:45 - 15:00'),
                'status' => $displayStatus,
                'original_status' => $status,
                'note' => $note,
                'kbm_leaves' => $rowKbmLeaves,
                'kbm_alfa' => $rowKbmAlfa,
            ];
        }

        $effectiveTotal = $hadir + $sakit + $izin + $alpha;
        $percentage = $effectiveTotal > 0 ? round(($hadir / $effectiveTotal) * 100) : 100;
        $totalDisplay = count($detailedList);

        // Agregasi Statistik KBM Per Jam Pelajaran Bulan Ini (Rekap Sesi Mapel)
        $totalKbmSessions = 0;
        $totalKbmHadir = 0;
        $totalKbmIzin = 0;
        $totalKbmSakit = 0;
        $totalKbmAlfa = 0;

        foreach ($monthJournals as $mj) {
            $dec = null;
            if ($mj->notes && str_starts_with(trim($mj->notes), '{')) {
                $dec = json_decode($mj->notes, true);
                if (isset($dec['notes']) && is_string($dec['notes']) && str_starts_with(trim($dec['notes']), '{')) {
                    $nested = json_decode($dec['notes'], true);
                    if (is_array($nested)) $dec = array_merge($dec, $nested);
                }
            }
            $saList = is_array($dec) ? ($dec['student_attendance'] ?? []) : [];
            $absentList = is_array($dec) ? ($dec['absent_students'] ?? []) : [];

            $foundStatus = null;
            if (!empty($saList)) {
                foreach ($saList as $sa) {
                    if (($sa['id'] ?? null) == $targetStudentId) {
                        $foundStatus = strtoupper($sa['status'] ?? 'HADIR');
                        break;
                    }
                }
            }
            if (!$foundStatus && !empty($absentList)) {
                $stUser = User::find($targetStudentId);
                if ($stUser && in_array($stUser->name, $absentList)) {
                    $foundStatus = 'ALPHA';
                }
            }
            if (!$foundStatus && $mj->verification_status === 'TERVERIFIKASI') {
                $foundStatus = 'HADIR';
            }

            if ($foundStatus) {
                $totalKbmSessions++;
                if ($foundStatus === 'HADIR') $totalKbmHadir++;
                elseif ($foundStatus === 'IZIN') $totalKbmIzin++;
                elseif ($foundStatus === 'SAKIT') $totalKbmSakit++;
                elseif (in_array($foundStatus, ['ALPHA', 'ALFA'])) $totalKbmAlfa++;
            }
        }

        $kbmPercentage = $totalKbmSessions > 0 ? round(($totalKbmHadir / $totalKbmSessions) * 100) : 100;

        // Weekly breakdown computed from actual records
        $weekly = [
            ['week' => 'Minggu 1', 'count' => collect($detailedList)->filter(fn($r) => Carbon::parse($r['raw_date'])->day <= 7 && $r['status'] === 'HADIR')->count()],
            ['week' => 'Minggu 2', 'count' => collect($detailedList)->filter(fn($r) => Carbon::parse($r['raw_date'])->day > 7 && Carbon::parse($r['raw_date'])->day <= 14 && $r['status'] === 'HADIR')->count()],
            ['week' => 'Minggu 3', 'count' => collect($detailedList)->filter(fn($r) => Carbon::parse($r['raw_date'])->day > 14 && Carbon::parse($r['raw_date'])->day <= 21 && $r['status'] === 'HADIR')->count()],
            ['week' => 'Minggu 4', 'count' => collect($detailedList)->filter(fn($r) => Carbon::parse($r['raw_date'])->day > 21 && $r['status'] === 'HADIR')->count()],
        ];

        // Ambil tanggal target KBM (bisa per tanggal / hari yang dipilih oleh Orang Tua/Siswa, default hari ini)
        $kbmDateStr = $request->input('kbm_date') ?: $todayStr;
        $kbmDateCarbon = Carbon::parse($kbmDateStr, 'Asia/Jakarta');
        $kbmDayOfWeekNumber = $kbmDateCarbon->dayOfWeekIso;
        $isKbmWeekend = ($kbmDayOfWeekNumber >= 6);

        $daysMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $currentDayName = $daysMap[$kbmDayOfWeekNumber] ?? 'Senin';
        $kbmSchedules = !$isKbmWeekend ? Schedule::where('class_id', $classId)
            ->where('day', $currentDayName)
            ->with(['subject', 'teacher'])
            ->orderBy('start_time')
            ->get() : collect();

        // Ambil jurnal KBM pada tanggal yang dipilih
        $kbmJournals = ClassJournal::where('class_id', $classId)
            ->whereDate('date', $kbmDateStr)
            ->get();

        // Ambil surat izin yang disetujui Walas untuk siswa pada tanggal KBM yang dipilih
        $studentApprovedLeaves = $targetStudentId ? LeaveRequest::where('student_id', $targetStudentId)
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $kbmDateStr)
            ->whereDate('end_date', '>=', $kbmDateStr)
            ->get() : collect();

        $todayKbmPeriods = [];
        $isTargetToday = ($kbmDateStr === $todayStr);
        $isTargetPast = $kbmDateCarbon->lt(Carbon::today('Asia/Jakarta')->startOfDay());
        $nowStr = $nowJakarta->format('H:i');

        foreach ($kbmSchedules as $index => $sch) {
            $journal = $kbmJournals->firstWhere('schedule_id', $sch->id);
            $startStr = substr($sch->start_time ?? '07:30', 0, 5);
            $endStr = substr($sch->end_time ?? '09:00', 0, 5);

            $stStatus = null;
            $leaveTime = null;
            $leaveReason = null;

            if ($journal && $journal->notes && str_starts_with(trim($journal->notes), '{')) {
                $dec = json_decode($journal->notes, true);
                if (is_array($dec) && !empty($dec['student_attendance'])) {
                    foreach ($dec['student_attendance'] as $sa) {
                        $sId = $sa['id'] ?? ($sa['student_id'] ?? null);
                        if ($sId == $targetStudentId) {
                            $stStatus = strtoupper($sa['status'] ?? 'HADIR');
                            $leaveTime = $sa['leave_time'] ?? null;
                            $leaveReason = $sa['note'] ?? ($sa['reason'] ?? null);
                            break;
                        }
                    }
                }
                if (!$stStatus && !empty($dec['absent_students'])) {
                    $stUser = User::find($targetStudentId);
                    if ($stUser && in_array($stUser->name, $dec['absent_students'])) {
                        $stStatus = 'ALPHA';
                    } elseif ($journal->verification_status === 'TERVERIFIKASI') {
                        $stStatus = 'HADIR';
                    }
                }
            }

            // Jika belum ada presensi eksplisit di jurnal mapel, periksa apakah siswa memegang Surat Izin resmi Walas
            if (!$stStatus && $studentApprovedLeaves->isNotEmpty()) {
                foreach ($studentApprovedLeaves as $lv) {
                    $isHourly = str_contains($lv->reason ?? '', '[Dispensasi Jam KBM') || str_contains($lv->reason ?? '', '[Dispensasi Jam Tertentu]');
                    if (!$isHourly) {
                        // Izin Seharian Penuh (SAKIT / IZIN / DISPENSASI)
                        $stStatus = ($lv->type === 'SAKIT') ? 'SAKIT' : 'IZIN';
                        $leaveReason = $lv->reason;
                        break;
                    } else {
                        // Izin Jam Pelajaran Tertentu (Dispensasi KBM): periksa kecocokan nama mapel atau rentang waktu
                        $subName = strtolower($sch->subject->name ?? '');
                        $reasonLower = strtolower($lv->reason ?? '');
                        $matchSub = $subName && str_contains($reasonLower, $subName);
                        $matchTime = false;
                        if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $lv->reason ?? '', $m)) {
                            $leaveStart = $m[1];
                            $leaveEnd = $m[2];
                            if ($startStr < $leaveEnd && $endStr > $leaveStart) {
                                $matchTime = true;
                            }
                        }
                        if ($matchSub || $matchTime) {
                            $stStatus = 'IZIN';
                            $leaveReason = $lv->reason;
                            $leaveTime = $sch->start_time ?? $startStr;
                            break;
                        }
                    }
                }
            }

            if (!$stStatus) {
                if ($journal && $journal->verification_status === 'TERVERIFIKASI') {
                    $stStatus = 'HADIR';
                } elseif ($isTargetPast || ($isTargetToday && $nowStr > $endStr)) {
                    $stStatus = 'JAM_KOSONG';
                } else {
                    $stStatus = 'BELUM_KBM';
                }
            }

            $todayKbmPeriods[] = [
                'jam_ke' => 'Jam Ke-' . ($index + 1),
                'time' => "{$startStr} - {$endStr}",
                'subject' => $sch->subject->name ?? 'Mata Pelajaran',
                'teacher' => $sch->teacher->name ?? 'Guru Pengajar',
                'room' => $sch->room ?: 'Ruang Kelas',
                'student_status' => $stStatus,
                'leave_time' => $leaveTime,
                'reason' => $leaveReason,
                'journal_filled' => !!$journal,
            ];
        }

        return response()->json([
            'summary' => [
                'total' => $totalDisplay,
                'hadir' => $hadir,
                'hadir_percent' => $percentage,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpha' => $alpha,
            ],
            'kbm_summary' => [
                'total_sessions' => $totalKbmSessions,
                'hadir' => $totalKbmHadir,
                'hadir_percent' => $kbmPercentage,
                'izin_keluar' => $totalKbmIzin,
                'sakit' => $totalKbmSakit,
                'alpha' => $totalKbmAlfa,
            ],
            'donut' => [
                ['label' => 'Hadir', 'count' => $hadir, 'color' => '#16A34A', 'percent' => $effectiveTotal > 0 ? round(($hadir / $effectiveTotal) * 100) : 100],
                ['label' => 'Sakit', 'count' => $sakit, 'color' => '#EA580C', 'percent' => $effectiveTotal > 0 ? round(($sakit / $effectiveTotal) * 100) : 0],
                ['label' => 'Izin', 'count' => $izin, 'color' => '#2563EB', 'percent' => $effectiveTotal > 0 ? round(($izin / $effectiveTotal) * 100) : 0],
                ['label' => 'ALFA', 'count' => $alpha, 'color' => '#DC2626', 'percent' => $effectiveTotal > 0 ? round(($alpha / $effectiveTotal) * 100) : 0],
            ],
            'weekly' => $weekly,
            'details' => $detailedList,
            'today_status' => $todayStatus,
            'is_past_cutoff' => $isPastCutoffToday,
            'cutoff_time' => $cutoffTimeStr,
            'today_kbm_leaves' => $todayKbmLeaves,
            'today_kbm_alfa' => $todayKbmAlfa,
            'kbm_leave_logs' => $kbmLeaveLogs,
            'today_kbm_periods' => $todayKbmPeriods,
            'selected_kbm_date' => $kbmDateStr,
            'selected_kbm_date_formatted' => $kbmDateCarbon->translatedFormat('l, d F Y'),
            'is_kbm_weekend' => $isKbmWeekend,
            'server_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_day' => Carbon::now('Asia/Jakarta')->translatedFormat('l'),
            'server_time' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
        ])
        ->header('X-Server-Date', Carbon::today('Asia/Jakarta')->toDateString())
        ->header('X-Server-Day', Carbon::now('Asia/Jakarta')->translatedFormat('l'))
        ->header('X-Server-Time', Carbon::now('Asia/Jakarta')->toDateTimeString())
        ->header('Access-Control-Expose-Headers', 'X-Server-Date, X-Server-Day, X-Server-Time');
    }

    public function semesterRecap(Request $request)
    {
        $user = $request->user();
        if ($user && in_array($user->role, ['SEKRETARIS', 'SISWA'])) {
            $classId = $user->class_id;
        } elseif ($user && $user->role === 'ORANG_TUA') {
            $child = $request->student_id ? User::find($request->student_id) : ($user->student_id ? User::find($user->student_id) : User::where('nip_nis', $user->nip_nis)->first());
            $classId = $child?->class_id ?? $user->class_id;
        } elseif ($user && $user->role === 'GURU_PEMBIMBING') {
            $walasClassId = \App\Models\SchoolClass::where('advisor_id', $user->id)->value('id') ?? $user->class_id;
            // Wali Kelas strictly locked to their own guided class (tidak ada pilihan kelas lain)
            $classId = $walasClassId ?: $user->class_id;
        } elseif ($user && $user->role === 'GURU_PENGAJAR') {
            // Guru Pengajar: HANYA berhak mengakses kelas-kelas yang ada dalam jadwal mengajarnya
            $scheduleClassIds = \App\Models\Schedule::where('teacher_id', $user->id)->pluck('class_id')->filter()->unique();
            $taughtClassIds = $user->taughtClasses()->pluck('classes.id');
            $allTaughtIds = $scheduleClassIds->concat($taughtClassIds)->filter()->unique()->values()->toArray();

            if (!empty($allTaughtIds)) {
                $requestedClassId = (int) $request->input('class_id', $allTaughtIds[0]);
                if (!in_array($requestedClassId, $allTaughtIds)) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki hak akses rekapitulasi kelas ini karena bukan kelas yang Anda ampu.'
                    ], 403);
                }
                $classId = $requestedClassId;
            } else {
                $classId = null;
            }
        } else {
            if ($user && $user->role === 'SUPERADMIN' && !$request->filled('class_id')) {
                return response()->json(['message' => 'Silakan pilih kelas terlebih dahulu.'], 422);
            }
            $classId = $request->input('class_id', $user->class_id ?? SchoolClass::value('id'));
        }

        if (!$classId) {
            return response()->json([
                'effective_days' => 0,
                'average_attendance' => '0%',
                'summary' => ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0],
                'target_note' => 'Belum ada data kelas yang ditentukan.',
                'students' => [],
            ]);
        }

        $students = User::where('class_id', $classId)
            ->whereIn('role', ['SISWA', 'SEKRETARIS'])
            ->orderBy('name')
            ->get();

        $term = $request->input('term', '2026-1');
        $mode = $request->input('mode', $request->input('type', 'daily'));
        $subjectId = $request->input('subject_id');

        $termModel = \App\Models\Term::where('name', 'like', "%{$term}%")->orWhere('id', $term)->first();

        // Ambil daftar mata pelajaran kelas ini untuk filter di frontend
        $scheduleQuery = \App\Models\Schedule::where('class_id', $classId);
        if ($user && $user->role === 'GURU_PENGAJAR') {
            $scheduleQuery->where('teacher_id', $user->id);
        }
        $availableSubjects = $scheduleQuery->with('subject')
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();

        // Auto-detect mapel bagi Guru Pengajar (kunci ke mapel yang diampu)
        if ($user && $user->role === 'GURU_PENGAJAR') {
            if (empty($subjectId) && $availableSubjects->isNotEmpty()) {
                $subjectId = $availableSubjects->first()->id;
            } elseif ($subjectId && !$availableSubjects->contains('id', (int)$subjectId) && $availableSubjects->isNotEmpty()) {
                $subjectId = $availableSubjects->first()->id;
            }
        }

        // MODE 1: REKAP PRESENSI KBM MATA PELAJARAN (Dari Jurnal KBM Guru)
        if ($mode === 'kbm' || $mode === 'subject' || $request->filled('subject_id')) {
            $journalQuery = \App\Models\ClassJournal::where('class_id', $classId)
                ->whereNotNull('schedule_id');

            if ($termModel) {
                $journalQuery->where('date', '>=', $termModel->start_date)->where('date', '<=', $termModel->end_date);
            } elseif ($term === '2026-1') {
                $journalQuery->where('date', '>=', '2026-07-01')->where('date', '<=', '2026-12-31');
            } elseif ($term === '2025-2') {
                $journalQuery->where('date', '>=', '2026-01-01')->where('date', '<=', '2026-06-30');
            } elseif ($term === '2025-1') {
                $journalQuery->where('date', '>=', '2025-07-01')->where('date', '<=', '2025-12-31');
            }

            if ($subjectId) {
                $journalQuery->whereHas('schedule', function ($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                });
            } elseif ($user && $user->role === 'GURU_PENGAJAR') {
                $teacherScheduleIds = \App\Models\Schedule::where('teacher_id', $user->id)
                    ->where('class_id', $classId)
                    ->pluck('id');
                if ($teacherScheduleIds->isNotEmpty()) {
                    $journalQuery->where(function ($q) use ($teacherScheduleIds, $user) {
                        $q->whereIn('schedule_id', $teacherScheduleIds)
                          ->orWhere('confirmed_by_id', $user->id)
                          ->orWhere('created_by_id', $user->id);
                    });
                }
            }

            $journals = $journalQuery->with(['schedule.subject'])->orderBy('date')->get();
            $effectiveDays = $journals->count();

            $statsByStudent = [];
            foreach ($students as $std) {
                $statsByStudent[$std->id] = [
                    'HADIR' => 0,
                    'IZIN' => 0,
                    'SAKIT' => 0,
                    'ALPHA' => 0,
                    'leave_details' => [],
                ];
            }

            foreach ($journals as $j) {
                $notes = $j->notes;
                $dec = null;
                if ($notes && str_starts_with(trim($notes), '{')) {
                    $dec = json_decode($notes, true);
                    if (isset($dec['notes']) && is_string($dec['notes']) && str_starts_with(trim($dec['notes']), '{')) {
                        $nested = json_decode($dec['notes'], true);
                        if (is_array($nested)) {
                            $dec = array_merge($dec, $nested);
                        }
                    }
                }

                $saList = is_array($dec) ? ($dec['student_attendance'] ?? []) : [];
                $absentList = is_array($dec) ? ($dec['absent_students'] ?? []) : [];
                $recordedStudentIds = [];

                if (!empty($saList)) {
                    foreach ($saList as $sa) {
                        $sId = $sa['id'] ?? ($sa['student_id'] ?? null);
                        $st = strtoupper($sa['status'] ?? 'HADIR');
                        if ($sId && isset($statsByStudent[$sId])) {
                            if (!in_array($st, ['HADIR', 'IZIN', 'SAKIT', 'ALPHA'])) {
                                $st = 'HADIR';
                            }
                            $statsByStudent[$sId][$st]++;
                            $recordedStudentIds[] = $sId;
                            if ($st === 'IZIN') {
                                $carbonDate = Carbon::parse($j->date)->locale('id');
                                $dayName = $carbonDate->isoFormat('dddd');
                                $fullDate = $carbonDate->isoFormat('D MMMM Y');
                                $leaveTimeRaw = !empty($sa['leave_time']) ? $sa['leave_time'] : null;
                                $leaveTimeFormatted = $leaveTimeRaw ? "Pukul {$leaveTimeRaw} WIB" : "Saat jam pembelajaran KBM";
                                $subjName = $j->schedule?->subject?->name ?? 'Mata Pelajaran';
                                $topicName = !empty($j->topic) ? $j->topic : (!empty($j->activity) ? explode(' | ', $j->activity)[0] : 'Sesi KBM');
                                $leaveReason = !empty($sa['note']) ? $sa['note'] : (!empty($sa['reason']) ? $sa['reason'] : 'Izin keluar kelas saat KBM');

                                $humanized = "Pada hari {$dayName}, {$fullDate}, siswa izin keluar kelas {$leaveTimeFormatted} pada mata pelajaran {$subjName} (Topik: {$topicName}) dengan keterangan: \"{$leaveReason}\".";

                                $statsByStudent[$sId]['leave_details'][] = [
                                    'date' => $fullDate,
                                    'day' => $dayName,
                                    'full_date' => "{$dayName}, {$fullDate}",
                                    'raw_date' => $j->date,
                                    'subject' => $subjName,
                                    'topic' => $topicName,
                                    'leave_time' => $leaveTimeRaw ?: 'Saat KBM',
                                    'leave_time_formatted' => $leaveTimeFormatted,
                                    'note' => $leaveReason,
                                    'humanized' => $humanized,
                                ];
                            }
                        }
                    }
                }

                // Default cerdas bagi siswa yang tidak tercantum eksplisit di saList:
                // Jika terdaftar di absent_students -> ALPHA
                // Jika jurnal KBM terverifikasi dan siswa tidak absen -> HADIR otomatis
                foreach ($students as $std) {
                    if (!in_array($std->id, $recordedStudentIds)) {
                        $isAbsentName = false;
                        foreach ($absentList as $ab) {
                            $abName = is_array($ab) ? ($ab['name'] ?? '') : (string)$ab;
                            if (strtolower(trim($abName)) === strtolower(trim($std->name))) {
                                $isAbsentName = true;
                                break;
                            }
                        }
                        if ($isAbsentName) {
                            $statsByStudent[$std->id]['ALPHA']++;
                        } elseif ($j->verification_status === 'TERVERIFIKASI') {
                            $statsByStudent[$std->id]['HADIR']++;
                        }
                    }
                }
            }

            $totalHadirAll = 0;
            $totalSakitAll = 0;
            $totalIzinAll = 0;
            $totalAlphaAll = 0;

            $rows = $students->map(function ($std) use (&$statsByStudent, &$totalHadirAll, &$totalSakitAll, &$totalIzinAll, &$totalAlphaAll) {
                $st = $statsByStudent[$std->id] ?? ['HADIR' => 0, 'IZIN' => 0, 'SAKIT' => 0, 'ALPHA' => 0, 'leave_details' => []];
                $hadir = (int) $st['HADIR'];
                $sakit = (int) $st['SAKIT'];
                $izin = (int) $st['IZIN'];
                $alpha = (int) $st['ALPHA'];

                $totalHadirAll += $hadir;
                $totalSakitAll += $sakit;
                $totalIzinAll += $izin;
                $totalAlphaAll += $alpha;

                $totalRecorded = $hadir + $sakit + $izin + $alpha;
                $pct = $totalRecorded > 0 ? round(($hadir / $totalRecorded) * 100, 1) : 0;

                return [
                    'id' => $std->id,
                    'name' => $std->name,
                    'nis' => $std->nip_nis ?? '-',
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'alpha' => $alpha,
                    'percentage' => $pct . '%',
                    'leave_details' => $st['leave_details'] ?? [],
                ];
            });

            $totalRecordedAll = $totalHadirAll + $totalSakitAll + $totalIzinAll + $totalAlphaAll;
            $avgAttendancePct = $totalRecordedAll > 0 ? round(($totalHadirAll / $totalRecordedAll) * 100, 1) : 0;

            $subjectName = $subjectId ? ($availableSubjects->firstWhere('id', $subjectId)?->name ?? 'Mata Pelajaran') : 'Semua Mata Pelajaran';

            $teacherClasses = collect();
            if ($user && $user->role === 'GURU_PENGAJAR') {
                $teacherClasses = \App\Models\Schedule::where('teacher_id', $user->id)
                    ->with('schoolClass')
                    ->get()
                    ->pluck('schoolClass')
                    ->filter()
                    ->unique('id')
                    ->values();
            }

            return response()->json([
                'mode' => 'kbm',
                'class_id' => (int) $classId,
                'teacher_classes' => $teacherClasses,
                'effective_days' => $effectiveDays,
                'average_attendance' => min(100, $avgAttendancePct) . '%',
                'summary' => [
                    'hadir' => $totalHadirAll,
                    'sakit' => $totalSakitAll,
                    'izin' => $totalIzinAll,
                    'alpha' => $totalAlphaAll,
                ],
                'target_note' => "Rekapitulasi KBM Mapel: {$totalHadirAll} Hadir, {$totalSakitAll} Sakit, {$totalIzinAll} Izin Keluar, {$totalAlphaAll} Alfa ({$effectiveDays} Sesi)",
                'students' => $rows,
                'subjects' => $availableSubjects,
                'selected_subject' => $subjectName,
                'selected_subject_id' => $subjectId,
            ]);
        }

        // MODE 2: REKAP PRESENSI HARIAN KELAS (Dari Sesi Presensi Harian Walas)
        $sessionQuery = AttendanceSession::where('class_id', $classId);

        if ($termModel) {
            $sessionQuery->where('date', '>=', $termModel->start_date)->where('date', '<=', $termModel->end_date);
        } elseif ($term === '2026-1') {
            $sessionQuery->where('date', '>=', '2026-07-01')->where('date', '<=', '2026-12-31');
        } elseif ($term === '2025-2') {
            $sessionQuery->where('date', '>=', '2026-01-01')->where('date', '<=', '2026-06-30');
        } elseif ($term === '2025-1') {
            $sessionQuery->where('date', '>=', '2025-07-01')->where('date', '<=', '2025-12-31');
        }

        $sessionIds = $sessionQuery->pluck('id');

        $effectiveDays = $sessionIds->count();

        $totalHadirAll = 0;
        $totalSakitAll = 0;
        $totalIzinAll = 0;
        $totalAlphaAll = 0;

        // Optimasi: 1 query agregasi untuk seluruh siswa sekaligus (Cegah N+1 Query)
        $statsByStudent = $effectiveDays > 0 ? AttendanceRecord::whereIn('session_id', $sessionIds)
            ->whereIn('student_id', $students->pluck('id'))
            ->selectRaw('student_id, status, count(*) as total')
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id') : collect();

        $rows = $students->map(function ($std) use ($statsByStudent, &$totalHadirAll, &$totalSakitAll, &$totalIzinAll, &$totalAlphaAll) {
            $studentStats = $statsByStudent->get($std->id, collect())->pluck('total', 'status');
            $hadir = (int) ($studentStats['HADIR'] ?? 0);
            $sakit = (int) ($studentStats['SAKIT'] ?? 0);
            $izin = (int) ($studentStats['IZIN'] ?? 0);
            $alpha = (int) ($studentStats['ALPHA'] ?? 0);

            $totalHadirAll += $hadir;
            $totalSakitAll += $sakit;
            $totalIzinAll += $izin;
            $totalAlphaAll += $alpha;

            $totalRecorded = $hadir + $sakit + $izin + $alpha;
            $pct = $totalRecorded > 0 ? round(($hadir / $totalRecorded) * 100, 1) : 0;

            return [
                'id' => $std->id,
                'name' => $std->name,
                'nis' => $std->nip_nis ?? '-',
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'percentage' => $pct . '%',
            ];
        });

        $totalRecordedAll = $totalHadirAll + $totalSakitAll + $totalIzinAll + $totalAlphaAll;
        $avgAttendancePct = $totalRecordedAll > 0 ? round(($totalHadirAll / $totalRecordedAll) * 100, 1) : 0;

        $targetNote = "Rekapitulasi kumulatif: {$totalHadirAll} Hadir, {$totalSakitAll} Sakit, {$totalIzinAll} Izin, {$totalAlphaAll} Alfa";

        return response()->json([
            'mode' => 'daily',
            'effective_days' => $effectiveDays,
            'average_attendance' => min(100, $avgAttendancePct) . '%',
            'summary' => [
                'hadir' => $totalHadirAll,
                'sakit' => $totalSakitAll,
                'izin' => $totalIzinAll,
                'alpha' => $totalAlphaAll,
            ],
            'target_note' => $targetNote,
            'students' => $rows,
            'subjects' => $availableSubjects,
        ]);
    }
}
