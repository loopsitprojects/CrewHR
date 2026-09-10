@extends('layouts.app', ['title' => 'LOOPS HR - Manager Approvals', 'breadcrumb' => 'Manager Approvals'])

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <i class="ph ph-check-square-offset text-2xl text-blue-600"></i>
                <h1 class="text-xl font-black text-gray-900">Line Manager & HOD Approvals Workflow</h1>
            </div>
            <p class="text-xs font-semibold text-gray-500 mt-1">Direct Approval Workflow: Covering Confirmation (if assigned) → Line Manager / HOD Final Approval</p>
        </div>

        <!-- Filters & Search -->
        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('approvals.index') }}" method="GET" class="flex items-center gap-2">
                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee / ID..." class="pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs font-semibold bg-gray-50 focus:bg-white w-48">
                </div>

                @if(in_array($role, ['Super (Admin)', 'HR Lead']))
                    <select name="department_id" onchange="this.form.submit()" class="border-gray-200 rounded-xl text-xs font-semibold bg-gray-50 py-2">
                        <option value="">All Depts</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="px-3 py-1.5 bg-slate-100/90 text-slate-700 font-extrabold text-xs rounded-xl border border-slate-200">
                        Department: {{ $activeEmployee->department->name ?? 'My Department' }}
                    </div>
                @endif
            </form>

            <!-- Status Tabs -->
            <div class="bg-gray-100 p-1 rounded-xl flex items-center gap-1 text-xs font-bold text-gray-600">
                <a href="{{ route('approvals.index', ['status' => 'pending_manager']) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'pending_manager' ? 'bg-white text-blue-600 shadow-sm' : 'hover:text-gray-900' }}">
                    Pending Approval ({{ $counts['pending_manager'] }})
                </a>
                <a href="{{ route('approvals.index', ['status' => 'approved']) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'approved' ? 'bg-white text-blue-600 shadow-sm' : 'hover:text-gray-900' }}">
                    Approved ({{ $counts['approved'] }})
                </a>
                <a href="{{ route('approvals.index', ['status' => 'rejected']) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'rejected' ? 'bg-white text-blue-600 shadow-sm' : 'hover:text-gray-900' }}">
                    Rejected ({{ $counts['rejected'] }})
                </a>
                <a href="{{ route('approvals.index', ['status' => 'all']) }}" class="px-3 py-1.5 rounded-lg {{ $status === 'all' ? 'bg-white text-blue-600 shadow-sm' : 'hover:text-gray-900' }}">
                    All ({{ $counts['all'] }})
                </a>
            </div>
        </div>
    </div>

    <!-- Approvals List (Minimalistic & Compact Design) -->
    <div class="space-y-2.5">
        @forelse($leaveRequests as $req)
            <div class="bg-white rounded-xl border border-slate-200/90 hover:border-blue-300 hover:shadow-xs p-3.5 sm:p-4 transition-all flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Left: Employee & Leave Details -->
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                        {{ strtoupper(substr($req->employee->user->name ?? 'E', 0, 2)) }}
                    </div>
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs font-black text-slate-900">{{ $req->employee->user->name ?? 'Employee' }}</span>
                            <span class="text-[10px] font-mono text-slate-400 font-semibold">({{ $req->req_number ?? 'REQ-'.$req->id }})</span>
                            <span class="text-slate-300 text-xs">•</span>
                            <span class="text-[11px] font-bold text-slate-500">{{ $req->employee->designation->name ?? 'Developer' }}</span>
                            <span class="text-[10px] font-extrabold px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded">{{ $req->employee->department->name ?? 'IT' }}</span>
                            <span class="text-slate-300 text-xs">•</span>
                            <span class="text-[11px] font-extrabold text-indigo-700 bg-indigo-50 border border-indigo-100/80 px-2 py-0.5 rounded-md">
                                {{ $req->leaveType->name ?? 'Leave' }} • {{ $req->is_half_day ? 'Half Day' : ($req->is_short_leave ? 'Short Leave' : $req->duration.'d') }}
                            </span>
                            <span class="text-xs font-semibold text-slate-700">
                                <i class="ph ph-calendar text-slate-400"></i> {{ $req->start_date }}{{ $req->start_date !== $req->end_date ? ' → '.$req->end_date : '' }}
                            </span>
                        </div>

                        <!-- Secondary Info Strip (Reason, Covering, Cert, Slot) -->
                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                            @if($req->coveringEmployee)
                                <span class="inline-flex items-center gap-1 text-blue-700 font-semibold bg-blue-50/80 px-1.5 py-0.5 rounded border border-blue-100">
                                    <i class="ph ph-handshake text-xs text-blue-600"></i>
                                    Covering: {{ $req->coveringEmployee->user->name ?? 'Covering' }}
                                </span>
                            @endif

                            @if($req->is_half_day)
                                <span class="inline-flex items-center gap-1 text-purple-800 font-semibold bg-purple-50 px-1.5 py-0.5 rounded border border-purple-100">
                                    <i class="ph ph-clock text-xs"></i>
                                    {{ $req->half_day_slot ?? 'Morning' }} ({{ strtolower($req->half_day_slot) === 'afternoon' ? '1:00 PM – 5:30 PM' : '8:30 AM – 1:00 PM' }})
                                </span>
                            @elseif($req->is_short_leave)
                                <span class="inline-flex items-center gap-1 text-amber-800 font-semibold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100">
                                    <i class="ph ph-timer text-xs"></i>
                                    Short ({{ $req->short_leave_slot ?? '1.5 Hours' }})
                                </span>
                            @endif

                            @if($req->medical_certificate_path)
                                <a href="{{ $req->medical_certificate_path }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 font-bold hover:underline">
                                    <i class="ph ph-paperclip"></i> Certificate
                                </a>
                            @endif

                            @if($req->project_client_name)
                                <span class="text-teal-700 font-bold">Duty: {{ $req->project_client_name }}</span>
                            @endif

                            @if($req->reason)
                                <span class="italic text-slate-400 font-medium truncate max-w-sm sm:max-w-md" title="{{ $req->reason }}">
                                    "{{ $req->reason }}"
                                </span>
                            @endif

                            <span class="text-slate-400 text-[10px]">Applied: {{ $req->applied_at ?? $req->created_at->format('Y-m-d') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Action Buttons / Status Pill -->
                <div class="flex items-center justify-end gap-1.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                    @if($req->status === 'Approved' || $req->manager_status === 'Approved')
                        <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-xl text-xs font-extrabold">
                            <i class="ph ph-check-circle text-emerald-600 text-sm"></i>
                            Approved
                        </span>
                    @elseif($req->status === 'Rejected' || $req->manager_status === 'Rejected')
                        <span class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1 rounded-xl text-xs font-extrabold">
                            <i class="ph ph-x-circle text-rose-600 text-sm"></i>
                            Rejected
                        </span>
                    @elseif($activeEmployee && $req->employee_id === $activeEmployee->id && $role !== 'Super (Admin)')
                        <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 border border-slate-200 px-3 py-1 rounded-xl text-xs font-bold" title="Self-approval is prohibited for managers">
                            <i class="ph ph-user text-xs"></i>
                            Self Request
                        </span>
                    @elseif(in_array($role, ['HOD / Manager', 'Super (Admin)', 'HR Lead']))
                        <form action="{{ route('approvals.action', $req->id) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="action_type" value="manager_approve">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-xs transition-all cursor-pointer">
                                <i class="ph ph-check text-sm font-bold"></i>
                                Approve
                            </button>
                        </form>

                        <form action="{{ route('approvals.action', $req->id) }}" method="POST" onsubmit="return confirm('Reject this leave request?')" class="inline">
                            @csrf
                            <input type="hidden" name="action_type" value="reject_manager">
                            <button type="submit" class="bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 hover:border-rose-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 transition-all cursor-pointer">
                                <i class="ph ph-x text-sm"></i>
                                Reject
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-500">
                <i class="ph ph-check-circle text-4xl text-emerald-500 mb-2"></i>
                <h3 class="text-base font-bold text-gray-900">No approval requests found</h3>
                <p class="text-xs text-gray-500 mt-1">All leave requests in this view have been processed.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection
