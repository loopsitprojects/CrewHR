@extends('layouts.app', ['title' => 'Loops HR - Leave Approvals', 'breadcrumb' => 'Leave Approvals'])

@section('content')
<div class="space-y-6">

    <!-- Primary Approvals Type Sub-Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
        <a href="{{ route('approvals.index', array_filter(['tab' => 'leaves', 'status' => $status, 'month' => $month, 'year' => $year, 'department_id' => $deptId, 'search' => $search])) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $tab !== 'lieu' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-[#111927] text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white border border-slate-200/80 dark:border-slate-800' }}">
            <i data-lucide="calendar" class="w-4 h-4"></i>
            <span>Leave Requests</span>
            @if(($counts['pending_manager'] ?? 0) > 0)
                <span class="px-1.5 py-0.5 text-[10px] rounded-full {{ $tab !== 'lieu' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' }}">{{ $counts['pending_manager'] }}</span>
            @endif
        </a>

        <a href="{{ route('approvals.index', array_filter(['tab' => 'lieu', 'status' => $status, 'month' => $month, 'year' => $year, 'department_id' => $deptId, 'search' => $search])) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $tab === 'lieu' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-[#111927] text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white border border-slate-200/80 dark:border-slate-800' }}">
            <i data-lucide="clock" class="w-4 h-4"></i>
            <span>Lieu Leave Claims</span>
            @if(($counts['lieu_pending'] ?? 0) > 0)
                <span class="px-1.5 py-0.5 text-[10px] rounded-full {{ $tab === 'lieu' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' }}">{{ $counts['lieu_pending'] }}</span>
            @endif
        </a>
    </div>

    <!-- Filters & Action Toolbar -->
    <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] flex flex-wrap items-center justify-between gap-3">
        <!-- Filters & Search -->
        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('approvals.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="status" value="{{ $status }}">

                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search employee / ID..." class="pl-9 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 w-48">
                </div>

                <!-- Month Filter -->
                <div class="relative">
                    <select name="month" onchange="this.form.submit()" class="appearance-none [background-image:none]! border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer">
                        <option value="">All Months</option>
                        @foreach($monthsList as $mNum => $mName)
                            <option value="{{ $mNum }}" {{ (string)$month === (string)$mNum ? 'selected' : '' }}>{{ $mName }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-300 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>

                <!-- Year Filter -->
                <div class="relative">
                    <select name="year" onchange="this.form.submit()" class="appearance-none [background-image:none]! border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer">
                        <option value="">All Years</option>
                        @foreach($yearsList as $y)
                            <option value="{{ $y }}" {{ (string)$year === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-300 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>

                @if(in_array($role, ['Super (Admin)', 'HR Lead']))
                    <div class="relative">
                        <select name="department_id" onchange="this.form.submit()" class="appearance-none [background-image:none]! border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer">
                            <option value="">All Depts</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ (string)$deptId === (string)$dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-300 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                @else
                    <div class="px-3 py-1.5 bg-slate-100/90 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold text-xs rounded-xl border border-slate-200/90 dark:border-slate-700">
                        Dept: {{ $activeEmployee->department->name ?? 'My Department' }}
                    </div>
                @endif

                @if($month || $year || $deptId || $search)
                    <a href="{{ route('approvals.index', ['tab' => $tab, 'status' => $status]) }}" class="px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 flex items-center gap-1 transition-colors" title="Clear all filters">
                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                        <span>Clear</span>
                    </a>
                @endif
            </form>

            <!-- Status Tabs -->
            <div class="bg-slate-100 dark:bg-slate-800 p-1 rounded-xl flex items-center gap-1 text-xs font-bold text-slate-600 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60">
                @php
                    $pendingCount = $tab === 'lieu' ? ($counts['lieu_pending'] ?? 0) : ($counts['pending_manager'] ?? 0);
                    $approvedCount = $tab === 'lieu' ? ($counts['lieu_approved'] ?? 0) : ($counts['approved'] ?? 0);
                    $rejectedCount = $tab === 'lieu' ? ($counts['lieu_rejected'] ?? 0) : ($counts['rejected'] ?? 0);
                    $allCount = $tab === 'lieu' ? ($counts['lieu_all'] ?? 0) : ($counts['all'] ?? 0);
                @endphp
                <a href="{{ route('approvals.index', array_filter(['tab' => $tab, 'status' => 'pending_manager', 'month' => $month, 'year' => $year, 'department_id' => $deptId, 'search' => $search])) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'pending_manager' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm font-black' : 'hover:text-slate-900 dark:hover:text-white' }}">
                    Pending Approval ({{ $pendingCount }})
                </a>
                <a href="{{ route('approvals.index', array_filter(['tab' => $tab, 'status' => 'approved', 'month' => $month, 'year' => $year, 'department_id' => $deptId, 'search' => $search])) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'approved' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm font-black' : 'hover:text-slate-900 dark:hover:text-white' }}">
                    Approved ({{ $approvedCount }})
                </a>
                <a href="{{ route('approvals.index', array_filter(['tab' => $tab, 'status' => 'rejected', 'month' => $month, 'year' => $year, 'department_id' => $deptId, 'search' => $search])) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'rejected' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 shadow-sm font-black' : 'hover:text-slate-900 dark:hover:text-white' }}">
                    Rejected ({{ $rejectedCount }})
                </a>
                <a href="{{ route('approvals.index', array_filter(['tab' => $tab, 'status' => 'all', 'month' => $month, 'year' => $year, 'department_id' => $deptId, 'search' => $search])) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'all' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 shadow-sm font-black' : 'hover:text-slate-900 dark:hover:text-white' }}">
                    All ({{ $allCount }})
                </a>
            </div>
        </div>
    </div>

    <!-- Approvals List: Lieu Leave Claims -->
    @if($tab === 'lieu')
        <div class="space-y-2.5">
            @forelse($lieuClaims as $claim)
                <div class="bg-white dark:bg-[#111927] rounded-xl border border-slate-200/90 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-500/50 hover:shadow-xs p-3.5 sm:p-4 transition-all flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <!-- Left: Employee & Claim Details -->
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $claim->employee->user->name ?? 'Employee' }}</span>
                            <span class="text-[10px] font-mono text-slate-400 dark:text-slate-300 font-semibold">({{ $claim->claim_number }})</span>
                            <span class="text-slate-300 dark:text-slate-600 text-xs">•</span>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-300">{{ $claim->employee->designation->name ?? 'Developer' }}</span>
                            <span class="text-[10px] font-extrabold px-1.5 py-0.2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded">{{ $claim->employee->department->name ?? 'IT' }}</span>
                            <span class="text-slate-300 dark:text-slate-600 text-xs">•</span>
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-[#E2E8F0] bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-300 border border-teal-200/80 dark:border-teal-800/60 px-2 py-0.5 rounded-md flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-teal-500 shrink-0"></span>
                                Lieu Request • {{ $claim->duration_type === 'half_day' ? 'Half Day (4h)' : 'Full Day (8h)' }} (+{{ (float)$claim->credit_days }}d)
                            </span>
                            @if($claim->start_time && $claim->end_time)
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-1 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">
                                    <i data-lucide="timer" class="w-3.5 h-3.5 text-teal-500"></i>
                                    {{ \Carbon\Carbon::parse($claim->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($claim->end_time)->format('g:i A') }} ({{ (float)$claim->hours_worked }} hrs)
                                </span>
                            @endif
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-300"></i>
                                Worked: {{ \Carbon\Carbon::parse($claim->worked_date)->format('D, j M Y') }}
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                {{ $claim->day_type }}
                            </span>
                        </div>

                        <!-- Secondary Info Strip (Reason, Status Info) -->
                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-300">
                            @if($claim->reason)
                                <span class="italic text-slate-600 dark:text-slate-300 font-medium truncate max-w-sm sm:max-w-md" title="{{ $claim->reason }}">
                                    "{{ $claim->reason }}"
                                </span>
                            @endif

                            @if($claim->approved_at)
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">
                                    • Approved {{ \Carbon\Carbon::parse($claim->approved_at)->format('D, j M') }} by {{ $claim->managerEmployee->user->name ?? 'Manager' }}
                                </span>
                                @if($claim->expires_at)
                                    <span class="text-slate-400 dark:text-slate-400 text-[10px]">
                                        (Expires: {{ \Carbon\Carbon::parse($claim->expires_at)->format('D, j M Y') }})
                                    </span>
                                @endif
                            @endif

                            <span class="text-slate-400 dark:text-slate-400 text-[10px]">Submitted: {{ $claim->created_at->format('Y-m-d') }}</span>
                        </div>
                    </div>

                    <!-- Right: Action Buttons / Status Pill -->
                    <div class="flex items-center justify-end gap-1.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                        @if($claim->status === 'Approved')
                            <span class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 px-3 py-1 rounded-lg text-xs font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                Approved
                            </span>
                        @elseif($claim->status === 'Rejected')
                            <span class="inline-flex items-center gap-1.5 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/60 px-3 py-1 rounded-lg text-xs font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                Rejected
                            </span>
                        @elseif($activeEmployee && $claim->employee_id === $activeEmployee->id && $role !== 'Super (Admin)')
                            <span class="inline-flex items-center gap-1 bg-slate-100 dark:bg-white/[0.05] text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-white/[0.08] px-3 py-1 rounded-lg text-xs font-medium" title="Self-approval is prohibited for managers">
                                <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                Self Claim
                            </span>
                        @elseif(in_array($role, ['HOD / Manager', 'Super (Admin)', 'HR Lead']))
                            <form action="{{ route('approvals.lieu-action', $claim->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="action_type" value="approve">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all cursor-pointer">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                    Approve (+{{ (float)$claim->credit_days }}d)
                                </button>
                            </form>

                            <form action="{{ route('approvals.lieu-action', $claim->id) }}" method="POST" onsubmit="return confirm('Reject this Lieu Leave claim?')" class="inline">
                                @csrf
                                <input type="hidden" name="action_type" value="reject">
                                <button type="submit" class="bg-white dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-700 dark:text-slate-200 hover:text-rose-600 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-900/60 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 transition-all cursor-pointer">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    Reject
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-500 dark:text-slate-400">
                    <i data-lucide="check-circle" class="w-10 h-10 text-teal-500 mx-auto mb-2"></i>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No Lieu Leave Claims Found</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">All employee lieu leave claims in this filter have been processed.</p>
                </div>
            @endforelse
        </div>
    @else
        <!-- Approvals List: Standard Leave Requests -->
        <div class="space-y-2.5">
            @forelse($leaveRequests as $req)
                <div class="bg-white dark:bg-[#111927] rounded-xl border border-slate-200/90 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-500/50 hover:shadow-xs p-3.5 sm:p-4 transition-all flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <!-- Left: Employee & Leave Details -->
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $req->employee->user->name ?? 'Employee' }}</span>
                            <span class="text-[10px] font-mono text-slate-400 dark:text-slate-300 font-semibold">({{ $req->req_number ?? 'REQ-'.$req->id }})</span>
                            <span class="text-slate-300 dark:text-slate-600 text-xs">•</span>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-300">{{ $req->employee->designation->name ?? 'Developer' }}</span>
                            <span class="text-[10px] font-extrabold px-1.5 py-0.2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded">{{ $req->employee->department->name ?? 'IT' }}</span>
                            <span class="text-slate-300 dark:text-slate-600 text-xs">•</span>
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-[#E2E8F0] bg-slate-100 dark:bg-white/[0.05] border border-slate-200/80 dark:border-white/[0.08] px-2 py-0.5 rounded-md flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full {{ $req->leaveType?->code === 'NIGHT_SHIFT_RECOVERY' ? 'bg-indigo-500' : 'bg-blue-500' }} shrink-0"></span>
                                {{ $req->leaveType->name ?? 'Leave' }} • 
                                @if($req->leaveType?->code === 'NIGHT_SHIFT_RECOVERY')
                                    {{ $req->concession_category === 'full_day_off' ? 'Full Day Off' : ($req->concession_category === 'half_day_off' ? 'Half Day Off' : ($req->concession_category === 'report_10am' ? 'Report 10:00 AM' : 'Report 11:00 AM')) }}
                                @else
                                    {{ $req->is_half_day ? 'Half Day' : ($req->is_short_leave ? 'Short Leave' : ((float)$req->duration).'d') }}
                                @endif
                            </span>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-300"></i>
                                {{ $req->start_date }}{{ $req->start_date !== $req->end_date ? ' → '.$req->end_date : '' }}
                            </span>
                        </div>

                        <!-- Secondary Info Strip (Reason, Covering, Cert, Slot) -->
                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-300">
                            @if($req->coveringEmployee)
                                <span class="inline-flex items-center gap-1.5 text-slate-700 dark:text-[#E2E8F0] font-semibold bg-slate-100 dark:bg-white/[0.05] px-2 py-0.5 rounded border border-slate-200/80 dark:border-white/[0.08]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                    Covering: {{ $req->coveringEmployee->user->name ?? 'Covering' }}
                                </span>
                            @endif

                            @if($req->is_half_day)
                                <span class="inline-flex items-center gap-1.5 text-slate-700 dark:text-[#E2E8F0] font-semibold bg-slate-100 dark:bg-white/[0.05] px-2 py-0.5 rounded border border-slate-200/80 dark:border-white/[0.08]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 shrink-0"></span>
                                    {{ $req->half_day_slot ?? 'Morning' }} ({{ strtolower($req->half_day_slot) === 'afternoon' ? '1:00 PM – 5:30 PM' : '8:30 AM – 1:00 PM' }})
                                </span>
                            @elseif($req->is_short_leave)
                                <span class="inline-flex items-center gap-1.5 text-slate-700 dark:text-[#E2E8F0] font-semibold bg-slate-100 dark:bg-white/[0.05] px-2 py-0.5 rounded border border-slate-200/80 dark:border-white/[0.08]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                    Short ({{ $req->short_leave_slot ?? '1.5 Hours' }})
                                </span>
                            @endif

                            @if($req->medical_certificate_path)
                                <a href="{{ $req->medical_certificate_path }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 font-bold hover:underline">
                                    <i data-lucide="paperclip" class="w-3.5 h-3.5"></i> Certificate
                                </a>
                            @endif

                            @if($req->project_client_name)
                                <span class="text-slate-700 dark:text-[#E2E8F0] font-semibold bg-slate-100 dark:bg-white/[0.05] px-2 py-0.5 rounded border border-slate-200/80 dark:border-white/[0.08]">Duty: {{ $req->project_client_name }}</span>
                            @endif

                            @if($req->leaveType?->code === 'NIGHT_SHIFT_RECOVERY' || $req->concession_category)
                                <span class="inline-flex items-center gap-1.5 text-indigo-700 dark:text-indigo-300 font-bold bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800">
                                    <i data-lucide="moon" class="w-3 h-3 text-indigo-500"></i>
                                    Sign-out: {{ $req->night_sign_out_time ? \Carbon\Carbon::parse($req->night_sign_out_time)->format('h:i A') : 'Late' }} → 
                                    @if($req->concession_category === 'report_10am')
                                        Report next day at 10:00 AM
                                    @elseif($req->concession_category === 'report_11am')
                                        Report next day at 11:00 AM
                                    @elseif($req->concession_category === 'half_day_off')
                                        Half Day Off
                                    @elseif($req->concession_category === 'full_day_off')
                                        Full Day Off
                                    @endif
                                </span>
                            @endif

                            @if($req->reason)
                                <span class="italic text-slate-400 dark:text-slate-300 font-medium truncate max-w-sm sm:max-w-md" title="{{ $req->reason }}">
                                    "{{ $req->reason }}"
                                </span>
                            @endif

                            <span class="text-slate-400 dark:text-slate-300 text-[10px]">Applied: {{ $req->applied_at ?? $req->created_at->format('Y-m-d') }}</span>
                        </div>
                    </div>

                    <!-- Right: Action Buttons / Status Pill -->
                    <div class="flex items-center justify-end gap-1.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                        @if($req->status === 'Approved' || $req->manager_status === 'Approved')
                            <span class="inline-flex items-center gap-1.5 bg-slate-100 dark:bg-white/[0.05] text-slate-700 dark:text-[#E2E8F0] border border-slate-200/80 dark:border-white/[0.08] px-3 py-1 rounded-lg text-xs font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                Approved
                            </span>
                        @elseif($req->status === 'Rejected' || $req->manager_status === 'Rejected')
                            <span class="inline-flex items-center gap-1.5 bg-slate-100 dark:bg-white/[0.05] text-slate-700 dark:text-[#E2E8F0] border border-slate-200/80 dark:border-white/[0.08] px-3 py-1 rounded-lg text-xs font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                Rejected
                            </span>
                        @elseif($activeEmployee && $req->employee_id === $activeEmployee->id && $role !== 'Super (Admin)')
                            <span class="inline-flex items-center gap-1 bg-slate-100 dark:bg-white/[0.05] text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-white/[0.08] px-3 py-1 rounded-lg text-xs font-medium" title="Self-approval is prohibited for managers">
                                <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                Self Request
                            </span>
                        @elseif(in_array($role, ['HOD / Manager', 'Super (Admin)', 'HR Lead']))
                            <form action="{{ route('approvals.action', $req->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="action_type" value="manager_approve">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all cursor-pointer">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                    Approve
                                </button>
                            </form>

                            <form action="{{ route('approvals.action', $req->id) }}" method="POST" onsubmit="return confirm('Reject this leave request?')" class="inline">
                                @csrf
                                <input type="hidden" name="action_type" value="reject_manager">
                                <button type="submit" class="bg-white dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-700 dark:text-slate-200 hover:text-rose-600 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-900/60 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 transition-all cursor-pointer">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    Reject
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-500 dark:text-slate-400">
                    <i data-lucide="check-circle" class="w-10 h-10 text-blue-500 mx-auto mb-2"></i>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No approval requests found</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">All leave requests in this view have been processed.</p>
                </div>
            @endforelse
        </div>
    @endif

</div>
@endsection
