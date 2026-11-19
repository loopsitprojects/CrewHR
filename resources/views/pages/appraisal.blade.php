@extends('layouts.app', ['title' => 'Loops HR - Performance Appraisal Suite', 'breadcrumb' => 'Appraisal System'])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'roster',
    showCycleModal: false,
    showReminderModal: false,
    statusFilter: '{{ request('status', '') }}',
    deptFilter: '{{ request('department_id', '') }}'
}">

    <!-- Top Action Bar & Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl flex items-center justify-between text-xs font-bold text-emerald-800 dark:text-emerald-300">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-lg text-emerald-500"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="ph ph-x"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl flex items-center justify-between text-xs font-bold text-rose-800 dark:text-rose-300">
            <div class="flex items-center gap-2">
                <i class="ph ph-warning-circle text-lg text-rose-500"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="ph ph-x"></i></button>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>Enterprise Performance Appraisals</span>
                <span class="text-[11px] font-extrabold px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                    Role: {{ $role }}
                </span>
            </h1>
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                Two-tier & three-tier evaluation workflows, automated scoring, increment management, and e-signed letters.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Cycle Selector Dropdown -->
            <form method="GET" action="{{ route('appraisal.index') }}" class="flex items-center">
                <select name="cycle_id" onchange="this.form.submit()" class="border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-white dark:bg-[#111927] text-slate-800 dark:text-slate-200 p-2 shadow-sm focus:ring-2 focus:ring-blue-500/20">
                    @foreach($cycles as $c)
                        <option value="{{ $c->id }}" {{ ($activeCycle?->id == $c->id) ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->status }})
                        </option>
                    @endforeach
                </select>
            </form>

            @if(in_array($role, ['Super Admin', 'HR Lead']))
                <a href="{{ route('appraisal.change-requests.index') }}" class="relative bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm transition-all">
                    <i class="ph ph-lock-key-open text-amber-500 text-sm"></i>
                    <span>Change Requests</span>
                    @if($pendingChangeRequestsCount > 0)
                        <span class="w-5 h-5 rounded-full bg-rose-500 text-white text-[10px] font-black flex items-center justify-center animate-pulse">
                            {{ $pendingChangeRequestsCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('appraisal.templates.index') }}" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm transition-all">
                    <i class="ph ph-sliders-horizontal text-blue-500 text-sm"></i>
                    <span>KPI Templates</span>
                </a>

                <a href="{{ route('appraisal.analytics') }}" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm transition-all">
                    <i class="ph ph-chart-line-up text-emerald-500 text-sm"></i>
                    <span>Analytics</span>
                </a>

                <button @click="showReminderModal = true" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm transition-all">
                    <i class="ph ph-bell-ringing text-indigo-500 text-sm"></i>
                    <span>Send Reminders</span>
                </button>

                <button @click="showCycleModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all shrink-0 cursor-pointer">
                    <i class="ph ph-plus-circle text-base"></i>
                    <span>New Appraisal Cycle</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Active Cycle Banner -->
    @if($activeCycle)
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-700 via-indigo-700 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-3">
                <span class="bg-white/20 backdrop-blur-md px-3.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border border-white/20">
                    {{ $activeCycle->review_period }} Cycle &bull; {{ $activeCycle->status }}
                </span>
                @if($activeCycle->self_eval_deadline)
                    <span class="text-xs font-bold text-blue-200 flex items-center gap-1.5">
                        <i class="ph ph-clock text-sm"></i>
                        Self-Eval Deadline: {{ $activeCycle->self_eval_deadline->format('d M Y') }}
                    </span>
                @endif
            </div>
            <div class="text-xs font-extrabold text-blue-100 flex items-center gap-2">
                <span>{{ $completedCount }} of {{ $totalAppraisals }} Appraisals Finalized ({{ $completionRate }}%)</span>
            </div>
        </div>

        <div class="space-y-2">
            <h2 class="text-2xl sm:text-3xl font-black tracking-tight">{{ $activeCycle->name }}</h2>
            <p class="text-xs text-blue-100 max-w-2xl leading-relaxed">
                {{ $activeCycle->description ?? 'Annual employee evaluation cycle covering competencies, KPI achievements, career progression, and salary increment terms.' }}
            </p>
        </div>

        <div class="mt-6">
            <div class="w-full bg-black/30 h-2.5 rounded-full overflow-hidden p-0.5 border border-white/10">
                <div class="bg-gradient-to-r from-blue-300 to-emerald-400 h-full rounded-full transition-all duration-500" style="width: {{ $completionRate }}%;"></div>
            </div>
        </div>
    </div>
    @endif

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Total Evaluated</span>
                <i class="ph ph-users text-lg text-blue-500"></i>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $totalAppraisals }}</div>
            <span class="text-[10px] text-slate-400 font-semibold">In current cycle</span>
        </div>

        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Self-Evaluation</span>
                <i class="ph ph-hourglass-high text-lg text-amber-500"></i>
            </div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ $pendingSelfCount }}</div>
            <span class="text-[10px] text-amber-500/80 font-semibold">Pending employee</span>
        </div>

        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Review Queue</span>
                <i class="ph ph-git-pull-request text-lg text-indigo-500"></i>
            </div>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ $pendingReviewCount }}</div>
            <span class="text-[10px] text-indigo-500/80 font-semibold">Pending Manager / HOD</span>
        </div>

        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Completed</span>
                <i class="ph ph-check-circle text-lg text-emerald-500"></i>
            </div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $completedCount }}</div>
            <span class="text-[10px] text-emerald-500/80 font-semibold">Fully finalized</span>
        </div>

        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-sm col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Average Score</span>
                <i class="ph ph-star text-lg text-amber-400"></i>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white flex items-baseline gap-1">
                <span>{{ number_format($averageScore, 2) }}</span>
                <span class="text-xs font-semibold text-slate-400">/ 5.00</span>
            </div>
            <span class="text-[10px] text-slate-400 font-semibold">{{ \Modules\Performance\Models\Appraisal::scoreToBand($averageScore) }}</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-6">
            <button @click="activeTab = 'roster'" :class="activeTab === 'roster' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-list-dashes text-sm"></i>
                <span>Appraisals Roster ({{ $appraisals->count() }})</span>
            </button>

            @if($employee)
                <button @click="activeTab = 'my_appraisal'" :class="activeTab === 'my_appraisal' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-user text-sm"></i>
                    <span>My Self-Evaluation</span>
                </button>
            @endif

            <button @click="activeTab = 'letters'" :class="activeTab === 'letters' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" class="pb-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-file-text text-sm"></i>
                <span>Official Letters & E-Signatures ({{ $letters->count() }})</span>
            </button>
        </div>

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
                                <th class="p-3.5">Increment / Promo</th>
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
                                                <span class="text-[10px] text-slate-400 font-semibold italic">2-Tier Review</span>
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

                                        @if($app->is_locked)
                                            <span class="block text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                                <i class="ph ph-lock text-xs"></i> Locked
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
                                            <span class="text-slate-400 italic text-[11px]">Not rated yet</span>
                                        @endif
                                    </td>

                                    <td class="p-3.5">
                                        @if(in_array($role, ['Super Admin', 'HR Lead']) || ($app->is_increment_visible_to_employee && $employee?->id == $app->employee_id) || ($app->is_increment_visible_to_hod && $employee?->id == $app->hod_id))
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
                                                <span>Review Form</span>
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
            $myAppraisal = $appraisals->where('employee_id', $employee->id)->first();
        @endphp

        @if($myAppraisal)
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
                    <div>
                        <span class="text-[10px] font-black uppercase text-blue-600 dark:text-blue-400 tracking-wider">My Annual Review</span>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ $myAppraisal->cycle?->name }}</h2>
                        <p class="text-xs text-slate-400 mt-1">Review your self-ratings, submit comments, and track manager and HOD evaluations.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full text-xs font-bold 
                            {{ $myAppraisal->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            Status: {{ ucwords(str_replace('_', ' ', $myAppraisal->status)) }}
                        </span>
                        <a href="{{ route('appraisal.show', $myAppraisal->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20">
                            <span>Open Full Evaluation Sheet</span>
                            <i class="ph ph-arrow-square-out text-sm"></i>
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Self-Evaluation</div>
                        <div class="text-sm font-black text-slate-900 dark:text-white mt-1">
                            {{ $myAppraisal->self_submitted_at ? 'Submitted on ' . $myAppraisal->self_submitted_at->format('d M Y') : 'Pending Submission' }}
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Reporting Structure</div>
                        <div class="text-sm font-black text-slate-900 dark:text-white mt-1">
                            {{ $myAppraisal->reportingManager ? $myAppraisal->reportingManager->user->name . ' (Manager) → ' : '' }}
                            {{ $myAppraisal->hod->user->name ?? 'HOD' }} (HOD)
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Final Weighted Score</div>
                        <div class="text-sm font-black text-slate-900 dark:text-white mt-1">
                            {{ $myAppraisal->total_weighted_score > 0 ? $myAppraisal->total_weighted_score . ' / 5.00 (' . $myAppraisal->performance_band . ')' : 'Pending Finalization' }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-12 text-center space-y-2">
                <i class="ph ph-smiley text-3xl text-slate-400"></i>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Active Appraisal Assigned</h3>
                <p class="text-xs text-slate-400">You do not currently have a pending evaluation assigned in this review cycle.</p>
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 3: Official Letters & E-Signatures -->
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

    <!-- MODAL: Create Appraisal Cycle -->
    <div x-show="showCycleModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showCycleModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center">
                        <i class="ph ph-calendar-plus text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">Create Appraisal Cycle</h3>
                        <p class="text-[11px] text-slate-400 font-semibold">Define performance review period and timeline</p>
                    </div>
                </div>
                <button @click="showCycleModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('appraisal.cycles.store') }}" method="POST" class="space-y-4 text-xs font-bold">
                @csrf
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Cycle Name</label>
                    <input type="text" name="name" required placeholder="e.g. 2026 Annual Employee Performance Review" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Review Period</label>
                        <select name="review_period" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="Annual">Annual</option>
                            <option value="Bi-Annual">Bi-Annual</option>
                            <option value="Probation">Probationary</option>
                            <option value="Quarterly">Quarterly</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Start Date</label>
                        <input type="date" name="start_date" required value="{{ date('Y-01-01') }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">End Date</label>
                        <input type="date" name="end_date" required value="{{ date('Y-12-31') }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Self-Eval Deadline</label>
                        <input type="date" name="self_eval_deadline" value="{{ date('Y-11-30') }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Manager / HOD Deadline</label>
                    <input type="date" name="manager_eval_deadline" value="{{ date('Y-12-15') }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Description / Guidelines</label>
                    <textarea name="description" rows="2" placeholder="Guidelines for staff and managers..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
                </div>

                <label class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 rounded-xl cursor-pointer">
                    <input type="checkbox" name="initialize_all_employees" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs font-bold text-blue-900 dark:text-blue-300">
                        Automatically initialize evaluation forms for all active employees
                    </span>
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showCycleModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold">Launch Cycle</button>
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
@endsection
