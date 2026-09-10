<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Report - {{ $payroll->cycle_name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm 12mm 10mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #1e293b;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }
        .meta-box {
            text-align: right;
            font-size: 8px;
            color: #475569;
        }
        .meta-box strong {
            color: #0f172a;
        }
        .filter-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 7.5px;
            border: 1px solid #bfdbfe;
            margin-left: 4px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            padding: 5px 4px;
            border: 1px solid #cbd5e1;
            text-align: right;
        }
        table.data-table th.text-left {
            text-align: left;
        }
        table.data-table th.text-center {
            text-align: center;
        }
        table.data-table td {
            padding: 4.5px 4px;
            border: 1px solid #e2e8f0;
            font-size: 7.5px;
            text-align: right;
        }
        table.data-table td.text-left {
            text-align: left;
        }
        table.data-table td.text-center {
            text-align: center;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table.data-table tr.totals-row {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #94a3b8;
        }
        table.data-table tr.totals-row td {
            font-size: 8px;
            font-weight: bold;
            border-color: #94a3b8;
            color: #0f172a;
        }
        .emp-name {
            font-weight: bold;
            color: #0f172a;
        }
        .emp-sub {
            font-size: 6.5px;
            color: #64748b;
        }
        .net-pay {
            font-weight: bold;
            color: #1e3a8a;
        }
        .footer {
            margin-top: 15px;
            font-size: 7.5px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">LOOPS HR &bull; Payroll Master Report</div>
                <div class="header-subtitle">
                    Salary Cycle: <strong>{{ $payroll->cycle_name }}</strong> &bull; Period: {{ date('F Y', mktime(0, 0, 0, $payroll->month, 1, $payroll->year)) }}
                </div>
            </td>
            <td class="meta-box">
                <div>Printed On: <strong>{{ date('d M Y, h:i A') }}</strong></div>
                <div style="margin-top: 3px;">
                    Category: <span class="filter-badge">{{ $category }}</span>
                    Method: <span class="filter-badge">{{ $paymentMethodFilter }}</span>
                    Records: <span class="filter-badge">{{ $payslips->count() }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 14%;">Employee</th>
                <th class="text-center" style="width: 6%;">EPF / NIC</th>
                <th class="text-left" style="width: 8%;">Department</th>
                <th style="width: 6.5%;">Base Pay</th>
                <th style="width: 6.5%;">Fixed Allw</th>
                <th style="width: 6%;">OT / Var</th>
                <th style="width: 5.5%;">No-Pay</th>
                <th style="width: 7%;">Gross Pay</th>
                <th style="width: 6%;">EPF 8%</th>
                <th style="width: 6%;">EPF 12%</th>
                <th style="width: 5%;">ETF 3%</th>
                <th style="width: 5.5%;">APIT Tax</th>
                <th style="width: 6%;">Other Ded</th>
                <th style="width: 6.5%;">Total Ded</th>
                <th style="width: 8%;">Net Salary</th>
                <th class="text-center" style="width: 6.5%;">Payment</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payslips as $ps)
                @php
                    $emp = $ps->employee;
                    $totalBase = $ps->total_base_pay ?: ($ps->basic_salary + $ps->increments_basic + $ps->budget_allowance);
                    $totalFixed = $ps->total_fixed_allowance ?: ($ps->travelling_allowance + $ps->cost_of_living_allowance + $ps->increments_allowance + $ps->fixed_allowance);
                    $totalVar = $ps->total_variable_pay ?: ($ps->ot_amount + $ps->shift_allowance + $ps->incentive_commission + $ps->salary_arrears_basic + $ps->salary_arrears_allowance);
                    $noPayDed = $ps->total_no_pay_deduction ?: $ps->no_pay_deduction;
                @endphp
                <tr>
                    <td class="text-left">
                        <div class="emp-name">{{ $emp->user->name ?? 'Employee' }}</div>
                        <div class="emp-sub">{{ $emp->employee_id_number ?? 'EMP-'.$emp->id }} &bull; {{ $ps->staff_category ?? 'Executive' }}</div>
                    </td>
                    <td class="text-center">
                        <div>{{ $emp->epf_registration_no ?? 'N/A' }}</div>
                        <div class="emp-sub">{{ $emp->national_id ?? $emp->nic ?? 'N/A' }}</div>
                    </td>
                    <td class="text-left">{{ $ps->department_name ?: ($emp->department->name ?? 'Corporate') }}</td>
                    <td>{{ number_format($totalBase, 2) }}</td>
                    <td>{{ number_format($totalFixed, 2) }}</td>
                    <td>{{ number_format($totalVar, 2) }}</td>
                    <td style="color: #b45309;">{{ number_format($noPayDed, 2) }}</td>
                    <td style="font-weight: bold;">{{ number_format($ps->gross_salary, 2) }}</td>
                    <td>{{ number_format($ps->epf_employee, 2) }}</td>
                    <td>{{ number_format($ps->epf_employer, 2) }}</td>
                    <td>{{ number_format($ps->etf_employer, 2) }}</td>
                    <td>{{ number_format($ps->apit_tax, 2) }}</td>
                    <td>{{ number_format($ps->salary_advance + $ps->loan_installment + $ps->personal_expense_recovery + $ps->other_deductions, 2) }}</td>
                    <td style="color: #b91c1c; font-weight: bold;">{{ number_format($ps->total_deductions, 2) }}</td>
                    <td class="net-pay">{{ number_format($ps->net_salary, 2) }}</td>
                    <td class="text-center">{{ $ps->payment_method ?? 'Bank Transfer' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="text-center" style="padding: 20px; color: #94a3b8;">
                        No salary records found for the selected criteria.
                    </td>
                </tr>
            @endforelse

            <!-- Grand Totals Row -->
            @if($payslips->count() > 0)
                <tr class="totals-row">
                    <td colspan="3" class="text-left">GRAND TOTALS ({{ $payslips->count() }} Employees)</td>
                    <td>{{ number_format($payslips->sum(fn($ps) => $ps->total_base_pay ?: ($ps->basic_salary + $ps->increments_basic + $ps->budget_allowance)), 2) }}</td>
                    <td>{{ number_format($payslips->sum(fn($ps) => $ps->total_fixed_allowance ?: ($ps->travelling_allowance + $ps->cost_of_living_allowance + $ps->increments_allowance + $ps->fixed_allowance)), 2) }}</td>
                    <td>{{ number_format($payslips->sum(fn($ps) => $ps->total_variable_pay ?: ($ps->ot_amount + $ps->shift_allowance + $ps->incentive_commission + $ps->salary_arrears_basic + $ps->salary_arrears_allowance)), 2) }}</td>
                    <td>{{ number_format($payslips->sum(fn($ps) => $ps->total_no_pay_deduction ?: $ps->no_pay_deduction), 2) }}</td>
                    <td>{{ number_format($payslips->sum('gross_salary'), 2) }}</td>
                    <td>{{ number_format($payslips->sum('epf_employee'), 2) }}</td>
                    <td>{{ number_format($payslips->sum('epf_employer'), 2) }}</td>
                    <td>{{ number_format($payslips->sum('etf_employer'), 2) }}</td>
                    <td>{{ number_format($payslips->sum('apit_tax'), 2) }}</td>
                    <td>{{ number_format($payslips->sum(fn($ps) => $ps->salary_advance + $ps->loan_installment + $ps->personal_expense_recovery + $ps->other_deductions), 2) }}</td>
                    <td>{{ number_format($payslips->sum('total_deductions'), 2) }}</td>
                    <td class="net-pay">{{ number_format($payslips->sum('net_salary'), 2) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 30px; border-collapse: collapse; font-size: 8px;">
        <tr>
            <td style="width: 33%; text-align: left; border-top: 1px dashed #94a3b8; padding-top: 5px;">
                Prepared By: _____________________<br>
                <span style="color: #64748b; font-size: 7px;">Payroll Executive / HR Admin</span>
            </td>
            <td style="width: 33%; text-align: center; border-top: 1px dashed #94a3b8; padding-top: 5px;">
                Checked By: _____________________<br>
                <span style="color: #64748b; font-size: 7px;">Head of Finance / Accountant</span>
            </td>
            <td style="width: 33%; text-align: right; border-top: 1px dashed #94a3b8; padding-top: 5px;">
                Approved By: _____________________<br>
                <span style="color: #64748b; font-size: 7px;">Managing Director / Super Admin</span>
            </td>
        </tr>
    </table>

</body>
</html>
