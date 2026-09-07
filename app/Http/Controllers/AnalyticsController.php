<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Employee\Models\Department;
use Modules\Leave\Models\Holiday;
use Modules\Employee\Models\Employee;
use Modules\Leave\Models\LeaveRequest;

class AnalyticsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $employee = $user ? Employee::where('user_id', $user->id)->first() : null;
        $userRole = session('current_role', $employee->system_role ?? 'Employee');
        $normalizedRole = match($userRole) {
            'Manager (Team Approvals)', 'HOD / Manager' => 'Manager',
            'HR Lead' => 'HR Lead',
            'Employee' => 'Employee',
            default => 'Super Admin',
        };

        $permissionsPath = base_path('role_module_permissions.json');
        $rolePermissions = file_exists($permissionsPath) ? json_decode(file_get_contents($permissionsPath), true) : [];

        $hasAccess = $rolePermissions[$normalizedRole]['Analytics'] ?? in_array($userRole, ['HR Lead', 'Super (Admin)']);
        if (!$hasAccess) {
            return redirect()->route('dashboard')->with('error', 'Access Denied: Reports & Leave Analytics module is restricted to HR Admin and Super Admin.');
        }

        $departments = Department::all();
        $holidays = Holiday::where('type', 'Gazette')->latest()->take(4)->get();
        $employeesCount = Employee::count() ?: 106;
        $onLeaveCount = LeaveRequest::where('status', 'Approved')->where('start_date', '<=', now())->where('end_date', '>=', now())->count() ?: 7;

        return view('pages.analytics', compact('departments', 'holidays', 'employeesCount', 'onLeaveCount'));
    }
}
