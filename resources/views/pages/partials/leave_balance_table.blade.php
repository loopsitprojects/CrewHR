{{-- Modernized 5-Column Leave Balance & Quota Summary Table (Linear / Stripe inspired) --}}
<div class="border border-slate-200/90 dark:border-white/[0.08] rounded-2xl bg-white dark:bg-[#111827] shadow-[0_4px_20px_-2px_rgba(15,23,42,0.06),0_1px_3px_0_rgba(15,23,42,0.04)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[760px]">
            <thead>
                <tr class="bg-slate-50 dark:bg-white/[0.03] border-b border-slate-200/80 dark:border-white/[0.06] text-[11px] font-semibold tracking-wider text-slate-500 dark:text-slate-400 uppercase">
                    <th scope="col" class="py-3.5 px-4 w-[34%] sticky left-0 bg-slate-50 dark:bg-[#111827] z-10">
                        Leave Type
                    </th>
                    <th scope="col" class="py-3.5 px-4 text-right w-[15%]">
                        Eligible Leaves
                    </th>
                    <th scope="col" class="py-3.5 px-4 text-right w-[13%]">
                        Taken
                    </th>
                    <th scope="col" class="py-3.5 px-4 text-right w-[18%]">
                        Remaining Balance
                    </th>
                    <th scope="col" class="py-3.5 px-4 text-right w-[20%]">
                        Visual Utilization
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.05] text-xs">
                @forelse($summaryCards as $card)
                    @php
                        $balanceNum = (float)$card['balance_num'];
                        $badgeClass = match(true) {
                            $balanceNum > 0 => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                            default => 'bg-black/[0.04] text-slate-500 dark:bg-white/[0.06] dark:text-slate-400 border-black/[0.05] dark:border-white/[0.08]'
                        };

                        $barColor = match(true) {
                            $card['pct_used'] >= 90 => 'bg-[#FF3B30]',
                            $card['pct_used'] >= 60 => 'bg-[#FF9500]',
                            default => 'bg-[#34C759]'
                        };
                    @endphp
                    <tr @if($targetEmployee && $loggedEmployee && $targetEmployee->id === $loggedEmployee->id) @click="openApplyModal('{{ $card['id'] }}', '{{ $card['code'] }}')" @endif
                        class="group hover:bg-slate-50/70 dark:hover:bg-white/[0.03] transition-colors {{ $targetEmployee && $loggedEmployee && $targetEmployee->id === $loggedEmployee->id ? 'cursor-pointer' : '' }}">
                        
                        <!-- Column 1: LEAVE TYPE -->
                        <td class="py-3.5 px-4 sticky left-0 bg-white group-hover:bg-slate-50 dark:bg-[#111827] dark:group-hover:bg-[#161F32] transition-colors z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg {{ $card['theme']['icon_bg'] ?? 'bg-blue-50 dark:bg-blue-900/30' }} flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $card['icon'] ?? 'calendar' }}" class="w-3.5 h-3.5 {{ $card['theme']['icon_text'] ?? 'text-blue-600' }}"></i>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 min-w-0">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                                        {{ $card['name'] }}
                                    </span>

                                    @if(!empty($card['badge']))
                                        <span class="inline-flex items-center text-[10px] font-medium px-2 py-0.5 rounded-full bg-black/[0.04] text-slate-600 dark:bg-white/[0.06] dark:text-slate-400 border border-black/[0.04] dark:border-white/[0.06] shrink-0">
                                            {{ $card['badge'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Column 2: ELIGIBLE LEAVES -->
                        <td class="py-3.5 px-4 text-right">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 tabular-nums">
                                {{ $card['eligible_label'] }}
                            </span>
                        </td>

                        <!-- Column 3: TAKEN -->
                        <td class="py-3.5 px-4 text-right">
                            @if($card['taken_num'] > 0)
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300 tabular-nums">
                                    {{ $card['taken_label'] }}
                                </span>
                            @else
                                <span class="text-sm font-normal text-slate-400 dark:text-slate-500 tabular-nums">
                                    0 {{ $card['unit'] }}
                                </span>
                            @endif
                        </td>

                        <!-- Column 4: REMAINING BALANCE -->
                        <td class="py-3.5 px-4 text-right">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $badgeClass }} tabular-nums">
                                {{ $card['balance_label'] }}
                            </span>
                        </td>

                        <!-- Column 5: VISUAL UTILIZATION -->
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-2.5">
                                <div class="w-24 sm:w-28 bg-black/[0.05] dark:bg-white/[0.08] h-1.5 rounded-full overflow-hidden">
                                    <div class="h-full {{ $barColor }} rounded-full transition-all duration-500" 
                                         style="width: {{ $card['pct_used'] }}%"></div>
                                </div>
                                <span class="text-[11px] font-mono font-medium text-slate-500 dark:text-slate-400 w-8 text-right shrink-0 tabular-nums">
                                    {{ $card['pct_used'] }}%
                                </span>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                            No active leave quotas found for this cycle.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
