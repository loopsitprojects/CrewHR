<form action="{{ route('settings.save') }}" method="POST" class="space-y-6">
    @csrf
    <input type="hidden" name="active_module" value="attendance">

    <!-- 1. Attendance & Shift Policies -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center border border-cyan-100 dark:border-cyan-900/60">
                    <i class="ph ph-clock-countdown text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Attendance Shifts & Working Hours</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Define office shift timings, clock-in grace windows, and overtime parameters</p>
                </div>
            </div>
            <span class="bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-cyan-200/60 dark:border-cyan-900/60">Shift Engine</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Standard Work Day (Hours)</label>
                <input type="number" step="0.5" name="standard_work_hours" value="{{ $settingsRaw['standard_work_hours'] ?? 8 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Official Shift Start Time</label>
                <input type="time" name="shift_start_time" value="{{ $settingsRaw['shift_start_time'] ?? '09:00' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Official Shift End Time</label>
                <input type="time" name="shift_end_time" value="{{ $settingsRaw['shift_end_time'] ?? '17:30' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Clock-In Grace Period (Minutes)</label>
                <input type="number" name="grace_period_minutes" value="{{ $settingsRaw['grace_period_minutes'] ?? 15 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                <span class="text-[10px] text-slate-400 font-semibold mt-1 block">Arrivals within grace window are marked On-Time</span>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Overtime Rate Multiplier</label>
                <input type="number" step="0.1" name="overtime_rate_multiplier" value="{{ $settingsRaw['overtime_rate_multiplier'] ?? '1.5' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                <span class="text-[10px] text-slate-400 font-semibold mt-1 block">e.g. 1.5x of hourly rate</span>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input type="checkbox" name="allow_remote_clock_in" value="1" {{ ($settingsRaw['allow_remote_clock_in'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                <div>
                    <span class="text-slate-900 dark:text-white font-extrabold block">Allow Remote / Work-From-Home Clock-In</span>
                    <span class="text-[11px] font-normal text-slate-400">Permit employees to check-in outside physical office IP subnets</span>
                </div>
            </label>

            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input type="checkbox" name="auto_overtime_calculation" value="1" {{ ($settingsRaw['auto_overtime_calculation'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                <div>
                    <span class="text-slate-900 dark:text-white font-extrabold block">Automatic Overtime Calculation</span>
                    <span class="text-[11px] font-normal text-slate-400">Automatically compute overtime hours for approved extra shifts</span>
                </div>
            </label>
        </div>
    </div>

    <!-- Save Actions -->
    <div class="flex justify-end pt-2">
        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-cyan-500/20 transition-all cursor-pointer">
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Save Attendance Settings</span>
        </button>
    </div>
</form>
