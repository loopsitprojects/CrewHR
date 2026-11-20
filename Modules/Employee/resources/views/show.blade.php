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

@section('header_left')
    <div class="h-5 w-px bg-slate-200 dark:bg-white/10 hidden sm:block shrink-0"></div>
    <div class="flex items-center gap-2 min-w-0">
        <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-none shrink-0">
            {{ $employee->user->name ?? 'Employee Profile' }}
        </h1>
        <span class="text-slate-300 dark:text-slate-600 hidden sm:inline">&bull;</span>
        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium truncate hidden sm:inline">
            {{ $employee->employee_id_number }} &bull; {{ $employee->designation->name ?? 'Staff' }} &bull; <span class="text-blue-600 dark:text-blue-400">{{ $employee->department->name ?? 'General' }}</span>
        </span>
    </div>
@endsection

@section('header_actions')
    @if($isHrOrAdmin || Auth::id() === $employee->user_id)
    <a href="{{ route('employee.edit', $employee->id) }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-[#0071E3] hover:bg-[#0077ED] active:scale-95 text-white rounded-lg text-xs font-semibold shadow-sm shadow-blue-500/25 transition-all cursor-pointer">
        <i class="ph ph-pencil-simple text-sm"></i>
        <span>Edit Profile</span>
    </a>
    @endif
@endsection

