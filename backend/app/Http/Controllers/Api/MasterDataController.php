<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\Term;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Setting;

class MasterDataController extends Controller
{
    private function checkSuperadmin(Request $request)
    {
        if ($request->user()->role !== 'SUPERADMIN') {
            abort(response()->json(['message' => 'Hanya Superadmin yang berhak mengelola data master institusi.'], 403));
        }
    }

    private function checkStaff(Request $request)
    {
        if (!in_array($request->user()->role, ['SUPERADMIN', 'GURU_PEMBIMBING', 'GURU_PENGAJAR'])) {
            abort(response()->json(['message' => 'Hanya Guru dan Administrator yang berhak mengakses data ini.'], 403));
        }
    }

    // ==========================================
    // 1. CLASSES (ROMBEL)
    // ==========================================

    public function getClasses(Request $request)
    {
        $this->checkStaff($request);

        $query = SchoolClass::with(['advisor', 'secretary'])
            ->withCount(['students', 'schedules'])
            ->orderBy('name');

        if ($request->filled('major') && $request->major !== 'ALL') {
            $query->where('major', $request->major);
        }

        return response()->json($query->get());
    }

    public function storeClass(Request $request)
    {
        $this->checkSuperadmin($request);

        $request->validate([
            'name' => 'required|string|unique:classes,name',
            'major' => 'nullable|string',
            'advisor_id' => 'nullable|exists:users,id',
            'secretary_id' => 'nullable|exists:users,id',
        ]);

        $cls = SchoolClass::create([
            'name' => $request->name,
            'major' => $request->major ?: 'RPL',
            'advisor_id' => $request->advisor_id,
            'secretary_id' => $request->secretary_id,
        ]);

        // If advisor assigned, set class_id for that user
        if ($cls->advisor_id) {
            User::where('id', $cls->advisor_id)->update(['class_id' => $cls->id]);
        }

        // If secretary assigned, set class_id for that user
        if ($cls->secretary_id) {
            User::where('id', $cls->secretary_id)->update(['class_id' => $cls->id, 'role' => 'SEKRETARIS']);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Membuat rombongan belajar baru: {$cls->name} (Jurusan {$cls->major})",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Kelas {$cls->name} berhasil ditambahkan",
            'class' => $cls->load(['advisor', 'secretary']),
        ]);
    }

    public function updateClass(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $cls = SchoolClass::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:classes,name,' . $cls->id,
            'major' => 'nullable|string',
            'advisor_id' => 'nullable|exists:users,id',
            'secretary_id' => 'nullable|exists:users,id',
        ]);

        $cls->update([
            'name' => $request->name,
            'major' => $request->major ?: $cls->major,
            'advisor_id' => $request->advisor_id,
            'secretary_id' => $request->secretary_id,
        ]);

        if ($cls->advisor_id) {
            User::where('id', $cls->advisor_id)->update(['class_id' => $cls->id]);
        }

        if ($cls->secretary_id) {
            User::where('id', $cls->secretary_id)->update(['class_id' => $cls->id, 'role' => 'SEKRETARIS']);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Memperbarui data kelas: {$cls->name} ({$cls->major})",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Kelas {$cls->name} berhasil diperbarui",
            'class' => $cls->load(['advisor', 'secretary']),
        ]);
    }

    public function destroyClass(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $cls = SchoolClass::findOrFail($id);
        $name = $cls->name;

        // Unlink users from this class
        User::where('class_id', $cls->id)->update(['class_id' => null]);
        $cls->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Menghapus rombongan belajar: {$name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => "Kelas {$name} berhasil dihapus"]);
    }

    public function promoteStudents(Request $request)
    {
        $this->checkSuperadmin($request);

        $request->validate([
            'source_class_id' => 'required|exists:classes,id',
            'target_class_id' => 'required|exists:classes,id',
            'student_ids' => 'nullable|array',
        ]);

        $source = SchoolClass::findOrFail($request->source_class_id);
        $target = SchoolClass::findOrFail($request->target_class_id);

        $query = User::where('class_id', $source->id)->whereIn('role', ['SISWA', 'SEKRETARIS']);
        if (!empty($request->student_ids)) {
            $query->whereIn('id', $request->student_ids);
        }

        $count = $query->count();
        $query->update(['class_id' => $target->id]);

        // Reset secretary_id di kelas asal jika sekretarisnya ikut pindah ke kelas tujuan
        if ($source->secretary_id) {
            $isSecMoved = User::where('id', $source->secretary_id)->where('class_id', $target->id)->exists();
            if ($isSecMoved) {
                $source->update(['secretary_id' => null]);
            }
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Memindahkan {$count} siswa dari {$source->name} ke {$target->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Berhasil memindahkan {$count} siswa dari {$source->name} ke {$target->name}",
            'transferred_count' => $count,
        ]);
    }

    // ==========================================
    // 2. SUBJECTS (MATA PELAJARAN)
    // ==========================================

    public function getSubjects(Request $request)
    {
        $this->checkStaff($request);

        $query = Subject::with(['teacher'])
            ->withCount('schedules')
            ->orderBy('name');

        if ($request->filled('major') && $request->major !== 'ALL') {
            $query->where('major', $request->major);
        }

        return response()->json($query->get());
    }

    public function storeSubject(Request $request)
    {
        $this->checkSuperadmin($request);

        $request->validate([
            'name' => 'required|string',
            'code' => 'nullable|string|max:20',
            'major' => 'nullable|string',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $code = $request->code ?: strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $request->name), 0, 4));

        $sub = Subject::create([
            'name' => $request->name,
            'code' => $code,
            'major' => $request->major ?: null,
            'teacher_id' => $request->teacher_id,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Menambahkan mata pelajaran: {$sub->name} ({$sub->code})",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Mata pelajaran {$sub->name} berhasil ditambahkan",
            'subject' => $sub->load('teacher'),
        ]);
    }

    public function updateSubject(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $sub = Subject::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'code' => 'nullable|string|max:20',
            'major' => 'nullable|string',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $sub->update($request->only(['name', 'code', 'major', 'teacher_id']));

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Memperbarui mata pelajaran: {$sub->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Mata pelajaran {$sub->name} berhasil diperbarui",
            'subject' => $sub->load('teacher'),
        ]);
    }

    public function destroySubject(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $sub = Subject::findOrFail($id);
        $name = $sub->name;
        $sub->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Menghapus mata pelajaran: {$name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => "Mata pelajaran {$name} berhasil dihapus"]);
    }

    // ==========================================
    // 3. SCHEDULES (JADWAL PELAJARAN)
    // ==========================================

    public function getSchedules(Request $request)
    {
        $this->checkStaff($request);

        $classId = $request->input('class_id');
        $major = $request->input('major');

        $query = Schedule::with(['schoolClass', 'subject', 'teacher'])->orderBy('day')->orderBy('start_time');

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($major && $major !== 'ALL') {
            $query->whereHas('schoolClass', function ($q) use ($major) {
                $q->where('major', $major);
            });
        }

        return response()->json($query->get());
    }

    private function normalizeDay($day)
    {
        $dayMap = [
            'monday' => 'Senin', 'mon' => 'Senin', 'senin' => 'Senin',
            'tuesday' => 'Selasa', 'tue' => 'Selasa', 'selasa' => 'Selasa',
            'wednesday' => 'Rabu', 'wed' => 'Rabu', 'rabu' => 'Rabu',
            'thursday' => 'Kamis', 'thu' => 'Kamis', 'kamis' => 'Kamis',
            'friday' => 'Jumat', 'fri' => 'Jumat', 'jumat' => 'Jumat',
            'saturday' => 'Sabtu', 'sat' => 'Sabtu', 'sabtu' => 'Sabtu',
            'sunday' => 'Minggu', 'sun' => 'Minggu', 'minggu' => 'Minggu',
        ];
        $clean = strtolower(trim($day));
        return $dayMap[$clean] ?? ucfirst($clean);
    }

    private function checkScheduleConflict($classId, $teacherId, $day, $startTime, $endTime, $room, $ignoreScheduleId = null)
    {
        $day = $this->normalizeDay($day);

        // Also check against both English and Indonesian equivalent to prevent any bypass
        $equivalentDays = [$day];
        $invMap = [
            'Senin' => 'Monday', 'Selasa' => 'Tuesday', 'Rabu' => 'Wednesday',
            'Kamis' => 'Thursday', 'Jumat' => 'Friday', 'Sabtu' => 'Saturday', 'Minggu' => 'Sunday'
        ];
        if (isset($invMap[$day])) {
            $equivalentDays[] = $invMap[$day];
        }

        // 1. Cek bentrok guru (1 guru tidak bisa mengajar di 2 tempat pada jam yang sama)
        $teacherConflict = Schedule::where('teacher_id', $teacherId)
            ->whereIn('day', $equivalentDays)
            ->when($ignoreScheduleId, fn($q) => $q->where('id', '!=', $ignoreScheduleId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->with(['schoolClass', 'subject', 'teacher'])
            ->first();

        if ($teacherConflict) {
            $tName = $teacherConflict->teacher->name ?? 'Guru bersangkutan';
            $cName = $teacherConflict->schoolClass->name ?? 'Kelas lain';
            $subName = $teacherConflict->subject->name ?? 'Mata pelajaran lain';
            return "Bentrok Jadwal Guru: {$tName} sudah terjadwal mengajar {$subName} di kelas {$cName} pada hari {$day} pukul {$teacherConflict->start_time} - {$teacherConflict->end_time}.";
        }

        // 2. Cek bentrok kelas (1 kelas tidak bisa menerima 2 mapel berbeda pada jam yang sama)
        $classConflict = Schedule::where('class_id', $classId)
            ->whereIn('day', $equivalentDays)
            ->when($ignoreScheduleId, fn($q) => $q->where('id', '!=', $ignoreScheduleId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->with(['subject', 'teacher', 'schoolClass'])
            ->first();

        if ($classConflict) {
            $cName = $classConflict->schoolClass->name ?? 'Kelas ini';
            $subName = $classConflict->subject->name ?? 'Mata pelajaran lain';
            $tName = $classConflict->teacher->name ?? 'Guru lain';
            return "Bentrok Jadwal Kelas: {$cName} sudah memiliki jadwal {$subName} ({$tName}) pada hari {$day} pukul {$classConflict->start_time} - {$classConflict->end_time}.";
        }

        // 3. Cek bentrok ruangan (1 ruangan tidak bisa dipakai 2 kelas pada jam yang sama)
        if ($room) {
            $roomConflict = Schedule::where('room', $room)
                ->whereIn('day', $equivalentDays)
                ->when($ignoreScheduleId, fn($q) => $q->where('id', '!=', $ignoreScheduleId))
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->with(['schoolClass', 'subject', 'teacher'])
                ->first();

            if ($roomConflict) {
                $cName = $roomConflict->schoolClass->name ?? 'Kelas lain';
                $tName = $roomConflict->teacher->name ?? 'Guru';
                return "Bentrok Ruangan: Ruang '{$room}' sedang digunakan oleh {$cName} ({$tName}) pada hari {$day} pukul {$roomConflict->start_time} - {$roomConflict->end_time}.";
            }
        }

        return null;
    }

    public function storeSchedule(Request $request)
    {
        $this->checkSuperadmin($request);

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day' => 'required|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'room' => 'nullable|string',
        ]);

        $defaultRoom = Setting::get('default_room_name', 'Ruang Kelas');
        $room = $request->room ?: $defaultRoom;
        $day = $this->normalizeDay($request->day);
        if (!in_array($day, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'])) {
            return response()->json(['message' => 'Hari belajar mengajar hanya dari Senin hingga Jumat (tidak ada jadwal pada hari Sabtu/Minggu).'], 422);
        }

        $conflict = $this->checkScheduleConflict(
            $request->class_id,
            $request->teacher_id,
            $day,
            $request->start_time,
            $request->end_time,
            $room
        );

        if ($conflict) {
            return response()->json(['message' => $conflict], 422);
        }

        $sch = Schedule::create([
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'day' => $day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'room' => $room,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Membuat jadwal pelajaran baru untuk kelas ID {$request->class_id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Jadwal pelajaran berhasil ditambahkan',
            'schedule' => $sch->load(['schoolClass', 'subject', 'teacher']),
        ]);
    }

    public function updateSchedule(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $sch = Schedule::findOrFail($id);

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day' => 'required|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'room' => 'nullable|string',
        ]);

        $defaultRoom = Setting::get('default_room_name', 'Ruang Kelas');
        $room = $request->room ?: $defaultRoom;
        $day = $this->normalizeDay($request->day);
        if (!in_array($day, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'])) {
            return response()->json(['message' => 'Hari belajar mengajar hanya dari Senin hingga Jumat (tidak ada jadwal pada hari Sabtu/Minggu).'], 422);
        }

        $conflict = $this->checkScheduleConflict(
            $request->class_id,
            $request->teacher_id,
            $day,
            $request->start_time,
            $request->end_time,
            $room,
            $id
        );

        if ($conflict) {
            return response()->json(['message' => $conflict], 422);
        }

        $sch->update([
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'day' => $day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'room' => $room,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Memperbarui jadwal pelajaran ID #{$sch->id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Jadwal pelajaran berhasil diperbarui',
            'schedule' => $sch->load(['schoolClass', 'subject', 'teacher']),
        ]);
    }

    public function destroySchedule(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $sch = Schedule::findOrFail($id);
        $sch->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Menghapus jadwal pelajaran ID #{$id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Jadwal pelajaran berhasil dihapus']);
    }

    // ==========================================
    // 4. TERMS (SEMESTER & TAHUN AJARAN)
    // ==========================================

    public function getTerms(Request $request)
    {
        $this->checkStaff($request);

        return response()->json(Term::orderBy('start_date', 'desc')->get());
    }

    public function storeTerm(Request $request)
    {
        $this->checkSuperadmin($request);

        $request->validate([
            'name' => 'required|string|unique:terms,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'effective_days' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        if ($request->is_active) {
            Term::query()->update(['is_active' => false]);
        }

        $term = Term::create($request->only(['name', 'start_date', 'end_date', 'effective_days', 'is_active']));

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Menambahkan tahun ajaran/semester baru: {$term->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Semester {$term->name} berhasil ditambahkan",
            'term' => $term,
        ]);
    }

    public function updateTerm(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $term = Term::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:terms,name,' . $term->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'effective_days' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        if ($request->is_active && !$term->is_active) {
            Term::query()->update(['is_active' => false]);
        }

        $term->update($request->only(['name', 'start_date', 'end_date', 'effective_days', 'is_active']));

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Memperbarui data tahun ajaran/semester ID #{$term->id}: {$term->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Semester {$term->name} berhasil diperbarui",
            'term' => $term,
        ]);
    }

    public function activateTerm(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $term = Term::findOrFail($id);
        Term::query()->update(['is_active' => false]);
        $term->update(['is_active' => true]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Mengaktifkan tahun ajaran/semester: {$term->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Semester {$term->name} sekarang menjadi semester aktif",
            'term' => $term,
        ]);
    }

    public function destroyTerm(Request $request, $id)
    {
        $this->checkSuperadmin($request);

        $term = Term::findOrFail($id);
        if ($term->is_active) {
            return response()->json([
                'message' => 'Tidak dapat menghapus semester yang sedang berstatus AKTIF. Silakan aktifkan semester lain terlebih dahulu sebelum menghapus.',
            ], 422);
        }

        $name = $term->name;
        $term->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => "Menghapus data tahun ajaran/semester ID #{$id}: {$name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Semester {$name} berhasil dihapus",
        ]);
    }
}
