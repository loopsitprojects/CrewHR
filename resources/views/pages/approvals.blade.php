@extends('layouts.app', ['title' => 'Loops HR - Leave Approvals', 'breadcrumb' => 'Leave Approvals'])

@section('content')
<div class="space-y-6">

    <!-- Filters & Action Toolbar -->
    <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] flex flex-wrap items-center justify-between gap-3">
        <!-- Filters & Search -->
        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('approvals.index') }}" method="GET" class="flex items-center gap-2">
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee / ID..." class="pl-9 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 w-48">
                </div>

                @if(in_array($role, ['Super (Admin)', 'HR Lead']))
                    <select name="department_id" onchange="this.form.submit()" class="border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">All Depts</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="px-3 py-1.5 bg-slate-100/90 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold text-xs rounded-xl border border-slate-200/90 dark:border-slate-700">
                        Department: {{ $activeEmployee->department->name ?? 'My Department' }}
                    </div>
                @endif
            </form>

            <!-- Status Tabs -->
            <div class="bg-slate-100 dark:bg-slate-800 p-1 rounded-xl flex items-center gap-1 text-xs font-bold text-slate-600 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60">
                <a href="{{ route('approvals.index', ['status' => 'pending_manager']) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'pending_manager' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'hover:text-slate-900 dark:hover:text-white' }}">
                    Pending Approval ({{ $counts['pending_manager'] }})
                </a>
                <a href="{{ route('approvals.index', ['status' => 'approved']) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'approved' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'hover:text-slate-900 dark:hover:text-white' }}">
                    Approved ({{ $counts['approved'] }})
                </a>
                <a href="{{ route('approvals.index', ['status' => 'rejected']) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'rejected' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 shadow-sm font-black' : 'hover:text-slate-900 dark:hover:text-white' }}">
                    Rejected ({{ $counts['rejected'] }})
                </a>
                <a href="{{ route('approvals.index', ['status' => 'all']) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'all' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 shadow-sm font-black' : 'hover:text-slate-900 dark:hover:text-white' }}">
                    All ({{ $counts['all'] }})
                </a>
            </div>
        </div>
    </div>

    <!-- Approvals List (Minimalistic & Compact Design) -->
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
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                {{ $req->leaveType->name ?? 'Leave' }} • {{ $req->is_half_day ? 'Half Day' : ($req->is_short_leave ? 'Short Leave' : ((float)$req->duration).'d') }}
                            </span>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                <i class="ph ph-calendar text-slate-400 dark:text-slate-300"></i> {{ $req->start_date }}{{ $req->start_date !== $req->end_date ? ' → '.$req->end_date : '' }}
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
                                    <i class="ph ph-paperclip"></i> Certificate
                                </a>
                            @endif

                            @if($req->project_client_name)
                                <span class="text-slate-700 dark:text-[#E2E8F0] font-semibold bg-slate-100 dark:bg-white/[0.05] px-2 py-0.5 rounded border border-slate-200/80 dark:border-white/[0.08]">Duty: {{ $req->project_client_name }}</span>
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
                            <i class="ph ph-user text-xs"></i>
                            Self Request
                        </span>
                    @elseif(in_array($role, ['HOD / Manager', 'Super (Admin)', 'HR Lead']))
                        <form action="{{ route('approvals.action', $req->id) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="action_type" value="manager_approve">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all cursor-pointer">
                                <i class="ph ph-check text-sm font-bold"></i>
                                Approve
                            </button>
                        </form>

                        <form action="{{ route('approvals.action', $req->id) }}" method="POST" onsubmit="return confirm('Reject this leave request?')" class="inline">
                            @csrf
                            <input type="hidden" name="action_type" value="reject_manager">
                            <button type="submit" class="bg-white dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-700 dark:text-slate-200 hover:text-rose-600 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-900/60 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 transition-all cursor-pointer">
                                <i class="ph ph-x text-sm"></i>
                                Reject
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-500 dark:text-slate-400">
                <i class="ph ph-check-circle text-4xl text-blue-500 mb-2"></i>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No approval requests found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">All leave requests in this view have been processed.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection
