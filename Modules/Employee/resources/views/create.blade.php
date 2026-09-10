@extends('layouts.app', ['title' => 'LOOPS HR - Add New Employee Profile'])

@section('breadcrumbs')
    <span class="text-slate-300">/</span>
    <a href="{{ route('employee.index') }}" class="text-slate-500 hover:text-blue-600 transition-colors">Employees Directory</a>
    <span class="text-slate-300">/</span>
    <span class="text-blue-600 font-black">Add New Employee</span>
@endsection

@php
    $initialAllowances = [];
    if (old('allowances')) {
        $initialAllowances = old('allowances');
    } else {
        $travel = $allowanceTypes->firstWhere('code', 'TRAVEL') ?? $allowanceTypes->firstWhere('name', 'Travelling Allowance');
        if ($travel) $initialAllowances[] = ['allowance_type_id' => $travel->id, 'amount' => (float)($travel->default_amount ?: 15000)];

        $cola = $allowanceTypes->firstWhere('code', 'COLA') ?? $allowanceTypes->firstWhere('name', 'Cost of Living Allowance');
        if ($cola) $initialAllowances[] = ['allowance_type_id' => $cola->id, 'amount' => (float)($cola->default_amount ?: 10000)];

        if (empty($initialAllowances) && $allowanceTypes->isNotEmpty()) {
            $first = $allowanceTypes->first();
            $initialAllowances[] = ['allowance_type_id' => $first->id, 'amount' => (float)$first->default_amount];
        }
    }
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{
    basic: {{ old('basic_salary', 150000) }},
    incrementsBasic: {{ old('increments_basic', 0) }},
    budgetAllowance: {{ old('budget_allowance', 0) }},
    other: {{ old('other_allowance', 0) }},
    apit: {{ old('apit_tax', 0) }},
    allowanceTypes: @js($allowanceTypes),
    allowances: @js($initialAllowances),
    
    addAllowance() {
        if (this.allowanceTypes.length > 0) {
            this.allowances.push({
                allowance_type_id: this.allowanceTypes[0].id,
                amount: this.allowanceTypes[0].default_amount || 0
            });
        }
    },
    removeAllowance(index) {
        this.allowances.splice(index, 1);
    },
    onAllowanceTypeChange(index) {
        const typeId = this.allowances[index].allowance_type_id;
        const selected = this.allowanceTypes.find(t => t.id == typeId);
        if (selected && (!this.allowances[index].amount || this.allowances[index].amount == 0)) {
            this.allowances[index].amount = selected.default_amount || 0;
        }
    },
    isAllowanceEpfLiable(typeId) {
        const selected = this.allowanceTypes.find(t => t.id == typeId);
        return selected ? !!selected.is_epf_liable : false;
    },
    get totalBase() {
        return (parseFloat(this.basic) || 0) + (parseFloat(this.incrementsBasic) || 0) + (parseFloat(this.budgetAllowance) || 0);
    },
    get epfLiableAllowances() {
        return this.allowances.reduce((sum, item) => {
            if (this.isAllowanceEpfLiable(item.allowance_type_id)) {
                return sum + (parseFloat(item.amount) || 0);
            }
            return sum;
        }, 0);
    },
    get totalFixed() {
        return this.allowances.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
    },
    get totalForEpf() {
        return this.totalBase + this.epfLiableAllowances;
    },
    get gross() {
        return this.totalBase + this.totalFixed + (parseFloat(this.other) || 0);
    },
    get epfEmployee() {
        return Math.round(this.totalForEpf * 0.08);
    },
    get epfEmployer() {
        return Math.round(this.totalForEpf * 0.12);
    },
    get etfEmployer() {
        return Math.round(this.totalForEpf * 0.03);
    },
    get netTakeHome() {
        return Math.max(0, this.gross - this.epfEmployee - (parseFloat(this.apit) || 0));
    },
    formatNumber(num) {
        return new Intl.NumberFormat('en-LK', { maximumFractionDigits: 0 }).format(num || 0);
    }
}">

    <!-- Top Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('employee.index') }}" class="w-9 h-9 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center shadow-sm">
                <i class="ph ph-arrow-left text-lg"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <i class="ph ph-user-plus text-xl text-blue-600"></i>
                    <h1 class="text-xl font-black text-gray-900">Add New Employee Profile</h1>
                </div>
                <p class="text-xs font-bold text-gray-500 mt-0.5">Create a new employee profile, EPF registration, system role, and salary details.</p>
            </div>
        </div>

        <button form="createEmployeeForm" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-blue-500/20 transition-all">
            <i class="ph ph-plus-circle text-base"></i>
            <span>Create Profile & Save</span>
        </button>
    </div>

    <!-- Create Form -->
    <form id="createEmployeeForm" action="{{ route('employee.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- SECTION 1 -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-5">
            <h2 class="text-xs font-black text-blue-600 uppercase tracking-wider border-b border-gray-100 pb-3">1. Profile Picture & Personal Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-gray-700 font-bold mb-1">Title *</label>
                    <select name="title" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                        <option value="Mr." {{ old('title') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                        <option value="Ms." {{ old('title') == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                        <option value="Mrs." {{ old('title') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                        <option value="Dr." {{ old('title') == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="e.g. John Doe" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5" required>
                    @error('full_name') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">NIC / Passport *</label>
                    <input type="text" name="nic_passport" value="{{ old('nic_passport') }}" placeholder="e.g. 199512345678" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="john@loopshr.lk" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5" required>
                    @error('email') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="+94 77 123 4567" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Emergency Contact</label>
                    <input type="text" name="emergency_contact" value="{{ old('emergency_contact') }}" placeholder="e.g. Jane Doe (Wife) - +94 77 999 8888" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-bold mb-1">Profile Picture (File or Avatar URL)</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="file" name="profile_picture_file" accept="image/*" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2">
                        <input type="text" name="profile_picture" value="{{ old('profile_picture') }}" placeholder="or image URL: https://..." class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-5">
            <h2 class="text-xs font-black text-blue-600 uppercase tracking-wider border-b border-gray-100 pb-3">2. Employment Role, System Permissions & Important Dates</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-gray-700 font-bold mb-1">Employee ID *</label>
                    <input type="text" name="employee_id_number" value="{{ old('employee_id_number') }}" placeholder="e.g. EMP-0105" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5" required>
                    @error('employee_id_number') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Department *</label>
                    <select name="department_id" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">System Role *</label>
                    <select name="system_role" class="w-full border-purple-200 rounded-xl text-xs font-bold bg-purple-50 text-purple-900 p-2.5">
                        <option value="Employee" selected>Employee</option>
                        <option value="Manager (Team Approvals)">Manager (Team Approvals)</option>
                        <option value="HR Lead">HR Lead</option>
                        <option value="Super (Admin)">Super (Admin)</option>
                    </select>
                </div>

                <div x-data="{ desigMode: '{{ old('designation_id') === 'custom' || old('custom_designation') ? 'custom' : 'select' }}' }">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-gray-700 font-bold">Current Designation *</label>
                        <button type="button" class="text-[11px] text-blue-600 font-bold hover:underline cursor-pointer" @click="desigMode = (desigMode === 'custom' ? 'select' : 'custom')">
                            <span x-show="desigMode === 'select'">+ Enter Manually</span>
                            <span x-show="desigMode === 'custom'" x-cloak>← Select Existing</span>
                        </button>
                    </div>

                    <div x-show="desigMode === 'select'">
                        <select name="designation_id" x-bind:disabled="desigMode === 'custom'" @change="if ($event.target.value === 'custom') desigMode = 'custom'" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                            @foreach($designations as $desig)
                                <option value="{{ $desig->id }}" {{ old('designation_id') == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
                            @endforeach
                            <option value="custom">+ Type New Designation Manually...</option>
                        </select>
                    </div>

                    <div x-show="desigMode === 'custom'" x-cloak>
                        <input type="hidden" name="designation_id" value="custom" x-bind:disabled="desigMode !== 'custom'">
                        <input type="text" name="custom_designation" value="{{ old('custom_designation') }}" placeholder="Type custom designation title (e.g. Senior QA Lead)..." class="w-full border-blue-300 bg-blue-50/70 rounded-xl text-xs font-bold p-2.5 text-blue-900 focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Joined Date *</label>
                    <input type="date" name="joined_date" value="{{ old('joined_date', date('Y-m-d')) }}" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Reporting Person</label>
                    <select name="reporting_person_id" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                        <option value="">None (Top Manager)</option>
                        @foreach($reportingPersons as $rp)
                            <option value="{{ $rp->id }}" {{ old('reporting_person_id') == $rp->id ? 'selected' : '' }}>{{ $rp->user->name ?? 'Reporting Person' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">EPF Registration No.</label>
                    <input type="text" name="epf_registration_no" value="{{ old('epf_registration_no') }}" placeholder="e.g. EPF/2026/0105" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Staff Category (Payroll) *</label>
                    <select name="staff_category" class="w-full border-blue-200 rounded-xl text-xs font-bold bg-blue-50/40 p-2.5">
                        <option value="Executive" selected>Executive</option>
                        <option value="Non-Executive">Non-Executive</option>
                        <option value="Shift Staff">Shift Staff</option>
                        <option value="Management">Management</option>
                        <option value="Contract">Contract</option>
                        <option value="Intern">Intern</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Cost Center Classification</label>
                    <select name="cost_classification" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                        <option value="Direct" selected>Direct (Cost of Sales / Production)</option>
                        <option value="Indirect">Indirect (Administrative / Corporate)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                        <option value="Bank Transfer" selected>Bank Transfer</option>
                        <option value="Cash">Cash</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-bold mb-1">Job Category *</label>
                    <select name="job_category" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                        <option value="Full Time (Permanent)" selected>Full Time (Permanent)</option>
                        <option value="Probation">Probation</option>
                        <option value="Contract">Contract</option>
                        <option value="Intern">Intern</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION 3 -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h2 class="text-xs font-black text-blue-600 uppercase tracking-wider">3. Compensation & Statutory Earnings Architecture</h2>
                    <p class="text-[11px] font-semibold text-gray-400">Base Pay (Qualifying for EPF) & Fixed Allowances Breakdown</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="bg-indigo-50 text-indigo-800 border border-indigo-200 px-3 py-1 rounded-full text-xs font-extrabold">
                        EPF BASE: RS. <span x-text="formatNumber(totalBase)">150,000</span>
                    </span>
                    <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1 rounded-full text-xs font-extrabold">
                        GROSS: RS. <span x-text="formatNumber(gross)">175,000</span>
                    </span>
                </div>
            </div>

            <!-- Base Pay Section (EPF Liable Base) -->
            <div class="space-y-2">
                <span class="text-xs font-black uppercase tracking-wider text-blue-700 flex items-center gap-1.5">
                    <i class="ph ph-coins"></i> 1. Base Pay Architecture (EPF Liable Base)
                </span>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Basic Salary (Rs.) *</label>
                        <input type="number" name="basic_salary" x-model="basic" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Increments Basic (Rs.)</label>
                        <input type="number" name="increments_basic" x-model="incrementsBasic" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Budget Allowance (Rs.)</label>
                        <input type="number" name="budget_allowance" x-model="budgetAllowance" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                    </div>
                </div>
            </div>

            <!-- Fixed Allowances Section (Dynamic Allowance Types) -->
            <div class="space-y-3 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-indigo-700 flex items-center gap-1.5">
                            <i class="ph ph-hand-coins"></i> 2. Fixed Allowances Architecture
                        </span>
                        <p class="text-[11px] font-semibold text-gray-400">Add or remove customized allowance components assigned to this employee</p>
                    </div>
                    <button type="button" @click="addAllowance()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-black rounded-xl border border-indigo-200/80 transition-colors shadow-sm cursor-pointer">
                        <i class="ph ph-plus-circle text-base"></i> Add Allowance
                    </button>
                </div>

                <div class="space-y-2.5">
                    <template x-for="(item, index) in allowances" :key="index">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 bg-gray-50/80 hover:bg-gray-50 p-3 rounded-xl border border-gray-200 transition-all">
                            <div class="flex-1">
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">Allowance Type *</label>
                                <select :name="'allowances[' + index + '][allowance_type_id]'" x-model.number="item.allowance_type_id" @change="onAllowanceTypeChange(index)" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-white p-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                    <template x-for="type in allowanceTypes" :key="type.id">
                                        <option :value="type.id" :selected="type.id == item.allowance_type_id" x-text="type.name + (type.is_epf_liable ? ' (EPF Liable)' : ' (Non-EPF)')"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="w-full sm:w-48">
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">Amount (Rs.) *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-xs font-bold text-gray-400">Rs.</span>
                                    <input type="number" step="any" min="0" :name="'allowances[' + index + '][amount]'" x-model.number="item.amount" placeholder="0.00" class="w-full pl-9 pr-3 py-2.5 border-gray-200 rounded-xl text-xs font-black bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-gray-800">
                                </div>
                            </div>

                            <div class="sm:w-32 flex flex-col justify-end pt-1 sm:pt-0">
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">Statutory Base</label>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-extrabold border"
                                      :class="isAllowanceEpfLiable(item.allowance_type_id) ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-gray-100 text-gray-600 border-gray-200'">
                                    <i class="ph" :class="isAllowanceEpfLiable(item.allowance_type_id) ? 'ph-check-circle' : 'ph-minus-circle'"></i>
                                    <span x-text="isAllowanceEpfLiable(item.allowance_type_id) ? 'EPF Liable' : 'Non-EPF'"></span>
                                </span>
                            </div>

                            <div class="flex items-end justify-end sm:pt-4">
                                <button type="button" @click="removeAllowance(index)" class="w-9 h-9 rounded-xl bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Remove allowance">
                                    <i class="ph ph-trash text-base"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div x-show="allowances.length === 0" class="p-4 bg-gray-50 border border-dashed border-gray-300 rounded-xl text-center">
                        <i class="ph ph-hand-coins text-2xl text-gray-400 block mb-1"></i>
                        <p class="text-xs font-bold text-gray-500">No fixed allowances configured for this employee.</p>
                        <button type="button" @click="addAllowance()" class="mt-2 inline-flex items-center gap-1 text-xs font-extrabold text-indigo-600 hover:underline cursor-pointer">
                            + Click here to add an allowance
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-indigo-50/50 border border-indigo-100 rounded-xl p-3 text-xs font-bold text-indigo-900">
                    <span class="flex items-center gap-1.5">
                        <i class="ph ph-calculator text-indigo-600 text-sm"></i>
                        Total Fixed Allowances:
                    </span>
                    <span class="font-black text-sm text-indigo-700">Rs. <span x-text="formatNumber(totalFixed)"></span></span>
                </div>
            </div>

            <!-- Statutory Deductions -->
            <div class="bg-blue-50/40 border border-blue-200/80 p-4 rounded-xl space-y-4">
                <h4 class="text-xs font-extrabold text-blue-900 uppercase tracking-wider">Sri Lanka Statutory Payroll Deductions & Take-Home Calculation</h4>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs font-bold">
                    <div class="bg-white p-3 rounded-xl border border-blue-100">
                        <span class="text-[10px] text-gray-400 block">EPF (Employee 8%)</span>
                        <span class="text-rose-600 font-extrabold text-sm">- Rs. <span x-text="formatNumber(epfEmployee)">12,000</span></span>
                    </div>

                    <div class="bg-white p-3 rounded-xl border border-blue-100">
                        <span class="text-[10px] text-gray-400 block">EPF Employer (12%)</span>
                        <span class="text-blue-600 font-extrabold text-sm">Rs. <span x-text="formatNumber(epfEmployer)">18,000</span></span>
                    </div>

                    <div class="bg-white p-3 rounded-xl border border-blue-100">
                        <span class="text-[10px] text-gray-400 block">ETF Employer (3%)</span>
                        <span class="text-blue-600 font-extrabold text-sm">Rs. <span x-text="formatNumber(etfEmployer)">4,500</span></span>
                    </div>

                    <div class="bg-white p-3 rounded-xl border border-blue-100">
                        <label class="text-[10px] text-gray-400 block mb-1">PAYE / APIT Tax (Rs.)</label>
                        <input type="number" name="apit_tax" x-model="apit" class="w-full border-gray-200 rounded-lg text-xs font-bold p-1 bg-gray-50 text-rose-600">
                    </div>
                </div>

                <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-emerald-200 shadow-sm">
                    <span class="text-xs font-extrabold text-gray-700">Estimated Net Take-Home:</span>
                    <span class="text-lg font-black text-emerald-600">Rs. <span x-text="formatNumber(netTakeHome)">173,000</span> / month</span>
                </div>
            </div>
        </div>

        <!-- SECTION 4 -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-xs font-black text-blue-600 uppercase tracking-wider border-b border-gray-100 pb-3">4. Financial & Bank Account Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-gray-700 font-bold mb-1">Bank Name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. Commercial Bank" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Bank Branch</label>
                    <input type="text" name="bank_branch" value="{{ old('bank_branch') }}" placeholder="e.g. Colombo Fort" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Account Number</label>
                    <input type="text" name="account_number" value="{{ old('account_number') }}" placeholder="e.g. 1000293847" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Account Holder Name</label>
                    <input type="text" name="account_holder_name" value="{{ old('account_holder_name') }}" placeholder="e.g. J. Doe" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>
            </div>
        </div>

        <!-- SECTION 5: QUALIFICATIONS -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-xs font-black text-blue-600 uppercase tracking-wider border-b border-gray-100 pb-3">5. Higher Education & Professional Qualifications</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-gray-700 font-bold mb-1">Higher Education</label>
                    <textarea name="higher_education" rows="3" placeholder="e.g. B.Sc. in Computer Science" class="w-full border-gray-200 rounded-xl text-xs font-medium bg-gray-50 p-2.5">{{ old('higher_education') }}</textarea>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-1">Professional Qualifications</label>
                    <textarea name="professional_qualifications" rows="3" placeholder="e.g. AWS Solutions Architect, PMP" class="w-full border-gray-200 rounded-xl text-xs font-medium bg-gray-50 p-2.5">{{ old('professional_qualifications') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('employee.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 flex items-center gap-2">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Create Profile & Save</span>
            </button>
        </div>
    </form>

</div>
@endsection
