<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Employee\Models\Department;
use Modules\Leave\Models\Holiday;
use Modules\Leave\Models\LeaveType;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index(Request $request, $module = null)
    {
        $user = auth()->user() ?? \App\Models\User::first();
        $employee = $user ? \Modules\Employee\Models\Employee::where('user_id', $user->id)->first() : null;
        $userRole = session('current_role', $employee->system_role ?? 'Employee');
        $isAdminOrHr = in_array($userRole, ['HR Lead', 'Super (Admin)', 'Super Admin']);

        $validModules = ['general', 'leave', 'organization', 'roles', 'attendance', 'payroll', 'recruitment', 'performance', 'system', 'profile'];
        
        $activeModule = $module ?? $request->query('module', ($isAdminOrHr ? 'general' : 'profile'));
        if (!in_array($activeModule, $validModules)) {
            $activeModule = $isAdminOrHr ? 'general' : 'profile';
        }
        
        if (!$isAdminOrHr && $activeModule !== 'profile') {
            $activeModule = 'profile';
        }

        $settingsRaw = DB::table('settings')->get()->pluck('value', 'key')->toArray();

        $departments = Department::all();
        $customHolidays = Holiday::where('type', 'Company')->get();
        $leaveTypes = LeaveType::orderBy('id')->get();

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

        // Modular Settings Configuration Registry
        $modulesList = [
            'general' => [
                'title' => 'General Organization',
                'desc' => 'Company profile, timezone, currency, fiscal year & official holidays',
                'icon' => 'ph-buildings',
                'color' => 'text-blue-600 dark:text-blue-400',
                'badge' => 'Core'
            ],
            'leave' => [
                'title' => 'Leave Management',
                'desc' => 'Sri Lanka S&O Act Quotas, Short Leave (2/mo), Half-days & Weekends',
                'icon' => 'ph-calendar-check',
                'color' => 'text-emerald-600 dark:text-emerald-400',
                'badge' => 'Policy'
            ],
            'organization' => [
                'title' => 'Departments & Structure',
                'desc' => 'Company departments, department codes, HOD leadership',
                'icon' => 'ph-tree-structure',
                'color' => 'text-indigo-600 dark:text-indigo-400',
                'badge' => count($departments) . ' Depts'
            ],
            'roles' => [
                'title' => 'Roles & Access Control',
                'desc' => 'RBAC Matrix, permission privileges, module registries',
                'icon' => 'ph-shield-check',
                'color' => 'text-purple-600 dark:text-purple-400',
                'badge' => 'RBAC'
            ],
            'attendance' => [
                'title' => 'Attendance & Shifts',
                'desc' => 'Standard working hours, grace period, overtime policies & clock-in',
                'icon' => 'ph-clock-countdown',
                'color' => 'text-cyan-600 dark:text-cyan-400',
                'badge' => 'Shifts'
            ],
            'payroll' => [
                'title' => 'Payroll & Statutory Tax',
                'desc' => 'EPF (8%/12%), ETF (3%), APIT Tax brackets, salary cutoff cycles',
                'icon' => 'ph-money',
                'color' => 'text-amber-600 dark:text-amber-400',
                'badge' => 'EPF/ETF'
            ],
            'recruitment' => [
                'title' => 'Recruitment & ATS',
                'desc' => 'Candidate hiring pipeline stages, portal visibility & auto-emails',
                'icon' => 'ph-briefcase',
                'color' => 'text-pink-600 dark:text-pink-400',
                'badge' => 'ATS'
            ],
            'performance' => [
                'title' => 'Performance & Appraisal',
                'desc' => 'Appraisal cycles, evaluation rating scales, self-appraisals',
                'icon' => 'ph-award',
                'color' => 'text-orange-600 dark:text-orange-400',
                'badge' => 'KPIs'
            ],
            'system' => [
                'title' => 'System & Developer Engine',
                'desc' => 'Developer mode, debug logging, cache flush & health diagnostics',
                'icon' => 'ph-cpu',
                'color' => 'text-slate-600 dark:text-slate-400',
                'badge' => 'Engine'
            ],
            'profile' => [
                'title' => 'My Account & Security',
                'desc' => 'Personal details, emergency contact, qualifications & password',
                'icon' => 'ph-user-gear',
                'color' => 'text-rose-600 dark:text-rose-400',
                'badge' => 'Personal'
            ]
        ];

        // Overlay custom module metadata overrides if saved in settings table
        foreach ($modulesList as $mKey => &$mInfo) {
            if (!empty($settingsRaw['module_title_' . $mKey])) {
                $mInfo['title'] = $settingsRaw['module_title_' . $mKey];
            }
            if (!empty($settingsRaw['module_desc_' . $mKey])) {
                $mInfo['desc'] = $settingsRaw['module_desc_' . $mKey];
            }
            if (!empty($settingsRaw['module_badge_' . $mKey])) {
                $mInfo['badge'] = $settingsRaw['module_badge_' . $mKey];
            }
        }
        unset($mInfo);

        return view('pages.settings', compact(
            'user', 
            'employee', 
            'settingsRaw', 
            'departments', 
            'customHolidays', 
            'leaveTypes',
            'moduleStatuses', 
            'rolePermissions', 
            'activeModule', 
            'modulesList', 
            'isAdminOrHr', 
            'userRole'
        ));
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

        return redirect()->route('settings.index', ['module' => 'profile'])->with('success', 'Personal profile, bank details & qualifications updated successfully!');
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

        return redirect()->route('settings.index', ['module' => 'profile'])->with('success', 'Password updated successfully!');
    }

    public function clearCache()
    {
        $this->authorizeAdminOrHr();
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');

        return redirect()->route('settings.index', ['module' => 'system'])->with('success', 'Application cache and compiled views cleared successfully!');
    }

    public function save(Request $request)
    {
        $user = auth()->user();
        $activeEmp = $user ? \Modules\Employee\Models\Employee::where('user_id', $user->id)->first() : null;
        $userRole = session('current_role', $activeEmp->system_role ?? 'Employee');
        
        if (!in_array($userRole, ['HR Lead', 'Super (Admin)', 'Super Admin'])) {
            return redirect()->back()->with('error', 'Access Denied: Administrative system settings are restricted to HR Leads and Super Admins.');
        }

        $activeModule = $request->input('active_module', 'general');

        $textKeys = [
            // General
            'company_name', 'timezone', 'base_currency', 'fiscal_year_start', 'company_address', 'contact_email', 'contact_phone',
            // Leave
            'annual_leave_max', 'casual_leave_max', 'medical_leave_max', 'short_leave_max', 'duty_leave_max', 'lieu_leave_max',
            // Attendance
            'standard_work_hours', 'shift_start_time', 'shift_end_time', 'grace_period_minutes', 'overtime_rate_multiplier',
            // Payroll
            'epf_employee_rate', 'epf_employer_rate', 'etf_employer_rate', 'apit_tax_threshold', 'salary_cutoff_day', 'pay_frequency',
            // Recruitment
            'recruitment_auto_email', 'interview_feedback_deadline_days',
            // Performance
            'appraisal_frequency', 'rating_scale_max', 'min_rating_for_increment',
            // System
            'developer_mode', 'maintenance_mode'
        ];

        foreach ($textKeys as $k) {
            if ($request->has($k)) {
                DB::table('settings')->updateOrInsert(['key' => $k], ['value' => $request->input($k), 'updated_at' => now()]);
            }
        }

        if (in_array($activeModule, ['leave', 'general'])) {
            foreach (['auto_sync_holidays', 'allow_half_day', 'weekend_saturday_off', 'weekend_sunday_off'] as $cbKey) {
                if ($request->has('submitted_' . $cbKey) || $request->has($cbKey)) {
                    $val = $request->has($cbKey) ? '1' : '0';
                    DB::table('settings')->updateOrInsert(['key' => $cbKey], ['value' => $val, 'updated_at' => now()]);
                }
            }
        }

        if ($activeModule === 'attendance') {
            foreach (['allow_remote_clock_in', 'require_geolocation', 'auto_overtime_calculation'] as $cbKey) {
                $val = $request->has($cbKey) ? '1' : '0';
                DB::table('settings')->updateOrInsert(['key' => $cbKey], ['value' => $val, 'updated_at' => now()]);
            }
        }

        if ($activeModule === 'payroll') {
            foreach (['auto_deduct_nopay', 'generate_payslip_pdf'] as $cbKey) {
                $val = $request->has($cbKey) ? '1' : '0';
                DB::table('settings')->updateOrInsert(['key' => $cbKey], ['value' => $val, 'updated_at' => now()]);
            }
        }

        if ($activeModule === 'recruitment') {
            foreach (['career_portal_public', 'send_rejection_emails'] as $cbKey) {
                $val = $request->has($cbKey) ? '1' : '0';
                DB::table('settings')->updateOrInsert(['key' => $cbKey], ['value' => $val, 'updated_at' => now()]);
            }
        }

        if ($activeModule === 'performance') {
            foreach (['self_appraisal_enabled', 'peer_reviews_enabled'] as $cbKey) {
                $val = $request->has($cbKey) ? '1' : '0';
                DB::table('settings')->updateOrInsert(['key' => $cbKey], ['value' => $val, 'updated_at' => now()]);
            }
        }

        if ($activeModule === 'system') {
            foreach (['developer_mode', 'debug_logging_enabled'] as $cbKey) {
                $val = $request->has($cbKey) ? '1' : '0';
                DB::table('settings')->updateOrInsert(['key' => $cbKey], ['value' => $val, 'updated_at' => now()]);
            }
        }

        if ($activeModule === 'roles') {
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
                $val = $request->has($reqKey) ? '1' : '0';
                DB::table('settings')->updateOrInsert(['key' => $reqKey], ['value' => $val, 'updated_at' => now()]);
            }

            $statusesPath = base_path('modules_statuses.json');
            file_put_contents($statusesPath, json_encode($statuses, JSON_PRETTY_PRINT));
        }

        $moduleTitle = ucfirst($activeModule);
        return redirect()->route('settings.index', ['module' => $activeModule])->with('success', "{$moduleTitle} module configurations updated successfully!");
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

    public function updateModule(Request $request, $key)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'module_title' => 'required|string|max:100',
            'module_desc' => 'nullable|string|max:255',
            'module_badge' => 'nullable|string|max:30',
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'module_title_' . $key],
            ['value' => $request->module_title, 'updated_at' => now()]
        );

        if ($request->filled('module_desc')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'module_desc_' . $key],
                ['value' => $request->module_desc, 'updated_at' => now()]
            );
        }

        if ($request->filled('module_badge')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'module_badge_' . $key],
                ['value' => $request->module_badge, 'updated_at' => now()]
            );
        }

        return redirect()->back()->with('success', "Module '{$request->module_title}' updated successfully!");
    }

    public function storeLeaveType(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:leave_types,code',
            'days' => 'required|numeric|min:0',
            'is_paid' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:255',
        ]);

        $leaveType = LeaveType::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'days' => (int) $request->days,
            'default_quota' => (int) $request->days,
            'is_paid' => $request->has('is_paid') ? (bool) $request->is_paid : true,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', "Leave type '{$leaveType->name}' created successfully!");
    }

    public function updateLeaveType(Request $request, $id)
    {
        $this->authorizeAdminOrHr();

        $leaveType = LeaveType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:leave_types,code,' . $leaveType->id,
            'days' => 'required|numeric|min:0',
            'is_paid' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:255',
        ]);

        $leaveType->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'days' => (int) $request->days,
            'default_quota' => (int) $request->days,
            'is_paid' => $request->boolean('is_paid'),
            'is_active' => $request->boolean('is_active'),
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', "Leave type '{$leaveType->name}' updated successfully!");
    }

    public function toggleLeaveType($id)
    {
        $this->authorizeAdminOrHr();

        $leaveType = LeaveType::findOrFail($id);
        $leaveType->is_active = !$leaveType->is_active;
        $leaveType->save();

        $statusStr = $leaveType->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Leave type '{$leaveType->name}' {$statusStr} successfully!");
    }

    public function destroyLeaveType($id)
    {
        $this->authorizeAdminOrHr();

        $leaveType = LeaveType::findOrFail($id);
        
        // Prevent deleting core statutory leave types
        if (in_array(strtoupper($leaveType->code), ['ANNUAL', 'CASUAL', 'SHORT', 'MEDICAL'])) {
            return redirect()->back()->with('error', "Core statutory leave type '{$leaveType->name}' cannot be deleted. You can deactivate it instead.");
        }

        $typeName = $leaveType->name;
        $leaveType->delete();

        return redirect()->back()->with('success', "Leave type '{$typeName}' deleted successfully.");
    }
}
