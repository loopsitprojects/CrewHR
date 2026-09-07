@extends('layouts.app', ['title' => 'LOOPS HR - Loan Management Sub-Module', 'breadcrumb' => 'Loan Management'])

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: 'active', 
    applyModal: false, 
    typeModal: false, 
    paymentModal: false,
    ledgerModal: false,
    selectedLoanForPayment: null,
    selectedLoanLedger: null
}">

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-emerald-600 text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <i class="ph ph-landmark text-2xl text-purple-600"></i>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Staff Loan Management Sub-Module</h1>
            </div>
            <p class="text-xs font-semibold text-slate-500 mt-1">Loan Types • Applications & Multi-Tier Approvals • Payroll Auto-Deductions • Repayment Transaction Ledger.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button @click="applyModal = true" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-purple-500/25 transition-all">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Apply for Loan</span>
            </button>

            @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                <button @click="typeModal = true" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-gear text-base"></i>
                    <span>Configure Loan Types</span>
                </button>

                <a href="{{ route('payroll.loans.export') }}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-download-simple text-base"></i>
                    <span>Export Report CSV</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Active Staff Loans</span>
            <div class="text-2xl font-black text-purple-600">{{ $totalActiveCount }}</div>
            <span class="text-[11px] font-bold text-slate-500">Currently Serviced Loans</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Total Outstanding Portfolio</span>
            <div class="text-2xl font-black text-slate-900">Rs. {{ number_format($totalOutstanding, 2) }}</div>
            <span class="text-[11px] font-bold text-purple-600">Pending Repayments</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Total Loan Capital Issued</span>
            <div class="text-2xl font-black text-blue-600">Rs. {{ number_format($totalIssuedAmount, 2) }}</div>
            <span class="text-[11px] font-bold text-slate-400">Lifetime Issued Value</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Pending Approval Queue</span>
            <div class="text-2xl font-black text-amber-600">{{ $pendingCount }}</div>
            <span class="text-[11px] font-bold text-amber-600">Awaiting HR Approval</span>
        </div>
    </div>

    <!-- Tabbed Navigation Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-2 shadow-sm flex items-center gap-1.5 text-xs font-bold text-slate-600">
        <button @click="activeTab = 'active'" :class="activeTab === 'active' ? 'bg-purple-600 text-white shadow-md shadow-purple-500/20' : 'hover:text-slate-900 hover:bg-slate-100'" class="px-4 py-2 rounded-xl transition-all flex items-center gap-2">
            <i class="ph ph-list-checks text-base"></i>
            <span>Active & Historical Loans ({{ $activeLoans->count() }})</span>
        </button>

        <button @click="activeTab = 'pending'" :class="activeTab === 'pending' ? 'bg-purple-600 text-white shadow-md shadow-purple-500/20' : 'hover:text-slate-900 hover:bg-slate-100'" class="px-4 py-2 rounded-xl transition-all flex items-center gap-2">
            <i class="ph ph-clock text-base"></i>
            <span>Pending Approvals ({{ $pendingLoans->count() }})</span>
        </button>

        <button @click="activeTab = 'types'" :class="activeTab === 'types' ? 'bg-purple-600 text-white shadow-md shadow-purple-500/20' : 'hover:text-slate-900 hover:bg-slate-100'" class="px-4 py-2 rounded-xl transition-all flex items-center gap-2">
            <i class="ph ph-sliders text-base"></i>
            <span>Loan Types Matrix ({{ $loanTypes->count() }})</span>
        </button>

        <button @click="activeTab = 'ledger'" :class="activeTab === 'ledger' ? 'bg-purple-600 text-white shadow-md shadow-purple-500/20' : 'hover:text-slate-900 hover:bg-slate-100'" class="px-4 py-2 rounded-xl transition-all flex items-center gap-2">
            <i class="ph ph-receipt text-base"></i>
            <span>Repayment Transaction Ledger</span>
        </button>
    </div>

    <!-- TAB 1: ACTIVE & HISTORICAL LOANS -->
    <div x-show="activeTab === 'active'" class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight border-b border-slate-100 pb-3">Active & Serviced Loans Master List</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-semibold text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Loan No & Employee</th>
                        <th class="p-3">Loan Type</th>
                        <th class="p-3">Principal Amount</th>
                        <th class="p-3">Monthly Installment</th>
                        <th class="p-3">Total Paid</th>
                        <th class="p-3">Remaining Balance</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activeLoans as $l)
                        <tr>
                            <td class="p-3 font-bold text-slate-900">
                                <div class="flex items-center gap-2.5">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($l->employee->user->name ?? 'Employee') }}&background=6B21A8&color=fff" class="w-7 h-7 rounded-full border border-purple-500" alt="Avatar">
                                    <div>
                                        <div class="font-extrabold text-slate-900">{{ $l->employee->user->name ?? 'Employee' }}</div>
                                        <div class="text-[10px] text-purple-600 font-bold">{{ $l->loan_number ?? "LOAN-{$l->id}" }} • {{ $l->employee->department->name ?? 'IT' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 font-bold text-slate-800">
                                <span class="bg-purple-50 text-purple-900 border border-purple-200 px-2.5 py-0.5 rounded-lg text-[11px]">
                                    {{ $l->loanType->name ?? $l->loan_title }}
                                </span>
                            </td>
                            <td class="p-3 font-bold text-slate-900">Rs. {{ number_format($l->principal_amount, 2) }}</td>
                            <td class="p-3 text-purple-700 font-extrabold">Rs. {{ number_format($l->monthly_installment, 2) }}/m</td>
                            <td class="p-3 text-emerald-600 font-bold">Rs. {{ number_format($l->total_paid, 2) }}</td>
                            <td class="p-3 font-black text-slate-900">Rs. {{ number_format($l->remaining_balance, 2) }}</td>
                            <td class="p-3">
                                @if($l->status === 'Completed')
                                    <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full text-[10px] font-black">Completed</span>
                                @else
                                    <span class="bg-purple-100 text-purple-800 border border-purple-300 px-2.5 py-0.5 rounded-full text-[10px] font-black">Active</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="selectedLoanLedger = {{ json_encode($l->load('repayments')) }}; ledgerModal = true" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1">
                                        <i class="ph ph-receipt"></i> Ledger
                                    </button>

                                    @if($l->status === 'Active' && in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                                        <button @click="selectedLoanForPayment = {{ json_encode($l) }}; paymentModal = true" class="bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1">
                                            <i class="ph ph-plus text-xs"></i> Extra Pay
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 italic font-bold">
                                No active staff loans recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: PENDING APPROVALS QUEUE -->
    <div x-show="activeTab === 'pending'" class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight border-b border-slate-100 pb-3">Loan Applications Pending Approval</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-semibold text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Application No & Employee</th>
                        <th class="p-3">Loan Type</th>
                        <th class="p-3">Requested Amount</th>
                        <th class="p-3">Repayment Period</th>
                        <th class="p-3">Est. Monthly Installment</th>
                        <th class="p-3">Purpose</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pendingLoans as $pl)
                        <tr>
                            <td class="p-3 font-bold text-slate-900">
                                <div class="flex items-center gap-2.5">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($pl->employee->user->name ?? 'Employee') }}&background=D97706&color=fff" class="w-7 h-7 rounded-full border border-amber-500" alt="Avatar">
                                    <div>
                                        <div class="font-extrabold text-slate-900">{{ $pl->employee->user->name ?? 'Employee' }}</div>
                                        <div class="text-[10px] text-amber-600 font-bold">{{ $pl->loan_number ?? "LOAN-{$pl->id}" }} • Applied {{ \Carbon\Carbon::parse($pl->applied_at)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 font-bold text-slate-800">{{ $pl->loanType->name ?? $pl->loan_title }}</td>
                            <td class="p-3 font-black text-slate-900">Rs. {{ number_format($pl->principal_amount, 2) }}</td>
                            <td class="p-3 font-bold text-slate-700">{{ $pl->repayment_months }} Months</td>
                            <td class="p-3 font-black text-purple-600">Rs. {{ number_format($pl->monthly_installment, 2) }}/m</td>
                            <td class="p-3 text-slate-500 italic max-w-xs truncate">{{ $pl->purpose ?? 'N/A' }}</td>
                            <td class="p-3 text-right">
                                @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('payroll.loans.approve', $pl->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg text-xs font-extrabold shadow-2xs">
                                                Approve
                                            </button>
                                        </form>

                                        <form action="{{ route('payroll.loans.reject', $pl->id) }}" method="POST" onsubmit="return confirm('Reject this loan application?')">
                                            @csrf
                                            <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-300 px-3 py-1 rounded-lg text-xs font-bold">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full text-[10px] font-black">Awaiting HR Approval</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 italic font-bold">
                                No pending loan applications in queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: LOAN TYPES MATRIX -->
    <div x-show="activeTab === 'types'" class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Configured Loan Types Presets</h2>
            @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                <button @click="typeModal = true" class="bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 px-3 py-1 rounded-lg text-xs font-bold">
                    + Add New Loan Type
                </button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($loanTypes as $type)
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-sm text-slate-900">{{ $type->name }}</span>
                        <span class="bg-purple-100 text-purple-800 text-[10px] font-extrabold px-2 py-0.5 rounded uppercase">{{ $type->code }}</span>
                    </div>
                    <p class="text-xs text-slate-500 font-normal min-h-[32px]">{{ $type->description ?? 'Standard Company Staff Loan Preset' }}</p>
                    <div class="pt-2 border-t border-slate-200/60 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px]">Max Amount</span>
                            <span class="font-extrabold text-slate-900">Rs. {{ number_format($type->max_amount) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Max Duration</span>
                            <span class="font-extrabold text-purple-600">{{ $type->max_repayment_months }} Months</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TAB 4: REPAYMENT TRANSACTION LEDGER -->
    <div x-show="activeTab === 'ledger'" class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight border-b border-slate-100 pb-3">Recent Repayment Transaction History</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-semibold text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Date</th>
                        <th class="p-3">Loan No & Employee</th>
                        <th class="p-3">Repayment Type</th>
                        <th class="p-3">Amount Paid</th>
                        <th class="p-3">Remaining Balance After</th>
                        <th class="p-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentRepayments as $rr)
                        <tr>
                            <td class="p-3 text-slate-500 font-bold">{{ \Carbon\Carbon::parse($rr->paid_date)->format('M d, Y - h:i A') }}</td>
                            <td class="p-3 font-bold text-slate-900">
                                {{ $rr->loan->employee->user->name ?? 'Employee' }} ({{ $rr->loan->loan_number ?? "LOAN-{$rr->employee_loan_id}" }})
                            </td>
                            <td class="p-3">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black {{ str_contains($rr->repayment_type, 'Payroll') ? 'bg-blue-100 text-blue-900 border border-blue-300' : 'bg-emerald-100 text-emerald-900 border border-emerald-300' }}">
                                    {{ $rr->repayment_type }}
                                </span>
                            </td>
                            <td class="p-3 font-black text-emerald-600">Rs. {{ number_format($rr->amount_paid, 2) }}</td>
                            <td class="p-3 font-extrabold text-slate-900">Rs. {{ number_format($rr->remaining_balance_after, 2) }}</td>
                            <td class="p-3 text-slate-500 italic">{{ $rr->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 italic font-bold">
                                No loan repayments recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Apply for Loan Modal -->
    <div x-show="applyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-200" @click.away="applyModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-plus-circle text-purple-600 text-xl"></i>
                    <h3 class="text-base font-black text-slate-900">Apply for Staff Loan</h3>
                </div>
                <button @click="applyModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('payroll.loans.apply') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Select Employee</label>
                    <select name="employee_id" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ $activeEmp && $activeEmp->id == $e->id ? 'selected' : '' }}>{{ $e->user->name ?? 'Employee' }} ({{ $e->employee_id_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Select Loan Type</label>
                    <select name="loan_type_id" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                        @foreach($loanTypes as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} (Max: Rs. {{ number_format($t->max_amount) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Requested Amount (LKR)</label>
                        <input type="number" step="0.01" name="principal_amount" placeholder="100000" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Repayment Period (Months)</label>
                        <input type="number" name="repayment_months" value="12" min="1" max="120" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Loan Purpose / Remarks</label>
                    <textarea name="purpose" rows="2" placeholder="e.g. Home Repairs / Family Medical Emergency" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50"></textarea>
                </div>

                @if(in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']))
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="auto_approve" id="auto_approve" value="1" checked class="rounded text-purple-600 focus:ring-purple-500">
                        <label for="auto_approve" class="font-extrabold text-purple-900 text-xs">Auto-Approve & Activate Loan Immediately</label>
                    </div>
                @endif

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="applyModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white font-bold rounded-xl shadow-md shadow-purple-500/25">Submit Application</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Configure Loan Type Modal -->
    <div x-show="typeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-200" @click.away="typeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-gear text-purple-600 text-xl"></i>
                    <h3 class="text-base font-black text-slate-900">Add Loan Type Preset</h3>
                </div>
                <button @click="typeModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('payroll.loans.types.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Loan Type Name</label>
                    <input type="text" name="name" placeholder="e.g. Vehicle Loan / Education Loan" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Code (Unique)</label>
                        <input type="text" name="code" placeholder="VEHICLE" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50 uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Max Amount (LKR)</label>
                        <input type="number" name="max_amount" placeholder="500000" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Annual Interest Rate (%)</label>
                        <input type="number" step="0.1" name="interest_rate_annual" value="0" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Max Duration (Months)</label>
                        <input type="number" name="max_repayment_months" value="60" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Description</label>
                    <input type="text" name="description" placeholder="Brief explanation of loan policy..." class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="typeModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white font-bold rounded-xl shadow-md shadow-purple-500/25">Create Loan Type</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Record Extra Payment Modal -->
    <div x-show="paymentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-200" @click.away="paymentModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-receipt text-purple-600 text-xl"></i>
                    <h3 class="text-base font-black text-slate-900">Record Manual Payment</h3>
                </div>
                <button @click="paymentModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <template x-if="selectedLoanForPayment">
                <form :action="'{{ url('/payrolls/loans') }}/' + selectedLoanForPayment.id + '/payment'" method="POST" class="space-y-3 text-xs">
                    @csrf
                    <div class="p-3 bg-purple-50 rounded-xl text-xs font-semibold text-purple-900 border border-purple-100">
                        <div>Loan: <strong x-text="selectedLoanForPayment.loan_number"></strong> (<span x-text="selectedLoanForPayment.loan_title"></span>)</div>
                        <div>Remaining Balance: <strong class="text-purple-700">Rs. <span x-text="parseFloat(selectedLoanForPayment.remaining_balance).toLocaleString()"></span></strong></div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Amount Paid (LKR)</label>
                        <input type="number" step="0.01" name="amount_paid" :max="selectedLoanForPayment.remaining_balance" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Payment Method</label>
                        <select name="repayment_type" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                            <option value="Manual Bank Transfer">Manual Bank Transfer</option>
                            <option value="Cash Payment">Cash Payment</option>
                            <option value="Cheque Payment">Cheque Payment</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Payment Notes / Receipt No</label>
                        <input type="text" name="notes" placeholder="e.g. Receipt #8902 / Early Payoff" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="paymentModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white font-bold rounded-xl shadow-md shadow-purple-500/25">Record Payment</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- Loan Repayment Ledger Modal -->
    <div x-show="ledgerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 border border-slate-200 max-h-[85vh] overflow-y-auto" @click.away="ledgerModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-receipt text-purple-600 text-xl"></i>
                    <h3 class="text-base font-black text-slate-900">Loan Repayment Transaction Ledger</h3>
                </div>
                <button @click="ledgerModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <template x-if="selectedLoanLedger">
                <div class="space-y-4">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 grid grid-cols-3 gap-2 text-xs font-semibold">
                        <div>
                            <span class="text-slate-400 block text-[10px]">Loan No</span>
                            <span class="font-black text-purple-600" x-text="selectedLoanLedger.loan_number || ('LOAN-' + selectedLoanLedger.id)"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Principal Amount</span>
                            <span class="font-bold text-slate-900">Rs. <span x-text="parseFloat(selectedLoanLedger.principal_amount).toLocaleString()"></span></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Remaining Balance</span>
                            <span class="font-extrabold text-rose-600">Rs. <span x-text="parseFloat(selectedLoanLedger.remaining_balance).toLocaleString()"></span></span>
                        </div>
                    </div>

                    <table class="w-full text-left text-xs font-semibold text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase text-[10px]">
                            <tr>
                                <th class="p-2">Date</th>
                                <th class="p-2">Method</th>
                                <th class="p-2">Amount Paid</th>
                                <th class="p-2">Balance After</th>
                                <th class="p-2">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="r in selectedLoanLedger.repayments" :key="r.id">
                                <tr>
                                    <td class="p-2 text-slate-500" x-text="new Date(r.paid_date).toLocaleDateString()"></td>
                                    <td class="p-2">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black" :class="r.repayment_type.includes('Payroll') ? 'bg-blue-100 text-blue-900' : 'bg-emerald-100 text-emerald-900'" x-text="r.repayment_type"></span>
                                    </td>
                                    <td class="p-2 font-black text-emerald-600">Rs. <span x-text="parseFloat(r.amount_paid).toLocaleString()"></span></td>
                                    <td class="p-2 font-bold text-slate-900">Rs. <span x-text="parseFloat(r.remaining_balance_after).toLocaleString()"></span></td>
                                    <td class="p-2 text-slate-500 italic" x-text="r.notes || '-'"></td>
                                </tr>
                            </template>
                            <template x-if="!selectedLoanLedger.repayments || selectedLoanLedger.repayments.length === 0">
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-slate-400 italic">No repayments recorded yet for this loan.</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
