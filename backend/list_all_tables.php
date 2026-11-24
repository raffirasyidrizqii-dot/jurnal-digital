<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = Illuminate\Support\Facades\DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name;");
foreach ($tables as $t) {
    $name = $t->name;
    try {
        $cnt = Illuminate\Support\Facades\DB::table($name)->count();
        echo str_pad($name, 25) . ": " . $cnt . " rows\n";
    } catch (\Throwable $e) {
        echo str_pad($name, 25) . ": ERROR (" . $e->getMessage() . ")\n";
    }
}
