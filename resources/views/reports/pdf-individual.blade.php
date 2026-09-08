<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Individual Leave Report - {{ $selectedYear }}</title>
    <style>
        @page {
            margin: 20px 20px 30px 20px;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.3;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
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
        .profile-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .profile-card table {
            width: 100%;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 5px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 5px;
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
            padding: 2px 4px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-approved { background-color: #dcfce7; color: #15803d; }
        .badge-pending { background-color: #fef3c7; color: #b45309; }
        .badge-rejected { background-color: #ffe4e6; color: #be123c; }
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
                    <div class="report-title" style="font-size: 14px; font-weight: bold; color: #0f172a; margin: 0;">Individual Employee Leave Records & Audit Report</div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Leave Management System</div>
                </td>
                <td class="text-right" style="vertical-align: middle;">
                    <div style="font-size: 9.5px; color: #475569;">Period: <strong>{{ $selectedMonth ? ($monthsList[$selectedMonth] ?? '') . ' ' : 'Full Year ' }}{{ $selectedYear }}</strong></div>
                    <div style="font-size: 8.5px; color: #94a3b8; margin-top: 2px;">Generated: {{ now('Asia/Colombo')->format('Y-m-d h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if($selectedEmployeeProfile)
        <div class="profile-card">
            <table style="width: 100%;">
                <tr>
                    <td width="35%">
                        <strong>Employee Name:</strong> <span style="font-size: 11px; font-weight: bold; color: #0f172a;">{{ $selectedEmployeeProfile->user->name ?? 'N/A' }}</span><br>
                        <strong>Employee ID:</strong> <span style="font-weight: bold; color: #2563eb;">{{ $selectedEmployeeProfile->employee_id_number ?? 'EMP' }}</span>
                    </td>
                    <td width="35%">
                        <strong>Department:</strong> {{ $selectedEmployeeProfile->department->name ?? 'N/A' }}<br>
                        <strong>Designation:</strong> {{ $selectedEmployeeProfile->designation->name ?? 'N/A' }}
                    </td>
                    <td width="30%" class="text-right">
                        <strong>Official Email:</strong> {{ $selectedEmployeeProfile->user->email ?? 'N/A' }}<br>
                        <strong>Statement Year:</strong> {{ $selectedYear }}
                    </td>
                </tr>
            </table>
        </div>

        @if($selectedEmployeeBalances && $selectedEmployeeBalances->count() > 0)
            <div style="font-size: 10px; font-weight: bold; color: #1e293b; margin: 10px 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                Leave Quota & Balances ({{ $selectedYear }})
            </div>
            <table class="data-table" style="margin-bottom: 14px;">
                <thead>
                    <tr>
                        <th width="34%">Leave Category</th>
                        <th width="22%" class="text-center">Total Allocated (Days)</th>
                        <th width="22%" class="text-center">Days Consumed</th>
                        <th width="22%" class="text-center" style="background-color: #e2e8f0;">Remaining Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selectedEmployeeBalances as $bal)
                        @php
                            $isShort = strtoupper($bal->leaveType->code ?? '') === 'SHORT' || str_contains(strtolower($bal->leaveType->name ?? ''), 'short');
                            $allocated = (float) $bal->allocated;
                            $used = (float) $bal->used;
                            $remaining = $isShort ? '2 / Month' : (float) max(0, ($bal->allocated + ($bal->carried_forward ?? 0)) - $bal->used);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $bal->leaveType->name ?? 'Leave' }}</strong>
                            </td>
                            <td class="text-center">
                                {{ $isShort ? '2 / Month' : $allocated }}
                            </td>
                            <td class="text-center font-bold" style="color: #dc2626;">
                                {{ $used }}
                            </td>
                            <td class="text-center font-bold" style="color: #16a34a; background-color: #f8fafc;">
                                {{ $remaining }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div style="font-size: 10px; font-weight: bold; color: #1e293b; margin: 10px 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">
            Leave Applications & History
        </div>
    @endif

    <!-- Individual Leaves Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="14%">Employee Name</th>
                <th width="8%">EMP ID</th>
                <th width="12%">Department</th>
                <th width="12%">Leave Type</th>
                <th width="12%">Start Date</th>
                <th width="12%">End Date</th>
                <th width="6%" class="text-center">Days</th>
                <th width="12%">Covering Colleague</th>
                <th width="8%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leaves as $index => $req)
                @php
                    $statusClass = match(strtolower($req->status)) {
                        'approved' => 'badge-approved',
                        'rejected', 'cancelled' => 'badge-rejected',
                        default => 'badge-pending',
                    };
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $req->employee->user->name ?? 'N/A' }}</strong></td>
                    <td>{{ $req->employee->employee_id_number ?? 'N/A' }}</td>
                    <td>{{ $req->employee->department->name ?? 'N/A' }}</td>
                    <td>
                        {{ $req->leaveType->name ?? 'General' }}
                        @if($req->is_short_leave)
                            <span style="font-size: 7px; color: #b45309; font-weight: bold;">(Short)</span>
                        @elseif($req->is_half_day)
                            <span style="font-size: 7px; color: #0369a1; font-weight: bold;">(Half-Day)</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($req->start_date)->format('Y-m-d') }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->end_date)->format('Y-m-d') }}</td>
                    <td class="text-center font-bold">{{ $req->duration ?: 1.0 }}</td>
                    <td>{{ $req->coveringEmployee->user->name ?? 'None' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $statusClass }}">{{ $req->status }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #94a3b8;">No leave applications found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        LOOPS Integrated HRIS • Confidential • Individual Leave Statement
    </div>

</body>
</html>
