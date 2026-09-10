@extends('layouts.app', ['title' => 'LOOPS HR - Overtime Management Sub-Module', 'breadcrumb' => 'Overtime Management'])

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: '{{ in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) ? 'all' : 'my' }}', 
    claimModal: false, 
    rejectModal: false,
    selectedRecordForReject: null,
    calcStartTime: '17:30',
    calcEndTime: '20:30',
    calcMultiplierType: 'Standard (1.5x)',
    get computedHours() {
        if (!this.calcStartTime || !this.calcEndTime) return 0;
        let [sH, sM] = this.calcStartTime.split(':').map(Number);
        let [eH, eM] = this.calcEndTime.split(':').map(Number);
        let diffMinutes = (eH * 60 + eM) - (sH * 60 + sM);
        if (diffMinutes < 0) diffMinutes += 24 * 60;
        return (diffMinutes / 60).toFixed(2);
    }
}">


    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-md shadow-amber-500/20">
                    <i class="ph ph-clock-countdown text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Overtime Management Sub-Module</h1>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Claims Submission • Multi-Tier Approval Workflow • Standard (1.5x), Double & Holiday Multipliers • Automated Payroll Integration.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button @click="claimModal = true" class="bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-orange-500/25 transition-all">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Log Overtime Claim</span>
            </button>

            @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                <a href="{{ route('payroll.overtime.export', ['month' => $selectedMonth, 'year' => $selectedYear, 'status' => $statusFilter]) }}" 
                   class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-3.5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-download-simple text-base"></i>
                    <span>Export CSV</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#152038] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Approved Hours</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($totalHoursApproved, 1) }} hrs</div>
            <span class="text-[11px] font-bold text-slate-500">In selected period</span>
        </div>

        <div class="bg-white dark:bg-[#152038] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Estimated OT Payout</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">LKR {{ number_format($totalAmountApproved, 2) }}</div>
            <span class="text-[11px] font-bold text-slate-500">Approved for payroll</span>
        </div>

        <div class="bg-white dark:bg-[#152038] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Pending Approvals</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ $pendingRequestsCount }}</div>
            <span class="text-[11px] font-bold text-slate-500">Awaiting HR/Manager review</span>
        </div>

        <div class="bg-white dark:bg-[#152038] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Claims Logged</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $totalClaimCount }}</div>
            <span class="text-[11px] font-bold text-slate-500">Across all statuses</span>
        </div>
    </div>

    <!-- Filter Bar & Search -->
    <div class="bg-white dark:bg-[#152038] p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <form method="GET" action="{{ route('payroll.overtime.index') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500">Year:</label>
                <select name="year" onchange="this.form.submit()" class="bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 px-3 py-2">
                    @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500">Month:</label>
                <select name="month" onchange="this.form.submit()" class="bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 px-3 py-2">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500">Status:</label>
                <select name="status" onchange="this.form.submit()" class="bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 px-3 py-2">
                    <option value="All" {{ $statusFilter === 'All' ? 'selected' : '' }}>All Statuses</option>
                    <option value="Pending" {{ $statusFilter === 'Pending' ? 'selected' : '' }}>Pending Review</option>
                    <option value="Approved" {{ $statusFilter === 'Approved' ? 'selected' : '' }}>Approved</option>
                    <option value="Rejected" {{ $statusFilter === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="Cancelled" {{ $statusFilter === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-500">Department:</label>
                    <select name="department_id" onchange="this.form.submit()" class="bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 px-3 py-2">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ $deptFilter == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($statusFilter !== 'All' || $deptFilter)
                <a href="{{ route('payroll.overtime.index') }}" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline ml-auto">
                    Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Overtime Claims Table -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="ph ph-list-dashes text-lg text-amber-500"></i>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Overtime Claims & Duty Records</h3>
            </div>
            <span class="text-xs font-bold text-slate-400">{{ $records->count() }} Records</span>
        </div>

        <div class="overflow-x-auto border border-slate-200/80 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Claim ID / Date</th>
                        <th class="p-3">Employee</th>
                        <th class="p-3">Department</th>
                        <th class="p-3 text-center">Timing</th>
                        <th class="p-3 text-center">Hours</th>
                        <th class="p-3 text-center">Rate Multiplier</th>
                        <th class="p-3 text-right">Estimated Amount</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                    @forelse($records as $rec)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-[#1e2d4d]/60 transition-colors">
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $rec->ot_number }}</div>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $rec->ot_date->format('M d, Y') }}</span>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $rec->employee?->user?->name ?? 'N/A' }}</div>
                                <span class="text-[10px] text-slate-400">{{ $rec->employee?->employee_id_number ?? 'EMP' }}</span>
                            </td>
                            <td class="p-3 text-xs text-slate-500 dark:text-slate-400">
                                {{ $rec->employee?->department?->name ?? 'N/A' }}
                            </td>
                            <td class="p-3 text-center text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-300">{{ date('h:i A', strtotime($rec->start_time)) }} &rarr; {{ date('h:i A', strtotime($rec->end_time)) }}</span>
                            </td>
                            <td class="p-3 text-center font-black text-slate-900 dark:text-white">
                                {{ $rec->hours }} hrs
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    {{ $rec->rate_multiplier_type }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-black text-emerald-600 dark:text-emerald-400">
                                LKR {{ number_format($rec->estimated_amount, 2) }}
                            </td>
                            <td class="p-3 text-center">
                                @if($rec->status === 'Approved')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Approved</span>
                                @elseif($rec->status === 'Pending')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Pending</span>
                                @elseif($rec->status === 'Rejected')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">Rejected</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">{{ $rec->status }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) && $rec->status === 'Pending')
                                        <form method="POST" action="{{ route('payroll.overtime.approve', $rec->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" title="Approve Claim" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-all">
                                                <i class="ph ph-check text-sm font-bold"></i>
                                            </button>
                                        </form>

                                        <button type="button" @click="selectedRecordForReject = {{ $rec->id }}; rejectModal = true" title="Reject Claim" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-all">
                                            <i class="ph ph-x text-sm font-bold"></i>
                                        </button>
                                    @elseif($rec->status === 'Pending' && $rec->employee_id === $activeEmp?->id)
                                        <form method="POST" action="{{ route('payroll.overtime.cancel', $rec->id) }}" class="inline" onsubmit="return confirm('Cancel this overtime claim?')">
                                            @csrf
                                            <button type="submit" class="text-xs text-slate-400 hover:text-rose-600 font-bold">
                                                Cancel
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-slate-400 font-medium">Completed</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400 text-xs">
                                No overtime claim records found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Log Overtime Claim -->
    <div x-show="claimModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-4" @click.away="claimModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/80 text-amber-600 flex items-center justify-center">
                        <i class="ph ph-clock-clockwise text-base"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Log Overtime Claim</h3>
                </div>
                <button @click="claimModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form method="POST" action="{{ route('payroll.overtime.store') }}" class="space-y-4">
                @csrf

                @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Employee</label>
                        <select name="employee_id" class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold p-2.5 text-slate-800 dark:text-slate-200">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $activeEmp?->id == $emp->id ? 'selected' : '' }}>{{ $emp->user->name ?? 'N/A' }} ({{ $emp->employee_id_number ?? 'EMP' }} - {{ $emp->department->name ?? 'Dept' }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Overtime Date</label>
                        <input type="date" name="ot_date" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold p-2.5 text-slate-800 dark:text-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Rate Multiplier</label>
                        <select name="rate_multiplier_type" x-model="calcMultiplierType" class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold p-2.5 text-slate-800 dark:text-slate-200">
                            <option value="Standard (1.5x)">Standard (1.5x) - Weekdays</option>
                            <option value="Double (2.0x)">Double (2.0x) - Weekends / Night</option>
                            <option value="Holiday (2.5x)">Holiday (2.5x) - Public Holidays</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Start Time</label>
                        <input type="time" name="start_time" x-model="calcStartTime" required class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold p-2.5 text-slate-800 dark:text-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">End Time</label>
                        <input type="time" name="end_time" x-model="calcEndTime" required class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold p-2.5 text-slate-800 dark:text-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Duration (Hrs)</label>
                        <input type="number" step="0.1" min="0.5" max="24" name="hours" :value="computedHours" required class="w-full bg-slate-100 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-black p-2.5 text-amber-600 dark:text-amber-400 text-center">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Reason / Task Description</label>
                    <textarea name="reason" rows="3" required placeholder="Detail the emergency tasks or project deliveries worked on..." class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold p-2.5 text-slate-800 dark:text-slate-200"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" @click="claimModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-md shadow-orange-500/20">
                        Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Reject Overtime Claim -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="rejectModal = false">
            <h3 class="text-base font-black text-rose-600">Reject Overtime Claim</h3>
            <p class="text-xs text-slate-500">Please provide a valid explanation for rejecting this overtime request.</p>

            <form :action="'{{ url('/payrolls/overtime') }}/' + selectedRecordForReject + '/reject'" method="POST" class="space-y-4">
                @csrf
                <textarea name="rejection_reason" rows="3" required placeholder="Specify reason for rejection..." class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold p-2.5 text-slate-800 dark:text-slate-200"></textarea>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" @click="rejectModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500">Cancel</button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-xs font-bold">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
