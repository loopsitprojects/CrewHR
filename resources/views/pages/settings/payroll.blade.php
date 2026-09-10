<div class="space-y-6" x-data="{ addAllowanceModal: false, editAllowanceModal: false, activeAllowance: null }">
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
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-amber-500/20 transition-all cursor-pointer">
                    <i class="ph ph-floppy-disk text-base"></i>
                    <span>Save Statutory Rates & Tax Settings</span>
                </button>
            </div>
        </div>
    </form>

    <!-- 3. Dynamic Company Allowance Types Registry -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-900/60">
                    <i class="ph ph-hand-coins text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Customizable Allowance Types Registry</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Add, configure, or remove allowance types available across employee profiles and payroll runs</p>
                </div>
            </div>

            <button type="button" @click="addAllowanceModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/20 transition-all cursor-pointer self-start sm:self-auto">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Add Allowance Type</span>
            </button>
        </div>

        <div class="overflow-x-auto border border-slate-200/80 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 dark:bg-[#1e2d4d] text-slate-600 dark:text-slate-300 font-extrabold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="p-3">Allowance Name</th>
                        <th class="p-3">Code</th>
                        <th class="p-3">Classification</th>
                        <th class="p-3 text-right">Default Amount (LKR)</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-semibold text-slate-700 dark:text-slate-300">
                    @forelse($allowanceTypes as $type)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="p-3 font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="ph ph-coin text-blue-600 text-base"></i>
                                <span>{{ $type->name }}</span>
                            </td>
                            <td class="p-3 font-mono text-slate-500 font-bold">{{ $type->code ?? 'N/A' }}</td>
                            <td class="p-3">
                                @if($type->is_epf_liable)
                                    <span class="bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-indigo-200 dark:border-indigo-800">
                                        EPF Liable Base
                                    </span>
                                @else
                                    <span class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                                        Fixed Allowance (Non-EPF)
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ number_format($type->default_amount, 2) }}
                            </td>
                            <td class="p-3 text-center">
                                <form action="{{ route('settings.allowance_type.toggle', $type->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold cursor-pointer {{ $type->status === 'Active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                        {{ $type->status }}
                                    </button>
                                </form>
                            </td>
                            <td class="p-3 text-right space-x-1.5">
                                <button type="button" @click="activeAllowance = {{ json_encode($type) }}; editAllowanceModal = true" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer" title="Edit">
                                    <i class="ph ph-pencil-simple text-base"></i>
                                </button>

                                <form action="{{ route('settings.allowance_type.destroy', $type->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this allowance type?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer" title="Delete">
                                        <i class="ph ph-trash text-base"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400 italic">
                                No custom allowance types configured yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: Add Allowance Type -->
    <div x-show="addAllowanceModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
        <div class="bg-white dark:bg-[#152038] w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6 relative" @click.outside="addAllowanceModal = false">
            <button @click="addAllowanceModal = false" class="absolute top-5 right-5 p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                <i class="ph ph-x text-lg"></i>
            </button>

            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="ph ph-plus-circle text-xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Add New Allowance Type</h3>
            </div>

            <form action="{{ route('settings.allowance_type.store') }}" method="POST" class="space-y-3.5 text-xs font-bold text-slate-600 dark:text-slate-300">
                @csrf
                <div>
                    <label class="block mb-1 text-slate-700 dark:text-slate-200">Allowance Title *</label>
                    <input type="text" name="name" required placeholder="e.g. Fuel Allowance, Attendance Allowance" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 text-xs font-bold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1 text-slate-700 dark:text-slate-200">Short Code</label>
                        <input type="text" name="code" placeholder="e.g. FUEL, ATTN" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 text-xs font-bold">
                    </div>
                    <div>
                        <label class="block mb-1 text-slate-700 dark:text-slate-200">Default Amount (LKR)</label>
                        <input type="number" step="100" name="default_amount" placeholder="0" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 text-xs font-bold">
                    </div>
                </div>

                <div class="pt-2 space-y-2">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_epf_liable" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-slate-900 dark:text-white font-extrabold block">EPF Liable (Qualifies for EPF/ETF)</span>
                            <span class="text-[10px] font-normal text-slate-400">If checked, this allowance will be included in the Total for EPF computation base</span>
                        </div>
                    </label>
                </div>

                <div class="pt-3 flex justify-end gap-2">
                    <button type="button" @click="addAllowanceModal = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl shadow-md shadow-blue-500/20">Save Allowance Type</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Edit Allowance Type -->
    <div x-show="editAllowanceModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
        <div class="bg-white dark:bg-[#152038] w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6 relative" @click.outside="editAllowanceModal = false">
            <button @click="editAllowanceModal = false" class="absolute top-5 right-5 p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                <i class="ph ph-x text-lg"></i>
            </button>

            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="ph ph-pencil-simple text-xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Edit Allowance Type</h3>
            </div>

            <form :action="'/settings/allowance-type/' + activeAllowance?.id" method="POST" class="space-y-3.5 text-xs font-bold text-slate-600 dark:text-slate-300" x-if="activeAllowance">
                @csrf
                @method('PUT')
                <div>
                    <label class="block mb-1 text-slate-700 dark:text-slate-200">Allowance Title *</label>
                    <input type="text" name="name" :value="activeAllowance?.name" required class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 text-xs font-bold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1 text-slate-700 dark:text-slate-200">Short Code</label>
                        <input type="text" name="code" :value="activeAllowance?.code" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 text-xs font-bold">
                    </div>
                    <div>
                        <label class="block mb-1 text-slate-700 dark:text-slate-200">Default Amount (LKR)</label>
                        <input type="number" step="100" name="default_amount" :value="activeAllowance?.default_amount" class="w-full border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 text-xs font-bold">
                    </div>
                </div>

                <div class="pt-2 space-y-2">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_epf_liable" value="1" :checked="activeAllowance?.is_epf_liable" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-slate-900 dark:text-white font-extrabold block">EPF Liable (Qualifies for EPF/ETF)</span>
                            <span class="text-[10px] font-normal text-slate-400">If checked, this allowance will be included in the Total for EPF computation base</span>
                        </div>
                    </label>
                </div>

                <div class="pt-3 flex justify-end gap-2">
                    <button type="button" @click="editAllowanceModal = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl shadow-md shadow-blue-500/20">Update Allowance Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
