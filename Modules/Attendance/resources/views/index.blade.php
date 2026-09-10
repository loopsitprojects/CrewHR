@extends('layouts.app', ['title' => 'LOOPS HR - Attendance & Time Tracking', 'breadcrumb' => 'Attendance'])

@section('content')
<div class="space-y-6" x-data="{ 
    tab: '{{ $tab }}',
    clockedIn: {{ $isClockedIn ? 'true' : 'false' }},
    currentTime: '',
    currentDate: '',
    manualModalOpen: false,
    manualEditMode: false,
    manualForm: {
        id: '',
        employee_id: '{{ $activeEmp ? $activeEmp->id : ($employees->first()->id ?? '') }}',
        date: '{{ $date }}',
        clock_in: '09:00',
        clock_out: '17:00',
        notes: ''
    },
    adjustmentModalOpen: false,
    importModalOpen: false,
    adjForm: {
        date: '{{ $date }}',
        clock_in: '09:00',
        clock_out: '17:00',
        reason: ''
    },
    reviewModalOpen: false,
    reviewForm: {
        id: '',
        action: 'Approved',
        review_remarks: '',
        emp_name: '',
        date: '',
        clock_in: '',
        clock_out: '',
        reason: ''
    },
    init() {
        this.updateClock();
        setInterval(() => this.updateClock(), 1000);
    },
    updateClock() {
        const now = new Date();
        this.currentTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        this.currentDate = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    },
    openManualCreate() {
        this.manualEditMode = false;
        this.manualForm = {
            id: '',
            employee_id: '{{ $activeEmp ? $activeEmp->id : ($employees->first()->id ?? '') }}',
            date: '{{ $date }}',
            clock_in: '09:00',
            clock_out: '17:00',
            notes: ''
        };
        this.manualModalOpen = true;
    },
    openManualEdit(att, empId, empDate) {
        this.manualEditMode = true;
        this.manualForm = {
            id: att.id,
            employee_id: empId,
            date: empDate,
            clock_in: att.clock_in ? att.clock_in.substring(11, 16) : '09:00',
            clock_out: att.clock_out ? att.clock_out.substring(11, 16) : '17:00',
            notes: att.notes || ''
        };
        this.manualModalOpen = true;
    },
    openReviewModal(adj) {
        this.reviewForm = {
            id: adj.id,
            action: 'Approved',
            review_remarks: '',
            emp_name: adj.employee && adj.employee.user ? adj.employee.user.name : 'Employee',
            date: adj.date,
            clock_in: adj.clock_in,
            clock_out: adj.clock_out,
            reason: adj.reason
        };
        this.reviewModalOpen = true;
    }
}">

    <!-- Alert Banners -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-emerald-600 text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-warning-circle text-rose-600 text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-info text-blue-600 text-lg"></i>
                <span>{{ session('info') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    <!-- Top Hero Section & Real-Time Punch Clock Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                    <i class="ph ph-fingerprint-simple text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Attendance & Time Tracking</h1>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500 mt-0.5">
                        <span>Standard Shift: <strong class="text-slate-800">{{ date('h:i A', strtotime($shiftSettings['shift_start'])) }} &ndash; {{ date('h:i A', strtotime($shiftSettings['shift_end'])) }}</strong></span>
                        <span class="text-slate-300">&bull;</span>
                        <span>Grace Period: <strong class="text-amber-600">{{ $shiftSettings['grace_period'] }} mins</strong></span>
                        <span class="text-slate-300">&bull;</span>
                        <span>OT Multiplier: <strong class="text-blue-600">{{ $shiftSettings['ot_multiplier'] }}x</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Punch Clock & Digital Time Display -->
        <div class="flex flex-wrap items-center gap-4 bg-slate-50 border border-slate-200/80 p-3.5 rounded-2xl">
            <div class="text-right pr-2 border-r border-slate-200">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider" x-text="currentDate"></div>
                <div class="text-lg font-black text-slate-800 tracking-tight font-mono" x-text="currentTime">--:--:--</div>
            </div>

            <div class="flex items-center gap-2">
                <form action="{{ route('attendance.clock_toggle') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-extrabold flex items-center gap-2 shadow-md transition-all cursor-pointer"
                        :class="clockedIn ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-rose-500/20' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/20'">
                        <i class="ph ph-fingerprint text-lg"></i>
                        <span>{{ $isClockedIn ? 'Clock Out' : 'Clock In' }}</span>
                    </button>
                </form>

                <button @click="adjustmentModalOpen = true" class="px-3.5 py-2.5 rounded-xl text-xs font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs flex items-center gap-1.5 transition-all">
                    <i class="ph ph-clock-counter-clockwise text-base text-blue-600"></i>
                    <span class="hidden sm:inline">Regularize</span>
                </button>

                @if(in_array($role, ['Super Admin', 'HR Admin', 'HR Manager']))
                    <button @click="openManualCreate()" class="px-3.5 py-2.5 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 shadow-2xs flex items-center gap-1.5 transition-all">
                        <i class="ph ph-plus-circle text-base"></i>
                        <span class="hidden sm:inline">Manual Entry</span>
                    </button>

                    <button @click="importModalOpen = true" class="px-3.5 py-2.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 shadow-2xs flex items-center gap-1.5 transition-all">
                        <i class="ph ph-file-csv text-base text-indigo-600"></i>
                        <span class="hidden sm:inline">Upload Machine CSV</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards (Daily Overview) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Staff</div>
            <div class="text-xl font-black text-slate-800 mt-1">{{ $totalEmployeesCount }}</div>
            <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Active Workforce</div>
        </div>

        <div class="bg-emerald-50/50 p-4 rounded-2xl border border-emerald-200/80 shadow-2xs">
            <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Present Today</div>
            <div class="text-xl font-black text-emerald-700 mt-1">{{ $presentTodayCount }}</div>
            <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">{{ $totalEmployeesCount > 0 ? round(($presentTodayCount / $totalEmployeesCount) * 100) : 0 }}% Attendance</div>
        </div>

        <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-200/80 shadow-2xs">
            <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Late Arrivals</div>
            <div class="text-xl font-black text-amber-700 mt-1">{{ $lateArrivalsCount }}</div>
            <div class="text-[10px] text-amber-600 font-semibold mt-0.5">After {{ date('h:i A', strtotime($shiftSettings['shift_start'] . ' +' . $shiftSettings['grace_period'] . ' minutes')) }}</div>
        </div>

        <div class="bg-purple-50/50 p-4 rounded-2xl border border-purple-200/80 shadow-2xs">
            <div class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">On Leave</div>
            <div class="text-xl font-black text-purple-700 mt-1">{{ $onLeaveTodayCount }}</div>
            <div class="text-[10px] text-purple-600 font-semibold mt-0.5">Approved Leaves</div>
        </div>

        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-200/80 shadow-2xs">
            <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Total Work Hours</div>
            <div class="text-xl font-black text-blue-700 mt-1">{{ round($totalWorkHoursToday, 1) }}h</div>
            <div class="text-[10px] text-blue-600 font-semibold mt-0.5">Logged Hours</div>
        </div>

        <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-200/80 shadow-2xs">
            <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Overtime Hours</div>
            <div class="text-xl font-black text-indigo-700 mt-1">{{ round($totalOtHoursToday, 1) }}h</div>
            <div class="text-[10px] text-indigo-600 font-semibold mt-0.5">Early & Late OT</div>
        </div>
    </div>

    <!-- Multi-View Navigation Tabs -->
    <div class="flex flex-wrap items-center justify-between border-b border-slate-200 gap-2">
        <div class="flex items-center gap-1">
            <a href="{{ route('attendance.index', ['tab' => 'daily', 'date' => $date, 'department_id' => $deptFilter]) }}" 
                class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $tab === 'daily' ? 'border-blue-600 text-blue-600 bg-white font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <i class="ph ph-calendar-check text-base"></i>
                <span>Daily Roster</span>
            </a>

            <a href="{{ route('attendance.index', ['tab' => 'monthly', 'month' => $month, 'year' => $year, 'department_id' => $deptFilter]) }}" 
                class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $tab === 'monthly' ? 'border-blue-600 text-blue-600 bg-white font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <i class="ph ph-grid-four text-base"></i>
                <span>Monthly Timesheet Matrix</span>
            </a>

            <a href="{{ route('attendance.index', ['tab' => 'my_attendance', 'month' => $month, 'year' => $year]) }}" 
                class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $tab === 'my_attendance' ? 'border-blue-600 text-blue-600 bg-white font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <i class="ph ph-user text-base"></i>
                <span>My Attendance</span>
            </a>

            <a href="{{ route('attendance.index', ['tab' => 'adjustments']) }}" 
                class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $tab === 'adjustments' ? 'border-blue-600 text-blue-600 bg-white font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <i class="ph ph-arrows-counter-clockwise text-base"></i>
                <span>Regularizations</span>
                @if($pendingAdjustmentsCount > 0)
                    <span class="bg-amber-500 text-white text-[10px] font-black px-1.5 py-0.2 rounded-full">{{ $pendingAdjustmentsCount }}</span>
                @endif
            </a>

            @if(in_array($role, ['Super Admin', 'HR Admin', 'HR Manager']))
                <a href="{{ route('attendance.index', ['tab' => 'import']) }}" 
                    class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $tab === 'import' ? 'border-blue-600 text-blue-600 bg-white font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                    <i class="ph ph-upload-simple text-base"></i>
                    <span>Biometric Import</span>
                </a>
            @endif
        </div>
    </div>

    <!-- TAB 1: DAILY ROSTER -->
    @if($tab === 'daily')
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            <!-- Filter & Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <form method="GET" action="{{ route('attendance.index') }}" class="flex flex-wrap items-center gap-2.5 text-xs font-bold text-slate-600">
                    <input type="hidden" name="tab" value="daily">
                    
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400 font-semibold">Date:</span>
                        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-1.5 px-3">
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400 font-semibold">Department:</span>
                        <select name="department_id" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-1.5 px-3">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $deptFilter == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                @if(in_array($role, ['Super Admin', 'HR Admin', 'HR Manager']))
                    <div class="flex items-center gap-2">
                        <form action="{{ route('attendance.sync_leaves') }}" method="POST">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button type="submit" class="bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                                <i class="ph ph-arrows-clockwise text-sm"></i>
                                <span>Sync With Approved Leaves</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Table -->
            <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
                <table class="w-full text-left text-xs font-semibold text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 uppercase text-[10px] font-extrabold border-b border-slate-200">
                        <tr>
                            <th class="p-3">Employee</th>
                            <th class="p-3">Department</th>
                            <th class="p-3 text-center">Clock In</th>
                            <th class="p-3 text-center">Clock Out</th>
                            <th class="p-3 text-center">Total Hours</th>
                            <th class="p-3 text-center">Regular</th>
                            <th class="p-3 text-center">Early OT</th>
                            <th class="p-3 text-center">Late OT</th>
                            <th class="p-3 text-center">Total OT</th>
                            <th class="p-3 text-center">Status</th>
                            @if(in_array($role, ['Super Admin', 'HR Admin', 'HR Manager']))
                                <th class="p-3 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($employees as $emp)
                            @php
                                $att = $attendances->get($emp->id);
                                $isOnLeave = isset($leavesOnDate[$emp->id]);
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="p-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs">
                                            {{ substr($emp->user->name ?? 'E', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $emp->user->name ?? 'Employee' }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $emp->employee_id_number ?? 'EMP-'.$emp->id }} &bull; {{ $emp->designation->name ?? 'Staff' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded">
                                        {{ $emp->department->name ?? 'Corporate' }}
                                    </span>
                                </td>
                                <td class="p-3 text-center font-bold text-slate-900">
                                    {{ $att && $att->clock_in ? Carbon\Carbon::parse($att->clock_in)->format('h:i A') : '—' }}
                                </td>
                                <td class="p-3 text-center font-bold text-slate-900">
                                    {{ $att && $att->clock_out ? Carbon\Carbon::parse($att->clock_out)->format('h:i A') : ($att && $att->clock_in ? 'In Progress' : '—') }}
                                </td>
                                <td class="p-3 text-center font-black text-slate-900">
                                    {{ $att ? $att->total_hours . 'h' : '0.0h' }}
                                </td>
                                <td class="p-3 text-center text-slate-600 font-semibold">
                                    {{ $att ? $att->regular_hours . 'h' : '0.0h' }}
                                </td>
                                <td class="p-3 text-center">
                                    @if($att && $att->early_ot_hours > 0)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                                            +{{ $att->early_ot_hours }}h
                                        </span>
                                    @else
                                        <span class="text-slate-300">0</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($att && $att->late_ot_hours > 0)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                                            +{{ $att->late_ot_hours }}h
                                        </span>
                                    @else
                                        <span class="text-slate-300">0</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center font-black text-amber-600">
                                    {{ $att && $att->total_ot_hours > 0 ? $att->total_ot_hours . 'h' : '0.0' }}
                                </td>
                                <td class="p-3 text-center">
                                    @if($att)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $att->status_badge_class }}">
                                            ● {{ $att->status }}
                                        </span>
                                    @elseif($isOnLeave)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-50 text-purple-700 border border-purple-200">
                                            ● On Leave
                                        </span>
                                    @else
                                        <span class="bg-slate-100 text-slate-400 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                            Not Checked In
                                        </span>
                                    @endif
                                </td>
                                @if(in_array($role, ['Super Admin', 'HR Admin', 'HR Manager']))
                                    <td class="p-3 text-right">
                                        @if($att)
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button @click="openManualEdit({{ json_encode($att) }}, {{ $emp->id }}, '{{ $date }}')" class="p-1 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-blue-600 transition-colors" title="Edit Record">
                                                    <i class="ph ph-pencil-simple text-sm"></i>
                                                </button>
                                                <form action="{{ route('attendance.destroy', $att->id) }}" method="POST" onsubmit="return confirm('Remove attendance record for this date?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                                                        <i class="ph ph-trash text-sm"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <button @click="manualForm.employee_id = '{{ $emp->id }}'; manualForm.date = '{{ $date }}'; manualModalOpen = true;" class="text-blue-600 hover:text-blue-800 text-[11px] font-bold">
                                                + Log
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="p-8 text-center text-slate-400 text-xs font-semibold">
                                    No employees found for this criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 2: MONTHLY TIMESHEET MATRIX (1-31 GRID) -->
    @if($tab === 'monthly')
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            <!-- Filter Toolbar & Legend -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-3 border-b border-slate-100">
                <form method="GET" action="{{ route('attendance.index') }}" class="flex flex-wrap items-center gap-2.5 text-xs font-bold text-slate-600">
                    <input type="hidden" name="tab" value="monthly">
                    
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400 font-semibold">Month:</span>
                        <select name="month" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-1.5 px-3">
                            @foreach($monthsList as $mNum => $mName)
                                <option value="{{ $mNum }}" {{ $month == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400 font-semibold">Year:</span>
                        <select name="year" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-1.5 px-3">
                            @foreach($yearsList as $yNum)
                                <option value="{{ $yNum }}" {{ $year == $yNum ? 'selected' : '' }}>{{ $yNum }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400 font-semibold">Department:</span>
                        <select name="department_id" onchange="this.form.submit()" class="border-slate-200 rounded-xl text-xs font-extrabold bg-slate-50 py-1.5 px-3">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $deptFilter == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('attendance.export_monthly', ['month' => $month, 'year' => $year, 'department_id' => $deptFilter]) }}" class="bg-blue-600 hover:bg-blue-700 text-white shadow-xs px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                        <i class="ph ph-file-csv text-base"></i>
                        <span>Export Timesheet (CSV)</span>
                    </a>
                </div>
            </div>

            <!-- Matrix Legend Badges -->
            <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-200/70">
                <span class="text-slate-400 font-extrabold uppercase mr-1">Legend:</span>
                <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded">P: Present</span>
                <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded">L: Late</span>
                <span class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded">HD: Half Day</span>
                <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded">LV: Leave</span>
                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded">H: Holiday</span>
                <span class="bg-slate-200 text-slate-600 px-2 py-0.5 rounded">WO: Week Off</span>
                <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded">A: Absent</span>
            </div>

            <!-- Matrix Grid -->
            <div class="overflow-x-auto border border-slate-200/80 rounded-xl max-h-[600px]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-100 text-slate-600 font-black sticky top-0 z-10 border-b border-slate-200 shadow-2xs">
                        <tr>
                            <th class="p-2.5 sticky left-0 z-20 bg-slate-100 min-w-[160px] border-r border-slate-200">Employee</th>
                            @foreach($monthDays as $mDay)
                                <th class="p-1 text-center min-w-[28px] border-r border-slate-200 {{ $mDay['is_weekend'] ? 'bg-slate-200/70 text-slate-400' : '' }}">
                                    <div class="text-[9px] uppercase">{{ $mDay['day_name'][0] }}</div>
                                    <div class="text-[11px]">{{ $mDay['day'] }}</div>
                                </th>
                            @endforeach
                            <th class="p-2 text-center bg-emerald-50 text-emerald-800 font-extrabold border-r border-slate-200 min-w-[45px]">Pres</th>
                            <th class="p-2 text-center bg-amber-50 text-amber-800 font-extrabold border-r border-slate-200 min-w-[45px]">Late</th>
                            <th class="p-2 text-center bg-purple-50 text-purple-800 font-extrabold border-r border-slate-200 min-w-[45px]">Leave</th>
                            <th class="p-2 text-center bg-blue-50 text-blue-800 font-extrabold border-r border-slate-200 min-w-[50px]">Hours</th>
                            <th class="p-2 text-center bg-indigo-50 text-indigo-800 font-extrabold min-w-[45px]">OT</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($matrixEmployees as $item)
                            @php
                                $emp = $item['employee'];
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="p-2.5 sticky left-0 z-10 bg-white border-r border-slate-200">
                                    <div class="font-bold text-slate-900 truncate max-w-[150px]">{{ $emp->user->name ?? 'Employee' }}</div>
                                    <div class="text-[10px] text-slate-400 truncate">{{ $emp->employee_id_number }}</div>
                                </td>

                                @foreach($monthDays as $mDay)
                                    @php
                                        $d = $mDay['day'];
                                        $dayInfo = $item['days'][$d] ?? ['code' => '-', 'badge' => 'text-slate-300'];
                                        $code = $dayInfo['code'];
                                        $badgeStyle = match($code) {
                                            'P' => 'bg-emerald-100 text-emerald-800 font-bold',
                                            'L' => 'bg-amber-100 text-amber-800 font-bold',
                                            'HD' => 'bg-indigo-100 text-indigo-800 font-bold',
                                            'LV' => 'bg-purple-100 text-purple-800 font-bold',
                                            'H' => 'bg-blue-100 text-blue-800 font-bold',
                                            'WO' => 'bg-slate-100 text-slate-400',
                                            'A' => 'bg-rose-100 text-rose-700 font-bold',
                                            default => 'text-slate-300'
                                        };
                                    @endphp
                                    <td class="p-0.5 text-center border-r border-slate-200/60 {{ $mDay['is_weekend'] ? 'bg-slate-50/50' : '' }}" title="{{ $dayInfo['status'] ?? '' }}">
                                        <div class="w-6 h-6 mx-auto rounded flex items-center justify-center text-[10px] {{ $badgeStyle }}">
                                            {{ $code }}
                                        </div>
                                    </td>
                                @endforeach

                                <td class="p-2 text-center font-bold text-emerald-700 bg-emerald-50/30 border-r border-slate-200">{{ $item['present_days'] }}</td>
                                <td class="p-2 text-center font-bold text-amber-700 bg-amber-50/30 border-r border-slate-200">{{ $item['late_days'] }}</td>
                                <td class="p-2 text-center font-bold text-purple-700 bg-purple-50/30 border-r border-slate-200">{{ $item['leave_days'] }}</td>
                                <td class="p-2 text-center font-black text-blue-700 bg-blue-50/30 border-r border-slate-200">{{ $item['total_hours'] }}h</td>
                                <td class="p-2 text-center font-black text-indigo-700 bg-indigo-50/30">{{ $item['total_ot_hours'] }}h</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 3: MY ATTENDANCE (PERSONAL TIMESHEET) -->
    @if($tab === 'my_attendance')
        <div class="space-y-4">
            <!-- Personal Monthly Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase">Days Present</div>
                    <div class="text-xl font-black text-emerald-600 mt-1">{{ $myStats['present'] }}</div>
                    <div class="text-[10px] text-slate-400 font-semibold mt-0.5">This Month</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase">Late Check-Ins</div>
                    <div class="text-xl font-black text-amber-600 mt-1">{{ $myStats['late'] }}</div>
                    <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Recorded Late</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase">Total Hours Worked</div>
                    <div class="text-xl font-black text-blue-600 mt-1">{{ $myStats['hours'] }}h</div>
                    <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Logged Time</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase">Overtime Accumulated</div>
                    <div class="text-xl font-black text-indigo-600 mt-1">{{ $myStats['ot_hours'] }}h</div>
                    <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Syncs to Payslip</div>
                </div>
            </div>

            <!-- Personal Logs Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-black text-slate-800">My Monthly Attendance Log</h3>
                    <button @click="adjustmentModalOpen = true" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                        <i class="ph ph-plus-circle text-sm"></i>
                        <span>Request Regularization</span>
                    </button>
                </div>

                <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
                    <table class="w-full text-left text-xs font-semibold text-slate-600">
                        <thead class="bg-slate-50 text-slate-400 uppercase text-[10px] font-extrabold border-b border-slate-200">
                            <tr>
                                <th class="p-3">Date</th>
                                <th class="p-3 text-center">Clock In</th>
                                <th class="p-3 text-center">Clock Out</th>
                                <th class="p-3 text-center">Regular Hours</th>
                                <th class="p-3 text-center">Overtime Hours</th>
                                <th class="p-3 text-center">Total Hours</th>
                                <th class="p-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($myMonthlyAttendances as $myAtt)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="p-3 font-bold text-slate-900">
                                        {{ Carbon\Carbon::parse($myAtt->date)->format('D, M d, Y') }}
                                    </td>
                                    <td class="p-3 text-center font-bold text-slate-800">
                                        {{ $myAtt->clock_in ? Carbon\Carbon::parse($myAtt->clock_in)->format('h:i A') : '—' }}
                                    </td>
                                    <td class="p-3 text-center font-bold text-slate-800">
                                        {{ $myAtt->clock_out ? Carbon\Carbon::parse($myAtt->clock_out)->format('h:i A') : '—' }}
                                    </td>
                                    <td class="p-3 text-center text-slate-600">
                                        {{ $myAtt->regular_hours }}h
                                    </td>
                                    <td class="p-3 text-center font-bold text-indigo-600">
                                        {{ $myAtt->total_ot_hours > 0 ? '+'.$myAtt->total_ot_hours.'h' : '0' }}
                                    </td>
                                    <td class="p-3 text-center font-black text-slate-900">
                                        {{ $myAtt->total_hours }}h
                                    </td>
                                    <td class="p-3 text-right">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $myAtt->status_badge_class }}">
                                            ● {{ $myAtt->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-400 text-xs">
                                        No attendance records logged for this month yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 4: REGULARIZATION REQUESTS -->
    @if($tab === 'adjustments')
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-black text-slate-800">Attendance Regularization & Missed Punch Requests</h3>
                    <p class="text-xs text-slate-400">Employees can request corrections for missed clock-ins or clock-outs.</p>
                </div>
                <button @click="adjustmentModalOpen = true" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                    <i class="ph ph-plus text-sm"></i>
                    <span>New Request</span>
                </button>
            </div>

            <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
                <table class="w-full text-left text-xs font-semibold text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 uppercase text-[10px] font-extrabold border-b border-slate-200">
                        <tr>
                            <th class="p-3">Employee</th>
                            <th class="p-3">Date</th>
                            <th class="p-3 text-center">Proposed In</th>
                            <th class="p-3 text-center">Proposed Out</th>
                            <th class="p-3">Reason</th>
                            <th class="p-3 text-center">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($adjustments as $adj)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">{{ $adj->employee->user->name ?? 'Employee' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $adj->employee->employee_id_number ?? '' }} &bull; {{ $adj->employee->department->name ?? 'Dept' }}</div>
                                </td>
                                <td class="p-3 font-bold text-slate-800">
                                    {{ Carbon\Carbon::parse($adj->date)->format('M d, Y') }}
                                </td>
                                <td class="p-3 text-center font-mono font-bold text-slate-800">
                                    {{ $adj->clock_in ?? '—' }}
                                </td>
                                <td class="p-3 text-center font-mono font-bold text-slate-800">
                                    {{ $adj->clock_out ?? '—' }}
                                </td>
                                <td class="p-3 max-w-[200px] truncate text-slate-600" title="{{ $adj->reason }}">
                                    {{ $adj->reason }}
                                </td>
                                <td class="p-3 text-center">
                                    @if($adj->status === 'Pending')
                                        <span class="bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                            ● Pending
                                        </span>
                                    @elseif($adj->status === 'Approved')
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                            ● Approved
                                        </span>
                                    @else
                                        <span class="bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                            ● Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    @if($adj->status === 'Pending' && in_array($role, ['Super Admin', 'HR Admin', 'HR Manager', 'Department Head', 'Line Manager']))
                                        <button @click="openReviewModal({{ json_encode($adj) }})" class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1 rounded-lg text-xs font-bold transition-all">
                                            Review
                                        </button>
                                    @elseif($adj->reviewed_at)
                                        <div class="text-[10px] text-slate-400">
                                            By {{ $adj->reviewer->name ?? 'Admin' }} on {{ $adj->reviewed_at->format('M d') }}
                                        </div>
                                    @else
                                        <span class="text-slate-300 text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400 text-xs">
                                    No regularization requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 5: BIOMETRIC / BULK IMPORT -->
    @if($tab === 'import')
        <div class="space-y-6 max-w-4xl">
            <!-- Header card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <i class="ph ph-fingerprint text-blue-600 text-xl"></i>
                        <span>Biometric Device & Machine Log CSV Import</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Upload and auto-sync attendance records directly from timeclock machines, biometric scanners, and turnstiles.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('attendance.sample_csv', ['format' => 'summary']) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition-all shadow-2xs">
                        <i class="ph ph-download-simple text-sm text-blue-600"></i>
                        <span>Summary Template</span>
                    </a>
                    <a href="{{ route('attendance.sample_csv', ['format' => 'raw']) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 flex items-center gap-1.5 transition-all shadow-2xs">
                        <i class="ph ph-download-simple text-sm text-indigo-600"></i>
                        <span>Raw Punches Template</span>
                    </a>
                </div>
            </div>

            <!-- Upload Area & Supported Formats -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Upload Box (Left) -->
                <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Select & Upload CSV File</h4>
                    
                    <form action="{{ route('attendance.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="border-2 border-dashed border-blue-200 hover:border-blue-500 rounded-2xl p-8 text-center bg-blue-50/20 transition-all">
                            <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mx-auto mb-3">
                                <i class="ph ph-file-csv text-2xl"></i>
                            </div>
                            <div class="text-xs font-black text-slate-800">Select Machine CSV Log File</div>
                            <div class="text-[11px] text-slate-400 mt-1">Supports standard CSV exports (.csv, .txt) with Daily Logs or Raw Timestamps</div>
                            
                            <input type="file" name="csv_file" required accept=".csv,.txt" class="mt-4 text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs text-slate-600 space-y-1.5">
                            <div class="flex items-center gap-1.5 font-bold text-slate-800">
                                <i class="ph ph-sparkle text-amber-500"></i>
                                <span>Automatic Intelligence Engine:</span>
                            </div>
                            <ul class="list-disc list-inside text-[11px] text-slate-500 space-y-0.5 ml-1">
                                <li>Auto-detects employee via Employee ID Number, EPF No, or System ID.</li>
                                <li>Automatically calculates working hours, grace period late arrivals, and half days.</li>
                                <li>Auto-calculates early/late overtime and syncs with the Payroll Overtime module.</li>
                            </ul>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-2.5 rounded-xl text-xs font-black shadow-md shadow-blue-500/20 transition-all flex items-center gap-2">
                                <i class="ph ph-upload-simple text-base"></i>
                                <span>Import & Sync Attendance</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Format Guidance (Right) -->
                <div class="lg:col-span-5 space-y-4">
                    <!-- Format 1 Card -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-800">Format A: Daily Summary Log</span>
                            <span class="text-[10px] bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded-full border border-emerald-200">Recommended</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Each row specifies one employee's shift date, clock-in, and clock-out.</p>
                        <div class="font-mono text-[10px] bg-slate-50 p-2.5 rounded-xl border border-slate-200/80 text-slate-700 overflow-x-auto">
                            Employee_ID,Date,Clock_In,Clock_Out<br>
                            EMP-001,2026-09-10,08:52,17:08<br>
                            EMP-002,2026-09-10,09:25,17:15
                        </div>
                    </div>

                    <!-- Format 2 Card -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-800">Format B: Raw Device Punches</span>
                            <span class="text-[10px] bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded-full border border-indigo-200">Biometric Dumps</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Continuous punch logs from turnstiles/scanners. Earliest punch = In, latest = Out.</p>
                        <div class="font-mono text-[10px] bg-slate-50 p-2.5 rounded-xl border border-slate-200/80 text-slate-700 overflow-x-auto">
                            Employee_ID,Timestamp,Punch_Type<br>
                            EMP-001,2026-09-10 08:52:00,In<br>
                            EMP-001,2026-09-10 17:08:00,Out
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 1: MANUAL ATTENDANCE RECORD (CREATE / EDIT) -->
    <div x-show="manualModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="manualModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-black text-slate-800" x-text="manualEditMode ? 'Edit Attendance Record' : 'Log Manual Attendance'"></h3>
                <button @click="manualModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-base"></i></button>
            </div>

            <form action="{{ route('attendance.manual_store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Employee</label>
                    <select name="employee_id" x-model="manualForm.employee_id" required class="w-full border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2.5">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->user->name ?? 'Employee' }} ({{ $e->employee_id_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Date</label>
                    <input type="date" name="date" x-model="manualForm.date" required class="w-full border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2.5">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Clock In (Time)</label>
                        <input type="time" name="clock_in" x-model="manualForm.clock_in" required class="w-full border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Clock Out (Time)</label>
                        <input type="time" name="clock_out" x-model="manualForm.clock_out" required class="w-full border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Notes / Remarks</label>
                    <textarea name="notes" x-model="manualForm.notes" rows="2" placeholder="e.g. Field visit, Client meeting" class="w-full border-slate-200 rounded-xl text-xs bg-slate-50 p-2.5"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="manualModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-sm">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: REQUEST REGULARIZATION -->
    <div x-show="adjustmentModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="adjustmentModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-black text-slate-800">Submit Attendance Regularization Request</h3>
                <button @click="adjustmentModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-base"></i></button>
            </div>

            <form action="{{ route('attendance.adjustments.store') }}" method="POST" class="space-y-3.5">
                @csrf
                @if(in_array($role, ['Super Admin', 'HR Admin', 'HR Manager']))
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Employee</label>
                        <select name="employee_id" class="w-full border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2.5">
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->user->name ?? 'Employee' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Date of Missed Punch</label>
                    <input type="date" name="date" x-model="adjForm.date" required class="w-full border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2.5">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Actual Clock In</label>
                        <input type="time" name="clock_in" x-model="adjForm.clock_in" required class="w-full border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Actual Clock Out</label>
                        <input type="time" name="clock_out" x-model="adjForm.clock_out" required class="w-full border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Missing Punch</label>
                    <textarea name="reason" x-model="adjForm.reason" rows="3" required placeholder="e.g. Biometric scanner offline, worked at client site, forgot punch card..." class="w-full border-slate-200 rounded-xl text-xs bg-slate-50 p-2.5"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="adjustmentModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: REVIEW REGULARIZATION (APPROVE / REJECT) -->
    <div x-show="reviewModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="reviewModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-black text-slate-800">Review Regularization Request</h3>
                <button @click="reviewModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-base"></i></button>
            </div>

            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 text-xs space-y-1.5">
                <div>Employee: <strong class="text-slate-800" x-text="reviewForm.emp_name"></strong></div>
                <div>Date: <strong class="text-slate-800" x-text="reviewForm.date"></strong></div>
                <div>Proposed Timings: <strong class="text-slate-800" x-text="reviewForm.clock_in + ' – ' + reviewForm.clock_out"></strong></div>
                <div class="pt-1 text-slate-600 italic" x-text="'Reason: ' + reviewForm.reason"></div>
            </div>

            <form :action="'/attendance/adjustments/' + reviewForm.id + '/review'" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Decision</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-emerald-50/50" :class="reviewForm.action === 'Approved' ? 'border-emerald-500 bg-emerald-50' : ''">
                            <input type="radio" name="action" value="Approved" x-model="reviewForm.action" class="text-emerald-600">
                            <span class="text-xs font-bold text-emerald-700">Approve</span>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-rose-50/50" :class="reviewForm.action === 'Rejected' ? 'border-rose-500 bg-rose-50' : ''">
                            <input type="radio" name="action" value="Rejected" x-model="reviewForm.action" class="text-rose-600">
                            <span class="text-xs font-bold text-rose-700">Reject</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Review Remarks</label>
                    <textarea name="review_remarks" x-model="reviewForm.review_remarks" rows="2" placeholder="Optional notes regarding approval/rejection" class="w-full border-slate-200 rounded-xl text-xs bg-slate-50 p-2.5"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="reviewModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-sm">Confirm Decision</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: UPLOAD MACHINE CSV -->
    <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="importModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="ph ph-file-csv text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800">Upload Machine Attendance CSV</h3>
                        <p class="text-[11px] text-slate-400">Import punch logs from biometric devices or timeclock CSVs</p>
                    </div>
                </div>
                <button @click="importModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-base"></i></button>
            </div>

            <div class="flex items-center justify-between bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                <span class="text-xs font-semibold text-slate-600">Need a sample file?</span>
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('attendance.sample_csv', ['format' => 'summary']) }}" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-blue-600 border border-slate-200 shadow-2xs">
                        Daily Summary .CSV
                    </a>
                    <a href="{{ route('attendance.sample_csv', ['format' => 'raw']) }}" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-indigo-600 border border-slate-200 shadow-2xs">
                        Raw Punches .CSV
                    </a>
                </div>
            </div>

            <form action="{{ route('attendance.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-blue-200 hover:border-blue-500 rounded-2xl p-6 text-center bg-blue-50/20 transition-all">
                    <i class="ph ph-cloud-arrow-up text-3xl text-blue-600 mb-2"></i>
                    <div class="text-xs font-bold text-slate-800">Choose CSV Log File</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Supports .csv or .txt files up to 10MB</div>
                    <input type="file" name="csv_file" required accept=".csv,.txt" class="mt-3 text-xs text-slate-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                </div>

                <div class="text-[11px] text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200/80 space-y-1">
                    <div class="font-bold text-slate-700">Supported CSV Formats:</div>
                    <p>• <strong>Format A (Summary):</strong> <span class="font-mono text-slate-600">Employee_ID, Date, Clock_In, Clock_Out</span></p>
                    <p>• <strong>Format B (Raw Dumps):</strong> <span class="font-mono text-slate-600">Employee_ID, Timestamp (YYYY-MM-DD HH:MM:SS)</span></p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" @click="importModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-5 py-2.5 rounded-xl text-xs font-black shadow-md shadow-blue-500/20">
                        Upload & Sync Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
