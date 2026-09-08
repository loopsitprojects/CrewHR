<form action="{{ route('settings.save') }}" method="POST" class="space-y-6">
    @csrf
    <input type="hidden" name="active_module" value="roles">

    <!-- 1. System Roles & Module Access Control Matrix -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center border border-purple-100 dark:border-purple-900/60">
                    <i class="ph ph-shield-check text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">System Role Module & Sub-Module Access Control (RBAC)</h2>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Configure role-based access permissions across all main modules and sub-modules</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="addModuleModal = true" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm cursor-pointer transition-all">
                    <i class="ph ph-plus text-sm"></i> Register Module
                </button>
                <span class="bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-purple-200 dark:border-purple-800">RBAC Matrix</span>
            </div>
        </div>

        <!-- Role Access Matrix Table -->
        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Module Category & Sub-Modules</th>
                        <th class="p-3 text-center text-slate-900 dark:text-white">Global Enable (ON/OFF)</th>
                        <th class="p-3 text-center text-blue-600 dark:text-blue-400">Super (Admin)</th>
                        <th class="p-3 text-center text-purple-600 dark:text-purple-400">HR Lead</th>
                        <th class="p-3 text-center text-amber-600 dark:text-amber-400">HOD / Manager</th>
                        <th class="p-3 text-center text-emerald-600 dark:text-emerald-400">Employee</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-bold text-slate-800 dark:text-slate-200">
                    @php
                        $moduleGroups = [
                            [
                                'title' => 'Leave Management',
                                'slug' => 'leave-management',
                                'icon' => 'ph-calendar',
                                'color' => 'text-blue-600 dark:text-blue-400',
                                'master_status' => ($moduleStatuses['Leave'] ?? true) && ($moduleStatuses['Approvals'] ?? true) && ($moduleStatuses['Analytics'] ?? true),
                                'submodules' => [
                                    'Leave' => [
                                        'name' => 'My Leaves & Calendar', 
                                        'desc' => 'Personal Leave Application & Sri Lanka Gazette Calendar',
                                        'toggle' => 'module_leave_enabled',
                                        'status' => $moduleStatuses['Leave'] ?? true
                                    ],
                                    'Approvals' => [
                                        'name' => 'Leave Approvals Workflow', 
                                        'desc' => 'Team & HR Leave Request Approvals Screen',
                                        'toggle' => 'module_approvals_enabled',
                                        'status' => $moduleStatuses['Approvals'] ?? true
                                    ],
                                    'Analytics' => [
                                        'name' => 'Reports & Leave Analytics', 
                                        'desc' => 'Organizational Leave & Attendance Reports',
                                        'toggle' => 'module_analytics_enabled',
                                        'status' => $moduleStatuses['Analytics'] ?? true
                                    ],
                                ]
                            ],
                            [
                                'title' => 'Employees & Workforce',
                                'slug' => 'employees-workforce',
                                'icon' => 'ph-users',
                                'color' => 'text-indigo-600 dark:text-indigo-400',
                                'master_status' => ($moduleStatuses['Employee'] ?? true) && ($moduleStatuses['Attendance'] ?? true),
                                'submodules' => [
                                    'Employee' => [
                                        'name' => 'Employee Directory & Structure', 
                                        'desc' => 'Employee Profiles, Directory & Org Chart',
                                        'toggle' => 'module_employee_enabled',
                                        'status' => $moduleStatuses['Employee'] ?? true
                                    ],
                                    'Attendance' => [
                                        'name' => 'Attendance Tracking', 
                                        'desc' => 'Clock-In/Clock-Out & Attendance Logs',
                                        'toggle' => 'module_attendance_enabled',
                                        'status' => $moduleStatuses['Attendance'] ?? true
                                    ],
                                ]
                            ],
                            [
                                'title' => 'Payroll & Payslips',
                                'slug' => 'payroll-payslips',
                                'icon' => 'ph-file-text',
                                'color' => 'text-emerald-600 dark:text-emerald-400',
                                'master_status' => $moduleStatuses['Payroll'] ?? true,
                                'submodules' => [
                                    'Payroll' => [
                                        'name' => 'Payroll & Salary Management', 
                                        'desc' => 'Salary Records, Payslips, APIT Tax & EPF/ETF',
                                        'toggle' => 'module_payroll_enabled',
                                        'status' => $moduleStatuses['Payroll'] ?? true
                                    ],
                                ]
                            ],
                            [
                                'title' => 'Recruitment (ATS)',
                                'slug' => 'recruitment-ats',
                                'icon' => 'ph-briefcase',
                                'color' => 'text-purple-600 dark:text-purple-400',
                                'master_status' => $moduleStatuses['Recruitment'] ?? true,
                                'submodules' => [
                                    'Recruitment' => [
                                        'name' => 'Recruitment ATS Pipeline', 
                                        'desc' => 'Job Postings, Applicants & Candidate ATS Pipeline',
                                        'toggle' => 'module_recruitment_enabled',
                                        'status' => $moduleStatuses['Recruitment'] ?? true
                                    ],
                                ]
                            ],
                            [
                                'title' => 'Performance Management',
                                'slug' => 'performance-management',
                                'icon' => 'ph-award',
                                'color' => 'text-amber-600 dark:text-amber-400',
                                'master_status' => $moduleStatuses['Performance'] ?? true,
                                'submodules' => [
                                    'Performance' => [
                                        'name' => 'Appraisal System', 
                                        'desc' => 'Performance Appraisals & Goal Tracking',
                                        'toggle' => 'module_performance_enabled',
                                        'status' => $moduleStatuses['Performance'] ?? true
                                    ],
                                ]
                            ]
                        ];
                        $matrixRoles = ['Super Admin', 'HR Lead', 'Manager', 'Employee'];
                    @endphp

                    @foreach($moduleGroups as $group)
                        <tr class="bg-slate-100/90 dark:bg-slate-900/90 border-t border-b border-slate-200 dark:border-slate-800 font-extrabold">
                            <td class="p-3" colspan="6">
                                <div class="flex items-center gap-2">
                                    <i class="ph {{ $group['icon'] }} {{ $group['color'] }} text-base"></i>
                                    <span class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-xs">{{ $group['title'] }} Suite</span>
                                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ count($group['submodules']) }} Sub-modules
                                    </span>
                                </div>
                            </td>
                        </tr>

                        @foreach($group['submodules'] as $subKey => $subData)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-3 pl-8">
                                    <div class="font-black text-slate-900 dark:text-white text-xs">{{ $subData['name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-semibold">{{ $subData['desc'] }}</div>
                                </td>

                                <!-- Global Toggle -->
                                <td class="p-3 text-center">
                                    <label class="inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="{{ $subData['toggle'] }}" value="1" {{ $subData['status'] ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-8 h-4.5 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-blue-600 relative"></div>
                                    </label>
                                </td>

                                <!-- Role Matrix Columns -->
                                @foreach($matrixRoles as $roleName)
                                    @php
                                        $inputName = 'perm_' . str_replace([' ', '(', ')'], '_', $roleName) . '_' . $subKey;
                                        $isChecked = $rolePermissions[$roleName][$subKey] ?? true;
                                    @endphp
                                    <td class="p-3 text-center">
                                        <input type="checkbox" name="{{ $inputName }}" value="1" {{ $isChecked ? 'checked' : '' }}
                                               class="rounded border-slate-300 text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Save Actions -->
    <div class="flex justify-end pt-2">
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-purple-500/20 transition-all cursor-pointer">
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Save Roles & Access Matrix</span>
        </button>
    </div>
</form>
