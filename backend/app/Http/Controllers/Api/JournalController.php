<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassJournal;
use App\Models\MaterialJournal;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\Subject;
use App\Models\User;
use App\Models\Setting;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use Illuminate\Support\Str;
use Carbon\Carbon;

class JournalController extends Controller
{
    public function getClassJournals(Request $request)
    {
        $user = $request->user();
        $classId = $request->input('class_id');
        $month = $request->input('month');
        $date = $request->input('date');
        $type = $request->input('type'); // 'personal' or 'class_agenda'
        $query = ClassJournal::with(['schoolClass', 'schedule.subject', 'schedule.teacher', 'createdBy', 'confirmedBy'])->latest('date')->latest('id');

        if ($user->role === 'SISWA') {
            // SISWA HANYA BOLEH MELIHAT CATATAN PRIBADINYA SENDIRI! PRIVASI TERKUNCI RAPAT
            $query->where('created_by_id', $user->id);
        } elseif ($user->role === 'ORANG_TUA') {
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
            // Tanpa fallback sembarangan ke siswa lain
            if ($child) {
                $query->where('created_by_id', $child->id);
            } else {
                $query->where('id', 0); // Cegah melihat catatan siswa lain
            }
        } elseif ($user->role === 'SEKRETARIS') {
            if ($type === 'class_agenda') {
                // Buku Agenda KBM Kelas: Menampilkan agenda resmi tatap muka kelas
                $classId = $user->class_id;
                $query->where('class_id', $classId ?: 0)
                    ->whereHas('createdBy', function ($qu) {
                        $qu->whereIn('role', ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SEKRETARIS', 'SUPERADMIN']);
                    });
            } else {
                // Catatan Belajar Mandiri: Walaupun sekretaris kelas, ia juga hanya bisa melihat catatan belajarnya sendiri!
                $query->where('created_by_id', $user->id);
            }
        } elseif (in_array($user->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING'])) {
            // Pastikan HANYA mengambil agenda resmi buatan Guru, Walas, Sekretaris, atau Admin
            $query->whereHas('createdBy', function ($qu) {
                $qu->whereIn('role', ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SEKRETARIS', 'SUPERADMIN']);
            });

            // Filter tambahan: Abaikan catatan yang bertipe "personal"
            $query->where(function ($q) {
                $q->whereNull('notes')
                    ->orWhere('notes', 'not like', '%"type":"personal"%');
            });

            if ($type === 'personal') {
                // Guru Pengajar dan Walas tidak bisa melihat catatan jurnal pribadi siswa (privasi siswa)
                $query->where('id', 0);
            } elseif ($user->role === 'GURU_PEMBIMBING') {
                $advisorClass = SchoolClass::where('advisor_id', $user->id)->first();
                if ($classId) {
                    $query->where('class_id', $classId);
                } else {
                    // Tampilkan agenda KBM jadwal yang dia ajar ATAU kelas binaannya
                    $query->where(function ($q) use ($user, $advisorClass) {
                        $q->where(function ($sqSub) use ($user) {
                            $sqSub->whereHas('schedule', function ($sq) use ($user) {
                                $sq->where('teacher_id', $user->id);
                            });
                        });
                        if ($advisorClass) {
                            $q->orWhere(function ($advQ) use ($advisorClass) {
                                $advQ->where('class_id', $advisorClass->id)
                                     ->whereHas('createdBy', function ($qu) {
                                         $qu->whereIn('role', ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SEKRETARIS', 'SUPERADMIN']);
                                     });
                            });
                        }
                        $q->orWhere('created_by_id', $user->id);
                    });
                }
            } elseif ($user->role === 'GURU_PENGAJAR') {
                if ($classId) {
                    $query->where('class_id', $classId);
                } else {
                    // Tampilkan agenda KBM untuk jadwal mengajar guru ini
                    $query->where(function ($q) use ($user) {
                        $q->where(function ($sqSub) use ($user) {
                            $sqSub->whereHas('schedule', function ($sq) use ($user) {
                                $sq->where('teacher_id', $user->id);
                            });
                        })->orWhere('created_by_id', $user->id);
                    });
                }
            }
        } elseif ($classId) {
            $query->where('class_id', $classId);
        }

        if ($date) {
            $query->whereDate('date', $date);
        } elseif ($month) {
            $query->where('date', 'like', $month . '%');
        }

        $schedules = Schedule::with('subject', 'teacher')->get();

        $defaultSlot = Setting::get('default_slot_hours', '07:30 - 09:00');
        $defaultRoom = Setting::get('default_room_name', 'Ruang Kelas');

        $journals = $query->get()->map(function ($j) use ($defaultSlot, $defaultRoom) {
            $timeSlot = ($j->schedule && $j->schedule->start_time && $j->schedule->end_time)
                ? ($j->schedule->start_time . ' - ' . $j->schedule->end_time)
                : $defaultSlot;
            $absentStudents = [];
            $studentAttendance = [];
            $photos = [];
            $displayNotes = $j->notes;
            if ($j->notes && str_starts_with(trim($j->notes), '{')) {
                $decoded = json_decode($j->notes, true);
                if (is_array($decoded)) {
                    $displayNotes = $decoded['notes'] ?? '';
                    $absentStudents = $decoded['absent_students'] ?? [];
                    $studentAttendance = $decoded['student_attendance'] ?? [];
                    $photos = $decoded['photos'] ?? [];
                }
            }

            return [
                'id' => $j->id,
                'schedule_id' => $j->schedule_id,
                'date' => Carbon::parse($j->date)->format('Y-m-d'),
                'date_time' => Carbon::parse($j->date)->translatedFormat('d M,') . ' ' . ($j->schedule->start_time ?? explode('-', $defaultSlot)[0]),
                'time_slot' => $timeSlot,
                'room' => $j->schedule->room ?? $defaultRoom,
                'class' => $j->schoolClass->name ?? '-',
                'class_id' => $j->class_id,
                'teacher' => $j->confirmedBy->name ?? ($j->schedule->teacher->name ?? '-'),
                'subject' => $j->schedule->subject->name ?? ($j->notes ? explode(' | ', $j->activity)[0] : '-'),
                'topic' => $j->notes ? explode(' | ', $j->activity)[0] : $j->activity,
                'activity' => $j->activity,
                'notes' => $displayNotes,
                'absent_students' => $absentStudents,
                'student_attendance' => $studentAttendance,
                'photos' => $photos,
                'status' => $j->verification_status,
                'confirmed_at' => $j->confirmed_at ? Carbon::parse($j->confirmed_at)->format('d M Y H:i') : null,
                'confirmed_by' => $j->confirmedBy->name ?? null,
                'created_by' => $j->createdBy->name ?? 'Siswa',
                'created_by_id' => $j->created_by_id,
                'created_by_role' => $j->createdBy->role ?? 'SISWA',
            ];
        });

        $now = Carbon::now('Asia/Jakarta');
        $daysMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $serverDay = $daysMap[$now->dayOfWeekIso] ?? 'Senin';
        $serverDate = $now->toDateString();
        $serverTime = $now->toDateTimeString();

        return response()->json([
            'data' => $journals,
            'schedules' => $schedules,
            'server_date' => $serverDate,
            'server_day' => $serverDay,
            'server_time' => $serverTime,
        ])
        ->header('X-Server-Day', $serverDay)
        ->header('X-Server-Date', $serverDate)
        ->header('X-Server-Time', $serverTime)
        ->header('Access-Control-Expose-Headers', 'X-Server-Day, X-Server-Date, X-Server-Time');
    }

    public function getClassStudents(Request $request, $classId)
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $session = AttendanceSession::where('class_id', $classId)->whereDate('date', $today)->first();
        $records = $session ? AttendanceRecord::where('session_id', $session->id)->get()->keyBy('student_id') : collect();

        // Cari surat izin aktif yang disetujui Walas untuk siswa kelas ini hari ini
        $activeLeaves = \App\Models\LeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->whereHas('student', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            })
            ->get()
            ->keyBy('student_id');

        $students = User::where('class_id', $classId)
            ->whereIn('role', ['SISWA', 'SEKRETARIS'])
            ->orderBy('name')
            ->select('id', 'name', 'nip_nis as nis', 'class_id')
            ->get()
            ->map(function ($s) use ($records, $activeLeaves) {
                $rec = $records->get($s->id);
                $lv = $activeLeaves->get($s->id);
                $isHourly = $lv ? (str_contains($lv->reason ?? '', '[Dispensasi Jam KBM') || str_contains($lv->reason ?? '', '[Dispensasi Jam Tertentu]')) : false;

                $status = $rec ? $rec->status : 'BELUM_ABSEN';
                // Jika ada izin seharian penuh yang disetujui Walas, prioritaskan status izin
                if ($lv && !$isHourly && in_array($status, ['BELUM_ABSEN', 'ALFA'])) {
                    $status = ($lv->type === 'SAKIT') ? 'SAKIT' : 'IZIN';
                }

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'nis' => $s->nis,
                    'class_id' => $s->class_id,
                    'today_status' => $status,
                    'has_hourly_leave' => $isHourly,
                    'hourly_leave_info' => $isHourly ? $lv->reason : null,
                    'leave_type' => $lv ? $lv->type : null,
                ];
            });

        return response()->json($students);
    }

    public function getSchedules(Request $request)
    {
        $user = $request->user();
        $query = Schedule::with(['schoolClass', 'subject', 'teacher'])->orderBy('start_time');
        if ($user && in_array($user->role, ['SEKRETARIS', 'SISWA'])) {
            $query->where('class_id', $user->class_id ?: 0);
        } elseif ($user && $user->role === 'ORANG_TUA') {
            $child = $request->student_id ? User::find($request->student_id) : ($user->student_id ? User::find($user->student_id) : User::where('nip_nis', $user->nip_nis)->first());
            $query->where('class_id', $child?->class_id ?? ($user->class_id ?: 0));
        } elseif ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        } elseif ($user && in_array($user->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING'])) {
            $query->where('teacher_id', $user->id);
        } elseif ($user && $user->class_id) {
            $query->where('class_id', $user->class_id);
        }
        $now = Carbon::now('Asia/Jakarta');
        $daysMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $serverDay = $daysMap[$now->dayOfWeekIso] ?? 'Senin';
        $isWeekend = $now->dayOfWeekIso >= 6 ? '1' : '0';

        return response()->json($query->get())
            ->header('X-Server-Day', $serverDay)
            ->header('X-Server-Date', $now->toDateString())
            ->header('X-Server-Time', $now->toDateTimeString())
            ->header('X-Is-Weekend', $isWeekend)
            ->header('Access-Control-Expose-Headers', 'X-Server-Day, X-Server-Date, X-Server-Time, X-Is-Weekend');
    }

    public function storeSchedule(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'class_id' => 'required',
            'day' => 'nullable|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $subjectId = $request->subject_id;
        if (!$subjectId && $request->subject_name) {
            $sub = Subject::firstOrCreate(
                ['name' => $request->subject_name],
                ['code' => strtoupper(substr($request->subject_name, 0, 4))]
            );
            $subjectId = $sub->id;
        }

        $schedule = Schedule::create([
            'class_id' => $request->class_id,
            'subject_id' => $subjectId ?: 1,
            'teacher_id' => $request->teacher_id ?: $user->id,
            'day' => $request->day ?: 'Senin',
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'room' => $request->room ?: Setting::get('default_room_name', 'Ruang Kelas'),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Menambahkan Jadwal Mengajar baru: ' . ($schedule->subject->name ?? 'Mapel'),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Jadwal mengajar berhasil ditambahkan',
            'schedule' => $schedule->load(['schoolClass', 'subject', 'teacher']),
        ]);
    }

    public function storeClassJournal(Request $request)
    {
        $user = $request->user();
        // Siswa, Sekretaris, Guru Pengajar, dan Wali Kelas berhak mengisi Jurnal Kegiatan Belajar
        if (!in_array($user->role, ['SISWA', 'SEKRETARIS', 'GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk mengisi jurnal kegiatan.'], 403);
        }

        $schedule = $request->schedule_id ? Schedule::find($request->schedule_id) : null;
        $classId = in_array($user->role, ['SEKRETARIS', 'SISWA'])
            ? $user->class_id
            : ($request->class_id ?? ($schedule->class_id ?? $user->class_id));

        if (!$classId) {
            return response()->json(['message' => 'Kelas rombel belum ditentukan.'], 422);
        }

        if (!$schedule) {
            $schedule = Schedule::where('class_id', $classId)->first();
        }

        $date = $request->date ?? Carbon::today()->toDateString();
        if (Carbon::parse($date)->isWeekend()) {
            return response()->json(['message' => 'Jurnal kegiatan pembelajaran tidak dapat dicatat pada hari libur akhir pekan (Sabtu/Minggu). Kegiatan KBM hanya berlangsung Senin hingga Jumat.'], 422);
        }
        $activity = $request->topic ? ($request->topic . ' - ' . $request->activity) : $request->activity;

        $type = $request->input('type'); // 'class_agenda' or 'personal'
        $isAgenda = ($type === 'class_agenda');
        $isTeacher = in_array($user->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN']);

        if ($isAgenda) {
            // Agenda KBM Kelas: jika diisi guru otomatis TERVERIFIKASI, jika diisi sekretaris menunggu konfirmasi guru pengajar
            $status = $isTeacher ? 'TERVERIFIKASI' : 'PENDING';
            $confirmedById = $isTeacher ? $user->id : null;
            $confirmedAt = $isTeacher ? Carbon::now() : null;
        } else {
            // Catatan belajar pribadi siswa langsung tercatat (TERVERIFIKASI) agar terpantau orang tua & wali kelas
            $status = 'TERVERIFIKASI';
            $confirmedById = $schedule?->teacher_id ?? $user->id;
            $confirmedAt = Carbon::now();
        }

        $studentAttendance = $request->input('student_attendance', []);
        $absentStudents = $request->input('absent_students', []);
        $notesInput = $request->input('notes', '');
        $photos = (array) $request->input('photos', []);

        if ($request->hasFile('photo_1')) {
            $p1 = $request->file('photo_1')->store('kbm_photos', 'public');
            $photos[] = url('storage/' . $p1);
        }
        if ($request->hasFile('photo_2')) {
            $p2 = $request->file('photo_2')->store('kbm_photos', 'public');
            $photos[] = url('storage/' . $p2);
        }

        $notesToStore = json_encode([
            'type' => $type ?: ($isAgenda ? 'class_agenda' : 'personal'),
            'notes' => $notesInput ?: '',
            'student_attendance' => $studentAttendance,
            'absent_students' => $absentStudents,
            'photos' => array_values(array_filter($photos)),
        ], JSON_UNESCAPED_UNICODE);

        $journal = ClassJournal::create([
            'class_id' => $classId,
            'schedule_id' => $schedule?->id,
            'date' => $date,
            'activity' => $activity,
            'notes' => $notesToStore,
            'verification_status' => $status,
            'confirmed_by_id' => $confirmedById,
            'confirmed_at' => $confirmedAt,
            'created_by_id' => $user->id,
        ]);

        // Notifikasi ke Orang Tua secara real-time jika ada siswa yang dicatat IZIN Keluar atau ALFA Mapel
        if (!empty($studentAttendance)) {
            $this->notifyParentsForKbmAttendance($studentAttendance, $schedule, $date);
        }

        if ($isAgenda) {
            // Notifikasi ke guru pengajar mapel bersangkutan
            if ($schedule && $schedule->teacher_id && $schedule->teacher_id !== $user->id) {
                Notification::create([
                    'user_id' => $schedule->teacher_id,
                    'type' => 'JURNAL',
                    'title' => 'Agenda KBM Kelas Menunggu Konfirmasi',
                    'body' => 'Sekretaris kelas telah mengisi agenda KBM mapel ' . ($schedule->subject->name ?? 'Anda') . '. Silakan periksa dan berikan paraf/konfirmasi.',
                ]);
            }
        } else {
            // Catatan belajar mandiri siswa/sekretaris: Beri tahu Orang Tua secara real-time
            if (in_array($user->role, ['SISWA', 'SEKRETARIS'])) {
                $parent = User::where('role', 'ORANG_TUA')
                    ->where(function($q) use ($user) {
                        $q->where('student_id', $user->id)
                          ->orWhere('nip_nis', $user->nip_nis);
                    })
                    ->first();

                if ($parent) {
                    Notification::create([
                        'user_id' => $parent->id,
                        'type' => 'JURNAL',
                        'title' => 'Laporan Jurnal Belajar Putra/Putri Anda',
                        'body' => $user->name . ' telah mengisi laporan kegiatan belajar: "' . \Illuminate\Support\Str::limit($activity, 80) . '" pada mapel ' . ($schedule->subject->name ?? 'KBM') . '.',
                    ]);
                }
            }
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => ($isAgenda ? 'Mengisi Agenda KBM Kelas ' : 'Melaporkan Catatan Belajar ') . 'tanggal ' . $date . ' (' . $user->name . ')',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => $isAgenda
                ? ($isTeacher ? 'Agenda KBM kelas berhasil disimpan & diverifikasi' : 'Agenda KBM kelas berhasil dicatat dan menunggu verifikasi guru pengajar')
                : 'Catatan kegiatan belajar berhasil dilaporkan dan otomatis terpantau oleh Orang Tua serta Wali Kelas!',
            'journal' => $journal,
        ]);
    }

    public function updateClassJournal(Request $request, $id)
    {
        $user = $request->user();
        $journal = ClassJournal::findOrFail($id);

        // Point 7: Access Control Policy (IDOR Protection)
        if (in_array($user->role, ['SISWA', 'SEKRETARIS']) && $journal->created_by_id !== $user->id) {
            return response()->json([
                'message' => 'Akses ditolak (IDOR Policy): Anda hanya dapat menyunting catatan jurnal milik Anda sendiri.'
            ], 403);
        }

        if ($journal->verification_status === 'TERVERIFIKASI' && !in_array($user->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'])) {
            return response()->json([
                'message' => 'Jurnal kegiatan telah diverifikasi guru. Data telah dikunci permanen (read-only).'
            ], 422);
        }

        $activity = $request->topic ? ($request->topic . ' - ' . $request->activity) : ($request->activity ?: $journal->activity);

        $studentAttendance = $request->input('student_attendance');
        $absentStudents = $request->input('absent_students');
        $notesInput = $request->input('notes');
        $photos = $request->input('photos');

        $notesToStore = $journal->notes;
        if ($absentStudents !== null || $notesInput !== null || $studentAttendance !== null || $photos !== null) {
            $currentAbsent = [];
            $currentStudentAttendance = [];
            $currentPhotos = [];
            $currentText = $journal->notes;
            if ($journal->notes && str_starts_with(trim($journal->notes), '{')) {
                $dec = json_decode($journal->notes, true);
                if (is_array($dec)) {
                    $currentAbsent = $dec['absent_students'] ?? [];
                    $currentStudentAttendance = $dec['student_attendance'] ?? [];
                    $currentPhotos = $dec['photos'] ?? [];
                    $currentText = $dec['notes'] ?? '';
                }
            }
            $finalAbsent = $absentStudents !== null ? $absentStudents : $currentAbsent;
            $finalStudentAttendance = $studentAttendance !== null ? $studentAttendance : $currentStudentAttendance;
            $finalPhotos = $photos !== null ? $photos : $currentPhotos;
            $finalText = $notesInput !== null ? $notesInput : $currentText;

            $notesToStore = json_encode([
                'notes' => $finalText ?: '',
                'student_attendance' => $finalStudentAttendance,
                'absent_students' => $finalAbsent,
                'photos' => array_values(array_filter($finalPhotos)),
            ], JSON_UNESCAPED_UNICODE);
        }

        $journal->update([
            'activity' => $activity,
            'notes' => $notesToStore,
            'date' => $request->date ?: $journal->date,
            'schedule_id' => $request->schedule_id ?: $journal->schedule_id,
        ]);

        if (!empty($finalStudentAttendance)) {
            $sch = $journal->schedule ?: Schedule::find($request->schedule_id ?: $journal->schedule_id);
            $this->notifyParentsForKbmAttendance($finalStudentAttendance, $sch, $journal->date);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Menyunting draf jurnal kegiatan ID #' . $journal->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Jurnal kegiatan berhasil diperbarui',
            'journal' => $journal,
        ]);
    }

    public function confirmClassJournal(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Hanya Guru, Wali Kelas, atau Superadmin yang berhak mengonfirmasi jurnal kegiatan.'], 403);
        }
        $journal = ClassJournal::findOrFail($id);

        $journal->update([
            'verification_status' => 'TERVERIFIKASI',
            'confirmed_by_id' => $user->id,
            'confirmed_at' => Carbon::now(),
        ]);

        Notification::create([
            'user_id' => $journal->created_by_id,
            'type' => 'JURNAL',
            'title' => 'Jurnal Kegiatan Terverifikasi',
            'body' => 'Jurnal kegiatan belajar Anda telah dikonfirmasi oleh ' . $user->name,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Mengonfirmasi jurnal kegiatan ID #' . $journal->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Jurnal kegiatan berhasil dikonfirmasi']);
    }

    public function getMaterialJournals(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $user = $request->user();

        $query = MaterialJournal::with(['subject', 'teacher', 'schoolClass'])->latest('date');

        if ($user) {
            if (in_array($user->role, ['SISWA', 'SEKRETARIS'])) {
                if ($user->class_id) {
                    $query->where('class_id', $user->class_id);
                }
            } elseif ($user->role === 'ORANG_TUA') {
                $child = null;
                if ($request->filled('student_id')) {
                    $child = User::find($request->input('student_id'));
                }
                if (!$child && $user->student_id) {
                    $child = User::find($user->student_id);
                }
                if ($child && $child->class_id) {
                    $query->where('class_id', $child->class_id);
                } elseif ($user->class_id) {
                    $query->where('class_id', $user->class_id);
                }
            } elseif ($user->role === 'GURU_PEMBIMBING') {
                $class = SchoolClass::where('advisor_id', $user->id)->first();
                $targetClassId = $request->input('class_id') ?: ($class ? $class->id : $user->class_id);
                if ($targetClassId) {
                    $query->where('class_id', $targetClassId);
                }
            } elseif ($user->role === 'GURU_PENGAJAR') {
                if ($request->filled('class_id')) {
                    $query->where('class_id', $request->input('class_id'));
                } else {
                    $query->where('teacher_id', $user->id);
                }
            } elseif ($request->filled('class_id')) {
                $query->where('class_id', $request->input('class_id'));
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('topic', 'like', "%{$search}%")
                  ->orWhereHas('subject', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($month) {
            $query->where('date', 'like', "{$month}%");
        }

        $materials = $query->get()->map(function ($m) {
            $attachmentUrl = null;
            if ($m->attachment_path) {
                $attachmentUrl = url('api/materials/file/' . basename($m->attachment_path));
            }
            return [
                'id' => $m->id,
                'schedule_id' => $m->schedule_id,
                'class_id' => $m->class_id,
                'date' => Carbon::parse($m->date)->format('Y-m-d'),
                'date_time' => Carbon::parse($m->date)->translatedFormat('d M, 07:30'),
                'teacher' => $m->teacher->name ?? 'Guru',
                'subject' => $m->subject->name ?? 'Mata Pelajaran',
                'topic' => $m->topic,
                'plan' => $m->plan,
                'assignment' => $m->assignment,
                'attachment_path' => $m->attachment_path,
                'attachment_name' => $m->attachment_path ? basename($m->attachment_path) : 'Modul_Belajar_Kelas.pdf',
                'attachment_url' => $attachmentUrl,
                'attachments_count' => $m->attachment_path ? 1 : 0,
            ];
        });

        return response()->json($materials);
    }

    public function storeMaterialJournal(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Hanya Guru yang berhak mengunggah materi pelajaran.'], 403);
        }

        // Point 7: Strict File Upload Hardening (MIME Type riil, max 10MB)
        $allowedMimes = 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpg,jpeg,png';
        $allowedMimeTypes = 'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar-compressed,image/jpeg,image/png';

        $request->validate([
            'topic' => 'required|string|max:255',
            'plan' => 'nullable|string',
            'assignment' => 'nullable|string',
            'file' => "nullable|file|max:10240|mimes:{$allowedMimes}|mimetypes:{$allowedMimeTypes}",
            'attachment' => "nullable|file|max:10240|mimes:{$allowedMimes}|mimetypes:{$allowedMimeTypes}",
        ]);

        $schedule = $request->schedule_id ? Schedule::find($request->schedule_id) : null;
        $classId = $request->class_id ?: ($schedule->class_id ?? $user->class_id);
        $subjectId = $request->subject_id ?: ($schedule->subject_id ?? null);
        $date = $request->date ?: Carbon::today()->toDateString();

        // Point 7: Simpan file dengan nama acak UUID & ekstensi aman
        $attachmentPath = null;
        $file = $request->file('file') ?: $request->file('attachment');
        if ($file) {
            $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $randomName = Str::uuid()->toString() . '.' . $ext;
            $attachmentPath = $file->storeAs('materials', $randomName, 'public');
        }

        $material = MaterialJournal::create([
            'teacher_id' => $user->id,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'schedule_id' => $request->schedule_id,
            'date' => $date,
            'topic' => $request->topic,
            'plan' => $request->plan,
            'assignment' => $request->assignment,
            'attachment_path' => $attachmentPath,
        ]);

        $subjectName = Subject::find($subjectId)->name ?? 'Mata Pelajaran';
        $classStudents = User::where('class_id', $classId)
            ->whereIn('role', ['SISWA', 'SEKRETARIS', 'ORANG_TUA'])
            ->get();

        foreach ($classStudents as $std) {
            Notification::create([
                'user_id' => $std->id,
                'type' => 'JURNAL',
                'title' => 'Materi Baru: ' . $subjectName,
                'body' => $user->name . ' mengunggah materi baru "' . $request->topic . '". Silakan cek di menu Jurnal Kegiatan.',
            ]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Mengunggah Rencana Belajar & Berkas Materi: ' . $request->topic,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Rencana belajar & berkas materi berhasil disimpan',
            'material' => $material,
        ]);
    }

    public function downloadMaterialFile(Request $request, $filename)
    {
        $cleanFilename = basename($filename);
        $path = storage_path('app/public/materials/' . $cleanFilename);
        if (!file_exists($path)) {
            return response()->json(['message' => 'Berkas lampiran materi tidak ditemukan.'], 404);
        }

        // Point 7: Access Control Policy (IDOR Protection) untuk berkas materi
        $user = $request->user();
        if (!$user && $request->filled('token')) {
            $tokenObj = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            if ($tokenObj) {
                $user = $tokenObj->tokenable;
            }
        }

        $material = MaterialJournal::where('attachment_path', 'like', "%{$cleanFilename}")->first();
        if ($material && $user) {
            if (!in_array($user->role, ['SUPERADMIN', 'GURU_PEMBIMBING', 'GURU_PENGAJAR'])) {
                if ($user->class_id && $material->class_id !== $user->class_id) {
                    return response()->json(['message' => 'Akses ditolak: Berkas materi ini tidak ditujukan untuk kelas Anda.'], 403);
                }
            }
        }

        return response()->file($path, [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    public function deleteClassJournal(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['SISWA', 'SEKRETARIS', 'GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Anda tidak berhak menghapus jurnal kegiatan.'], 403);
        }
        $journal = ClassJournal::findOrFail($id);

        // Point 7: Access Control Policy (IDOR) - Siswa & Sekretaris hanya dapat menghapus catatannya sendiri
        if (in_array($user->role, ['SISWA', 'SEKRETARIS']) && $journal->created_by_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak (IDOR Policy): Anda hanya dapat menghapus catatan jurnal milik Anda sendiri.'], 403);
        }

        if ($journal->verification_status === 'TERVERIFIKASI' && !in_array($user->role, ['GURU_PEMBIMBING', 'SUPERADMIN'])) {
            return response()->json([
                'message' => 'Jurnal kegiatan telah terverifikasi dan tidak dapat dihapus.'
            ], 422);
        }
        $journal->delete();
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Menghapus jurnal kegiatan ID #' . $id,
            'ip_address' => $request->ip(),
        ]);
        return response()->json(['message' => 'Jurnal kegiatan berhasil dihapus']);
    }

    public function updateMaterialJournal(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Hanya Guru atau Wali Kelas yang berhak menyunting materi pelajaran.'], 403);
        }
        $material = MaterialJournal::findOrFail($id);

        // Point 7: Access Control Policy (IDOR Protection) - Memvalidasi ownership policy guru ($material->teacher_id === auth()->id())
        if ($user->role !== 'SUPERADMIN' && $material->teacher_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak (IDOR Policy): Anda hanya berhak menyunting materi pelajaran yang Anda buat sendiri.'], 403);
        }

        $material->update([
            'topic' => $request->topic ?: $material->topic,
            'plan' => $request->plan ?: $material->plan,
            'assignment' => $request->assignment !== null ? $request->assignment : $material->assignment,
        ]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Menyunting jurnal materi ID #' . $material->id,
            'ip_address' => $request->ip(),
        ]);
        return response()->json(['message' => 'Jurnal materi berhasil diperbarui', 'material' => $material]);
    }

    public function deleteMaterialJournal(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Hanya Guru atau Wali Kelas yang berhak menghapus materi pelajaran.'], 403);
        }
        $material = MaterialJournal::findOrFail($id);

        // Point 7: Access Control Policy (IDOR Protection) - Memvalidasi ownership policy guru ($material->teacher_id === auth()->id())
        if ($user->role !== 'SUPERADMIN' && $material->teacher_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak (IDOR Policy): Anda hanya berhak menghapus materi pelajaran milik Anda sendiri.'], 403);
        }

        if ($material->attachment_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($material->attachment_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($material->attachment_path);
        }
        $material->delete();
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Menghapus jurnal materi ID #' . $id,
            'ip_address' => $request->ip(),
        ]);
        return response()->json(['message' => 'Materi pelajaran berhasil dihapus']);
    }

    /**
     * Kirim notifikasi lonceng ke akun Orang Tua ketika siswa dicatat IZIN Keluar atau ALFA pada jam pelajaran KBM
     */
    private function notifyParentsForKbmAttendance(array $studentAttendance, $schedule, $date)
    {
        if (empty($studentAttendance)) return;

        $subjectName = $schedule?->subject?->name ?? 'Mata Pelajaran';
        $timeStr = ($schedule?->start_time ? substr($schedule->start_time, 0, 5) : '') . ($schedule?->end_time ? (' - ' . substr($schedule->end_time, 0, 5)) : '');

        foreach ($studentAttendance as $sa) {
            $studentId = $sa['id'] ?? null;
            $status = strtoupper($sa['status'] ?? '');
            if (!$studentId || !in_array($status, ['IZIN', 'ALPHA', 'ALFA'])) {
                continue;
            }

            $studentUser = User::find($studentId);
            if (!$studentUser) continue;

            $parent = User::where('role', 'ORANG_TUA')
                ->where(function($q) use ($studentUser) {
                    $q->where('student_id', $studentUser->id)
                      ->orWhere('nip_nis', $studentUser->nip_nis);
                })
                ->first();

            if (!$parent) continue;

            if ($status === 'IZIN') {
                $leaveTime = $sa['leave_time'] ?? 'Saat KBM';
                $reason = $sa['note'] ?? 'Izin keluar kelas saat jam pelajaran';
                Notification::create([
                    'user_id' => $parent->id,
                    'type' => 'PRESENSI',
                    'title' => 'Pemberitahuan Izin Keluar Jam Mapel',
                    'body' => "Pemberitahuan: Ananda {$studentUser->name} tercatat izin keluar kelas pada jam mapel {$subjectName} pukul {$leaveTime}. Keterangan: \"{$reason}\".",
                ]);
            } elseif (in_array($status, ['ALPHA', 'ALFA'])) {
                Notification::create([
                    'user_id' => $parent->id,
                    'type' => 'PRESENSI',
                    'title' => 'Peringatan Kehadiran (Alfa Jam Mapel)',
                    'body' => "Peringatan: Ananda {$studentUser->name} tercatat Tidak Hadir (Alfa) pada jam pelajaran {$subjectName}" . ($timeStr ? " ({$timeStr} WIB)" : "") . " oleh Guru Pengajar.",
                ]);
            }
        }
    }
}
