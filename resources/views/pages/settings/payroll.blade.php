<form action="{{ route('settings.save') }}" method="POST" class="space-y-6">
    @csrf
    <input type="hidden" name="active_module" value="payroll">

    <!-- 1. Sri Lanka Statutory Payroll Deductions (EPF & ETF) -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-100 dark:border-amber-900/60">
                    <i class="ph ph-money text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Sri Lanka Statutory Deductions (EPF & ETF)</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Employees' Provident Fund Act No. 15 of 1958 & Employees' Trust Fund Act No. 46 of 1980</p>
                </div>
            </div>
            <span class="bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-amber-200/60 dark:border-amber-900/60">Statutory Tax Engine</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
            <div class="bg-slate-50 dark:bg-[#1e2d4d] p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">EPF Employee Contribution (%)</label>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.5" name="epf_employee_rate" value="{{ $settingsRaw['epf_employee_rate'] ?? '8.0' }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2.5 text-center">
                    <span class="font-extrabold text-slate-400">%</span>
                </div>
                <span class="text-[10px] text-slate-400 font-semibold mt-1.5 block">Standard statutory employee deduction</span>
            </div>

            <div class="bg-slate-50 dark:bg-[#1e2d4d] p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">EPF Employer Contribution (%)</label>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.5" name="epf_employer_rate" value="{{ $settingsRaw['epf_employer_rate'] ?? '12.0' }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2.5 text-center">
                    <span class="font-extrabold text-slate-400">%</span>
                </div>
                <span class="text-[10px] text-slate-400 font-semibold mt-1.5 block">Employer mandatory contribution</span>
            </div>

            <div class="bg-slate-50 dark:bg-[#1e2d4d] p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">ETF Employer Contribution (%)</label>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.5" name="etf_employer_rate" value="{{ $settingsRaw['etf_employer_rate'] ?? '3.0' }}" class="w-full border-slate-200 dark:border-slate-600 rounded-lg text-xs font-black bg-white dark:bg-[#152038] text-slate-900 dark:text-white p-2.5 text-center">
                    <span class="font-extrabold text-slate-400">%</span>
                </div>
                <span class="text-[10px] text-slate-400 font-semibold mt-1.5 block">100% Employer borne contribution</span>
            </div>
        </div>
    </div>

    <!-- 2. APIT Tax & Salary Processing Cycle -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <i class="ph ph-receipt text-amber-600 dark:text-amber-400 text-lg"></i>
            <h2 class="text-sm font-black text-slate-900 dark:text-white">APIT Tax Brackets & Salary Processing</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">APIT Tax Exemption Monthly Relief (LKR)</label>
                <input type="number" name="apit_tax_threshold" value="{{ $settingsRaw['apit_tax_threshold'] ?? 100000 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                <span class="text-[10px] text-slate-400 font-semibold mt-1 block">Monthly income below threshold is exempt from APIT</span>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Monthly Payroll Cut-off Day</label>
                <input type="number" min="1" max="31" name="salary_cutoff_day" value="{{ $settingsRaw['salary_cutoff_day'] ?? 25 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                <span class="text-[10px] text-slate-400 font-semibold mt-1 block">Date of month when attendance and leaves lock for payroll calculation</span>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input type="checkbox" name="auto_deduct_nopay" value="1" {{ ($settingsRaw['auto_deduct_nopay'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                <div>
                    <span class="text-slate-900 dark:text-white font-extrabold block">Auto-deduct Unpaid / No-Pay Leaves from Salary</span>
                    <span class="text-[11px] font-normal text-slate-400">Automatically calculate per-day rate deduction for unauthorized/no-pay absences</span>
                </div>
            </label>

            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input type="checkbox" name="generate_payslip_pdf" value="1" {{ ($settingsRaw['generate_payslip_pdf'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                <div>
                    <span class="text-slate-900 dark:text-white font-extrabold block">Generate Digital PDF Payslips</span>
                    <span class="text-[11px] font-normal text-slate-400">Make downloadable PDF payslips accessible on employee self-service portal</span>
                </div>
            </label>
        </div>
    </div>

    <!-- Save Actions -->
    <div class="flex justify-end pt-2">
        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-amber-500/20 transition-all cursor-pointer">
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Save Payroll Settings</span>
        </button>
    </div>
</form>
