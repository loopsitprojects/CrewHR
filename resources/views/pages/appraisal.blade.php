@extends('layouts.app', ['title' => 'Loops HR - Performance Appraisal Suite', 'breadcrumb' => 'Appraisal System'])

@section('content')
@php
    // Cycles for both tracks
    $quarterlyCycle = $cycles->firstWhere('cycle_type', 'mid_year') ?? $cycles->first(fn($c) => $c->isMidYear());
    $biAnnualCycle = $cycles->firstWhere('cycle_type', 'year_end') ?? $cycles->first(fn($c) => !$c->isMidYear());

    // Tile count source (Self or Team)
    $tileSourceAppraisals = ($role === 'Employee' || (isset($myAppraisals) && $myAppraisals->isNotEmpty()))
        ? ($myAppraisals ?? $appraisals->where('employee_id', $employee?->id))
        : $appraisals;

    $quarterlyTileCount = $tileSourceAppraisals->filter(fn($app) => $app->cycle?->isMidYear())->count();
    $biAnnualTileCount = $tileSourceAppraisals->filter(fn($app) => !$app->cycle?->isMidYear())->count();

    // Partition appraisals for roster
    $quarterlyAppraisals = $appraisals->filter(fn($app) => $app->cycle?->isMidYear());
    $biAnnualAppraisals = $appraisals->filter(fn($app) => !$app->cycle?->isMidYear());

    // Partition myAppraisals (for self-eval)
    $selfList = (isset($myAppraisals) && $myAppraisals->isNotEmpty())
        ? $myAppraisals
        : $appraisals->where('employee_id', $employee?->id);

    // Sort chronologically by urgency/active status:
    // 1. Active review cycles first (e.g. FY 2026/2027 Mid-Year Performance Review)
    // 2. Earliest deadline / due date first
    $selfList = $selfList->sort(function($a, $b) {
        $aActive = $a->cycle?->status === 'Active' ? 0 : 1;
        $bActive = $b->cycle?->status === 'Active' ? 0 : 1;
        if ($aActive !== $bActive) return $aActive <=> $bActive;

        $aDue = $a->self_eval_deadline 
            ? \Carbon\Carbon::parse($a->self_eval_deadline)->timestamp 
            : ($a->cycle?->end_date ? \Carbon\Carbon::parse($a->cycle->end_date)->timestamp : PHP_INT_MAX);
        $bDue = $b->self_eval_deadline 
            ? \Carbon\Carbon::parse($b->self_eval_deadline)->timestamp 
            : ($b->cycle?->end_date ? \Carbon\Carbon::parse($b->cycle->end_date)->timestamp : PHP_INT_MAX);

        return $aDue <=> $bDue;
    })->values();

    $quarterlySelf = $selfList->filter(fn($app) => $app->cycle?->isMidYear());
    $biAnnualSelf = $selfList->filter(fn($app) => !$app->cycle?->isMidYear());

    // Partition letters
    $quarterlyLetters = $letters->filter(fn($l) => $l->appraisal?->cycle?->isMidYear());
    $biAnnualLetters = $letters->filter(fn($l) => !$l->appraisal?->cycle?->isMidYear());

    // Partition change requests
    $quarterlyChanges = $myChangeRequests->filter(fn($cr) => $cr->appraisal?->cycle?->isMidYear());
    $biAnnualChanges = $myChangeRequests->filter(fn($cr) => !$cr->appraisal?->cycle?->isMidYear());

    // Prepare self-evaluation metadata for Alpine dynamic filtering
    $selfYearsList = collect();
    $selfAppsMeta = [];

    foreach ($selfList as $appItem) {
        $cStart = $appItem->cycle?->start_date ? \Carbon\Carbon::parse($appItem->cycle->start_date) : null;
        $cEnd = $appItem->cycle?->end_date ? \Carbon\Carbon::parse($appItem->cycle->end_date) : null;
        $pStart = $appItem->cycle?->performance_period_start ? \Carbon\Carbon::parse($appItem->cycle->performance_period_start) : null;
        $pEnd = $appItem->cycle?->performance_period_end ? \Carbon\Carbon::parse($appItem->cycle->performance_period_end) : null;

        $itemYears = [];
        if (!empty($appItem->cycle?->financial_year)) {
            $itemYears[] = $appItem->cycle->financial_year;
            if (preg_match_all('/\b(20\d{2})\b/', $appItem->cycle->financial_year, $m)) {
                foreach ($m[1] as $y) $itemYears[] = $y;
            }
        }
        if ($cStart) $itemYears[] = $cStart->format('Y');
        if ($cEnd) $itemYears[] = $cEnd->format('Y');
        if ($pStart) $itemYears[] = $pStart->format('Y');
        if ($pEnd) $itemYears[] = $pEnd->format('Y');
        if ($appItem->created_at) $itemYears[] = $appItem->created_at->format('Y');
        $itemYears = array_values(array_unique(array_filter($itemYears)));

        $itemMonths = [];
        if ($pStart && $pEnd) {
            $cur = $pStart->copy()->startOfMonth();
            $limit = $pEnd->copy()->endOfMonth();
            $loops = 0;
            while ($cur->lte($limit) && $loops < 24) {
                $itemMonths[] = $cur->month;
                $cur->addMonth();
                $loops++;
            }
        }
        if ($cStart && $cEnd) {
            $cur = $cStart->copy()->startOfMonth();
            $limit = $cEnd->copy()->endOfMonth();
            $loops = 0;
            while ($cur->lte($limit) && $loops < 24) {
                $itemMonths[] = $cur->month;
                $cur->addMonth();
                $loops++;
            }
        }
        if (empty($itemMonths)) {
            if ($cStart) $itemMonths[] = $cStart->month;
            if ($appItem->created_at) $itemMonths[] = $appItem->created_at->month;
        }
        $itemMonths = array_values(array_unique($itemMonths));

        $itemTrack = $appItem->cycle?->isMidYear() ? 'mid_year' : ($appItem->cycle?->cycle_type === 'year_end' ? 'year_end' : 'annual');

        $itemSearch = strtolower(implode(' ', array_filter([
            $appItem->cycle?->name,
            $appItem->cycle?->financial_year,
            $appItem->cycle?->performance_period,
            $appItem->reportingManager?->user?->name,
            $appItem->hod?->user?->name,
            $appItem->status,
            $appItem->performance_band,
        ])));

        $selfAppsMeta[] = [
            'id' => $appItem->id,
            'years' => $itemYears,
            'months' => $itemMonths,
            'track' => $itemTrack,
            'status' => $appItem->status,
            'search' => $itemSearch,
        ];

        foreach ($itemYears as $y) {
            $selfYearsList->push($y);
        }
    }

    $distinctYears = $selfYearsList->unique()->values();
@endphp

<script id="self-apps-meta-data" type="application/json">
    {!! json_encode($selfAppsMeta ?? []) !!}
</script>

