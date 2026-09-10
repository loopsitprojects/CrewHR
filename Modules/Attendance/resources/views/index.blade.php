@extends('layouts.app', ['title' => 'LOOPS HR - Attendance & Time Tracking', 'breadcrumb' => 'Attendance'])

@section('content')
<div class="space-y-6" x-data="{ 
    clockedIn: {{ $isClockedIn ? 'true' : 'false' }} 
}">

    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-emerald-600 text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-warning-circle text-rose-600 text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-extrabold shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ph ph-info text-blue-600 text-lg"></i>
                <span>{{ session('info') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700"><i class="ph ph-x text-base"></i></button>
        </div>
    @endif

    <!-- Header Banner & Clock-In Widget -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                    <i class="ph ph-clock-user text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Attendance & Time Tracking</h1>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                        Shift Hours: <strong class="text-slate-700 dark:text-slate-300">{{ date('h:i A', strtotime($shiftSettings['shift_start'])) }} &ndash; {{ date('h:i A', strtotime($shiftSettings['shift_end'])) }}</strong> &bull; 
                        Overtime Multiplier: <strong class="text-amber-600 dark:text-amber-400">{{ $shiftSettings['ot_multiplier'] }}x</strong> (Calculated hourly for early/late work)
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($userAttendanceToday && $userAttendanceToday->clock_in)
                <div class="text-right hidden sm:block">
                    <span class="text-[10px] font-bold text-slate-400 block uppercase">Clocked In At</span>
                    <span class="text-xs font-black text-slate-800 dark:text-slate-200">{{ $userAttendanceToday->clock_in->format('h:i A') }}</span>
                </div>
            @endif

            <form action="{{ route('attendance.clock_toggle') }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-3 rounded-xl text-xs font-bold flex items-center gap-2.5 shadow-lg transition-all cursor-pointer"
                    :class="clockedIn ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-rose-500/25' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/25'">
                    <i class="ph ph-fingerprint text-xl"></i>
                    <span>{{ $isClockedIn ? 'Clock Out Now' : 'Clock In Now' }}</span>
                </button>
            </form>
        </div>
    </div>



    <!-- Attendance Roster Table -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 gap-3">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">Daily Team Attendance & Overtime Roster</h2>
                <p class="text-[11px] text-slate-400">Automatic OT calculation: Hours worked before {{ date('h:i A', strtotime($shiftSettings['shift_start'])) }} or after {{ date('h:i A', strtotime($shiftSettings['shift_end'])) }}</p>
            </div>
            
            <form method="GET" action="{{ route('attendance.index') }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2">
                @if($deptFilter)
                    <input type="hidden" name="department_id" value="{{ $deptFilter }}">
                @endif
            </form>
        </div>

        <div class="overflow-x-auto border border-slate-200/80 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px]">
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
                        <th class="p-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($employees as $emp)
                        @php
                            $att = $attendances->get($emp->id);
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-[#1e2d4d]/60 transition-colors">
                            <td class="p-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-bold flex items-center justify-center text-xs">
                                        {{ substr($emp->user->name ?? 'E', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $emp->user->name ?? 'Employee' }}</div>
                                        <div class="text-[10px] text-slate-400 font-normal">{{ $emp->employee_id_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                <span class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold px-2 py-0.5 rounded">
                                    {{ $emp->department->name ?? 'IT' }}
                                </span>
                            </td>
                            <td class="p-3 text-center font-bold text-slate-900 dark:text-white">
                                {{ $att && $att->clock_in ? $att->clock_in->format('h:i A') : '—' }}
                            </td>
                            <td class="p-3 text-center font-bold text-slate-900 dark:text-white">
                                {{ $att && $att->clock_out ? $att->clock_out->format('h:i A') : ($att && $att->clock_in ? 'Working...' : '—') }}
                            </td>
                            <td class="p-3 text-center font-black text-slate-900 dark:text-white">
                                {{ $att ? $att->total_hours . ' hrs' : '0.0 hrs' }}
                            </td>
                            <td class="p-3 text-center text-slate-600 dark:text-slate-300 font-semibold">
                                {{ $att ? $att->regular_hours . ' hrs' : '0.0 hrs' }}
                            </td>
                            <td class="p-3 text-center">
                                @if($att && $att->early_ot_hours > 0)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        +{{ $att->early_ot_hours }}h
                                    </span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">0</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if($att && $att->late_ot_hours > 0)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        +{{ $att->late_ot_hours }}h
                                    </span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">0</span>
                                @endif
                            </td>
                            <td class="p-3 text-center font-black text-amber-600 dark:text-amber-400">
                                {{ $att && $att->total_ot_hours > 0 ? $att->total_ot_hours . ' hrs' : '0.0' }}
                            </td>
                            <td class="p-3 text-right">
                                @if($att && $att->clock_in)
                                    @if($att->status === 'Late')
                                        <span class="bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800 text-[10px] font-black px-2.5 py-0.5 rounded-full">
                                            ● Late
                                        </span>
                                    @else
                                        <span class="bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800 text-[10px] font-black px-2.5 py-0.5 rounded-full">
                                            ● Present
                                        </span>
                                    @endif
                                @else
                                    <span class="bg-slate-100 dark:bg-slate-800 text-slate-400 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                        Not Checked In
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-6 text-center text-slate-400 text-xs">
                                No attendance records found for this date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

