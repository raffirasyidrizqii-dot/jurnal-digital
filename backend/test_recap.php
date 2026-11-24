<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $u = App\Models\User::where('role', 'GURU_PENGAJAR')->first();
    echo "Testing user: " . ($u ? $u->name : 'none') . "\n";
    $req = Illuminate\Http\Request::create('/api/attendance/semester-recap?class_id=1&term=2026-1', 'GET');
    $req->setUserResolver(fn() => $u);
    $controller = app(App\Http\Controllers\Api\AttendanceController::class);
    $res = $controller->semesterRecap($req);
    echo "Status: " . $res->getStatusCode() . "\n";
    echo "Content preview: " . substr($res->getContent(), 0, 150) . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
