<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "--- ATTENDANCE RECORDS ---\n";
foreach (\Modules\Attendance\Models\Attendance::with('employee.user')->get() as $a) {
    echo ($a->employee->user->name ?? 'N/A') . " | Date: " . $a->date->format('Y-m-d') . " | In: " . ($a->clock_in ? $a->clock_in->format('H:i') : 'None') . " | Out: " . ($a->clock_out ? $a->clock_out->format('H:i') : 'None') . " | Hours: {$a->total_hours} | Status: {$a->status}\n";
}
