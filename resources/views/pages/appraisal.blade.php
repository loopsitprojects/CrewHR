@extends('layouts.app', ['title' => 'Loops HR - Performance Appraisal Suite', 'breadcrumb' => 'Appraisal System'])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ request('tab', ($role !== 'Employee' && $employee) ? 'my_appraisal' : 'roster') }}',
    selectedCategory: '{{ request('category', '') }}' || null,
    showCycleModal: false,
    showReminderModal: false,
    statusFilter: '{{ request('status', '') }}',
    deptFilter: '{{ request('department_id', '') }}',

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
}">

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

    $quarterlySelf = $selfList->filter(fn($app) => $app->cycle?->isMidYear());
    $biAnnualSelf = $selfList->filter(fn($app) => !$app->cycle?->isMidYear());

    // Partition letters
    $quarterlyLetters = $letters->filter(fn($l) => $l->appraisal?->cycle?->isMidYear());
    $biAnnualLetters = $letters->filter(fn($l) => !$l->appraisal?->cycle?->isMidYear());

    // Partition change requests
    $quarterlyChanges = $myChangeRequests->filter(fn($cr) => $cr->appraisal?->cycle?->isMidYear());
    $biAnnualChanges = $myChangeRequests->filter(fn($cr) => !$cr->appraisal?->cycle?->isMidYear());
