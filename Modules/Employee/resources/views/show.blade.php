@extends('layouts.app', ['title' => 'Loops HR - Employee Profile Details'])

@section('breadcrumbs')
    <span class="text-slate-300">/</span>
    <a href="{{ route('employee.index') }}" class="text-slate-500 hover:text-blue-600 transition-colors">Employees Directory</a>
    <span class="text-slate-300">/</span>
    <span class="text-blue-600 font-black">{{ $employee->user->name ?? 'Profile Details' }}</span>
@endsection

@php
    $currentRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Super (Admin)');
    $isHrOrAdmin = in_array($currentRole, ['HR Lead', 'Super (Admin)']);
    $showCompensation = $showCompensation ?? is_compensation_enabled();
@endphp

@section('content')
<div class="w-full space-y-6 pb-12">

    <!-- Top Action Bar -->
    <div class="sticky top-0 z-30 apple-glass rounded-2xl border border-[var(--border-hairline)] p-4 flex items-center justify-between transition-colors">
        <div class="flex items-center gap-3">
            <a href="{{ route('employee.index') }}" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 border border-slate-200/70 dark:border-white/10 text-slate-700 dark:text-slate-200 flex items-center justify-center shadow-2xs transition-all active:scale-95" title="Back to Directory">
                <i class="ph ph-arrow-left text-base"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">{{ $employee->user->name ?? 'Employee' }}</h1>
                    <span class="bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 text-xs font-black px-2.5 py-0.5 rounded-full border border-blue-200/80 dark:border-blue-500/30">{{ $employee->employee_id_number }}</span>
                    @if($employee->user->username)
                        <span class="bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300 text-xs font-mono font-bold px-2.5 py-0.5 rounded-full border border-slate-200/70 dark:border-white/10">@ {{ $employee->user->username }}</span>
                    @endif
                </div>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-0.5">{{ $employee->designation->name ?? 'Staff' }} • <span class="text-blue-600 dark:text-blue-400">{{ $employee->department->name ?? 'General' }}</span></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($isHrOrAdmin || Auth::id() === $employee->user_id)
            <a href="{{ route('employee.edit', $employee->id) }}" class="bg-[#0071E3] hover:bg-[#0077ED] text-white px-5 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm shadow-blue-500/25 transition-all active:scale-95">
                <i class="ph ph-pencil-simple text-base"></i>
                <span>Edit Profile</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Main Profile Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Personal Card (Section 01) -->
        <div class="lg:col-span-4 xl:col-span-3 space-y-6 lg:sticky lg:top-20">
            <div class="apple-card space-y-5 transition-colors">
                <div class="flex flex-col items-center text-center pb-4 border-b border-slate-100 dark:border-white/[0.06]">
                    <img src="{{ $employee->profile_picture ? asset($employee->profile_picture) : ('https://ui-avatars.com/api/?name=' . urlencode($employee->user->name ?? 'Employee') . '&background=0D8ABC&color=fff') }}" class="w-24 h-24 rounded-full object-cover ring-4 ring-blue-500/30 shadow-md mb-3 bg-white dark:bg-slate-800" alt="Avatar">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ $employee->user->name ?? 'Employee' }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 text-xs font-bold px-2.5 py-0.5 rounded-full border border-blue-200 dark:border-blue-500/30">{{ $employee->employee_id_number }}</span>
                        <span class="bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300 text-xs font-extrabold px-3 py-1 rounded-full border border-purple-200 dark:border-purple-500/30">{{ $employee->system_role ?? 'Employee' }}</span>
                    </div>
                </div>

                <div class="space-y-3 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">Designation</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ $employee->designation->name ?? 'Staff' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">Department</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->department->name ?? 'General' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">Gender</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->gender ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">Date of Birth</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->date_of_birth ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">NIC / Passport</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->nic_passport ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">Username</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400 font-mono bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded border border-blue-100 dark:border-blue-900/50">@<span>{{ $employee->user->username ?? 'N/A' }}</span></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">Personal Email</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->personal_email ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">Company Email</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400 truncate max-w-[160px]">{{ $employee->company_email ?? $employee->user->email ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">Phone</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->phone_number ?? 'N/A' }}</span>
                    </div>
                    <div class="pt-2 border-t border-slate-100 dark:border-white/[0.06]">
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold mb-1">Emergency Contact</span>
                        <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">{{ $employee->emergency_contact ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Direct Subordinates -->
                @if($employee->subordinates && $employee->subordinates->count() > 0)
                    <div class="pt-3 border-t border-slate-100 dark:border-white/[0.06] space-y-2">
                        <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider block">Direct Team ({{ $employee->subordinates->count() }})</span>
                        <div class="space-y-1.5">
                            @foreach($employee->subordinates as $sub)
                                <a href="{{ route('employee.show', $sub->id) }}" class="flex items-center gap-2 p-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-xl transition-all">
                                    <img src="{{ $sub->profile_picture ? asset($sub->profile_picture) : ('https://ui-avatars.com/api/?name=' . urlencode($sub->user->name ?? 'Employee') . '&background=0D8ABC&color=fff') }}" class="w-6 h-6 rounded-full object-cover" alt="Avatar">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400">{{ $sub->user->name ?? 'Subordinate' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Middle & Right Columns -->
        <div class="lg:col-span-8 xl:col-span-9 space-y-6">

            <!-- SECTION 02: Employment & Role Details -->
            <div class="apple-card space-y-5 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200/60 dark:bg-indigo-500/15 dark:text-indigo-400 dark:border-indigo-500/30 font-black text-[11px] flex items-center justify-center">02</span>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Employment & Role Details</h3>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold">Organizational Hierarchy & Terms</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-xs font-semibold">
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Department</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->department->name ?? 'General' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Current Designation</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ $employee->designation->name ?? 'Staff' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">System Access Role</span>
                        <span class="font-bold text-purple-600 dark:text-purple-400">{{ $employee->system_role ?? 'Employee' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Job Category</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->job_category ?? 'Full Time' }} @if($employee->staff_category)<span class="text-slate-500 font-semibold text-[11px]">({{ $employee->staff_category }})</span>@endif</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Joined Date</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->joined_date ? \Carbon\Carbon::parse($employee->joined_date)->format('d M Y') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Reporting Person</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400 block">{{ $employee->reportingPerson->user->name ?? 'None (Top Manager)' }}</span>
                        @if($employee->reportingPerson?->designation)
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold block">{{ $employee->reportingPerson->designation->name }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">EPF Registration No.</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->epf_registration_no ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">ETF Registration No.</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->etf_registration_no ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Cost Center Classification</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->cost_classification ? ($employee->cost_classification . ' (' . ($employee->cost_classification == 'Direct' ? 'Cost of Sales' : 'Administrative') . ')') : 'Direct' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Payment Method</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->payment_method ?? 'Bank Transfer' }}</span>
                    </div>
                </div>

                <!-- Current & Past Designations -->
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 dark:bg-[#101726]/60 border border-slate-200/80 dark:border-white/[0.06] space-y-3.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 font-black uppercase text-[11px] text-slate-800 dark:text-slate-200">
                            <i class="ph ph-identification-badge text-base text-blue-600 dark:text-blue-400"></i> Current & Past Designations
                        </div>
                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/50">
                            Designation History
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-1">
                        <!-- Current Active Designation -->
                        <div class="p-3.5 bg-white dark:bg-slate-900/80 rounded-xl border border-blue-200/80 dark:border-blue-500/30 shadow-2xs space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">Current Designation</span>
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Active</span>
                            </div>
                            <div class="flex items-center gap-2.5 pt-0.5">
                                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 text-lg">
                                    <i class="ph ph-briefcase"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-black text-slate-900 dark:text-white truncate">{{ $employee->designation->name ?? 'Staff' }}</h4>
                                    <p class="text-[10px] text-slate-400 font-medium">Department: {{ $employee->department->name ?? 'General' }} &bull; Since {{ $employee->joined_date ? \Carbon\Carbon::parse($employee->joined_date)->format('M Y') : 'Joining' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Past Designations -->
                        <div class="p-3.5 bg-white dark:bg-slate-900/80 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Past Designations</span>
                                <span class="text-[9px] font-semibold text-slate-400">Role History</span>
                            </div>
                            @php
                                $pastRoles = collect($employee->working_experience_list ?? [])->filter(fn($r) => !empty($r['designation']));
                            @endphp
                            @if($pastRoles->isNotEmpty())
                                <div class="space-y-1.5 max-h-28 overflow-y-auto pr-1">
                                    @foreach($pastRoles as $role)
                                        <div class="flex items-center justify-between gap-2 p-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                                            <div class="min-w-0 flex items-center gap-2">
                                                <i class="ph ph-clock-counter-clockwise text-xs text-slate-400 shrink-0"></i>
                                                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate">{{ $role['designation'] }}</span>
                                                @if(!empty($role['company']))
                                                    <span class="text-[10px] text-slate-400 truncate">&bull; {{ $role['company'] }}</span>
                                                @endif
                                            </div>
                                            @if(!empty($role['duration']))
                                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded shrink-0">{{ $role['duration'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500 text-[11px] italic py-1.5">
                                    <i class="ph ph-minus-circle text-sm"></i>
                                    <span>No past designations recorded.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 03: Compensation & Statutory Payroll -->
            @if($showCompensation && ($isHrOrAdmin || Auth::id() === $employee->user_id))
            <div class="apple-card space-y-5 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200/60 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30 font-black text-[11px] flex items-center justify-center">03</span>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Compensation & Statutory Payroll</h3>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold">Earnings & Deductions</span>
                </div>

                <!-- 1. Base Pay Architecture -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-blue-700 dark:text-blue-400 flex items-center gap-1.5">
                            <i class="ph ph-coins text-sm"></i> 1. Base Pay Architecture (EPF Liable Base)
                        </span>
                        <span class="text-[11px] font-extrabold text-blue-700 dark:text-blue-400">Total Base: Rs. {{ number_format($employee->total_base_pay, 2) }}</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs font-bold">
                        <div class="bg-slate-50/80 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-200/80 dark:border-white/[0.06]">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Basic Salary</span>
                            <span class="text-slate-900 dark:text-white">Rs. {{ number_format($employee->basic_salary, 2) }}</span>
                        </div>
                        <div class="bg-slate-50/80 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-200/80 dark:border-white/[0.06]">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Increments (Basic)</span>
                            <span class="text-slate-900 dark:text-white">Rs. {{ number_format($employee->increments_basic ?: 0, 2) }}</span>
                        </div>
                        <div class="bg-slate-50/80 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-200/80 dark:border-white/[0.06]">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Budget Allowance</span>
                            <span class="text-slate-900 dark:text-white">Rs. {{ number_format($employee->budget_allowance ?: 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Fixed Allowances Architecture -->
                <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-white/[0.06]">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-400 flex items-center gap-1.5">
                            <i class="ph ph-hand-coins text-sm"></i> 2. Fixed Allowances Architecture
                        </span>
                        <span class="text-[11px] font-extrabold text-indigo-700 dark:text-indigo-400">Total Fixed: Rs. {{ number_format($employee->total_fixed_allowance + ($employee->other_allowance ?: 0), 2) }}</span>
                    </div>

                    @if($employee->employeeAllowances && $employee->employeeAllowances->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                            @foreach($employee->employeeAllowances as $allowance)
                                <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between text-xs">
                                    <div class="min-w-0 pr-2">
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate block text-[11px]">{{ $allowance->allowanceType->name ?? 'Allowance' }}</span>
                                        <span class="text-[9px] font-extrabold {{ $allowance->allowanceType?->is_epf_liable ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}">
                                            {{ $allowance->allowanceType?->is_epf_liable ? 'EPF Liable' : 'Non-EPF' }}
                                        </span>
                                    </div>
                                    <span class="font-black text-slate-900 dark:text-white shrink-0">Rs. {{ number_format($allowance->amount, 2) }}</span>
                                </div>
                            @endforeach
                            @if($employee->other_allowance > 0)
                                <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between text-xs">
                                    <div class="min-w-0 pr-2">
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate block text-[11px]">Other Allowance</span>
                                        <span class="text-[9px] font-extrabold text-slate-400">Non-EPF</span>
                                    </div>
                                    <span class="font-black text-slate-900 dark:text-white shrink-0">Rs. {{ number_format($employee->other_allowance, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-bold">
                            <div class="bg-slate-50/80 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-200/80 dark:border-white/[0.06]">
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Fixed Recurring Allowance</span>
                                <span class="text-slate-900 dark:text-white">Rs. {{ number_format($employee->fixed_allowance, 2) }}</span>
                            </div>
                            <div class="bg-slate-50/80 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-200/80 dark:border-white/[0.06]">
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Other Allowance</span>
                                <span class="text-slate-900 dark:text-white">Rs. {{ number_format($employee->other_allowance, 2) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 3. Statutory Deductions & Take-Home Calculation -->
                <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-white/[0.06]">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                            <i class="ph ph-receipt text-sm"></i> 3. Statutory Calculations & Net Take-Home
                        </span>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-bold text-slate-500">Gross: <strong class="text-slate-900 dark:text-white">Rs. {{ number_format($employee->gross_salary, 2) }}</strong></span>
                            <span class="text-[11px] font-extrabold text-emerald-600 dark:text-emerald-400">Net: Rs. {{ number_format($employee->net_take_home, 2) }}</span>
                        </div>
                    </div>

                    <div class="bg-blue-50/40 dark:bg-blue-950/20 p-4 rounded-xl border border-blue-100 dark:border-blue-900/40 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-bold">
                        <div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">EPF (Employee 8%)</span>
                            <span class="text-rose-600 dark:text-rose-400 font-black">- Rs. {{ number_format($employee->epf_employee, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">EPF (Employer 12%)</span>
                            <span class="text-blue-600 dark:text-blue-400 font-black">Rs. {{ number_format($employee->epf_employer, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">ETF (Employer 3%)</span>
                            <span class="text-blue-600 dark:text-blue-400 font-black">Rs. {{ number_format($employee->etf_employer, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">APIT / Income Tax</span>
                            <span class="text-slate-900 dark:text-white font-black">- Rs. {{ number_format($employee->apit_tax ?: 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- SECTION 04: Financial & Bank Account Details -->
            @if($isHrOrAdmin || Auth::id() === $employee->user_id)
            <div class="apple-card space-y-4 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 border border-amber-200/60 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 font-black text-[11px] flex items-center justify-center">{{ $showCompensation ? '04' : '03' }}</span>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Financial & Bank Account Details</h3>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold">Salary Remittance</span>
                </div>

                <div class="bg-slate-50/80 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200/80 dark:border-white/[0.06] grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 text-xs font-semibold">
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Name</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->account_holder_name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Account Number</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->account_number ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Bank</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->bank_name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Branch</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->bank_branch ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Branch Code</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $employee->branch_code ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- SECTION 05: Higher Education & Working Experience -->
            <div class="apple-card space-y-4 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-violet-50 text-violet-600 border border-violet-200/60 dark:bg-violet-500/15 dark:text-violet-400 dark:border-violet-500/30 font-black text-[11px] flex items-center justify-center">05</span>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span>Higher Education & Working Experience</span>
                        </h3>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold">Academic & Career Credentials</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-medium">
                    <!-- Higher Education -->
                    <div class="bg-blue-50/50 dark:bg-blue-950/30 p-4 rounded-xl border border-blue-100 dark:border-blue-900/40 space-y-3">
                        <span class="font-extrabold text-blue-900 dark:text-blue-300 flex items-center gap-1.5 text-xs">
                            <i class="ph ph-graduation-cap text-base"></i> Higher Education Qualifications
                        </span>
                        @php
                            $eduList = $employee->higher_education_list;
                        @endphp
                        @if(!empty($eduList))
                            <div class="space-y-2.5">
                                @foreach($eduList as $edu)
                                    <div class="p-2.5 bg-white/80 dark:bg-slate-900/60 rounded-lg border border-blue-100/80 dark:border-blue-900/40">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="font-bold text-slate-900 dark:text-white">{{ $edu['qualification'] ?? 'N/A' }}</span>
                                            @if(!empty($edu['duration']))
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 shrink-0">{{ $edu['duration'] }}</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-x-2">
                                            @if(!empty($edu['institute']))
                                                <span>{{ $edu['institute'] }}</span>
                                            @endif
                                            @if(!empty($edu['country']))
                                                <span class="text-slate-300 dark:text-slate-600">•</span>
                                                <span>{{ $edu['country'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-slate-400 dark:text-slate-500 italic text-[11px]">No higher education records provided.</p>
                        @endif
                    </div>

                    <!-- Working Experience -->
                    <div class="bg-purple-50/50 dark:bg-purple-950/30 p-4 rounded-xl border border-purple-100 dark:border-purple-900/40 space-y-3">
                        <span class="font-extrabold text-purple-900 dark:text-purple-300 flex items-center gap-1.5 text-xs">
                            <i class="ph ph-briefcase text-base"></i> Past Working Experience
                        </span>
                        @php
                            $expList = $employee->working_experience_list;
                        @endphp
                        @if(!empty($expList))
                            <div class="space-y-2.5">
                                @foreach($expList as $exp)
                                    <div class="p-2.5 bg-white/80 dark:bg-slate-900/60 rounded-lg border border-purple-100/80 dark:border-purple-900/40">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="font-bold text-slate-900 dark:text-white">{{ $exp['designation'] ?? 'N/A' }}</span>
                                            @if(!empty($exp['duration']))
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-300 shrink-0">{{ $exp['duration'] }}</span>
                                            @endif
                                        </div>
                                        @if(!empty($exp['company']))
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                                <span>{{ $exp['company'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-slate-400 dark:text-slate-500 italic text-[11px]">No working experience records provided.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Performance Appraisal History (Chronological View) -->
            <div class="apple-card space-y-4 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <span class="font-extrabold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                        <i class="ph ph-award text-blue-600 text-lg"></i> Performance Appraisal History
                    </span>
                    <a href="{{ route('appraisal.index') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                        View Appraisal Hub &rarr;
                    </a>
                </div>

                @if($employee->appraisals && $employee->appraisals->isNotEmpty())
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @foreach($employee->appraisals->sortByDesc('id') as $histApp)
                            <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ $histApp->cycle?->name ?? 'Appraisal Cycle' }}</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $histApp->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ ucwords(str_replace('_', ' ', $histApp->status)) }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                        Workflow: {{ $histApp->workflow_type === 'three_tier' ? '3-Tier (Manager + HOD)' : '2-Tier (Direct HOD)' }} &bull; Template: {{ $histApp->template?->title }}
                                    </div>
                                    @if($histApp->general_hod_comments || $histApp->general_employee_comments)
                                        <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-1 italic line-clamp-1">
                                            "{{ $histApp->general_hod_comments ?: $histApp->general_employee_comments }}"
                                        </p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    @if($histApp->total_weighted_score > 0)
                                        <div class="text-right">
                                            <span class="text-xs font-black text-slate-900 dark:text-white block">
                                                {{ number_format($histApp->total_weighted_score, 2) }} / 5.00
                                            </span>
                                            <span class="text-[10px] font-extrabold text-blue-600 dark:text-blue-400">
                                                {{ $histApp->performance_band }}
                                            </span>
                                        </div>
                                    @endif

                                    <a href="{{ route('appraisal.show', $histApp->id) }}" class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 text-blue-600 dark:text-blue-400 font-bold text-xs flex items-center gap-1 border border-slate-200 dark:border-slate-700">
                                        <span>View</span>
                                        <i class="ph ph-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-400 dark:text-slate-500 italic text-xs py-2">No historical performance appraisals recorded for this employee profile yet.</p>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
