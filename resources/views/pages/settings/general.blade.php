<form action="{{ route('settings.save') }}" method="POST" class="space-y-6">
    @csrf
    <input type="hidden" name="active_module" value="general">

    <!-- 1. Organization Information -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-900/60">
                    <i class="ph ph-buildings text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Organization Profile & Brand Identity</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Configure your company name, location, and fiscal standards</p>
                </div>
            </div>
            <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-blue-200/60 dark:border-blue-900/60">Core Configuration</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Company / Organization Name *</label>
                <input type="text" name="company_name" value="{{ $settingsRaw['company_name'] ?? 'Loops HR Portal (Sri Lanka)' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20" required>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Official Timezone</label>
                <select name="timezone" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                    <option value="Asia/Colombo (GMT+5:30)" selected>Asia/Colombo (GMT+5:30 - Sri Lanka Standard Time)</option>
                    <option value="UTC (GMT+0:00)">UTC (GMT+0:00)</option>
                    <option value="Asia/Dubai (GMT+4:00)">Asia/Dubai (GMT+4:00 - UAE Standard Time)</option>
                    <option value="Asia/Singapore (GMT+8:00)">Asia/Singapore (GMT+8:00 - SGT)</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Base Currency (Statutory & Payroll)</label>
                <input type="text" name="base_currency" value="{{ $settingsRaw['base_currency'] ?? 'LKR (Rs.)' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Fiscal Year Start Month</label>
                <select name="fiscal_year_start" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                    <option value="January" {{ ($settingsRaw['fiscal_year_start'] ?? 'January') == 'January' ? 'selected' : '' }}>January</option>
                    <option value="April" {{ ($settingsRaw['fiscal_year_start'] ?? '') == 'April' ? 'selected' : '' }}>April (Sri Lanka Financial Year standard)</option>
                    <option value="July" {{ ($settingsRaw['fiscal_year_start'] ?? '') == 'July' ? 'selected' : '' }}>July</option>
                    <option value="October" {{ ($settingsRaw['fiscal_year_start'] ?? '') == 'October' ? 'selected' : '' }}>October</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Headquarters / Primary Address</label>
                <input type="text" name="company_address" value="{{ $settingsRaw['company_address'] ?? 'No. 45, Alfred House Gardens, Colombo 03, Sri Lanka' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Official HR Contact Email</label>
                <input type="email" name="contact_email" value="{{ $settingsRaw['contact_email'] ?? 'hr@loops.lk' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>
        </div>
    </div>

    <!-- 2. Active Custom Company Holidays -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="ph ph-calendar-plus text-blue-600 dark:text-blue-400 text-lg"></i>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Active Custom Company Holidays ({{ count($customHolidays) }})</h2>
            </div>
            <a href="{{ route('leave.index') }}" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline flex items-center gap-1">
                <span>Manage on Leave Calendar</span>
                <i class="ph ph-arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            @forelse($customHolidays as $ch)
                <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-3 bg-slate-50/50 dark:bg-[#1e2d4d]/50 flex items-center justify-between">
                    <div>
                        <div class="font-extrabold text-slate-900 dark:text-white">{{ $ch->title }}</div>
                        <div class="text-[11px] text-slate-400 font-semibold">{{ $ch->date }} • {{ $ch->category ?? 'Company Holiday' }}</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        {{ $ch->category ?? 'Paid' }}
                    </span>
                </div>
            @empty
                <div class="col-span-3 text-center py-6 text-slate-400 text-xs italic bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
                    No custom company holidays registered yet. Add them from the Leave Calendar dashboard.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Save Actions -->
    <div class="flex justify-end pt-2">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Save General Settings</span>
        </button>
    </div>
</form>
