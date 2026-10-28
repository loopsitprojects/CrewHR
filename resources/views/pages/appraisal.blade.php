@extends('layouts.app', ['title' => 'Loops HR - Appraisal System', 'breadcrumb' => 'Appraisal System'])

@section('content')
<div class="space-y-6">

    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Manage KPI evaluations, quarterly performance reviews, goals, and core competency ratings.</p>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all self-start sm:self-auto shrink-0">
            <i class="ph ph-plus-circle text-base"></i>
            <span>Create Appraisal Cycle</span>
        </button>
    </div>

    <!-- Active Cycle Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg space-y-4">
        <div class="flex items-center justify-between">
            <span class="bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Q3 2026 Active Cycle</span>
            <span class="text-xs font-bold">Ends in 24 Days</span>
        </div>
        <div>
            <h2 class="text-2xl font-black">2026 Annual Employee Evaluation</h2>
            <p class="text-xs text-blue-100 mt-1">92% of self-appraisals and manager reviews completed across all enterprise departments.</p>
        </div>
        <div class="w-full bg-white/20 h-2 rounded-full overflow-hidden">
            <div class="bg-white h-full w-[92%] rounded-full"></div>
        </div>
    </div>

    <!-- Employee Appraisals Roster Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($employees as $emp)
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm space-y-4 hover:border-blue-300 dark:hover:border-blue-500/50 transition-all">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($emp->user->name ?? 'Employee') }}&background=2563EB&color=fff" class="w-10 h-10 rounded-full border-2 border-blue-500" alt="Avatar">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $emp->user->name ?? 'Employee' }}</h3>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-400">{{ $emp->designation->name ?? 'Developer' }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 bg-slate-100 dark:bg-white/[0.05] text-slate-700 dark:text-[#E2E8F0] border border-slate-200/80 dark:border-white/[0.08] text-[10px] font-semibold px-2 py-0.5 rounded-md">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span> 4.8 / 5.0
                    </span>
                </div>

                <div class="space-y-2 text-xs font-semibold text-slate-600 dark:text-slate-300 border-t border-slate-100 dark:border-slate-800 pt-3">
                    <div class="flex justify-between">
                        <span class="text-slate-400">KPI Score</span>
                        <span class="font-bold text-slate-900 dark:text-white">95%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Competency Review</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">Exceeds Expectations</span>
                    </div>
                </div>

                <button class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 text-slate-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 py-2 rounded-xl text-xs font-bold transition-all">
                    View Full Review
                </button>
            </div>
        @endforeach
    </div>

</div>
@endsection
