@extends('layouts.app', ['title' => 'Loops HR - Leave Reports & Analytics', 'breadcrumb' => 'Reports & Analytics'])

@section('content')
<div class="space-y-6 pb-16" x-data="{
    activeTab: '{{ $activeTab }}',
    deptFilter: '{{ $selectedDeptName ?? 'all' }}',
    patternFilter: 'all',
    searchQuery: '',
    min20DaysOnly: false,
    sortCol: 'late_pct',
    sortAsc: false,
    showNotes: false,
    
    // 50 staff records
    staffList: {{ Js::from($allStaffMeasures) }},
    timelineList: {{ Js::from($timelineStaff) }},

    get filteredStaff() {
        return this.staffList.filter(s => {
            const matchesDept = this.deptFilter === 'all' || s.dept.toLowerCase() === this.deptFilter.toLowerCase();
            const matchesPattern = this.patternFilter === 'all' || s.pattern.toLowerCase() === this.patternFilter.toLowerCase();
            const matchesSearch = !this.searchQuery || 
                s.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                s.role.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                s.dept.toLowerCase().includes(this.searchQuery.toLowerCase());
            const matchesDays = !this.min20DaysOnly || (s.recorded_days >= 20);
            return matchesDept && matchesPattern && matchesSearch && matchesDays;
        }).sort((a, b) => {
            let valA = a[this.sortCol];
            let valB = b[this.sortCol];
            if (typeof valA === 'string') {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }
            if (valA < valB) return this.sortAsc ? -1 : 1;
            if (valA > valB) return this.sortAsc ? 1 : -1;
            return 0;
        });
    },

    sortBy(column) {
        if (this.sortCol === column) {
            this.sortAsc = !this.sortAsc;
        } else {
            this.sortCol = column;
            this.sortAsc = false;
        }
    }
}">

    <!-- ==================== 1. TOP NAVIGATION & ACTIONS ==================== -->
    <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] flex flex-wrap items-center justify-between gap-4 transition-all">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
            <i class="ph ph-calendar text-sm text-emerald-600 dark:text-emerald-400"></i>
            <span>Reporting Period: <strong class="text-slate-800 dark:text-slate-200">1 Apr – 31 Aug 2026</strong></span>
        </div>

        <!-- Mode Switcher Tabs & Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 text-xs font-bold">
                <button type="button" 
                        @click="activeTab = 'executive'" 
                        :class="activeTab === 'executive' ? 'bg-white dark:bg-[#111927] text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="ph ph-presentation-chart text-sm"></i>
                    <span>Executive Analysis</span>
                </button>
                <button type="button" 
                        @click="activeTab = 'department'" 
                        :class="activeTab === 'department' ? 'bg-white dark:bg-[#111927] text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="ph ph-buildings text-sm"></i>
                    <span>Department Quotas</span>
                </button>
                <button type="button" 
                        @click="activeTab = 'individual'" 
                        :class="activeTab === 'individual' ? 'bg-white dark:bg-[#111927] text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="ph ph-user text-sm"></i>
                    <span>Individual Records</span>
                </button>
            </div>

            <!-- Export Buttons -->
            <div class="flex items-center gap-2">
                <a href="{{ route('analytics.export', ['type' => 'executive', 'format' => 'pdf']) }}" 
                   target="_blank"
                   title="Download Executive 12-Page PDF Report"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 shadow-md shadow-rose-500/20 transition-all transform hover:-translate-y-0.5">
                    <i class="ph ph-file-pdf text-base"></i>
                    <span>Executive PDF</span>
                </a>
                <a href="{{ route('analytics.export', ['type' => 'executive', 'format' => 'csv']) }}" 
                   title="Export CSV Dataset"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition-all transform hover:-translate-y-0.5">
                    <i class="ph ph-file-csv text-emerald-600 text-base"></i>
                    <span>CSV</span>
                </a>
                <button type="button" 
                        onclick="window.print()" 
                        class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer"
                        title="Print View">
                    <i class="ph ph-printer text-base"></i>
                </button>
            </div>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- TAB 1: EXECUTIVE ANALYSIS REPORT -->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'executive'" class="space-y-8" x-cloak>

        <!-- 2. HERO SUMMARY & RULES BANNER -->
        <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-9 shadow-sm relative overflow-hidden">
            <!-- Subtle accent glow -->
            <div class="absolute -right-16 -top-16 w-72 h-72 bg-gradient-to-br from-blue-500/10 to-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="max-w-3xl space-y-2">
                <div class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
                    <span>Executive Workforce Analysis</span>
                    <span>&bull;</span>
                    <span class="text-blue-600 dark:text-blue-400">Attendance & Leave Benchmarks</span>
                </div>

                <h2 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    Executive Attendance & Workforce Analytics
                </h2>

                <p class="text-sm text-slate-600 dark:text-slate-400 font-medium leading-relaxed">
                    {{ $executiveSummary['narrative'] }}
                </p>
            </div>

            <!-- Shift Parameters Rule Bar -->
            <div class="mt-8 grid grid-cols-2 sm:grid-cols-5 gap-3 p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                <div class="border-r border-slate-200/80 dark:border-slate-800/80 pr-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Shift Start</span>
                    <span class="text-xl md:text-2xl font-black font-mono text-slate-900 dark:text-white block mt-0.5">{{ $shiftConfig['shift_start'] }}</span>
                </div>
                <div class="border-r border-slate-200/80 dark:border-slate-800/80 pr-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-rose-500 block">Grace Until</span>
                    <span class="text-xl md:text-2xl font-black font-mono text-rose-600 dark:text-rose-400 block mt-0.5">{{ $shiftConfig['grace_until'] }}</span>
                </div>
                <div class="border-r border-slate-200/80 dark:border-slate-800/80 pr-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Shift End</span>
                    <span class="text-xl md:text-2xl font-black font-mono text-slate-900 dark:text-white block mt-0.5">{{ $shiftConfig['shift_end'] }}</span>
                </div>
                <div class="border-r border-slate-200/80 dark:border-slate-800/80 pr-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Active Staff</span>
                    <span class="text-xl md:text-2xl font-black text-slate-900 dark:text-white block mt-0.5">{{ $shiftConfig['staff_count'] }}</span>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Working Days</span>
                    <span class="text-xl md:text-2xl font-black text-slate-900 dark:text-white block mt-0.5">{{ number_format($shiftConfig['working_days']) }}</span>
                </div>
            </div>

            <!-- "The Office at a Glance" — 6 Key Metric Cards -->
            <div class="mt-8 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">The office at a glance</h3>
                    <span class="text-xs font-semibold text-slate-400">Across recorded working days</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- KPI 1: Avg In -->
                    <div class="group p-5 rounded-2xl bg-slate-50/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-800 hover:border-blue-500/50 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Average Clock-In</span>
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        </div>
                        <div class="text-3xl font-black font-mono text-slate-900 dark:text-white mt-1.5">
                            {{ $executiveSummary['kpis']['avg_clock_in']['value'] }}
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">
                            {{ $executiveSummary['kpis']['avg_clock_in']['subtext'] }}
                        </div>
                    </div>

                    <!-- KPI 2: Avg Out -->
                    <div class="group p-5 rounded-2xl bg-slate-50/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-800 hover:border-amber-500/50 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Average Clock-Out</span>
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        </div>
                        <div class="text-3xl font-black font-mono text-amber-600 dark:text-amber-400 mt-1.5">
                            {{ $executiveSummary['kpis']['avg_clock_out']['value'] }}
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">
                            {{ $executiveSummary['kpis']['avg_clock_out']['subtext'] }}
                        </div>
                    </div>

                    <!-- KPI 3: Day Length -->
                    <div class="group p-5 rounded-2xl bg-slate-50/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-800 hover:border-indigo-500/50 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Average Day Length</span>
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        </div>
                        <div class="text-3xl font-black font-mono text-slate-900 dark:text-white mt-1.5">
                            {{ $executiveSummary['kpis']['avg_day_length']['value'] }}
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">
                            {{ $executiveSummary['kpis']['avg_day_length']['subtext'] }}
                        </div>
                    </div>

                    <!-- KPI 4: Late Arrivals -->
                    <div class="group p-5 rounded-2xl bg-slate-50/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-800 hover:border-rose-500/50 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-rose-500 font-bold">Days Arriving Late</span>
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        </div>
                        <div class="text-3xl font-black text-rose-600 dark:text-rose-400 mt-1.5">
                            {{ $executiveSummary['kpis']['days_arriving_late']['value'] }}
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">
                            {{ $executiveSummary['kpis']['days_arriving_late']['subtext'] }}
                        </div>
                    </div>

                    <!-- KPI 5: Leaving Early -->
                    <div class="group p-5 rounded-2xl bg-slate-50/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-800 hover:border-emerald-500/50 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 font-bold">Days Leaving Early</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5">
                            {{ $executiveSummary['kpis']['days_leaving_early']['value'] }}
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">
                            {{ $executiveSummary['kpis']['days_leaving_early']['subtext'] }}
                        </div>
                    </div>

                    <!-- KPI 6: Absence Rate -->
                    <div class="group p-5 rounded-2xl bg-slate-50/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-800 hover:border-cyan-500/50 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-cyan-600 font-bold">Absence Rate</span>
                            <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                        </div>
                        <div class="text-3xl font-black text-cyan-600 dark:text-cyan-400 mt-1.5">
                            {{ $executiveSummary['kpis']['absence_rate']['value'] }}
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">
                            {{ $executiveSummary['kpis']['absence_rate']['subtext'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- 3. "WHAT THE DATA SAYS" — 5 INSIGHT CARDS -->
        <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-9 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Executive Behavioral Insights</h3>
                <span class="text-xs font-bold text-slate-400">Core Patterns</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(array_slice($keyPatterns, 0, 4) as $pat)
                    @php
                        $accentColor = match($pat['number']) {
                            '01' => 'border-rose-500 bg-rose-500/5',
                            '02' => 'border-amber-500 bg-amber-500/5',
                            '03' => 'border-red-500 bg-red-500/5',
                            '04' => 'border-indigo-500 bg-indigo-500/5',
                            default => 'border-blue-500 bg-blue-500/5',
                        };
                    @endphp
                    <div class="p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 border-l-4 {{ $accentColor }} transition-all hover:shadow-sm space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-black text-slate-400">{{ $pat['number'] }}</span>
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                {{ $pat['tag'] }}
                            </span>
                        </div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-white">{{ $pat['title'] }}</h4>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $pat['description'] }}</p>
                    </div>
                @endforeach
            </div>

            <!-- 5th Pattern Full Width -->
            @if(isset($keyPatterns[4]))
                <div class="p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 border-l-4 border-blue-500 bg-blue-500/5 transition-all hover:shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono font-black text-slate-400">{{ $keyPatterns[4]['number'] }}</span>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            {{ $keyPatterns[4]['tag'] }}
                        </span>
                    </div>
                    <h4 class="text-sm font-black text-slate-900 dark:text-white">{{ $keyPatterns[4]['title'] }}</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $keyPatterns[4]['description'] }}</p>
                </div>
            @endif
        </div>


        <!-- 4. "EVERYONE'S AVERAGE DAY" HORIZONTAL GANTT CHART -->
        <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-9 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Average Working Day by Employee</h3>
                    <div class="flex flex-wrap items-center gap-3 text-xs font-bold text-slate-500 dark:text-slate-400 mt-1">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-blue-600"></span>
                            <span>Inside 09:00–17:00</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-amber-500"></span>
                            <span>After 17:00</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-3 rounded bg-rose-600"></span>
                            <span>Past 09:30 Grace</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <select x-model="deptFilter" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:outline-none">
                        <option value="all">All Departments</option>
                        @foreach($allDepartments as $deptOpt)
                            <option value="{{ $deptOpt->name }}">{{ $deptOpt->name }}</option>
                        @endforeach
                    </select>

                    <div class="relative">
                        <input type="text" x-model="searchQuery" placeholder="Filter employee..." class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl pl-8 pr-3 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                        <i class="ph ph-magnifying-glass absolute left-2.5 top-2 text-slate-400 text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Timeline Scale Top Marks -->
            <div class="flex items-center justify-between text-[11px] font-mono font-bold text-slate-400 pl-48 md:pl-56 pr-12 pb-1 border-b border-slate-100 dark:border-slate-800/80">
                <span>08:00</span>
                <span class="text-rose-500 font-black">09:30 grace</span>
                <span>11:00</span>
                <span>13:00</span>
                <span>15:00</span>
                <span class="text-slate-900 dark:text-white font-black">17:00 shift end</span>
                <span>19:00</span>
                <span>21:00</span>
            </div>

            <!-- Server-Side Rendered Bars with Instant Alpine Filtering -->
            <div class="space-y-1 pt-1 max-h-[640px] overflow-y-auto pr-1">
                @foreach($timelineStaff as $person)
                    <div class="group flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 px-2.5 py-1.5 rounded-xl transition-all duration-200 text-xs"
                         x-show="(deptFilter === 'all' || '{{ strtolower($person['dept']) }}' === deptFilter.toLowerCase()) && 
                                 (!searchQuery || '{{ strtolower($person['name']) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($person['role']) }}'.includes(searchQuery.toLowerCase()))">
                        
                        <!-- Employee Label -->
                        <div class="w-44 md:w-52 shrink-0 truncate">
                            <span class="font-bold text-slate-900 dark:text-white block truncate">{{ $person['name'] }}</span>
                            <span class="text-[10px] text-slate-400 block truncate">{{ $person['dept'] }}</span>
                        </div>

                        <!-- Bar Track -->
                        <div class="flex-1 relative h-5 bg-slate-100 dark:bg-slate-800/80 rounded-md overflow-hidden">
                            <!-- 09:30 Grace Marker Line -->
                            <div class="absolute top-0 bottom-0 left-[11.5%] w-[1px] bg-rose-400/50 dark:bg-rose-500/40 z-10 pointer-events-none"></div>
                            <!-- 17:00 Shift End Line -->
                            <div class="absolute top-0 bottom-0 left-[69.2%] w-[1px] bg-slate-300 dark:bg-slate-600 z-10 pointer-events-none"></div>

                            <!-- Red Arrival Tick if past 09:30 -->
                            @if($person['is_past_grace'])
                                <div class="absolute top-0 bottom-0 w-1 bg-rose-600 z-20 shadow-sm" style="left: {{ $person['left_pct'] }}%;"></div>
                            @endif

                            <!-- Blue Segment: 09:00 - 17:00 -->
                            <div class="absolute top-0.5 bottom-0.5 rounded-l-sm bg-gradient-to-r from-blue-600 to-indigo-600 shadow-sm transition-all group-hover:brightness-110"
                                 style="left: {{ $person['left_pct'] }}%; width: {{ $person['blue_width_pct'] }}%;"></div>

                            <!-- Orange Segment: After 17:00 -->
                            @if($person['orange_width_pct'] > 0)
                                <div class="absolute top-0.5 bottom-0.5 rounded-r-sm bg-gradient-to-r from-amber-500 to-orange-500 shadow-sm transition-all group-hover:brightness-110"
                                     style="left: {{ $person['left_pct'] + $person['blue_width_pct'] }}%; width: {{ $person['orange_width_pct'] }}%;"></div>
                            @endif
                        </div>

                        <!-- Late % on right -->
                        <div class="w-12 text-right font-black font-mono {{ $person['late_pct'] >= 50 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-600 dark:text-slate-300' }}">
                            {{ $person['late_pct'] }}%
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Timeline Scale Bottom Marks -->
            <div class="flex items-center justify-between text-[11px] font-mono font-bold text-slate-400 pl-48 md:pl-56 pr-12 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                <span>08:00</span>
                <span class="text-rose-500 font-black">09:30 grace</span>
                <span>11:00</span>
                <span>13:00</span>
                <span>15:00</span>
                <span class="text-slate-900 dark:text-white font-black">17:00 shift end</span>
                <span>19:00</span>
                <span>21:00</span>
            </div>
        </div>


        <!-- 5. "THE TWO LEADERBOARDS" (SIDE BY SIDE) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Leaderboard: Latest Arrivers -->
            <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-8 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Latest arrivers (>09:30)</h3>
                        <p class="text-xs text-slate-400 font-semibold mt-0.5">Top staff with highest % of days arriving after 09:30</p>
                    </div>
                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300">Arrivals</span>
                </div>

                <div class="space-y-3">
                    @foreach($leaderboards['latest_arrivers'] as $idx => $arriver)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-mono font-bold flex items-center justify-center text-slate-500">{{ $idx + 1 }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $arriver['name'] }}</span>
                                    <span class="text-[11px] text-slate-400">avg in {{ $arriver['avg_in'] }}</span>
                                </div>
                                <span class="font-black font-mono text-blue-600 dark:text-blue-400">{{ $arriver['late_pct'] }}%</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-500 rounded-full transition-all duration-700" style="width: {{ $arriver['late_pct'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Leaderboard: Latest Leavers -->
            <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-8 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Latest leavers (Clock-Out)</h3>
                        <p class="text-xs text-slate-400 font-semibold mt-0.5">Staff with latest average departures past scheduled shift</p>
                    </div>
                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300">Departures</span>
                </div>

                <div class="space-y-3">
                    @foreach($leaderboards['latest_leavers'] as $idx => $leaver)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-mono font-bold flex items-center justify-center text-slate-500">{{ $idx + 1 }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $leaver['name'] }}</span>
                                    <span class="text-[11px] text-slate-400">past 18:30 on {{ $leaver['past_1830_pct'] }}% days</span>
                                </div>
                                <span class="font-black font-mono text-amber-600 dark:text-amber-400">{{ $leaver['avg_out'] }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-500 to-orange-500 rounded-full transition-all duration-700" style="width: {{ $leaver['past_1830_pct'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>


        <!-- 6. ARRIVAL HISTOGRAM & 4-QUADRANT MATRIX -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart A: Arrival Histogram -->
            <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-8 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Arrival Distribution (15-Min Intervals)</h3>
                        <p class="text-xs text-slate-400 font-semibold">Cliff at 09:30 marks office real start time</p>
                    </div>
                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400">09:30 Grace</span>
                </div>

                <div class="pt-6 pb-2">
                    <div class="h-44 flex items-end gap-1.5 border-b border-slate-200 dark:border-slate-700 relative">
                        <!-- Grace Cliff Line -->
                        <div class="absolute left-[52%] top-0 bottom-0 border-l-2 border-dashed border-rose-500 z-10 flex flex-col items-start pl-1">
                            <span class="text-[9px] font-black uppercase text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950 px-1 rounded border border-rose-200 dark:border-rose-800">
                                Grace line
                            </span>
                        </div>

                        @foreach($arrivalDistribution as $bucket)
                            @php
                                $heightPct = min(100, max(5, round(($bucket['count'] / 260) * 100)));
                            @endphp
                            <div class="flex-1 flex flex-col items-center justify-end h-full group relative" title="{{ $bucket['time'] }}: {{ $bucket['count'] }} arrivals">
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-8 bg-slate-900 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap z-20">
                                    {{ $bucket['time'] }} ({{ $bucket['count'] }} days)
                                </div>
                                <div class="w-full rounded-t-sm transition-all duration-300 {{ $bucket['is_late'] ? 'bg-gradient-to-t from-amber-500 to-orange-500' : 'bg-gradient-to-t from-blue-600 to-indigo-600' }}"
                                     style="height: {{ $heightPct }}%;"></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between text-[10px] font-mono font-bold text-slate-400 mt-2">
                        <span>07:00</span>
                        <span>08:00</span>
                        <span>09:00</span>
                        <span class="text-rose-600 font-black">09:30</span>
                        <span>10:00</span>
                        <span>11:00</span>
                        <span>12:00</span>
                        <span>13:00</span>
                    </div>

                    <div class="flex items-center justify-between text-xs font-bold pt-4 text-slate-600 dark:text-slate-300">
                        <span class="text-blue-600 dark:text-blue-400">&larr; 33% arrive by 09:00</span>
                        <span class="text-amber-600 dark:text-amber-400">36% arrive after 09:30 &rarr;</span>
                    </div>
                </div>
            </div>

            <!-- Chart B: 4-Quadrant Scatter Matrix -->
            <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-8 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Late in, Late out — or Just Late?</h3>
                        <p class="text-xs text-slate-400 font-semibold">Correlation between late arrival and late departure</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-500">r = 0.52</span>
                </div>

                <!-- Quadrant Container -->
                <div class="relative h-64 rounded-2xl border border-slate-200 dark:border-slate-800 p-2 overflow-hidden bg-slate-50/50 dark:bg-slate-900/50">
                    <!-- Quadrant Background Tints -->
                    <div class="absolute left-0 top-0 w-1/2 h-1/2 bg-blue-500/5 border-r border-b border-dashed border-slate-300 dark:border-slate-700"></div>
                    <div class="absolute right-0 top-0 w-1/2 h-1/2 bg-amber-500/5 border-b border-dashed border-slate-300 dark:border-slate-700"></div>
                    <div class="absolute left-0 bottom-0 w-1/2 h-1/2 bg-emerald-500/5 border-r border-dashed border-slate-300 dark:border-slate-700"></div>
                    <div class="absolute right-0 bottom-0 w-1/2 h-1/2 bg-rose-500/5"></div>

                    <!-- Labels -->
                    <span class="absolute top-2 left-3 text-[9px] font-black uppercase text-slate-400 pointer-events-none">On Time, Stays Late</span>
                    <span class="absolute top-2 right-3 text-[9px] font-black uppercase text-slate-400 pointer-events-none">Late In, Late Out</span>
                    <span class="absolute bottom-2 left-3 text-[9px] font-black uppercase text-slate-400 pointer-events-none">On The Clock</span>
                    <span class="absolute bottom-2 right-3 text-[9px] font-black uppercase text-rose-500 font-bold pointer-events-none">Late In, Out On Time</span>

                    <!-- Scatter Points -->
                    @foreach($scatterPlotData as $pt)
                        @php
                            $leftPct = max(5, min(93, $pt['x']));
                            $bottomPct = max(5, min(93, $pt['y']));
                        @endphp
                        <div class="group absolute w-3 h-3 -ml-1.5 -mb-1.5 rounded-full cursor-pointer transition-transform duration-200 hover:scale-175 z-10 ring-2 ring-white dark:ring-slate-900"
                             style="left: {{ $leftPct }}%; bottom: {{ $bottomPct }}%; background-color: {{ $pt['color'] }};">
                            
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute bottom-4 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-lg shadow-xl pointer-events-none whitespace-nowrap z-30">
                                <div>{{ $pt['name'] }}</div>
                                <div class="text-[9px] text-slate-300">{{ $pt['x'] }}% late in · {{ $pt['y'] }}% stays late</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 pt-1">
                    <span>0% Arrival after 09:30 &rarr;</span>
                    <span>100%</span>
                </div>
            </div>
        </div>


        <!-- 7. MONTHLY TRENDS, WEEKDAYS & LEAVE MIX -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Col 1: Month by Month & Weekday -->
            <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-7 shadow-sm space-y-4">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Monthly & Day Trends</h3>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Average clock movements over the period</p>
                </div>

                <div class="space-y-3">
                    <div class="p-3.5 rounded-2xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/50">
                        <span class="text-[10px] font-black uppercase text-blue-700 dark:text-blue-300 block">Avg Clock-In</span>
                        <div class="grid grid-cols-5 gap-1 text-center font-mono font-black text-xs text-blue-900 dark:text-blue-200 mt-1.5">
                            @foreach($trendsAndCuts['months']['labels'] as $idx => $m)
                                <div>
                                    <div class="text-[9px] text-blue-400 font-bold">{{ $m }}</div>
                                    <div>{{ $trendsAndCuts['months']['avg_in'][$idx] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-amber-50/50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50">
                        <span class="text-[10px] font-black uppercase text-amber-700 dark:text-amber-300 block">Avg Clock-Out</span>
                        <div class="grid grid-cols-5 gap-1 text-center font-mono font-black text-xs text-amber-900 dark:text-amber-200 mt-1.5">
                            @foreach($trendsAndCuts['months']['labels'] as $idx => $m)
                                <div>
                                    <div class="text-[9px] text-amber-400 font-bold">{{ $m }}</div>
                                    <div>{{ $trendsAndCuts['months']['avg_out'][$idx] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2">
                        <span class="text-[10px] font-black uppercase text-slate-400 block mb-1">By Weekday</span>
                        <div class="grid grid-cols-5 gap-1 text-center text-xs">
                            @foreach($trendsAndCuts['weekdays']['labels'] as $idx => $day)
                                <div class="p-1.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                    <span class="text-[10px] font-black text-slate-400 block">{{ $day }}</span>
                                    <span class="text-xs font-black text-blue-600 block mt-0.5">{{ $trendsAndCuts['weekdays']['late_in_pct'][$idx] }}%</span>
                                    <span class="text-[10px] font-bold text-amber-600 block">{{ $trendsAndCuts['weekdays']['past_1830_pct'][$idx] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Col 2: By Department -->
            <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-7 shadow-sm space-y-4">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Department Schedules</h3>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Average in/out schedule by department</p>
                </div>

                <div class="space-y-3 pt-1">
                    @foreach($trendsAndCuts['departments'] as $dept)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $dept['name'] }}</span>
                                <span class="font-black font-mono text-slate-600 dark:text-slate-400">{{ $dept['late_rate'] }}% Late</span>
                            </div>
                            <div class="h-2.5 w-full rounded-md bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
                                <div class="absolute top-0 bottom-0 bg-blue-600" style="left: {{ $dept['in_pct'] }}%; width: {{ $dept['blue_width'] }}%;"></div>
                                <div class="absolute top-0 bottom-0 bg-amber-500" style="left: {{ $dept['in_pct'] + $dept['blue_width'] }}%; width: {{ $dept['orange_width'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Col 3: Leave & Exception Mix -->
            <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-7 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Leave & Exception Mix</h3>
                        <p class="text-[11px] text-slate-400 font-semibold mt-0.5">374 tagged days across period</p>
                    </div>
                    <span class="text-xs font-black px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300">
                        {{ $leaveAndExceptionMix['total_tagged_days'] }} Days
                    </span>
                </div>

                <div class="space-y-2.5 pt-1">
                    @foreach($leaveAndExceptionMix['items'] as $item)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $item['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-slate-400">{{ $item['pct'] }}%</span>
                                    <span class="font-mono font-black text-slate-700 dark:text-slate-300">{{ $item['count'] }}</span>
                                </div>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $item['pct'] * 3.5 }}%; background-color: {{ $item['color'] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>


        <!-- 8. "EVERYONE, EVERY MEASURE" MASTER TABLE -->
        <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 md:p-9 shadow-sm space-y-5">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Workforce Attendance Matrix</h3>
                    <p class="text-xs text-slate-400 font-semibold mt-0.5">Click any header to sort</p>
                </div>

                <!-- Table Search & Filter Bar -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <select x-model="patternFilter" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:outline-none">
                        <option value="all">All Patterns</option>
                        <option value="Shifted schedule">Shifted schedule</option>
                        <option value="Punctuality risk">Punctuality risk</option>
                        <option value="Overextended">Overextended</option>
                        <option value="On the clock">On the clock</option>
                        <option value="Balanced">Balanced</option>
                    </select>

                    <label class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 cursor-pointer bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                        <input type="checkbox" x-model="min20DaysOnly" class="rounded text-blue-600 focus:ring-0">
                        <span>20+ Days</span>
                    </label>

                    <span class="text-xs font-black text-slate-400 px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800" x-text="filteredStaff.length + ' Staff'"></span>
                </div>
            </div>

            <!-- Master Table -->
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-2xl">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-900/90 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 sticky top-0">
                        <tr>
                            <th @click="sortBy('name')" class="p-3 cursor-pointer hover:text-blue-600 transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>Name</span>
                                    <span x-show="sortCol === 'name'" x-text="sortAsc ? '↑' : '↓'"></span>
                                </div>
                            </th>
                            <th @click="sortBy('dept')" class="p-3 cursor-pointer hover:text-blue-600 transition-colors">
                                <span>Dept</span>
                            </th>
                            <th @click="sortBy('avg_in')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>Avg In</span>
                            </th>
                            <th @click="sortBy('avg_out')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>Avg Out</span>
                            </th>
                            <th @click="sortBy('avg_hrs')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>Avg Hrs</span>
                            </th>
                            <th @click="sortBy('late_pct')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors text-rose-600">
                                <div class="flex items-center justify-center gap-1">
                                    <span>Late %</span>
                                    <span x-show="sortCol === 'late_pct'" x-text="sortAsc ? '↑' : '↓'"></span>
                                </div>
                            </th>
                            <th @click="sortBy('avg_late')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>Avg Late</span>
                            </th>
                            <th @click="sortBy('after_1000')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>&gt;10:00</span>
                            </th>
                            <th @click="sortBy('past_1830_pct')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>&gt;18:30 %</span>
                            </th>
                            <th @click="sortBy('past_2000')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>&gt;20:00</span>
                            </th>
                            <th @click="sortBy('extra_hrs')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>Extra Hrs</span>
                            </th>
                            <th @click="sortBy('absent')" class="p-3 text-center cursor-pointer hover:text-blue-600 transition-colors">
                                <span>Absent</span>
                            </th>
                            <th @click="sortBy('pattern')" class="p-3 text-left cursor-pointer hover:text-blue-600 transition-colors">
                                <span>Pattern</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                        <template x-for="row in filteredStaff" :key="row.name">
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 dark:text-white" x-text="row.name"></div>
                                    <div class="text-[10px] text-slate-400 font-medium" x-text="row.role"></div>
                                </td>
                                <td class="p-3 text-slate-500 dark:text-slate-400 text-[11px]" x-text="row.dept"></td>
                                <td class="p-3 text-center font-mono font-bold" x-text="row.avg_in"></td>
                                <td class="p-3 text-center font-mono font-bold" x-text="row.avg_out"></td>
                                <td class="p-3 text-center font-black" x-text="row.avg_hrs"></td>
                                <td class="p-3 text-center font-black" 
                                    :class="row.late_pct >= 50 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                    x-text="row.late_pct + '%'"></td>
                                <td class="p-3 text-center text-slate-500" x-text="row.avg_late"></td>
                                <td class="p-3 text-center font-mono" x-text="row.after_1000"></td>
                                <td class="p-3 text-center font-mono" x-text="row.past_1830_pct + '%'"></td>
                                <td class="p-3 text-center font-mono" x-text="row.past_2000"></td>
                                <td class="p-3 text-center font-black text-slate-900 dark:text-white" x-text="row.extra_hrs"></td>
                                <td class="p-3 text-center text-slate-500 font-mono" x-text="row.absent"></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black border"
                                          :class="{
                                              'bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-800': row.pattern === 'Shifted schedule',
                                              'bg-rose-50 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-800': row.pattern === 'Punctuality risk',
                                              'bg-orange-50 dark:bg-orange-950/70 text-orange-700 dark:text-orange-300 border-orange-300 dark:border-orange-800': row.pattern === 'Overextended',
                                              'bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800': row.pattern === 'On the clock',
                                              'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700': row.pattern === 'Balanced'
                                          }"
                                          x-text="row.pattern">
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- 9. SLEEK METHODOLOGY & POLICY RULES (COLLAPSIBLE) -->
        <div class="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm">
            <button type="button" 
                    @click="showNotes = !showNotes" 
                    class="w-full flex items-center justify-between text-left cursor-pointer">
                <div class="flex items-center gap-2">
                    <i class="ph ph-info text-blue-600 text-lg"></i>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Methodology & Policy Definitions</h3>
                </div>
                <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" :class="showNotes ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="showNotes" x-collapse x-cloak class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-slate-600 dark:text-slate-300">
                    @foreach($methodAndCaveats as $c)
                        <div class="p-3 rounded-xl bg-slate-50/70 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                            <strong class="text-slate-900 dark:text-white block mb-0.5">{{ $c['title'] }}</strong>
                            <span class="text-[11px] leading-relaxed">{{ $c['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>


    <!-- ============================================================== -->
    <!-- TAB 2: DEPARTMENT BREAKDOWN & QUOTAS -->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'department'" class="space-y-6" x-cloak>
        <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <i class="ph ph-buildings text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Department Leave Utilization</h2>
                        <p class="text-[11px] text-slate-400 font-semibold">Allocated quotas vs consumed leave days</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('analytics.export', ['type' => 'company', 'format' => 'pdf']) }}" 
                       target="_blank"
                       class="bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 dark:text-rose-300 border border-rose-200 dark:border-rose-900/60 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                        <i class="ph ph-file-pdf text-rose-600"></i>
                        <span>Department PDF</span>
                    </a>
                    <a href="{{ route('analytics.export', ['type' => 'company', 'format' => 'csv']) }}" 
                       class="bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                        <i class="ph ph-file-csv text-emerald-600"></i>
                        <span>Department CSV</span>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3">Department</th>
                            <th class="p-3 text-center">Staff</th>
                            <th class="p-3 text-center">Days Consumed</th>
                            <th class="p-3 text-center">Short Leaves</th>
                            <th class="p-3 text-center">Total Quota</th>
                            <th class="p-3 text-center">Utilization</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                        @forelse($departmentReport as $row)
                            @php
                                $dept = $row['department'];
                                $util = $row['utilization_pct'];
                                $utilColor = $util > 80 ? 'text-rose-600 dark:text-rose-400' : ($util > 50 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-[#1e2d4d]/60 transition-colors">
                                <td class="p-3 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-mono text-[10px]">{{ $dept->code ?? 'DEPT' }}</span>
                                        <span>{{ $dept->name }}</span>
                                    </div>
                                </td>
                                <td class="p-3 text-center font-bold">{{ $row['employee_count'] }}</td>
                                <td class="p-3 text-center font-black text-slate-900 dark:text-white">{{ $row['total_days_taken'] }}</td>
                                <td class="p-3 text-center font-bold text-amber-600 dark:text-amber-400">{{ $row['total_short_leaves'] }}</td>
                                <td class="p-3 text-center text-slate-500">{{ $row['total_quota'] }}</td>
                                <td class="p-3 text-center font-black {{ $utilColor }}">{{ $util }}%</td>
                                <td class="p-3 text-right">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <a href="{{ route('analytics.export', ['type' => 'department', 'format' => 'pdf', 'department_id' => $dept->id]) }}" 
                                           target="_blank"
                                           class="p-1 rounded-md text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 font-bold">
                                            <i class="ph ph-file-pdf text-sm"></i>
                                        </a>
                                        <a href="{{ route('analytics.export', ['type' => 'department', 'format' => 'csv', 'department_id' => $dept->id]) }}" 
                                           class="p-1 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950 font-bold">
                                            <i class="ph ph-file-csv text-sm"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-400">No departments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- TAB 3: INDIVIDUAL EMPLOYEE STATEMENTS -->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'individual'" class="space-y-6" x-cloak>
        <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <i class="ph ph-user-list text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Individual Leave Statements</h2>
                        <p class="text-[11px] font-semibold text-slate-400">Detailed employee leave logs</p>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3">Employee</th>
                            <th class="p-3">Leave Type</th>
                            <th class="p-3">Date Range</th>
                            <th class="p-3 text-center">Duration</th>
                            <th class="p-3">Covering Colleague</th>
                            <th class="p-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                        @forelse($individualLeaves as $req)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="p-3 font-bold text-slate-900 dark:text-white">
                                    {{ $req->employee?->user?->name ?? 'Employee' }}
                                </td>
                                <td class="p-3">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $req->leaveType->name ?? 'General' }}</span>
                                </td>
                                <td class="p-3 text-xs">
                                    {{ \Carbon\Carbon::parse($req->start_date)->format('M d, Y') }}
                                    @if($req->start_date !== $req->end_date)
                                        &rarr; {{ \Carbon\Carbon::parse($req->end_date)->format('M d, Y') }}
                                    @endif
                                </td>
                                <td class="p-3 text-center font-black">
                                    {{ (float)($req->duration ?: 1.0) }} {{ Str::plural('day', (float)$req->duration) }}
                                </td>
                                <td class="p-3 text-xs text-slate-500">
                                    {{ $req->coveringEmployee?->user?->name ?? 'None' }}
                                </td>
                                <td class="p-3 text-center">
                                    @if($req->status === 'Approved')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-white/[0.05] text-slate-700 dark:text-[#E2E8F0] border border-slate-200/80 dark:border-white/[0.08]"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span> Approved</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-white/[0.05] text-slate-700 dark:text-[#E2E8F0] border border-slate-200/80 dark:border-white/[0.08]"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span> {{ $req->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                                    No individual leave applications found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-2">
                {{ $individualLeaves->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
