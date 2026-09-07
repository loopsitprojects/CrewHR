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

    <!-- Approvals List Cards -->
    <div class="space-y-4">
        @forelse($leaveRequests as $req)
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-4 hover:border-blue-300 transition-all">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Left: Employee Avatar & Info -->
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($req->employee->user->name ?? 'Employee') }}&background=random" class="w-10 h-10 rounded-full border-2 border-blue-500" alt="Avatar">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-black text-gray-900">{{ $req->employee->user->name ?? 'Employee' }}</h3>
                                <span class="text-xs font-bold text-gray-400">({{ $req->req_number ?? 'REQ-1000' }})</span>
                            </div>
                            <p class="text-xs font-bold text-gray-500 mt-0.5">
                                {{ $req->employee->designation->name ?? 'Developer' }} <span class="text-gray-300 mx-1">•</span> <span class="text-blue-600">{{ $req->employee->department->name ?? 'IT' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Right: Workflow Status Pill -->
                    <div>
                        @if($req->status === 'Approved' || $req->manager_status === 'Approved')
                            <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full text-xs font-extrabold shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                Approved
                            </span>
                        @elseif($req->status === 'Rejected' || $req->manager_status === 'Rejected')
                            <span class="inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1 rounded-full text-xs font-extrabold shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                Rejected
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 rounded-full text-xs font-extrabold shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                                {{ $req->status ?? 'Pending Manager Approval' }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Middle Details Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-gray-50 p-3 rounded-xl border border-gray-100 text-xs">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Leave details pill -->
                        <span class="bg-white border border-gray-200 text-gray-800 px-3 py-1 rounded-lg font-bold">
                            <span class="text-purple-600 font-black">{{ $req->leaveType->name ?? 'Leave' }}</span> • {{ $req->is_half_day ? 'Half Day' : ($req->is_short_leave ? 'Short Leave' : $req->duration.'d') }} • Date: {{ $req->start_date }}{{ $req->start_date !== $req->end_date ? ' → '.$req->end_date : '' }} • <span class="text-gray-400 font-normal">Applied: {{ $req->applied_at ?? $req->created_at->format('Y-m-d') }}</span>
                        </span>

                        <!-- Half Day Badge with Hours -->
                        @if($req->is_half_day)
                            <span class="bg-purple-100 text-purple-900 border border-purple-300 px-3 py-1 rounded-lg font-black flex items-center gap-1.5 shadow-2xs">
                                <i class="ph ph-clock text-purple-700 font-bold"></i>
                                Half Day ({{ $req->half_day_slot ?? 'Morning' }}: {{ strtolower($req->half_day_slot) === 'afternoon' ? '1:00 PM – 5:30 PM' : '8:30 AM – 1:00 PM' }})
                            </span>
                        @elseif($req->is_short_leave)
                            <span class="bg-amber-100 text-amber-900 border border-amber-300 px-3 py-1 rounded-lg font-black flex items-center gap-1.5 shadow-2xs">
                                <i class="ph ph-timer text-amber-700 font-bold"></i>
                                Short Leave ({{ $req->short_leave_slot ?? '1.5 Hours' }})
                            </span>
                        @endif

                        <!-- Medical Certificate File Link -->
                        @if($req->medical_certificate_path)
                            <a href="{{ $req->medical_certificate_path }}" target="_blank" class="bg-blue-50 text-blue-700 border border-blue-300 hover:bg-blue-100 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5">
                                <i class="ph ph-paperclip text-blue-600"></i>
                                Medical Certificate
                            </a>
                        @endif

                        <!-- Duty Leave Client/Project Badge -->
                        @if($req->project_client_name)
                            <span class="bg-teal-50 text-teal-800 border border-teal-300 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5">
                                Duty: {{ $req->project_client_name }}
                            </span>
                        @endif

                        <!-- Covering Person Badge -->
                        @if($req->coveringEmployee)
                            <div class="flex items-center gap-2">
                                <span class="bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5">
                                    <i class="ph ph-handshake text-blue-600 text-sm"></i>
                                    Covering: {{ $req->coveringEmployee->user->name ?? 'Covering' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Reason string -->
                    <div class="italic text-gray-500 font-medium">
                        "{{ $req->reason }}"
                    </div>
                </div>

                <!-- Bottom Action Controls -->
                <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <!-- 1. Manager Approval Section -->
                    @if($req->status === 'Approved' || $req->manager_status === 'Approved')
                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5">
                            <i class="ph ph-check-circle text-emerald-600 text-base"></i>
                            Approved ({{ $req->managerEmployee->user->name ?? 'Line Manager' }})
                        </span>
                    @elseif($activeEmployee && $req->employee_id === $activeEmployee->id && $role !== 'Super (Admin)')
                        <span class="bg-slate-100 text-slate-600 border border-slate-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 opacity-90" title="Self-approval is prohibited for managers">
                            <i class="ph ph-user text-slate-500"></i>
                            Self Request (Pending Manager)
                        </span>
                    @elseif(in_array($role, ['HOD / Manager', 'Super (Admin)', 'HR Lead']))
                        <form action="{{ route('approvals.action', $req->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="action_type" value="manager_approve">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-1.5 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all shadow-md shadow-emerald-500/20">
                                <i class="ph ph-check-circle text-base"></i>
                                Approve Leave
                            </button>
                        </form>

                        <form action="{{ route('approvals.action', $req->id) }}" method="POST" onsubmit="return confirm('Reject this leave request?')">
                            @csrf
                            <input type="hidden" name="action_type" value="reject_manager">
                            <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-300 px-4 py-1.5 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all">
                                <i class="ph ph-x-circle text-base"></i>
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