<div class="space-y-6" x-data="appraisalPageData({
    activeTab: '{{ request('tab', ($role !== 'Employee' && $employee) ? 'my_appraisal' : 'roster') }}',
    selectedCategory: '{{ request('category', '') }}' || null,
    statusFilter: '{{ request('status', '') }}',
    deptFilter: '{{ request('department_id', '') }}'
})">

    <!-- ========================================================================= -->
    <!-- VIEW 1: CATEGORIES OVERVIEW / HUB (Shown when selectedCategory is null)   -->
    <!-- ========================================================================= -->
    <div x-show="!selectedCategory" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">
                    Performance Appraisals
                </h1>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                    Select an appraisal review track to view evaluation cycles, submission progress, and team rosters.
                </p>
            </div>

            @if(in_array($role, ['Super Admin', 'HR Lead']))
            <div class="flex flex-wrap items-center gap-2.5">
                <button @click="showCycleModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all shrink-0 cursor-pointer">
                    <i class="ph ph-plus-circle text-base"></i>
                    <span>New Appraisal Cycle</span>
                </button>
            </div>
            @endif
        </div>

        <!-- The 2 Category Cards (Click to open as one entire page) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Tile 1: Quarterly Reviews -->
            <button type="button"
                    @click="openCategory('quarterly')"
                    class="group w-full text-left bg-white dark:bg-[#111927] rounded-3xl p-6 sm:p-7 border border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer flex flex-col justify-between space-y-6">
                <div class="space-y-4 w-full">
                    <div class="flex items-center justify-between gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/70 dark:border-slate-700/60 flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                            <i class="ph ph-calendar-check text-2xl"></i>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            {{ $quarterlyTileCount }} {{ Str::plural('Appraisal', $quarterlyTileCount) }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Quarterly Reviews</h2>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Active</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium leading-relaxed">
                            Click to view all quarterly &amp; mid-year appraisals, KPI evaluations, and progress reviews.
                        </p>
                    </div>
                </div>

                <div class="w-full pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="ph ph-clock text-sm"></i>
                        <span>FY 2026/2027 Mid-Year Review</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 group-hover:translate-x-0.5 transition-all">
                        <span>Open Page</span>
                        <i class="ph ph-arrow-right"></i>
                    </span>
                </div>
            </button>

            <!-- Tile 2: Bi-Annual Reviews -->
            <button type="button"
                    @click="openCategory('biannual')"
                    class="group w-full text-left bg-white dark:bg-[#111927] rounded-3xl p-6 sm:p-7 border border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer flex flex-col justify-between space-y-6">
                <div class="space-y-4 w-full">
                    <div class="flex items-center justify-between gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/70 dark:border-slate-700/60 flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                            <i class="ph ph-chart-line-up text-2xl"></i>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            {{ $biAnnualTileCount }} {{ Str::plural('Appraisal', $biAnnualTileCount) }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Bi-Annual Reviews</h2>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                <span>Year-End</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium leading-relaxed">
                            Click to view all bi-annual &amp; year-end appraisals, final band scoring, promotions, and salary increments.
                        </p>
                    </div>
                </div>

                <div class="w-full pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="ph ph-clock text-sm"></i>
                        <span>FY 2026/2027 Year-End Review</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 group-hover:translate-x-0.5 transition-all">
                        <span>Open Page</span>
                        <i class="ph ph-arrow-right"></i>
                    </span>
                </div>
            </button>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- VIEW 2: DEDICATED CATEGORY PAGE (Shown when selectedCategory is set)      -->
    <!-- ========================================================================= -->
    <div x-show="selectedCategory" x-cloak class="space-y-6">
        <!-- Top Navigation Bar: Back to Categories, Title, Clean Cycle Indicator -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <button type="button" @click="closeCategory()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/80 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/60 transition-colors cursor-pointer">
                    <i class="ph ph-arrow-left font-bold"></i>
                    <span>All Categories</span>
                </button>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <h1 class="text-xl font-black text-slate-900 dark:text-white" x-text="selectedCategory === 'quarterly' ? 'Quarterly Reviews' : 'Bi-Annual Reviews'"></h1>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Clean, Neutral Cycle Indicator -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/60">
                    <i class="ph ph-calendar text-slate-400 text-sm"></i>
                    <span>{{ $quarterlyCycle?->financial_year ?? $biAnnualCycle?->financial_year ?? 'FY 2026/2027' }}</span>
                </div>

                @if(in_array($role, ['Super Admin', 'HR Lead']))
                <button @click="showCycleModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm shadow-blue-500/20 transition-all shrink-0 cursor-pointer">
                    <i class="ph ph-plus-circle text-base"></i>
                    <span>New Cycle</span>
                </button>
                @endif
            </div>
        </div>

        <!-- Category Cycle Information Banner -->
        <!-- Quarterly Review Cycle Header Banner -->
        <template x-if="selectedCategory === 'quarterly' && activeTab !== 'my_appraisal'">
            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 flex items-center justify-center shrink-0 shadow-xs font-bold text-xs">
                        Q1
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ $quarterlyCycle?->name ?? 'FY 2026/2027 Mid-Year Performance Review' }}</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Active Cycle</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                            Performance Period: <strong>{{ $quarterlyCycle?->performance_period ?? 'April to September' }}</strong> &bull; Review Focus: <strong>KPI Progress &amp; Adjustments (No Salary Increments)</strong>
                        </p>
                    </div>
                </div>
                <div class="text-[11px] font-bold text-slate-600 dark:text-slate-300 sm:text-right shrink-0">
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider">Review Window</span>
                    <span>01 Oct – 31 Oct 2026</span>
                </div>
            </div>
        </template>

        <!-- Bi-Annual Review Cycle Header Banner -->
        <template x-if="selectedCategory === 'biannual' && activeTab !== 'my_appraisal'">
            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 flex items-center justify-center shrink-0 shadow-xs font-bold text-xs">
                        Q2
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ $biAnnualCycle?->name ?? 'FY 2026/2027 Year-End Performance Review' }}</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                <span>Year-End Track</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                            Performance Period: <strong>{{ $biAnnualCycle?->performance_period ?? 'October to March' }}</strong> &bull; Review Focus: <strong>Final Band Scores, Promotions &amp; Increment Proposals</strong>
                        </p>
                    </div>
                </div>
                <div class="text-[11px] font-bold text-slate-600 dark:text-slate-300 sm:text-right shrink-0">
                    <span class="text-slate-400 block text-[10px] uppercase tracking-wider">Review Window</span>
                    <span>15 Feb – 15 Mar 2027</span>
                </div>
            </div>
        </template>

        <!-- Category Navigation Tabs -->
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-6 -mb-px">
                @if($role === 'Employee')
                    <button @click="activeTab = 'my_appraisal'" :class="activeTab === 'my_appraisal' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <i class="ph ph-clipboard-text text-sm"></i>
                        <span>My Appraisals (<span x-text="selfApps.length"></span>)</span>
                    </button>
                    <button @click="activeTab = 'my_changes'" :class="activeTab === 'my_changes' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <i class="ph ph-clock-counter-clockwise text-sm"></i>
                        <span>Changes Requested (<span x-text="selectedCategory === 'quarterly' ? '{{ $quarterlyChanges->count() }}' : '{{ $biAnnualChanges->count() }}'"></span>)</span>
                    </button>
                @else
                    @if($employee)
                        <button @click="activeTab = 'my_appraisal'" :class="activeTab === 'my_appraisal' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="ph ph-user text-sm"></i>
                            <span>My Self-Evaluation (<span x-text="selfApps.length"></span>)</span>
                        </button>
                    @endif

                    <button @click="activeTab = 'roster'" :class="activeTab === 'roster' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <i class="ph ph-list-dashes text-sm"></i>
                        <span>Appraisals Roster (<span x-text="selectedCategory === 'quarterly' ? '{{ $quarterlyAppraisals->count() }}' : '{{ $biAnnualAppraisals->count() }}'"></span>)</span>
                    </button>

                    <button @click="activeTab = 'letters'" :class="activeTab === 'letters' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <i class="ph ph-file-text text-sm"></i>
                        <span>Official Letters &amp; E-Signatures (<span x-text="selectedCategory === 'quarterly' ? '{{ $quarterlyLetters->count() }}' : '{{ $biAnnualLetters->count() }}'"></span>)</span>
                    </button>
                @endif
            </div>

            @if($role !== 'Employee')
            <!-- Filter bar (Roster only) -->
            <div x-show="activeTab === 'roster'" class="hidden md:flex items-center gap-2 pb-2">
                <input type="text" x-model="searchFilter" placeholder="Search employee..." class="text-xs bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-blue-500/20 text-slate-800 dark:text-slate-200">
                
                <select x-model="deptFilter" class="text-xs bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-blue-500/20 text-slate-800 dark:text-slate-200 font-bold">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->name }}">{{ $d->name }}</option>
                    @endforeach
                </select>

                <select x-model="statusFilter" class="text-xs bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-blue-500/20 text-slate-800 dark:text-slate-200 font-bold">
                    <option value="">All Statuses</option>
                    <option value="pending_self_eval">Pending Self-Evaluation</option>
                    <option value="pending_manager_eval">Pending Manager Review</option>
                    <option value="pending_hod_eval">Pending HOD Review</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            @endif
        </div>

        <!-- ========================================================= -->
        <!-- TAB CONTENT: MY SELF-EVALUATION (All Appraisals of Individual) -->
        <!-- ========================================================= -->
        <div x-show="activeTab === 'my_appraisal'" class="space-y-4">
            @if($selfList->isEmpty())
                <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-12 text-center space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-2xl">
                        <i class="ph ph-clipboard-text"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Self-Evaluations Assigned</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">You do not currently have any performance evaluations assigned to you across active review tracks.</p>
                </div>
            @else
                <!-- Filter Bar: Compact Single-Row Layout -->
                <div class="bg-white dark:bg-[#0f1623] border border-slate-200 dark:border-slate-800/80 rounded-2xl p-3.5 sm:p-4 shadow-xs">
                    <!-- Top Bar: Title, Count, Subtle Reset -->
                    <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-100 dark:border-slate-800/60">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-funnel text-slate-400 text-xs"></i>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Filter My Appraisals</span>
                            <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500" x-text="'(Showing ' + filteredSelfCount + ' of ' + selfApps.length + ')'"></span>
                        </div>

                        <!-- Subtle Reset Button -->
                        <button type="button"
                                x-show="hasActiveSelfFilters"
                                x-cloak
                                @click="resetSelfFilters()"
                                class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 dark:text-slate-400 dark:hover:text-rose-400 flex items-center gap-1 transition-colors cursor-pointer">
                            <i class="ph ph-arrow-counter-clockwise text-xs"></i>
                            <span>Reset</span>
                        </button>
                    </div>

                    <!-- 6 Filter Inputs in Tight Balanced Row (Search first on the left) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5">
                        <!-- 1. Search Bar (Left, wider) -->
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Search</label>
                            <div class="relative">
                                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" x-model="selfSearch" placeholder="Cycle name, manager..." class="w-full text-xs bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/60 rounded-xl pl-8 pr-2.5 py-1.5 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- 2. Year Filter -->
                        <div class="lg:col-span-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Year</label>
                            <select x-model="selfYearFilter" class="w-full text-xs bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/60 rounded-xl px-2.5 py-1.5 text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <option value="">All Years</option>
                                @foreach($distinctYears as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 3. From Month -->
                        <div class="lg:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">From Month</label>
                            <select x-model="selfMonthFrom" class="w-full text-xs bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/60 rounded-xl px-2.5 py-1.5 text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <option value="">All (Start)</option>
                                <option value="1">Jan (01)</option>
                                <option value="2">Feb (02)</option>
                                <option value="3">Mar (03)</option>
                                <option value="4">Apr (04)</option>
                                <option value="5">May (05)</option>
                                <option value="6">Jun (06)</option>
                                <option value="7">Jul (07)</option>
                                <option value="8">Aug (08)</option>
                                <option value="9">Sep (09)</option>
                                <option value="10">Oct (10)</option>
                                <option value="11">Nov (11)</option>
                                <option value="12">Dec (12)</option>
                            </select>
                        </div>

                        <!-- 4. To Month -->
                        <div class="lg:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">To Month</label>
                            <select x-model="selfMonthTo" class="w-full text-xs bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/60 rounded-xl px-2.5 py-1.5 text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <option value="">All (End)</option>
                                <option value="1">Jan (01)</option>
                                <option value="2">Feb (02)</option>
                                <option value="3">Mar (03)</option>
                                <option value="4">Apr (04)</option>
                                <option value="5">May (05)</option>
                                <option value="6">Jun (06)</option>
                                <option value="7">Jul (07)</option>
                                <option value="8">Aug (08)</option>
                                <option value="9">Sep (09)</option>
                                <option value="10">Oct (10)</option>
                                <option value="11">Nov (11)</option>
                                <option value="12">Dec (12)</option>
                            </select>
                        </div>

                        <!-- 5. Review Track -->
                        <div class="lg:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Review Track</label>
                            <select x-model="selfTrackFilter" class="w-full text-xs bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/60 rounded-xl px-2.5 py-1.5 text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <option value="">All Tracks</option>
                                <option value="mid_year">Quarter 1 &bull; Mid-Year</option>
                                <option value="year_end">Quarter 2 &bull; Year-End</option>
                                <option value="annual">Annual / Other</option>
                            </select>
                        </div>

                        <!-- 6. Evaluation Status -->
                        <div class="lg:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Status</label>
                            <select x-model="selfStatusFilter" class="w-full text-xs bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/60 rounded-xl px-2.5 py-1.5 text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <option value="">All Statuses</option>
                                <option value="pending_self_eval">Pending Self-Eval</option>
                                <option value="in_review">In Review (Manager/HOD)</option>
                                <option value="completed">Finalized</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Unified Appraisals Table for Individual -->
                <div class="bg-white dark:bg-[#0f1623] border border-slate-200 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 dark:bg-[#0b1019] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800/80">
                                <tr>
                                    <th class="py-3 px-4 pl-5">Review Cycle &amp; Track</th>
                                    <th class="py-3 px-3">Review Window &amp; Period</th>
                                    <th class="py-3 px-3">Workflow Evaluators</th>
                                    <th class="py-3 px-3">Evaluation Status</th>
                                    <th class="py-3 px-3">Score &amp; Band</th>
                                    <th class="py-3 px-3">Due Date</th>
                                    <th class="py-3 px-4 pr-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                @foreach($selfList as $app)
                                    @php
                                        $meta = collect($selfAppsMeta)->firstWhere('id', $app->id) ?? [];
                                        $appYears = $meta['years'] ?? [];
                                        $appMonths = $meta['months'] ?? [];
                                        $trackType = $meta['track'] ?? 'annual';
                                        $searchString = $meta['search'] ?? '';

                                        $cStart = $app->cycle?->start_date ? \Carbon\Carbon::parse($app->cycle->start_date)->startOfDay() : null;
                                        $cEnd = $app->cycle?->end_date ? \Carbon\Carbon::parse($app->cycle->end_date)->startOfDay() : null;
                                        $pStart = $app->cycle?->performance_period_start ? \Carbon\Carbon::parse($app->cycle->performance_period_start) : null;
                                        $pEnd = $app->cycle?->performance_period_end ? \Carbon\Carbon::parse($app->cycle->performance_period_end) : null;

                                        $dueDate = $app->self_eval_deadline ? \Carbon\Carbon::parse($app->self_eval_deadline)->startOfDay() : ($cEnd ?: null);
                                        $today = \Carbon\Carbon::now()->startOfDay();

                                        $dueRelativeText = '';
                                        $dueRelativeClass = 'text-slate-400 dark:text-slate-500';

                                        if ($app->status === 'completed') {
                                            $dueRelativeText = 'Finalized';
                                        } elseif ($cStart && $cStart->isFuture()) {
                                            $dueRelativeText = 'Opens ' . $cStart->format('d M Y');
                                            $dueRelativeClass = 'text-slate-400 dark:text-slate-400 font-medium';
                                        } elseif ($dueDate) {
                                            if ($dueDate->isPast()) {
                                                $days = (int) $dueDate->diffInDays($today);
                                                $dueRelativeText = $days === 0 ? 'Overdue today' : ($days === 1 ? 'Overdue by 1 day' : "Overdue by {$days} days");
                                                $dueRelativeClass = 'text-rose-500/90 dark:text-rose-400/90 font-medium';
                                            } elseif ($dueDate->isToday()) {
                                                $dueRelativeText = 'Due today';
                                                $dueRelativeClass = 'text-amber-500 dark:text-amber-400 font-medium';
                                            } else {
                                                $days = (int) $today->diffInDays($dueDate);
                                                $dueRelativeText = $days === 1 ? 'Due tomorrow' : "Due in {$days} days";
                                                $dueRelativeClass = 'text-amber-500/90 dark:text-amber-400/90 font-medium';
                                            }
                                        } else {
                                            $dueRelativeText = 'No deadline';
                                        }

                                        $isPrimaryAction = ($app->cycle?->status === 'Active' || ($dueDate && $dueDate->isPast())) && $app->status === 'pending_self_eval';
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-[#161f30] transition-colors"
                                        x-show="matchesSelfAppraisal({{ json_encode($appYears) }}, {{ json_encode($appMonths) }}, '{{ $trackType }}', '{{ $app->status }}', '{{ addslashes($searchString) }}')">
                                        
                                        <!-- 1. Review Cycle & Track -->
                                        <td class="py-3.5 px-4 pl-5">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <a href="{{ route('appraisal.show', $app->id) }}" class="font-bold text-slate-900 dark:text-slate-100 hover:text-blue-600 dark:hover:text-blue-400 text-xs sm:text-sm block transition-colors">
                                                        {{ $app->cycle?->name ?? 'Performance Review' }}
                                                    </a>
                                                    @if($app->cycle?->status === 'Active')
                                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-[#10b981] shrink-0"></span>
                                                            <span>Active</span>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-1.5 flex-wrap text-[11px] pt-0.5">
                                                    @if($app->cycle?->isMidYear())
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                            <i class="ph ph-calendar-check text-slate-400"></i>
                                                            <span>Quarter 1 &bull; Mid-Year</span>
                                                        </span>
                                                    @elseif($app->cycle?->cycle_type === 'year_end')
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                            <i class="ph ph-chart-line-up text-slate-400"></i>
                                                            <span>Quarter 2 &bull; Year-End</span>
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                            <i class="ph ph-calendar text-slate-400"></i>
                                                            <span>Annual Review</span>
                                                        </span>
                                                    @endif

                                                    @if($app->cycle?->financial_year)
                                                        <span class="px-2 py-0.5 rounded-md font-medium bg-slate-100 dark:bg-white/[0.03] text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-800/80">
                                                            {{ $app->cycle->financial_year }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 2. Performance Period & Dates -->
                                        <td class="py-3.5 px-3">
                                            <div class="space-y-0.5">
                                                <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1 text-[11px]">
                                                    <i class="ph ph-clock text-slate-400 text-xs"></i>
                                                    <span>{{ $app->cycle?->performance_period ?: ($app->cycle?->isMidYear() ? 'April to September' : ($pStart && $pEnd ? $pStart->format('M Y') . ' – ' . $pEnd->format('M Y') : 'Full Period')) }}</span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                                                    @if($cStart && $cEnd)
                                                        <span>Window: {{ $cStart->format('d M') }} – {{ $cEnd->format('d M Y') }}</span>
                                                    @else
                                                        <span>Window: Active</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 3. Workflow Evaluators -->
                                        <td class="py-3.5 px-3">
                                            <div class="space-y-1 text-[11px]">
                                                @if($app->reportingManager)
                                                    <div class="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-300 text-[9px] font-bold border border-slate-200 dark:border-slate-700/60">MGR</span>
                                                        <span>{{ $app->reportingManager->user->name ?? 'Manager' }}</span>
                                                    </div>
                                                @endif
                                                @if($app->hod)
                                                    <div class="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-300 text-[9px] font-bold border border-slate-200 dark:border-slate-700/60">HOD</span>
                                                        <span>{{ $app->hod->user->name ?? 'HOD' }}</span>
                                                    </div>
                                                @endif
                                                @if(!$app->reportingManager && !$app->hod)
                                                    <span class="text-slate-400 dark:text-slate-500 italic text-[11px]">Self Direct</span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- 4. Evaluation Status -->
                                        <td class="py-3.5 px-3">
                                            @if($app->status === 'completed')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-[#10b981] shrink-0"></span>
                                                    <span>Finalized</span>
                                                </span>
                                            @elseif($app->status === 'pending_self_eval')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-[#d97706] shrink-0"></span>
                                                    <span>Pending Self-Eval</span>
                                                </span>
                                            @elseif($app->status === 'pending_manager_eval' || $app->status === 'submitted_self_eval')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400 shrink-0"></span>
                                                    <span>Manager Review</span>
                                                </span>
                                            @elseif($app->status === 'pending_hod_eval' || $app->status === 'submitted_manager_eval')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400 shrink-0"></span>
                                                    <span>HOD Review</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
                                                    <span>In Review</span>
                                                </span>
                                            @endif
                                        </td>

                                        <!-- 5. Score & Band -->
                                        <td class="py-3.5 px-3">
                                            @if($app->total_weighted_score > 0)
                                                <div class="space-y-0.5">
                                                    <div class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1 text-xs">
                                                        <span>{{ number_format($app->total_weighted_score, 2) }}</span>
                                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-normal">/ 5.00</span>
                                                    </div>
                                                    @if($app->performance_band)
                                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                            {{ $app->performance_band }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-slate-400 dark:text-slate-500 text-xs font-normal">—</span>
                                            @endif
                                        </td>

                                        <!-- 6. Due Date -->
                                        <td class="py-3.5 px-3">
                                            <div class="space-y-0.5">
                                                <span class="font-bold text-slate-800 dark:text-slate-200 block text-xs">
                                                    {{ $dueDate ? $dueDate->format('d M Y') : '—' }}
                                                </span>
                                                <span class="text-[10px] block {{ $dueRelativeClass }}">
                                                    {{ $dueRelativeText }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- 7. Actions -->
                                        <td class="py-3.5 px-4 pr-5 text-right">
                                            <div class="flex items-center justify-end">
                                                @if($isPrimaryAction)
                                                    <a href="{{ route('appraisal.show', $app->id) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition-all inline-flex items-center gap-1.5 cursor-pointer shrink-0">
                                                        <span>Complete Form</span>
                                                        <i class="ph ph-arrow-square-out text-xs"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('appraisal.show', $app->id) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-medium border border-slate-300 hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-600 text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-all inline-flex items-center gap-1.5 cursor-pointer shrink-0">
                                                        <span>{{ $app->cycle?->status === 'Upcoming' ? 'View Form' : ($app->status === 'pending_self_eval' ? 'Complete Form' : 'View Form') }}</span>
                                                        <i class="ph ph-arrow-square-out text-xs"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Empty State when filters yield 0 results -->
                                <tr x-show="filteredSelfCount === 0">
                                    <td colspan="7" class="p-10 text-center">
                                        <div class="space-y-2">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-lg">
                                                <i class="ph ph-funnel-x"></i>
                                            </div>
                                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">No appraisals match the selected filters</p>
                                            <p class="text-[11px] text-slate-400">Try adjusting your year, month range, or review track filters.</p>
                                            <button type="button" @click="resetSelfFilters()" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline pt-1 cursor-pointer">
                                                <i class="ph ph-arrow-counter-clockwise"></i>
                                                <span>Reset All Filters</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- ========================================================= -->
        <!-- TAB CONTENT: APPRAISALS ROSTER (For Management / HR)       -->
        <!-- ========================================================= -->
        @if($role !== 'Employee')
        <div x-show="activeTab === 'roster'" class="space-y-4">
            <!-- 1. Quarterly Roster Table -->
            <template x-if="selectedCategory === 'quarterly'">
                <div>
                    @if($quarterlyAppraisals->isEmpty())
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-2xl">
                                <i class="ph ph-calendar-check"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Quarterly Appraisals Found</h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">No staff performance evaluations found in the quarterly review track.</p>
                        </div>
                    @else
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                                        <tr>
                                            <th class="p-3.5 pl-5">Employee &amp; Designation</th>
                                            <th class="p-3.5">Department</th>
                                            <th class="p-3.5">Workflow Hierarchy</th>
                                            <th class="p-3.5">Evaluation Status</th>
                                            <th class="p-3.5">Score &amp; Band</th>
                                            <th class="p-3.5">Review Focus</th>
                                            <th class="p-3.5 pr-5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                        @foreach($quarterlyAppraisals as $app)
                                             @php $empUser = $app->employee?->user; @endphp
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                                x-show="(!statusFilter || '{{ $app->status }}' === statusFilter) && (!deptFilter || '{{ $app->employee?->department?->name }}' === deptFilter)">
                                                <td class="p-3.5 pl-5">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-full bg-slate-700 dark:bg-slate-700 text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-sm">
                                                            {{ strtoupper(substr($empUser?->name ?? 'E', 0, 2)) }}
                                                        </div>
                                                        <div>
                                                            <a href="{{ route('appraisal.show', $app->id) }}" class="font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 block transition-colors">
                                                                {{ $empUser?->name ?? 'Staff Employee' }}
                                                            </a>
                                                            <div class="text-[11px] text-slate-400 font-semibold flex items-center gap-1.5 mt-0.5">
                                                                <span>{{ $app->employee?->designation?->name ?? 'Staff' }}</span>
                                                                <span>&bull;</span>
                                                                <span>{{ $app->employee?->employee_id_number ?? 'EMP-00' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-3.5 font-bold text-slate-700 dark:text-slate-300">
                                                    {{ $app->employee?->department?->name ?? 'General' }}
                                                </td>
                                                <td class="p-3.5">
                                                    <div class="space-y-1">
                                                        @if($app->workflow_type === 'three_tier' && $app->reportingManager)
                                                            <div class="text-[11px] font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">Mgr:</span> {{ $app->reportingManager->user->name ?? 'Manager' }}
                                                            </div>
                                                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}
                                                            </div>
                                                        @else
                                                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">Direct HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->status === 'completed')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Finalized
                                                        </span>
                                                    @elseif($app->status === 'pending_self_eval')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Self-Eval
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> In Review
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->total_weighted_score > 0)
                                                        <div class="space-y-1">
                                                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                                                <span>{{ number_format($app->total_weighted_score, 2) }}</span>
                                                                <span class="text-[10px] text-slate-400">/ 5.00</span>
                                                            </div>
                                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                                {{ $app->performance_band }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400 text-xs font-semibold">—</span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full border border-slate-200 dark:border-slate-700" title="Mid-Year Review focuses on progress and KPIs without salary increments.">
                                                        <i class="ph ph-prohibit text-xs"></i> No Inc (Mid-Year)
                                                    </span>
                                                </td>
                                                <td class="p-3.5 pr-5 text-right">
                                                    <a href="{{ route('appraisal.show', $app->id) }}" class="bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-xl font-bold transition-all text-xs inline-flex items-center gap-1.5">
                                                        <span>Review</span>
                                                        <i class="ph ph-arrow-right text-xs"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </template>

            <!-- 2. Bi-Annual Roster Table -->
            <template x-if="selectedCategory === 'biannual'">
                <div>
                    @if($biAnnualAppraisals->isEmpty())
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-2xl">
                                <i class="ph ph-chart-line-up"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Bi-Annual Appraisals Found</h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">No staff performance evaluations found in the bi-annual / year-end review track.</p>
                        </div>
                    @else
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                                        <tr>
                                            <th class="p-3.5 pl-5">Employee &amp; Designation</th>
                                            <th class="p-3.5">Department</th>
                                            <th class="p-3.5">Workflow Hierarchy</th>
                                            <th class="p-3.5">Evaluation Status</th>
                                            <th class="p-3.5">Score &amp; Band</th>
                                            <th class="p-3.5">Increment / Promo</th>
                                            <th class="p-3.5 pr-5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                        @foreach($biAnnualAppraisals as $app)
                                            @php $empUser = $app->employee?->user; @endphp
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                                x-show="(!statusFilter || '{{ $app->status }}' === statusFilter) && (!deptFilter || '{{ $app->employee?->department?->name }}' === deptFilter)">
                                                <td class="p-3.5 pl-5">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-full bg-slate-700 dark:bg-slate-700 text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-sm">
                                                            {{ strtoupper(substr($empUser?->name ?? 'E', 0, 2)) }}
                                                        </div>
                                                        <div>
                                                            <a href="{{ route('appraisal.show', $app->id) }}" class="font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 block transition-colors">
                                                                {{ $empUser?->name ?? 'Staff Employee' }}
                                                            </a>
                                                            <div class="text-[11px] text-slate-400 font-semibold flex items-center gap-1.5 mt-0.5">
                                                                <span>{{ $app->employee?->designation?->name ?? 'Staff' }}</span>
                                                                <span>&bull;</span>
                                                                <span>{{ $app->employee?->employee_id_number ?? 'EMP-00' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-3.5 font-bold text-slate-700 dark:text-slate-300">
                                                    {{ $app->employee?->department?->name ?? 'General' }}
                                                </td>
                                                <td class="p-3.5">
                                                    <div class="space-y-1">
                                                        @if($app->workflow_type === 'three_tier' && $app->reportingManager)
                                                            <div class="text-[11px] font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">Mgr:</span> {{ $app->reportingManager->user->name ?? 'Manager' }}
                                                            </div>
                                                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}
                                                            </div>
                                                        @else
                                                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">Direct HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->status === 'completed')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Finalized
                                                        </span>
                                                    @elseif($app->status === 'pending_self_eval')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Self-Eval
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> In Review
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->total_weighted_score > 0)
                                                        <div class="space-y-1">
                                                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                                                <span>{{ number_format($app->total_weighted_score, 2) }}</span>
                                                                <span class="text-[10px] text-slate-400">/ 5.00</span>
                                                            </div>
                                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                                {{ $app->performance_band }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400 text-xs font-semibold">—</span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->increment_percentage > 0)
                                                        <div class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                            <i class="ph ph-trend-up"></i>
                                                            <span>+{{ number_format($app->increment_percentage, 1) }}% Inc</span>
                                                        </div>
                                                    @elseif($app->is_promoted)
                                                        <div class="font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                                            <i class="ph ph-crown"></i>
                                                            <span>Promoted</span>
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400 text-[10px]">—</span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5 pr-5 text-right">
                                                    <a href="{{ route('appraisal.show', $app->id) }}" class="bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-xl font-bold transition-all text-xs inline-flex items-center gap-1.5">
                                                        <span>Review</span>
                                                        <i class="ph ph-arrow-right text-xs"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </template>
        </div>
        @endif

        <!-- ========================================================= -->
        <!-- TAB CONTENT: OFFICIAL LETTERS & E-SIGNATURES              -->
        <!-- ========================================================= -->
        @if($role !== 'Employee')
        <div x-show="activeTab === 'letters'" class="space-y-4">
            <template x-if="selectedCategory === 'quarterly'">
                <div>
                    @if($quarterlyLetters->isEmpty())
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-2xl">
                                <i class="ph ph-stamp"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Quarterly Letters Generated</h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">No official appraisal letters have been generated for quarterly reviews.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($quarterlyLetters as $ltr)
                                <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 hover:border-blue-400/40 transition-all">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-lg font-bold border border-slate-200 dark:border-slate-700 shrink-0">
                                                <i class="ph ph-file-text"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">{{ $ltr->employee?->user?->name ?? 'Staff Employee' }}</h4>
                                                <span class="text-[11px] text-slate-400">{{ $ltr->letter_type }} &bull; {{ $ltr->letter_number }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                                        <span class="text-[11px] text-slate-400 font-medium">{{ $ltr->created_at->format('d M Y') }}</span>
                                        <a href="{{ route('appraisal.show', $ltr->appraisal_id ?? 1) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">View Letter &rarr;</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </template>

            <template x-if="selectedCategory === 'biannual'">
                <div>
                    @if($biAnnualLetters->isEmpty())
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-2xl">
                                <i class="ph ph-stamp"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Bi-Annual Letters Generated</h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">No official promotion or increment letters have been generated for bi-annual reviews yet.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($biAnnualLetters as $ltr)
                                <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 hover:border-blue-400/40 transition-all">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-lg font-bold border border-slate-200 dark:border-slate-700 shrink-0">
                                                <i class="ph ph-file-text"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">{{ $ltr->employee?->user?->name ?? 'Staff Employee' }}</h4>
                                                <span class="text-[11px] text-slate-400">{{ $ltr->letter_type }} &bull; {{ $ltr->letter_number }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                                        <span class="text-[11px] text-slate-400 font-medium">{{ $ltr->created_at->format('d M Y') }}</span>
                                        <a href="{{ route('appraisal.show', $ltr->appraisal_id ?? 1) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">View Letter &rarr;</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </template>
        </div>
        @endif

        <!-- ========================================================= -->
        <!-- TAB CONTENT: CHANGES REQUESTED (For Employees)            -->
        <!-- ========================================================= -->
        @if($role === 'Employee')
        <div x-show="activeTab === 'my_changes'" class="space-y-4">
            <template x-if="selectedCategory === 'quarterly'">
                <div>
                    @if($quarterlyChanges->isEmpty())
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-xl">
                                <i class="ph ph-shield-check"></i>
                            </div>
                            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">No Changes Requested</h4>
                            <p class="text-[11px] text-slate-400 max-w-sm mx-auto">You have not submitted any edit or unlock requests for your quarterly evaluation forms.</p>
                        </div>
                    @else
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-6 shadow-sm divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            @foreach($quarterlyChanges as $cr)
                                <div class="py-4 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-extrabold text-slate-900 dark:text-white">{{ $cr->appraisal?->cycle?->name ?? 'Quarterly Review' }}</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">{{ $cr->status }}</span>
                                    </div>
                                    <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl text-slate-700 dark:text-slate-300">
                                        <strong>Reason for Request:</strong> {{ $cr->reason }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </template>

            <template x-if="selectedCategory === 'biannual'">
                <div>
                    @if($biAnnualChanges->isEmpty())
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-xl">
                                <i class="ph ph-shield-check"></i>
                            </div>
                            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">No Changes Requested</h4>
                            <p class="text-[11px] text-slate-400 max-w-sm mx-auto">You have not submitted any edit or unlock requests for your bi-annual evaluation forms.</p>
                        </div>
                    @else
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-6 shadow-sm divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            @foreach($biAnnualChanges as $cr)
                                <div class="py-4 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-extrabold text-slate-900 dark:text-white">{{ $cr->appraisal?->cycle?->name ?? 'Bi-Annual Review' }}</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">{{ $cr->status }}</span>
                                    </div>
                                    <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl text-slate-700 dark:text-slate-300">
                                        <strong>Reason for Request:</strong> {{ $cr->reason }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </template>
        </div>
        @endif

    </div>


    <!-- ========================================================= -->
    <!-- MODAL: Create Appraisal Cycle                             -->
    <!-- ========================================================= -->
    <div x-show="showCycleModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showCycleModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-2xl w-full shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto"
             x-data="cyclePresetForm()">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                        <i class="ph ph-calendar-plus text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">Create Appraisal Cycle</h3>
                        <p class="text-[11px] text-slate-400 font-semibold">Select review structure preset to auto-configure windows, periods &amp; increment rules</p>
                    </div>
                </div>
                <button type="button" @click="showCycleModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('appraisal.cycles.store') }}" method="POST" class="space-y-4 text-xs font-bold">
                @csrf

                <!-- Presets Switcher: Mid-Year vs Year-End -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-2">Cycle Architecture Preset</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Option 1: Mid-Year Review -->
                        <div @click="applyPreset('mid_year')" 
                             :class="cycleType === 'mid_year' ? 'border-blue-600 bg-blue-50/40 dark:bg-slate-800 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 bg-slate-50/50 dark:bg-slate-900/20'"
                             class="p-3.5 rounded-2xl border transition-all cursor-pointer space-y-1.5 relative">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <i class="ph ph-calendar-check text-base text-blue-600 dark:text-blue-400"></i>
                                    1. Mid-Year Review
                                </span>
                                <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                    Oct 01 – Oct 31
                                </span>
                            </div>
                            <p class="text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                Performance: <strong>April to September</strong>
                            </p>
                            <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 leading-snug">
                                Progress, improvement areas &amp; KPI adjustments. <strong>No salary increments.</strong>
                            </p>
                        </div>

                        <!-- Option 2: Year-End Review -->
                        <div @click="applyPreset('year_end')" 
                             :class="cycleType === 'year_end' ? 'border-blue-600 bg-blue-50/40 dark:bg-slate-800 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 bg-slate-50/50 dark:bg-slate-900/20'"
                             class="p-3.5 rounded-2xl border transition-all cursor-pointer space-y-1.5 relative">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <i class="ph ph-trend-up text-base text-blue-600 dark:text-blue-400"></i>
                                    2. Year-End Review
                                </span>
                                <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                    Feb 15 – Mar 15
                                </span>
                            </div>
                            <p class="text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                Performance: <strong>October to March</strong>
                            </p>
                            <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 leading-snug">
                                Final performance evaluation, potential promotions &amp; increments (aligned with approved budget).
                            </p>
                        </div>
                    </div>
                    <input type="hidden" name="cycle_type" :value="cycleType">
                </div>

                <!-- Cycle Name & Financial Year -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Cycle Name</label>
                        <input type="text" name="name" x-model="name" required class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Financial Year</label>
                        <input type="text" name="financial_year" x-model="financialYear" @input="updateCycleName()" placeholder="e.g. FY 2026/2027" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Review Period & Performance Period -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Review Period Type</label>
                        <select name="review_period" x-model="reviewPeriod" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="Mid-Year">Mid-Year</option>
                            <option value="Year-End">Year-End</option>
                            <option value="Annual">Annual</option>
                            <option value="Probation">Probationary</option>
                            <option value="Quarterly">Quarterly</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Covered Performance Period</label>
                        <input type="text" name="performance_period" x-model="perfPeriod" required class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Review Window (Start & End Dates) -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Review Window: Start Date</label>
                        <input type="date" name="start_date" x-model="startDate" required class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Review Window: End Date</label>
                        <input type="date" name="end_date" x-model="endDate" required class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Deadlines -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Self-Eval Deadline</label>
                        <input type="date" name="self_eval_deadline" x-model="selfEvalDeadline" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Manager / HOD Deadline</label>
                        <input type="date" name="manager_eval_deadline" x-model="managerEvalDeadline" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Compensation Policy Indicator -->
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <i :class="cycleType === 'mid_year' ? 'ph-prohibit text-slate-500 dark:text-slate-400' : 'ph-check-circle text-emerald-500'" class="ph text-lg"></i>
                        <div>
                            <span class="text-xs font-bold block text-slate-800 dark:text-slate-200">
                                <template x-if="cycleType === 'mid_year'">
                                    <span>Salary Increments: <strong>Excluded (Mid-Year Policy)</strong></span>
                                </template>
                                <template x-if="cycleType !== 'mid_year'">
                                    <span>Salary Increments &amp; Promotions: <strong>Enabled</strong></span>
                                </template>
                            </span>
                            <span class="text-[10px] font-normal text-slate-500 dark:text-slate-400">
                                <template x-if="cycleType === 'mid_year'">
                                    <span>Mid-Year reviews strictly focus on KPI progress, areas for improvement, and adjustments without salary increments.</span>
                                </template>
                                <template x-if="cycleType !== 'mid_year'">
                                    <span>Increment recommendations considered alongside financial performance and approved budget.</span>
                                </template>
                            </span>
                        </div>
                    </div>
                    <input type="hidden" name="allow_increments" :value="cycleType === 'mid_year' ? 0 : 1">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Focus Areas &amp; Guidelines</label>
                    <textarea name="description" x-model="description" rows="2" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
                </div>

                <label class="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer">
                    <input type="checkbox" name="initialize_all_employees" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Automatically initialize evaluation forms for all active employees
                    </span>
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showCycleModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 cursor-pointer">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold cursor-pointer">Launch Cycle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL: Send Reminders                                     -->
    <!-- ========================================================= -->
    <div x-show="showReminderModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showReminderModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-bell-ringing text-lg text-indigo-500"></i>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Dispatch Reminders</h3>
                </div>
                <button @click="showReminderModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x"></i></button>
            </div>

            <form action="{{ route('appraisal.reminders.send') }}" method="POST" class="space-y-4 text-xs font-bold">
                @csrf
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Target Group</label>
                    <select name="reminder_type" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="pending_self_eval">Pending Self-Evaluations (Employees)</option>
                        <option value="pending_manager_review">Pending Manager &amp; HOD Reviews</option>
                    </select>
                </div>

                <p class="text-[11px] font-semibold text-slate-400">
                    Dispatches in-system bell alerts and registered notifications to pending users with a direct evaluation link.
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showReminderModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                        <i class="ph ph-paper-plane-tilt"></i>
                        <span>Send Notifications</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function cyclePresetForm() {
    const currentYear = {{ (int)date('Y') }};
    const nextYear = currentYear + 1;
    return {
        cycleType: 'mid_year',
        name: 'FY ' + currentYear + '/' + nextYear + ' Mid-Year Performance Review',
        financialYear: 'FY ' + currentYear + '/' + nextYear,
        reviewPeriod: 'Mid-Year',
        perfPeriod: 'April to September',
        startDate: currentYear + '-10-01',
        endDate: currentYear + '-10-31',
        selfEvalDeadline: currentYear + '-10-20',
        managerEvalDeadline: currentYear + '-10-31',
        description: 'Covers the performance period from April to September. Focuses on reviewing employee progress, identifying areas for improvement, and setting or adjusting KPIs for the remainder of the year. This review will not involve salary increments.',

        updateCycleName() {
            if (this.cycleType === 'mid_year') {
                this.name = (this.financialYear ? this.financialYear + ' ' : '') + 'Mid-Year Performance Review';
            } else {
                this.name = (this.financialYear ? this.financialYear + ' ' : '') + 'Year-End Performance Review';
            }
        },

        applyPreset(type) {
            this.cycleType = type;
            if (type === 'mid_year') {
                this.reviewPeriod = 'Mid-Year';
                this.name = (this.financialYear ? this.financialYear + ' ' : '') + 'Mid-Year Performance Review';
                this.perfPeriod = 'April to September';
                this.startDate = currentYear + '-10-01';
                this.endDate = currentYear + '-10-31';
                this.selfEvalDeadline = currentYear + '-10-20';
                this.managerEvalDeadline = currentYear + '-10-31';
                this.description = 'Covers the performance period from April to September. Focuses on reviewing employee progress, identifying areas for improvement, and setting or adjusting KPIs for the remainder of the year. This review will not involve salary increments.';
            } else {
                this.reviewPeriod = 'Year-End';
                this.name = (this.financialYear ? this.financialYear + ' ' : '') + 'Year-End Performance Review';
                this.perfPeriod = 'October to March';
                this.startDate = nextYear + '-02-15';
                this.endDate = nextYear + '-03-15';
                this.selfEvalDeadline = nextYear + '-03-01';
                this.managerEvalDeadline = nextYear + '-03-15';
                this.description = 'Covers the performance period from October to March. Focuses on the final performance evaluation, identifying employees for potential promotions and increments. Increment recommendations will be considered alongside the company\'s financial performance and approved budget.';
            }
        }
    };
}

function appraisalPageData(config) {
    let selfAppsData = config.selfApps;
    if (!selfAppsData || !Array.isArray(selfAppsData)) {
        const el = document.getElementById('self-apps-meta-data');
        if (el && el.textContent) {
            try {
                selfAppsData = JSON.parse(el.textContent);
            } catch (e) {
                console.error("Failed to parse self-apps-meta-data", e);
                selfAppsData = [];
            }
        } else {
            selfAppsData = [];
        }
    }

    return {
        activeTab: config.activeTab || 'my_appraisal',
        selectedCategory: config.selectedCategory || null,
        showCycleModal: false,
        showReminderModal: false,
        statusFilter: config.statusFilter || '',
        deptFilter: config.deptFilter || '',
        searchFilter: '',

        // Individual Self-Evaluation Dynamic Filters
        selfApps: selfAppsData,
        selfYearFilter: '',
        selfMonthFrom: '',
        selfMonthTo: '',
        selfTrackFilter: '',
        selfStatusFilter: '',
        selfSearch: '',

        get hasActiveSelfFilters() {
            return !!(this.selfYearFilter || this.selfMonthFrom || this.selfMonthTo || this.selfTrackFilter || this.selfStatusFilter || this.selfSearch);
        },

        resetSelfFilters() {
            this.selfYearFilter = '';
            this.selfMonthFrom = '';
            this.selfMonthTo = '';
            this.selfTrackFilter = '';
            this.selfStatusFilter = '';
            this.selfSearch = '';
        },

        filterSelfYear(appYears) {
            if (!this.selfYearFilter) return true;
            return appYears.some(function(y) {
                return String(y).toLowerCase().includes(this.selfYearFilter.toLowerCase());
            }.bind(this));
        },

        filterSelfMonth(appMonths) {
            if (!this.selfMonthFrom && !this.selfMonthTo) return true;
            const from = this.selfMonthFrom ? parseInt(this.selfMonthFrom) : null;
            const to = this.selfMonthTo ? parseInt(this.selfMonthTo) : null;

            if (from && to) {
                let targetMonths = [];
                if (from <= to) {
                    for (let m = from; m <= to; m++) targetMonths.push(m);
                } else {
                    for (let m = from; m <= 12; m++) targetMonths.push(m);
                    for (let m = 1; m <= to; m++) targetMonths.push(m);
                }
                return appMonths.some(function(m) { return targetMonths.includes(m); });
            }
            if (from) return appMonths.some(function(m) { return m >= from; });
            if (to) return appMonths.some(function(m) { return m <= to; });
            return true;
        },

        filterSelfTrack(trackType) {
            if (!this.selfTrackFilter) return true;
            return trackType === this.selfTrackFilter;
        },

        filterSelfStatus(status) {
            if (!this.selfStatusFilter) return true;
            if (this.selfStatusFilter === 'completed') return status === 'completed';
            if (this.selfStatusFilter === 'pending_self_eval') return status === 'pending_self_eval';
            if (this.selfStatusFilter === 'in_review') return status !== 'completed' && status !== 'pending_self_eval';
            return true;
        },

        filterSelfSearch(searchStr) {
            if (!this.selfSearch.trim()) return true;
            return searchStr.toLowerCase().includes(this.selfSearch.trim().toLowerCase());
        },

        matchesSelfAppraisal(appYears, appMonths, trackType, status, searchStr) {
            return this.filterSelfYear(appYears)
                && this.filterSelfMonth(appMonths)
                && this.filterSelfTrack(trackType)
                && this.filterSelfStatus(status)
                && this.filterSelfSearch(searchStr);
        },

        get filteredSelfCount() {
            return this.selfApps.filter(function(app) {
                return this.filterSelfYear(app.years) &&
                    this.filterSelfMonth(app.months) &&
                    this.filterSelfTrack(app.track) &&
                    this.filterSelfStatus(app.status) &&
                    this.filterSelfSearch(app.search);
            }.bind(this)).length;
        },

        openCategory(cat) {
            this.selectedCategory = cat;
            const url = new URL(window.location.href);
            url.searchParams.set('category', cat);
            window.history.replaceState({}, '', url);
        },
        closeCategory() {
            this.selectedCategory = null;
            const url = new URL(window.location.href);
            url.searchParams.delete('category');
            window.history.replaceState({}, '', url);
        }
    };
}
</script>
@endsection
