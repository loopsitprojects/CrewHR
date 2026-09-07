@extends('layouts.app', ['title' => 'LOOPS HR - My Payslips', 'breadcrumb' => 'My Payslips'])

@section('content')
<div class="space-y-6" x-data="{ payslipModal: false, activeEmp: null }">

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <i class="ph ph-receipt text-2xl text-blue-600"></i>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">My Salary Payslips</h1>
            </div>
            <p class="text-xs font-semibold text-slate-500 mt-1">View and download your monthly salary slips and statutory EPF/ETF statements.</p>
        </div>

        @if($employee)
            <div class="bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold flex items-center gap-3">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">EPF Reg No</span>
                    <span class="font-extrabold text-slate-900">{{ $employee->epf_registration_no ?? 'EPF-N/A' }}</span>
                </div>
                <div class="h-6 w-px bg-slate-200"></div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Bank Account</span>
                    <span class="font-extrabold text-blue-600">{{ $employee->account_number ?? 'N/A' }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Payslip History List -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight border-b border-slate-100 pb-3">Monthly Payslip History</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-semibold text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Pay Period</th>
                        <th class="p-3">Basic Salary</th>
                        <th class="p-3">Allowances</th>
                        <th class="p-3">Gross Salary</th>
                        <th class="p-3">EPF 8% (Deducted)</th>
                        <th class="p-3">Net Take-Home</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payslips as $ps)
                        @php
                            $monthName = \Carbon\Carbon::createFromDate($ps->year, $ps->month, 1)->format('F');
                        @endphp
                        <tr>
                            <td class="p-3 font-extrabold text-slate-900">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-calendar-blank text-blue-600 text-base"></i>
                                    <span>{{ $monthName }} {{ $ps->year }}</span>
                                </div>
                            </td>
                            <td class="p-3">Rs. {{ number_format($ps->basic_salary, 2) }}</td>
                            <td class="p-3">Rs. {{ number_format($ps->fixed_allowance + $ps->other_allowance, 2) }}</td>
                            <td class="p-3 font-bold text-slate-900">Rs. {{ number_format($ps->gross_salary, 2) }}</td>
                            <td class="p-3 text-rose-600 font-bold">- Rs. {{ number_format($ps->epf_employee, 2) }}</td>
                            <td class="p-3 font-black text-emerald-600">Rs. {{ number_format($ps->net_salary, 2) }}</td>
                            <td class="p-3 text-right">
                                <button @click="activeEmp = {
                                    name: '{{ addslashes($employee->user->name ?? 'Employee') }}',
                                    id: '{{ $employee->employee_id_number ?? ('EMP-'.$employee->id) }}',
                                    epf: '{{ $employee->epf_registration_no ?? 'N/A' }}',
                                    dept: '{{ addslashes($employee->department->name ?? 'IT') }}',
                                    designation: '{{ addslashes($employee->designation->name ?? 'Staff') }}',
                                    bank: '{{ addslashes($employee->bank_name ?? 'Commercial Bank PLC') }}',
                                    account: '{{ $employee->account_number ?? '0010098234' }}',
                                    basic: '{{ number_format($ps->basic_salary, 2) }}',
                                    allowances: '{{ number_format($ps->fixed_allowance + $ps->other_allowance, 2) }}',
                                    gross: '{{ number_format($ps->gross_salary, 2) }}',
                                    epf8: '{{ number_format($ps->epf_employee, 2) }}',
                                    epf12: '{{ number_format($ps->epf_employer, 2) }}',
                                    etf3: '{{ number_format($ps->etf_employer, 2) }}',
                                    apit: '{{ number_format($ps->apit_tax, 2) }}',
                                    net: '{{ number_format($ps->net_salary, 2) }}',
                                    monthYear: '{{ $monthName }} {{ $ps->year }}'
                                }; payslipModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5 ml-auto">
                                    <i class="ph ph-receipt text-sm"></i> View / Print Payslip
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 italic font-bold">
                                No processed payslips found for your account.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payslip Printable Modal -->
    <div x-show="payslipModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-8 shadow-2xl space-y-6 border border-slate-200" @click.away="payslipModal = false">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-4">
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
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-extrabold">Employee ID / EPF No</span>
                    <span class="font-bold text-blue-600" x-text="activeEmp?.id + ' • ' + activeEmp?.epf"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-extrabold">Department & Designation</span>
                    <span class="font-bold text-slate-900" x-text="activeEmp?.dept + ' • ' + activeEmp?.designation"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-extrabold">Bank Account</span>
                    <span class="font-bold text-slate-900" x-text="activeEmp?.bank + ' (' + activeEmp?.account + ')'"></span>
                </div>
            </div>

            <!-- Payslip Breakdown Table -->
            <div class="space-y-3 text-xs font-semibold">
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Basic Salary</span>
                    <span class="font-bold text-slate-900">Rs. <span x-text="activeEmp?.basic"></span></span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Allowances (Fixed & Other)</span>
                    <span class="font-bold text-slate-900">Rs. <span x-text="activeEmp?.allowances"></span></span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-200 bg-slate-50 px-2.5 rounded-lg font-bold text-slate-900">
                    <span>Gross Salary</span>
                    <span>Rs. <span x-text="activeEmp?.gross"></span></span>
                </div>

                <div class="flex justify-between py-1.5 border-b border-slate-100 text-rose-600">
                    <span>EPF Deduction (Employee 8%)</span>
                    <span>- Rs. <span x-text="activeEmp?.epf8"></span></span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100 text-rose-600">
                    <span>APIT Tax Deduction</span>
                    <span>- Rs. <span x-text="activeEmp?.apit"></span></span>
                </div>

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
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white p-4 rounded-xl shadow-md flex items-center justify-between mt-4">
                    <span class="font-extrabold text-xs uppercase tracking-wider">Net Take-Home Pay</span>
                    <span class="text-xl font-black">Rs. <span x-text="activeEmp?.net"></span></span>
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" @click="window.print()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md flex items-center gap-2 transition-all">
                    <i class="ph ph-printer text-base"></i> Print Payslip
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
