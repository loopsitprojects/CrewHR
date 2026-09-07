<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Employee\Models\Department;
use Modules\Leave\Models\Holiday;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user() ?? \App\Models\User::first();
        $employee = $user ? \Modules\Employee\Models\Employee::where('user_id', $user->id)->first() : null;

        $settingsRaw = DB::table('settings')->get()->pluck('value', 'key')->toArray();

        $departments = Department::all();
        $customHolidays = Holiday::where('type', 'Company')->get();

        $statusesPath = base_path('modules_statuses.json');
        $moduleStatuses = file_exists($statusesPath) ? json_decode(file_get_contents($statusesPath), true) : [
            'Employee' => true,
            'Leave' => true,
            'Organization' => true,
            'Attendance' => true,
            'Payroll' => true,
            'Recruitment' => true,
            'Performance' => true,
        ];

        $permissionsPath = base_path('role_module_permissions.json');
        $rolePermissions = file_exists($permissionsPath) ? json_decode(file_get_contents($permissionsPath), true) : [
            'Super Admin' => ['Employee' => true, 'Leave' => true, 'Approvals' => true, 'Analytics' => true, 'Attendance' => true, 'Payroll' => true, 'Recruitment' => true, 'Performance' => true],
            'HR Lead' => ['Employee' => true, 'Leave' => true, 'Approvals' => true, 'Analytics' => true, 'Attendance' => true, 'Payroll' => true, 'Recruitment' => true, 'Performance' => true],
            'Manager' => ['Employee' => true, 'Leave' => true, 'Approvals' => true, 'Analytics' => false, 'Attendance' => true, 'Payroll' => false, 'Recruitment' => false, 'Performance' => true],
            'Employee' => ['Employee' => false, 'Leave' => true, 'Approvals' => false, 'Analytics' => false, 'Attendance' => true, 'Payroll' => false, 'Recruitment' => false, 'Performance' => true],
        ];

        return view('pages.settings', compact('user', 'employee', 'settingsRaw', 'departments', 'customHolidays', 'moduleStatuses', 'rolePermissions'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        if (!$user) return redirect()->back()->with('error', 'User not authenticated');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:50',
            'emergency_contact' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'account_holder_name' => 'nullable|string|max:255',
            'higher_education' => 'nullable|string',
            'professional_qualifications' => 'nullable|string',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $employee = \Modules\Employee\Models\Employee::where('user_id', $user->id)->first();
        if ($employee) {
            $employee->update([
                'phone_number' => $request->phone_number,
                'emergency_contact' => $request->emergency_contact,
                'nic_passport' => $request->nic_passport ?? $employee->nic_passport,
                'bank_name' => $request->bank_name,
                'bank_branch' => $request->bank_branch,
                'account_number' => $request->account_number,
                'account_holder_name' => $request->account_holder_name,
                'higher_education' => $request->higher_education,
                'professional_qualifications' => $request->professional_qualifications,
            ]);
        }

        return redirect()->back()->with('success', 'Personal profile, bank details & qualifications updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();
        if (!$user) return redirect()->back()->with('error', 'User not authenticated');

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password does not match our records.']);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully!');
    }

    public function save(Request $request)
    {
        $user = auth()->user();
        $activeEmp = $user ? \Modules\Employee\Models\Employee::where('user_id', $user->id)->first() : null;
        $userRole = session('current_role', $activeEmp->system_role ?? 'Employee');
        
        if (!in_array($userRole, ['HR Lead', 'Super (Admin)'])) {
            return redirect()->back()->with('error', 'Access Denied: Administrative system settings are restricted to HR Leads and Super Admins.');
        }

        $textKeys = [
            'company_name', 'timezone', 'base_currency', 'fiscal_year_start',
            'annual_leave_max', 'casual_leave_max', 'medical_leave_max', 'short_leave_max',
            'duty_leave_max', 'lieu_leave_max'
        ];

        foreach ($textKeys as $k) {
            if ($request->has($k)) {
                DB::table('settings')->updateOrInsert(['key' => $k], ['value' => $request->input($k), 'updated_at' => now()]);
            }
        }

        // Save Role Access Control Permissions Matrix
        $roles = ['Super Admin', 'HR Lead', 'Manager', 'Employee'];
        $modules = ['Employee', 'Leave', 'Approvals', 'Analytics', 'Attendance', 'Payroll', 'Recruitment', 'Performance'];

        $newPermissions = [];
        foreach ($roles as $rKey) {
            foreach ($modules as $mKey) {
                $inputName = 'perm_' . str_replace([' ', '(', ')'], '_', $rKey) . '_' . $mKey;
                $newPermissions[$rKey][$mKey] = $request->has($inputName);
            }
        }

        $permissionsPath = base_path('role_module_permissions.json');
        file_put_contents($permissionsPath, json_encode($newPermissions, JSON_PRETTY_PRINT));
        DB::table('settings')->updateOrInsert(['key' => 'role_permissions'], ['value' => json_encode($newPermissions), 'updated_at' => now()]);

        if ($userRole === 'HR Lead') {
            foreach (['auto_sync_holidays', 'allow_half_day', 'weekend_saturday_off', 'weekend_sunday_off'] as $cbKey) {
                $val = $request->has($cbKey) ? '1' : '0';
                DB::table('settings')->updateOrInsert(['key' => $cbKey], ['value' => $val, 'updated_at' => now()]);
            }
            return redirect()->back()->with('success', 'HR Admin settings and role permissions saved successfully!');
        }

        $checkboxKeys = [
            'auto_sync_holidays', 'allow_half_day', 'weekend_saturday_off', 'weekend_sunday_off',
            'leave_management_enabled', 'appraisal_system_enabled', 'developer_mode',
            'module_employee_enabled', 'module_leave_enabled', 'module_approvals_enabled', 'module_analytics_enabled',
            'module_attendance_enabled', 'module_payroll_enabled', 'module_recruitment_enabled', 'module_performance_enabled',
            'module_organization_enabled'
        ];

        foreach ($checkboxKeys as $cbKey) {
            $val = $request->has($cbKey) ? '1' : '0';
            DB::table('settings')->updateOrInsert(['key' => $cbKey], ['value' => $val, 'updated_at' => now()]);
        }

        // Sync modules_statuses.json
        $moduleMap = [
            'Employee' => 'module_employee_enabled',
            'Leave' => 'module_leave_enabled',
            'Approvals' => 'module_approvals_enabled',
            'Analytics' => 'module_analytics_enabled',
            'Attendance' => 'module_attendance_enabled',
            'Payroll' => 'module_payroll_enabled',
            'Recruitment' => 'module_recruitment_enabled',
            'Performance' => 'module_performance_enabled',
            'Organization' => 'module_organization_enabled',
        ];

        $statuses = [];
        foreach ($moduleMap as $modName => $reqKey) {
            $statuses[$modName] = $request->has($reqKey);
        }

        $statusesPath = base_path('modules_statuses.json');
        file_put_contents($statusesPath, json_encode($statuses, JSON_PRETTY_PRINT));

        return redirect()->back()->with('success', 'System settings, module toggles & role access matrix saved successfully!');
    }

    public function switchRole(Request $request)
    {
        $role = $request->input('role', 'Super (Admin)');
        session(['current_role' => $role]);

        return redirect()->back()->with('success', "Role switched to {$role}!");
    }

    private function authorizeAdminOrHr()
    {
        $user = auth()->user();
        $activeEmp = $user ? \Modules\Employee\Models\Employee::where('user_id', $user->id)->first() : null;
        $userRole = session('current_role', $activeEmp->system_role ?? 'Employee');
        
        if (!in_array($userRole, ['HR Lead', 'Super (Admin)', 'Super Admin'])) {
            abort(403, 'Access Denied: Only HR Lead and Super Admin accounts can perform administrative CRUD operations.');
        }
    }

    public function storeDepartment(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate(['name' => 'required|string', 'code' => 'required|string']);

        Department::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'hod_name' => $request->hod_name ?? 'Super Admin',
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Department added successfully!');
    }

    public function updateDepartment(Request $request, $id)
    {
        $this->authorizeAdminOrHr();

        $request->validate(['name' => 'required|string', 'code' => 'required|string']);

        $dept = Department::findOrFail($id);
        $dept->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'hod_name' => $request->hod_name ?? $dept->hod_name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Department updated successfully!');
    }

    public function destroyDepartment($id)
    {
        $this->authorizeAdminOrHr();

        Department::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Department removed.');
    }

    public function storeModule(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'module_key' => 'required|string|max:50',
            'module_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $modKey = ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', $request->module_key));
        
        $statusesPath = base_path('modules_statuses.json');
        $statuses = file_exists($statusesPath) ? json_decode(file_get_contents($statusesPath), true) : [];
        $statuses[$modKey] = true;
        file_put_contents($statusesPath, json_encode($statuses, JSON_PRETTY_PRINT));

        $permissionsPath = base_path('role_module_permissions.json');
        $permissions = file_exists($permissionsPath) ? json_decode(file_get_contents($permissionsPath), true) : [];
        foreach (['Super Admin', 'HR Lead', 'Manager', 'Employee'] as $role) {
            if (!isset($permissions[$role])) $permissions[$role] = [];
            $permissions[$role][$modKey] = in_array($role, ['Super Admin', 'HR Lead']);
        }
        file_put_contents($permissionsPath, json_encode($permissions, JSON_PRETTY_PRINT));

        return redirect()->back()->with('success', "Module '{$request->module_name}' created successfully!");
    }

    public function destroyModule($modKey)
    {
        $this->authorizeAdminOrHr();

        $statusesPath = base_path('modules_statuses.json');
        $statuses = file_exists($statusesPath) ? json_decode(file_get_contents($statusesPath), true) : [];
        if (isset($statuses[$modKey])) {
            unset($statuses[$modKey]);
            file_put_contents($statusesPath, json_encode($statuses, JSON_PRETTY_PRINT));
        }

        $permissionsPath = base_path('role_module_permissions.json');
        $permissions = file_exists($permissionsPath) ? json_decode(file_get_contents($permissionsPath), true) : [];
        foreach ($permissions as $role => &$mods) {
            unset($mods[$modKey]);
        }
        file_put_contents($permissionsPath, json_encode($permissions, JSON_PRETTY_PRINT));

        return redirect()->back()->with('success', "Module '{$modKey}' deleted successfully.");
    }
}
