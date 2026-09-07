@extends('layouts.app', ['title' => 'LOOPS HR - Recruitment & Applicant Tracking', 'breadcrumb' => 'Recruitment'])

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <i class="ph ph-briefcase text-2xl text-blue-600"></i>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Recruitment & Applicant Tracking System (ATS)</h1>
            </div>
            <p class="text-xs font-semibold text-slate-500 mt-1">Manage active job vacancies, candidate application pipelines, and interview schedules.</p>
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-blue-500/25 transition-all">
            <i class="ph ph-plus-circle text-base"></i>
            <span>Post New Job Vacancy</span>
        </button>
    </div>

    <!-- Job Vacancies Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($jobs as $j)
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4 hover:border-blue-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="bg-blue-50 text-blue-700 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-blue-200">{{ $j['dept'] }}</span>
                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">● {{ $j['status'] }}</span>
                </div>

                <div>
                    <h3 class="text-sm font-black text-slate-900">{{ $j['title'] }}</h3>
                    <p class="text-xs font-semibold text-slate-400 mt-0.5">{{ $j['type'] }} • Colombo Office</p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700"><span class="text-blue-600 font-black text-base">{{ $j['applicants'] }}</span> Applicants</span>
                    <button class="bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-700 px-3 py-1 rounded-lg text-xs font-bold transition-all">View Pipeline</button>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
