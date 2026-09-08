<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company-Wide Leave & Attendance Executive Summary - {{ $selectedYear }}</title>
    <style>
        @page {
            margin: 25px 25px 35px 25px;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #475569;
            margin-top: 3px;
        }
        .stats-grid {
            width: 100%;
            margin-bottom: 15px;
        }
        .stat-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }
        .stat-label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
        }
        .stat-value {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .section-heading {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
            margin-top: 15px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 6px 6px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-dept {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
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
                    <div class="report-title" style="font-size: 14px; font-weight: bold; color: #0f172a; margin: 0;">Company-Wide Leave & Attendance Executive Report</div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Leave Management System</div>
                </td>
                <td class="text-right" style="vertical-align: middle;">
                    <div style="font-size: 9.5px; color: #475569;">Period: <strong>{{ $selectedMonth ? ($monthsList[$selectedMonth] ?? '') . ' ' : 'Full Year ' }}{{ $selectedYear }}</strong></div>
                    <div style="font-size: 8.5px; color: #94a3b8; margin-top: 2px;">Generated: {{ now('Asia/Colombo')->format('Y-m-d h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Executive KPI Grid -->
    <table class="stats-grid" cellspacing="5" cellpadding="0">
        <tr>
            <td width="25%">
                <div class="stat-box">
                    <div class="stat-label">Total Workforce</div>
                    <div class="stat-value">{{ $totalWorkforce }} Employees</div>
                </div>
            </td>
            <td width="25%">
                <div class="stat-box">
                    <div class="stat-label">Departments</div>
                    <div class="stat-value">{{ count($departments) }} Active</div>
                </div>
            </td>
            <td width="25%">
                <div class="stat-box">
                    <div class="stat-label">Total Days Utilized</div>
                    <div class="stat-value">{{ $totalLeavesPeriod }} Days</div>
                </div>
            </td>
            <td width="25%">
                <div class="stat-box">
                    <div class="stat-label">Avg Days / Employee</div>
                    <div class="stat-value">{{ $avgDaysPerEmp }} Days</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Departmental Utilization Summary -->
    <div class="section-heading">Department-Wise Breakdown</div>
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
                @php
                    $dept = $row['department'];
                @endphp
                <tr>
                    <td class="text-center">
                        <span class="badge badge-dept">{{ $dept->code ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <strong>{{ $dept->name }}</strong>
                    </td>
                    <td class="text-center">{{ $row['employee_count'] }}</td>
                    <td class="text-center font-bold">{{ $row['total_days_taken'] }}</td>
                    <td class="text-center">{{ $row['total_short_leaves'] }}</td>
                    <td class="text-center">{{ $row['total_quota'] }}</td>
                    <td class="text-center font-bold">
                        {{ $row['utilization_pct'] }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #94a3b8;">No departments recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Leave Type Consumption Breakdown -->
    @if(isset($leaveTypeBreakdown) && count($leaveTypeBreakdown) > 0)
        <div class="section-heading">Consumption by Leave Type</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="40%">Leave Type</th>
                    <th width="20%" class="text-center">Total Applications</th>
                    <th width="20%" class="text-center">Total Days Taken</th>
                    <th width="20%" class="text-center">Approval Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaveTypeBreakdown as $lt)
                    <tr>
                        <td><strong>{{ $lt['name'] }}</strong></td>
                        <td class="text-center">{{ $lt['applications'] }}</td>
                        <td class="text-center font-bold">{{ $lt['days'] }}</td>
                        <td class="text-center">{{ $lt['approval_rate'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        LOOPS Integrated HRIS • Confidential • Company-Wide Executive Report
    </div>

</body>
</html>
