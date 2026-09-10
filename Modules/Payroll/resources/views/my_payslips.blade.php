@extends('layouts.app', ['title' => 'LOOPS HR - My Payslips', 'breadcrumb' => 'My Payslips'])

@section('content')
<div class="space-y-6" x-data="{ 
    payslipModal: false, 
    activePs: null, 
    activeEmp: {{ json_encode($employee) }},
    searchQuery: '',
    openAndPrint(ps) {
        this.activePs = ps;
        this.payslipModal = true;
        this.$nextTick(() => {
            setTimeout(() => { window.print(); }, 250);
        });
    },
    openView(ps) {
        this.activePs = ps;
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
        if (!ps) return '';
        const months = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];
        return (months[ps.month - 1] || '') + ' ' + (ps.year || '');
    }
}">

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-xl">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">My Salary Payslips & Pay Advice</h1>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5">View, filter, and download all your official monthly employee payroll slips and statutory statements.</p>
                </div>
            </div>
        </div>

        @if($employee)
            <div class="bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold flex flex-wrap items-center gap-3">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Employee ID</span>
                    <span class="font-extrabold text-slate-900">{{ $employee->employee_id_number ?? 'EMP-'.$employee->id }}</span>
                </div>
                <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">EPF Reg No</span>
                    <span class="font-extrabold text-blue-700">{{ $employee->epf_registration_no ?? 'EPF-'.$employee->id }}</span>
                </div>
                <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Bank Account</span>
                    <span class="font-extrabold text-slate-900">{{ $employee->account_number ?? '0010098234' }}</span>
                </div>
            </div>
        @endif
    </div>



    <!-- Payslip History Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <!-- Controls & Filters -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="ph ph-list-dashes text-blue-600"></i> Monthly Employee Payroll Slips
                </h2>
                <p class="text-xs text-slate-400 font-medium">All monthly salary advices generated for your profile</p>
            </div>

            <div class="flex items-center gap-2.5">
                <!-- Search input -->
                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Search month..." 
                           class="pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white w-36 sm:w-44">
                </div>

                <!-- Year Filter Dropdown -->
                <form method="GET" action="{{ route('payroll.my_payslips') }}" class="flex items-center">
                    <select name="year" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="All" {{ ($yearFilter ?? 'All') === 'All' ? 'selected' : '' }}>All Years</option>
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ (string)($yearFilter ?? '') === (string)$yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <!-- Payslips Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-semibold text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Pay Period</th>
                        <th class="p-3 text-right">Base Pay</th>
                        <th class="p-3 text-right">Allowances</th>
                        <th class="p-3 text-right">Gross Salary</th>
                        <th class="p-3 text-right text-indigo-700">Total for EPF</th>
                        <th class="p-3 text-right text-rose-600">EPF 8%</th>
                        <th class="p-3 text-right text-rose-600">Total Deductions</th>
                        <th class="p-3 text-right font-black text-emerald-700">Net Take-Home</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payslips as $ps)
                        @php
                            $monthName = \Carbon\Carbon::createFromDate($ps->year, $ps->month, 1)->format('F');
                            $totBase = $ps->total_base_pay ?: ($ps->basic_salary + $ps->increments_basic + $ps->budget_allowance);
                            $totAllw = ($ps->total_fixed_allowance ?: ($ps->travelling_allowance + $ps->cost_of_living_allowance + $ps->increments_allowance + $ps->fixed_allowance)) + ($ps->total_variable_pay ?: ($ps->ot_amount + $ps->shift_allowance + $ps->incentive_commission + $ps->salary_arrears_basic + $ps->salary_arrears_allowance));
                            $totDeduct = $ps->total_deductions ?: ($ps->epf_employee + $ps->apit_tax + $ps->loan_installment + $ps->salary_advance + $ps->personal_expense_recovery + $ps->other_deductions);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors" x-show="!searchQuery || '{{ strtolower($monthName . ' ' . $ps->year) }}'.includes(searchQuery.toLowerCase())">
                            <td class="p-3 font-extrabold text-slate-900">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        <i class="ph ph-calendar-blank"></i>
                                    </div>
                                    <div>
                                        <span class="block text-slate-900 font-extrabold">{{ $monthName }} {{ $ps->year }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold">{{ $ps->staff_category ?? 'Executive' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-right font-mono">LKR {{ number_format($totBase, 2) }}</td>
                            <td class="p-3 text-right font-mono">LKR {{ number_format($totAllw, 2) }}</td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900">LKR {{ number_format($ps->gross_salary, 2) }}</td>
                            <td class="p-3 text-right font-mono font-black text-indigo-700 bg-indigo-50/40">LKR {{ number_format($ps->total_for_epf ?: $totBase, 2) }}</td>
                            <td class="p-3 text-right font-mono text-rose-600 font-bold">- LKR {{ number_format($ps->epf_employee, 2) }}</td>
                            <td class="p-3 text-right font-mono text-rose-600">- LKR {{ number_format($totDeduct, 2) }}</td>
                            <td class="p-3 text-right font-mono font-black text-emerald-600 bg-emerald-50/40 text-sm">
                                LKR {{ number_format($ps->net_salary, 2) }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disbursed
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" 
                                            @click="openAndPrint({{ json_encode($ps) }})" 
                                            title="Download / Print Payslip PDF"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold shadow-xs flex items-center gap-1 transition-all cursor-pointer">
                                        <i class="ph ph-download-simple text-sm"></i> Download Slip
                                    </button>
                                    <button type="button" 
                                            @click="openView({{ json_encode($ps) }})" 
                                            title="View Breakdown Advice"
                                            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 transition-all cursor-pointer">
                                        <i class="ph ph-eye text-sm"></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-slate-400 italic font-bold">
                                <div class="flex flex-col items-center justify-center py-6 gap-2">
                                    <i class="ph ph-receipt text-3xl text-slate-300"></i>
                                    <span>No monthly employee payroll slips found for your account.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Official Employee Pay Advice Modal (Naturally Fitted & Perfectly Centered) -->
    <div x-show="payslipModal" data-printable-container="true" id="printable-pay-advice-container" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto print:p-0 print:bg-white print:fixed-none print:overflow-visible">
        <div data-printable-card="true" id="printable-pay-advice-card" class="bg-white mx-auto my-auto w-full max-w-xl md:max-w-2xl shadow-2xl relative flex flex-col overflow-hidden rounded-sm print:p-0 print:border-0 print:shadow-none print:max-w-none print:rounded-none print:h-auto" @click.away="payslipModal = false">
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
                            <button type="button" @click="window.print()" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold rounded-lg shadow-md flex items-center gap-1 transition-all cursor-pointer">
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
                        <span class="font-bold text-slate-900 uppercase" x-text="activeEmp?.user?.name || activePs?.employee?.user?.name || '{{ addslashes(auth()->user()->name ?? '') }}'"></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 sm:w-36 font-black uppercase text-slate-900">EMP NO :</span>
                        <span class="font-bold text-slate-900 uppercase" x-text="activeEmp?.epf_registration_no || ('EPF-' + activeEmp?.id)"></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 sm:w-36 font-black uppercase text-slate-900">MONTH & YEAR :</span>
                        <span class="font-bold text-slate-900 uppercase" x-text="getMonthYear(activePs)"></span>
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
                                <td class="py-1 text-right font-mono text-slate-900 font-extrabold" x-text="formatTotal(Number(activePs?.total_base_pay || activePs?.basic_salary || activeEmp?.basic_salary || 0) - Number(activePs?.no_pay_basic || 0))"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">FIXED ALLOWANCE</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">FIXED ALLOWANCE FOR THE PERIOD</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900 font-extrabold" x-text="formatTotal(Number(activePs?.total_fixed_allowance || activePs?.fixed_allowance || 0) - Number(activePs?.no_pay_allowance || 0))"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">OTHER</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(Number(activePs?.ot_amount || 0) + Number(activePs?.shift_allowance || 0) + Number(activePs?.incentive_commission || 0))"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">SALARY ARREARES</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.salary_arrears_basic)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">ALLOWANCE ARREARES</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.salary_arrears_allowance)"></td>
                            </tr>
                            <!-- GROSS SALARY -->
                            <tr class="border-t-2 border-b-2 border-slate-900 font-black text-slate-900 bg-slate-50/50">
                                <td class="py-1 sm:py-1.5 uppercase">GROSS SALARY</td>
                                <td class="py-1 sm:py-1.5 text-center"></td>
                                <td class="py-1 sm:py-1.5 text-right font-mono" x-text="formatTotal(activePs?.gross_salary)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">ADJUSTMENTS</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900">-</td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">NO PAY DEDUCTIONS</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.total_no_pay_deduction || activePs?.no_pay_deduction)"></td>
                            </tr>
                            <!-- TOTAL FOR EPF/ETF -->
                            <tr class="border-t-2 border-b-2 border-slate-900 font-black text-slate-900 bg-slate-50/50">
                                <td class="py-1 sm:py-1.5 uppercase">TOTAL FOR EPF/ETF</td>
                                <td class="py-1 sm:py-1.5 text-center"></td>
                                <td class="py-1 sm:py-1.5 text-right font-mono" x-text="formatTotal(activePs?.total_for_epf || activePs?.gross_salary)"></td>
                            </tr>

                            <!-- DEDUCTIONS SECTION -->
                            <tr>
                                <td colspan="3" class="pt-2 pb-1 font-black underline uppercase text-slate-900">LESS : DEDUCTIONS</td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">EPF 8%</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.epf_employee)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">CORPORATE PICKME - PERSONAL USE</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.personal_expense_recovery)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">LOAN</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.loan_installment)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">PAYE TAX</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.apit_tax)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">SALARY ADVANCE</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.salary_advance)"></td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">OTHER DEDUCTIONS</td>
                                <td class="py-1 text-center text-slate-400"></td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.other_deductions)"></td>
                            </tr>
                            <!-- TOTAL DEDUCTIONS -->
                            <tr class="border-t-2 border-b-2 border-slate-900 font-black text-slate-900 bg-slate-50/50">
                                <td class="py-1 sm:py-1.5 uppercase">TOTAL DEDUCTIONS</td>
                                <td class="py-1 sm:py-1.5 text-center"></td>
                                <td class="py-1 sm:py-1.5 text-right font-mono" x-text="formatTotal(activePs?.total_deductions)"></td>
                            </tr>

                            <!-- NET SALARY -->
                            <tr class="border-b-2 border-slate-900 font-black text-slate-900 text-[11px] sm:text-xs bg-slate-50/80">
                                <td class="py-1.5 uppercase">NET SALARY</td>
                                <td class="py-1.5 text-center"></td>
                                <td class="py-1.5 text-right font-mono font-black" x-text="formatTotal(activePs?.net_salary)"></td>
                            </tr>

                            <!-- EMPLOYER CONTRIBUTION SECTION -->
                            <tr>
                                <td colspan="3" class="pt-2 pb-1 font-black underline uppercase text-slate-900">EMPLOYER CONTRIBUTION</td>
                            </tr>
                            <tr class="border-b border-slate-200">
                                <td class="py-1 text-slate-900 uppercase">EPF</td>
                                <td class="py-1 text-center font-bold text-slate-900">12%</td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.epf_employer)"></td>
                            </tr>
                            <tr class="border-b-2 border-slate-900">
                                <td class="py-1 text-slate-900 uppercase">ETF</td>
                                <td class="py-1 text-center font-bold text-slate-900">3%</td>
                                <td class="py-1 text-right font-mono text-slate-900" x-text="formatAmt(activePs?.etf_employer)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
