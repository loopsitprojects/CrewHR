<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Leave Report - {{ $targetDepartment ? $targetDepartment->name : 'All Departments' }} - {{ $selectedYear }}</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 12px;
            font-weight: bold;
            color: #475569;
            margin-top: 2px;
        }
        .dept-banner {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 8px 12px;
            margin-bottom: 12px;
            border-radius: 0 4px 4px 0;
        }
        .dept-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
        }
        .dept-subtitle {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }
        .stats-grid {
            width: 100%;
            margin-bottom: 12px;
        }
        .stat-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 8px;
            text-align: center;
        }
        .stat-label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
        }
        .stat-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            margin: 14px 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 12px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-dept { background-color: #dbeafe; color: #1e40af; }
        .badge-approved { background-color: #dcfce7; color: #15803d; }
        .badge-pending { background-color: #fef9c3; color: #a16207; }
        .badge-rejected { background-color: #fee2e2; color: #b91c1c; }
        .badge-leave { background-color: #f3e8ff; color: #7e22ce; }
        .font-bold { font-weight: bold; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 160px; vertical-align: middle;">
                    @php
                        $logoPath = public_path('LoopsBlack.png');
                        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
                    @endphp
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Loops Logo" style="height: 38px; width: auto; object-fit: contain;">
                    @else
                        <div class="company-name">LOOPS INTEGRATED</div>
                    @endif
                </td>
                <td style="vertical-align: middle; padding-left: 15px;">
                    <div class="report-title" style="font-size: 14px; font-weight: bold; color: #0f172a; margin: 0;">
                        @if($targetDepartment)
                            Department Leave Statement: {{ $targetDepartment->name }}
                        @else
                            Department-Wise Leave Summary Report
                        @endif
                    </div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Leave Management System</div>
                </td>
                <td class="text-right" style="vertical-align: middle;">
                    <div style="font-size: 9.5px; color: #475569;">Period: <strong>{{ $selectedMonth ? ($monthsList[$selectedMonth] ?? '') . ' ' : 'Full Year ' }}{{ $selectedYear }}</strong></div>
                    <div style="font-size: 8.5px; color: #94a3b8; margin-top: 2px;">Generated: {{ now('Asia/Colombo')->format('Y-m-d h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if($targetDepartment)
        <div class="dept-banner">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="dept-title">{{ $targetDepartment->name }} ({{ $targetDepartment->code ?? 'N/A' }})</div>
                        <div class="dept-subtitle">Head of Department: <strong>{{ $targetDepartment->hod_name ?? 'Super Admin' }}</strong> &bull; Total Staff: <strong>{{ $departmentEmployees->count() }} employees</strong></div>
                    </td>
                    <td class="text-right" style="vertical-align: middle;">
                        <span class="badge badge-dept" style="font-size: 9px; padding: 4px 8px;">Department Report</span>
                    </td>
                </tr>
            </table>
        </div>
    @endif



    @if(!$targetDepartment)
        <!-- All Departments Summary Table -->
        <div class="section-heading">Departments Overview</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="8%">Code</th>
                    <th width="28%">Department</th>
                    <th width="12%" class="text-center">Staff Count</th>
                    <th width="14%" class="text-center">Days Consumed</th>
                    <th width="12%" class="text-center">Short Leaves</th>
                    <th width="12%" class="text-center">Total Quota</th>
                    <th width="14%" class="text-center">Utilization</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departmentReport as $row)
                    @php $dept = $row['department']; @endphp
                    <tr>
                        <td class="text-center">
                            <span class="badge badge-dept">{{ $dept->code ?? 'N/A' }}</span>
                        </td>
                        <td><strong>{{ $dept->name }}</strong></td>
                        <td class="text-center">{{ $row['employee_count'] }}</td>
                        <td class="text-center font-bold">{{ $row['total_days_taken'] }}</td>
                        <td class="text-center">{{ $row['total_short_leaves'] }}</td>
                        <td class="text-center">{{ $row['total_quota'] }}</td>
                        <td class="text-center font-bold">{{ $row['utilization_pct'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 15px; color: #94a3b8;">No department records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <!-- Department Staff Leave Breakdown by Category Table -->
        <div class="section-heading">Department Staff Leave Days Taken by Category ({{ $selectedMonth ? ($monthsList[$selectedMonth] ?? '') . ' ' : 'Full Year ' }}{{ $selectedYear }})</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="10%">EMP ID</th>
                    <th width="20%">Employee Name</th>
                    <th width="16%">Designation</th>
                    @foreach($leaveTypes as $lt)
                        <th class="text-center" style="font-size: 8px;">{{ $lt->name }}</th>
                    @endforeach
                    <th width="10%" class="text-center" style="background-color: #e2e8f0;">Total Days Taken</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departmentEmployees as $emp)
                    @php
                        // Filter approved/active leaves taken for this employee in the period
                        $empLeaves = $departmentLeaves->where('employee_id', $emp->id)->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']));
                        $empTotalDays = 0;
                    @endphp
                    <tr>
                        <td class="font-bold">{{ $emp->employee_id_number ?? 'N/A' }}</td>
                        <td><strong>{{ $emp->user->name ?? 'N/A' }}</strong></td>
                        <td>{{ $emp->designation->name ?? 'Employee' }}</td>
                        @foreach($leaveTypes as $lt)
                            @php
                                $typeLeaves = $empLeaves->where('leave_type_id', $lt->id);
                                $daysTaken = $typeLeaves->sum(fn($l) => (float) ($l->duration ?: 1.0));
                                $empTotalDays += $daysTaken;
                            @endphp
                            <td class="text-center">
                                @if($daysTaken > 0)
                                    <span class="font-bold" style="color: #1e3a8a;">{{ (float) $daysTaken }}</span>
                                @else
                                    <span style="color: #cbd5e1;">0</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center font-bold" style="background-color: #f1f5f9; font-size: 9.5px; color: #0f172a;">
                            {{ (float) $empTotalDays }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + count($leaveTypes) }}" class="text-center" style="padding: 15px; color: #94a3b8;">No employees assigned to this department.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #f8fafc; font-weight: bold; border-top: 2px solid #cbd5e1;">
                    <td colspan="3" class="text-right" style="padding: 6px 8px; font-size: 9px; text-transform: uppercase;">Department Totals:</td>
                    @php $grandTotal = 0; @endphp
                    @foreach($leaveTypes as $lt)
                        @php
                            $deptTypeDays = $departmentLeaves->where('leave_type_id', $lt->id)->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']))->sum(fn($l) => (float) ($l->duration ?: 1.0));
                            $grandTotal += $deptTypeDays;
                        @endphp
                        <td class="text-center" style="color: #1e40af; font-size: 9.5px;">{{ (float) $deptTypeDays }}</td>
                    @endforeach
                    <td class="text-center" style="background-color: #e2e8f0; font-size: 10px; color: #1e3a8a;">{{ (float) $grandTotal }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        LOOPS Integrated HRIS • Confidential • Department Leave Audit Statement
    </div>

</body>
</html>

