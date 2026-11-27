<div class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-xs">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800/80">
                <tr>
                    <th class="py-3 px-4 pl-5">Review Cycle &amp; Track</th>
                    <th class="py-3 px-3">Review Window &amp; Period</th>
                    <th class="py-3 px-3">Workflow Evaluators</th>
                    <th class="py-3 px-3">Evaluation Status</th>
                    <th class="py-3 px-3">Due Date</th>
                    <th class="py-3 px-4 pr-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                @foreach($apps as $app)
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
                <tr class="hover:bg-slate-50/70 dark:hover:bg-[#161f30] transition-colors">
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

                    <!-- 5. Due Date -->
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

                    <!-- 6. Actions -->
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
            </tbody>
        </table>
    </div>
</div>
