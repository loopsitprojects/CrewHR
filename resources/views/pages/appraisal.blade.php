@extends('layouts.app', ['title' => 'LOOPS HR - Appraisal System', 'breadcrumb' => 'Appraisal System'])

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <i class="ph ph-seal-check text-2xl text-blue-600"></i>
                <h1 class="text-xl font-black text-gray-900">Performance & Appraisal System</h1>
            </div>
            <p class="text-xs font-semibold text-gray-500 mt-1">Manage KPI evaluations, quarterly performance reviews, goals, and core competency ratings.</p>
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all">
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
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($emp->user->name ?? 'Employee') }}&background=0D8ABC&color=fff" class="w-10 h-10 rounded-full border-2 border-blue-500" alt="Avatar">
                        <div>
                            <h3 class="text-sm font-black text-gray-900">{{ $emp->user->name ?? 'Employee' }}</h3>
                            <p class="text-xs font-bold text-gray-400">{{ $emp->designation->name ?? 'Developer' }}</p>
                        </div>
                    </div>
                    <span class="bg-emerald-50 text-emerald-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full">4.8 / 5.0</span>
                </div>

                <div class="space-y-2 text-xs font-semibold text-gray-600 border-t border-gray-100 pt-3">
                    <div class="flex justify-between">
                        <span class="text-gray-400">KPI Score</span>
                        <span class="font-bold text-gray-900">95%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Competency Review</span>
                        <span class="font-bold text-blue-600">Exceeds Expectations</span>
                    </div>
                </div>

                <button class="w-full bg-gray-50 border border-gray-200 hover:bg-blue-50 hover:text-blue-600 text-gray-700 py-2 rounded-xl text-xs font-bold transition-all">
                    View Full Review
                </button>
            </div>
        @endforeach
    </div>

</div>
@endsection
