<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loops Digital · Attendance Analysis · 1 Apr – 31 Aug 2026</title>
    <style>
        @page {
            margin: 32px 35px 40px 35px;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.45;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
            clear: both;
        }

        .page-header {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 25px;
        }

        .page-footer {
            position: absolute;
            bottom: 0px;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
        }

        .footer-left { float: left; width: 60%; }
        .footer-right { float: right; width: 38%; text-align: right; }

        h1.hero-title {
            font-size: 32px;
            font-weight: 900;
            line-height: 1.05;
            color: #0f172a;
            letter-spacing: -0.8px;
            margin: 0 0 4px 0;
        }

        h1.hero-title span.blue {
            color: #2563eb;
        }

        p.hero-lead {
            font-size: 11px;
            color: #475569;
            line-height: 1.55;
            max-width: 90%;
            margin: 18px 0 28px 0;
        }

        .rule-bar {
            width: 100%;
            border-top: 1px solid #e2e8f0;
            border-bottom: 2px solid #0f172a;
            padding: 16px 0;
            margin-bottom: 30px;
        }
        .rule-col {
            float: left;
            width: 20%;
            border-right: 1px solid #e2e8f0;
            padding: 0 10px;
        }
        .rule-col:last-child { border-right: none; }
        .rule-label {
            font-size: 8px;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .rule-val {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            font-family: monospace, monospace;
        }

        .section-title {
            font-size: 17px;
            font-weight: 900;
            color: #0f172a;
            margin: 0 0 3px 0;
        }
        .section-subtitle {
            font-size: 9.5px;
            color: #64748b;
            margin: 0 0 16px 0;
        }

        .kpi-grid {
            width: 100%;
            border-top: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }
        .kpi-box {
            float: left;
            width: 33.333%;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 12px;
            min-height: 85px;
        }
        .kpi-label {
            font-size: 8px;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .kpi-val {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .kpi-sub {
            font-size: 8px;
            color: #64748b;
            line-height: 1.35;
        }

        /* Patterns Grid */
        .pattern-grid {
            width: 100%;
            border-top: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
            margin-top: 12px;
        }
        .pattern-box {
            float: left;
            width: 50%;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 18px 16px;
            min-height: 145px;
        }
        .pattern-box.full-width {
            width: 100%;
            min-height: 105px;
        }
        .pattern-num {
            font-size: 9px;
            font-weight: 800;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .pattern-title {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .pattern-desc {
            font-size: 9.5px;
            color: #475569;
            line-height: 1.5;
        }

        /* Leaderboards */
        .board-col {
            float: left;
            width: 48.5%;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 14px;
        }
        .board-col.right { float: right; }
        .board-title {
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
            margin: 0 0 3px 0;
        }
        .board-sub {
            font-size: 8.5px;
            color: #64748b;
            margin-bottom: 12px;
        }
        .board-row {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .board-row:last-child { border-bottom: none; }
        .board-name {
            font-size: 9.5px;
            font-weight: 700;
            color: #0f172a;
        }
        .board-meta {
            font-size: 8px;
            color: #64748b;
        }
        .board-bar-bg {
            background-color: #f1f5f9;
            height: 5px;
            border-radius: 3px;
            margin-top: 4px;
            position: relative;
        }
        .board-bar-fill {
            height: 100%;
            border-radius: 3px;
        }
        .board-val {
            float: right;
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Timeline Table */
        .timeline-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        .timeline-table tr {
            border-bottom: 1px solid #f8fafc;
        }
        .timeline-name {
            width: 130px;
            font-weight: 600;
            color: #1e293b;
            padding: 2.5px 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .timeline-cell {
            padding: 2.5px 0;
            position: relative;
        }
        .timeline-track {
            position: relative;
            height: 9px;
            background: #f8fafc;
            border-radius: 1px;
            width: 100%;
        }
        .timeline-pct {
            width: 35px;
            text-align: right;
            font-weight: 700;
            color: #64748b;
            padding: 2.5px 4px;
        }
        .timeline-bar-blue {
            position: absolute;
            top: 0;
            bottom: 0;
            background: #2563eb;
            border-top-left-radius: 1px;
            border-bottom-left-radius: 1px;
        }
        .timeline-bar-orange {
            position: absolute;
            top: 0;
            bottom: 0;
            background: #f97316;
            border-top-right-radius: 1px;
            border-bottom-right-radius: 1px;
        }
        .timeline-grace-line {
            position: absolute;
            top: -2px;
            bottom: -2px;
            left: 11.5%;
            width: 1px;
            background: #cbd5e1;
            z-index: 10;
        }
        .timeline-shift-line {
            position: absolute;
            top: -2px;
            bottom: -2px;
            left: 69.2%;
            width: 1px;
            background: #cbd5e1;
            z-index: 10;
        }
        .timeline-tick-red {
            position: absolute;
            top: -1px;
            bottom: -1px;
            width: 2px;
            background: #ef4444;
            z-index: 20;
        }

        /* Master Table */
        .master-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            margin-top: 10px;
        }
        .master-table th {
            font-size: 7px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: 1.5px solid #0f172a;
            padding: 5px 3px;
            text-align: left;
        }
        .master-table th.center, .master-table td.center { text-align: center; }
        .master-table th.right, .master-table td.right { text-align: right; }
        .master-table td {
            padding: 4px 3px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        .emp-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 8px;
            line-height: 1.1;
        }
        .emp-role {
            font-size: 6.5px;
            color: #64748b;
        }
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 2px;
            font-size: 6.5px;
            font-weight: 800;
            text-transform: capitalize;
            border: 1px solid transparent;
        }
        .badge-shifted { border-color: #f59e0b; color: #b45309; background: #fffbeb; }
        .badge-risk { border-color: #ef4444; color: #b91c1c; background: #fef2f2; }
        .badge-extended { border-color: #ea580c; color: #c2410c; background: #fff7ed; }
        .badge-clock { border-color: #10b981; color: #047857; background: #ecfdf5; }
        .badge-balanced { border-color: #cbd5e1; color: #475569; background: #f8fafc; }

        /* Mix items */
        .mix-row {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .mix-name { font-size: 9.5px; font-weight: 700; color: #0f172a; }
        .mix-pct { font-size: 8.5px; color: #64748b; margin-left: 6px; }
        .mix-count { float: right; font-weight: 800; font-size: 9.5px; color: #0f172a; }
        .mix-track { height: 6px; background: #f1f5f9; border-radius: 3px; margin-top: 3px; }
        .mix-fill { height: 100%; border-radius: 3px; background: #2563eb; }

        .caveats-box {
            font-size: 7.5px;
            color: #475569;
            line-height: 1.5;
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .caveats-title {
            font-size: 8.5px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
        }
        .caveat-item { margin-bottom: 5px; }
        .caveat-item strong { color: #0f172a; }
    </style>
</head>
<body>

    <!-- ==================== PAGE 1 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <h1 class="hero-title">Workforce Attendance &<br><span class="blue">Leave Analytics</span></h1>

        <p class="hero-lead">
            {{ $executiveSummary['narrative'] }}
        </p>

        <!-- Rule parameters bar -->
        <div class="rule-bar">
            <div class="rule-col">
                <div class="rule-label">Shift Start</div>
                <div class="rule-val">{{ $shiftConfig['shift_start'] }}</div>
            </div>
            <div class="rule-col">
                <div class="rule-label">Grace Until</div>
                <div class="rule-val">{{ $shiftConfig['grace_until'] }}</div>
            </div>
            <div class="rule-col">
                <div class="rule-label">Shift End</div>
                <div class="rule-val">{{ $shiftConfig['shift_end'] }}</div>
            </div>
            <div class="rule-col">
                <div class="rule-label">Staff</div>
                <div class="rule-val">{{ $shiftConfig['staff_count'] }}</div>
            </div>
            <div class="rule-col">
                <div class="rule-label">Working Days</div>
                <div class="rule-val">{{ number_format($shiftConfig['working_days']) }}</div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="section-title">The office at a glance</div>
        <div class="section-subtitle">Averages across every recorded working day, all five months, all staff.</div>

        <div class="kpi-grid">
            <div class="kpi-box">
                <div class="kpi-label">{{ $executiveSummary['kpis']['avg_clock_in']['label'] }}</div>
                <div class="kpi-val">{{ $executiveSummary['kpis']['avg_clock_in']['value'] }}</div>
                <div class="kpi-sub">{{ $executiveSummary['kpis']['avg_clock_in']['subtext'] }}</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">{{ $executiveSummary['kpis']['avg_clock_out']['label'] }}</div>
                <div class="kpi-val">{{ $executiveSummary['kpis']['avg_clock_out']['value'] }}</div>
                <div class="kpi-sub">{{ $executiveSummary['kpis']['avg_clock_out']['subtext'] }}</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">{{ $executiveSummary['kpis']['avg_day_length']['label'] }}</div>
                <div class="kpi-val">{{ $executiveSummary['kpis']['avg_day_length']['value'] }}</div>
                <div class="kpi-sub">{{ $executiveSummary['kpis']['avg_day_length']['subtext'] }}</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">{{ $executiveSummary['kpis']['days_arriving_late']['label'] }}</div>
                <div class="kpi-val" style="color: #ef4444;">{{ $executiveSummary['kpis']['days_arriving_late']['value'] }}</div>
                <div class="kpi-sub">{{ $executiveSummary['kpis']['days_arriving_late']['subtext'] }}</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">{{ $executiveSummary['kpis']['days_leaving_early']['label'] }}</div>
                <div class="kpi-val" style="color: #10b981;">{{ $executiveSummary['kpis']['days_leaving_early']['value'] }}</div>
                <div class="kpi-sub">{{ $executiveSummary['kpis']['days_leaving_early']['subtext'] }}</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">{{ $executiveSummary['kpis']['absence_rate']['label'] }}</div>
                <div class="kpi-val" style="color: #2563eb;">{{ $executiveSummary['kpis']['absence_rate']['value'] }}</div>
                <div class="kpi-sub">{{ $executiveSummary['kpis']['absence_rate']['subtext'] }}</div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">1 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 2 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div class="section-title">What the data says</div>
        <div class="section-subtitle">Five patterns that hold across all five months.</div>

        <div class="pattern-grid">
            <div class="pattern-box" style="border-top: 3px solid #ef4444;">
                <div class="pattern-num">{{ $keyPatterns[0]['number'] }}</div>
                <div class="pattern-title">{{ $keyPatterns[0]['title'] }}</div>
                <div class="pattern-desc">{{ $keyPatterns[0]['description'] }}</div>
            </div>
            <div class="pattern-box" style="border-top: 3px solid #f59e0b;">
                <div class="pattern-num">{{ $keyPatterns[1]['number'] }}</div>
                <div class="pattern-title">{{ $keyPatterns[1]['title'] }}</div>
                <div class="pattern-desc">{{ $keyPatterns[1]['description'] }}</div>
            </div>
            <div class="pattern-box" style="border-top: 3px solid #ef4444;">
                <div class="pattern-num">{{ $keyPatterns[2]['number'] }}</div>
                <div class="pattern-title">{{ $keyPatterns[2]['title'] }}</div>
                <div class="pattern-desc">{{ $keyPatterns[2]['description'] }}</div>
            </div>
            <div class="pattern-box" style="border-top: 3px solid #10b981;">
                <div class="pattern-num">{{ $keyPatterns[3]['number'] }}</div>
                <div class="pattern-title">{{ $keyPatterns[3]['title'] }}</div>
                <div class="pattern-desc">{{ $keyPatterns[3]['description'] }}</div>
            </div>
            <div class="pattern-box full-width" style="border-top: 3px solid #2563eb;">
                <div class="pattern-num">{{ $keyPatterns[4]['number'] }}</div>
                <div class="pattern-title">{{ $keyPatterns[4]['title'] }}</div>
                <div class="pattern-desc">{{ $keyPatterns[4]['description'] }}</div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">2 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 3: Timeline Part 1 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div class="section-title">Everyone's average day</div>
        <div class="section-subtitle">
            One bar per person, drawn from their average clock-in to their average clock-out.<br>
            Blue is time inside the 9-to-5 window; orange is time after five. A red tick marks an average arrival that already sits past the 9:30 grace line.
        </div>
        <div style="font-size: 8px; color: #94a3b8; font-weight: bold; margin-bottom: 6px;">All 50 staff, sorted by average arrival time (Part 1).</div>

        <table class="timeline-table">
            @foreach(array_slice($timelineStaff, 0, 33) as $s)
                <tr>
                    <td class="timeline-name">{{ $s['name'] }}</td>
                    <td class="timeline-cell">
                        <div class="timeline-track">
                            <div class="timeline-grace-line"></div>
                            <div class="timeline-shift-line"></div>
                            @if($s['is_past_grace'])
                                <div class="timeline-tick-red" style="left: {{ $s['left_pct'] }}%;"></div>
                            @endif
                            <div class="timeline-bar-blue" style="left: {{ $s['left_pct'] }}%; width: {{ $s['blue_width_pct'] }}%;"></div>
                            @if($s['orange_width_pct'] > 0)
                                <div class="timeline-bar-orange" style="left: {{ $s['left_pct'] + $s['blue_width_pct'] }}%; width: {{ $s['orange_width_pct'] }}%;"></div>
                            @endif
                        </div>
                    </td>
                    <td class="timeline-pct">{{ $s['late_pct'] }}%</td>
                </tr>
            @endforeach
        </table>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">3 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 4: Timeline Part 2 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div style="font-size: 8px; color: #94a3b8; font-weight: bold; margin-bottom: 6px;">All 50 staff, sorted by average arrival time (Part 2).</div>

        <table class="timeline-table">
            @foreach(array_slice($timelineStaff, 33) as $s)
                <tr>
                    <td class="timeline-name">{{ $s['name'] }}</td>
                    <td class="timeline-cell">
                        <div class="timeline-track">
                            <div class="timeline-grace-line"></div>
                            <div class="timeline-shift-line"></div>
                            @if($s['is_past_grace'])
                                <div class="timeline-tick-red" style="left: {{ $s['left_pct'] }}%;"></div>
                            @endif
                            <div class="timeline-bar-blue" style="left: {{ $s['left_pct'] }}%; width: {{ $s['blue_width_pct'] }}%;"></div>
                            @if($s['orange_width_pct'] > 0)
                                <div class="timeline-bar-orange" style="left: {{ $s['left_pct'] + $s['blue_width_pct'] }}%; width: {{ $s['orange_width_pct'] }}%;"></div>
                            @endif
                        </div>
                    </td>
                    <td class="timeline-pct">{{ $s['late_pct'] }}%</td>
                </tr>
            @endforeach
        </table>

        <!-- Timeline axis markings -->
        <div style="margin-top: 15px; position: relative; height: 35px; border-top: 1px solid #cbd5e1; font-size: 7.5px; color: #64748b;">
            <div style="position: absolute; left: 0%;">08:00</div>
            <div style="position: absolute; left: 11.5%; color: #dc2626; font-weight: bold;">09:30 grace</div>
            <div style="position: absolute; left: 23%;">11:00</div>
            <div style="position: absolute; left: 38.5%;">13:00</div>
            <div style="position: absolute; left: 53.8%;">15:00</div>
            <div style="position: absolute; left: 69.2%; font-weight: bold; color: #0f172a;">17:00 shift end</div>
            <div style="position: absolute; left: 84.6%;">19:00</div>
            <div style="position: absolute; right: 0%;">21:00</div>
        </div>

        <div style="margin-top: 20px; font-size: 8px; color: #64748b; line-height: 1.5;">
            <span style="display: inline-block; width: 10px; height: 10px; background: #2563eb; vertical-align: middle; margin-right: 4px;"></span> Inside 09:00–17:00 &nbsp;&nbsp;&nbsp;
            <span style="display: inline-block; width: 10px; height: 10px; background: #f97316; vertical-align: middle; margin-right: 4px;"></span> After 17:00 &nbsp;&nbsp;&nbsp;
            <span style="display: inline-block; width: 2px; height: 10px; background: #ef4444; vertical-align: middle; margin-right: 4px;"></span> Average arrival past 09:30 &nbsp;&nbsp;&nbsp;
            <span style="color: #94a3b8;">Right-hand figure = share of days arriving after 09:30</span>
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">4 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 5 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div class="section-title">The two leaderboards</div>
        <div class="section-subtitle">Chronic lateness on the left, chronic late-staying on the right. Several names appear on both.</div>

        <div style="width: 100%; margin-top: 15px;">
            <!-- Left: Latest Arrivers -->
            <div class="board-col">
                <div class="board-title">Latest arrivers</div>
                <div class="board-sub">Share of days clocking in after 09:30. Staff with 20+ recorded arrivals.</div>

                @foreach($leaderboards['latest_arrivers'] as $row)
                    <div class="board-row">
                        <div class="board-val">{{ $row['late_pct'] }}%</div>
                        <div class="board-name">{{ $row['name'] }}</div>
                        <div class="board-meta">avg in {{ $row['avg_in'] }}</div>
                        <div class="board-bar-bg">
                            <div class="board-bar-fill" style="width: {{ $row['late_pct'] }}%; background: #2563eb;"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Right: Latest Leavers -->
            <div class="board-col right">
                <div class="board-title">Latest leavers</div>
                <div class="board-sub">Average clock-out time. Staff with 20+ recorded departures.</div>

                @foreach($leaderboards['latest_leavers'] as $row)
                    <div class="board-row">
                        <div class="board-val">{{ $row['avg_out'] }}</div>
                        <div class="board-name">{{ $row['name'] }}</div>
                        <div class="board-meta">past 18:30 on {{ $row['past_1830_pct'] }}% of days</div>
                        <div class="board-bar-bg">
                            <div class="board-bar-fill" style="width: {{ $row['past_1830_pct'] }}%; background: #ea580c;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">5 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 6 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div class="section-title">When people actually arrive</div>
        <div class="section-subtitle">Every clock-in across the four months, in 15-minute buckets. The bar at 09:30 is the office's real start time — a cliff, not a curve.</div>

        <!-- Arrival Histogram Visual -->
        <div style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 15px; margin-bottom: 25px;">
            <div style="height: 120px; display: flex; align-items: flex-end; gap: 3px; border-bottom: 1px solid #cbd5e1; position: relative;">
                <div style="position: absolute; left: 51.5%; top: 0; bottom: 0; border-left: 1.5px dashed #dc2626; z-index: 5;">
                    <span style="font-size: 7px; color: #dc2626; font-weight: bold; background: white; padding: 1px 3px;">09:30 grace</span>
                </div>
                @foreach($arrivalDistribution as $bar)
                    @php $barHeight = round(($bar['count'] / 260) * 110); @endphp
                    <div style="flex: 1; text-align: center;">
                        <div style="height: {{ max(2, $barHeight) }}px; background: {{ $bar['is_late'] ? '#ea580c' : '#2563eb' }}; border-top-left-radius: 2px; border-top-right-radius: 2px;"></div>
                    </div>
                @endforeach
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 7px; color: #64748b; margin-top: 4px;">
                <span>07:00</span>
                <span>08:00</span>
                <span>09:00</span>
                <span style="color: #dc2626; font-weight: bold;">09:30</span>
                <span>10:00</span>
                <span>11:00</span>
                <span>12:00</span>
                <span>13:00</span>
            </div>
            <div style="margin-top: 8px; font-size: 8px; color: #475569; display: flex; justify-content: space-between;">
                <span>&larr; <strong>33%</strong> arrive by 09:00</span>
                <span style="color: #ea580c;"><strong>36%</strong> arrive after 09:30 &rarr;</span>
            </div>
        </div>

        <div class="section-title">Late in, late out — or just late?</div>
        <div class="section-subtitle">
            Each dot is one person: how often they arrive after 09:30, against how often they stay past 18:30. The two move together (r = 0.52), which is the single most important thing in this dataset — most late arrivals are shifted days, not short ones. The bottom-right corner is where a punctuality conversation actually belongs.
        </div>

        <!-- 4 Quadrant Chart Matrix -->
        <div style="border: 1px solid #cbd5e1; height: 260px; position: relative; background: #fafafa; margin-top: 15px;">
            <!-- Dividing Lines -->
            <div style="position: absolute; left: 50%; top: 0; bottom: 0; border-left: 1px dashed #cbd5e1;"></div>
            <div style="position: absolute; top: 50%; left: 0; right: 0; border-top: 1px dashed #cbd5e1;"></div>

            <!-- Quadrant Labels -->
            <div style="position: absolute; top: 8px; left: 10px; font-size: 7.5px; font-weight: 800; color: #94a3b8;">ON TIME, STAYS LATE</div>
            <div style="position: absolute; top: 8px; right: 10px; font-size: 7.5px; font-weight: 800; color: #94a3b8;">LATE IN, LATE OUT</div>
            <div style="position: absolute; bottom: 8px; left: 10px; font-size: 7.5px; font-weight: 800; color: #94a3b8;">ON THE CLOCK</div>
            <div style="position: absolute; bottom: 8px; right: 10px; font-size: 7.5px; font-weight: 800; color: #dc2626;">LATE IN, OUT ON TIME — the punctuality question</div>

            <!-- Scatter dots -->
            @foreach($scatterPlotData as $pt)
                @php
                    $leftPos = max(3, min(95, $pt['x']));
                    $bottomPos = max(3, min(95, $pt['y']));
                @endphp
                <div style="position: absolute; left: {{ $leftPos }}%; bottom: {{ $bottomPos }}%; width: 7px; height: 7px; border-radius: 50%; background: {{ $pt['color'] }}; margin-left: -3.5px; margin-bottom: -3.5px;" title="{{ $pt['name'] }}: {{ $pt['x'] }}% in, {{ $pt['y'] }}% out"></div>
            @endforeach
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 7px; color: #64748b; margin-top: 4px;">
            <span>0% Share arriving after 09:30 &rarr;</span>
            <span>100%</span>
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">6 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 7 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div class="section-title">Trends and cuts</div>
        <div class="section-subtitle">Month by month, weekday by weekday, department by department. April includes six people who left before May.</div>

        <div style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 18px; margin-top: 15px;">
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Month by month</div>
            <div style="font-size: 8.5px; color: #64748b; margin-bottom: 25px;">
                Two scales, because the movements are small. Arrivals bounce inside a ten-minute band all five months; departures climb almost without interruption, ending 24 minutes later in August than in April.
            </div>

            <!-- Clock-In Trend -->
            <div style="margin-bottom: 30px;">
                <div style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Average clock-in</div>
                <table style="width: 100%; text-align: center; font-size: 9px; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: bold;">
                        <th style="padding: 6px;">Apr</th>
                        <th style="padding: 6px;">May</th>
                        <th style="padding: 6px;">Jun</th>
                        <th style="padding: 6px;">Jul</th>
                        <th style="padding: 6px;">Aug</th>
                    </tr>
                    <tr style="font-size: 14px; font-weight: 900; color: #2563eb; font-family: monospace;">
                        @foreach($trendsAndCuts['months']['avg_in'] as $val)
                            <td style="padding: 10px;">{{ $val }}</td>
                        @endforeach
                    </tr>
                </table>
            </div>

            <!-- Clock-Out Trend -->
            <div>
                <div style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Average clock-out</div>
                <table style="width: 100%; text-align: center; font-size: 9px; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: bold;">
                        <th style="padding: 6px;">Apr</th>
                        <th style="padding: 6px;">May</th>
                        <th style="padding: 6px;">Jun</th>
                        <th style="padding: 6px;">Jul</th>
                        <th style="padding: 6px;">Aug</th>
                    </tr>
                    <tr style="font-size: 14px; font-weight: 900; color: #ea580c; font-family: monospace;">
                        @foreach($trendsAndCuts['months']['avg_out'] as $val)
                            <td style="padding: 10px;">{{ $val }}</td>
                        @endforeach
                    </tr>
                </table>
            </div>
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">7 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 8 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <!-- By Day of the Week -->
        <div style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 16px; margin-bottom: 25px;">
            <div class="section-title" style="font-size: 13px;">By day of the week</div>
            <div class="section-subtitle">Thursday is the latest-arriving day, Friday the latest-finishing and most-absent. <span style="color: #2563eb; font-weight: bold;">■ late in</span> &nbsp; <span style="color: #ea580c; font-weight: bold;">■ past 18:30</span></div>

            <table style="width: 100%; text-align: center; font-size: 8.5px; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b;">
                    @foreach($trendsAndCuts['weekdays']['labels'] as $day)
                        <th style="padding: 4px;">{{ $day }}</th>
                    @endforeach
                </tr>
                <tr>
                    @for($i = 0; $i < count($trendsAndCuts['weekdays']['labels']); $i++)
                        <td style="padding: 8px 4px;">
                            <div style="font-weight: 800; color: #2563eb;">{{ $trendsAndCuts['weekdays']['late_in_pct'][$i] }}% Late</div>
                            <div style="font-weight: 800; color: #ea580c; margin-top: 2px;">{{ $trendsAndCuts['weekdays']['past_1830_pct'][$i] }}% Past 18:30</div>
                        </td>
                    @endfor
                </tr>
            </table>
        </div>

        <!-- By Department -->
        <div style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 16px;">
            <div class="section-title" style="font-size: 13px;">By department</div>
            <div class="section-subtitle">Average clock-in against average clock-out. IT, Finance, HR and Admin run to the clock; Creative and Digital Marketing run on a shifted day.</div>

            <table style="width: 100%; font-size: 8.5px; border-collapse: collapse; margin-top: 10px;">
                @foreach($trendsAndCuts['departments'] as $dept)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="width: 140px; font-weight: 700; color: #0f172a; padding: 5px 4px;">{{ $dept['name'] }}</td>
                        <td style="padding: 5px 0;">
                            <div style="position: relative; height: 10px; background: #f1f5f9; border-radius: 2px;">
                                <div style="position: absolute; left: {{ $dept['in_pct'] }}%; width: {{ $dept['blue_width'] }}%; top: 0; bottom: 0; background: #2563eb;"></div>
                                <div style="position: absolute; left: {{ $dept['in_pct'] + $dept['blue_width'] }}%; width: {{ $dept['orange_width'] }}%; top: 0; bottom: 0; background: #ea580c;"></div>
                            </div>
                        </td>
                        <td style="width: 45px; text-align: right; font-weight: 800; color: #64748b; padding: 5px 4px;">{{ $dept['late_rate'] }}%</td>
                    </tr>
                @endforeach
            </table>
            <div style="font-size: 7.5px; color: #94a3b8; text-align: right; margin-top: 8px;">
                bar = average in &rarr; average out · right column = late-arrival rate
            </div>
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">8 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 9 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div class="section-title">Leave and exception mix</div>
        <div class="section-subtitle">{{ $leaveAndExceptionMix['total_tagged_days'] }} leave-tagged days {{ $leaveAndExceptionMix['period_label'] }}.</div>

        <div style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 18px; margin-top: 15px;">
            @foreach($leaveAndExceptionMix['items'] as $item)
                <div class="mix-row">
                    <span class="mix-name">{{ $item['name'] }}</span>
                    <span class="mix-pct">{{ $item['pct'] }}% of tagged days</span>
                    <span class="mix-count">{{ $item['count'] }}</span>
                    <div class="mix-track">
                        <div class="mix-fill" style="width: {{ $item['pct'] * 3.5 }}%; background: {{ $item['color'] }};"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">9 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 10: Master Table Part 1 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div class="section-title">Everyone, every measure</div>
        <div class="section-subtitle">Click a column heading to sort in the online view. Staff 1–23.</div>

        <table class="master-table">
            <thead>
                <tr>
                    <th style="width: 130px;">Name</th>
                    <th style="width: 90px;">Dept</th>
                    <th class="center">Avg In</th>
                    <th class="center">Avg Out</th>
                    <th class="center">Avg Hrs</th>
                    <th class="center" style="color: #ef4444;">Late %</th>
                    <th class="center">Avg Late</th>
                    <th class="center">After 10:00</th>
                    <th class="center">Past 18:30 %</th>
                    <th class="center">Past 20:00</th>
                    <th class="center">Extra Hrs</th>
                    <th class="center">Absent</th>
                    <th>Pattern</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($allStaffMeasures, 0, 23) as $row)
                    @php
                        $badgeClass = match($row['pattern']) {
                            'Shifted schedule' => 'badge-shifted',
                            'Punctuality risk' => 'badge-risk',
                            'Overextended' => 'badge-extended',
                            'On the clock' => 'badge-clock',
                            default => 'badge-balanced',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="emp-name">{{ $row['name'] }}</div>
                            <div class="emp-role">{{ $row['role'] }}</div>
                        </td>
                        <td style="color: #64748b;">{{ $row['dept'] }}</td>
                        <td class="center font-mono">{{ $row['avg_in'] }}</td>
                        <td class="center font-mono">{{ $row['avg_out'] }}</td>
                        <td class="center font-bold">{{ $row['avg_hrs'] }}</td>
                        <td class="center font-bold" style="color: {{ $row['late_pct'] > 50 ? '#ef4444' : '#10b981' }};">{{ $row['late_pct'] }}%</td>
                        <td class="center">{{ $row['avg_late'] }}</td>
                        <td class="center">{{ $row['after_1000'] }}</td>
                        <td class="center">{{ $row['past_1830_pct'] }}%</td>
                        <td class="center">{{ $row['past_2000'] }}</td>
                        <td class="center font-bold">{{ $row['extra_hrs'] }}</td>
                        <td class="center">{{ $row['absent'] }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $row['pattern'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">10 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 11: Master Table Part 2 ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <div class="section-title">Everyone, every measure</div>
        <div class="section-subtitle">Staff 24–48.</div>

        <table class="master-table">
            <thead>
                <tr>
                    <th style="width: 130px;">Name</th>
                    <th style="width: 90px;">Dept</th>
                    <th class="center">Avg In</th>
                    <th class="center">Avg Out</th>
                    <th class="center">Avg Hrs</th>
                    <th class="center" style="color: #ef4444;">Late %</th>
                    <th class="center">Avg Late</th>
                    <th class="center">After 10:00</th>
                    <th class="center">Past 18:30 %</th>
                    <th class="center">Past 20:00</th>
                    <th class="center">Extra Hrs</th>
                    <th class="center">Absent</th>
                    <th>Pattern</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($allStaffMeasures, 23, 25) as $row)
                    @php
                        $badgeClass = match($row['pattern']) {
                            'Shifted schedule' => 'badge-shifted',
                            'Punctuality risk' => 'badge-risk',
                            'Overextended' => 'badge-extended',
                            'On the clock' => 'badge-clock',
                            default => 'badge-balanced',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="emp-name">{{ $row['name'] }}</div>
                            <div class="emp-role">{{ $row['role'] }}</div>
                        </td>
                        <td style="color: #64748b;">{{ $row['dept'] }}</td>
                        <td class="center font-mono">{{ $row['avg_in'] }}</td>
                        <td class="center font-mono">{{ $row['avg_out'] }}</td>
                        <td class="center font-bold">{{ $row['avg_hrs'] }}</td>
                        <td class="center font-bold" style="color: {{ $row['late_pct'] > 50 ? '#ef4444' : '#10b981' }};">{{ $row['late_pct'] }}%</td>
                        <td class="center">{{ $row['avg_late'] }}</td>
                        <td class="center">{{ $row['after_1000'] }}</td>
                        <td class="center">{{ $row['past_1830_pct'] }}%</td>
                        <td class="center">{{ $row['past_2000'] }}</td>
                        <td class="center font-bold">{{ $row['extra_hrs'] }}</td>
                        <td class="center">{{ $row['absent'] }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $row['pattern'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">11 / 12</div>
        </div>
    </div>
    <div class="page-break"></div>

    <!-- ==================== PAGE 12: Final Table & Method / Caveats ==================== -->
    <div style="position: relative; min-height: 1000px;">
        <div class="page-header">Loops Digital · Attendance analysis · {{ $shiftConfig['period_label'] }}</div>

        <table class="master-table" style="margin-top: 0; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="width: 130px;">Name</th>
                    <th style="width: 90px;">Dept</th>
                    <th class="center">Avg In</th>
                    <th class="center">Avg Out</th>
                    <th class="center">Avg Hrs</th>
                    <th class="center" style="color: #ef4444;">Late %</th>
                    <th class="center">Avg Late</th>
                    <th class="center">After 10:00</th>
                    <th class="center">Past 18:30 %</th>
                    <th class="center">Past 20:00</th>
                    <th class="center">Extra Hrs</th>
                    <th class="center">Absent</th>
                    <th>Pattern</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($allStaffMeasures, 48) as $row)
                    @php
                        $badgeClass = match($row['pattern']) {
                            'Shifted schedule' => 'badge-shifted',
                            'Punctuality risk' => 'badge-risk',
                            'Overextended' => 'badge-extended',
                            'On the clock' => 'badge-clock',
                            default => 'badge-balanced',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="emp-name">{{ $row['name'] }}</div>
                            <div class="emp-role">{{ $row['role'] }}</div>
                        </td>
                        <td style="color: #64748b;">{{ $row['dept'] }}</td>
                        <td class="center font-mono">{{ $row['avg_in'] }}</td>
                        <td class="center font-mono">{{ $row['avg_out'] }}</td>
                        <td class="center font-bold">{{ $row['avg_hrs'] }}</td>
                        <td class="center font-bold" style="color: {{ $row['late_pct'] > 50 ? '#ef4444' : '#10b981' }};">{{ $row['late_pct'] }}%</td>
                        <td class="center">{{ $row['avg_late'] }}</td>
                        <td class="center">{{ $row['after_1000'] }}</td>
                        <td class="center">{{ $row['past_1830_pct'] }}%</td>
                        <td class="center">{{ $row['past_2000'] }}</td>
                        <td class="center font-bold">{{ $row['extra_hrs'] }}</td>
                        <td class="center">{{ $row['absent'] }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $row['pattern'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Method and Caveats -->
        <div class="caveats-box">
            <div class="caveats-title">Method and Caveats</div>
            @foreach($methodAndCaveats as $c)
                <div class="caveat-item">
                    <strong>{{ $c['title'] }}.</strong> {{ $c['text'] }}
                </div>
            @endforeach
        </div>

        <div class="page-footer">
            <div class="footer-left">{{ $shiftConfig['source_label'] }}</div>
            <div class="footer-right">12 / 12</div>
        </div>
    </div>

</body>
</html>
