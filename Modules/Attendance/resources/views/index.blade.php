@extends('layouts.app', ['title' => 'LOOPS HR - Attendance & Time Tracking', 'breadcrumb' => 'Attendance'])

@section('content')
<div class="space-y-6" x-data="{ clockedIn: {{ session('clocked_in', false) ? 'true' : 'false' }} }">

    <!-- Header Banner & Clock-In Widget -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <i class="ph ph-clock-user text-2xl text-blue-600"></i>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Attendance & Time Tracking</h1>
            </div>
            <p class="text-xs font-semibold text-slate-500 mt-1">Real-time daily clock-in/out log, attendance records, and timesheet summaries.</p>
        </div>

        <form action="{{ route('attendance.clock_toggle') }}" method="POST">
            @csrf
            <button type="submit" class="px-6 py-3 rounded-xl text-xs font-bold flex items-center gap-2.5 shadow-lg transition-all"
                :class="clockedIn ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-rose-500/25' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/25'">
                <i class="ph ph-fingerprint text-xl"></i>
                <span x-text="clockedIn ? 'Clock Out Now (08:30 hrs)' : 'Clock In Now'">Clock In Now</span>
            </button>
        </form>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Total Employees Present</span>
            <div class="text-2xl font-black text-slate-900">102 / 106</div>
            <span class="text-[11px] font-bold text-emerald-600">96% On-Time Rate</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">On Approved Leave</span>
            <div class="text-2xl font-black text-blue-600">4 Staff</div>
            <span class="text-[11px] font-bold text-slate-400">Casual & Medical</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Late Arrivals Today</span>
            <div class="text-2xl font-black text-amber-600">2 Staff</div>
            <span class="text-[11px] font-bold text-amber-600">Grace period applied</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">Avg Work Hours / Day</span>
            <div class="text-2xl font-black text-slate-900">8.4 Hours</div>
            <span class="text-[11px] font-bold text-emerald-600">Standard 8.0h Target</span>
        </div>
    </div>

    <!-- Attendance Roster Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Today's Team Attendance Roster</h2>
            <div class="flex items-center gap-2">
                <input type="date" value="{{ date('Y-m-d') }}" class="border-slate-200 rounded-xl text-xs font-bold bg-slate-50 p-2">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-semibold text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Employee</th>
                        <th class="p-3">Department</th>
                        <th class="p-3">Clock In</th>
                        <th class="p-3">Clock Out</th>
                        <th class="p-3">Hours Worked</th>
                        <th class="p-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($employees as $emp)
                        <tr>
                            <td class="p-3">
                                <div class="flex items-center gap-2.5">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($emp->user->name ?? 'Employee') }}&background=0D8ABC&color=fff" class="w-7 h-7 rounded-full border border-blue-500" alt="Avatar">
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $emp->user->name ?? 'Employee' }}</div>
                                        <div class="text-[10px] text-slate-400 font-normal">{{ $emp->employee_id_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3"><span class="bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded">{{ $emp->department->name ?? 'IT' }}</span></td>
                            <td class="p-3 font-bold text-slate-900">08:30 AM</td>
                            <td class="p-3 font-bold text-slate-900">05:30 PM</td>
                            <td class="p-3 font-extrabold text-blue-600">8.5 hrs</td>
                            <td class="p-3 text-right">
                                <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-[10px] font-black px-2.5 py-0.5 rounded-full">
                                    ● Present
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
