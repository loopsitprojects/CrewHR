@extends('layouts.app', ['title' => 'Loops HR - Recruitment & Applicant Tracking', 'breadcrumb' => 'Recruitment'])

@section('content')
<div class="space-y-6">

    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Manage active job vacancies, candidate application pipelines, and interview schedules.</p>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-blue-500/25 transition-all self-start sm:self-auto shrink-0">
            <i class="ph ph-plus-circle text-base"></i>
            <span>Post New Job Vacancy</span>
        </button>
    </div>

    <!-- Job Vacancies Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($jobs as $j)
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm space-y-4 hover:border-blue-300 dark:hover:border-blue-500/50 transition-all">
                <div class="flex items-center justify-between">
                    <span class="bg-slate-100 dark:bg-white/[0.05] text-slate-700 dark:text-[#E2E8F0] text-[10px] font-semibold px-2.5 py-0.5 rounded-md border border-slate-200/80 dark:border-white/[0.08]">{{ $j['dept'] }}</span>
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-slate-700 dark:text-[#E2E8F0] bg-slate-100 dark:bg-white/[0.05] px-2 py-0.5 rounded-md border border-slate-200/80 dark:border-white/[0.08]">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span> {{ $j['status'] }}
                    </span>
                </div>

                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $j['title'] }}</h3>
                    <p class="text-xs font-semibold text-slate-400 mt-0.5">{{ $j['type'] }} • Colombo Office</p>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300"><span class="text-blue-600 dark:text-blue-400 font-black text-base">{{ $j['applicants'] }}</span> Applicants</span>
                    <button class="bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/30 text-slate-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 border border-slate-200/80 dark:border-slate-700 px-3 py-1 rounded-lg text-xs font-bold transition-all">View Pipeline</button>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
