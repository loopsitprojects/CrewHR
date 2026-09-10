@extends('layouts.app', ['title' => 'LOOPS HR - Leave Reports & Analytics', 'breadcrumb' => 'Reports & Analytics'])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ $activeTab }}',
    filterOpen: false,
    selectedDept: '{{ $selectedDeptId ?? '' }}',
    selectedEmp: '{{ $selectedEmpId ?? '' }}'
}">

    <!-- Top Banner & Report Switcher -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                    <i class="ph ph-chart-polar text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                        Leave Reports & Utilization Analytics
                    </h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/60">
                            {{ $selectedMonth ? ($monthsList[$selectedMonth] ?? 'Month') : 'All Months' }} {{ $selectedYear }}
                        </span>
                        <span class="text-xs font-bold text-slate-400">•</span>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            {{ $totalWorkforce }} Active Employees
                        </span>
                    </div>
                </div>
            </div>
            <p class="text-xs font-semibold text-slate-400 dark:text-slate-400 mt-2">
                Analyze department-wide leave utilization quotas, employee leave balance statements, and absenteeism trends.
            </p>
        </div>

        <!-- (Removed global header export pills as per request) -->
    </div>



    <!-- Unified Department Leave Overview & Drilldown -->
    <div class="space-y-6">

        @if($selectedEmpId && $selectedEmployeeProfile)
            <!-- 1. Selected Employee Detailed Profile & Quota Card -->
            <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-base flex items-center justify-center shadow-md">
                            {{ substr($selectedEmployeeProfile->user->name ?? 'E', 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ $selectedEmployeeProfile->user->name ?? 'N/A' }}</h3>
                                <a href="{{ route('analytics.index') }}" class="text-[11px] font-bold text-blue-600 hover:underline">
                                    &larr; Back to all
                                </a>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-400 mt-0.5">
                                <span class="font-mono text-slate-600 dark:text-slate-300 font-bold">{{ $selectedEmployeeProfile->employee_id_number }}</span>
                                <span>•</span>
                                <span>{{ $selectedEmployeeProfile->department->name ?? 'No Dept' }}</span>
                                <span>•</span>
                                <span>{{ $selectedEmployeeProfile->designation->name ?? 'Employee' }}</span>
                                <span>•</span>
                                <span class="bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 px-2 py-0.2 rounded-full text-[10px] font-extrabold">
                                    Joined: {{ $selectedEmployeeProfile->joined_date ? \Carbon\Carbon::parse($selectedEmployeeProfile->joined_date)->format('M d, Y') : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Download Buttons for this Selected Employee -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('analytics.export', ['type' => 'individual', 'format' => 'pdf', 'employee_id' => $selectedEmpId]) }}" 
                           target="_blank"
                           class="bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-rose-500/20 transition-all">
                            <i class="ph ph-file-pdf text-sm"></i>
                            <span>Download PDF Statement</span>
                        </a>
                        <a href="{{ route('analytics.export', ['type' => 'individual', 'format' => 'csv', 'employee_id' => $selectedEmpId]) }}" 
                           class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                            <i class="ph ph-file-csv text-emerald-600 text-sm"></i>
                            <span>Export CSV</span>
                        </a>
                    </div>
                </div>

                <!-- Individual Quota Balances Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 text-xs">
                    @foreach($selectedEmployeeBalances as $bal)
                        @php
                            $isShort = strtoupper($bal->leaveType->code ?? '') === 'SHORT';
                            $available = $isShort ? '2 / Month' : max(0, ($bal->allocated + $bal->carried_forward) - $bal->used);
                        @endphp
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 text-center space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">{{ $bal->leaveType->name ?? 'Leave' }}</span>
                            <div class="text-base font-black text-slate-900 dark:text-white">{{ $available }}</div>
                            <span class="text-[10px] font-semibold text-slate-400 block">{{ $bal->used }} Days Used</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Individual Leaves History Table for this Employee -->
            <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i class="ph ph-user-list text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white">Leave History & Applications</h2>
                            <p class="text-[11px] font-semibold text-slate-400">All leave requests submitted by {{ $selectedEmployeeProfile->user->name ?? 'the employee' }} in {{ $selectedYear }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                        {{ $individualLeaves->total() }} Total Applications
                    </span>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-3">Leave Type</th>
                                <th class="p-3">Date Range</th>
                                <th class="p-3 text-center">Duration</th>
                                <th class="p-3">Covering Colleague</th>
                                <th class="p-3">Reason / Project</th>
                                <th class="p-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                            @forelse($individualLeaves as $req)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-[#1e2d4d]/60 transition-colors">
                                    <td class="p-3">
                                        <span class="font-bold text-slate-900 dark:text-white">{{ $req->leaveType->name ?? 'General' }}</span>
                                        @if($req->is_short_leave)
                                            <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Short ({{ $req->short_leave_slot ?? '1.5 hrs' }})</span>
                                        @elseif($req->is_half_day)
                                            <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-cyan-50 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800">Half Day ({{ $req->half_day_slot ?? 'Half' }})</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-xs">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($req->start_date)->format('M d, Y') }}
                                            @if($req->start_date !== $req->end_date)
                                                &rarr; {{ \Carbon\Carbon::parse($req->end_date)->format('M d, Y') }}
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-medium">Applied: {{ $req->created_at ? $req->created_at->format('M d, Y') : 'N/A' }}</span>
                                    </td>
                                    <td class="p-3 text-center font-black text-slate-900 dark:text-white">
                                        {{ $req->duration ?: 1.0 }} {{ Str::plural('day', (float)$req->duration) }}
                                    </td>
                                    <td class="p-3 text-xs font-medium text-slate-600 dark:text-slate-300">
                                        {{ $req->coveringEmployee?->user?->name ?? 'None Assigned' }}
                                    </td>
                                    <td class="p-3 text-xs text-slate-500 max-w-xs truncate">
                                        {{ $req->reason ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($req->status === 'Approved')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Approved</span>
                                        @elseif($req->status === 'Rejected')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">Rejected</span>
                                        @elseif($req->status === 'Cancelled')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">Cancelled</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                                        No leave applications found for this employee.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-2">
                    {{ $individualLeaves->links() }}
                </div>
            </div>

        @elseif($selectedDeptId && $departments->first())
            @php
                $deptData = $departmentReport->first();
                $curDept = $departments->first();
            @endphp
            <!-- 2. Selected Department Detailed Overview & Employee Directory -->
            <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-base flex items-center justify-center shadow-md">
                            {{ $curDept->code ?? 'DEPT' }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ $curDept->name }} Department</h3>
                                <a href="{{ route('analytics.index') }}" class="text-[11px] font-bold text-blue-600 hover:underline">
                                    &larr; View all departments
                                </a>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-400 mt-0.5">
                                <span>HOD: <strong class="text-slate-700 dark:text-slate-200">{{ $curDept->hod_name ?? 'Super Admin' }}</strong></span>
                                <span>•</span>
                                <span>{{ $dropdownEmployees->count() }} Employees</span>
                                <span>•</span>
                                <span class="text-blue-600 dark:text-blue-400 font-bold">
                                    {{ $selectedMonth ? ($monthsList[$selectedMonth] ?? '') . ' ' : '' }}{{ $selectedYear }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Department Level Download Buttons -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('analytics.export', ['type' => 'department', 'format' => 'pdf', 'department_id' => $curDept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                           target="_blank"
                           class="bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-rose-500/20 transition-all">
                            <i class="ph ph-file-pdf text-sm"></i>
                            <span>Download Department PDF</span>
                        </a>
                        <a href="{{ route('analytics.export', ['type' => 'department', 'format' => 'csv', 'department_id' => $curDept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                           class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                            <i class="ph ph-file-csv text-emerald-600 text-sm"></i>
                            <span>Export Department CSV</span>
                        </a>
                    </div>
                </div>

                @if($deptData)
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 text-center space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Total Days Taken</span>
                            <div class="text-xl font-black text-slate-900 dark:text-white">{{ $deptData['total_days_taken'] }}</div>
                            <span class="text-[10px] text-slate-400 font-semibold">{{ $deptData['approved_count'] }} Approved Leaves</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 text-center space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Short Leaves</span>
                            <div class="text-xl font-black text-amber-600 dark:text-amber-400">{{ $deptData['total_short_leaves'] }}</div>
                            <span class="text-[10px] text-slate-400 font-semibold">1.5h slots</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 text-center space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Total Quota</span>
                            <div class="text-xl font-black text-slate-900 dark:text-white">{{ $deptData['total_quota'] }}</div>
                            <span class="text-[10px] text-slate-400 font-semibold">Days allocated</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 text-center space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Utilization</span>
                            <div class="text-xl font-black {{ $deptData['utilization_pct'] > 80 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $deptData['utilization_pct'] }}%</div>
                            <span class="text-[10px] text-slate-400 font-semibold">Quota Consumed</span>
                        </div>
                    </div>
                @endif

                <!-- Department Employees Directory Table with Direct Download Buttons -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Employees in {{ $curDept->name }}
                        </h4>
                        <span class="text-xs font-bold text-slate-400">
                            Download employee statements directly or view history
                        </span>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="p-3">Employee</th>
                                    <th class="p-3">Designation</th>
                                    <th class="p-3 text-center">EPF / Emp ID</th>
                                    <th class="p-3 text-center">Joined Date</th>
                                    <th class="p-3 text-right">Individual Statement</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                                @forelse($dropdownEmployees as $emp)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-[#1e2d4d]/60 transition-colors">
                                        <td class="p-3">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-bold flex items-center justify-center text-xs">
                                                    {{ substr($emp->user->name ?? 'E', 0, 1) }}
                                                </div>
                                                <div>
                                                    <a href="{{ route('analytics.index', ['department_id' => $curDept->id, 'employee_id' => $emp->id]) }}" class="font-bold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                                        {{ $emp->user->name ?? 'N/A' }}
                                                    </a>
                                                    <span class="text-[10px] text-slate-400 block">{{ $emp->user->email ?? '' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3 text-slate-600 dark:text-slate-300">
                                            {{ $emp->designation->name ?? 'Employee' }}
                                        </td>
                                        <td class="p-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                            {{ $emp->employee_id_number }}
                                        </td>
                                        <td class="p-3 text-center text-slate-500">
                                            {{ $emp->joined_date ? \Carbon\Carbon::parse($emp->joined_date)->format('M d, Y') : '—' }}
                                        </td>
                                        <td class="p-3 text-right">
                                            <div class="inline-flex items-center gap-1.5 justify-end">
                                                <!-- Direct Individual PDF Download -->
                                                <a href="{{ route('analytics.export', ['type' => 'individual', 'format' => 'pdf', 'employee_id' => $emp->id]) }}" 
                                                   target="_blank"
                                                   title="Download {{ $emp->user->name ?? 'Employee' }} PDF Statement"
                                                   class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/50 transition-all flex items-center gap-1 text-[10px] font-bold">
                                                    <i class="ph ph-file-pdf text-xs"></i>
                                                    <span>PDF</span>
                                                </a>

                                                <!-- Direct Individual CSV Download -->
                                                <a href="{{ route('analytics.export', ['type' => 'individual', 'format' => 'csv', 'employee_id' => $emp->id]) }}" 
                                                   title="Export {{ $emp->user->name ?? 'Employee' }} CSV"
                                                   class="p-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition-all flex items-center gap-1 text-[10px] font-bold">
                                                    <i class="ph ph-file-csv text-emerald-600 text-xs"></i>
                                                    <span>CSV</span>
                                                </a>

                                                <!-- View Statement -->
                                                <a href="{{ route('analytics.index', ['department_id' => $curDept->id, 'employee_id' => $emp->id]) }}" 
                                                   class="text-blue-600 dark:text-blue-400 font-bold hover:underline flex items-center gap-1 ml-1 text-xs">
                                                    <span>View</span>
                                                    <i class="ph ph-arrow-right"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-6 text-center text-slate-400">No employees in this department.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @else
            <!-- 3. All Departments Overview Table -->
            <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i class="ph ph-buildings text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white">Department Leave Utilization Overview</h2>
                            <p class="text-[11px] font-semibold text-slate-400">High-level comparison across all active company departments</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 mr-2">
                            {{ $selectedMonth ? ($monthsList[$selectedMonth] ?? '') . ' ' : 'Full Year ' }}{{ $selectedYear }}
                        </span>
                        <a href="{{ route('analytics.export', ['type' => 'company', 'format' => 'pdf']) }}" 
                           target="_blank"
                           class="bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 dark:text-rose-300 border border-rose-200 dark:border-rose-900/60 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                            <i class="ph ph-file-pdf text-rose-600"></i>
                            <span>Company PDF</span>
                        </a>
                        <a href="{{ route('analytics.export', ['type' => 'company', 'format' => 'csv']) }}" 
                           class="bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                            <i class="ph ph-file-csv text-emerald-600"></i>
                            <span>Company CSV</span>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-3">Department</th>
                                <th class="p-3 text-center">Staff Count</th>
                                <th class="p-3 text-center">Days Consumed</th>
                                <th class="p-3 text-center">Short Leaves</th>
                                <th class="p-3 text-center">Total Quota</th>
                                <th class="p-3 text-center">Utilization</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                            @forelse($departmentReport as $row)
                                @php
                                    $dept = $row['department'];
                                    $util = $row['utilization_pct'];
                                    $utilColor = $util > 80 ? 'text-rose-600 dark:text-rose-400' : ($util > 50 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-[#1e2d4d]/60 transition-colors">
                                    <td class="p-3 font-bold text-slate-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <span class="px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-mono text-[10px]">{{ $dept->code ?? 'DEPT' }}</span>
                                            <span>{{ $dept->name }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3 text-center font-bold">{{ $row['employee_count'] }}</td>
                                    <td class="p-3 text-center font-black text-slate-900 dark:text-white">{{ $row['total_days_taken'] }}</td>
                                    <td class="p-3 text-center font-bold text-amber-600 dark:text-amber-400">{{ $row['total_short_leaves'] }}</td>
                                    <td class="p-3 text-center text-slate-500">{{ $row['total_quota'] }}</td>
                                    <td class="p-3 text-center font-black {{ $utilColor }}">{{ $util }}%</td>
                                    <td class="p-3 text-right">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <!-- Download Dept PDF -->
                                            <a href="{{ route('analytics.export', ['type' => 'department', 'format' => 'pdf', 'department_id' => $dept->id]) }}" 
                                               target="_blank"
                                               title="PDF Report"
                                               class="p-1 rounded-md text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 font-bold">
                                                <i class="ph ph-file-pdf text-sm"></i>
                                            </a>
                                            <!-- Download Dept CSV -->
                                            <a href="{{ route('analytics.export', ['type' => 'department', 'format' => 'csv', 'department_id' => $dept->id]) }}" 
                                               title="CSV Report"
                                               class="p-1 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 font-bold">
                                                <i class="ph ph-file-csv text-sm"></i>
                                            </a>
                                            <!-- Drill Down into Department -->
                                            <a href="{{ route('analytics.index', ['department_id' => $dept->id]) }}" class="text-blue-600 dark:text-blue-400 font-bold hover:underline ml-1">
                                                View Staff &rarr;
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-6 text-center text-slate-400">No departments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

</div>
@endsection
