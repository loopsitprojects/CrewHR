<form action="{{ route('settings.save') }}" method="POST" class="space-y-6">
    @csrf
    <input type="hidden" name="active_module" value="performance">

    <!-- 1. Performance Appraisal Engine -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-orange-50 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 flex items-center justify-center border border-orange-100 dark:border-orange-900/60">
                    <i class="ph ph-award text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Performance Appraisals & Evaluation Cycles</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Configure employee review periods, rating scoring scales & increment eligibility</p>
                </div>
            </div>
            <span class="bg-orange-50 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-orange-200/60 dark:border-orange-900/60">KPI Engine</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Appraisal Review Frequency</label>
                <select name="appraisal_frequency" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                    <option value="Annual" {{ ($settingsRaw['appraisal_frequency'] ?? 'Annual') == 'Annual' ? 'selected' : '' }}>Annual Review (Year-End)</option>
                    <option value="Bi-annual" {{ ($settingsRaw['appraisal_frequency'] ?? '') == 'Bi-annual' ? 'selected' : '' }}>Bi-annual (Every 6 Months)</option>
                    <option value="Quarterly" {{ ($settingsRaw['appraisal_frequency'] ?? '') == 'Quarterly' ? 'selected' : '' }}>Quarterly Reviews</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Rating Scoring Scale Maximum</label>
                <select name="rating_scale_max" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                    <option value="5" {{ ($settingsRaw['rating_scale_max'] ?? '5') == '5' ? 'selected' : '' }}>5-Point Rating Scale (1 to 5 Stars)</option>
                    <option value="10" {{ ($settingsRaw['rating_scale_max'] ?? '') == '10' ? 'selected' : '' }}>10-Point Score Scale</option>
                    <option value="100" {{ ($settingsRaw['rating_scale_max'] ?? '') == '100' ? 'selected' : '' }}>100% Percentage Scale</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Min Rating for Salary Increment Eligibility</label>
                <input type="number" step="0.1" name="min_rating_for_increment" value="{{ $settingsRaw['min_rating_for_increment'] ?? '3.5' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input type="checkbox" name="self_appraisal_enabled" value="1" {{ ($settingsRaw['self_appraisal_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                <div>
                    <span class="text-slate-900 dark:text-white font-extrabold block">Enable Self-Appraisal Step</span>
                    <span class="text-[11px] font-normal text-slate-400">Require staff members to submit a self-assessment evaluation before manager review</span>
                </div>
            </label>

            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input type="checkbox" name="peer_reviews_enabled" value="1" {{ ($settingsRaw['peer_reviews_enabled'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                <div>
                    <span class="text-slate-900 dark:text-white font-extrabold block">Enable 360° Peer Reviews</span>
                    <span class="text-[11px] font-normal text-slate-400">Allow team peers and subordinates to submit anonymous evaluation feedback</span>
                </div>
            </label>
        </div>
    </div>

    <!-- Save Actions -->
    <div class="flex justify-end pt-2">
        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-orange-500/20 transition-all cursor-pointer">
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Save Performance Settings</span>
        </button>
    </div>
</form>
