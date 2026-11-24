<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\User;

echo "=== 1. SYNC INDUSTRY CLASSES ===\n";
// 1. RPL
$clsRpl = SchoolClass::firstOrCreate(
    ['name' => 'Kelas Industri RPL'],
    ['major' => 'RPL']
);
$clsRpl->update(['major' => 'RPL']);
echo "Class ID {$clsRpl->id}: {$clsRpl->name} ({$clsRpl->major})\n";

// 2. DKV
$clsDkv = SchoolClass::firstOrCreate(
    ['name' => 'Kelas Industri DKV'],
    ['major' => 'DKV']
);
$clsDkv->update(['major' => 'DKV']);
echo "Class ID {$clsDkv->id}: {$clsDkv->name} ({$clsDkv->major})\n";

// 3. Animasi
$clsAni = SchoolClass::firstOrCreate(
    ['name' => 'Kelas Industri Animasi'],
    ['major' => 'Animasi']
);
$clsAni->update(['major' => 'Animasi']);
echo "Class ID {$clsAni->id}: {$clsAni->name} ({$clsAni->major})\n";

echo "\n=== 2. SYNC INDUSTRY SUBJECTS ===\n";
// Teachers for industry
// Nanda Arsyia Murti (ID 71) or Bagas Pratama (ID 15) for RPL
$rplTeacher = User::where('name', 'like', '%Nanda Arsyia%')->first() ?: User::where('role', 'GURU_PENGAJAR')->first();
// Khairul Arifin (KA, ID 57) or Nurhadi Yahya (NY, ID 72) for DKV
$dkvTeacher = User::where('name', 'like', '%Khairul Arifin%')->first() ?: User::where('name', 'like', '%Nurhadi Yahya%')->first();
// Fonny Krisnawulan (FK, ID 45) or Miftahul Zannah (MZ, ID 64) for Animasi
$aniTeacher = User::where('name', 'like', '%Fonny Krisnawulan%')->first() ?: User::where('name', 'like', '%Miftahul Zannah%')->first();

$subRpl = Subject::firstOrCreate(
    ['code' => 'IND-RPL'],
    [
        'name' => 'Kelas Industri RPL - Pemrograman & Web Mobile',
        'major' => 'RPL',
        'teacher_id' => $rplTeacher ? $rplTeacher->id : null,
    ]
);

$subDkv = Subject::firstOrCreate(
    ['code' => 'IND-DKV'],
    [
        'name' => 'Kelas Industri DKV - Creative & Digital Branding',
        'major' => 'DKV',
        'teacher_id' => $dkvTeacher ? $dkvTeacher->id : null,
    ]
);

$subAni = Subject::firstOrCreate(
    ['code' => 'IND-ANI'],
    [
        'name' => 'Kelas Industri Animasi - 3D Animation & Motion Graphic',
        'major' => 'Animasi',
        'teacher_id' => $aniTeacher ? $aniTeacher->id : null,
    ]
);

echo "Subjects created/verified:\n";
echo "- {$subRpl->code}: {$subRpl->name}\n";
echo "- {$subDkv->code}: {$subDkv->name}\n";
echo "- {$subAni->code}: {$subAni->name}\n";

echo "\n=== 3. SYNC INDUSTRY SCHEDULES (SELASA 13:15 - 15:00) ===\n";
// Clean old industry schedules for these 3 classes
Schedule::whereIn('class_id', [$clsRpl->id, $clsDkv->id, $clsAni->id])->delete();

// Schedule 1: RPL
$schRpl = Schedule::create([
    'class_id' => $clsRpl->id,
    'subject_id' => $subRpl->id,
    'teacher_id' => $rplTeacher ? $rplTeacher->id : 15,
    'day' => 'Selasa',
    'start_time' => '13:15',
    'end_time' => '15:00',
    'room' => 'Lab RPL Industri (R.409)',
]);
echo "Created Schedule: {$schRpl->day} {$schRpl->start_time}-{$schRpl->end_time} | {$clsRpl->name} | {$subRpl->name}\n";

// Schedule 2: DKV
$schDkv = Schedule::create([
    'class_id' => $clsDkv->id,
    'subject_id' => $subDkv->id,
    'teacher_id' => $dkvTeacher ? $dkvTeacher->id : 57,
    'day' => 'Selasa',
    'start_time' => '13:15',
    'end_time' => '15:00',
    'room' => 'Lab DKV Industri (R.206)',
]);
echo "Created Schedule: {$schDkv->day} {$schDkv->start_time}-{$schDkv->end_time} | {$clsDkv->name} | {$subDkv->name}\n";

// Schedule 3: Animasi
$schAni = Schedule::create([
    'class_id' => $clsAni->id,
    'subject_id' => $subAni->id,
    'teacher_id' => $aniTeacher ? $aniTeacher->id : 45,
    'day' => 'Selasa',
    'start_time' => '13:15',
    'end_time' => '15:00',
    'room' => 'Lab Animasi Industri (R.302)',
]);
echo "Created Schedule: {$schAni->day} {$schAni->start_time}-{$schAni->end_time} | {$clsAni->name} | {$subAni->name}\n";

echo "\n=== ALL CLASSES NOW IN DATABASE ===\n";
foreach (SchoolClass::orderBy('name')->get() as $c) {
    echo "ID: {$c->id} | {$c->name} | {$c->major} | Jadwal: " . $c->schedules()->count() . "\n";
}

echo "\nSUCCESSFULLY SYNCED INDUSTRY CLASSES & SCHEDULES!\n";
