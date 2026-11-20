{{-- Modernized 5-Column Leave Balance & Quota Summary Table (Apple HIG) --}}
<div class="apple-card overflow-hidden !p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[760px]">
            <thead>
                <tr class="bg-[var(--bg-elevated)] dark:bg-[#2C2C2E] border-b border-[var(--border-hairline)] text-[11px] font-semibold tracking-wider text-[var(--text-secondary)] uppercase">
                    <th scope="col" class="py-3.5 px-5 w-[34%] sticky left-0 bg-[var(--bg-elevated)] dark:bg-[#2C2C2E] z-10">
                        Leave Type
                    </th>
                    <th scope="col" class="py-3.5 px-5 text-right w-[15%]">
                        Eligible Leaves
                    </th>
                    <th scope="col" class="py-3.5 px-5 text-right w-[13%]">
                        Taken
                    </th>
                    <th scope="col" class="py-3.5 px-5 text-right w-[18%]">
                        Remaining Balance
                    </th>
                    <th scope="col" class="py-3.5 px-5 text-right w-[20%]">
                        Visual Utilization
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border-hairline)] text-xs">
                @forelse($summaryCards as $card)
                    @php
                        $balanceNum = (float)$card['balance_num'];
                        $badgeClass = match(true) {
                            $balanceNum > 0 => 'pill-apple-green',
                            default => 'bg-[var(--bg-elevated)] text-[var(--text-secondary)] border border-[var(--border-hairline)]'
                        };

                        $barColor = match(true) {
                            $card['pct_used'] >= 90 => 'bg-[#FF3B30] dark:bg-[#FF453A]',
                            $card['pct_used'] >= 60 => 'bg-[#FF9500] dark:bg-[#FF9F0A]',
                            default => 'bg-[#28CD41] dark:bg-[#30D158]'
                        };
                    @endphp
                    <tr class="group hover:bg-[var(--bg-elevated)] dark:hover:bg-[#2C2C2E] transition-colors">
                        
                        <!-- Column 1: LEAVE TYPE -->
                        <td class="py-3.5 px-5 sticky left-0 bg-[var(--bg-surface)] group-hover:bg-[var(--bg-elevated)] dark:bg-[#1C1C1E] dark:group-hover:bg-[#2C2C2E] transition-colors z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl {{ $card['theme']['icon_bg'] ?? 'bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 border border-blue-200 dark:border-blue-500/40' }} flex items-center justify-center shrink-0 shadow-2xs transition-transform group-hover:scale-105 duration-200">
                                    <i data-lucide="{{ $card['icon'] ?? 'calendar' }}" class="w-4 h-4 {{ $card['theme']['icon_text'] ?? 'text-blue-600 dark:text-blue-300' }} stroke-[2.2]"></i>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 min-w-0">
                                    <span class="text-sm font-semibold text-[var(--text-primary)] truncate">
                                        {{ $card['name'] }}
                                    </span>

                                    @if(!empty($card['badge']))
                                        <span class="inline-flex items-center text-[10px] font-medium px-2 py-0.5 rounded-full bg-[var(--bg-elevated)] text-[var(--text-secondary)] border border-[var(--border-hairline)] shrink-0">
                                            {{ $card['badge'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Column 2: ELIGIBLE LEAVES -->
                        <td class="py-3.5 px-5 text-right">
                            <span class="text-sm font-medium text-[var(--text-primary)] tabular-nums">
                                {{ $card['eligible_label'] }}
                            </span>
                        </td>

                        <!-- Column 3: TAKEN -->
                        <td class="py-3.5 px-5 text-right">
                            @if($card['taken_num'] > 0)
                                <span class="text-sm font-medium text-[var(--text-primary)] tabular-nums">
                                    {{ $card['taken_label'] }}
                                </span>
                            @else
                                <span class="text-sm font-normal text-[var(--text-tertiary)] tabular-nums">
                                    0 {{ $card['unit'] }}
                                </span>
                            @endif
                        </td>

                        <!-- Column 4: REMAINING BALANCE -->
                        <td class="py-3.5 px-5 text-right">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold tabular-nums {{ $badgeClass }}">
                                {{ $card['balance_label'] }}
                            </span>
                        </td>

                        <!-- Column 5: VISUAL UTILIZATION -->
                        <td class="py-3.5 px-5 text-right">
                            <div class="flex items-center justify-end gap-2.5">
                                <div class="w-24 sm:w-28 bg-[var(--bg-elevated)] dark:bg-[#2C2C2E] h-1.5 rounded-full overflow-hidden">
                                    <div class="h-full {{ $barColor }} rounded-full transition-all duration-500" 
                                         style="width: {{ $card['pct_used'] }}%"></div>
                                </div>
                                <span class="text-[11px] font-mono font-medium text-[var(--text-secondary)] w-8 text-right shrink-0 tabular-nums">
                                    {{ $card['pct_used'] }}%
                                </span>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-[var(--text-tertiary)] text-xs">
                            No active leave quotas found for this cycle.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
