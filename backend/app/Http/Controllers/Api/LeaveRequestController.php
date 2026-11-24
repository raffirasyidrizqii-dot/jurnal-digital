<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status');
        $classId = $request->input('class_id');

        $query = LeaveRequest::with(['student.schoolClass', 'submittedBy', 'reviewer'])->latest();

        // Jika login sebagai SISWA atau SEKRETARIS, tampilkan surat izin milik siswa itu sendiri (Alur Mandiri Siswa)
        if ($user && in_array($user->role, ['SISWA', 'SEKRETARIS'])) {
            $query->where('student_id', $user->id);
        } elseif ($user && $user->role === 'ORANG_TUA') {
            $child = ($request->filled('student_id') ? User::find($request->input('student_id')) : null)
                ?? ($user->student_id ? User::find($user->student_id) : null)
                ?? User::where('nip_nis', $user->nip_nis)->whereIn('role', ['SISWA', 'SEKRETARIS'])->first();
            if ($child) {
                $query->where('student_id', $child->id);
            } else {
                $query->where('id', 0);
            }
        } elseif ($user && $user->role === 'GURU_PEMBIMBING') {
            $walasClassId = \App\Models\SchoolClass::where('advisor_id', $user->id)->value('id') ?? $user->class_id;
            if ($walasClassId) {
                $query->whereHas('student', function ($q) use ($walasClassId) {
                    $q->where('class_id', $walasClassId);
                });
            } else {
                $query->where('id', 0);
            }
        } elseif ($classId && $user && $user->role === 'SUPERADMIN') {
            $query->whereHas('student', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        if ($status && $status !== 'Semua') {
            $query->where('status', $status);
        }

        $list = $query->get()->map(function ($lv, $idx) {
            return [
                'no' => $idx + 1,
                'id' => $lv->id,
                'date_formatted' => Carbon::parse($lv->start_date)->translatedFormat('l, d M Y'),
                'student_name' => $lv->student->name ?? 'Siswa',
                'nis' => $lv->student->nip_nis ?? '-',
                'class' => $lv->student->schoolClass->name ?? 'Kelas Industri RPL',
                'type' => $lv->type,
                'start_date' => $lv->start_date->toDateString(),
                'end_date' => $lv->end_date->toDateString(),
                'recipient' => $lv->recipient ?: 'Wali Kelas',
                'reason' => $lv->reason,
                'status' => $lv->status,
                'attachment' => $lv->attachment_path ? basename($lv->attachment_path) : null,
                'attachment_url' => $lv->attachment_path ? url('api/leave-requests/file/' . basename($lv->attachment_path)) : null,
                'review_note' => $lv->review_note,
                'reviewer_name' => $lv->reviewer->name ?? null,
            ];
        });

        return response()->json($list);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'student_id' => 'nullable',
            'type' => 'required|in:SAKIT,IZIN',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'required|string',
            'signature' => 'nullable|string',
            'file' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png',
        ]);

        if ($user->role === 'ORANG_TUA') {
            $studentId = $request->student_id ?: ($user->student_id ?: User::where('nip_nis', $user->nip_nis)->whereIn('role', ['SISWA', 'SEKRETARIS'])->value('id'));
        } elseif (in_array($user->role, ['SISWA', 'SEKRETARIS'])) {
            $studentId = $user->id;
        } else {
            $studentId = $request->student_id ?: $user->id;
        }

        // LOGIKA PENGUNCIAN FORM SURAT IZIN (ANTI-KECURANGAN)
        $startDateStr = Carbon::parse($request->start_date)->toDateString();
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();
        $nowJakarta = Carbon::now('Asia/Jakarta');
        $isHourlyLeave = ($request->input('leave_scope') === 'HOURLY');
        if ($isHourlyLeave) {
            $request->merge(['end_date' => $request->start_date]);
        }

        // Pengecekan hanya jika pengajuan untuk hari ini
        if ($startDateStr === $todayStr) {
            // Jika izin sehari penuh (FULL_DAY)
            if (!$isHourlyLeave) {
                // Kondisi 1: Siswa Sudah Tercatat HADIR di Kelas Hari Ini
                $isHadirToday = AttendanceRecord::where('student_id', $studentId)
                    ->where('status', 'HADIR')
                    ->where(function ($q) use ($todayStr) {
                        $q->whereHas('session', function ($sq) use ($todayStr) {
                            $sq->whereDate('date', $todayStr);
                        })->orWhereDate('created_at', $todayStr);
                    })->exists();

                if ($isHadirToday) {
                    return response()->json([
                        'message' => 'Status kehadiran siswa hari ini telah tercatat HADIR di sekolah. Pengajuan surat izin harian tidak dapat dilakukan. Jika ada keperluan keluar kelas atau pulang awal, silakan ajukan opsi "Izin Jam Pelajaran (Dispensasi KBM)" atau lapor langsung ke Guru Pengajar / Wali Kelas.',
                        'error_code' => 'ALREADY_HADIR_TODAY',
                    ], 422);
                }

                // Kondisi 2: Siswa Belum Hadir & Waktu Sudah Melewati Batas Jam Masuk (Lewat 07:15 WIB)
                // HANYA BERLAKU UNTUK SISWA DAN SEKRETARIS UNTUK IZIN SEHARI PENUH (mencegah siswa izin sendiri setelah jam sekolah berjalan)
                // Akun ORANG_TUA TIDAK DIBLOKIR oleh cutoff time 07:15 agar orang tua bisa mengirim surat dokter kapan saja
                if (in_array($user->role, ['SISWA', 'SEKRETARIS'])) {
                    $schoolHours = Setting::get('default_school_hours', '06:45 - 15:00');
                    $hoursParts = explode('-', $schoolHours);
                    $startTime = trim($hoursParts[0] ?? '06:45');
                    $toleranceMinutes = (int) Setting::get('attendance_late_tolerance_minutes', '30');
                    $cutoffCarbon = Carbon::createFromTimeString($startTime, 'Asia/Jakarta')->addMinutes($toleranceMinutes);
                    $cutoffTimeStr = $cutoffCarbon->format('H:i');

                    if ($nowJakarta->greaterThan($cutoffCarbon)) {
                        return response()->json([
                            'message' => "Batas waktu pengajuan izin mandiri hari ini telah berakhir (pukul {$cutoffTimeStr} WIB). Status Anda telah tercatat ALFA. Untuk izin mendadak, Orang Tua wajib menghubungi Wali Kelas secara langsung.",
                            'error_code' => 'PAST_CUTOFF_TODAY',
                        ], 422);
                    }
                }
            }
        }

        // Validasi: Cegah permohonan ganda jika sudah ada surat izin yang DISETUJUI atau PENDING yang tumpang tindih
        $overlapping = LeaveRequest::where('student_id', $studentId)
            ->whereIn('status', ['APPROVED', 'PENDING'])
            ->where(function ($q) use ($request) {
                $start = $request->start_date;
                $end = $request->end_date;
                $q->where(function ($sub) use ($start, $end) {
                    $sub->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end]);
                })->orWhere(function ($sub) use ($start, $end) {
                    $sub->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                });
            })
            ->first();

        if ($overlapping) {
            $isOverlappingHourly = str_contains($overlapping->reason ?? '', '[Dispensasi Jam');
            // Jika salah satunya atau keduanya adalah izin sehari penuh, blokir duplikasi pada tanggal yang sama.
            // Namun jika keduanya izin per jam pelajaran (HOURLY), siswa diperbolehkan izin mapel yang berbeda.
            if (!$isHourlyLeave || !$isOverlappingHourly) {
                $isApproved = ($overlapping->status === 'APPROVED');
                $statusText = $isApproved ? 'TELAH DISETUJUI' : 'MENUNGGU KONFIRMASI (PENDING)';
                return response()->json([
                    'message' => 'Anda sudah memiliki permohonan surat izin yang ' . $statusText . ' untuk tanggal ' . Carbon::parse($overlapping->start_date)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($overlapping->end_date)->translatedFormat('d M Y') . '. Tidak dapat mengirimkan surat izin ganda pada rentang waktu yang sama.',
                ], 422);
            }
        }

        $path = null;
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $ext = strtolower($uploaded->guessExtension() ?: $uploaded->getClientOriginalExtension());
            $safeName = Str::uuid()->toString() . '.' . $ext;
            $path = $uploaded->storeAs('leave_attachments', $safeName, 'public');
        }

        $reasonText = $request->reason;
        if ($request->input('leave_scope') === 'HOURLY') {
            $hourlyInfo = [];
            if ($request->filled('time_range')) {
                $hourlyInfo[] = 'Pukul ' . $request->input('time_range');
            }
            if ($request->filled('subject_name')) {
                $hourlyInfo[] = 'Mapel ' . $request->input('subject_name');
            }
            $prefix = !empty($hourlyInfo) ? ('[Dispensasi Jam KBM: ' . implode(' - ', $hourlyInfo) . '] ') : '[Dispensasi Jam Tertentu] ';
            $reasonText = $prefix . $reasonText;
        }

        $leave = LeaveRequest::create([
            'student_id' => $studentId,
            'submitted_by_id' => $user->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'recipient' => 'Wali Kelas',
            'reason' => $reasonText,
            'attachment_path' => $path,
            'signature' => $request->signature,
            'status' => 'PENDING',
        ]);

        $student = User::find($studentId) ?? $leave->student;
        $studentName = $student->name ?? $user->name;

        // IZIN JAM PELAJARAN (HOURLY): otomatis APPROVED - ini pemberitahuan, bukan permohonan.
        // Alur fisik (lapor guru mapel, ke UKS, dll) sudah terjadi di dunia nyata; sistem
        // hanya mencatat + memberi tahu guru yang sesinya ditinggal. Walas memantau via
        // rekap per mapel yang sudah ada, jadi tidak diberi notif untuk jalur ini.
        if ($isHourlyLeave) {
            $leave->update([
                'status' => 'APPROVED',
                'review_note' => 'Otomatis disetujui (izin jam pelajaran)',
                'reviewed_at' => Carbon::now(),
            ]);

            $className = $student?->schoolClass?->name ?? 'Kelas';
            $dayNameMap = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
            $dayName = $dayNameMap[Carbon::parse($leave->start_date)->format('l')] ?? null;
            $rangeParts = array_map('trim', explode('-', $request->input('time_range', '')));
            $rangeStart = $rangeParts[0] ?? null;
            $rangeEnd = $rangeParts[1] ?? null;
            $notifiedTeachers = [];

            if ($dayName && $rangeStart && $rangeEnd && $student?->class_id) {
                $schedules = \App\Models\Schedule::with(['subject', 'teacher'])
                    ->where('class_id', $student->class_id)
                    ->where('day', $dayName)
                    ->where('start_time', '<', $rangeEnd)
                    ->where('end_time', '>', $rangeStart)
                    ->get();

                $perTeacher = [];
                foreach ($schedules as $sch) {
                    if (!$sch->teacher_id) continue;
                    $perTeacher[$sch->teacher_id]['name'] = $sch->teacher?->name ?? 'Guru';
                    $perTeacher[$sch->teacher_id]['mapel'][] = $sch->subject?->name ?? 'Mapel';
                }

                foreach ($perTeacher as $tid => $info) {
                    Notification::create([
                        'user_id' => $tid,
                        'type' => 'SURAT_IZIN',
                        'title' => 'Siswa Izin Keluar Jam Pelajaran Anda',
                        'body' => 'FYI: ' . $studentName . ' (' . $className . ') tidak mengikuti jam pelajaran Anda pukul ' . $rangeStart . ' - ' . $rangeEnd . ' (' . implode(', ', array_unique($info['mapel'])) . ') tanggal ' . Carbon::parse($leave->start_date)->translatedFormat('d M Y') . '. Tercatat otomatis (dispensasi).',
                    ]);
                    $notifiedTeachers[] = $info['name'];
                }
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'Izin jam pelajaran (dispensasi) ' . $request->type . ' otomatis disetujui (' . $studentName . ')' . (!empty($notifiedTeachers) ? ' - notif ke: ' . implode(', ', $notifiedTeachers) : ' - tanpa guru jadwal ter-match'),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Izin jam pelajaran otomatis disetujui. Guru mapel yang sesinya ditinggal telah diberi tahu.',
                'leave' => $leave->fresh(),
            ]);
        }

        // IZIN SEHARI PENUH (FULL_DAY): jalur lama - PENDING, menunggu persetujuan Wali Kelas
        $class = $student ? $student->schoolClass : null;
        if ($class && $class->advisor_id) {
            Notification::create([
                'user_id' => $class->advisor_id,
                'type' => 'SURAT_IZIN',
                'title' => 'Permohonan Surat Izin Siswa Baru',
                'body' => 'Siswa ' . ($student->name ?? $user->name) . ' mengajukan surat izin ' . strtolower($request->type) . ' langsung kepada Wali Kelas.',
            ]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Mengajukan permohonan surat izin ' . $request->type . ' mandiri (' . ($student->name ?? $user->name) . ') ke Wali Kelas',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Permohonan surat izin berhasil dikirimkan langsung ke Wali Kelas dan menunggu persetujuan',
            'leave' => $leave,
        ]);
    }

    public function review(Request $request, $id)
    {
        $user = $request->user();
        $isWalas = ($user->role === 'GURU_PEMBIMBING')
            || \App\Models\SchoolClass::where('advisor_id', $user->id)->exists();
        if (!$isWalas && $user->role !== 'SUPERADMIN') {
            return response()->json(['message' => 'Hanya Wali Kelas atau Superadmin yang berhak memverifikasi surat izin.'], 403);
        }
        $request->validate([
            'status' => 'required|in:APPROVED,REJECTED',
            'review_note' => 'nullable|string',
        ]);

        $leave = LeaveRequest::with('student')->findOrFail($id);

        if ($user->role !== 'SUPERADMIN') {
            $walasClassId = \App\Models\SchoolClass::where('advisor_id', $user->id)->value('id') ?? $user->class_id;
            if (!$walasClassId || (int)$leave->student?->class_id !== (int)$walasClassId) {
                return response()->json(['message' => 'Wali Kelas hanya berhak memverifikasi surat izin siswa di kelas binaan sendiri.'], 403);
            }
        }

        $leave->update([
            'status' => $request->status,
            'reviewer_id' => $user->id,
            'review_note' => $request->review_note,
            'reviewed_at' => Carbon::now(),
        ]);

        // If APPROVED: automatically mark student attendance as SAKIT or IZIN in attendance_records
        if ($request->status === 'APPROVED') {
            $isHourly = str_contains($leave->reason ?? '', '[Dispensasi Jam KBM') || str_contains($leave->reason ?? '', '[Dispensasi Jam Tertentu]');
            $statusToRecord = ($leave->type === 'SAKIT') ? 'SAKIT' : 'IZIN';

            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if ($date->isWeekend()) continue;

                $classId = $leave->student?->class_id;
                if (!$classId) continue;
                $dateStr = $date->toDateString();
                $session = AttendanceSession::where('class_id', $classId)
                    ->whereDate('date', $dateStr)
                    ->first();

                if (!$session) {
                    $session = AttendanceSession::create([
                        'class_id' => $classId,
                        'date' => $dateStr,
                        'status' => 'DRAFT',
                        'verification_status' => 'BELUM_KONFIRMASI',
                        'school_hours' => Setting::get('default_school_hours', '06:45 - 15:00'),
                        'room' => Setting::get('default_room_name', 'Ruang Kelas'),
                    ]);
                }

                $existingRecord = AttendanceRecord::where('session_id', $session->id)
                    ->where('student_id', $leave->student_id)
                    ->first();

                // Jika izin jam pelajaran (Dispensasi KBM) dan siswa sudah HADIR di sekolah harian:
                // JANGAN timpa status hariannya menjadi IZIN! Tetap HADIR, tapi tambahkan catatan izin keluar KBM.
                if ($isHourly && $existingRecord && $existingRecord->status === 'HADIR') {
                    $existingRecord->update([
                        'note' => ($existingRecord->note ? $existingRecord->note . ' | ' : '') . 'Dispensasi Jam KBM disetujui Walas: ' . $leave->reason,
                    ]);
                } else {
                    AttendanceRecord::updateOrCreate(
                        ['session_id' => $session->id, 'student_id' => $leave->student_id],
                        ['status' => $statusToRecord, 'note' => 'Surat Izin ' . $leave->type . ' disetujui Wali Kelas: ' . $leave->reason]
                    );
                }
            }
        } elseif ($request->status === 'REJECTED') {
            // Jika DITOLAK: Batalkan izin approved lain yang tumpang tindih untuk siswa ini
            LeaveRequest::where('student_id', $leave->student_id)
                ->where('id', '!=', $leave->id)
                ->where('status', 'APPROVED')
                ->where(function ($q) use ($leave) {
                    $q->whereBetween('start_date', [$leave->start_date, $leave->end_date])
                      ->orWhereBetween('end_date', [$leave->start_date, $leave->end_date]);
                })
                ->update([
                    'status' => 'REJECTED',
                    'review_note' => 'Dibatalkan karena izin ditolak oleh Wali Kelas: ' . ($request->review_note ?: 'Tidak memenuhi syarat izin.')
                ]);

            // Hapus/batalkan seluruh attendance record siswa untuk tanggal tersebut (baik HADIR lama, SAKIT, maupun IZIN)
            // agar siswa tidak tercatat hadir/izin tanpa verifikasi pasca penolakan
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            $studentClassId = $leave->student?->class_id;
            if ($studentClassId) {
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $session = AttendanceSession::where('class_id', $studentClassId)
                        ->whereDate('date', $date->toDateString())
                        ->first();
                    if ($session) {
                        AttendanceRecord::where('session_id', $session->id)
                            ->where('student_id', $leave->student_id)
                            ->delete();
                    }
                }
            }
        }

        // Notify student with exact reason
        $reasonText = $request->review_note ? ' Alasan: ' . $request->review_note : '';
        Notification::create([
            'user_id' => $leave->student_id,
            'type' => 'SURAT_IZIN',
            'title' => 'Surat Izin ' . ($request->status === 'APPROVED' ? 'Disetujui Wali Kelas' : 'Ditolak Wali Kelas'),
            'body' => ($request->status === 'APPROVED')
                ? 'Permohonan izin Anda telah disetujui oleh Wali Kelas (' . $user->name . ').'
                : 'Permohonan izin Anda telah ditolak oleh Wali Kelas (' . $user->name . ').' . $reasonText,
        ]);

        if ($leave->submitted_by_id && $leave->submitted_by_id !== $leave->student_id) {
            Notification::create([
                'user_id' => $leave->submitted_by_id,
                'type' => 'SURAT_IZIN',
                'title' => 'Surat Izin ' . ($request->status === 'APPROVED' ? 'Disetujui' : 'Ditolak'),
                'body' => 'Permohonan izin untuk ' . ($leave->student->name ?? 'siswa') . ' telah ' . ($request->status === 'APPROVED' ? 'disetujui' : 'ditolak') . ' oleh Wali Kelas.',
            ]);
        }

        // Notifikasi ke Orang Tua siswa jika akun terhubung
        $parent = User::where('student_id', $leave->student_id)->where('role', 'ORANG_TUA')->first()
            ?? User::where('nip_nis', $leave->student?->nip_nis)->where('role', 'ORANG_TUA')->first();
        if ($parent && $parent->id !== $leave->submitted_by_id) {
            Notification::create([
                'user_id' => $parent->id,
                'type' => 'SURAT_IZIN',
                'title' => 'Surat Izin Ananda ' . ($request->status === 'APPROVED' ? 'Disetujui' : 'Ditolak'),
                'body' => 'Permohonan izin ananda ' . ($leave->student->name ?? 'siswa') . ' telah ' . ($request->status === 'APPROVED' ? 'disetujui' : 'ditolak') . ' oleh Wali Kelas.' . $reasonText,
            ]);
        }

        // Otomasi WhatsApp ke Siswa dan Orang Tua
        $stLabel = ($request->status === 'APPROVED' ? 'DISETUJUI' : 'DITOLAK');
        $studentUser = $leave->student;
        $className = $studentUser?->schoolClass?->name ?? 'Kelas';
        $dateFormatted = Carbon::parse($leave->start_date)->translatedFormat('d M Y') . ($leave->start_date !== $leave->end_date ? ' s/d ' . Carbon::parse($leave->end_date)->translatedFormat('d M Y') : '');

        $waMessage = \App\Services\WhatsAppService::formatTemplate('wa_template_izin', [
            'nama' => $studentUser?->name ?? 'Siswa',
            'kelas' => $className,
            'jenis_izin' => $leave->type,
            'tanggal' => $dateFormatted,
            'status' => $stLabel,
            'catatan' => $request->review_note ? "Alasan/Catatan: {$request->review_note}" : '',
        ], "*STATUS SURAT IZIN*: Permohonan izin ({$leave->type}) ananda " . ($studentUser?->name ?? 'Siswa') . " ({$className}) telah {$stLabel} oleh Wali Kelas.");

        if ($studentUser && $studentUser->phone) {
            \App\Services\WhatsAppService::send($studentUser->phone, $waMessage, 'IZIN', $studentUser->id, $studentUser->name);
        }
        if ($parent && $parent->phone) {
            \App\Services\WhatsAppService::send($parent->phone, $waMessage, 'IZIN', $parent->id, $parent->name);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => ($request->status === 'APPROVED' ? 'Menyetujui' : 'Menolak') . ' surat izin siswa ID #' . $leave->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Status surat izin berhasil diperbarui oleh Wali Kelas']);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $leave = LeaveRequest::findOrFail($id);

        // Siswa only their own, Sekretaris or Wali Kelas
        if (in_array($user->role, ['SISWA', 'SEKRETARIS']) && $leave->student_id !== $user->id) {
            return response()->json(['message' => 'Anda tidak memiliki hak untuk membatalkan izin ini.'], 403);
        }

        if ($user->role !== 'GURU_PEMBIMBING' && $leave->status !== 'PENDING') {
            return response()->json(['message' => 'Surat izin yang telah diproses Wali Kelas tidak dapat dibatalkan.'], 422);
        }

        if ($leave->attachment_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($leave->attachment_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($leave->attachment_path);
        }

        $leave->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Membatalkan permohonan surat izin ID #' . $id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Permohonan surat izin berhasil dibatalkan']);
    }

    public function downloadLeaveFile(Request $request, $filename)
    {
        $cleanFilename = basename($filename);
        $path = storage_path('app/public/leave_attachments/' . $cleanFilename);
        if (!file_exists($path)) {
            $altPath = storage_path('app/public/' . $cleanFilename);
            if (file_exists($altPath)) {
                $path = $altPath;
            } else {
                return response()->json(['message' => 'Berkas lampiran surat izin tidak ditemukan.'], 404);
            }
        }

        // Point 7: Access Control Policy (IDOR Protection) untuk berkas surat izin
        $user = $request->user();
        if (!$user && $request->filled('token')) {
            $tokenObj = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            if ($tokenObj) {
                $user = $tokenObj->tokenable;
            }
        }

        $leave = LeaveRequest::where('attachment_path', 'like', "%{$cleanFilename}")->first();
        if ($leave) {
            if (!$user) {
                return response()->json(['message' => 'Autentikasi diperlukan untuk mengunduh lampiran surat izin.'], 401);
            }
            $canAccess = in_array($user->role, ['SUPERADMIN', 'GURU_PEMBIMBING', 'GURU_PENGAJAR'])
                || $leave->student_id === $user->id
                || $leave->submitted_by_id === $user->id
                || ($user->role === 'ORANG_TUA' && $user->student_id === $leave->student_id);
            if (!$canAccess) {
                return response()->json(['message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mengunduh lampiran surat izin ini.'], 403);
            }
        }

        return response()->file($path, [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