@section('content')
<div class="w-full space-y-6 pb-12">

    <!-- SECTION 01: Personal & Contact Details -->
    <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-5 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 border border-blue-200/60 dark:bg-blue-500/15 dark:text-blue-400 dark:border-blue-500/30 font-black text-[11px] flex items-center justify-center">01</span>
                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Personal & Contact Details</h3>
            </div>
            <span class="text-[10px] text-slate-400 font-bold">Identity, Demographic & Contact</span>
        </div>

        <!-- Identity Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/80 dark:border-white/[0.06]">
            <div class="flex items-center gap-4 min-w-0">
                <img src="{{ $employee->profile_picture ? asset($employee->profile_picture) : ('https://ui-avatars.com/api/?name=' . urlencode($employee->user->name ?? 'Employee') . '&background=0D8ABC&color=fff') }}" 
                     class="w-16 h-16 rounded-full object-cover ring-4 ring-blue-500/30 shadow-md bg-white dark:bg-slate-800 shrink-0" alt="Avatar">
                <div class="space-y-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white truncate">{{ $employee->user->name ?? 'Employee' }}</h2>
                        <span class="bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 text-xs font-bold px-2.5 py-0.5 rounded-full border border-blue-200 dark:border-blue-500/30 shrink-0">{{ $employee->employee_id_number }}</span>
                        <span class="bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300 text-xs font-extrabold px-2.5 py-0.5 rounded-full border border-purple-200 dark:border-purple-500/30 shrink-0">{{ $employee->system_role ?? 'Employee' }}</span>
                    </div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 truncate">
                        {{ $employee->designation->name ?? 'Staff' }} &bull; <span class="text-blue-600 dark:text-blue-400">{{ $employee->department->name ?? 'General' }}</span>
                    </p>
                </div>
            </div>
            @if($isHrOrAdmin || Auth::id() === $employee->user_id)
                <a href="{{ route('employee.edit', $employee->id) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#0071E3] hover:bg-[#0077ED] active:scale-95 text-white rounded-xl text-xs font-bold shadow-sm shadow-blue-500/25 transition-all shrink-0">
                    <i class="ph ph-pencil-simple text-sm"></i>
                    <span>Edit Profile</span>
                </a>
            @endif
        </div>

        <!-- Personal & Contact Information Fields Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-xs font-semibold">
            <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Username</span>
                <span class="font-bold text-blue-600 dark:text-blue-400 font-mono">@<span>{{ $employee->user->username ?? 'N/A' }}</span></span>
            </div>
            <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Gender</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $employee->gender ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Date of Birth</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('d M Y') : 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">NIC / Passport</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $employee->nic_passport ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Personal Email</span>
                <span class="font-bold text-slate-900 dark:text-white truncate block">{{ $employee->personal_email ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Company Email</span>
                <span class="font-bold text-blue-600 dark:text-blue-400 truncate block">{{ $employee->company_email ?? $employee->user->email ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Phone Number</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $employee->phone_number ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Emergency Contact</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $employee->emergency_contact ?? 'N/A' }}</span>
            </div>
            @if($employee->residential_address || $employee->permanent_address)
            <div class="col-span-2">
                <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Residential / Permanent Address</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $employee->residential_address ?: $employee->permanent_address }}</span>
            </div>
            @endif
        </div>

        <!-- Direct Subordinates -->
        @if($employee->subordinates && $employee->subordinates->count() > 0)
            <div class="pt-4 border-t border-slate-100 dark:border-white/[0.06] space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-1.5">
                        <i class="ph ph-users text-sm text-blue-600 dark:text-blue-400"></i> Direct Team ({{ $employee->subordinates->count() }})
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                    @foreach($employee->subordinates as $sub)
                        <a href="{{ route('employee.show', $sub->id) }}" class="flex items-center gap-2.5 p-2.5 bg-slate-50/80 dark:bg-slate-800/40 hover:bg-blue-50/80 dark:hover:bg-blue-950/40 rounded-xl border border-slate-200/80 dark:border-white/[0.06] transition-all group">
                            <img src="{{ $sub->profile_picture ? asset($sub->profile_picture) : ('https://ui-avatars.com/api/?name=' . urlencode($sub->user->name ?? 'Employee') . '&background=0D8ABC&color=fff') }}" class="w-8 h-8 rounded-full object-cover shrink-0" alt="Avatar">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate">{{ $sub->user->name ?? 'Subordinate' }}</div>
                                <div class="text-[10px] text-slate-400 truncate">{{ $sub->designation->name ?? $sub->department->name ?? 'Staff' }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

            <!-- SECTION 02: Employment & Role Details -->
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-5 transition-colors">
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
                    @php
                        $pastRoles = collect($employee->working_experience_list ?? [])->filter(fn($r) => !empty($r['designation']));
                    @endphp
                    <div>
                        <span class="text-slate-400 dark:text-slate-500 block text-[10px]">Past Designations</span>
                        @if($pastRoles->isNotEmpty())
                            <div class="relative inline-block w-full" x-data="{ open: false }" @click.away="open = false">
                                <button type="button" @click="open = !open" 
                                        class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer group text-left w-full">
                                    <span class="truncate">{{ $pastRoles->first()['designation'] }}</span>
                                    @if($pastRoles->count() > 1)
                                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shrink-0">+{{ $pastRoles->count() - 1 }}</span>
                                    @endif
                                    <i class="ph ph-caret-down text-xs text-slate-400 group-hover:text-blue-600 transition-transform duration-200 shrink-0 ml-auto" :class="open ? 'rotate-180' : ''"></i>
                                </button>

                                <!-- Dropdown Panel -->
                                <div x-show="open" x-cloak 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     class="absolute left-0 top-full mt-1.5 w-64 bg-white dark:bg-[#152038] border border-slate-200/90 dark:border-slate-700 rounded-xl shadow-xl z-30 p-2 space-y-1 text-xs">
                                    <div class="text-[10px] font-black uppercase text-slate-400 px-2 py-1 border-b border-slate-100 dark:border-slate-800">
                                        Past Designations ({{ $pastRoles->count() }})
                                    </div>
                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
                                        @foreach($pastRoles as $role)
                                            <div class="p-2 space-y-0.5">
                                                <div class="font-bold text-slate-900 dark:text-white text-xs">{{ $role['designation'] }}</div>
                                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                                    <span>{{ $role['company'] ?? 'Previous Employer' }}</span>
                                                    @if(!empty($role['duration']))
                                                        <span class="font-semibold text-slate-500 dark:text-slate-400">{{ $role['duration'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <span class="font-bold text-slate-400 dark:text-slate-500">None Recorded</span>
                        @endif
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
            </div>

            <!-- SECTION 03: Compensation & Statutory Payroll -->
            @if($showCompensation && ($isHrOrAdmin || Auth::id() === $employee->user_id))
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-5 transition-colors">
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
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-4 transition-colors">
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
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-4 transition-colors">
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
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-4 transition-colors">
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
@endsection
