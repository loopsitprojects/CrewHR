@extends('layouts.app', ['title' => 'LOOPS HR - Salary Advances & Approvals', 'breadcrumb' => 'Salary Advances'])

@section('content')
<div class="space-y-6" x-data="{ 
    applyModal: false, 
    rejectModal: false,
    selectedAdvanceForReject: null
}">

    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-emerald-600 text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-warning-circle text-rose-600 text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-yellow-600 text-white flex items-center justify-center shadow-md shadow-amber-500/20">
                    <i class="ph ph-hand-coins text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Salary Advances & Approvals</h1>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Mid-Month Advance Requests • Multi-Tier Approval Workflow • Automatic Payroll Deductions & Recovery.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button @click="applyModal = true" class="bg-gradient-to-r from-amber-500 to-yellow-600 hover:from-amber-600 hover:to-yellow-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-amber-500/25 transition-all cursor-pointer">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Request Salary Advance</span>
            </button>

            @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                <a href="{{ route('payroll.advances.export', ['month' => $selectedMonth, 'year' => $selectedYear, 'status' => $statusFilter]) }}" 
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
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Approved Advances</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400">LKR {{ number_format($totalApprovedAmount, 2) }}</div>
            <span class="text-[11px] font-bold text-slate-500">In {{ \Carbon\Carbon::create()->month($selectedMonth)->format('F') }} {{ $selectedYear }}</span>
        </div>

        <div class="bg-white dark:bg-[#152038] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Pending Approval Amount</span>
            <div class="text-2xl font-black text-blue-600 dark:text-blue-400">LKR {{ number_format($pendingAmount, 2) }}</div>
            <span class="text-[11px] font-bold text-slate-500">{{ $pendingRequestsCount }} request(s) awaiting review</span>
        </div>

        <div class="bg-white dark:bg-[#152038] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Deducted in Payroll</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">LKR {{ number_format($deductedInPayroll, 2) }}</div>
            <span class="text-[11px] font-bold text-slate-500">Recovered via monthly payslips</span>
        </div>

        <div class="bg-white dark:bg-[#152038] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Applications</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $advances->count() }}</div>
            <span class="text-[11px] font-bold text-slate-500">Across all statuses</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-[#152038] p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <form method="GET" action="{{ route('payroll.advances.index') }}" class="flex flex-wrap items-center gap-3 w-full">
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
                    <option value="Approved" {{ $statusFilter === 'Approved' ? 'selected' : '' }}>Approved (Queued)</option>
                    <option value="Deducted" {{ $statusFilter === 'Deducted' ? 'selected' : '' }}>Deducted in Payroll</option>
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
                <a href="{{ route('payroll.advances.index') }}" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline ml-auto">
                    Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Advances Applications Table -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="ph ph-list-dashes text-lg text-amber-500"></i>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Salary Advance Requests & Ledger</h3>
            </div>
            <span class="text-xs font-bold text-slate-400">{{ $advances->count() }} Records</span>
        </div>

        <div class="overflow-x-auto border border-slate-200/80 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Advance ID</th>
                        <th class="p-3">Employee</th>
                        <th class="p-3">Department</th>
                        <th class="p-3 text-center">Cycle Month / Year</th>
                        <th class="p-3 text-right">Requested Amount</th>
                        <th class="p-3">Reason</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                    @forelse($advances as $adv)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-[#1e2d4d]/60 transition-colors">
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $adv->advance_number ?? ('ADV-' . $adv->id) }}</div>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $adv->requested_date ? $adv->requested_date->format('M d, Y') : ($adv->created_at ? $adv->created_at->format('M d, Y') : 'N/A') }}</span>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $adv->employee?->user?->name ?? 'N/A' }}</div>
                                <span class="text-[10px] text-slate-400">{{ $adv->employee?->employee_id_number ?? 'EMP' }}</span>
                            </td>
                            <td class="p-3 text-xs text-slate-500 dark:text-slate-400">
                                {{ $adv->employee?->department?->name ?? 'N/A' }}
                            </td>
                            <td class="p-3 text-center font-bold text-slate-800 dark:text-slate-200">
                                {{ \Carbon\Carbon::create()->month($adv->month)->format('F') }} {{ $adv->year }}
                            </td>
                            <td class="p-3 text-right font-black text-amber-600 dark:text-amber-400 text-sm">
                                LKR {{ number_format($adv->amount, 2) }}
                            </td>
                            <td class="p-3 text-xs text-slate-500 max-w-xs truncate">
                                {{ $adv->reason ?? '—' }}
                            </td>
                            <td class="p-3 text-center">
                                @if($adv->status === 'Approved')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Approved (Queued)</span>
                                @elseif($adv->status === 'Deducted')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Deducted in Payroll</span>
                                @elseif($adv->status === 'Pending')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Pending Review</span>
                                @elseif($adv->status === 'Rejected')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">Rejected</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">{{ $adv->status }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) && $adv->status === 'Pending')
                                        <form method="POST" action="{{ route('payroll.advances.approve', $adv->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" title="Approve Advance" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold text-xs flex items-center gap-1 transition-all">
                                                <i class="ph ph-check font-bold"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>

                                        <button type="button" @click="selectedAdvanceForReject = {{ $adv->id }}; rejectModal = true" title="Reject Advance" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-all">
                                            <i class="ph ph-x text-sm font-bold"></i>
                                        </button>
                                    @elseif($adv->status === 'Pending' && $adv->employee_id === $activeEmp?->id)
                                        <form method="POST" action="{{ route('payroll.advances.cancel', $adv->id) }}" class="inline" onsubmit="return confirm('Cancel this advance request?')">
                                            @csrf
                                            <button type="submit" class="text-xs text-slate-400 hover:text-rose-600 font-bold">
                                                Cancel
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-slate-400 font-medium">Processed</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 text-xs">
                                No salary advance records found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Request Salary Advance -->
    <div x-show="applyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-4" @click.away="applyModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/80 text-amber-600 flex items-center justify-center">
                        <i class="ph ph-hand-coins text-base"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Apply for Salary Advance</h3>
                </div>
                <button @click="applyModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form method="POST" action="{{ route('payroll.advances.apply') }}" class="space-y-4">
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

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Advance Amount (LKR)</label>
                    <input type="number" step="1000" min="1000" max="200000" name="amount" placeholder="e.g. 25000" required class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold p-2.5 text-slate-800 dark:text-slate-200">
                    <span class="text-[10px] text-slate-400 block mt-1">Amount will be deducted automatically from the next monthly payroll run.</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deduction Month</label>
                        <select name="month" class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold p-2.5 text-slate-800 dark:text-slate-200">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deduction Year</label>
                        <select name="year" class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold p-2.5 text-slate-800 dark:text-slate-200">
                            @for($y = date('Y'); $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Reason / Purpose</label>
                    <textarea name="reason" rows="3" required placeholder="State emergency reason (e.g. medical, emergency family expense)..." class="w-full bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold p-2.5 text-slate-800 dark:text-slate-200"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" @click="applyModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="bg-gradient-to-r from-amber-500 to-yellow-600 hover:from-amber-600 hover:to-yellow-700 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-md shadow-amber-500/20">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Reject Salary Advance -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="rejectModal = false">
            <h3 class="text-base font-black text-rose-600">Reject Advance Application</h3>
            <p class="text-xs text-slate-500">Please provide a valid explanation for declining this salary advance request.</p>

            <form :action="'{{ url('/payrolls/advances') }}/' + selectedAdvanceForReject + '/reject'" method="POST" class="space-y-4">
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