@endphp

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
                    class="group w-full text-left bg-white dark:bg-[#111927] rounded-3xl p-6 sm:p-7 border border-slate-200/90 dark:border-white/[0.08] hover:border-amber-400 dark:hover:border-amber-600 shadow-sm hover:shadow-xl hover:shadow-amber-500/5 transition-all duration-300 cursor-pointer flex flex-col justify-between space-y-6">
                <div class="space-y-4 w-full">
                    <div class="flex items-center justify-between gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60 flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                            <i class="ph ph-calendar-check text-2xl"></i>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            {{ $quarterlyTileCount }} {{ Str::plural('Appraisal', $quarterlyTileCount) }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Quarterly Reviews</h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">
                                Active
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium leading-relaxed">
                            Click to view all quarterly &amp; mid-year appraisals, KPI evaluations, and progress reviews.
                        </p>
                    </div>
                </div>

                <div class="w-full pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="ph ph-clock text-sm"></i>
                        <span>FY 2026/2027 Mid-Year Review</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-black text-amber-600 dark:text-amber-400 group-hover:translate-x-1 transition-transform">
                        <span>Open Page</span>
                        <i class="ph ph-arrow-right"></i>
                    </span>
                </div>
            </button>

            <!-- Tile 2: Bi-Annual Reviews -->
            <button type="button"
                    @click="openCategory('biannual')"
                    class="group w-full text-left bg-white dark:bg-[#111927] rounded-3xl p-6 sm:p-7 border border-slate-200/90 dark:border-white/[0.08] hover:border-indigo-400 dark:hover:border-indigo-600 shadow-sm hover:shadow-xl hover:shadow-indigo-500/5 transition-all duration-300 cursor-pointer flex flex-col justify-between space-y-6">
                <div class="space-y-4 w-full">
                    <div class="flex items-center justify-between gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-800/60 flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                            <i class="ph ph-chart-line-up text-2xl"></i>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            {{ $biAnnualTileCount }} {{ Str::plural('Appraisal', $biAnnualTileCount) }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Bi-Annual Reviews</h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                Year-End
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium leading-relaxed">
                            Click to view all bi-annual &amp; year-end appraisals, final band scoring, promotions, and salary increments.
                        </p>
                    </div>
                </div>

                <div class="w-full pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="ph ph-clock text-sm"></i>
                        <span>FY 2026/2027 Year-End Review</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-black text-indigo-600 dark:text-indigo-400 group-hover:translate-x-1 transition-transform">
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
        <!-- Top Navigation Bar: Back to Categories, Title, Category Switcher -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <button type="button" @click="closeCategory()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors cursor-pointer">
                    <i class="ph ph-arrow-left font-bold"></i>
                    <span>All Categories</span>
                </button>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 dark:text-white" x-text="selectedCategory === 'quarterly' ? 'Quarterly Reviews' : 'Bi-Annual Reviews'"></h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                          :class="selectedCategory === 'quarterly' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-800'"
                          x-text="selectedCategory === 'quarterly' ? 'Quarter 1 • Mid-Year' : 'Quarter 2 • Year-End'"></span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Direct Switcher Pill (Quarterly <-> Bi-Annual) -->
                <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700/80">
                    <button type="button" @click="openCategory('quarterly')"
                            :class="selectedCategory === 'quarterly' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-3 py-1 rounded-xl text-xs font-extrabold transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="ph ph-calendar-check"></i>
                        <span>Quarterly ({{ $quarterlyTileCount }})</span>
                    </button>
                    <button type="button" @click="openCategory('biannual')"
                            :class="selectedCategory === 'biannual' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-3 py-1 rounded-xl text-xs font-extrabold transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="ph ph-chart-line-up"></i>
                        <span>Bi-Annual ({{ $biAnnualTileCount }})</span>
                    </button>
                </div>

                @if(in_array($role, ['Super Admin', 'HR Lead']))
                <button @click="showCycleModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/20 transition-all shrink-0 cursor-pointer">
                    <i class="ph ph-plus-circle text-base"></i>
                    <span>New Cycle</span>
                </button>
                @endif
            </div>
        </div>

        <!-- Category Cycle Information Banner -->
        <!-- Quarterly Review Cycle Header Banner -->
        <template x-if="selectedCategory === 'quarterly'">
            <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-200 dark:border-amber-900/40 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm font-black text-sm">
                        Q1
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ $quarterlyCycle?->name ?? 'FY 2026/2027 Mid-Year Performance Review' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Active Cycle</span>
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
        <template x-if="selectedCategory === 'biannual'">
            <div class="bg-gradient-to-r from-indigo-500/10 via-indigo-500/5 to-transparent border border-indigo-200 dark:border-indigo-900/40 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm font-black text-sm">
                        Q2
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ $biAnnualCycle?->name ?? 'FY 2026/2027 Year-End Performance Review' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Year-End Track</span>
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
            <div class="flex items-center gap-6">
                @if($role === 'Employee')
                    <button @click="activeTab = 'roster'" :class="activeTab === 'roster' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <i class="ph ph-clipboard-text text-sm"></i>
                        <span>Evaluation Form (<span x-text="selectedCategory === 'quarterly' ? '{{ $quarterlySelf->count() }}' : '{{ $biAnnualSelf->count() }}'"></span>)</span>
                    </button>
                    <button @click="activeTab = 'my_changes'" :class="activeTab === 'my_changes' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <i class="ph ph-clock-counter-clockwise text-sm"></i>
                        <span>Changes Requested (<span x-text="selectedCategory === 'quarterly' ? '{{ $quarterlyChanges->count() }}' : '{{ $biAnnualChanges->count() }}'"></span>)</span>
                    </button>
                @else
                    @if($employee)
                        <button @click="activeTab = 'my_appraisal'" :class="activeTab === 'my_appraisal' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="ph ph-user text-sm"></i>
                            <span>My Self-Evaluation</span>
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
        <!-- TAB CONTENT: MY SELF-EVALUATION (For Manager & Employee)  -->
        <!-- ========================================================= -->
        <div x-show="{{ $role === 'Employee' ? 'activeTab === \'roster\'' : 'activeTab === \'my_appraisal\'' }}" class="space-y-4">
            <!-- 1. Quarterly Self-Evaluations List -->
            <template x-if="selectedCategory === 'quarterly'">
                <div class="space-y-4">
                    @if($quarterlySelf->isEmpty())
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 mx-auto flex items-center justify-center text-2xl">
                                <i class="ph ph-calendar-check"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Quarterly Self-Evaluation Assigned</h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">You do not currently have a pending evaluation assigned in the quarterly review track.</p>
                        </div>
                    @else
                        @foreach($quarterlySelf as $app)
                            <!-- Hero Details Card -->
                            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <span class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                            My Quarterly Review
                                        </span>
                                        <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                                            {{ $app->cycle?->name ?? 'FY 2026/2027 Mid-Year Performance Review' }}
                                        </h2>
                                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">
                                            Review your self-ratings, submit comments, and track manager and HOD evaluations.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $app->status === 'completed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200' }}">
                                            Status: {{ $app->status === 'completed' ? 'Finalized' : ($app->status === 'pending_self_eval' ? 'Pending Self Eval' : 'In Review') }}
                                        </span>
                                        <a href="{{ route('appraisal.show', $app->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                                            <span>Open Full Evaluation Sheet</span>
                                            <i class="ph ph-arrow-square-out text-sm"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- 4 Metric Columns -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Evaluation Stage</span>
                                            <span class="text-xs font-black text-blue-600 dark:text-blue-400 mt-1 block">
                                                {{ $app->status === 'pending_self_eval' ? '1. Self-Evaluation' : ($app->status === 'completed' ? '4. Finalized' : '2. Manager Review') }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                                {{ $app->status === 'pending_self_eval' ? 'Pending your submission' : ($app->status === 'completed' ? 'Evaluation finalized' : 'In review') }}
                                            </span>
                                        </div>
                                        <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center shrink-0">
                                            <i class="ph ph-git-branch text-sm"></i>
                                        </div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Due Date</span>
                                            <span class="text-xs font-black text-slate-900 dark:text-white mt-1 block">
                                                {{ $app->self_eval_deadline ? \Carbon\Carbon::parse($app->self_eval_deadline)->format('d M Y') : ($app->cycle?->end_date ? \Carbon\Carbon::parse($app->cycle->end_date)->format('d M Y') : '20 Oct 2026') }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Self-Evaluation Due</span>
                                        </div>
                                        <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center shrink-0">
                                            <i class="ph ph-calendar text-sm"></i>
                                        </div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Assigned Managers</span>
                                            <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 mt-1 space-y-0.5">
                                                @if($app->reportingManager)
                                                    <div class="flex items-center gap-1.5"><span class="px-1 py-0.2 bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 rounded text-[9px]">MGR</span> <span>{{ $app->reportingManager->user->name ?? 'Manager' }}</span></div>
                                                @endif
                                                @if($app->hod)
                                                    <div class="flex items-center gap-1.5"><span class="px-1 py-0.2 bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 rounded text-[9px]">HOD</span> <span>{{ $app->hod->user->name ?? 'HOD' }}</span></div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 flex items-center justify-center shrink-0">
                                            <i class="ph ph-users text-sm"></i>
                                        </div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Form Updated Date</span>
                                            <span class="text-xs font-black text-slate-900 dark:text-white mt-1 block">
                                                {{ $app->updated_at ? $app->updated_at->format('d M Y') : '—' }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                                {{ $app->updated_at ? $app->updated_at->diffForHumans() : '' }}
                                            </span>
                                        </div>
                                        <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center shrink-0">
                                            <i class="ph ph-clock-counter-clockwise text-sm"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Table Roster Row -->
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
                                        <tbody>
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                                <td class="p-3.5 pl-5">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-sm">
                                                            {{ strtoupper(substr($app->employee?->user?->name ?? 'E', 0, 2)) }}
                                                        </div>
                                                        <div>
                                                            <a href="{{ route('appraisal.show', $app->id) }}" class="font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 block transition-colors">
                                                                {{ $app->employee?->user?->name ?? 'Employee' }}
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
                                                    <div class="space-y-0.5 text-[11px]">
                                                        @if($app->reportingManager)
                                                            <div class="font-bold text-slate-600 dark:text-slate-300"><span class="text-slate-400 font-normal">Mgr:</span> {{ $app->reportingManager->user->name ?? 'Manager' }}</div>
                                                        @endif
                                                        @if($app->hod)
                                                            <div class="font-bold text-indigo-600 dark:text-indigo-400"><span class="text-slate-400 font-normal">HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->status === 'completed')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Finalized</span>
                                                    @elseif($app->status === 'pending_self_eval')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Self-Eval</span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">In Review</span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5 text-slate-400 text-xs font-semibold">
                                                    {{ $app->total_weighted_score > 0 ? number_format($app->total_weighted_score, 2) . ' / 5.00' : '—' }}
                                                </td>
                                                <td class="p-3.5">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/60">
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
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </template>

            <!-- 2. Bi-Annual Self-Evaluations List -->
            <template x-if="selectedCategory === 'biannual'">
                <div class="space-y-4">
                    @if($biAnnualSelf->isEmpty())
                        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center text-2xl">
                                <i class="ph ph-chart-line-up"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Bi-Annual Self-Evaluation Assigned</h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">You do not currently have a pending evaluation assigned in the bi-annual / year-end review track.</p>
                        </div>
                    @else
                        @foreach($biAnnualSelf as $app)
                            <!-- Hero Details Card -->
                            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                            My Bi-Annual / Year-End Review
                                        </span>
                                        <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                                            {{ $app->cycle?->name ?? 'FY 2026/2027 Year-End Performance Review' }}
                                        </h2>
                                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">
                                            Review your self-ratings, submit comments, and track manager and HOD evaluations.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $app->status === 'completed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200' : 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200' }}">
                                            Status: {{ $app->status === 'completed' ? 'Finalized' : ($app->status === 'pending_self_eval' ? 'Pending Self Eval' : 'In Review') }}
                                        </span>
                                        <a href="{{ route('appraisal.show', $app->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                                            <span>Open Full Evaluation Sheet</span>
                                            <i class="ph ph-arrow-square-out text-sm"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- 4 Metric Columns -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Evaluation Stage</span>
                                            <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 mt-1 block">
                                                {{ $app->status === 'pending_self_eval' ? '1. Self-Evaluation' : ($app->status === 'completed' ? '4. Finalized' : '2. Manager Review') }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                                {{ $app->status === 'pending_self_eval' ? 'Pending your submission' : ($app->status === 'completed' ? 'Evaluation finalized' : 'In review') }}
                                            </span>
                                        </div>
                                        <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 flex items-center justify-center shrink-0">
                                            <i class="ph ph-git-branch text-sm"></i>
                                        </div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Due Date</span>
                                            <span class="text-xs font-black text-slate-900 dark:text-white mt-1 block">
                                                {{ $app->self_eval_deadline ? \Carbon\Carbon::parse($app->self_eval_deadline)->format('d M Y') : ($app->cycle?->end_date ? \Carbon\Carbon::parse($app->cycle->end_date)->format('d M Y') : '15 Mar 2027') }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Self-Evaluation Due</span>
                                        </div>
                                        <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center shrink-0">
                                            <i class="ph ph-calendar text-sm"></i>
                                        </div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Assigned Managers</span>
                                            <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 mt-1 space-y-0.5">
                                                @if($app->reportingManager)
                                                    <div class="flex items-center gap-1.5"><span class="px-1 py-0.2 bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 rounded text-[9px]">MGR</span> <span>{{ $app->reportingManager->user->name ?? 'Manager' }}</span></div>
                                                @endif
                                                @if($app->hod)
                                                    <div class="flex items-center gap-1.5"><span class="px-1 py-0.2 bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 rounded text-[9px]">HOD</span> <span>{{ $app->hod->user->name ?? 'HOD' }}</span></div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 flex items-center justify-center shrink-0">
                                            <i class="ph ph-users text-sm"></i>
                                        </div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Form Updated Date</span>
                                            <span class="text-xs font-black text-slate-900 dark:text-white mt-1 block">
                                                {{ $app->updated_at ? $app->updated_at->format('d M Y') : '—' }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                                {{ $app->updated_at ? $app->updated_at->diffForHumans() : '' }}
                                            </span>
                                        </div>
                                        <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center shrink-0">
                                            <i class="ph ph-clock-counter-clockwise text-sm"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Table Roster Row -->
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
                                        <tbody>
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                                <td class="p-3.5 pl-5">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-sm">
                                                            {{ strtoupper(substr($app->employee?->user?->name ?? 'E', 0, 2)) }}
                                                        </div>
                                                        <div>
                                                            <a href="{{ route('appraisal.show', $app->id) }}" class="font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 block transition-colors">
                                                                {{ $app->employee?->user?->name ?? 'Employee' }}
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
                                                    <div class="space-y-0.5 text-[11px]">
                                                        @if($app->reportingManager)
                                                            <div class="font-bold text-slate-600 dark:text-slate-300"><span class="text-slate-400 font-normal">Mgr:</span> {{ $app->reportingManager->user->name ?? 'Manager' }}</div>
                                                        @endif
                                                        @if($app->hod)
                                                            <div class="font-bold text-indigo-600 dark:text-indigo-400"><span class="text-slate-400 font-normal">HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->status === 'completed')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Finalized</span>
                                                    @elseif($app->status === 'pending_self_eval')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Self-Eval</span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">In Review</span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5 text-slate-400 text-xs font-semibold">
                                                    {{ $app->total_weighted_score > 0 ? number_format($app->total_weighted_score, 2) . ' / 5.00' : '—' }}
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->increment_percentage > 0)
                                                        <span class="font-extrabold text-emerald-600 dark:text-emerald-400 text-xs">+{{ number_format($app->increment_percentage, 1) }}% Inc</span>
                                                    @else
                                                        <span class="text-slate-400 text-[10px] font-medium">Eligible at Final</span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5 pr-5 text-right">
                                                    <a href="{{ route('appraisal.show', $app->id) }}" class="bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-xl font-bold transition-all text-xs inline-flex items-center gap-1.5">
                                                        <span>Review</span>
                                                        <i class="ph ph-arrow-right text-xs"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </template>
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
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 mx-auto flex items-center justify-center text-2xl">
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
                                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-sm">
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
                                                            <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}
                                                            </div>
                                                        @else
                                                            <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">Direct HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->status === 'completed')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Finalized
                                                        </span>
                                                    @elseif($app->status === 'pending_self_eval')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Self-Eval
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> In Review
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->total_weighted_score > 0)
                                                        <div class="space-y-1">
                                                            <div class="font-black text-slate-900 dark:text-white flex items-center gap-1">
                                                                <span>{{ number_format($app->total_weighted_score, 2) }}</span>
                                                                <span class="text-[10px] text-slate-400">/ 5.00</span>
                                                            </div>
                                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                                                {{ $app->performance_band }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400 text-xs font-semibold">—</span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/60" title="Mid-Year Review focuses on progress and KPIs without salary increments.">
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
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center text-2xl">
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
                                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-sm">
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
                                                            <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}
                                                            </div>
                                                        @else
                                                            <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                                                <span class="text-slate-400 font-normal">Direct HOD:</span> {{ $app->hod->user->name ?? 'HOD' }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->status === 'completed')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Finalized
                                                        </span>
                                                    @elseif($app->status === 'pending_self_eval')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Self-Eval
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> In Review
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->total_weighted_score > 0)
                                                        <div class="space-y-1">
                                                            <div class="font-black text-slate-900 dark:text-white flex items-center gap-1">
                                                                <span>{{ number_format($app->total_weighted_score, 2) }}</span>
                                                                <span class="text-[10px] text-slate-400">/ 5.00</span>
                                                            </div>
                                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                                                {{ $app->performance_band }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400 text-xs font-semibold">—</span>
                                                    @endif
                                                </td>
                                                <td class="p-3.5">
                                                    @if($app->increment_percentage > 0)
                                                        <div class="font-extrabold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                            <i class="ph ph-trend-up"></i>
                                                            <span>+{{ number_format($app->increment_percentage, 1) }}% Inc</span>
                                                        </div>
                                                    @elseif($app->is_promoted)
                                                        <div class="font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
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
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 mx-auto flex items-center justify-center text-2xl">
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
                                            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg font-bold border border-amber-100 dark:border-amber-900/40 shrink-0">
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
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center text-2xl">
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
                                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg font-bold border border-indigo-100 dark:border-indigo-900/40 shrink-0">
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
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">{{ $cr->status }}</span>
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
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-100 text-indigo-800">{{ $cr->status }}</span>
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
                    <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center">
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
                             :class="cycleType === 'mid_year' ? 'border-amber-500 bg-amber-50/70 dark:bg-amber-950/30 ring-2 ring-amber-500/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 bg-slate-50/50 dark:bg-slate-900/20'"
                             class="p-3.5 rounded-2xl border transition-all cursor-pointer space-y-1.5 relative">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                    <i class="ph ph-calendar-check text-base"></i>
                                    1. Mid-Year Review
                                </span>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300">
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
                             :class="cycleType === 'year_end' ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-950/30 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 bg-slate-50/50 dark:bg-slate-900/20'"
                             class="p-3.5 rounded-2xl border transition-all cursor-pointer space-y-1.5 relative">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-blue-700 dark:text-blue-400 flex items-center gap-1.5">
                                    <i class="ph ph-trend-up text-base"></i>
                                    2. Year-End Review
                                </span>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300">
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
                <div class="p-3 rounded-xl border flex items-center justify-between"
                     :class="cycleType === 'mid_year' ? 'bg-amber-50/80 dark:bg-amber-950/40 border-amber-200 dark:border-amber-900/60' : 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900/60'">
                    <div class="flex items-center gap-2.5">
                        <i :class="cycleType === 'mid_year' ? 'ph-prohibit text-amber-600' : 'ph-check-circle text-emerald-600'" class="ph text-lg"></i>
                        <div>
                            <span class="text-xs font-bold block" :class="cycleType === 'mid_year' ? 'text-amber-900 dark:text-amber-300' : 'text-emerald-900 dark:text-emerald-300'">
                                <template x-if="cycleType === 'mid_year'">
                                    <span>Salary Increments: <strong>Excluded (Mid-Year Policy)</strong></span>
                                </template>
                                <template x-if="cycleType !== 'mid_year'">
                                    <span>Salary Increments &amp; Promotions: <strong>Enabled</strong></span>
                                </template>
                            </span>
                            <span class="text-[10px] font-normal" :class="cycleType === 'mid_year' ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400'">
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

                <label class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 rounded-xl cursor-pointer">
                    <input type="checkbox" name="initialize_all_employees" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs font-bold text-blue-900 dark:text-blue-300">
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
</script>
@endsection
