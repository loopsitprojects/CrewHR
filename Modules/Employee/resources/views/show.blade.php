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

    // Calculate profile completion count for personal info (out of 9 fields)
    $infoFields = [
        $employee->user->name ?? null,
        $employee->user->username ?? null,
        $employee->company_email ?: ($employee->user->email ?? null),
        $employee->date_of_birth ?? null,
        $employee->gender ?? null,
        $employee->personal_email ?? null,
        $employee->phone_number ?? null,
        $employee->nic_passport ?? null,
        $employee->emergency_contact ?? null,
    ];
    $filledCount = collect($infoFields)->filter(fn($f) => !empty($f) && $f !== 'N/A')->count();
    $totalFields = 9;
    $completionPct = round(($filledCount / $totalFields) * 100);

    // Calculate joined duration
    $joinedYears = $employee->joined_date ? \Carbon\Carbon::parse($employee->joined_date)->diffInYears(now()) : null;
    $age = $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->age : null;
    $compEmail = $employee->company_email ?: ($employee->user->email ?? '');
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
        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
        <span>Edit Profile</span>
    </a>
    @endif
@endsection

@section('content')
<div class="w-full space-y-5 pb-12">

    <!-- MAIN 2-COLUMN GRID (Matching Screenshot One) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- ============================================== -->
        <!-- LEFT COLUMN: Personal & Employment Details (7) -->
        <!-- ============================================== -->
        <div class="lg:col-span-7 space-y-5">

            <!-- CARD 1: Personal & Contact Details -->
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-5 shadow-sm space-y-4 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/60 dark:border-blue-500/30 flex items-center justify-center shrink-0">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Personal & Contact Details</h3>
                            <p class="text-[11px] text-slate-400">Identity, demographic & direct reachability records</p>
                        </div>
                    </div>

                    <!-- Profile Completion Progress -->
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 px-2.5 py-1 rounded-full text-[10px]">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Profile Info</span>
                        <div class="w-12 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="bg-amber-500 h-full rounded-full transition-all" style="width: {{ $completionPct }}%;"></div>
                        </div>
                        <span class="font-bold text-slate-700 dark:text-slate-300 tabular-nums">{{ $filledCount }}/{{ $totalFields }}</span>
                    </div>
                </div>

                <!-- 3-Column Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6 pt-1 text-xs font-semibold">
                    <!-- Row 1: Full Name, Username, Company Email -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Full Name</span>
                        @if(!empty($employee->user->name))
                            <span class="font-bold text-slate-900 dark:text-white text-xs block truncate" title="{{ $employee->user->name }}">{{ $employee->user->name }}</span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Username</span>
                        @if(!empty($employee->user->username))
                            <span class="font-bold text-blue-600 dark:text-blue-400 font-mono text-xs">@<span>{{ $employee->user->username }}</span></span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    <div x-data="{ copied: false }">
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Company Email</span>
                        @if(!empty($compEmail))
                            <span class="inline-flex items-center gap-1 font-bold text-blue-600 dark:text-blue-400 text-xs cursor-pointer hover:underline"
                                  @click="navigator.clipboard.writeText('{{ $compEmail }}'); copied = true; setTimeout(() => copied = false, 2000)">
                                <span class="truncate max-w-[150px]">{{ $compEmail }}</span>
                                <i data-lucide="copy" class="w-3 h-3 text-slate-400 shrink-0" x-show="!copied"></i>
                                <i data-lucide="check" class="w-3 h-3 text-emerald-500 shrink-0" x-show="copied" x-cloak></i>
                            </span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    <!-- Row 2: Date of Birth, Gender, NIC / Passport -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Date of Birth</span>
                        @if(!empty($employee->date_of_birth))
                            <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white text-xs">
                                <span>{{ \Carbon\Carbon::parse($employee->date_of_birth)->format('d M Y') }}</span>
                                @if($age !== null)
                                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        {{ $age }} yrs
                                    </span>
                                @endif
                            </div>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Gender</span>
                        @if(!empty($employee->gender) && $employee->gender !== 'N/A')
                            <span class="font-bold text-slate-900 dark:text-white text-xs">{{ $employee->gender }}</span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">NIC / Passport</span>
                        @if(!empty($employee->nic_passport) && $employee->nic_passport !== 'N/A')
                            <span class="font-bold text-slate-900 dark:text-white text-xs">{{ $employee->nic_passport }}</span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    <!-- Row 3: Personal Email, Phone Number, Emergency Contact -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Personal Email</span>
                        @if(!empty($employee->personal_email) && $employee->personal_email !== 'N/A')
                            <span class="font-bold text-slate-900 dark:text-white text-xs truncate block" title="{{ $employee->personal_email }}">{{ $employee->personal_email }}</span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Phone Number</span>
                        @if(!empty($employee->phone_number) && $employee->phone_number !== 'N/A')
                            <span class="font-bold text-slate-900 dark:text-white text-xs">{{ $employee->phone_number }}</span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Emergency Contact</span>
                        @if(!empty($employee->emergency_contact) && $employee->emergency_contact !== 'N/A')
                            <span class="font-bold text-slate-900 dark:text-white text-xs">{{ $employee->emergency_contact }}</span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                        @endif
                    </div>

                    @if(!empty($employee->residential_address) || !empty($employee->permanent_address))
                    <div class="sm:col-span-3 pt-2 border-t border-slate-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Address</span>
                        <span class="font-bold text-slate-900 dark:text-white text-xs block" title="{{ $employee->residential_address ?: $employee->permanent_address }}">
                            {{ $employee->residential_address ?: $employee->permanent_address }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- CARD 2: Employment & Role Details -->
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-5 shadow-sm space-y-4 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-500/30 flex items-center justify-center shrink-0">
                            <i data-lucide="briefcase" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Employment & Role Details</h3>
                            <p class="text-[11px] text-slate-400">Organizational placement, contract terms & statutory registration</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 pt-1 text-xs font-semibold">
                    <!-- Top 3 cols -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Department</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $employee->department->name ?? 'General' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Current Designation</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400">{{ $employee->designation->name ?? 'Staff' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">System Access Role</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400">{{ $employee->system_role ?? 'Employee' }}</span>
                        </div>
                    </div>

                    <!-- Middle 3 cols -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Job Category</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ $employee->job_category ?? 'Full Time' }}
                                @if($employee->staff_category)
                                    <span class="text-slate-500 font-semibold text-[11px]">({{ $employee->staff_category }})</span>
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Joined Date</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ $employee->joined_date ? \Carbon\Carbon::parse($employee->joined_date)->format('d M Y') : 'N/A' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Reporting Person</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400 block">
                                {{ $employee->reportingPerson->user->name ?? 'None (Top Manager)' }}
                            </span>
                        </div>
                    </div>

                    <!-- Past Designations row -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Past Designations</span>
                        @php
                            $pastRoles = collect($employee->working_experience_list ?? [])->filter(fn($r) => !empty($r['designation']));
                        @endphp
                        @if($pastRoles->isNotEmpty())
                            <div class="relative inline-block w-full max-w-sm" x-data="{ open: false }" @click.away="open = false">
                                <button type="button" @click="open = !open" 
                                        class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer group text-left">
                                    <span class="truncate">{{ $pastRoles->first()['designation'] }}</span>
                                    @if($pastRoles->count() > 1)
                                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shrink-0">+{{ $pastRoles->count() - 1 }}</span>
                                    @endif
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition-transform duration-200 shrink-0 ml-1" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="open" x-cloak class="absolute left-0 top-full mt-1.5 w-64 bg-white dark:bg-[#152038] border border-slate-200/90 dark:border-slate-700 rounded-xl shadow-xl z-30 p-2 space-y-1 text-xs">
                                    <div class="text-[10px] font-black uppercase text-slate-400 px-2 py-1 border-b border-slate-100 dark:border-slate-800">Past Designations ({{ $pastRoles->count() }})</div>
                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
                                        @foreach($pastRoles as $role)
                                            <div class="p-2 space-y-0.5">
                                                <div class="font-bold text-slate-900 dark:text-white text-xs">{{ $role['designation'] }}</div>
                                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                                    <span>{{ $role['company'] ?? 'Previous Employer' }}</span>
                                                    @if(!empty($role['duration']))
                                                        <span class="font-semibold text-slate-500">{{ $role['duration'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <span class="font-medium text-slate-400 dark:text-slate-500 italic">None Recorded</span>
                        @endif
                    </div>

                    <!-- Statutory & Payment Terms (4 cols across) -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-y-3 gap-x-4 pt-3 border-t border-slate-100 dark:border-white/[0.06]">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">EPF Registration No.</span>
                            @if(!empty($employee->epf_registration_no) && $employee->epf_registration_no !== 'N/A')
                                <span class="font-bold text-slate-900 dark:text-white">{{ $employee->epf_registration_no }}</span>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">ETF Registration No.</span>
                            @if(!empty($employee->etf_registration_no) && $employee->etf_registration_no !== 'N/A')
                                <span class="font-bold text-slate-900 dark:text-white">{{ $employee->etf_registration_no }}</span>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic font-normal">Not provided</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Cost Center</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ $employee->cost_classification ? ($employee->cost_classification . ' (' . ($employee->cost_classification == 'Direct' ? 'Cost of Sales' : 'Administrative') . ')') : 'Direct (Cost of Sales)' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-0.5">Payment Method</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $employee->payment_method ?? 'Bank Transfer' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Direct Subordinates (if any) -->
            @if($employee->subordinates && $employee->subordinates->count() > 0)
                <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-5 shadow-sm space-y-3 transition-colors">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-1.5">
                            <i data-lucide="users" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i> Direct Team ({{ $employee->subordinates->count() }})
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                        @foreach($employee->subordinates as $sub)
                            <a href="{{ route('employee.show', $sub->id) }}" class="flex items-center gap-2.5 p-2 bg-slate-50/80 dark:bg-slate-800/40 hover:bg-blue-50/80 dark:hover:bg-blue-950/40 rounded-xl border border-slate-200/80 dark:border-white/[0.06] transition-all group">
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

        <!-- ============================================== -->
        <!-- RIGHT COLUMN: Financial & Education (5)       -->
        <!-- ============================================== -->
        <div class="lg:col-span-5 space-y-5">

            <!-- CARD 3: Financial & Bank Details -->
            <div x-data="{ copied: false }" class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-5 shadow-sm space-y-4 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200/60 dark:border-amber-500/30 flex items-center justify-center shrink-0">
                            <i data-lucide="landmark" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Financial & Bank Details</h3>
                            <p class="text-[11px] text-slate-400">Salary remittance account</p>
                        </div>
                    </div>

                    <!-- Payment Mode Badge -->
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                        <i data-lucide="check-circle" class="w-3 h-3 text-emerald-500"></i>
                        <span>{{ $employee->payment_method ?? 'Bank Transfer' }}</span>
                    </span>
                </div>

                <!-- Structured Bank Details -->
                <div class="space-y-1 text-xs divide-y divide-slate-100 dark:divide-slate-800/80">
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-400 dark:text-slate-500 font-medium text-[11px]">Account Holder</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">
                            {{ $employee->account_holder_name ?: ($employee->user->name ?? 'Not provided') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-400 dark:text-slate-500 font-medium text-[11px]">Bank</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">
                            {{ $employee->bank_name ?: 'Commercial Bank of Ceylon' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-400 dark:text-slate-500 font-medium text-[11px]">Account Number</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono font-bold text-slate-900 dark:text-white tabular-nums tracking-wide">
                                {{ $employee->account_number ?: 'Not provided' }}
                            </span>
                            @if(!empty($employee->account_number))
                                <button type="button" @click="navigator.clipboard.writeText('{{ $employee->account_number }}'); copied = true; setTimeout(() => copied = false, 2000)" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer" title="Copy account number">
                                    <i data-lucide="copy" class="w-3.5 h-3.5" x-show="!copied"></i>
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500" x-show="copied" x-cloak></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-400 dark:text-slate-500 font-medium text-[11px]">Branch & Code</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">
                            {{ $employee->bank_branch ?: 'Kollupitiya' }} 
                            <span class="text-slate-400 font-normal">(Code: {{ $employee->branch_code ?: 'N/A' }})</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- CARD 4: Education & Experience -->
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-5 shadow-sm space-y-4 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-500/30 flex items-center justify-center shrink-0">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Education & Experience</h3>
                            <p class="text-[11px] text-slate-400">Academic & career credentials</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <!-- Higher Education Qualifications Item -->
                    <div class="p-3.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-200/50 dark:border-blue-500/20">
                                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">Higher Education Qualifications</h4>
                                @if(!empty($employee->higher_education_list))
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                        {{ count($employee->higher_education_list) }} qualification(s) recorded
                                    </div>
                                @else
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">No education records provided.</p>
                                @endif
                            </div>
                        </div>
                        @if($isHrOrAdmin || Auth::id() === $employee->user_id)
                            <a href="{{ route('employee.edit', $employee->id) }}" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 shrink-0 transition-colors">
                                + Add
                            </a>
                        @endif
                    </div>

                    <!-- Past Working Experience Item -->
                    <div class="p-3.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400 flex items-center justify-center shrink-0 border border-purple-200/50 dark:border-purple-500/20">
                                <i data-lucide="briefcase" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">Past Working Experience</h4>
                                @if(!empty($employee->working_experience_list))
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                        {{ count($employee->working_experience_list) }} experience record(s)
                                    </div>
                                @else
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">No working experience records provided.</p>
                                @endif
                            </div>
                        </div>
                        @if($isHrOrAdmin || Auth::id() === $employee->user_id)
                            <a href="{{ route('employee.edit', $employee->id) }}" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 shrink-0 transition-colors">
                                + Add
                            </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ============================================== -->
    <!-- OPTIONAL FULL-WIDTH SECTIONS BELOW             -->
    <!-- ============================================== -->

    <!-- COMPENSATION & STATUTORY PAYROLL (When authorized) -->
    @if($showCompensation && ($isHrOrAdmin || Auth::id() === $employee->user_id))
    <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-5 sm:p-6 shadow-sm space-y-5 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-500/30 flex items-center justify-center shrink-0">
                    <i data-lucide="coins" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Compensation & Statutory Payroll</h3>
                    <p class="text-[11px] text-slate-400">Earnings architecture & statutory deductions</p>
                </div>
            </div>
            <div class="flex items-center gap-3 text-xs font-bold">
                <span class="text-slate-500">Gross: <strong class="text-slate-900 dark:text-white">Rs. {{ number_format($employee->gross_salary, 2) }}</strong></span>
                <span class="text-emerald-600 dark:text-emerald-400">Net: Rs. {{ number_format($employee->net_take_home, 2) }}</span>
            </div>
        </div>

        <!-- 1. Base Pay Architecture -->
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs font-semibold">
                <span class="text-[11px] font-black uppercase tracking-wider text-blue-700 dark:text-blue-400">
                    1. Base Pay Architecture (EPF Liable Base)
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
            <div class="flex items-center justify-between text-xs font-semibold">
                <span class="text-[11px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-400">
                    2. Fixed Allowances Architecture
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

        <!-- 3. Statutory Deductions -->
        <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-white/[0.06]">
            <span class="text-[11px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400 block">
                3. Statutory Contributions & Taxes
            </span>
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

    <!-- PERFORMANCE APPRAISAL HISTORY (Chronological View) -->
    <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-5 sm:p-6 shadow-sm space-y-4 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
            <span class="font-extrabold text-slate-900 dark:text-white flex items-center gap-2 text-xs uppercase tracking-wider">
                <i data-lucide="award" class="w-4 h-4 text-blue-600"></i> Performance Appraisal History
            </span>
            <a href="{{ route('appraisal.index') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                View Appraisal Hub &rarr;
            </a>
        </div>

        @if($employee->appraisals && $employee->appraisals->isNotEmpty())
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs font-semibold">
                @foreach($employee->appraisals->sortByDesc('id') as $histApp)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <span>{{ $histApp->cycle?->name ?? 'Appraisal Cycle' }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $histApp->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                                    {{ ucwords(str_replace('_', ' ', $histApp->status)) }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                Workflow: {{ $histApp->workflow_type === 'three_tier' ? '3-Tier (Manager + HOD)' : '2-Tier (Direct HOD)' }} &bull; Template: {{ $histApp->template?->title }}
                            </div>
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

                            <a href="{{ route('appraisal.show', $histApp->id) }}" class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 text-blue-600 dark:text-blue-400 font-bold text-xs flex items-center gap-1 border border-slate-200 dark:border-slate-700 transition-colors">
                                <span>View</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
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
@endsection
