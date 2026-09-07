<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Employees:\n";
foreach (\Modules\Employee\Models\Employee::with(['user', 'reportingPerson.user'])->get() as $e) {
    $boss = $e->reportingPerson ? $e->reportingPerson->user->name : 'NONE';
    echo "Emp ID: {$e->id} | User ID: {$e->user_id} | Name: {$e->user->name} | SystemRole: {$e->system_role} | ReportingTo: {$boss}\n";
}

echo "\nLeave Requests:\n";
foreach (\Modules\Leave\Models\LeaveRequest::with(['employee.user', 'coveringEmployee.user', 'managerEmployee.user'])->get() as $l) {
    $emp = $l->employee->user->name ?? 'Unknown';
    $cov = $l->coveringEmployee->user->name ?? 'None';
    $mgr = $l->managerEmployee->user->name ?? 'None';
    echo "Req ID: {$l->id} | ReqNo: {$l->req_number} | Emp: {$emp} | Status: {$l->status} | Covering: {$cov} ({$l->covering_status}) | Manager: {$mgr} ({$l->manager_status})\n";
}
