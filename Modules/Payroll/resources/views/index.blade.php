@extends('layouts.app', ['title' => 'LOOPS HR - Monthly Payroll & Payslips', 'breadcrumb' => 'Payroll'])

@section('content')
<div class="space-y-6" x-data="{ 
    payslipModal: false, 
    activePayslip: null, 
    activeEmp: null,
    cformModal: false,
    journalModal: false,
    loanModal: false, 
    advanceModal: false,
    viewPayslip(ps, emp) {
        this.activePayslip = ps;
        this.activeEmp = emp;
        this.payslipModal = true;
    },
    formatAmt(val) {
        if (!val || Number(val) === 0) return '-';
        return Number(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },
    formatTotal(val) {
        return Number(val || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },
    getMonthYear(ps) {
        if (!ps) return '{{ strtoupper($cycleName) }}';
        const months = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];
        return (months[(ps.month || {{ $month }}) - 1] || '{{ strtoupper($monthName) }}') + ' ' + (ps.year || '{{ $year }}');
    }
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

    <!-- Header Banner & Top Controls -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                    <i class="ph ph-receipt text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Enterprise Payroll & Statutory Engine</h1>
                    <p class="text-xs font-semibold text-slate-500">Sri Lanka Shop & Office Act Compliant • Total for EPF Separation • APIT IRD Slabs • Balanced Double-Entry Journal</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
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



    <!-- Filters & Export Toolbar -->
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

        <div class="flex flex-wrap items-center gap-2">
            @if($payroll)
                <a href="{{ route('payroll.export_master_register', $payroll->id) }}" class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-table"></i> Master Register CSV
                </a>

                <a href="{{ route('payroll.export_epf_cform', $payroll->id) }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-file-csv"></i> EPF C-Form CSV
                </a>

                <a href="{{ route('payroll.export_journal', $payroll->id) }}" class="bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-scales"></i> Journal CSV
                </a>

                <a href="{{ route('payroll.export_bank_advice', $payroll->id) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-bank"></i> Bank Advice
                </a>

                <a href="{{ route('payroll.export_cash_denomination', $payroll->id) }}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-money"></i> Cash Notes
                </a>
            @endif
        </div>
    </div>

    <!-- Monthly Salary Master Register Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="ph ph-list-numbers text-lg text-blue-600"></i>
                <h3 class="text-sm font-black text-slate-800">Monthly Salary Master Register & Statutory Breakdown</h3>
            </div>
            <div class="text-xs font-semibold text-slate-400">
                Showing {{ $payroll && $payslips->count() > 0 ? $payslips->count() : $employees->count() }} Employees
            </div>
        </div>

        <div class="overflow-x-auto max-h-[600px]">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50/80 text-slate-600 font-extrabold sticky top-0 z-10 border-b border-slate-200 shadow-2xs">
                    <tr>
                        <th class="p-3 text-slate-400 uppercase tracking-wider">Employee / EPF</th>
                        <th class="p-3 text-slate-400 uppercase tracking-wider">Dept / Cost Center</th>
                        <th class="p-3 text-right text-slate-700">Basic Pay</th>
                        <th class="p-3 text-right text-slate-700">Fixed Allw.</th>
                        <th class="p-3 text-right text-slate-700">Variable / OT</th>
                        <th class="p-3 text-right text-amber-700">No Pay (Days)</th>
                        <th class="p-3 text-right text-indigo-700">Total For EPF</th>
                        <th class="p-3 text-right text-blue-700">Gross Salary</th>
                        <th class="p-3 text-right text-indigo-600">EPF (8%)</th>
                        <th class="p-3 text-right text-indigo-500">EPF (12%)</th>
                        <th class="p-3 text-right text-indigo-400">ETF (3%)</th>
                        <th class="p-3 text-right text-purple-700">APIT Tax</th>
                        <th class="p-3 text-right text-rose-700">Deductions</th>
                        <th class="p-3 text-right text-emerald-700 font-black">Net Salary</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @if($payroll && $payslips->count() > 0)
                        @foreach($payslips as $ps)
                            @php
                                $emp = $ps->employee;
                                $qualifyingEpf = $ps->total_for_epf ?: ($ps->basic_salary + $ps->increments_basic + $ps->budget_allowance);
                                $fixedTotal = $ps->total_fixed_allowance ?: ($ps->travelling_allowance + $ps->cost_of_living_allowance + $ps->increments_allowance + $ps->fixed_allowance);
                                $variableTotal = $ps->total_variable_pay ?: ($ps->ot_amount + $ps->shift_allowance + $ps->incentive_commission + $ps->salary_arrears_basic + $ps->salary_arrears_allowance);
                                $totalDeduct = $ps->total_deductions ?: ($ps->epf_employee + $ps->apit_tax + $ps->loan_installment + $ps->salary_advance + $ps->personal_expense_recovery + $ps->other_deductions);
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="p-3">
                                    <div class="font-extrabold text-slate-900">{{ $emp->user->name ?? 'Employee' }}</div>
                                    <div class="text-[11px] text-slate-400 font-medium">EPF: {{ $emp->epf_registration_no ?? 'EPF-'.$emp->id }} • {{ $ps->staff_category }}</div>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-800">{{ $ps->department_name ?: ($emp->department->name ?? 'Corporate') }}</div>
                                    <span class="inline-block text-[10px] px-2 py-0.5 rounded-md font-extrabold {{ $ps->cost_classification === 'Direct' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ $ps->cost_classification }} Cost
                                    </span>
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-900">
                                    {{ number_format($ps->total_base_pay ?: ($ps->basic_salary + $ps->increments_basic + $ps->budget_allowance), 2) }}
                                    @if($ps->increments_basic > 0 || $ps->budget_allowance > 0)
                                        <div class="text-[10px] text-slate-400">Inc: {{ number_format($ps->increments_basic) }} | Bud: {{ number_format($ps->budget_allowance) }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-mono text-slate-700">
                                    {{ number_format($fixedTotal, 2) }}
                                    @if($ps->travelling_allowance > 0 || $ps->cost_of_living_allowance > 0)
                                        <div class="text-[10px] text-slate-400">Trav: {{ number_format($ps->travelling_allowance) }} | COLA: {{ number_format($ps->cost_of_living_allowance) }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-mono text-slate-700">
                                    {{ number_format($variableTotal, 2) }}
                                    @if($ps->ot_amount > 0 || $ps->incentive_commission > 0)
                                        <div class="text-[10px] text-slate-400">OT: {{ number_format($ps->ot_amount) }} | Inc: {{ number_format($ps->incentive_commission) }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-mono text-amber-700">
                                    @if($ps->no_pay_days > 0)
                                        <span class="font-extrabold">{{ $ps->no_pay_days }}d</span>
                                        <div class="text-[10px] text-rose-500 font-bold">-{{ number_format($ps->total_no_pay_deduction ?: $ps->no_pay_deduction, 2) }}</div>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-mono font-black text-indigo-700 bg-indigo-50/40">
                                    {{ number_format($qualifyingEpf, 2) }}
                                </td>
                                <td class="p-3 text-right font-mono font-extrabold text-blue-800 bg-blue-50/30">
                                    {{ number_format($ps->gross_salary, 2) }}
                                </td>
                                <td class="p-3 text-right font-mono text-indigo-600">
                                    {{ number_format($ps->epf_employee, 2) }}
                                </td>
                                <td class="p-3 text-right font-mono text-indigo-500">
                                    {{ number_format($ps->epf_employer, 2) }}
                                </td>
                                <td class="p-3 text-right font-mono text-indigo-400">
                                    {{ number_format($ps->etf_employer, 2) }}
                                </td>
                                <td class="p-3 text-right font-mono text-purple-700">
                                    {{ number_format($ps->apit_tax, 2) }}
                                </td>
                                <td class="p-3 text-right font-mono text-rose-600">
                                    {{ number_format($totalDeduct, 2) }}
                                    @if($ps->loan_installment > 0 || $ps->salary_advance > 0 || $ps->personal_expense_recovery > 0)
                                        <div class="text-[10px] text-rose-400">
                                            @if($ps->loan_installment > 0) Ln: {{ number_format($ps->loan_installment) }} @endif
                                            @if($ps->salary_advance > 0) Adv: {{ number_format($ps->salary_advance) }} @endif
                                            @if($ps->personal_expense_recovery > 0) Exp: {{ number_format($ps->personal_expense_recovery) }} @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-mono font-black text-emerald-700 bg-emerald-50/40 text-sm">
                                    {{ number_format($ps->net_salary, 2) }}
                                </td>
                                <td class="p-3 text-center">
                                    <button @click="viewPayslip({{ json_encode($ps) }}, {{ json_encode($emp) }})" class="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-all" title="View Pay Advice">
                                        <i class="ph ph-file-text text-base"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        @foreach($employees as $emp)
                            @php
                                $calc = app(\Modules\Payroll\Services\PayrollCalculationService::class)->calculateEmployeePayroll($emp, $month, $year);
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="p-3">
                                    <div class="font-extrabold text-slate-900">{{ $emp->user->name ?? 'Employee' }}</div>
                                    <div class="text-[11px] text-slate-400 font-medium">EPF: {{ $emp->epf_registration_no ?? 'EPF-'.$emp->id }} • {{ $emp->staff_category ?? 'Executive' }}</div>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-800">{{ $calc['department_name'] }}</div>
                                    <span class="inline-block text-[10px] px-2 py-0.5 rounded-md font-extrabold {{ $calc['cost_classification'] === 'Direct' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ $calc['cost_classification'] }} Cost
                                    </span>
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-900">{{ number_format($calc['total_base_pay'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-slate-700">{{ number_format($calc['total_fixed_allowance'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-slate-700">{{ number_format($calc['total_variable_pay'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-slate-400">-</td>
                                <td class="p-3 text-right font-mono font-black text-indigo-700 bg-indigo-50/40">{{ number_format($calc['total_for_epf'], 2) }}</td>
                                <td class="p-3 text-right font-mono font-extrabold text-blue-800 bg-blue-50/30">{{ number_format($calc['gross_salary'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-indigo-600">{{ number_format($calc['epf_employee'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-indigo-500">{{ number_format($calc['epf_employer'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-indigo-400">{{ number_format($calc['etf_employer'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-purple-700">{{ number_format($calc['apit_tax'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-rose-600">{{ number_format($calc['total_deductions'], 2) }}</td>
                                <td class="p-3 text-right font-mono font-black text-emerald-700 bg-emerald-50/40 text-sm">{{ number_format($calc['net_salary'], 2) }}</td>
                                <td class="p-3 text-center">
                                    <span class="text-xs font-bold text-slate-400">Draft</span>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
                <tfoot class="bg-slate-100/90 text-slate-800 font-extrabold border-t-2 border-slate-300">
                    <tr>
                        <td class="p-3" colspan="2">TOTALS ({{ $payroll ? 'Processed' : 'Estimated' }})</td>
                        <td class="p-3 text-right font-mono font-black text-slate-900">{{ number_format($payroll ? $payroll->payslips->sum('basic_salary') : 0, 2) }}</td>
                        <td class="p-3 text-right font-mono text-slate-800">{{ number_format($payroll ? $payroll->payslips->sum('total_fixed_allowance') : 0, 2) }}</td>
                        <td class="p-3 text-right font-mono text-slate-800">{{ number_format($payroll ? $payroll->payslips->sum('total_variable_pay') : 0, 2) }}</td>
                        <td class="p-3 text-right font-mono text-rose-600">-{{ number_format($payroll ? $payroll->payslips->sum('total_no_pay_deduction') : 0, 2) }}</td>
                        <td class="p-3 text-right font-mono font-black text-indigo-800 bg-indigo-100/50">{{ number_format($totalEpfQualifying, 2) }}</td>
                        <td class="p-3 text-right font-mono font-black text-blue-900 bg-blue-100/50">{{ number_format($totalGross, 2) }}</td>
                        <td class="p-3 text-right font-mono text-indigo-700">{{ number_format($totalEpfEmployee, 2) }}</td>
                        <td class="p-3 text-right font-mono text-indigo-700">{{ number_format($totalEpfEmployer, 2) }}</td>
                        <td class="p-3 text-right font-mono text-indigo-700">{{ number_format($totalEtfEmployer, 2) }}</td>
                        <td class="p-3 text-right font-mono text-purple-800">{{ number_format($totalApit, 2) }}</td>
                        <td class="p-3 text-right font-mono text-rose-700">{{ number_format($payroll ? $payroll->payslips->sum('total_deductions') : 0, 2) }}</td>
                        <td class="p-3 text-right font-mono font-black text-emerald-800 bg-emerald-100/60 text-sm">{{ number_format($totalNetPay, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Individual Employee Pay Advice (Naturally Fitted & Perfectly Centered) -->
    <div x-show="payslipModal" data-printable-container="true" id="printable-pay-advice-container" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 print:p-0 print:bg-white print:fixed-none print:overflow-visible" x-cloak>
        <div data-printable-card="true" id="printable-pay-advice-card" class="bg-white mx-auto my-auto w-full max-w-xl md:max-w-2xl shadow-2xl relative flex flex-col overflow-hidden rounded-sm print:p-0 print:border-0 print:shadow-none print:max-w-none print:rounded-none print:h-auto" @click.outside="payslipModal = false">
            <div class="border-2 border-slate-900 bg-white px-5 py-4 sm:px-6 sm:py-5 text-slate-900 font-sans flex flex-col print:border-2 print:border-slate-900 print:overflow-visible print:p-6">
                <!-- Printable/Modal Header -->
                <div class="flex items-center justify-between pb-2 border-b-2 border-slate-900 relative shrink-0">
                    <div class="flex items-center shrink-0">
                        <img src="{{ asset('LoopsBlack.png') }}" alt="LOOPS Logo" class="h-7 sm:h-8 w-auto object-contain">
                    </div>
                    <div class="text-center flex-1 px-2">
                        <h2 class="text-xs sm:text-sm font-black tracking-wider uppercase text-slate-900 leading-tight">LOOPS DIGITAL (PVT) LTD</h2>
                        <h3 class="text-[10px] sm:text-[11px] font-black tracking-widest uppercase text-slate-800 leading-tight mt-0.5">PAY ADVICE</h3>
                    </div>
                    <div class="flex items-center justify-end shrink-0">
                        <div class="flex items-center print:hidden">
                            <button type="button" onclick="window.print()" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold rounded-lg shadow-md flex items-center gap-1 transition-all cursor-pointer">
                                <i class="ph ph-printer text-xs"></i> Print
                            </button>
                            <button @click="payslipModal = false" class="text-slate-400 hover:text-slate-700 p-0.5 ml-1 rounded-lg cursor-pointer">
                                <i class="ph ph-x text-lg"></i>
                            </button>
                        </div>
                        <div class="hidden print:block w-16"></div>
                    </div>
                </div>

                <!-- Employee Info Header Grid -->
                <div class="py-2 border-b-2 border-slate-900 text-[11px] sm:text-xs font-bold grid grid-cols-1 gap-1 shrink-0">
                    <div class="flex">
                        <span class="w-32 sm:w-36 font-black uppercase text-slate-900">NAME :</span>
                        <span class="font-bold text-slate-900 uppercase" x-text="activeEmp?.user?.name || activePayslip?.employee?.user?.name || activeEmp?.name || 'EMPLOYEE'"></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 sm:w-36 font-black uppercase text-slate-900">EMP NO :</span>
                        <span class="font-bold text-slate-900 uppercase" x-text="activeEmp?.epf_registration_no || ('EPF-' + activeEmp?.id)"></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 sm:w-36 font-black uppercase text-slate-900">MONTH & YEAR :</span>
                        <span class="font-bold text-slate-900 uppercase" x-text="getMonthYear(activePayslip)"></span>
                    </div>
                </div>

                <!-- Main Ledger Table -->
                <div class="py-1">
                    <table class="w-full text-[11px] sm:text-xs font-bold border-collapse">
                        <tbody>
                            <!-- Earnings Section -->
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">BASIC SALARY</td>
                                <td class="py-1 w-16 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900 w-32"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">BASIC SALARY FOR THE PERIOD</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900 font-extrabold" x-text="formatTotal(Number(activePayslip?.total_base_pay || activePayslip?.basic_salary || activeEmp?.basic_salary || 0) - Number(activePayslip?.no_pay_basic || 0))"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">FIXED ALLOWANCE</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">FIXED ALLOWANCE FOR THE PERIOD</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900 font-extrabold" x-text="formatTotal(Number(activePayslip?.total_fixed_allowance || activePayslip?.fixed_allowance || 0) - Number(activePayslip?.no_pay_allowance || 0))"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">OTHER</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(Number(activePayslip?.ot_amount || 0) + Number(activePayslip?.shift_allowance || 0) + Number(activePayslip?.incentive_commission || 0))"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">SALARY ARREARES</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.salary_arrears_basic)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">ALLOWANCE ARREARES</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.salary_arrears_allowance)"></td>
                            </tr>
                            <!-- GROSS SALARY -->
                            <tr class="border-t-2 border-b-2 border-slate-900 font-black text-slate-900 bg-slate-50/50">
                                <td class="py-1 sm:py-1.5 uppercase">GROSS SALARY</td>
                                <td class="py-1 sm:py-1.5 text-center"></td>
                                <td class="py-1 sm:py-1.5 text-right font-mono" x-text="formatTotal(activePayslip?.gross_salary)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">ADJUSTMENTS</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900">-</td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">NO PAY DEDUCTIONS</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.total_no_pay_deduction || activePayslip?.no_pay_deduction)"></td>
                            </tr>
                            <!-- TOTAL FOR EPF/ETF -->
                            <tr class="border-t-2 border-b-2 border-slate-900 font-black text-slate-900 bg-slate-50/50">
                                <td class="py-1 sm:py-1.5 uppercase">TOTAL FOR EPF/ETF</td>
                                <td class="py-1 sm:py-1.5 text-center"></td>
                                <td class="py-1 sm:py-1.5 text-right font-mono" x-text="formatTotal(activePayslip?.total_for_epf || activePayslip?.gross_salary)"></td>
                            </tr>

                            <!-- DEDUCTIONS SECTION -->
                            <tr>
                                <td colspan="3" class="pt-2 pb-1 font-black underline uppercase text-slate-900">LESS : DEDUCTIONS</td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">EPF 8%</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.epf_employee)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">CORPORATE PICKME - PERSONAL USE</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.personal_expense_recovery)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">LOAN</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.loan_installment)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">PAYE TAX</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.apit_tax)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">SALARY ADVANCE</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.salary_advance)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">OTHER DEDUCTIONS</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.other_deductions)"></td>
                            </tr>
                            <!-- TOTAL DEDUCTIONS -->
                            <tr class="border-t-2 border-b-2 border-slate-900 font-black text-slate-900 bg-slate-50/50">
                                <td class="py-1 sm:py-1.5 uppercase">TOTAL DEDUCTIONS</td>
                                <td class="py-1 sm:py-1.5 text-center"></td>
                                <td class="py-1 sm:py-1.5 text-right font-mono" x-text="formatTotal(activePayslip?.total_deductions)"></td>
                            </tr>

                            <!-- NET SALARY -->
                            <tr class="border-b-2 border-slate-900 font-black text-slate-900 text-[11px] sm:text-xs bg-slate-50/80">
                                <td class="py-1.5 uppercase">NET SALARY</td>
                                <td class="py-1.5 text-center"></td>
                                <td class="py-1.5 text-right font-mono font-black" x-text="formatTotal(activePayslip?.net_salary)"></td>
                            </tr>

                            <!-- EMPLOYER CONTRIBUTION SECTION -->
                            <tr>
                                <td colspan="3" class="pt-2 pb-1 font-black underline uppercase text-slate-900">EMPLOYER CONTRIBUTION</td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">EPF</td>
                                <td class="py-1 text-center font-bold text-slate-900">12%</td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.epf_employer)"></td>
                            </tr>
                            <tr class="border-b-2 border-slate-900">
                                <td class="py-1 text-slate-900 uppercase">ETF</td>
                                <td class="py-1 text-center font-bold text-slate-900">3%</td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePayslip?.etf_employer)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: EPF / ETF Monthly Remittance Statement (C-Form Central Bank Format) -->
    <div x-show="cformModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
        <div class="bg-white w-full max-w-4xl rounded-3xl border border-slate-200 shadow-2xl p-6 sm:p-8 space-y-6 relative" @click.outside="cformModal = false">
            <button @click="cformModal = false" class="absolute top-6 right-6 p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                <i class="ph ph-x text-lg"></i>
            </button>

            <div class="border-b border-slate-200 pb-4">
                <div class="flex items-center gap-2">
                    <i class="ph ph-file-text text-2xl text-indigo-600"></i>
                    <div>
                        <h2 class="text-lg font-black text-slate-900 tracking-tight">EPF MONTHLY REMITTANCE STATEMENT (FORM C)</h2>
                        <p class="text-xs font-semibold text-slate-500">Central Bank of Sri Lanka EPF Department Format • Reg No: E-88941 • Cycle: {{ $cycleName }}</p>
                    </div>
                </div>
            </div>

            @if($payroll)
                <div class="overflow-x-auto max-h-[400px] border border-slate-200 rounded-2xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-600 font-extrabold sticky top-0 border-b border-slate-200">
                            <tr>
                                <th class="p-3">Member EPF No</th>
                                <th class="p-3">NIC / Identification</th>
                                <th class="p-3">Member Name</th>
                                <th class="p-3 text-right">Total Earnings for EPF</th>
                                <th class="p-3 text-right text-indigo-700">Employee 8%</th>
                                <th class="p-3 text-right text-indigo-600">Employer 12%</th>
                                <th class="p-3 text-right text-indigo-900 font-black">Total 20%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold">
                            @foreach($payroll->payslips as $ps)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-mono font-bold">{{ $ps->employee->epf_registration_no ?? 'EPF-'.$ps->employee_id }}</td>
                                    <td class="p-3 font-mono text-slate-500">{{ $ps->employee->national_id ?? $ps->employee->nic ?? 'N/A' }}</td>
                                    <td class="p-3 font-extrabold text-slate-900">{{ $ps->employee->user->name ?? 'Employee' }}</td>
                                    <td class="p-3 text-right font-mono font-bold">{{ number_format($ps->total_for_epf ?: ($ps->basic_salary + $ps->increments_basic + $ps->budget_allowance), 2) }}</td>
                                    <td class="p-3 text-right font-mono text-indigo-700">{{ number_format($ps->epf_employee, 2) }}</td>
                                    <td class="p-3 text-right font-mono text-indigo-600">{{ number_format($ps->epf_employer, 2) }}</td>
                                    <td class="p-3 text-right font-mono font-black text-indigo-900">{{ number_format($ps->epf_employee + $ps->epf_employer, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-indigo-50/80 font-black text-indigo-950 border-t border-indigo-200">
                            <tr>
                                <td class="p-3" colspan="3">TOTAL REMITTANCE PAYABLE</td>
                                <td class="p-3 text-right font-mono">{{ number_format($payroll->total_epf_qualifying ?: $payroll->payslips->sum('total_for_epf'), 2) }}</td>
                                <td class="p-3 text-right font-mono">{{ number_format($payroll->total_epf_employee, 2) }}</td>
                                <td class="p-3 text-right font-mono">{{ number_format($payroll->total_epf_employer, 2) }}</td>
                                <td class="p-3 text-right font-mono text-sm">{{ number_format($payroll->total_epf_employee + $payroll->total_epf_employer, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="text-xs font-bold text-slate-500">
                        ETF (3%) Remittance for ETF Board: <span class="text-slate-900 font-extrabold">LKR {{ number_format($payroll->total_etf_employer, 2) }}</span>
                    </div>
                    <a href="{{ route('payroll.export_epf_cform', $payroll->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-indigo-500/20">
                        <i class="ph ph-download-simple"></i> Download Form C (CSV)
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL 3: Balanced Double-Entry Accounting Journal Voucher -->
    <div x-show="journalModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
        <div class="bg-white w-full max-w-4xl rounded-3xl border border-slate-200 shadow-2xl p-6 sm:p-8 space-y-6 relative" @click.outside="journalModal = false">
            <button @click="journalModal = false" class="absolute top-6 right-6 p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                <i class="ph ph-x text-lg"></i>
            </button>

            <div class="border-b border-slate-200 pb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ph ph-scales text-2xl text-teal-600"></i>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 tracking-tight">DOUBLE-ENTRY PAYROLL JOURNAL VOUCHER</h2>
                            <p class="text-xs font-semibold text-slate-500">General Ledger Cost Allocation • Direct (Cost of Sales) vs Indirect (Administrative)</p>
                        </div>
                    </div>
                    <div>
                        @if($journalData && $journalData['is_balanced'])
                            <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full text-xs font-extrabold flex items-center gap-1">
                                <i class="ph ph-check-circle"></i> BALANCED (Δ 0.00)
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if($journalData)
                <div class="overflow-x-auto max-h-[400px] border border-slate-200 rounded-2xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-600 font-extrabold sticky top-0 border-b border-slate-200">
                            <tr>
                                <th class="p-3">Account Code</th>
                                <th class="p-3">Account Title & Description</th>
                                <th class="p-3">Category</th>
                                <th class="p-3 text-right text-blue-700">Debit (LKR)</th>
                                <th class="p-3 text-right text-purple-700">Credit (LKR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold">
                            @foreach($journalData['all_entries'] as $entry)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-mono font-extrabold text-slate-800">{{ $entry['account_code'] }}</td>
                                    <td class="p-3">
                                        <div class="font-extrabold text-slate-900">{{ $entry['account_name'] }}</div>
                                        <div class="text-[11px] text-slate-400 font-medium">{{ $entry['notes'] }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ str_contains($entry['category'], 'Direct') ? 'bg-blue-100 text-blue-700' : (str_contains($entry['category'], 'Liability') ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700') }}">
                                            {{ $entry['category'] }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-slate-900">
                                        {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-slate-900">
                                        {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-teal-50/80 font-black text-teal-950 border-t border-teal-200">
                            <tr>
                                <td class="p-3" colspan="3">BALANCED TOTALS</td>
                                <td class="p-3 text-right font-mono text-sm text-blue-900">{{ number_format($journalData['total_debits'], 2) }}</td>
                                <td class="p-3 text-right font-mono text-sm text-purple-900">{{ number_format($journalData['total_credits'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="text-xs font-bold text-slate-500">
                        Variance: <span class="font-mono text-slate-900 font-extrabold">{{ number_format($journalData['variance'], 2) }} LKR</span>
                    </div>
                    @if($payroll)
                        <a href="{{ route('payroll.export_journal', $payroll->id) }}" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-teal-500/20">
                            <i class="ph ph-download-simple"></i> Export Journal Voucher (CSV)
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL 4: Issue Staff Loan -->
    <div x-show="loanModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
        <div class="bg-white w-full max-w-md rounded-3xl border border-slate-200 shadow-2xl p-6 relative" @click.outside="loanModal = false">
            <button @click="loanModal = false" class="absolute top-5 right-5 p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                <i class="ph ph-x text-lg"></i>
            </button>

            <div class="flex items-center gap-2 mb-4">
                <i class="ph ph-bank text-2xl text-purple-600"></i>
                <h2 class="text-base font-black text-slate-900">Issue Staff Loan</h2>
            </div>

            <form action="{{ route('payroll.loans.apply') }}" method="POST" class="space-y-3.5 text-xs font-bold text-slate-600">
                @csrf
                <div>
                    <label class="block mb-1 text-slate-500">Select Employee</label>
                    <select name="employee_id" required class="w-full border-slate-200 rounded-xl bg-slate-50 p-2.5 text-xs font-bold">
                        <option value="">-- Choose Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->user->name ?? 'Staff' }} (EPF: {{ $emp->epf_registration_no ?? 'EPF-'.$emp->id }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-slate-500">Loan Type / Purpose</label>
                    <select name="loan_type_id" class="w-full border-slate-200 rounded-xl bg-slate-50 p-2.5 text-xs font-bold">
                        @foreach($loanTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-slate-500">Principal Amount (LKR)</label>
                    <input type="number" name="amount" min="1000" step="100" required class="w-full border-slate-200 rounded-xl bg-slate-50 p-2.5 text-xs font-bold" placeholder="e.g. 100000">
                </div>

                <div>
                    <label class="block mb-1 text-slate-500">Repayment Period (Months)</label>
                    <input type="number" name="months" min="1" max="60" value="10" required class="w-full border-slate-200 rounded-xl bg-slate-50 p-2.5 text-xs font-bold">
                </div>

                <div>
                    <label class="block mb-1 text-slate-500">Reason / Notes</label>
                    <textarea name="reason" rows="2" class="w-full border-slate-200 rounded-xl bg-slate-50 p-2 text-xs font-bold" placeholder="Emergency loan approval..."></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="loanModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-xl shadow-md shadow-purple-500/20">Submit Loan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: Salary Advance Request -->
    <div x-show="advanceModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
        <div class="bg-white w-full max-w-md rounded-3xl border border-slate-200 shadow-2xl p-6 relative" @click.outside="advanceModal = false">
            <button @click="advanceModal = false" class="absolute top-5 right-5 p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                <i class="ph ph-x text-lg"></i>
            </button>

            <div class="flex items-center gap-2 mb-4">
                <i class="ph ph-hand-coins text-2xl text-amber-600"></i>
                <h2 class="text-base font-black text-slate-900">Record Salary Advance</h2>
            </div>

            <form action="{{ route('payroll.advances.apply') }}" method="POST" class="space-y-3.5 text-xs font-bold text-slate-600">
                @csrf
                <div>
                    <label class="block mb-1 text-slate-500">Select Employee</label>
                    <select name="employee_id" required class="w-full border-slate-200 rounded-xl bg-slate-50 p-2.5 text-xs font-bold">
                        <option value="">-- Choose Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->user->name ?? 'Staff' }} (EPF: {{ $emp->epf_registration_no ?? 'EPF-'.$emp->id }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-slate-500">Advance Amount (LKR)</label>
                    <input type="number" name="amount" min="100" step="50" required class="w-full border-slate-200 rounded-xl bg-slate-50 p-2.5 text-xs font-bold" placeholder="e.g. 25000">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block mb-1 text-slate-500">Deduct In Month</label>
                        <select name="month" class="w-full border-slate-200 rounded-xl bg-slate-50 p-2.5 text-xs font-bold">
                            @foreach($monthsList as $mNum => $mName)
                                <option value="{{ $mNum }}" {{ $month == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-slate-500">Year</label>
                        <select name="year" class="w-full border-slate-200 rounded-xl bg-slate-50 p-2.5 text-xs font-bold">
                            @foreach($yearsList as $yNum)
                                <option value="{{ $yNum }}" {{ $year == $yNum ? 'selected' : '' }}>{{ $yNum }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block mb-1 text-slate-500">Reason</label>
                    <textarea name="reason" rows="2" class="w-full border-slate-200 rounded-xl bg-slate-50 p-2 text-xs font-bold" placeholder="Mid-month festive advance..."></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="advanceModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2 rounded-xl shadow-md shadow-amber-500/20">Queue Advance</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
