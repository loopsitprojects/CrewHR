@extends('layouts.app', ['title' => 'LOOPS HR - Leave Analytics', 'breadcrumb' => 'Leave Analytics'])

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <i class="ph ph-chart-line-up text-2xl text-blue-600"></i>
                <h1 class="text-xl font-black text-gray-900">HR & Leave Utilization Analytics</h1>
            </div>
            <p class="text-xs font-semibold text-gray-500 mt-1">Real-time employee leave metrics, absenteeism trends, and department rosters with dedicated department pages.</p>
        </div>

        <!-- Controls -->
        <div class="flex items-center gap-3">
            <button class="bg-gray-100 border border-gray-200 text-gray-700 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                <i class="ph ph-calendar"></i>
                <span>Jul 2026 - Dec 2026</span>
            </button>

            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all">
                <i class="ph ph-download-simple text-base"></i>
                <span>Export Summary CSV</span>
            </button>
        </div>
    </div>

    <!-- 4 Top Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm space-y-2">
            <span class="text-xs font-bold text-gray-400">Total Workforce</span>
            <div class="text-2xl font-black text-gray-900">{{ $employeesCount }} <span class="text-base font-bold">Employees</span></div>
            <div class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                <i class="ph ph-trend-up"></i> +4 this month
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm space-y-2">
            <span class="text-xs font-bold text-gray-400">Currently On Leave</span>
            <div class="text-2xl font-black text-blue-600">{{ $onLeaveCount }} <span class="text-base font-bold text-gray-900">Staff</span></div>
            <div class="text-xs font-semibold text-gray-400">
                6.6% absenteeism rate
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm space-y-2">
            <span class="text-xs font-bold text-gray-400">Avg Leave Days / Emp</span>
            <div class="text-2xl font-black text-gray-900">11.4 <span class="text-base font-bold">Days</span></div>
            <div class="text-xs font-bold text-emerald-600">
                Within planned quota
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm space-y-2">
            <span class="text-xs font-bold text-gray-400">Appraisal Completion</span>
            <div class="text-2xl font-black text-emerald-600">92%</div>
            <div class="text-xs font-bold text-purple-600">
                Q3 Cycle active
            </div>
        </div>
    </div>

    <!-- Main Grid Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Enterprise Departments Directory -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2">
                    <i class="ph ph-buildings text-xl text-blue-600"></i>
                    <h2 class="text-base font-black text-gray-900">Enterprise Departments Directory</h2>
                </div>
                <span class="text-xs font-semibold text-gray-400">Click any department to open its dedicated page</span>
            </div>

            <!-- Department Cards List -->
            <div class="space-y-4">
                @foreach($departments as $dept)
                    <div class="border border-gray-200 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:border-blue-300 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-black text-xs flex items-center justify-center shadow-sm">
                                {{ $dept->code ?? 'DEPT' }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-gray-900">{{ $dept->name }} Department</h3>
                                    <span class="bg-blue-50 text-blue-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                        {{ $dept->name === 'IT' ? '2 Members' : '1 Member' }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 font-medium mt-0.5">
                                    HOD: <span class="font-bold text-gray-700">{{ $dept->hod_name ?? 'Super Admin' }}</span> • <span class="text-emerald-600 font-bold">0 currently on leave</span>
                                </p>
                            </div>
                        </div>

                        <!-- Quota Progress & Action -->
                        <div class="flex items-center gap-6">
                            <div class="text-right">
                                <span class="text-xs font-black text-blue-600">100% Quota Used</span>
                                <div class="w-32 h-1.5 bg-blue-600 rounded-full mt-1"></div>
                            </div>

                            <a href="{{ route('employee.index') }}" class="bg-gray-50 border border-gray-200 hover:bg-blue-50 hover:text-blue-600 text-gray-700 px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 transition-all">
                                <span>Open Page</span>
                                <i class="ph ph-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Right 1 Col: Sri Lanka Official Holidays Widget -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-4">
                <i class="ph ph-calendar-check text-xl text-blue-600"></i>
                <h3 class="text-base font-black text-gray-900">Sri Lanka Official Holidays</h3>
            </div>

            <div class="space-y-3">
                @foreach($holidays as $h)
                    <div class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl space-y-1">
                        <div class="text-xs font-bold text-blue-600">{{ \Carbon\Carbon::parse($h->date)->format('M d, Y') }}</div>
                        <div class="text-xs font-extrabold text-gray-900">{{ $h->title }}</div>
                        <div class="text-[10px] font-bold text-gray-400">{{ $h->category ?? 'Public & Bank' }}</div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection
