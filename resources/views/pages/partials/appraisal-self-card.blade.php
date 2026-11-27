@php
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

<div class="space-y-2.5">
    <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">{{ $sectionTitle ?? 'Recent Appraisal' }}</h3>
    </div>

    <div class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
        <!-- Top Half: Name, Badges, Period & Primary Action -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('appraisal.show', $app->id) }}" class="font-bold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 text-sm sm:text-base transition-colors">
                        {{ $app->cycle?->name ?? 'Performance Review' }}
                    </a>
                    @if($app->cycle?->status === 'Active')
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-[#10b981] shrink-0"></span>
                            <span>Active</span>
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2 flex-wrap text-xs text-slate-500 dark:text-slate-400">
                    @if($app->cycle?->isMidYear())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 text-[11px]">
                            <i class="ph ph-calendar-check text-slate-400"></i>
                            <span>Quarter 1 &bull; Mid-Year</span>
                        </span>
                    @elseif($app->cycle?->cycle_type === 'year_end')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 text-[11px]">
                            <i class="ph ph-chart-line-up text-slate-400"></i>
                            <span>Quarter 2 &bull; Year-End</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-medium bg-slate-100 dark:bg-white/[0.04] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 text-[11px]">
                            <i class="ph ph-calendar text-slate-400"></i>
                            <span>Annual Review</span>
                        </span>
                    @endif

                    @if($app->cycle?->financial_year)
                        <span class="px-2 py-0.5 rounded-md font-medium bg-slate-100 dark:bg-white/[0.03] text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-800/80 text-[11px]">
                            {{ $app->cycle->financial_year }}
                        </span>
                    @endif

                    <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300 text-[11px] flex items-center gap-1">
                        <i class="ph ph-clock text-slate-400 text-xs"></i>
                        <span>Period: {{ $app->cycle?->performance_period ?: ($app->cycle?->isMidYear() ? 'April to September' : ($pStart && $pEnd ? $pStart->format('M Y') . ' – ' . $pEnd->format('M Y') : 'Full Period')) }}</span>
                    </span>

                    @if($cStart && $cEnd)
                        <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">
                            Window: {{ $cStart->format('d M') }} – {{ $cEnd->format('d M Y') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="shrink-0">
                @if($isPrimaryAction)
                    <a href="{{ route('appraisal.show', $app->id) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition-all inline-flex items-center gap-1.5 cursor-pointer">
                        <span>Complete Form</span>
                        <i class="ph ph-arrow-square-out text-xs"></i>
                    </a>
                @else
                    <a href="{{ route('appraisal.show', $app->id) }}" class="px-4 py-2 rounded-xl text-xs font-medium border border-slate-300 hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-600 text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-all inline-flex items-center gap-1.5 cursor-pointer">
                        <span>{{ $app->cycle?->status === 'Upcoming' ? 'View Form' : ($app->status === 'pending_self_eval' ? 'Complete Form' : 'View Form') }}</span>
                        <i class="ph ph-arrow-square-out text-xs"></i>
                    </a>
                @endif
            </div>
        </div>

        <!-- Bottom Row: Workflow Evaluators, Evaluation Status, Due Date, Score & Band -->
        <div class="grid grid-cols-2 sm:grid-cols-3 {{ $app->total_weighted_score > 0 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-3 pt-3.5 border-t border-slate-100 dark:border-slate-800/80 text-xs">
            <!-- 1. Workflow Evaluators -->
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Workflow Evaluators</span>
                <div class="space-y-0.5 text-[11px]">
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
            </div>

            <!-- 2. Evaluation Status -->
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Evaluation Status</span>
                @if($app->status === 'completed')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-[#10b981] shrink-0"></span>
                        <span>Finalized</span>
                    </span>
                @elseif($app->status === 'pending_self_eval')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-[#d97706] shrink-0"></span>
                        <span>Pending Self-Eval</span>
                    </span>
                @elseif($app->status === 'pending_manager_eval' || $app->status === 'submitted_self_eval')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400 shrink-0"></span>
                        <span>Manager Review</span>
                    </span>
                @elseif($app->status === 'pending_hod_eval' || $app->status === 'submitted_manager_eval')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400 shrink-0"></span>
                        <span>HOD Review</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
                        <span>In Review</span>
                    </span>
                @endif
            </div>

            <!-- 3. Due Date -->
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Due Date</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 block text-xs">
                    {{ $dueDate ? $dueDate->format('d M Y') : '—' }}
                </span>
                <span class="text-[10px] block {{ $dueRelativeClass }}">
                    {{ $dueRelativeText }}
                </span>
            </div>

            <!-- 4. Score & Band (Only displayed if total_weighted_score > 0) -->
            @if($app->total_weighted_score > 0)
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Score &amp; Band</span>
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
            </div>
            @endif
        </div>
    </div>
</div>
