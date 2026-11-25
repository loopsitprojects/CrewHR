@extends('layouts.app', ['title' => 'Loops HR - Performance Appraisal Suite', 'breadcrumb' => 'Appraisal System'])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ request('tab', 'roster') }}',
    showCycleModal: false,
    showReminderModal: false,
    statusFilter: '{{ request('status', '') }}',
    deptFilter: '{{ request('department_id', '') }}'
}">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">
                Performance Appraisals
            </h1>
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

    <!-- Review Cycles / Quarters Cards (Both Quarters) -->
    @if(!empty($cycleCardsData))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach($cycleCardsData as $cData)
            @php
                $c = $cData['cycle'];
                $isMid = $c->isMidYear();
                $isSelected = $cData['is_selected'];
                $cTotal = $cData['total'];
                $cCompleted = $cData['completed'];
                $cSelfSubmitted = $cData['self_submitted'];
                $cManagerDone = $cData['manager_done'];
                $cRate = $cData['rate'];

                $isCycleActive = ($c->status === 'Active');

                if (!$isCycleActive) {
                    $m1Status = 'upcoming';
                    $m2Status = 'upcoming';
                    $m3Status = 'upcoming';
                    $progressLineWidth = 0;
                } else {
                    // Milestone 1: Submit (Self-Evaluation)
                    $m1Status = ($cTotal > 0 && $cData['pending_self'] === 0) ? 'completed' : 'current';

                    // Milestone 2: Manager Review
                    if ($cTotal > 0 && $cManagerDone === $cTotal) {
                        $m2Status = 'completed';
                    } elseif ($cSelfSubmitted > 0 || $cManagerDone > 0) {
                        $m2Status = 'current';
                    } else {
                        $m2Status = 'upcoming';
                    }

                    // Milestone 3: Complete (Final Sign-off)
                    if ($cTotal > 0 && $cCompleted === $cTotal) {
                        $m3Status = 'completed';
                    } elseif ($cCompleted > 0) {
                        $m3Status = 'current';
                    } else {
                        $m3Status = 'upcoming';
                    }

                    $selfRatio = ($cTotal > 0) ? ($cSelfSubmitted / $cTotal) : 0;
                    $managerRatio = ($cTotal > 0) ? ($cManagerDone / $cTotal) : 0;
                    $completedRatio = ($cTotal > 0) ? ($cCompleted / $cTotal) : 0;

                    if ($cTotal > 0 && $cCompleted === $cTotal) {
                        $progressLineWidth = 100;
                    } elseif ($cTotal > 0) {
                        $progressLineWidth = round(($selfRatio * 50) + (($managerRatio * 0.25 + $completedRatio * 0.25) * 100));
                        $progressLineWidth = min(100, max(0, $progressLineWidth));
                    } else {
                        $progressLineWidth = 0;
                    }
                }
            @endphp

            <div class="bg-white dark:bg-[#111927] rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-sm space-y-4 transition-all {{ $isSelected ? 'border-2 border-blue-500/80 dark:border-blue-400/80 shadow-md ring-2 ring-blue-500/15' : 'border border-slate-200/90 dark:border-white/[0.08] hover:border-slate-300 dark:hover:border-white/[0.15]' }}">
                
                <!-- Card Header -->
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3.5">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $isMid ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300/80 dark:border-amber-800' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-300/80 dark:border-indigo-800' }}">
                                <span>{{ $cData['quarter_badge'] }}</span>
                            </span>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400">
                                {{ $c->financial_year ?: 'FY 2026/2027' }}
                            </span>
                            @if($c->status === 'Active')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Active</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    <span>Upcoming</span>
                                </span>
                            @endif
                        </div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight mt-1.5">
                            {{ $c->name }}
                        </h3>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                            Performance Period: <strong class="text-slate-700 dark:text-slate-300">{{ $cData['period_label'] }}</strong>
                        </p>
                    </div>

                    <div class="flex flex-col sm:items-end gap-2 shrink-0">
                        <div class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            <i class="ph ph-calendar text-xs text-blue-600 dark:text-blue-400"></i>
                            <span>{{ $c->start_date?->format('d M') }} – {{ $c->end_date?->format('d M Y') }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($isSelected)
                                <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 border border-blue-200 dark:border-blue-700">
                                    <i class="ph-bold ph-check"></i>
                                    <span>Active View</span>
                                </span>
                            @elseif($c->status === 'Active')
                                <a href="{{ route('appraisal.index', array_filter(['cycle_id' => $c->id, 'tab' => request('tab'), 'status' => request('status'), 'department_id' => request('department_id')])) }}" 
                                   class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 dark:bg-slate-800 dark:hover:bg-blue-950/60 dark:text-slate-300 dark:hover:text-blue-300 border border-slate-200 dark:border-slate-700 transition-colors">
                                    <span>Switch Quarter</span>
                                    <i class="ph ph-arrow-right text-xs"></i>
                                </a>
                            @endif

                            {{-- HR & Super Admin cycle activation management --}}
                            @if(in_array($role, ['Super Admin', 'HR Lead']))
                                @if($c->status === 'Upcoming')
                                    <form method="POST" action="{{ route('appraisal.cycles.toggle-status', $c->id) }}" onsubmit="return confirm('Activate review cycle \'{{ $c->name }}\' for the organization? Active employees will be initialized for evaluation.');">
                                        @csrf
                                        <button type="submit" 
                                                class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all cursor-pointer"
                                                title="Only HR or Admin can activate this appraisal cycle">
                                            <i class="ph-bold ph-play text-xs"></i>
                                            <span>Activate (HR)</span>
                                        </button>
                                    </form>
                                @elseif($c->status === 'Active' && !$isSelected)
                                    <form method="POST" action="{{ route('appraisal.cycles.toggle-status', $c->id) }}" onsubmit="return confirm('Deactivate review cycle \'{{ $c->name }}\' and set to Upcoming?');">
                                        @csrf
                                        <button type="submit" 
                                                class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                                title="Deactivate / Set to Upcoming">
                                            <i class="ph ph-pause text-xs"></i>
                                            <span>Pause</span>
                                        </button>
                                    </form>
                                @endif
                            @else
                                {{-- For Employees and Managers --}}
                                @if($c->status === 'Upcoming')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200/80 dark:border-slate-700/80" title="Activating cycles can only be done by HR or Admin">
                                        <i class="ph ph-lock-simple text-xs"></i>
                                        <span>Pending HR Activation</span>
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Cycle Finalization Progress Bar -->
                <div class="space-y-2.5 pt-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">
                            Cycle Finalization: <strong class="text-slate-800 dark:text-slate-200">{{ $cCompleted }} of {{ $cTotal }} Finalized</strong>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $cRate == 100 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' }}">
                            {{ $cRate }}%
                        </span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-500 rounded-full transition-all duration-500" style="width: {{ $cRate }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 pt-0.5">
                        <span>{{ $cSelfSubmitted }}/{{ $cTotal }} Submitted</span>
                        <span>&bull;</span>
                        <span>{{ $cManagerDone }}/{{ $cTotal }} Reviewed</span>
                        <span>&bull;</span>
                        <span>{{ $cCompleted }}/{{ $cTotal }} Finalized</span>
                    </div>
                </div>

            </div>
        @endforeach
    </div>
    @endif



    <!-- Navigation Tabs -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-6">
            @if($role === 'Employee')
                <button @click="activeTab = 'roster'" :class="activeTab === 'roster' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-clipboard-text text-sm"></i>
                    <span>Evaluation Form ({{ $appraisals->count() }})</span>
                </button>
                <button @click="activeTab = 'my_changes'" :class="activeTab === 'my_changes' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-clock-counter-clockwise text-sm"></i>
                    <span>Changes Requested ({{ $myChangeRequestsCount }})</span>
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
                    <span>Appraisals Roster ({{ $appraisals->count() }})</span>
                </button>

                <button @click="activeTab = 'letters'" :class="activeTab === 'letters' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-file-text text-sm"></i>
                    <span>Official Letters & E-Signatures ({{ $letters->count() }})</span>
                </button>
            @endif
        </div>

        @if($role !== 'Employee')
        <!-- Filter bar -->
        <form method="GET" action="{{ route('appraisal.index') }}" class="hidden md:flex items-center gap-2 pb-2">
            <input type="hidden" name="cycle_id" value="{{ $activeCycle?->id }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee..." class="text-xs bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-blue-500/20 text-slate-800 dark:text-slate-200">
            
            <select name="department_id" onchange="this.form.submit()" class="text-xs bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-blue-500/20 text-slate-800 dark:text-slate-200 font-bold">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()" class="text-xs bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-blue-500/20 text-slate-800 dark:text-slate-200 font-bold">
                <option value="">All Statuses</option>
                <option value="pending_self_eval" {{ request('status') == 'pending_self_eval' ? 'selected' : '' }}>Pending Self-Evaluation</option>
                <option value="pending_manager_eval" {{ request('status') == 'pending_manager_eval' ? 'selected' : '' }}>Pending Manager Review</option>
                <option value="pending_hod_eval" {{ request('status') == 'pending_hod_eval' ? 'selected' : '' }}>Pending HOD Review</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </form>
        @endif
    </div>

    <!-- TAB 1: Appraisals Roster -->
    <div x-show="activeTab === 'roster'" class="space-y-4">
        @if($appraisals->isEmpty())
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center text-2xl">
                    <i class="ph ph-award"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Appraisals Found</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">No performance evaluations match your search or current filter criteria for this review cycle.</p>
            </div>
        @else
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="p-3.5 pl-5">Employee & Designation</th>
                                <th class="p-3.5">Department</th>
                                <th class="p-3.5">Workflow Hierarchy</th>
                                <th class="p-3.5">Evaluation Status</th>
                                <th class="p-3.5">Score & Band</th>
                                <th class="p-3.5">
                                    @if($activeCycle?->isMidYear())
                                        Review Focus
                                    @else
                                        Increment / Promo
                                    @endif
                                </th>
                                <th class="p-3.5 pr-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @foreach($appraisals as $app)
                                @php
                                    $empUser = $app->employee?->user;
                                    $isDirectHod = ($app->workflow_type === 'two_tier');
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
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

                                    <td class="p-3.5">
                                        <span class="font-bold text-slate-700 dark:text-slate-300">
                                            {{ $app->employee?->department?->name ?? 'General' }}
                                        </span>
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
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Finalized
                                            </span>
                                        @elseif($app->status === 'pending_self_eval')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Self-Eval
                                            </span>
                                        @elseif($app->status === 'pending_manager_eval' || $app->status === 'submitted_self_eval')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> Pending Manager
                                            </span>
                                        @elseif($app->status === 'pending_hod_eval' || $app->status === 'submitted_manager_eval')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span> Pending HOD
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
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-extrabold 
                                                    {{ $app->performance_band === 'Exceptional' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : '' }}
                                                    {{ $app->performance_band === 'Exceeds Expectations' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300' : '' }}
                                                    {{ $app->performance_band === 'Meets Expectations' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' : '' }}
                                                    {{ $app->performance_band === 'Needs Improvement' ? 'bg-orange-100 text-orange-800 dark:bg-orange-950/70 dark:text-orange-300' : '' }}
                                                    {{ $app->performance_band === 'Unsatisfactory' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300' : '' }}
                                                ">
                                                    {{ $app->performance_band }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-xs font-semibold">—</span>
                                        @endif
                                    </td>

                                    <td class="p-3.5">
                                        @if($app->cycle && !$app->cycle->allowsIncrements())
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/60" title="Mid-Year Review focuses on progress and KPIs without salary increments.">
                                                <i class="ph ph-prohibit text-xs"></i> No Inc (Mid-Year)
                                            </span>
                                        @elseif(in_array($role, ['Super Admin', 'HR Lead']) || ($app->is_increment_visible_to_employee && $employee?->id == $app->employee_id) || ($app->is_increment_visible_to_hod && $employee?->id == $app->hod_id))
                                            <div class="space-y-0.5 text-[11px]">
                                                @if($app->increment_percentage > 0)
                                                    <div class="font-extrabold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                        <i class="ph ph-trend-up"></i>
                                                        <span>+{{ number_format($app->increment_percentage, 1) }}% Increment</span>
                                                    </div>
                                                @endif
                                                @if($app->is_promoted)
                                                    <div class="font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                                        <i class="ph ph-crown"></i>
                                                        <span>Promoted</span>
                                                    </div>
                                                @endif
                                                @if(!$app->increment_percentage && !$app->is_promoted)
                                                    <span class="text-slate-400 text-[10px]">—</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-[10px] italic flex items-center gap-1">
                                                <i class="ph ph-eye-slash text-xs"></i> Confidential
                                            </span>
                                        @endif
                                    </td>

                                    <td class="p-3.5 pr-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('appraisal.show', $app->id) }}" class="bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-xl font-bold transition-all text-xs flex items-center gap-1.5">
                                                <span>Review</span>
                                                <i class="ph ph-arrow-right text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- TAB 2: My Self-Evaluation (If active employee has an appraisal) -->
    @if($employee)
    <div x-show="activeTab === 'my_appraisal'" class="space-y-4">
        @php
            $selfList = (isset($myAppraisals) && $myAppraisals->isNotEmpty()) 
                ? $myAppraisals 
                : $appraisals->where('employee_id', $employee->id);
        @endphp

        @if($selfList->isEmpty())
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center text-2xl">
                    <i class="ph ph-user"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Self-Evaluation Assigned</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">You do not currently have a pending evaluation assigned in this review cycle.</p>
            </div>
        @else
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="p-3.5 pl-5">Employee & Designation</th>
                                <th class="p-3.5">Department</th>
                                <th class="p-3.5">Workflow Hierarchy</th>
                                <th class="p-3.5">Evaluation Status</th>
                                <th class="p-3.5">Score & Band</th>
                                <th class="p-3.5">
                                    @if($activeCycle?->isMidYear())
                                        Review Focus
                                    @else
                                        Increment / Promo
                                    @endif
                                </th>
                                <th class="p-3.5 pr-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @foreach($selfList as $app)
                                <tr>
                                    <td class="p-3.5 pl-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-black text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($app->employee->user->name ?? 'EM', 0, 2)) }}
                                            </div>
                                            <div>
                                                <span class="font-extrabold text-slate-900 dark:text-white block">
                                                    {{ $app->employee->user->name ?? 'Employee' }}
                                                </span>
                                                <span class="text-[11px] text-slate-400 font-medium">
                                                    {{ $app->employee->designation->name ?? 'Designation' }} &bull; {{ $app->employee->employee_id_number ?? 'ID' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-3.5 font-bold text-slate-700 dark:text-slate-300">
                                        {{ $app->employee->department->name ?? 'General' }}
                                    </td>

                                    <td class="p-3.5">
                                        <div class="space-y-0.5 text-[11px]">
                                            <div class="flex items-center gap-1 font-semibold text-slate-600 dark:text-slate-300">
                                                <span class="text-slate-400">Mgr:</span>
                                                <span>{{ $app->reportingManager->user->name ?? 'Not Assigned' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 font-semibold text-slate-600 dark:text-slate-300">
                                                <span class="text-slate-400">HOD:</span>
                                                <span class="text-purple-600 dark:text-purple-400">{{ $app->hod->user->name ?? 'Not Assigned' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-3.5">
                                        @if($app->status === 'completed')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Finalized
                                            </span>
                                        @elseif($app->status === 'pending_self_eval')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Self-Eval
                                            </span>
                                        @elseif($app->status === 'pending_manager_eval' || $app->status === 'submitted_self_eval')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> Pending Manager
                                            </span>
                                        @elseif($app->status === 'pending_hod_eval' || $app->status === 'submitted_manager_eval')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span> Pending HOD
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
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-extrabold 
                                                    {{ $app->performance_band === 'Exceptional' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : '' }}
                                                    {{ $app->performance_band === 'Exceeds Expectations' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300' : '' }}
                                                    {{ $app->performance_band === 'Meets Expectations' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' : '' }}
                                                    {{ $app->performance_band === 'Needs Improvement' ? 'bg-orange-100 text-orange-800 dark:bg-orange-950/70 dark:text-orange-300' : '' }}
                                                    {{ $app->performance_band === 'Unsatisfactory' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300' : '' }}
                                                ">
                                                    {{ $app->performance_band }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-xs font-semibold">—</span>
                                        @endif
                                    </td>

                                    <td class="p-3.5">
                                        @if($app->cycle && !$app->cycle->allowsIncrements())
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/60" title="Mid-Year Review focuses on progress and KPIs without salary increments.">
                                                <i class="ph ph-prohibit text-xs"></i> No Inc (Mid-Year)
                                            </span>
                                        @elseif(in_array($role, ['Super Admin', 'HR Lead']) || ($app->is_increment_visible_to_employee && $employee?->id == $app->employee_id) || ($app->is_increment_visible_to_hod && $employee?->id == $app->hod_id))
                                            <div class="space-y-0.5 text-[11px]">
                                                @if($app->increment_percentage > 0)
                                                    <div class="font-extrabold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                        <i class="ph ph-trend-up"></i>
                                                        <span>+{{ number_format($app->increment_percentage, 1) }}% Increment</span>
                                                    </div>
                                                @endif
                                                @if($app->is_promoted)
                                                    <div class="font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                                        <i class="ph ph-crown"></i>
                                                        <span>Promoted</span>
                                                    </div>
                                                @endif
                                                @if(!$app->increment_percentage && !$app->is_promoted)
                                                    <span class="text-slate-400 text-[10px]">—</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-[10px] italic flex items-center gap-1">
                                                <i class="ph ph-eye-slash text-xs"></i> Confidential
                                            </span>
                                        @endif
                                    </td>

                                    <td class="p-3.5 pr-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('appraisal.show', $app->id) }}" class="bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-xl font-bold transition-all text-xs flex items-center gap-1.5">
                                                <span>Review</span>
                                                <i class="ph ph-arrow-right text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 3: Official Letters & E-Signatures (Management / HR Only) -->
    @if($role !== 'Employee')
    <div x-show="activeTab === 'letters'" class="space-y-4">
        @if($letters->isEmpty())
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center text-2xl">
                    <i class="ph ph-stamp"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Generated Letters</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">No official appraisal, increment, or promotion letters have been published yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($letters as $ltr)
                    <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 hover:border-blue-400/40 transition-all">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg font-bold border border-blue-100 dark:border-blue-900/40 shrink-0">
                                    <i class="ph ph-file-text"></i>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black uppercase text-blue-600 dark:text-blue-400 tracking-wider">{{ $ltr->reference_no }}</span>
                                    <h4 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $ltr->title }}</h4>
                                    <p class="text-[11px] text-slate-400 font-semibold">{{ $ltr->employee?->user?->name }} &bull; {{ $ltr->employee?->designation?->name }}</p>
                                </div>
                            </div>

                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold 
                                {{ $ltr->status === 'acknowledged_by_employee' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                {{ $ltr->status === 'signed_by_management' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                {{ $ltr->status === 'pending_signatory' ? 'bg-amber-100 text-amber-800' : '' }}
                                {{ $ltr->status === 'draft' ? 'bg-slate-100 text-slate-800' : '' }}">
                                {{ ucwords(str_replace('_', ' ', $ltr->status)) }}
                            </span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 text-xs text-slate-600 dark:text-slate-300 line-clamp-3">
                            {!! strip_tags($ltr->template_content) !!}
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400">
                            <div>
                                <span>Signatory: <strong>{{ $ltr->signatory_name ?? 'Anoj / Shehan' }}</strong></span>
                                @if($ltr->signatory_signed_at)
                                    <span class="text-emerald-600 dark:text-emerald-400 block text-[10px]">
                                        <i class="ph ph-seal-check"></i> Management Signed on {{ $ltr->signatory_signed_at->format('d M Y') }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('appraisal.show', $ltr->appraisal_id ?? 1) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">
                                    View / E-Sign
                                </a>
                                @if($ltr->status === 'acknowledged_by_employee' || in_array($role, ['Super Admin', 'HR Lead']))
                                    <a href="{{ route('appraisal.letters.pdf', $ltr->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm">
                                        <i class="ph ph-download-simple"></i>
                                        <span>PDF</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    <!-- TAB: Changes Requested (For Employees) -->
    @if($role === 'Employee')
    <div x-show="activeTab === 'my_changes'" class="space-y-4">
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Changes Requested</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Formal edit requests and unlock status for your appraisal evaluation forms.</p>
                </div>
            </div>

            @if($myChangeRequests->isEmpty())
                <div class="text-center py-12 space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-xl">
                        <i class="ph ph-shield-check"></i>
                    </div>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">No Changes Requested</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto">You have not submitted any edit or unlock requests for your performance evaluations.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @foreach($myChangeRequests as $cr)
                        <div class="py-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-slate-900 dark:text-white">{{ $cr->appraisal?->cycle?->name ?? 'Appraisal Cycle' }}</span>
                                    <span class="text-[11px] text-slate-400 font-semibold">&bull; {{ $cr->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black 
                                    {{ $cr->status === 'Approved_Unlocked' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                    {{ $cr->status === 'Rejected' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}
                                    {{ $cr->status === 'Pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}">
                                    {{ $cr->status }}
                                </span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl text-slate-700 dark:text-slate-300">
                                <strong>Reason for Request:</strong> {{ $cr->reason }}
                            </div>
                            @if($cr->action_notes)
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 pl-2 border-l-2 border-slate-300 dark:border-slate-700">
                                    <strong>HR Response:</strong> {{ $cr->action_notes }} (by {{ $cr->actionedBy?->name ?? 'HR Admin' }} on {{ $cr->actioned_at?->format('d M Y') }})
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- MODAL: Create Appraisal Cycle -->
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
                        <p class="text-[11px] text-slate-400 font-semibold">Select review structure preset to auto-configure windows, periods & increment rules</p>
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
                                Progress, improvement areas & KPI adjustments. <strong>No salary increments.</strong>
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
                                Final performance evaluation, potential promotions & increments (aligned with approved budget).
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
                                    <span>Salary Increments & Promotions: <strong>Enabled</strong></span>
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
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Focus Areas & Guidelines</label>
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

    <!-- MODAL: Send Reminders -->
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
                        <option value="pending_manager_review">Pending Manager & HOD Reviews</option>
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
