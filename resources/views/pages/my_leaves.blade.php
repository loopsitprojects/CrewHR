@extends('layouts.app', ['title' => 'LOOPS HR - Leave Record Directory', 'breadcrumb' => 'Leave Record Directory'])

@section('content')
<div class="flex-1 flex flex-col min-h-0 space-y-4 h-full overflow-hidden" 
     x-data="{ viewModalOpen: false, selectedRequest: null }">

    <!-- Top Header Bar & Action -->
    <div class="flex flex-wrap items-center justify-between gap-3 shrink-0 bg-white dark:bg-[#152038] p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-blue-500/20">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Leave Record Directory</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-500/20 transition-all flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Apply New Leave</span>
            </a>
        </div>
    </div>

    <!-- Main Content Box: Filters & Data Table -->
    <div class="flex-1 bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm flex flex-col min-h-0 overflow-hidden space-y-3">
        
        <!-- Filter Tabs & Search Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 shrink-0 pb-3 border-b border-slate-100">
            <!-- Status Tabs -->
            <div class="flex items-center gap-1 bg-slate-100/90 p-1 rounded-full text-xs font-bold overflow-x-auto">
                <a href="{{ route('my-leaves.index', ['status' => 'all', 'search' => $search, 'leave_type_id' => $leaveTypeId, 'year' => $year, 'month' => $month]) }}" 
                   class="px-3.5 py-1.5 rounded-full transition-all whitespace-nowrap {{ $status === 'all' ? 'bg-white text-blue-600 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900' }}">
                    All ({{ $counts['all'] }})
                </a>
                <a href="{{ route('my-leaves.index', ['status' => 'pending', 'search' => $search, 'leave_type_id' => $leaveTypeId, 'year' => $year, 'month' => $month]) }}" 
                   class="px-3.5 py-1.5 rounded-full transition-all whitespace-nowrap {{ $status === 'pending' ? 'bg-white text-amber-600 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900' }}">
                    Pending ({{ $counts['pending'] }})
                </a>
                <a href="{{ route('my-leaves.index', ['status' => 'approved', 'search' => $search, 'leave_type_id' => $leaveTypeId, 'year' => $year, 'month' => $month]) }}" 
                   class="px-3.5 py-1.5 rounded-full transition-all whitespace-nowrap {{ $status === 'approved' ? 'bg-white text-emerald-600 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900' }}">
                    Approved ({{ $counts['approved'] }})
                </a>
                <a href="{{ route('my-leaves.index', ['status' => 'rejected', 'search' => $search, 'leave_type_id' => $leaveTypeId, 'year' => $year, 'month' => $month]) }}" 
                   class="px-3.5 py-1.5 rounded-full transition-all whitespace-nowrap {{ $status === 'rejected' ? 'bg-white text-rose-600 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900' }}">
                    Rejected/Canceled ({{ $counts['rejected'] }})
                </a>
            </div>

            <!-- Search Form -->
            <form action="{{ route('my-leaves.index') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search leave type, REQ#, reason..." 
                           class="w-56 border-slate-200 bg-slate-50 rounded-full text-xs font-bold py-1.5 pl-8 pr-3 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                </div>

                <div class="relative">
                    <select name="leave_type_id" onchange="this.form.submit()" class="appearance-none bg-slate-50 border border-slate-200 rounded-full text-xs font-bold text-slate-800 py-1.5 pl-3 pr-7 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer">
                        <option value="">All Types</option>
                        @foreach($leaveTypes as $lt)
                            @if(!in_array(strtolower($lt->name ?? ''), ['half day leave', 'half day', 'maternity leave', 'maternity', 'paternity leave', 'paternity']) && !in_array(strtoupper($lt->code ?? ''), ['HALF', 'MATERNITY', 'PATERNITY', 'MAT', 'PAT']))
                            <option value="{{ $lt->id }}" {{ $leaveTypeId == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none"></i>
                </div>

                <div class="relative">
                    <select name="month" onchange="this.form.submit()" class="appearance-none bg-slate-50 border border-slate-200 rounded-full text-xs font-bold text-slate-800 py-1.5 pl-3 pr-7 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer">
                        <option value="">All Months</option>
                        @foreach($monthsList as $mNum => $mName)
                            <option value="{{ $mNum }}" {{ (string)$month === (string)$mNum ? 'selected' : '' }}>{{ $mName }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none"></i>
                </div>

                <div class="relative">
                    <select name="year" onchange="this.form.submit()" class="appearance-none bg-slate-50 border border-slate-200 rounded-full text-xs font-bold text-slate-800 py-1.5 pl-3 pr-7 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer">
                        @foreach($yearsList as $y)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none"></i>
                </div>
            </form>
        </div>

        <!-- Leave Requests Table Container -->
        <div class="flex-1 overflow-y-auto min-h-0 border border-slate-100 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 sticky top-0 z-10">
                    <tr>
                        <th class="p-3">REQ Number</th>
                        <th class="p-3">Leave Type</th>
                        <th class="p-3">Date Range</th>
                        <th class="p-3">Duration</th>
                        <th class="p-3">Details / Slots</th>
                        <th class="p-3">Covering Person</th>
                        <th class="p-3">Approval Workflow</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold">
                    @forelse($leaveRequests as $req)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- REQ Number -->
                            <td class="p-3">
                                <span class="font-black text-slate-900 bg-slate-100 px-2 py-1 rounded-lg border border-slate-200/80 text-[11px]">
                                    {{ $req->req_number ?? 'REQ-' . $req->id }}
                                </span>
                                <span class="text-[9px] text-slate-400 block mt-1">Applied: {{ $req->applied_at ?? $req->created_at->format('Y-m-d') }}</span>
                            </td>

                            <!-- Leave Type -->
                            <td class="p-3">
                                <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full 
                                        {{ str_contains(strtolower($req->leaveType->name ?? ''), 'annual') ? 'bg-blue-500' : '' }}
                                        {{ str_contains(strtolower($req->leaveType->name ?? ''), 'casual') ? 'bg-purple-500' : '' }}
                                        {{ str_contains(strtolower($req->leaveType->name ?? ''), 'medical') ? 'bg-emerald-500' : '' }}
                                        {{ str_contains(strtolower($req->leaveType->name ?? ''), 'short') ? 'bg-amber-500' : '' }}
                                        {{ str_contains(strtolower($req->leaveType->name ?? ''), 'duty') ? 'bg-teal-500' : '' }}
                                        {{ !str_contains(strtolower($req->leaveType->name ?? ''), 'annual') && !str_contains(strtolower($req->leaveType->name ?? ''), 'casual') && !str_contains(strtolower($req->leaveType->name ?? ''), 'medical') && !str_contains(strtolower($req->leaveType->name ?? ''), 'short') && !str_contains(strtolower($req->leaveType->name ?? ''), 'duty') ? 'bg-indigo-500' : '' }}
                                    "></span>
                                    <span>{{ $req->leaveType->name ?? 'Leave' }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400 truncate max-w-[140px] block" title="{{ $req->reason }}">{{ Str::limit($req->reason, 25) }}</span>
                            </td>

                            <!-- Date Range -->
                            <td class="p-3 text-slate-800">
                                <div class="font-bold">{{ $req->start_date }}</div>
                                <div class="text-[10px] text-slate-400">to {{ $req->end_date }}</div>
                            </td>

                            <!-- Duration -->
                            <td class="p-3">
                                <span class="font-black text-slate-900 bg-blue-50 text-blue-800 px-2 py-0.5 rounded-md border border-blue-200/60 text-[11px]">
                                    {{ $req->is_half_day ? 'Half Day' : ($req->is_short_leave ? 'Short Leave' : $req->duration . ' Days') }}
                                </span>
                            </td>

                            <!-- Details / Special Slots -->
                            <td class="p-3">
                                @if($req->is_half_day)
                                    <span class="bg-purple-50 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded border border-purple-200 flex items-center gap-1 w-max">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Half Day ({{ $req->half_day_slot ?? 'Morning' }})
                                    </span>
                                @elseif($req->is_short_leave)
                                    <span class="bg-amber-50 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded border border-amber-200 flex items-center gap-1 w-max">
                                        <i data-lucide="timer" class="w-3 h-3"></i> Short ({{ $req->short_leave_slot }})
                                    </span>
                                @elseif($req->project_client_name)
                                    <span class="bg-teal-50 text-teal-800 text-[10px] font-bold px-2 py-0.5 rounded border border-teal-200 block truncate max-w-[140px]" title="{{ $req->project_client_name }}">
                                        Duty: {{ $req->project_client_name }}
                                    </span>
                                @elseif($req->medical_certificate_path)
                                    <a href="{{ $req->medical_certificate_path }}" target="_blank" class="bg-blue-50 hover:bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded border border-blue-200 flex items-center gap-1 w-max">
                                        <i data-lucide="paperclip" class="w-3 h-3"></i> Medical Certificate
                                    </a>
                                @else
                                    <span class="text-slate-400 text-[10px] italic">Standard Full Day</span>
                                @endif
                            </td>

                            <!-- Covering Person -->
                            <td class="p-3">
                                @if($req->coveringEmployee)
                                    <div class="text-slate-800 font-bold text-[11px]">{{ $req->coveringEmployee->user->name ?? 'Staff' }}</div>
                                    <span class="text-[9px] text-slate-500 font-medium">Covering Assigned</span>
                                @else
                                    <span class="text-slate-400 text-[10px] italic">None Assigned</span>
                                @endif
                            </td>

                            <!-- Approval Workflow Progress -->
                            <td class="p-3">
                                @if($req->status === 'Approved' || $req->manager_status === 'Approved')
                                    <span class="bg-emerald-100 text-emerald-800 font-black px-2.5 py-1 rounded-full text-[10px] border border-emerald-200 flex items-center gap-1 w-max">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Fully Approved
                                    </span>
                                @elseif(in_array($req->status, ['Rejected', 'Canceled']) || in_array($req->manager_status, ['Rejected']))
                                    <span class="bg-rose-100 text-rose-800 font-black px-2.5 py-1 rounded-full text-[10px] border border-rose-200 flex items-center gap-1 w-max">
                                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> {{ $req->status === 'Canceled' ? 'Canceled' : 'Rejected' }}
                                    </span>
                                @else
                                    <div class="space-y-1">
                                        <span class="bg-amber-100 text-amber-900 font-black px-2.5 py-0.5 rounded-full text-[10px] border border-amber-200 block w-max">
                                            Pending Manager Approval
                                        </span>
                                        <div class="flex items-center gap-1 text-[9px] text-slate-400">
                                            <span>Manager: {{ $req->manager_status }}</span> • <span>HR: {{ $req->hr_status }}</span>
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="selectedRequest = {{ json_encode($req) }}; viewModalOpen = true" 
                                            class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors" title="View Full Details">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>

                                    @if($req->manager_status !== 'Approved' && !in_array($req->status, ['Manager Approved (Pending HR)', 'Approved', 'Rejected', 'Canceled']))
                                        <form action="{{ route('my-leaves.cancel', $req->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this leave request? Deducted days will be instantly refunded to your balance.')">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition-colors" title="Cancel Request & Refund Balance">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 italic">
                                No leave records found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="shrink-0 pt-2 border-t border-slate-100">
            {{ $leaveRequests->links() }}
        </div>
    </div>

    <!-- VIEW LEAVE REQUEST DETAIL MODAL -->
    <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="viewModalOpen = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100">
            <template x-if="selectedRequest">
                <div class="space-y-4 text-xs font-semibold">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <span class="text-sm font-black text-slate-900" x-text="selectedRequest.req_number || ('REQ-' + selectedRequest.id)"></span>
                            <span class="text-[10px] text-slate-400 block" x-text="'Applied on: ' + (selectedRequest.applied_at || selectedRequest.created_at)"></span>
                        </div>
                        <button @click="viewModalOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                    </div>

                    <div class="space-y-2.5">
                        <div class="flex justify-between p-2.5 bg-slate-50 rounded-xl">
                            <span class="text-slate-500 font-bold">Leave Type</span>
                            <span class="font-black text-slate-900" x-text="selectedRequest.leave_type?.name || 'Leave'"></span>
                        </div>

                        <div class="flex justify-between p-2.5 bg-slate-50 rounded-xl">
                            <span class="text-slate-500 font-bold">Date Range</span>
                            <span class="font-black text-slate-900" x-text="selectedRequest.start_date + ' to ' + selectedRequest.end_date"></span>
                        </div>

                        <div class="flex justify-between p-2.5 bg-slate-50 rounded-xl">
                            <span class="text-slate-500 font-bold">Duration</span>
                            <span class="font-black text-blue-600" x-text="selectedRequest.duration + ' Working Days'"></span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded-xl space-y-1">
                            <span class="text-slate-500 font-bold block">Reason</span>
                            <p class="text-slate-800 font-medium" x-text="selectedRequest.reason || 'No reason provided.'"></p>
                        </div>

                        <template x-if="selectedRequest.project_client_name">
                            <div class="p-2.5 bg-teal-50 rounded-xl space-y-1 text-teal-900 border border-teal-200">
                                <span class="font-bold block">Duty Offsite Project / Client</span>
                                <p x-text="selectedRequest.project_client_name"></p>
                            </div>
                        </template>

                        <template x-if="selectedRequest.medical_certificate_path">
                            <div class="p-2.5 bg-blue-50 rounded-xl flex items-center justify-between border border-blue-200">
                                <span class="font-bold text-blue-900">Medical Certificate</span>
                                <a :href="selectedRequest.medical_certificate_path" target="_blank" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-[10px] font-bold">Download File</a>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <button type="button" @click="viewModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200">Close</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
