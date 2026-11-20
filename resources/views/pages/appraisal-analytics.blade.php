@extends('layouts.app', ['title' => 'Loops HR - Appraisal Analytics & Reports', 'breadcrumb' => 'Appraisal Analytics'])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'overview'
}">

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <i class="ph ph-chart-line-up text-emerald-600"></i>
                <span>Performance Appraisal Analytics & Reports</span>
            </h1>
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                Analyze company-wide competency ratings, department score distributions, and performance trajectories over time.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('appraisal.index') }}" onclick="handleModuleBack(event, '{{ route('appraisal.index') }}')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                <i class="ph ph-arrow-left"></i>
                <span>Back to Hub</span>
            </a>

            <button onclick="window.print()" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm">
                <i class="ph ph-printer text-base"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- Multi-dimensional Filter Bar -->
    <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
        <form method="GET" action="{{ route('appraisal.analytics') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs font-bold">
            <div>
                <label class="block text-slate-500 mb-1">Appraisal Review Cycle</label>
                <select name="cycle_id" onchange="this.form.submit()" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">All Review Cycles</option>
                    @foreach($cycles as $c)
                        <option value="{{ $c->id }}" {{ $selectedCycleId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Department</label>
                <select name="department_id" onchange="this.form.submit()" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">Company-Wide (All Depts)</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ $selectedDeptId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Individual Employee</label>
                <select name="employee_id" onchange="this.form.submit()" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" {{ $selectedEmpId == $e->id ? 'selected' : '' }}>{{ $e->user->name ?? 'Staff' }} ({{ $e->employee_id_number }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <a href="{{ route('appraisal.analytics') }}" class="w-full text-center p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 text-xs font-bold">
                    Reset Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Visual Charts: Performance Bands & Department Averages -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Performance Band Distribution -->
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-chart-pie-slice text-lg text-blue-600"></i>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Performance Band Distribution</h3>
                </div>
                <span class="text-[10px] font-bold text-slate-400">Total: {{ $appraisals->where('status', 'completed')->count() }} Rated</span>
            </div>

            <div class="space-y-3">
                @php
                    $completedTotal = max(1, $appraisals->where('status', 'completed')->count());
                @endphp
                @foreach($bandCounts as $bandName => $count)
                    @php
                        $pct = round(($count / $completedTotal) * 100);
                        $barColor = str_contains($bandName, 'Exceptional') ? 'bg-emerald-500' :
                            (str_contains($bandName, 'Exceeds') ? 'bg-blue-500' :
                            (str_contains($bandName, 'Meets') ? 'bg-amber-500' :
                            (str_contains($bandName, 'Needs') ? 'bg-orange-500' : 'bg-rose-500')));
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-700 dark:text-slate-300">{{ $bandName }}</span>
                            <span class="text-slate-900 dark:text-white">{{ $count }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                            <div class="{{ $barColor }} h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Chart 2: Department Score Averages -->
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-chart-bar text-lg text-indigo-600"></i>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Department Average Performance (Out of 5.00)</h3>
                </div>
                <span class="text-[10px] font-bold text-slate-400">Scale: 1 - 5</span>
            </div>

            <div class="space-y-3">
                @foreach($deptAverages as $deptName => $avgScore)
                    @php
                        $barWidth = min(100, round(($avgScore / 5.00) * 100));
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-700 dark:text-slate-300">{{ $deptName }}</span>
                            <span class="text-slate-900 dark:text-white">{{ number_format($avgScore, 2) }} / 5.00</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $barWidth }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Master Evaluations Summary Table -->
    <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white">Filtered Evaluations Master Roster</h3>
                <p class="text-xs text-slate-400 mt-0.5">Chronological listing of employee ratings, performance bands, and salary adjustments.</p>
            </div>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-xl">
                {{ $appraisals->count() }} Records Found
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="p-3 pl-4">Employee</th>
                        <th class="p-3">Department</th>
                        <th class="p-3">Designation</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Final Score</th>
                        <th class="p-3">Performance Band</th>
                        <th class="p-3">Increment %</th>
                        <th class="p-3 pr-4 text-right">Link</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($appraisals as $app)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="p-3 pl-4 font-bold text-slate-900 dark:text-white">
                                {{ $app->employee?->user?->name ?? 'Staff' }}
                            </td>
                            <td class="p-3 font-semibold text-slate-600 dark:text-slate-300">
                                {{ $app->employee?->department?->name ?? 'General' }}
                            </td>
                            <td class="p-3 text-slate-500 dark:text-slate-400">
                                {{ $app->employee?->designation?->name ?? 'Role' }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold 
                                    {{ $app->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ ucwords(str_replace('_', ' ', $app->status)) }}
                                </span>
                            </td>
                            <td class="p-3 font-black text-slate-900 dark:text-white">
                                {{ $app->total_weighted_score > 0 ? number_format($app->total_weighted_score, 2) . ' / 5.00' : '—' }}
                            </td>
                            <td class="p-3">
                                <span class="font-extrabold text-[11px] 
                                    {{ $app->performance_band === 'Exceptional' ? 'text-emerald-600' : '' }}
                                    {{ $app->performance_band === 'Exceeds Expectations' ? 'text-blue-600' : '' }}
                                    {{ $app->performance_band === 'Meets Expectations' ? 'text-amber-600' : '' }}
                                    {{ $app->performance_band === 'Needs Improvement' ? 'text-orange-600' : '' }}
                                    {{ $app->performance_band === 'Unsatisfactory' ? 'text-rose-600' : '' }}">
                                    {{ $app->performance_band ?: 'Pending' }}
                                </span>
                            </td>
                            <td class="p-3 font-bold text-emerald-600">
                                {{ $app->increment_percentage > 0 ? '+' . number_format($app->increment_percentage, 1) . '%' : '—' }}
                            </td>
                            <td class="p-3 pr-4 text-right">
                                <a href="{{ route('appraisal.show', $app->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-bold text-xs">
                                    View Form &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-8 text-slate-400">No evaluations found matching the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
