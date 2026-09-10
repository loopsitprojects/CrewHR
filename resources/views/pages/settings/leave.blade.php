<div class="space-y-6">
    <!-- 1. Interactive Leave Types Management (CRUD & On/Off Toggles) -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-100 dark:border-emerald-900/60 shrink-0">
                    <i class="ph ph-list-checks text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Leave Types & Policy Configuration ({{ count($leaveTypes ?? []) }})</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Switch leave categories on/off, manage annual quotas, paid status, and add custom leave types</p>
                </div>
            </div>
            <button type="button" @click="addLeaveTypeModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 active:scale-95 shadow-md shadow-emerald-500/25 transition-all cursor-pointer shrink-0">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Add Leave Type</span>
            </button>
        </div>

        <!-- Leave Types Table -->
        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Leave Type</th>
                        <th class="p-3">Code</th>
                        <th class="p-3 text-center">Default Quota</th>
                        <th class="p-3 text-center">Type</th>
                        <th class="p-3 text-center">Status (ON / OFF)</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-semibold text-slate-800 dark:text-slate-200">
                    @forelse($leaveTypes ?? [] as $lt)
                        @php
                            $isCore = in_array(strtoupper($lt->code), ['ANNUAL', 'CASUAL', 'SHORT', 'MEDICAL']);
                            $isActive = (bool) ($lt->is_active ?? true);
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-[#1e2d4d]/60 transition-colors {{ !$isActive ? 'opacity-60 bg-slate-50/40 dark:bg-slate-900/40' : '' }}">
                            <td class="p-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-xs {{ $isActive ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                        {{ substr($lt->code, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ $lt->name }}</span>
                                            @if($isCore)
                                                <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-900">Core Act</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium line-clamp-1">{{ $lt->description ?? 'Standard leave policy category' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 font-mono font-bold text-xs text-slate-600 dark:text-slate-300">
                                <span class="bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-[11px]">{{ $lt->code }}</span>
                            </td>
                            <td class="p-3 text-center font-black text-slate-900 dark:text-white">
                                {{ $lt->days }} {{ strtoupper($lt->code) === 'SHORT' ? 'Slots/Mo' : 'Days' }}
                            </td>
                            <td class="p-3 text-center">
                                @if($lt->is_paid)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Paid</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Unpaid</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <form action="{{ route('settings.leave_type.toggle', $lt->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" 
                                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $isActive ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-700' }}"
                                            title="{{ $isActive ? 'Click to deactivate leave type' : 'Click to activate leave type' }}">
                                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $isActive ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                    </button>
                                </form>
                            </td>
                            <td class="p-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" 
                                            @click="editLeaveType = { id: '{{ $lt->id }}', name: '{{ addslashes($lt->name) }}', code: '{{ $lt->code }}', days: {{ $lt->days }}, is_paid: {{ $lt->is_paid ? 1 : 0 }}, is_active: {{ $isActive ? 1 : 0 }}, description: '{{ addslashes($lt->description ?? '') }}' }; editLeaveTypeModal = true" 
                                            class="p-1.5 text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" 
                                            title="Edit Leave Type">
                                        <i class="ph ph-pencil-simple text-sm"></i>
                                    </button>
                                    
                                    @if(!$isCore)
                                        <form action="{{ route('settings.leave_type.destroy', $lt->id) }}" method="POST" onsubmit="return confirm('Permanently delete leave type {{ addslashes($lt->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Delete Leave Type">
                                                <i class="ph ph-trash text-sm"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400">No leave types configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Sri Lanka Leave Policy Rules Overview & Statutory Quotas -->
    <form action="{{ route('settings.save') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="active_module" value="leave">

        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-100 dark:border-emerald-900/60">
                        <i class="ph ph-scales text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Sri Lanka Shop & Office Employees Act Entitlements</h2>
                        <p class="text-[11px] font-semibold text-slate-400">Statutory leave frameworks & monthly allowance configurations</p>
                    </div>
                </div>
                <span class="bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-emerald-200/60 dark:border-emerald-900/60">Act No. 19 of 1954 Compliant</span>
            </div>

            <!-- Rules Summary Guidance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="bg-blue-50/60 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/60 p-3.5 rounded-xl space-y-1">
                    <h4 class="font-bold text-blue-900 dark:text-blue-300 flex items-center gap-1.5"><i class="ph ph-airplane-tilt text-blue-600 dark:text-blue-400"></i> ANNUAL LEAVE</h4>
                    <p class="text-[11px] text-blue-700 dark:text-blue-400">1st Year: Pro-rata based on joining quarter (14d, 10d, 7d, 4d). 2nd Year Onwards: 14 Days full entitlement.</p>
                </div>

                <div class="bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 p-3.5 rounded-xl space-y-1">
                    <h4 class="font-bold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5"><i class="ph ph-coffee text-emerald-600 dark:text-emerald-400"></i> CASUAL LEAVE</h4>
                    <p class="text-[11px] text-emerald-700 dark:text-emerald-400">Permanent: 7 Days Max Casual Leave per year. Maximum 3 consecutive days in a single application.</p>
                </div>

                <div class="bg-amber-50/60 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/60 p-3.5 rounded-xl space-y-1">
                    <h4 class="font-bold text-amber-900 dark:text-amber-300 flex items-center gap-1.5"><i class="ph ph-clock text-amber-600 dark:text-amber-400"></i> SHORT LEAVE</h4>
                    <p class="text-[11px] text-amber-700 dark:text-amber-400">Allocated as 2 Short Leaves per calendar month (1.5–2 hours each). Resets monthly and never deducted from annual leave.</p>
                </div>
            </div>

            <!-- Quota Input Fields -->
            <div class="pt-2">
                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider mb-3">Statutory Quota Limits Override</h3>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-xs font-semibold">
                    <div class="bg-slate-50 dark:bg-[#1e2d4d] p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                        <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Annual (Days)</label>
                        <input type="number" name="annual_leave_max" value="{{ $settingsRaw['annual_leave_max'] ?? 14 }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2 text-center">
                    </div>
                    <div class="bg-slate-50 dark:bg-[#1e2d4d] p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                        <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Casual (Days)</label>
                        <input type="number" name="casual_leave_max" value="{{ $settingsRaw['casual_leave_max'] ?? 7 }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2 text-center">
                    </div>
                    <div class="bg-slate-50 dark:bg-[#1e2d4d] p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                        <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Medical (Days)</label>
                        <input type="number" name="medical_leave_max" value="{{ $settingsRaw['medical_leave_max'] ?? 7 }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2 text-center">
                    </div>
                    <div class="bg-slate-50 dark:bg-[#1e2d4d] p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                        <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Short (Slots/Mo)</label>
                        <input type="number" name="short_leave_max" value="{{ $settingsRaw['short_leave_max'] ?? 2 }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2 text-center">
                    </div>
                    <div class="bg-slate-50 dark:bg-[#1e2d4d] p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                        <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Duty (Days)</label>
                        <input type="number" name="duty_leave_max" value="{{ $settingsRaw['duty_leave_max'] ?? 30 }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2 text-center">
                    </div>
                    <div class="bg-slate-50 dark:bg-[#1e2d4d] p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                        <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Lieu (Days)</label>
                        <input type="number" name="lieu_leave_max" value="{{ $settingsRaw['lieu_leave_max'] ?? 2 }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2 text-center">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Holiday & Day Types Calendar Engine (Drag & Drop Manual Engine) -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden" 
             x-data="holidayCalendar()" x-init="init()">
            
            <!-- Calendar Top Navigation & Month Selector -->
            <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-[#152038]">
                <div class="flex items-center gap-3">
                    <div class="relative flex items-center gap-1.5">
                        <select x-model="year" @change="setYear($event.target.value)" class="text-sm sm:text-base font-black text-slate-800 dark:text-white bg-transparent border-none focus:ring-0 p-0 cursor-pointer pr-4">
                            @for($y = 2024; $y <= 2030; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                        <select x-model="month" @change="setMonth($event.target.value)" class="text-sm sm:text-base font-black text-slate-800 dark:text-white bg-transparent border-none focus:ring-0 p-0 cursor-pointer pr-4">
                            <template x-for="(mName, idx) in monthNames" :key="idx">
                                <option :value="idx + 1" x-text="mName"></option>
                            </template>
                        </select>
                        <i class="ph ph-caret-down text-xs text-slate-400"></i>
                    </div>
                </div>

                <!-- Navigation Controls (< Today >) -->
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="prevMonth()" class="w-8 h-8 rounded-full border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition-all cursor-pointer shadow-2xs" title="Previous Month">
                        <i class="ph ph-caret-left text-sm"></i>
                    </button>
                    <button type="button" @click="goToday()" class="px-3.5 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all cursor-pointer shadow-2xs">
                        Today
                    </button>
                    <button type="button" @click="nextMonth()" class="w-8 h-8 rounded-full border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition-all cursor-pointer shadow-2xs" title="Next Month">
                        <i class="ph ph-caret-right text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Toast Notification Banner -->
            <div x-show="showToast" x-transition class="bg-teal-600 text-white text-xs font-bold px-4 py-2 flex items-center justify-between" x-cloak>
                <div class="flex items-center gap-2">
                    <i class="ph ph-check-circle text-base"></i>
                    <span x-text="toastMessage"></span>
                </div>
                <button type="button" @click="showToast = false" class="text-white hover:opacity-80"><i class="ph ph-x text-sm"></i></button>
            </div>

            <!-- Main Calendar Split: Day Types Palette (Left) & Calendar Grid (Right) -->
            <div class="flex flex-col lg:flex-row min-h-[480px]">
                <!-- Left Sidebar: Day Types Palette -->
                <div class="w-full lg:w-60 bg-slate-50/70 dark:bg-[#121c32] p-4 border-b lg:border-b-0 lg:border-r border-slate-200/80 dark:border-slate-800 shrink-0 space-y-3">
                    <div class="flex items-center justify-between gap-1">
                        <div>
                            <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">Day Types</h3>
                            <p class="text-[10px] font-semibold text-slate-400 mt-0.5">Drag & Drop to Calendar</p>
                        </div>
                        <button type="button" 
                                @click="openAddDayTypeModal()" 
                                class="px-2 py-1 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-extrabold flex items-center gap-1 shadow-xs transition-all cursor-pointer"
                                title="Add New Day Type">
                            <i class="ph ph-plus text-xs font-bold"></i> Add
                        </button>
                    </div>

                    <div class="space-y-2 pt-1">
                        <template x-for="dt in dayTypes" :key="dt.id || dt.name">
                            <div draggable="true"
                                 @dragstart="startDrag(dt, $event)"
                                 class="group bg-white dark:bg-[#1a2744] border border-slate-200/90 dark:border-slate-700/80 rounded-xl p-2.5 flex items-center justify-between cursor-grab active:cursor-grabbing hover:shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-all select-none relative">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <span class="w-3.5 h-3.5 rounded-full shrink-0 shadow-2xs" :style="`background-color: ${dt.color}`"></span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate" x-text="dt.name"></span>
                                </div>
                                
                                <div class="flex items-center gap-0.5">
                                    <!-- Edit Day Type -->
                                    <button type="button" 
                                            @click.stop="openEditDayTypeModal(dt)"
                                            class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-blue-600 transition-all" 
                                            title="Edit Day Type">
                                        <i class="ph ph-pencil-simple text-xs"></i>
                                    </button>
                                    <!-- Delete Day Type -->
                                    <button type="button" 
                                            @click.stop="deleteDayType(dt)"
                                            class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-rose-600 transition-all" 
                                            title="Delete Day Type">
                                        <i class="ph ph-trash text-xs"></i>
                                    </button>
                                    <i class="ph ph-dots-six-vertical text-slate-400 text-sm shrink-0 ml-0.5"></i>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="pt-2 text-[10px] text-slate-400 dark:text-slate-500 font-semibold space-y-1 border-t border-slate-200/60 dark:border-slate-800">
                        <p>• Drag onto any date to assign.</p>
                        <p>• Hover day type to edit/delete.</p>
                        <p>• Click date or (✕) to remove.</p>
                    </div>
                </div>

                <!-- Right Area: Monthly Calendar Grid -->
                <div class="flex-1 flex flex-col overflow-x-auto min-w-0">
                    <!-- Days Header (Teal Ribbon) -->
                    <div class="grid grid-cols-7 bg-[#00cba9] text-white text-center text-xs font-black uppercase tracking-wider py-2.5 shrink-0 select-none">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>

                    <!-- Day Grid Body -->
                    <div class="grid grid-cols-7 flex-1 bg-white dark:bg-[#152038] divide-x divide-y divide-slate-100 dark:divide-slate-800/80 border-b border-slate-200/80 dark:border-slate-800">
                        <template x-for="(cell, idx) in calendarDays" :key="idx">
                            <div @dragover.prevent="onDragOver($event)"
                                 @drop.prevent="dropDayType(cell.dateStr, $event)"
                                 @click="openEdit(cell.dateStr)"
                                 class="min-h-[84px] sm:min-h-[96px] p-2 flex flex-col justify-between transition-colors relative cursor-pointer group"
                                 :class="{
                                     'bg-slate-50/40 dark:bg-slate-900/40 text-slate-300 dark:text-slate-600': !cell.isCurrentMonth,
                                     'bg-[#7fffd4]/30 dark:bg-cyan-950/40 ring-1 ring-cyan-400 ring-inset': cell.isToday && cell.isCurrentMonth,
                                     'hover:bg-slate-50/80 dark:hover:bg-[#1e2d4d]/60': cell.isCurrentMonth && !cell.isToday
                                 }">
                                
                                <!-- Top Row: Date Number -->
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold select-none"
                                          :class="{
                                              'text-rose-400 dark:text-rose-400': cell.isSunday && cell.isCurrentMonth,
                                              'text-slate-700 dark:text-slate-200 font-extrabold': !cell.isSunday && cell.isCurrentMonth,
                                              'text-slate-300 dark:text-slate-600': !cell.isCurrentMonth
                                          }"
                                          x-text="cell.dayNum">
                                    </span>

                                    <!-- Quick Action Indicators -->
                                    <template x-if="getHolidaysForDate(cell.dateStr)">
                                        <button type="button" 
                                                @click.stop="removeHoliday(cell.dateStr)" 
                                                class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-600 transition-opacity p-0.5" 
                                                title="Reset to Working Day">
                                            <i class="ph ph-x text-xs"></i>
                                        </button>
                                    </template>
                                </div>

                                <!-- Middle / Bottom: Holiday Badge if assigned -->
                                <div class="mt-1">
                                    <template x-if="getHolidaysForDate(cell.dateStr)">
                                        <div class="px-1.5 py-1 rounded-lg text-[10px] font-bold border shadow-2xs flex items-center gap-1 leading-tight line-clamp-2"
                                             :style="`background-color: ${getDayTypeColor(getHolidaysForDate(cell.dateStr).category || getHolidaysForDate(cell.dateStr).type).bg}; border-color: ${getDayTypeColor(getHolidaysForDate(cell.dateStr).category || getHolidaysForDate(cell.dateStr).type).border}; color: ${getDayTypeColor(getHolidaysForDate(cell.dateStr).category || getHolidaysForDate(cell.dateStr).type).text}`">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="`background-color: ${getDayTypeColor(getHolidaysForDate(cell.dateStr).category || getHolidaysForDate(cell.dateStr).type).color}`"></span>
                                            <span class="truncate" x-text="getHolidaysForDate(cell.dateStr).title || getHolidaysForDate(cell.dateStr).category"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- MODAL 1: Edit / Custom Holiday Title Modal for a specific date -->
            <div x-show="editModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
                <div class="bg-white dark:bg-[#152038] w-full max-w-sm rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl p-5 space-y-4" @click.outside="editModal = false">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-calendar-plus text-lg text-teal-600 dark:text-teal-400"></i>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Configure Date Holiday</h3>
                        </div>
                        <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-base"></i></button>
                    </div>

                    <div class="space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Selected Date</label>
                            <input type="date" x-model="editForm.date" readonly class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-100 dark:bg-slate-800 p-2 text-xs font-mono font-bold">
                        </div>

                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Day Type</label>
                            <select x-model="editForm.day_type" @change="onDayTypeChangeInEditForm()" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] p-2 text-xs font-bold">
                                <template x-for="dt in dayTypes" :key="dt.id || dt.name">
                                    <option :value="dt.name" x-text="dt.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Mercantile / Non-Mercantile Selector -->
                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1.5 uppercase font-bold">Mercantile Classification</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-1.5 p-2 rounded-xl border cursor-pointer transition-all text-[11px]"
                                       :class="editForm.is_mercantile ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-500 text-emerald-800 dark:text-emerald-300 font-black ring-1 ring-emerald-500/20' : 'bg-slate-50 dark:bg-[#1e2d4d] border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'">
                                    <input type="radio" :value="true" x-model="editForm.is_mercantile" class="text-emerald-600 focus:ring-emerald-500 text-xs">
                                    <span>Mercantile</span>
                                </label>
                                <label class="flex items-center gap-1.5 p-2 rounded-xl border cursor-pointer transition-all text-[11px]"
                                       :class="!editForm.is_mercantile ? 'bg-blue-50 dark:bg-blue-950/50 border-blue-500 text-blue-800 dark:text-blue-300 font-black ring-1 ring-blue-500/20' : 'bg-slate-50 dark:bg-[#1e2d4d] border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'">
                                    <input type="radio" :value="false" x-model="editForm.is_mercantile" class="text-blue-600 focus:ring-blue-500 text-xs">
                                    <span>Non-Mercantile</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Custom Title / Name</label>
                            <input type="text" x-model="editForm.title" placeholder="e.g. Christmas Day, Deepavali, Company Outing" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] p-2 text-xs font-bold">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="removeHoliday(editForm.date)" class="px-3 py-1.5 rounded-xl text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-xs font-bold transition-all">
                            Reset to Working Day
                        </button>
                        <button type="button" @click="submitEditModal()" class="px-4 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-md shadow-teal-500/20 transition-all">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL 2: Add New Day Type Modal -->
            <div x-show="addDayTypeModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
                <div class="bg-white dark:bg-[#152038] w-full max-w-sm rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl p-5 space-y-4" @click.outside="addDayTypeModal = false">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-plus-circle text-lg text-teal-600 dark:text-teal-400"></i>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Add New Day Type</h3>
                        </div>
                        <button type="button" @click="addDayTypeModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-base"></i></button>
                    </div>

                    <div class="space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Day Type Name</label>
                            <input type="text" x-model="newDayType.name" placeholder="e.g. Special Holiday, Founders Day" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] p-2.5 text-xs font-bold">
                        </div>

                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Color Palette</label>
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <template x-for="c in colorPresets" :key="c">
                                    <button type="button" 
                                             @click="newDayType.color = c" 
                                             class="w-6 h-6 rounded-full transition-transform cursor-pointer ring-offset-2"
                                             :class="newDayType.color === c ? 'scale-110 ring-2 ring-slate-900 dark:ring-white' : 'hover:scale-105'"
                                             :style="`background-color: ${c}`"></button>
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="newDayType.color" class="h-8 w-12 rounded cursor-pointer border-0 bg-transparent">
                                <input type="text" x-model="newDayType.color" class="flex-1 border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] p-1.5 text-xs font-mono font-bold">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Description (Optional)</label>
                            <textarea x-model="newDayType.description" rows="2" placeholder="Notes on this day type..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] p-2 text-xs font-bold"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="addDayTypeModal = false" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100 text-xs font-bold">Cancel</button>
                        <button type="button" @click="submitAddDayType()" class="px-4 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-md shadow-teal-500/20">Create Day Type</button>
                    </div>
                </div>
            </div>

            <!-- MODAL 3: Edit Day Type Modal -->
            <div x-show="editDayTypeModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
                <div class="bg-white dark:bg-[#152038] w-full max-w-sm rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl p-5 space-y-4" @click.outside="editDayTypeModal = false">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-pencil-simple text-lg text-blue-600 dark:text-blue-400"></i>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Edit Day Type</h3>
                        </div>
                        <button type="button" @click="editDayTypeModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-base"></i></button>
                    </div>

                    <div class="space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Day Type Name</label>
                            <input type="text" x-model="editingDayType.name" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] p-2.5 text-xs font-bold">
                        </div>

                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Color Palette</label>
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <template x-for="c in colorPresets" :key="c">
                                    <button type="button" 
                                             @click="editingDayType.color = c" 
                                             class="w-6 h-6 rounded-full transition-transform cursor-pointer ring-offset-2"
                                             :class="editingDayType.color === c ? 'scale-110 ring-2 ring-slate-900 dark:ring-white' : 'hover:scale-105'"
                                             :style="`background-color: ${c}`"></button>
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="editingDayType.color" class="h-8 w-12 rounded cursor-pointer border-0 bg-transparent">
                                <input type="text" x-model="editingDayType.color" class="flex-1 border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] p-1.5 text-xs font-mono font-bold">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-400 text-[11px] mb-1 uppercase font-bold">Description (Optional)</label>
                            <textarea x-model="editingDayType.description" rows="2" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] p-2 text-xs font-bold"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="deleteDayType(editingDayType)" class="px-3 py-1.5 rounded-xl text-rose-600 hover:bg-rose-50 text-xs font-bold">Delete</button>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="editDayTypeModal = false" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100 text-xs font-bold">Cancel</button>
                            <button type="button" @click="submitEditDayType()" class="px-4 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 4. Application Policies & Calendar Rules -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <i class="ph ph-sliders text-emerald-600 dark:text-emerald-400 text-lg"></i>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Leave Application & Calendar Rules</h2>
            </div>

            <div class="space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <input type="checkbox" name="allow_half_day" value="1" {{ ($settingsRaw['allow_half_day'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <span class="text-slate-900 dark:text-white font-extrabold block">Allow Half-Day Requests (0.5 Days)</span>
                        <span class="text-[11px] font-normal text-slate-400">Enables staff to apply for Morning (8:30 AM–1:00 PM) or Afternoon (1:00 PM–5:30 PM) half-day slots</span>
                    </div>
                </label>

                <!-- Weekend Off Days -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                    <span class="text-slate-900 dark:text-white font-black text-xs flex items-center gap-1.5">
                        <i class="ph ph-calendar-x text-rose-500 text-base"></i> Weekend Non-Working Days Configuration:
                    </span>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-[#1e2d4d] px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                            <input type="checkbox" name="weekend_saturday_off" value="1" {{ ($settingsRaw['weekend_saturday_off'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="font-bold text-slate-900 dark:text-white">Saturday (Non-Working Day Off)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-[#1e2d4d] px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                            <input type="checkbox" name="weekend_sunday_off" value="1" {{ ($settingsRaw['weekend_sunday_off'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="font-bold text-slate-900 dark:text-white">Sunday (Non-Working Day Off)</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Actions -->
        <div class="flex justify-end pt-2">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-emerald-500/20 transition-all cursor-pointer">
                <i class="ph ph-floppy-disk text-base"></i>
                <span>Save Leave Policy Settings</span>
            </button>
        </div>
    </form>
</div>

<script>
function holidayCalendar() {
    return {
        year: {{ (int) date('Y') }},
        month: {{ (int) date('n') }},
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        colorPresets: ['#9333ea', '#2563eb', '#854d0e', '#db2777', '#4f46e5', '#0891b2', '#059669', '#d97706', '#ea580c', '#e11d48', '#475569'],
        dayTypes: @json(\Modules\Leave\Models\DayType::orderBy('id')->get()),
        draggedType: null,
        holidays: @json(\Modules\Leave\Models\Holiday::all()),
        editModal: false,
        addDayTypeModal: false,
        editDayTypeModal: false,
        activeCell: null,
        editForm: { date: '', day_type: '', is_mercantile: true, title: '', description: '' },
        newDayType: { name: '', color: '#9333ea', is_mercantile: true, description: '' },
        editingDayType: { id: null, name: '', color: '#9333ea', is_mercantile: true, description: '' },
        toastMessage: '',
        showToast: false,

        init() {
            if (!this.dayTypes || this.dayTypes.length === 0) {
                this.dayTypes = [
                    { name: 'Additional Company holiday', color: '#9333ea', is_mercantile: true },
                    { name: 'Mercantile Holiday', color: '#2563eb', is_mercantile: true },
                    { name: 'Poya Day', color: '#854d0e', is_mercantile: false },
                    { name: 'Public Holiday', color: '#db2777', is_mercantile: false }
                ];
            }
        },

        get currentMonthName() {
            return this.monthNames[this.month - 1];
        },

        getHolidaysForDate(dateStr) {
            return this.holidays.find(h => h.date === dateStr);
        },

        getDayTypeColor(typeOrCategory) {
            if (!typeOrCategory) return { color: '#64748b', bg: '#f1f5f9', border: '#cbd5e1', text: '#1e293b' };
            const found = this.dayTypes.find(dt => dt.name.toLowerCase() === typeOrCategory.toLowerCase()) || null;
            const hex = found ? found.color : '#3b82f6';
            return {
                color: hex,
                bg: `${hex}18`, // 10% opacity
                border: `${hex}40`, // 25% opacity
                text: hex
            };
        },

        prevMonth() {
            if (this.month === 1) {
                this.month = 12;
                this.year--;
            } else {
                this.month--;
            }
            this.fetchMonthHolidays();
        },

        nextMonth() {
            if (this.month === 12) {
                this.month = 1;
                this.year++;
            } else {
                this.month++;
            }
            this.fetchMonthHolidays();
        },

        goToday() {
            const now = new Date();
            this.year = now.getFullYear();
            this.month = now.getMonth() + 1;
            this.fetchMonthHolidays();
        },

        setMonth(m) {
            this.month = parseInt(m);
            this.fetchMonthHolidays();
        },

        setYear(y) {
            this.year = parseInt(y);
            this.fetchMonthHolidays();
        },

        fetchMonthHolidays() {
            fetch(`/settings/holidays/json?year=${this.year}&month=${this.month}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.holidays) {
                        const existingOther = this.holidays.filter(h => {
                            const d = new Date(h.date);
                            return !(d.getFullYear() === this.year && (d.getMonth() + 1) === this.month);
                        });
                        this.holidays = [...existingOther, ...data.holidays];
                    }
                })
                .catch(err => console.error(err));
        },

        startDrag(dayType, event) {
            this.draggedType = dayType;
            event.dataTransfer.setData('text/plain', dayType.name);
            event.dataTransfer.effectAllowed = 'copy';
        },

        onDragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
        },

        async dropDayType(dateStr, event) {
            event.preventDefault();
            const dayTypeName = this.draggedType?.name || event.dataTransfer.getData('text/plain');
            if (!dayTypeName || !dateStr) return;

            const isMerc = this.draggedType?.is_mercantile !== undefined ? this.draggedType.is_mercantile : true;
            await this.saveDayType(dateStr, dayTypeName, null, isMerc);
        },

        async saveDayType(dateStr, dayTypeName, title = null, isMercantile = null) {
            try {
                let mercVal = isMercantile;
                if (mercVal === null) {
                    const dt = this.dayTypes.find(d => d.name.toLowerCase() === (dayTypeName || '').toLowerCase());
                    mercVal = dt ? (dt.is_mercantile ?? true) : true;
                }

                const response = await fetch('/settings/holidays/assign', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        date: dateStr,
                        day_type: dayTypeName,
                        title: title || dayTypeName,
                        is_mercantile: mercVal
                    })
                });
                const res = await response.json();
                if (res.success) {
                    if (res.action === 'deleted') {
                        this.holidays = this.holidays.filter(h => h.date !== dateStr);
                    } else if (res.holiday) {
                        this.holidays = this.holidays.filter(h => h.date !== dateStr);
                        this.holidays.push(res.holiday);
                    }
                    this.notify(res.message);
                }
            } catch (e) {
                console.error(e);
            }
        },

        openEdit(dateStr) {
            const existing = this.getHolidaysForDate(dateStr);
            this.editForm.date = dateStr;
            this.editForm.day_type = existing ? (existing.type || existing.category || (this.dayTypes[0]?.name || 'Additional Company holiday')) : (this.dayTypes[0]?.name || 'Additional Company holiday');
            this.editForm.title = existing ? existing.title : '';
            this.editForm.is_mercantile = existing ? (existing.is_mercantile !== false && !((existing.category || '').toLowerCase().includes('non-mercantile'))) : true;
            this.editForm.description = existing ? (existing.description || '') : '';
            this.editModal = true;
        },

        onDayTypeChangeInEditForm() {
            const dt = this.dayTypes.find(d => d.name.toLowerCase() === (this.editForm.day_type || '').toLowerCase());
            if (dt && dt.is_mercantile !== undefined) {
                this.editForm.is_mercantile = Boolean(dt.is_mercantile);
            }
        },

        async submitEditModal() {
            await this.saveDayType(this.editForm.date, this.editForm.day_type, this.editForm.title, this.editForm.is_mercantile);
            this.editModal = false;
        },

        async removeHoliday(dateStr) {
            await this.saveDayType(dateStr, 'Working Day');
            this.editModal = false;
        },

        // Day Type CRUD methods
        openAddDayTypeModal() {
            this.newDayType = { name: '', color: '#9333ea', is_mercantile: true, description: '' };
            this.addDayTypeModal = true;
        },

        async submitAddDayType() {
            if (!this.newDayType.name.trim()) {
                alert('Please enter a day type name');
                return;
            }

            try {
                const res = await fetch('/settings/day-types', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.newDayType)
                });
                const data = await res.json();
                if (data.success && data.day_type) {
                    this.dayTypes.push(data.day_type);
                    this.addDayTypeModal = false;
                    this.notify(data.message);
                } else if (data.message) {
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
            }
        },

        openEditDayTypeModal(dt) {
            this.editingDayType = { 
                id: dt.id, 
                name: dt.name, 
                color: dt.color || '#9333ea', 
                is_mercantile: dt.is_mercantile !== undefined ? Boolean(dt.is_mercantile) : true, 
                description: dt.description || '' 
            };
            this.editDayTypeModal = true;
        },

        async submitEditDayType() {
            if (!this.editingDayType.name.trim()) {
                alert('Please enter a day type name');
                return;
            }

            try {
                const res = await fetch(`/settings/day-types/${this.editingDayType.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.editingDayType)
                });
                const data = await res.json();
                if (data.success && data.day_type) {
                    const idx = this.dayTypes.findIndex(d => d.id === data.day_type.id);
                    if (idx !== -1) {
                        this.dayTypes[idx] = data.day_type;
                    }
                    this.editDayTypeModal = false;
                    this.notify(data.message);
                } else if (data.message) {
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
            }
        },

        async deleteDayType(dt) {
            if (!confirm(`Permanently delete day type "${dt.name}"?`)) return;

            try {
                const res = await fetch(`/settings/day-types/${dt.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.dayTypes = this.dayTypes.filter(d => d.id !== dt.id);
                    if (this.editDayTypeModal) this.editDayTypeModal = false;
                    this.notify(data.message);
                }
            } catch (e) {
                console.error(e);
            }
        },

        notify(msg) {
            this.toastMessage = msg;
            this.showToast = true;
            setTimeout(() => { this.showToast = false; }, 3000);
        },

        get calendarDays() {
            const firstDay = new Date(this.year, this.month - 1, 1);
            const lastDay = new Date(this.year, this.month, 0);
            const numDays = lastDay.getDate();
            const startDayIndex = firstDay.getDay(); // 0 = Sun, 1 = Mon, ... 6 = Sat

            const cells = [];

            // Previous month overflow days
            const prevMonthLastDay = new Date(this.year, this.month - 1, 0).getDate();
            for (let i = startDayIndex - 1; i >= 0; i--) {
                const dayNum = prevMonthLastDay - i;
                const prevMonthNum = this.month === 1 ? 12 : this.month - 1;
                const prevYearNum = this.month === 1 ? this.year - 1 : this.year;
                const dateStr = `${prevYearNum}-${String(prevMonthNum).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
                cells.push({
                    dayNum: dayNum,
                    dateStr: dateStr,
                    isCurrentMonth: false,
                    isSunday: (cells.length % 7) === 0
                });
            }

            // Current month days
            const today = new Date();
            const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

            for (let d = 1; d <= numDays; d++) {
                const dateStr = `${this.year}-${String(this.month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                cells.push({
                    dayNum: d,
                    dateStr: dateStr,
                    isCurrentMonth: true,
                    isToday: dateStr === todayStr,
                    isSunday: (cells.length % 7) === 0
                });
            }

            // Next month trailing days to complete full rows (multiple of 7)
            const totalSlots = Math.ceil(cells.length / 7) * 7;
            const remaining = totalSlots - cells.length;
            for (let n = 1; n <= remaining; n++) {
                const nextMonthNum = this.month === 12 ? 1 : this.month + 1;
                const nextYearNum = this.month === 12 ? this.year + 1 : this.year;
                const dateStr = `${nextYearNum}-${String(nextMonthNum).padStart(2, '0')}-${String(n).padStart(2, '0')}`;
                cells.push({
                    dayNum: n,
                    dateStr: dateStr,
                    isCurrentMonth: false,
                    isSunday: (cells.length % 7) === 0
                });
            }

            return cells;
        }
    };
}
</script>
