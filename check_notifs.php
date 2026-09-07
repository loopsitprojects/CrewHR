<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "HR Notifications count: " . \App\Models\HrNotification::count() . "\n";
echo "HR Notifications items:\n";
foreach (\App\Models\HrNotification::all() as $n) {
    echo "ID: {$n->id} | UserID: {$n->user_id} | Type: {$n->type} | Title: {$n->title} | Read: {$n->is_read}\n";
}

echo "\nUsers & Employees:\n";
foreach (\App\Models\User::with('employee')->get() as $u) {
    $role = $u->employee->system_role ?? 'NO_EMP';
    echo "User ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | System Role: {$role}\n";
}
