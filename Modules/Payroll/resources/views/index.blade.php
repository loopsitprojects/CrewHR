@extends('layouts.app', ['title' => 'LOOPS HR - Monthly Payroll & Payslips', 'breadcrumb' => 'Payroll'])

@section('content')
<div class="space-y-6" x-data="{ payslipModal: false, activeEmp: null, loanModal: false, advanceModal: false }">

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-emerald-600 text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    <!-- Header Banner & Controls -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <i class="ph ph-receipt text-2xl text-blue-600"></i>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Enterprise Monthly Payroll & Payslips</h1>
            </div>
            <p class="text-xs font-semibold text-slate-500 mt-1">Sri Lanka Shop & Office Act Compliant • Multi-Staff Categories • OT & No-Pay Rules • Loans & Advances • Cash Denomination.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Action Buttons for Loans & Advances -->
            <button @click="loanModal = true" class="bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-2xs transition-all">
                <i class="ph ph-bank text-base"></i>
                <span>Issue Loan</span>
            </button>

            <button @click="advanceModal = true" class="bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-2xs transition-all">
                <i class="ph ph-hand-coins text-base"></i>
                <span>Salary Advance</span>
            </button>

            <!-- Process Payroll Trigger Form -->
            <form action="{{ route('payroll.process') }}" method="POST">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-blue-500/25 transition-all">
                    <i class="ph ph-lightning text-base"></i>
                    <span>Process {{ $monthName }} {{ $year }} Payroll</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Filters Bar (Month, Year, Staff Category, Payment Method) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <form action="{{ route('payroll.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full md:w-auto text-xs font-bold text-slate-600">
            <div class="flex items-center gap-1.5">
                <span class="text-slate-400 font-semibold">Cycle:</span>
                <select name="month" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-2 px-3">
                    @foreach($monthsList as $num => $name)
                        <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <select name="year" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-2 px-3">
                    @foreach($yearsList as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-1.5">
                <span class="text-slate-400 font-semibold">Staff Category:</span>
                <select name="staff_category" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-2 px-3">
                    @foreach($categoriesList as $cat)
                        <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-1.5">
                <span class="text-slate-400 font-semibold">Payment Method:</span>
                <select name="payment_method" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-2 px-3">
                    @foreach($paymentMethodsList as $pm)
                        <option value="{{ $pm }}" {{ $paymentMethodFilter == $pm ? 'selected' : '' }}>{{ $pm }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="flex items-center gap-2">
            @if($payroll)
                <a href="{{ route('payroll.export_bank_advice', $payroll->id) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-download-simple"></i> Bank Advice CSV
                </a>

                <a href="{{ route('payroll.export_cash_denomination', $payroll->id) }}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-money"></i> Cash Denomination CSV
                </a>
            @endif
        </div>
    </div>

    <!-- Payroll Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Total Gross Payroll</span>
            <div class="text-xl font-black text-slate-900">Rs. {{ number_format($totalGross, 2) }}</div>
            <span class="text-[11px] font-bold text-emerald-600">{{ $cycleName }} ({{ $category }})</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Total EPF Employer (12%)</span>
            <div class="text-xl font-black text-blue-600">Rs. {{ number_format($totalEpfEmployer, 2) }}</div>
            <span class="text-[11px] font-bold text-slate-400">Statutory Fund Contribution</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Total ETF Employer (3%)</span>
            <div class="text-xl font-black text-blue-600">Rs. {{ number_format($totalEtfEmployer, 2) }}</div>
            <span class="text-[11px] font-bold text-slate-400">Statutory Fund Contribution</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Total Net Take-Home</span>
            <div class="text-xl font-black text-emerald-600">Rs. {{ number_format($totalNetPay, 2) }}</div>
            <span class="text-[11px] font-bold text-emerald-600">Ready for Bank/Cash Payout</span>
        </div>
    </div>

    <!-- Payroll Master Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">{{ $cycleName }} Employee Master Sheet</h2>
                @if($payroll)
                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-emerald-300">
                        Processed on {{ \Carbon\Carbon::parse($payroll->processed_at)->format('M d, Y') }}
                    </span>
                @else
                    <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-amber-300">
                        Live Preview (Click Process to Save)
                    </span>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-semibold text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Employee & Category</th>
                        <th class="p-3">Method</th>
                        <th class="p-3">Basic Salary</th>
                        <th class="p-3">OT & Shift Allow.</th>
                        <th class="p-3">Gross Salary</th>
                        <th class="p-3">Statutory (EPF 8% / APIT)</th>
                        <th class="p-3">Deductions (No-Pay/Loans)</th>
                        <th class="p-3">Net Take-Home</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @if($payroll && $payslips->count() > 0)
                        @foreach($payslips as $ps)
                            @php $emp = $ps->employee; @endphp
                            <tr>
                                <td class="p-3 font-bold text-slate-900">
                                    <div class="flex items-center gap-2.5">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($emp->user->name ?? 'Employee') }}&background=0D8ABC&color=fff" class="w-7 h-7 rounded-full border border-blue-500" alt="Avatar">
                                        <div>
                                            <div>{{ $emp->user->name ?? 'Employee' }}</div>
                                            <div class="text-[10px] text-slate-400 font-normal">
                                                <span class="bg-slate-100 text-slate-700 px-1.5 py-0.2 rounded font-bold">{{ $ps->staff_category }}</span> • {{ $emp->employee_id_number ?? ('EMP-'.$emp->id) }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $ps->payment_method === 'Cash' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-blue-100 text-blue-900 border border-blue-300' }}">
                                        {{ $ps->payment_method }}
                                    </span>
                                </td>
                                <td class="p-3">Rs. {{ number_format($ps->basic_salary, 2) }}</td>
                                <td class="p-3">
                                    <div class="text-slate-900 font-bold">Rs. {{ number_format($ps->ot_amount + $ps->shift_allowance, 2) }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $ps->ot_hours }}h OT</div>
                                </td>
                                <td class="p-3 font-bold text-slate-900">Rs. {{ number_format($ps->gross_salary, 2) }}</td>
                                <td class="p-3 text-rose-600 font-bold">
                                    - Rs. {{ number_format($ps->epf_employee + $ps->apit_tax, 2) }}
                                </td>
                                <td class="p-3 text-rose-600 font-bold">
                                    - Rs. {{ number_format($ps->no_pay_deduction + $ps->loan_installment + $ps->salary_advance, 2) }}
                                    @if($ps->no_pay_days > 0)
                                        <div class="text-[10px] text-amber-600 font-semibold">{{ $ps->no_pay_days }}d No-Pay</div>
                                    @endif
                                </td>
                                <td class="p-3 font-black text-emerald-600">Rs. {{ number_format($ps->net_salary, 2) }}</td>
                                <td class="p-3 text-right">
                                    <button @click="activeEmp = {
                                        name: '{{ addslashes($emp->user->name ?? 'Employee') }}',
                                        id: '{{ $emp->employee_id_number ?? ('EMP-'.$emp->id) }}',
                                        epf: '{{ $emp->epf_registration_no ?? 'N/A' }}',
                                        category: '{{ $ps->staff_category }}',
                                        method: '{{ $ps->payment_method }}',
                                        dept: '{{ addslashes($emp->department->name ?? 'IT') }}',
                                        designation: '{{ addslashes($emp->designation->name ?? 'Staff') }}',
                                        bank: '{{ addslashes($emp->bank_name ?? 'Commercial Bank PLC') }}',
                                        account: '{{ $emp->account_number ?? '0010098234' }}',
                                        basic: '{{ number_format($ps->basic_salary, 2) }}',
                                        fixedAllowances: '{{ number_format($ps->fixed_allowance + $ps->other_allowance, 2) }}',
                                        otAmount: '{{ number_format($ps->ot_amount, 2) }}',
                                        otHours: '{{ $ps->ot_hours }}',
                                        shiftAllowance: '{{ number_format($ps->shift_allowance, 2) }}',
                                        gross: '{{ number_format($ps->gross_salary, 2) }}',
                                        epf8: '{{ number_format($ps->epf_employee, 2) }}',
                                        epf12: '{{ number_format($ps->epf_employer, 2) }}',
                                        etf3: '{{ number_format($ps->etf_employer, 2) }}',
                                        apit: '{{ number_format($ps->apit_tax, 2) }}',
                                        noPayDeduction: '{{ number_format($ps->no_pay_deduction, 2) }}',
                                        noPayDays: '{{ $ps->no_pay_days }}',
                                        loanInstallment: '{{ number_format($ps->loan_installment, 2) }}',
                                        salaryAdvance: '{{ number_format($ps->salary_advance, 2) }}',
                                        net: '{{ number_format($ps->net_salary, 2) }}',
                                        monthYear: '{{ $monthName }} {{ $year }}'
                                    }; payslipModal = true" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-xs font-bold border border-blue-200 shadow-2xs">
                                        View Payslip
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        @foreach($employees as $emp)
                            @php
                                $basic = $emp->basic_salary ?: 180000;
                                $allowances = ($emp->fixed_allowance ?: 30000) + ($emp->other_allowance ?: 20000);
                                $gross = $basic + $allowances;
                                $epf8 = $basic * 0.08;
                                $epf12 = $basic * 0.12;
                                $etf3 = $basic * 0.03;
                                $apit = $emp->apit_tax ?: 10000;
                                $net = $gross - $epf8 - $apit;
                            @endphp
                            <tr>
                                <td class="p-3 font-bold text-slate-900">
                                    <div class="flex items-center gap-2.5">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($emp->user->name ?? 'Employee') }}&background=0D8ABC&color=fff" class="w-7 h-7 rounded-full border border-blue-500" alt="Avatar">
                                        <div>
                                            <div>{{ $emp->user->name ?? 'Employee' }}</div>
                                            <div class="text-[10px] text-slate-400 font-normal">
                                                <span class="bg-slate-100 text-slate-700 px-1.5 py-0.2 rounded font-bold">{{ $emp->staff_category ?? 'Executive' }}</span> • {{ $emp->employee_id_number ?? ('EMP-'.$emp->id) }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black {{ ($emp->payment_method ?? 'Bank Transfer') === 'Cash' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-blue-100 text-blue-900 border border-blue-300' }}">
                                        {{ $emp->payment_method ?? 'Bank Transfer' }}
                                    </span>
                                </td>
                                <td class="p-3">Rs. {{ number_format($basic, 2) }}</td>
                                <td class="p-3">Rs. 0.00</td>
                                <td class="p-3 font-bold text-slate-900">Rs. {{ number_format($gross, 2) }}</td>
                                <td class="p-3 text-rose-600 font-bold">- Rs. {{ number_format($epf8 + $apit, 2) }}</td>
                                <td class="p-3 text-rose-600 font-bold">- Rs. 0.00</td>
                                <td class="p-3 font-black text-emerald-600">Rs. {{ number_format($net, 2) }}</td>
                                <td class="p-3 text-right">
                                    <button @click="activeEmp = {
                                        name: '{{ addslashes($emp->user->name ?? 'Employee') }}',
                                        id: '{{ $emp->employee_id_number ?? ('EMP-'.$emp->id) }}',
                                        epf: '{{ $emp->epf_registration_no ?? 'N/A' }}',
                                        category: '{{ $emp->staff_category ?? 'Executive' }}',
                                        method: '{{ $emp->payment_method ?? 'Bank Transfer' }}',
                                        dept: '{{ addslashes($emp->department->name ?? 'IT') }}',
                                        designation: '{{ addslashes($emp->designation->name ?? 'Staff') }}',
                                        bank: '{{ addslashes($emp->bank_name ?? 'Commercial Bank PLC') }}',
                                        account: '{{ $emp->account_number ?? '0010098234' }}',
                                        basic: '{{ number_format($basic, 2) }}',
                                        fixedAllowances: '{{ number_format($allowances, 2) }}',
                                        otAmount: '0.00',
                                        otHours: '0',
                                        shiftAllowance: '0.00',
                                        gross: '{{ number_format($gross, 2) }}',
                                        epf8: '{{ number_format($epf8, 2) }}',
                                        epf12: '{{ number_format($epf12, 2) }}',
                                        etf3: '{{ number_format($etf3, 2) }}',
                                        apit: '{{ number_format($apit, 2) }}',
                                        noPayDeduction: '0.00',
                                        noPayDays: '0',
                                        loanInstallment: '0.00',
                                        salaryAdvance: '0.00',
                                        net: '{{ number_format($net, 2) }}',
                                        monthYear: '{{ $monthName }} {{ $year }}'
                                    }; payslipModal = true" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-xs font-bold border border-blue-200 shadow-2xs">
                                        View Payslip
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Issue Staff Loan Modal -->
    <div x-show="loanModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-200" @click.away="loanModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-bank text-purple-600 text-xl"></i>
                    <h3 class="text-base font-black text-slate-900">Record Staff Loan</h3>
                </div>
                <button @click="loanModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('payroll.loans.apply') }}" method="POST" class="space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="auto_approve" value="1">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Select Employee</label>
                    <select name="employee_id" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->user->name ?? 'Employee' }} ({{ $e->employee_id_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Loan Type</label>
                    <select name="loan_type_id" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                        @foreach($loanTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }} (Max: Rs. {{ number_format($lt->max_amount) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Principal Amount (LKR)</label>
                        <input type="number" step="0.01" name="principal_amount" placeholder="100000" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tenure (Months)</label>
                        <input type="number" name="repayment_months" placeholder="12" value="12" min="1" max="120" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Purpose / Notes (Optional)</label>
                    <input type="text" name="purpose" placeholder="e.g. Festival Advance / Emergency" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="loanModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white font-bold rounded-xl shadow-md shadow-purple-500/25">Save & Issue Loan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Approve Salary Advance Modal -->
    <div x-show="advanceModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-200" @click.away="advanceModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-hand-coins text-amber-600 text-xl"></i>
                    <h3 class="text-base font-black text-slate-900">Approve Salary Advance</h3>
                </div>
                <button @click="advanceModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('payroll.advances.store') }}" method="POST" class="space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Select Employee</label>
                    <select name="employee_id" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->user->name ?? 'Employee' }} ({{ $e->employee_id_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Advance Amount (LKR)</label>
                    <input type="number" step="0.01" name="amount" placeholder="25000" required class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Reason / Notes</label>
                    <input type="text" name="reason" placeholder="e.g. Emergency Mid-Month Advance" class="w-full border-slate-200 rounded-xl font-semibold bg-slate-50">
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="advanceModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white font-bold rounded-xl shadow-md shadow-amber-500/25">Approve Advance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Enhanced Printable Payslip Modal -->
    <div x-show="payslipModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-8 shadow-2xl space-y-5 border border-slate-200 max-h-[90vh] overflow-y-auto" @click.away="payslipModal = false">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <span class="text-xs font-extrabold text-blue-600 uppercase tracking-widest block">LOOPS HR PORTAL</span>
                    <h3 class="text-lg font-black text-slate-900">Official Payslip for <span x-text="activeEmp?.monthYear"></span></h3>
                </div>
                <button @click="payslipModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-xl"></i></button>
            </div>

            <!-- Employee Info Box -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 grid grid-cols-2 gap-3 text-xs font-semibold" x-if="activeEmp">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-extrabold">Employee Name</span>
                    <span class="font-bold text-slate-900 text-sm" x-text="activeEmp?.name"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-extrabold">ID / EPF / Category</span>
                    <span class="font-bold text-blue-600" x-text="activeEmp?.id + ' • ' + activeEmp?.epf + ' (' + activeEmp?.category + ')'"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-extrabold">Department & Designation</span>
                    <span class="font-bold text-slate-900" x-text="activeEmp?.dept + ' • ' + activeEmp?.designation"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-extrabold">Payment Method & Bank</span>
                    <span class="font-bold text-slate-900" x-text="activeEmp?.method + ' (' + activeEmp?.bank + ' - ' + activeEmp?.account + ')'"></span>
                </div>
            </div>

            <!-- Detailed Itemized Earnings & Deductions Breakdown -->
            <div class="space-y-3 text-xs font-semibold">
                <div class="text-slate-900 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200 pb-1">Earnings & Allowances</div>
                
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Basic Salary</span>
                    <span class="font-bold text-slate-900">Rs. <span x-text="activeEmp?.basic"></span></span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Fixed & Other Allowances</span>
                    <span class="font-bold text-slate-900">Rs. <span x-text="activeEmp?.fixedAllowances"></span></span>
                </div>
                <template x-if="parseFloat(activeEmp?.otAmount || 0) > 0">
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">Overtime (1.5x) [<span x-text="activeEmp?.otHours"></span> hrs]</span>
                        <span class="font-bold text-slate-900">Rs. <span x-text="activeEmp?.otAmount"></span></span>
                    </div>
                </template>
                <template x-if="parseFloat(activeEmp?.shiftAllowance || 0) > 0">
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">Shift Allowance</span>
                        <span class="font-bold text-slate-900">Rs. <span x-text="activeEmp?.shiftAllowance"></span></span>
                    </div>
                </template>

                <div class="flex justify-between py-1.5 border-t border-b border-slate-200 bg-slate-50 px-2 rounded font-bold text-slate-900">
                    <span>Total Gross Earnings</span>
                    <span>Rs. <span x-text="activeEmp?.gross"></span></span>
                </div>

                <div class="text-slate-900 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200 pb-1 pt-2">Deductions & Statutory Payments</div>

                <div class="flex justify-between py-1 text-rose-600">
                    <span>EPF Employee Contribution (8%)</span>
                    <span>- Rs. <span x-text="activeEmp?.epf8"></span></span>
                </div>
                <div class="flex justify-between py-1 text-rose-600">
                    <span>APIT Tax Deduction</span>
                    <span>- Rs. <span x-text="activeEmp?.apit"></span></span>
                </div>
                <template x-if="parseFloat(activeEmp?.noPayDeduction || 0) > 0">
                    <div class="flex justify-between py-1 text-rose-600">
                        <span>No-Pay Absenteeism Deduction [<span x-text="activeEmp?.noPayDays"></span> days]</span>
                        <span>- Rs. <span x-text="activeEmp?.noPayDeduction"></span></span>
                    </div>
                </template>
                <template x-if="parseFloat(activeEmp?.loanInstallment || 0) > 0">
                    <div class="flex justify-between py-1 text-rose-600">
                        <span>Staff Loan Monthly Installment</span>
                        <span>- Rs. <span x-text="activeEmp?.loanInstallment"></span></span>
                    </div>
                </template>
                <template x-if="parseFloat(activeEmp?.salaryAdvance || 0) > 0">
                    <div class="flex justify-between py-1 text-rose-600">
                        <span>Salary Advance Recovery</span>
                        <span>- Rs. <span x-text="activeEmp?.salaryAdvance"></span></span>
                    </div>
                </template>

                <!-- Statutory Employer Contributions Info -->
                <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100 text-[11px] space-y-1">
                    <div class="text-blue-900 font-extrabold uppercase text-[9px] tracking-wider">Statutory Employer Contributions (Company Paid)</div>
                    <div class="flex justify-between text-slate-600">
                        <span>EPF Employer Contribution (12%):</span>
                        <span class="font-bold text-blue-700">Rs. <span x-text="activeEmp?.epf12"></span></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>ETF Employer Contribution (3%):</span>
                        <span class="font-bold text-blue-700">Rs. <span x-text="activeEmp?.etf3"></span></span>
                    </div>
                </div>

                <!-- Net Take-Home Hero Pill -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white p-4 rounded-xl shadow-md flex items-center justify-between mt-3">
                    <span class="font-extrabold text-xs uppercase tracking-wider">Net Take-Home Pay</span>
                    <span class="text-xl font-black">Rs. <span x-text="activeEmp?.net"></span></span>
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" @click="window.print()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md flex items-center gap-2 transition-all">
                    <i class="ph ph-printer text-base"></i> Print Payslip
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
