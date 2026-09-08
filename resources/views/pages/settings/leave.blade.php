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

        <!-- 3. Application Policies & Calendar Rules -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <i class="ph ph-sliders text-emerald-600 dark:text-emerald-400 text-lg"></i>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Leave Application & Calendar Rules</h2>
            </div>

            <div class="space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <input type="checkbox" name="auto_sync_holidays" value="1" {{ ($settingsRaw['auto_sync_holidays'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <span class="text-slate-900 dark:text-white font-extrabold block">Auto-sync Sri Lanka Gazette Holidays</span>
                        <span class="text-[11px] font-normal text-slate-400">Automatically sync Public, Bank, and Mercantile Poya holidays via the government calendar schedule</span>
                    </div>
                </label>

                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <input type="checkbox" name="allow_half_day" value="1" {{ ($settingsRaw['allow_half_day'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <span class="text-slate-900 dark:text-white font-extrabold block">Allow Half-Day Requests (0.5 Days)</span>
                        <span class="text-[11px] font-normal text-slate-400">Enables staff to apply for Morning (9 AM–1 PM) or Afternoon (1 PM–5 PM) half-day slots</span>
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
