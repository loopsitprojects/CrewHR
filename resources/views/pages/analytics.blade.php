@extends('layouts.app', ['title' => 'Loops HR - Reports & Analytics', 'breadcrumb' => 'Reports & Analytics'])

@section('content')
<div class="space-y-8 pb-16" x-data="{
    activeTab: '{{ in_array($activeTab, ['behavioral', 'matrix']) ? $activeTab : 'overview' }}',
    matrixSubView: '{{ request()->query('tab') === 'department' ? 'department' : (request()->query('tab') === 'individual' ? 'individual' : 'workforce') }}',
    deptFilter: '{{ $selectedDeptName ?? 'all' }}',
    patternFilter: 'all',
    searchQuery: '',
    min20DaysOnly: false,
    sortCol: 'late_pct',
    sortAsc: false,
    methodologyOpen: false,
    page: 1,
    pageSize: 10,
    
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

    get totalPages() {
        return Math.ceil(this.filteredStaff.length / this.pageSize) || 1;
    },

    get paginatedStaff() {
        const start = (this.page - 1) * this.pageSize;
        return this.filteredStaff.slice(start, start + this.pageSize);
    },

    sortBy(column) {
        if (this.sortCol === column) {
            this.sortAsc = !this.sortAsc;
        } else {
            this.sortCol = column;
            this.sortAsc = false;
        }
        this.page = 1;
    }
}">

    <!-- ==================== 1. UNIFIED TOP FILTER & NAVIGATION BAR ==================== -->
    <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] flex flex-wrap items-center justify-between gap-4 transition-all">
        
        <!-- Left: Filters (Reporting Period & Department Quick Selector) -->
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/70 text-xs font-semibold text-slate-700 dark:text-slate-200">
                <i class="ph ph-calendar text-indigo-500 text-sm"></i>
                <span>1 Apr – 31 Aug 2026</span>
            </div>

            <!-- Department Filter Dropdown -->
            <div class="relative flex items-center">
                <select x-model="deptFilter" 
                        @change="page = 1"
                        class="h-8.5 pl-3 pr-7 bg-slate-100 hover:bg-slate-200/70 dark:bg-slate-800 dark:hover:bg-slate-700/80 border border-slate-200/80 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none cursor-pointer appearance-none">
                    <option value="all">All Departments</option>
                    @foreach($allDepartments as $deptOpt)
                        <option value="{{ $deptOpt->name }}">{{ $deptOpt->name }}</option>
                    @endforeach
                </select>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2 pointer-events-none"></i>
            </div>
        </div>

        <!-- Center: Progressive Disclosure Sub-Tabs -->
        <div class="inline-flex p-1 bg-slate-100 dark:bg-[#0c1322] rounded-xl border border-slate-200/80 dark:border-slate-800 text-xs font-bold">
            <button type="button" 
                    @click="activeTab = 'overview'" 
                    :class="activeTab === 'overview' ? 'bg-indigo-600 text-white shadow-2xs font-black' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'"
                    class="px-4 py-2 rounded-lg transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-squares-four text-sm"></i>
                <span>Overview</span>
            </button>
            <button type="button" 
                    @click="activeTab = 'behavioral'" 
                    :class="activeTab === 'behavioral' ? 'bg-indigo-600 text-white shadow-2xs font-black' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'"
                    class="px-4 py-2 rounded-lg transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-brain text-sm"></i>
                <span>Behavioral Insights</span>
            </button>
            <button type="button" 
                    @click="activeTab = 'matrix'" 
                    :class="activeTab === 'matrix' ? 'bg-indigo-600 text-white shadow-2xs font-black' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'"
                    class="px-4 py-2 rounded-lg transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-table text-sm"></i>
                <span>Department & Employee Matrix</span>
            </button>
        </div>

        <!-- Right: Actions (Methodology Drawer Trigger & Standard Icon Buttons) -->
        <div class="flex items-center gap-2">
            <!-- Methodology Button -->
            <button type="button" 
                    @click="methodologyOpen = true" 
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition-all cursor-pointer"
                    title="Open Methodology & Policy Definitions">
                <i class="ph ph-info text-indigo-500 text-sm"></i>
                <span class="hidden sm:inline">Methodology</span>
            </button>

            <!-- PDF Icon Button -->
            <a href="{{ route('analytics.export', ['type' => 'executive', 'format' => 'pdf']) }}" 
               target="_blank"
               title="Download Executive PDF Report"
               class="p-2 rounded-xl text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition-all flex items-center justify-center">
                <i class="ph ph-file-pdf text-base"></i>
            </a>

            <!-- CSV Icon Button -->
            <a href="{{ route('analytics.export', ['type' => 'executive', 'format' => 'csv']) }}" 
               title="Export CSV Dataset"
               class="p-2 rounded-xl text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition-all flex items-center justify-center">
                <i class="ph ph-file-csv text-emerald-600 text-base"></i>
            </a>

            <!-- Print Icon Button -->
            <button type="button" 
                    onclick="window.print()" 
                    class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer flex items-center justify-center"
                    title="Print Analytics View">
                <i class="ph ph-printer text-base"></i>
            </button>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- TAB 1: OVERVIEW (TOP-LEVEL KPIS & DAILY SUMMARY) -->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'overview'" class="space-y-8" x-cloak>

        <!-- 1. HERO SUMMARY & SHIFT PARAMETERS BAR -->
        <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Workforce Benchmarks & Shift Target
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                Executive Attendance & Workforce Analytics
                            </h2>
                            <!-- Concise (i) Info Icon with Hover Tooltip replacing long text -->
                            <div class="relative" x-data="{ openTooltip: false }">
                                <button type="button" 
                                        @mouseenter="openTooltip = true" 
                                        @mouseleave="openTooltip = false" 
                                        @click="openTooltip = !openTooltip"
                                        class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg cursor-pointer transition-colors"
                                        title="View analysis details">
                                    <i data-lucide="info" class="w-4 h-4"></i>
                                </button>
                                <div x-show="openTooltip" 
                                     x-cloak
                                     class="absolute left-0 sm:left-auto sm:right-0 top-7 z-30 w-80 p-3.5 bg-slate-900 text-slate-100 dark:bg-slate-800 text-xs rounded-xl shadow-xl border border-slate-700/80 leading-relaxed font-medium">
                                    {{ $executiveSummary['narrative'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Across recorded working days</span>
                </div>
            </div>

            <!-- Shift Parameters Rule Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 p-4 rounded-xl bg-slate-50 dark:bg-[#0c1322] border border-slate-200/80 dark:border-slate-800">
                <div class="border-r border-slate-200/80 dark:border-slate-800/80 pr-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Shift Start</span>
                    <span class="text-xl sm:text-2xl font-black font-mono text-slate-900 dark:text-white block mt-0.5">{{ $shiftConfig['shift_start'] }}</span>
                </div>
                <div class="border-r border-slate-200/80 dark:border-slate-800/80 pr-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-500 block">Grace Until</span>
                    <span class="text-xl sm:text-2xl font-black font-mono text-amber-600 dark:text-amber-400 block mt-0.5">{{ $shiftConfig['grace_until'] }}</span>
                </div>
                <div class="border-r border-slate-200/80 dark:border-slate-800/80 pr-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Shift End</span>
                    <span class="text-xl sm:text-2xl font-black font-mono text-slate-900 dark:text-white block mt-0.5">{{ $shiftConfig['shift_end'] }}</span>
                </div>
                <div class="border-r border-slate-200/80 dark:border-slate-800/80 pr-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Active Staff</span>
                    <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white block mt-0.5">{{ $shiftConfig['staff_count'] }}</span>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Working Days</span>
                    <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white block mt-0.5">{{ number_format($shiftConfig['working_days']) }}</span>
                </div>
            </div>

            <!-- "The Office at a Glance" — 6 Key Metric Cards with Purpose-Driven Color Palette -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- KPI 1: Avg In (Primary Brand Accent) -->
                <div class="p-6 rounded-2xl bg-slate-50/70 dark:bg-[#0c1322] border border-slate-200/80 dark:border-slate-800 hover:border-indigo-500/50 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Average Clock-In</span>
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    </div>
                    <div class="text-3xl font-black font-mono text-slate-900 dark:text-white mt-2">
                        {{ $executiveSummary['kpis']['avg_clock_in']['value'] }}
                    </div>
                    <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-1">
                        Median 09:12 · 33% on time
                    </div>
                </div>

                <!-- KPI 2: Avg Out (Muted Indigo / Amber Target) -->
                <div class="p-6 rounded-2xl bg-slate-50/70 dark:bg-[#0c1322] border border-slate-200/80 dark:border-slate-800 hover:border-amber-500/50 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Average Clock-Out</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="text-3xl font-black font-mono text-amber-600 dark:text-amber-400 mt-2">
                        {{ $executiveSummary['kpis']['avg_clock_out']['value'] }}
                    </div>
                    <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-1">
                        Median 18:20 · 42% past 18:30
                    </div>
                </div>

                <!-- KPI 3: Day Length (Muted Indigo Accent) -->
                <div class="p-6 rounded-2xl bg-slate-50/70 dark:bg-[#0c1322] border border-slate-200/80 dark:border-slate-800 hover:border-indigo-500/50 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Average Day Length</span>
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    </div>
                    <div class="text-3xl font-black font-mono text-slate-900 dark:text-white mt-2">
                        {{ $executiveSummary['kpis']['avg_day_length']['value'] }}
                    </div>
                    <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-1">
                        8h 00m standard target
                    </div>
                </div>

                <!-- KPI 4: Late Arrivals (Coral Red Alert) -->
                <div class="p-6 rounded-2xl bg-slate-50/70 dark:bg-[#0c1322] border border-slate-200/80 dark:border-slate-800 hover:border-rose-500/50 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400">Late Arrival Rate</span>
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    </div>
                    <div class="text-3xl font-black text-rose-600 dark:text-rose-400 mt-2">
                        {{ $executiveSummary['kpis']['days_arriving_late']['value'] }}
                    </div>
                    <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-1">
                        161 of 614 days (&gt;09:30)
                    </div>
                </div>

                <!-- KPI 5: Leaving Early (Mint Green Success) -->
                <div class="p-6 rounded-2xl bg-slate-50/70 dark:bg-[#0c1322] border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">Early Departure Rate</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                        {{ $executiveSummary['kpis']['days_leaving_early']['value'] }}
                    </div>
                    <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1 font-bold">
                        0% Below Target
                    </div>
                </div>

                <!-- KPI 6: Absence Rate (Slate Blue Secondary Metric) -->
                <div class="p-6 rounded-2xl bg-slate-50/70 dark:bg-[#0c1322] border border-slate-200/80 dark:border-slate-800 hover:border-cyan-500/50 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-cyan-600 dark:text-cyan-400">Absence Rate</span>
                        <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                    </div>
                    <div class="text-3xl font-black text-cyan-600 dark:text-cyan-400 mt-2">
                        {{ $executiveSummary['kpis']['absence_rate']['value'] }}
                    </div>
                    <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-1">
                        20 of 634 scheduled days
                    </div>
                </div>
            </div>
        </div>


        <!-- 2. "EVERYONE'S AVERAGE DAY" HORIZONTAL GANTT CHART -->
        <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Average Working Day by Employee</h3>
                    <div class="flex flex-wrap items-center gap-3 text-xs font-bold text-slate-600 dark:text-slate-300 mt-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-indigo-600"></span>
                            <span>Shift 09:00–17:00</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-amber-500"></span>
                            <span>After 17:00</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-3 rounded bg-rose-500"></span>
                            <span>Past 09:30 Grace</span>
                        </div>
                    </div>
                </div>

                <!-- Instant Timeline Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <input type="text" x-model="searchQuery" placeholder="Filter employee..." class="bg-slate-50 dark:bg-[#0c1322] border border-slate-200 dark:border-slate-800 rounded-xl pl-8 pr-3 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                        <i class="ph ph-magnifying-glass absolute left-2.5 top-2 text-slate-400 text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Timeline Scale Top Marks -->
            <div class="flex items-center justify-between text-[11px] font-mono font-bold text-slate-500 dark:text-slate-400 pl-48 md:pl-56 pr-12 pb-1 border-b border-slate-100 dark:border-slate-800/80">
                <span>08:00</span>
                <span class="text-rose-500 font-bold">09:30 grace</span>
                <span>11:00</span>
                <span>13:00</span>
                <span>15:00</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">17:00 shift end</span>
                <span>19:00</span>
                <span>21:00</span>
            </div>

            <!-- Gantt Bars with Purpose-Driven Palette -->
            <div class="space-y-1.5 pt-1 max-h-[500px] overflow-y-auto pr-1">
                @foreach($timelineStaff as $person)
                    <div class="group flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 px-2.5 py-1.5 rounded-xl transition-all duration-200 text-xs"
                         x-show="(deptFilter === 'all' || '{{ strtolower($person['dept']) }}' === deptFilter.toLowerCase()) && 
                                 (!searchQuery || '{{ strtolower($person['name']) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($person['role']) }}'.includes(searchQuery.toLowerCase()))">
                        
                        <!-- Employee Label -->
                        <div class="w-44 md:w-52 shrink-0 truncate">
                            <span class="font-bold text-slate-900 dark:text-white block truncate">{{ $person['name'] }}</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate">{{ $person['dept'] }}</span>
                        </div>

                        <!-- Bar Track -->
                        <div class="flex-1 relative h-5 bg-slate-100 dark:bg-slate-800/80 rounded-md overflow-hidden">
                            <!-- 09:30 Grace Marker Line -->
                            <div class="absolute top-0 bottom-0 left-[11.5%] w-[1px] bg-rose-400/50 dark:bg-rose-500/40 z-10 pointer-events-none"></div>
                            <!-- 17:00 Shift End Line -->
                            <div class="absolute top-0 bottom-0 left-[69.2%] w-[1px] bg-slate-300 dark:bg-slate-600 z-10 pointer-events-none"></div>

                            <!-- Red Arrival Tick if past 09:30 -->
                            @if($person['is_past_grace'])
                                <div class="absolute top-0 bottom-0 w-1 bg-rose-500 z-20 shadow-xs" style="left: {{ $person['left_pct'] }}%;"></div>
                            @endif

                            <!-- Blue/Indigo Segment: 09:00 - 17:00 -->
                            <div class="absolute top-0.5 bottom-0.5 rounded-l-sm bg-indigo-600 dark:bg-indigo-500 shadow-xs transition-all group-hover:brightness-110"
                                 style="left: {{ $person['left_pct'] }}%; width: {{ $person['blue_width_pct'] }}%;"></div>

                            <!-- Soft Amber Segment: After 17:00 -->
                            @if($person['orange_width_pct'] > 0)
                                <div class="absolute top-0.5 bottom-0.5 rounded-r-sm bg-amber-500 shadow-xs transition-all group-hover:brightness-110"
                                     style="left: {{ $person['left_pct'] + $person['blue_width_pct'] }}%; width: {{ $person['orange_width_pct'] }}%;"></div>
                            @endif
                        </div>

                        <!-- Late % on right -->
                        <div class="w-12 text-right font-black font-mono {{ $person['late_pct'] >= 50 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300' }}">
                            {{ $person['late_pct'] }}%
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Timeline Scale Bottom Marks -->
            <div class="flex items-center justify-between text-[11px] font-mono font-bold text-slate-500 dark:text-slate-400 pl-48 md:pl-56 pr-12 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                <span>08:00</span>
                <span class="text-rose-500 font-bold">09:30 grace</span>
                <span>11:00</span>
                <span>13:00</span>
                <span>15:00</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">17:00 shift end</span>
                <span>19:00</span>
                <span>21:00</span>
            </div>
        </div>


        <!-- 3. ARRIVAL HISTOGRAM & 4-QUADRANT CORRELATION MATRIX -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Chart A: Arrival Histogram -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Arrival Distribution (15-Min Intervals)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Clock-in volume relative to the 09:30 threshold</p>
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
                                <div class="w-full rounded-t-sm transition-all duration-300 {{ $bucket['is_late'] ? 'bg-amber-500' : 'bg-indigo-600 dark:bg-indigo-500' }}"
                                     style="height: {{ $heightPct }}%;"></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between text-[10px] font-mono font-bold text-slate-500 dark:text-slate-400 mt-2">
                        <span>07:00</span>
                        <span>08:00</span>
                        <span>09:00</span>
                        <span class="text-rose-600 font-bold">09:30</span>
                        <span>10:00</span>
                        <span>11:00</span>
                        <span>12:00</span>
                        <span>13:00</span>
                    </div>

                    <div class="flex items-center justify-between text-xs font-bold pt-4 text-slate-700 dark:text-slate-200">
                        <span class="text-indigo-600 dark:text-indigo-400">&larr; 33% arrive by 09:00</span>
                        <span class="text-amber-600 dark:text-amber-400">36% arrive after 09:30 &rarr;</span>
                    </div>
                </div>
            </div>

            <!-- Chart B: 4-Quadrant Scatter Matrix -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Late in, Late out — or Just Late?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Correlation between late arrival and late departure</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400">r = 0.52</span>
                </div>

                <!-- Quadrant Container -->
                <div class="relative h-64 rounded-xl border border-slate-200 dark:border-slate-800 p-2 overflow-hidden bg-slate-50 dark:bg-[#0c1322]">
                    <!-- Quadrant Background Tints -->
                    <div class="absolute left-0 top-0 w-1/2 h-1/2 bg-indigo-500/5 border-r border-b border-dashed border-slate-300 dark:border-slate-700"></div>
                    <div class="absolute right-0 top-0 w-1/2 h-1/2 bg-amber-500/5 border-b border-dashed border-slate-300 dark:border-slate-700"></div>
                    <div class="absolute left-0 bottom-0 w-1/2 h-1/2 bg-emerald-500/5 border-r border-dashed border-slate-300 dark:border-slate-700"></div>
                    <div class="absolute right-0 bottom-0 w-1/2 h-1/2 bg-rose-500/5"></div>

                    <!-- Labels -->
                    <span class="absolute top-2 left-3 text-[9px] font-bold uppercase text-slate-500 dark:text-slate-400 pointer-events-none">On Time, Stays Late</span>
                    <span class="absolute top-2 right-3 text-[9px] font-bold uppercase text-slate-500 dark:text-slate-400 pointer-events-none">Late In, Late Out</span>
                    <span class="absolute bottom-2 left-3 text-[9px] font-bold uppercase text-slate-500 dark:text-slate-400 pointer-events-none">On The Clock</span>
                    <span class="absolute bottom-2 right-3 text-[9px] font-bold uppercase text-rose-500 pointer-events-none">Late In, Out On Time</span>

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

                <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400 pt-1">
                    <span>0% Arrival after 09:30 &rarr;</span>
                    <span>100%</span>
                </div>
            </div>
        </div>


        <!-- 4. TRENDS, SCHEDULES & LEAVE MIX (3-COLUMN GRID) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Col 1: Month by Month & Weekday -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Monthly & Day Trends</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">Average clock movements over the period</p>
                </div>

                <div class="space-y-3">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-[#0c1322] border border-slate-200/70 dark:border-slate-800">
                        <span class="text-[10px] font-bold uppercase text-indigo-600 dark:text-indigo-400 block">Avg Clock-In</span>
                        <div class="grid grid-cols-5 gap-1 text-center font-mono font-bold text-xs text-slate-800 dark:text-slate-200 mt-1.5">
                            @foreach($trendsAndCuts['months']['labels'] as $idx => $m)
                                <div>
                                    <div class="text-[9px] text-slate-400 font-semibold">{{ $m }}</div>
                                    <div>{{ $trendsAndCuts['months']['avg_in'][$idx] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-[#0c1322] border border-slate-200/70 dark:border-slate-800">
                        <span class="text-[10px] font-bold uppercase text-amber-600 dark:text-amber-400 block">Avg Clock-Out</span>
                        <div class="grid grid-cols-5 gap-1 text-center font-mono font-bold text-xs text-slate-800 dark:text-slate-200 mt-1.5">
                            @foreach($trendsAndCuts['months']['labels'] as $idx => $m)
                                <div>
                                    <div class="text-[9px] text-slate-400 font-semibold">{{ $m }}</div>
                                    <div>{{ $trendsAndCuts['months']['avg_out'][$idx] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-1">
                        <span class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400 block mb-1.5">By Weekday</span>
                        <div class="grid grid-cols-5 gap-1 text-center text-xs">
                            @foreach($trendsAndCuts['weekdays']['labels'] as $idx => $day)
                                <div class="p-2 rounded-xl bg-slate-50 dark:bg-[#0c1322] border border-slate-200/70 dark:border-slate-800">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block">{{ $day }}</span>
                                    <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 block mt-0.5">{{ $trendsAndCuts['weekdays']['late_in_pct'][$idx] }}%</span>
                                    <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 block">{{ $trendsAndCuts['weekdays']['past_1830_pct'][$idx] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Col 2: By Department -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Department Schedules</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">Average in/out schedule by department</p>
                </div>

                <div class="space-y-3 pt-1">
                    @foreach($trendsAndCuts['departments'] as $dept)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $dept['name'] }}</span>
                                <span class="font-bold font-mono text-slate-600 dark:text-slate-400">{{ $dept['late_rate'] }}% Late</span>
                            </div>
                            <div class="h-2.5 w-full rounded-md bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
                                <div class="absolute top-0 bottom-0 bg-indigo-600" style="left: {{ $dept['in_pct'] }}%; width: {{ $dept['blue_width'] }}%;"></div>
                                <div class="absolute top-0 bottom-0 bg-amber-500" style="left: {{ $dept['in_pct'] + $dept['blue_width'] }}%; width: {{ $dept['orange_width'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Col 3: Leave & Exception Mix -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Leave & Exception Mix</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">Recorded leave distribution</p>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-[#0c1322] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $leaveAndExceptionMix['total_tagged_days'] }} Days
                    </span>
                </div>

                <div class="space-y-2.5 pt-1">
                    @foreach($leaveAndExceptionMix['items'] as $item)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $item['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $item['pct'] }}%</span>
                                    <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item['count'] }}</span>
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

    </div>


    <!-- ============================================================== -->
    <!-- TAB 2: BEHAVIORAL INSIGHTS -->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'behavioral'" class="space-y-8" x-cloak>

        <!-- 1. "WHAT THE DATA SAYS" — 5 INSIGHT CARDS WITH INFO TOOLTIPS -->
        <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Executive Behavioral Insights</h3>
                    <div class="relative" x-data="{ tooltip: false }">
                        <button type="button" 
                                @mouseenter="tooltip = true" 
                                @mouseleave="tooltip = false" 
                                class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer"
                                title="Learn more about behavioral insights">
                            <i data-lucide="info" class="w-4 h-4"></i>
                        </button>
                        <div x-show="tooltip" 
                             x-cloak
                             class="absolute left-0 top-7 z-30 w-72 p-3 bg-slate-900 text-slate-100 dark:bg-slate-800 text-xs rounded-xl shadow-xl border border-slate-700 leading-relaxed font-medium">
                            Derived from individual clock-in and clock-out biometric logs, highlighting schedule shifting, overextension, and punctuality trends across departments.
                        </div>
                    </div>
                </div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Core Patterns Identified</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(array_slice($keyPatterns, 0, 4) as $pat)
                    @php
                        $accentColor = match($pat['number']) {
                            '01' => 'border-amber-500 bg-amber-500/5',
                            '02' => 'border-rose-500 bg-rose-500/5',
                            '03' => 'border-indigo-500 bg-indigo-500/5',
                            '04' => 'border-emerald-500 bg-emerald-500/5',
                            default => 'border-blue-500 bg-blue-500/5',
                        };
                    @endphp
                    <div class="p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 border-l-4 {{ $accentColor }} transition-all hover:shadow-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-slate-400">{{ $pat['number'] }}</span>
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                {{ $pat['tag'] }}
                            </span>
                        </div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-white">{{ $pat['title'] }}</h4>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-medium">{{ $pat['description'] }}</p>
                    </div>
                @endforeach
            </div>

            <!-- 5th Pattern Full Width -->
            @if(isset($keyPatterns[4]))
                <div class="p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 border-l-4 border-indigo-500 bg-indigo-500/5 transition-all hover:shadow-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono font-bold text-slate-400">{{ $keyPatterns[4]['number'] }}</span>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            {{ $keyPatterns[4]['tag'] }}
                        </span>
                    </div>
                    <h4 class="text-sm font-black text-slate-900 dark:text-white">{{ $keyPatterns[4]['title'] }}</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-medium">{{ $keyPatterns[4]['description'] }}</p>
                </div>
            @endif
        </div>


        <!-- 2. "THE TWO LEADERBOARDS" (SIDE BY SIDE) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Leaderboard: Latest Arrivers -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Latest arrivers (&gt;09:30)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Top staff with highest % of days arriving after 09:30</p>
                    </div>
                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-[#0c1322] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Arrivals</span>
                </div>

                <div class="space-y-3">
                    @foreach($leaderboards['latest_arrivers'] as $idx => $arriver)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-mono font-bold flex items-center justify-center text-slate-500">{{ $idx + 1 }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $arriver['name'] }}</span>
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">avg in {{ $arriver['avg_in'] }}</span>
                                </div>
                                <span class="font-black font-mono text-indigo-600 dark:text-indigo-400">{{ $arriver['late_pct'] }}%</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full bg-indigo-600 dark:bg-indigo-500 rounded-full transition-all duration-700" style="width: {{ $arriver['late_pct'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Leaderboard: Latest Leavers -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Latest leavers (Clock-Out)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Staff with latest departures past scheduled shift</p>
                    </div>
                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-[#0c1322] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Departures</span>
                </div>

                <div class="space-y-3">
                    @foreach($leaderboards['latest_leavers'] as $idx => $leaver)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-mono font-bold flex items-center justify-center text-slate-500">{{ $idx + 1 }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $leaver['name'] }}</span>
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">past 18:30 on {{ $leaver['past_1830_pct'] }}% days</span>
                                </div>
                                <span class="font-black font-mono text-amber-600 dark:text-amber-400">{{ $leaver['avg_out'] }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full transition-all duration-700" style="width: {{ $leaver['past_1830_pct'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>


    <!-- ============================================================== -->
    <!-- TAB 3: DEPARTMENT & EMPLOYEE MATRIX -->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'matrix'" class="space-y-8" x-cloak>

        <!-- Sub-View Switcher for Tab 3 -->
        <div class="flex items-center gap-2 border-b border-slate-200/80 dark:border-slate-800 pb-2">
            <button type="button" 
                    @click="matrixSubView = 'workforce'" 
                    :class="matrixSubView === 'workforce' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 font-extrabold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-semibold'"
                    class="px-4 py-2 text-xs transition-all cursor-pointer">
                Workforce Attendance Matrix (50 Staff)
            </button>
            <button type="button" 
                    @click="matrixSubView = 'department'" 
                    :class="matrixSubView === 'department' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 font-extrabold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-semibold'"
                    class="px-4 py-2 text-xs transition-all cursor-pointer">
                Department Quotas & Utilization
            </button>
            <button type="button" 
                    @click="matrixSubView = 'individual'" 
                    :class="matrixSubView === 'individual' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 font-extrabold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-semibold'"
                    class="px-4 py-2 text-xs transition-all cursor-pointer">
                Individual Leave Statements
            </button>
        </div>


        <!-- SUB-VIEW 1: WORKFORCE ATTENDANCE MATRIX -->
        <div x-show="matrixSubView === 'workforce'" class="space-y-4" x-cloak>
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-5">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Workforce Attendance Matrix</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Interactive biometric measures across all active personnel</p>
                    </div>

                    <!-- Search, Pattern Filter & 20+ Days Toggle -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <select x-model="patternFilter" 
                                @change="page = 1"
                                class="bg-slate-50 dark:bg-[#0c1322] border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:outline-none">
                            <option value="all">All Patterns</option>
                            <option value="Shifted schedule">Shifted schedule</option>
                            <option value="Punctuality risk">Punctuality risk</option>
                            <option value="Overextended">Overextended</option>
                            <option value="On the clock">On the clock</option>
                            <option value="Balanced">Balanced</option>
                        </select>

                        <label class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer bg-slate-50 dark:bg-[#0c1322] px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800">
                            <input type="checkbox" x-model="min20DaysOnly" @change="page = 1" class="rounded text-indigo-600 focus:ring-0">
                            <span>20+ Days</span>
                        </label>

                        <span class="text-xs font-black text-slate-500 dark:text-slate-300 px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/60" 
                              x-text="filteredStaff.length + ' Staff'"></span>
                    </div>
                </div>

                <!-- Streamlined Table with Reduced Column Density & Consistent Pill Badges -->
                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl [scrollbar-width:thin]">
                    <table class="w-full text-left text-xs border-collapse min-w-[760px]">
                        <thead class="bg-slate-50 dark:bg-[#0c1322] text-[10.5px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th @click="sortBy('name')" class="p-3.5 cursor-pointer hover:text-indigo-600 transition-colors">
                                    <div class="flex items-center gap-1">
                                        <span>Employee</span>
                                        <span x-show="sortCol === 'name'" x-text="sortAsc ? '↑' : '↓'"></span>
                                    </div>
                                </th>
                                <th @click="sortBy('dept')" class="p-3.5 cursor-pointer hover:text-indigo-600 transition-colors">
                                    <span>Department</span>
                                </th>
                                <th @click="sortBy('avg_in')" class="p-3.5 text-center cursor-pointer hover:text-indigo-600 transition-colors">
                                    <span>Avg In</span>
                                </th>
                                <th @click="sortBy('avg_out')" class="p-3.5 text-center cursor-pointer hover:text-indigo-600 transition-colors">
                                    <span>Avg Out</span>
                                </th>
                                <th @click="sortBy('avg_hrs')" class="p-3.5 text-center cursor-pointer hover:text-indigo-600 transition-colors">
                                    <span>Avg Hrs</span>
                                </th>
                                <th @click="sortBy('late_pct')" class="p-3.5 text-center cursor-pointer hover:text-rose-600 transition-colors">
                                    <div class="flex items-center justify-center gap-1">
                                        <span>Late %</span>
                                        <span x-show="sortCol === 'late_pct'" x-text="sortAsc ? '↑' : '↓'"></span>
                                    </div>
                                </th>
                                <th @click="sortBy('past_1830_pct')" class="p-3.5 text-center cursor-pointer hover:text-indigo-600 transition-colors">
                                    <span>&gt;18:30 %</span>
                                </th>
                                <th @click="sortBy('extra_hrs')" class="p-3.5 text-center cursor-pointer hover:text-indigo-600 transition-colors">
                                    <span>Extra Hrs</span>
                                </th>
                                <th @click="sortBy('absent')" class="p-3.5 text-center cursor-pointer hover:text-indigo-600 transition-colors">
                                    <span>Absent</span>
                                </th>
                                <th @click="sortBy('pattern')" class="p-3.5 text-center cursor-pointer hover:text-indigo-600 transition-colors">
                                    <span>Pattern</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium text-slate-800 dark:text-slate-200">
                            <template x-for="row in paginatedStaff" :key="row.name">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="p-3.5">
                                        <div class="font-bold text-slate-900 dark:text-white" x-text="row.name"></div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium" x-text="row.role"></div>
                                    </td>
                                    <td class="p-3.5 text-slate-600 dark:text-slate-300 text-xs font-semibold" x-text="row.dept"></td>
                                    <td class="p-3.5 text-center font-mono font-bold" x-text="row.avg_in"></td>
                                    <td class="p-3.5 text-center font-mono font-bold" x-text="row.avg_out"></td>
                                    <td class="p-3.5 text-center font-bold" x-text="row.avg_hrs"></td>
                                    <td class="p-3.5 text-center font-bold">
                                        <span class="inline-flex items-center gap-1.5" :class="row.late_pct >= 50 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300'">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="row.late_pct >= 50 ? 'bg-rose-500' : (row.late_pct > 0 ? 'bg-amber-400' : 'bg-emerald-500')"></span>
                                            <span x-text="row.late_pct + '%'"></span>
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center font-mono text-slate-700 dark:text-slate-300" x-text="row.past_1830_pct + '%'"></td>
                                    <td class="p-3.5 text-center font-bold text-slate-900 dark:text-white" x-text="row.extra_hrs"></td>
                                    <td class="p-3.5 text-center text-slate-600 dark:text-slate-300 font-mono" x-text="row.absent"></td>
                                    <td class="p-3.5 text-center">
                                        <span class="inline-block w-32 py-1 rounded-md text-[10px] font-bold border text-center whitespace-nowrap shadow-2xs"
                                              :class="{
                                                  'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60': row.pattern === 'Shifted schedule',
                                                  'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60': row.pattern === 'Punctuality risk',
                                                  'bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800/60': row.pattern === 'Overextended',
                                                  'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60': row.pattern === 'On the clock',
                                                  'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700': row.pattern === 'Balanced'
                                              }"
                                              x-text="row.pattern">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Clean Pagination Controls -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 text-xs">
                    <div class="text-slate-500 dark:text-slate-400 font-medium">
                        Showing <strong class="text-slate-800 dark:text-slate-200" x-text="filteredStaff.length > 0 ? ((page - 1) * pageSize) + 1 : 0"></strong>
                        to <strong class="text-slate-800 dark:text-slate-200" x-text="Math.min(page * pageSize, filteredStaff.length)"></strong>
                        of <strong class="text-slate-800 dark:text-slate-200" x-text="filteredStaff.length"></strong> staff members
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="page = Math.max(1, page - 1)" 
                                :disabled="page === 1"
                                :class="page === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer'"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold transition-all">
                            Previous
                        </button>

                        <span class="text-slate-600 dark:text-slate-400 font-bold px-1" x-text="'Page ' + page + ' of ' + totalPages"></span>

                        <button type="button" 
                                @click="page = Math.min(totalPages, page + 1)" 
                                :disabled="page >= totalPages"
                                :class="page >= totalPages ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer'"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold transition-all">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <!-- SUB-VIEW 2: DEPARTMENT QUOTAS & UTILIZATION -->
        <div x-show="matrixSubView === 'department'" class="space-y-4" x-cloak>
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Department Leave Utilization</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Allocated quotas vs consumed leave days across organizational units</p>
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
                        <thead class="bg-slate-50 dark:bg-[#0c1322] text-[10.5px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-3.5">Department</th>
                                <th class="p-3.5 text-center">Staff</th>
                                <th class="p-3.5 text-center">Days Consumed</th>
                                <th class="p-3.5 text-center">Short Leaves</th>
                                <th class="p-3.5 text-center">Total Quota</th>
                                <th class="p-3.5 text-center">Utilization</th>
                                <th class="p-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium text-slate-800 dark:text-slate-200">
                            @forelse($departmentReport as $row)
                                @php
                                    $dept = $row['department'];
                                    $util = $row['utilization_pct'];
                                    $utilColor = $util > 80 ? 'text-rose-600 dark:text-rose-400' : ($util > 50 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <span class="px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-mono text-[10px]">{{ $dept->code ?? 'DEPT' }}</span>
                                            <span>{{ $dept->name }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center font-bold">{{ $row['employee_count'] }}</td>
                                    <td class="p-3.5 text-center font-black text-slate-900 dark:text-white">{{ $row['total_days_taken'] }}</td>
                                    <td class="p-3.5 text-center font-bold text-amber-600 dark:text-amber-400">{{ $row['total_short_leaves'] }}</td>
                                    <td class="p-3.5 text-center text-slate-500">{{ $row['total_quota'] }}</td>
                                    <td class="p-3.5 text-center font-black {{ $utilColor }}">{{ $util }}%</td>
                                    <td class="p-3.5 text-right">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <a href="{{ route('analytics.export', ['type' => 'department', 'format' => 'pdf', 'department_id' => $dept->id]) }}" 
                                               target="_blank"
                                               class="p-1 rounded-md text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 font-bold"
                                               title="PDF Export">
                                                <i class="ph ph-file-pdf text-sm"></i>
                                            </a>
                                            <a href="{{ route('analytics.export', ['type' => 'department', 'format' => 'csv', 'department_id' => $dept->id]) }}" 
                                               class="p-1 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950 font-bold"
                                               title="CSV Export">
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


        <!-- SUB-VIEW 3: INDIVIDUAL LEAVE STATEMENTS -->
        <div x-show="matrixSubView === 'individual'" class="space-y-4" x-cloak>
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Individual Leave Statements</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Granular employee leave request audit log</p>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-[#0c1322] text-[10.5px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-3.5">Employee</th>
                                <th class="p-3.5">Leave Type</th>
                                <th class="p-3.5">Date Range</th>
                                <th class="p-3.5 text-center">Duration</th>
                                <th class="p-3.5">Covering Colleague</th>
                                <th class="p-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium text-slate-800 dark:text-slate-200">
                            @forelse($individualLeaves as $req)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                        {{ $req->employee?->user?->name ?? 'Employee' }}
                                    </td>
                                    <td class="p-3.5">
                                        <span class="font-bold text-slate-900 dark:text-white">{{ $req->leaveType->name ?? 'General' }}</span>
                                    </td>
                                    <td class="p-3.5 text-xs text-slate-600 dark:text-slate-300">
                                        {{ \Carbon\Carbon::parse($req->start_date)->format('M d, Y') }}
                                        @if($req->start_date !== $req->end_date)
                                            &rarr; {{ \Carbon\Carbon::parse($req->end_date)->format('M d, Y') }}
                                        @endif
                                    </td>
                                    <td class="p-3.5 text-center font-bold">
                                        {{ (float)($req->duration ?: 1.0) }} {{ Str::plural('day', (float)$req->duration) }}
                                    </td>
                                    <td class="p-3.5 text-xs text-slate-500">
                                        {{ $req->coveringEmployee?->user?->name ?? 'None' }}
                                    </td>
                                    <td class="p-3.5 text-center">
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


    <!-- ============================================================== -->
    <!-- DEDICATED METHODOLOGY & POLICY DEFINITIONS SIDE DRAWER -->
    <!-- ============================================================== -->
    <template x-teleport="body">
        <div>
            <!-- Backdrop Overlay -->
            <div x-show="methodologyOpen" 
                 x-transition:enter="transition opacity ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition opacity ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="methodologyOpen = false" 
                 x-cloak
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[999]"></div>

            <!-- Slide-Over Drawer Container -->
            <div x-show="methodologyOpen" 
                 x-transition:enter="transition transform ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition transform ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 x-cloak 
                 class="fixed inset-y-0 right-0 w-full sm:w-[480px] bg-white dark:bg-[#0f172a] shadow-2xl z-[1000] flex flex-col border-l border-slate-200/90 dark:border-slate-800 overflow-hidden">
                
                <!-- Drawer Header -->
                <div class="px-6 py-5 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center font-bold">
                            <i class="ph ph-book-open text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black tracking-tight text-white">Methodology & Policies</h2>
                            <p class="text-[11px] text-slate-400">Statutory benchmarks & calculation rules</p>
                        </div>
                    </div>
                    <button type="button" @click="methodologyOpen = false" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Drawer Content -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
                    <div class="p-3.5 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 text-indigo-900 dark:text-indigo-200 leading-relaxed font-medium">
                        These benchmarks follow Sri Lanka's <strong>Shop & Office Employees Act</strong> and corporate scheduling frameworks. All attendance metrics are computed from official timeclock entries.
                    </div>

                    <div class="space-y-3 pt-2">
                        @foreach($methodAndCaveats as $c)
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-[#0c1322] border border-slate-200/80 dark:border-slate-800 space-y-1.5">
                                <strong class="text-xs font-black text-slate-900 dark:text-white block">{{ $c['title'] }}</strong>
                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-medium">{{ $c['text'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Drawer Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#0c1322] flex justify-end">
                    <button type="button" @click="methodologyOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition-all cursor-pointer">
                        Close Drawer
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection
