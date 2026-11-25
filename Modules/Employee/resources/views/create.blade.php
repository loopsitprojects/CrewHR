@extends('layouts.app', ['title' => 'Loops HR - Add New Employee Profile', 'breadcrumb' => 'Add Employee'])

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
    $showCompensation = $showCompensation ?? is_compensation_enabled();
@endphp

@section('content')
<div class="w-full space-y-6 pb-12" x-data="{
    basic: {{ old('basic_salary', 150000) }},
    incrementsBasic: {{ old('increments_basic', 0) }},
    budgetAllowance: {{ old('budget_allowance', 0) }},
    other: {{ old('other_allowance', 0) }},
    apit: {{ old('apit_tax', 0) }},
    allowanceTypes: @js($allowanceTypes),
    allowances: @js($initialAllowances),
    educationList: @js(old('higher_education', [['qualification' => '', 'institute' => '', 'country' => '', 'duration' => '']])),
    experienceList: @js(old('working_experience', [['company' => '', 'designation' => '', 'duration' => '']])),

    addEducation() {
        this.educationList.push({ qualification: '', institute: '', country: '', duration: '' });
    },
    removeEducation(index) {
        if (this.educationList.length > 1) {
            this.educationList.splice(index, 1);
        } else {
            this.educationList[0] = { qualification: '', institute: '', country: '', duration: '' };
        }
    },

    addExperience() {
        this.experienceList.push({ company: '', designation: '', duration: '' });
    },
    removeExperience(index) {
        if (this.experienceList.length > 1) {
            this.experienceList.splice(index, 1);
        } else {
            this.experienceList[0] = { company: '', designation: '', duration: '' };
        }
    },
    
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

    <!-- Top Action Bar -->
    <div class="sticky top-0 z-30 bg-white/85 dark:bg-[#0F172A]/85 backdrop-blur-xl rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-4 shadow-sm dark:shadow-[0_4px_20px_rgba(0,0,0,0.3)] flex items-center justify-between transition-colors">
        <a href="{{ route('employee.index') }}" onclick="handleModuleBack(event, '{{ route('employee.index') }}')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 border border-slate-200/70 dark:border-white/10 text-slate-700 dark:text-slate-200 flex items-center gap-2 text-xs font-semibold shadow-2xs transition-all active:scale-95 cursor-pointer">
            <i class="ph ph-arrow-left text-base"></i>
            <span>Back to Employees</span>
        </a>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('employee.index') }}" onclick="handleModuleBack(event, '{{ route('employee.index') }}')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl border border-slate-200/70 dark:border-white/10 transition-all active:scale-95 cursor-pointer">Cancel</a>
            <button form="createEmployeeForm" type="submit" class="bg-[#0071E3] hover:bg-[#0077ED] active:scale-95 text-white px-5 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm shadow-blue-500/25 transition-all cursor-pointer">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Create Profile & Save</span>
            </button>
        </div>
    </div>

    <!-- Create Form -->
    <form id="createEmployeeForm" action="{{ route('employee.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- SECTION 1 -->
        <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-6 relative overflow-hidden transition-colors">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-200/60 dark:bg-blue-500/15 dark:text-blue-400 dark:border-blue-500/30 font-black text-xs flex items-center justify-center">01</span>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">PROFILE PICTURE & PERSONAL INFORMATION</h2>
                </div>
            </div>

            <!-- Avatar Uploader -->
            <div class="bg-slate-50/80 dark:bg-slate-800/40 p-4 rounded-2xl border border-slate-200/60 dark:border-white/[0.06] transition-colors">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-2">Profile Picture (File or Avatar URL)</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input type="file" name="profile_picture_file" accept="image/*" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 p-2 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:ring-blue-400/30">
                    <input type="text" name="profile_picture" value="{{ old('profile_picture') }}" placeholder="or image URL: https://..." class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:ring-blue-400/30">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="e.g. John Doe" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400" required>
                    @error('full_name') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Gender</label>
                    <select name="gender" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">NIC / Passport *</label>
                    <input type="text" name="nic_passport" value="{{ old('nic_passport') }}" placeholder="e.g. 199512345678" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Personal Email</label>
                    <input type="email" name="personal_email" value="{{ old('personal_email') }}" placeholder="e.g. personal@gmail.com" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                    @error('personal_email') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="+94 77 123 4567" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" placeholder="e.g. Jane Doe (Spouse)" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" placeholder="e.g. +94 77 999 8888" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>
            </div>
        </div>

        <!-- SECTION 2 -->
        <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-6 transition-colors">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200/60 dark:bg-indigo-500/15 dark:text-indigo-400 dark:border-indigo-500/30 font-black text-xs flex items-center justify-center">02</span>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">EMPLOYMENT ROLE, SYSTEM PERMISSIONS & IMPORTANT DATES</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs font-semibold">
                <!-- Employee ID and Company Email moved here from Section 1 -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Employee ID *</label>
                    <input type="text" name="employee_id_number" value="{{ old('employee_id_number') }}" placeholder="e.g. EMP-0105" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400" required>
                    @error('employee_id_number') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Company Email *</label>
                    <input type="email" name="company_email" value="{{ old('company_email') }}" placeholder="e.g. john@loopshr.lk" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                    @error('company_email') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div x-data="{ desigMode: '{{ old('designation_id') === 'custom' || old('custom_designation') ? 'custom' : 'select' }}' }">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-slate-700 dark:text-slate-300 font-bold">Current Designation *</label>
                        <button type="button" class="text-[11px] text-blue-600 dark:text-blue-400 font-bold hover:underline cursor-pointer" @click="desigMode = (desigMode === 'custom' ? 'select' : 'custom')">
                            <span x-show="desigMode === 'select'">+ Enter Manually</span>
                            <span x-show="desigMode === 'custom'" x-cloak>← Select Existing</span>
                        </button>
                    </div>

                    <div x-show="desigMode === 'select'">
                        <select name="designation_id" x-bind:disabled="desigMode === 'custom'" @change="if ($event.target.value === 'custom') desigMode = 'custom'" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                            @foreach($designations as $desig)
                                <option value="{{ $desig->id }}" {{ old('designation_id') == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
                            @endforeach
                            <option value="custom">+ Type New Designation Manually...</option>
                        </select>
                    </div>

                    <div x-show="desigMode === 'custom'" x-cloak>
                        <input type="hidden" name="designation_id" value="custom" x-bind:disabled="desigMode !== 'custom'">
                        <input type="text" name="custom_designation" value="{{ old('custom_designation') }}" placeholder="Type custom designation title (e.g. Senior QA Lead)..." class="w-full border-blue-300 dark:border-blue-500/50 bg-blue-50/70 dark:bg-blue-950/40 rounded-xl text-xs font-bold p-2.5 text-blue-900 dark:text-blue-200 focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Department *</label>
                    <select name="department_id" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Joined Date *</label>
                    <input type="date" name="joined_date" value="{{ old('joined_date', date('Y-m-d')) }}" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">System Role *</label>
                    <select name="system_role" class="w-full border-purple-200 dark:border-purple-500/30 rounded-xl text-xs font-bold bg-purple-50/70 dark:bg-purple-950/40 text-purple-900 dark:text-purple-300 p-2.5 focus:ring-2 focus:ring-purple-500/20">
                        <option value="Employee" selected>Employee</option>
                        <option value="Manager (Team Approvals)">Manager (Team Approvals)</option>
                        <option value="HR Lead">HR Lead</option>
                        <option value="Super (Admin)">Super (Admin)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Job Category *</label>
                    <select name="job_category" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        <option value="Full Time" selected>Full Time</option>
                        <option value="Probation">Probation</option>
                        <option value="Contract">Contract</option>
                        <option value="Intern">Intern</option>
                        <option value="Freelance">Freelance</option>
                    </select>
                </div>

                <!-- Reporting Person with Designation display -->
                <div x-data="{
                    reportingPersons: @js($reportingPersons->map(fn($rp) => ['id' => $rp->id, 'name' => $rp->user->name ?? 'Reporting Person', 'designation' => $rp->designation->name ?? 'Staff'])),
                    selectedRpId: '{{ old('reporting_person_id') }}',
                    get selectedRpDesignation() {
                        const found = this.reportingPersons.find(p => p.id == this.selectedRpId);
                        return found ? found.designation : '';
                    }
                }">
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5 flex items-center justify-between">
                        <span>Reporting Person (Manager / Supervisor)</span>
                        <span class="text-[10px] text-slate-400 font-normal">Optional</span>
                    </label>
                    <select name="reporting_person_id" x-model="selectedRpId" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        <option value="">None (Reports Directly to HOD)</option>
                        @foreach($reportingPersons as $rp)
                            <option value="{{ $rp->id }}" {{ old('reporting_person_id') == $rp->id ? 'selected' : '' }}>
                                {{ $rp->user->name ?? 'Reporting Person' }} — {{ $rp->designation->name ?? 'Staff' }}
                            </option>
                        @endforeach
                    </select>
                    <div x-show="selectedRpDesignation" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-950/40 px-2.5 py-1 rounded-lg border border-blue-100 dark:border-blue-900/50">
                        <i class="ph ph-identification-badge text-xs"></i>
                        <span>Reporting Person's Designation: <strong x-text="selectedRpDesignation"></strong></span>
                    </div>
                </div>

                <!-- Head of Department (HOD) -->
                <div x-data="{
                    hods: @js($reportingPersons->map(fn($rp) => ['id' => $rp->id, 'name' => $rp->user->name ?? 'HOD', 'designation' => $rp->designation->name ?? 'Staff', 'department' => $rp->department->name ?? ''])),
                    selectedHodId: '{{ old('hod_id') }}',
                    get selectedHodDetails() {
                        const found = this.hods.find(p => p.id == this.selectedHodId);
                        return found ? (found.designation + (found.department ? ' · ' + found.department : '')) : '';
                    }
                }">
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5 flex items-center justify-between">
                        <span>Head of Department (HOD) <span class="text-rose-500 font-black">*</span></span>
                        <span class="text-[10px] text-blue-600 dark:text-blue-400 font-normal">Department Head</span>
                    </label>
                    <select name="hod_id" x-model="selectedHodId" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        <option value="">Select Department HOD</option>
                        @foreach($reportingPersons as $rp)
                            <option value="{{ $rp->id }}" {{ old('hod_id') == $rp->id ? 'selected' : '' }}>
                                {{ $rp->user->name ?? 'HOD' }} — {{ $rp->designation->name ?? 'Staff' }} ({{ $rp->department->name ?? 'General' }})
                            </option>
                        @endforeach
                    </select>
                    <div x-show="selectedHodDetails" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50/80 dark:bg-indigo-950/40 px-2.5 py-1 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                        <i class="ph ph-shield-check text-xs"></i>
                        <span>HOD: <strong x-text="selectedHodDetails"></strong></span>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">EPF Registration No.</label>
                    <input type="text" name="epf_registration_no" value="{{ old('epf_registration_no') }}" placeholder="e.g. EPF/2026/0105" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">ETF Registration No.</label>
                    <input type="text" name="etf_registration_no" value="{{ old('etf_registration_no') }}" placeholder="e.g. ETF/2026/0105" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Cost Center Classification</label>
                    <select name="cost_classification" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        <option value="Direct" selected>Direct (Cost of Sales / Production)</option>
                        <option value="Indirect">Indirect (Administrative / Corporate)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Payment Method</label>
                    <select name="payment_method" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        <option value="Bank Transfer" selected>Bank Transfer</option>
                        <option value="Cash">Cash</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
            </div>
        </div>

        @if($showCompensation)
        <!-- SECTION 3 -->
        <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-6 transition-colors">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200/60 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30 font-black text-xs flex items-center justify-center">03</span>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">COMPENSATION & STATUTORY EARNINGS ARCHITECTURE</h2>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-400">Base Pay (Qualifying for EPF) & Fixed Allowances Breakdown</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="bg-indigo-50 text-indigo-800 border border-indigo-200/80 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30 px-3 py-1 rounded-full text-xs font-extrabold">
                        EPF BASE: RS. <span x-text="formatNumber(totalBase)">150,000</span>
                    </span>
                    <span class="bg-emerald-50 text-emerald-800 border border-emerald-200/80 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30 px-3 py-1 rounded-full text-xs font-extrabold">
                        GROSS: RS. <span x-text="formatNumber(gross)">175,000</span>
                    </span>
                </div>
            </div>

            <!-- Base Pay Section (EPF Liable Base) -->
            <div class="space-y-2">
                <span class="text-xs font-black uppercase tracking-wider text-blue-700 dark:text-blue-400 flex items-center gap-1.5">
                    <i class="ph ph-coins"></i> 1. Base Pay Architecture (EPF Liable Base)
                </span>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Basic Salary (Rs.) *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                            <input type="number" step="any" min="0" name="basic_salary" x-model="basic" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Increments Basic (Rs.)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                            <input type="number" step="any" min="0" name="increments_basic" x-model="incrementsBasic" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Budget Allowance (Rs.)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                            <input type="number" step="any" min="0" name="budget_allowance" x-model="budgetAllowance" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fixed Allowances Section (Dynamic Allowance Types) -->
            <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-white/[0.06]">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-400 flex items-center gap-1.5">
                            <i class="ph ph-hand-coins"></i> 2. Fixed Allowances Architecture
                        </span>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-400">Add or remove customized allowance components assigned to this employee</p>
                    </div>
                    <button type="button" @click="addAllowance()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 dark:bg-indigo-500/15 dark:hover:bg-indigo-500/25 dark:text-indigo-300 dark:border-indigo-500/30 text-xs font-black rounded-xl transition-colors shadow-2xs cursor-pointer active:scale-95">
                        <i class="ph ph-plus-circle text-base"></i> Add Allowance
                    </button>
                </div>

                <div class="space-y-2.5">
                    <template x-for="(item, index) in allowances" :key="index">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 bg-slate-50/80 dark:bg-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/70 p-3 rounded-xl border border-slate-200/80 dark:border-white/[0.06] transition-all">
                            <div class="flex-1">
                                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Allowance Type *</label>
                                <select :name="'allowances[' + index + '][allowance_type_id]'" x-model.number="item.allowance_type_id" @change="onAllowanceTypeChange(index)" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                    <template x-for="type in allowanceTypes" :key="type.id">
                                        <option :value="type.id" :selected="type.id == item.allowance_type_id" x-text="type.name + (type.is_epf_liable ? ' (EPF Liable)' : ' (Non-EPF)')"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="w-full sm:w-48">
                                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Amount (Rs.) *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rs.</span>
                                    <input type="number" step="any" min="0" :name="'allowances[' + index + '][amount]'" x-model.number="item.amount" placeholder="0.00" class="w-full pl-9 pr-3 py-2.5 border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-black bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-white">
                                </div>
                            </div>

                            <div class="sm:w-32 flex flex-col justify-end pt-1 sm:pt-0">
                                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Statutory Base</label>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-extrabold border"
                                      :class="isAllowanceEpfLiable(item.allowance_type_id) ? 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800/60' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-white/10'">
                                    <i class="ph" :class="isAllowanceEpfLiable(item.allowance_type_id) ? 'ph-check-circle' : 'ph-minus-circle'"></i>
                                    <span x-text="isAllowanceEpfLiable(item.allowance_type_id) ? 'EPF Liable' : 'Non-EPF'"></span>
                                </span>
                            </div>

                            <div class="flex items-end justify-end sm:pt-4">
                                <button type="button" @click="removeAllowance(index)" class="w-9 h-9 rounded-xl bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/50 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center justify-center transition-colors shadow-2xs cursor-pointer active:scale-95" title="Remove allowance">
                                    <i class="ph ph-trash text-base"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div x-show="allowances.length === 0" class="p-4 bg-slate-50/70 dark:bg-slate-800/30 border border-dashed border-slate-300 dark:border-white/10 rounded-xl text-center">
                        <i class="ph ph-hand-coins text-2xl text-slate-400 dark:text-slate-500 block mb-1"></i>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">No fixed allowances configured for this employee.</p>
                        <button type="button" @click="addAllowance()" class="mt-2 inline-flex items-center gap-1 text-xs font-extrabold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer">
                            + Click here to add an allowance
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-xl p-3 text-xs font-bold text-indigo-900 dark:text-indigo-200">
                    <span class="flex items-center gap-1.5">
                        <i class="ph ph-calculator text-indigo-600 dark:text-indigo-400 text-sm"></i>
                        Total Fixed Allowances:
                    </span>
                    <span class="font-black text-sm text-indigo-700 dark:text-indigo-300">Rs. <span x-text="formatNumber(totalFixed)"></span></span>
                </div>
            </div>

            <!-- Statutory Deductions HERO BANNER -->
            <div class="bg-blue-50/40 dark:bg-blue-950/20 border border-blue-200/80 dark:border-blue-900/40 p-5 rounded-2xl space-y-4">
                <h4 class="text-xs font-extrabold text-blue-900 dark:text-blue-300 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="ph ph-receipt text-blue-600 dark:text-blue-400 text-base"></i> Sri Lanka Statutory Payroll Deductions & Take-Home Calculation
                </h4>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs font-bold">
                    <div class="bg-white dark:bg-slate-900/90 p-3.5 rounded-xl border border-blue-100 dark:border-white/[0.08] shadow-sm">
                        <span class="text-[10px] text-slate-400 block uppercase font-extrabold">EPF (Employee 8%)</span>
                        <span class="text-rose-600 dark:text-rose-400 font-black text-sm">- Rs. <span x-text="formatNumber(epfEmployee)">12,000</span></span>
                    </div>

                    <div class="bg-white dark:bg-slate-900/90 p-3.5 rounded-xl border border-blue-100 dark:border-white/[0.08] shadow-sm">
                        <span class="text-[10px] text-slate-400 block uppercase font-extrabold">EPF Employer (12%)</span>
                        <span class="text-blue-600 dark:text-blue-400 font-black text-sm">Rs. <span x-text="formatNumber(epfEmployer)">18,000</span></span>
                    </div>

                    <div class="bg-white dark:bg-slate-900/90 p-3.5 rounded-xl border border-blue-100 dark:border-white/[0.08] shadow-sm">
                        <span class="text-[10px] text-slate-400 block uppercase font-extrabold">ETF Employer (3%)</span>
                        <span class="text-blue-600 dark:text-blue-400 font-black text-sm">Rs. <span x-text="formatNumber(etfEmployer)">4,500</span></span>
                    </div>

                    <div class="bg-white dark:bg-slate-900/90 p-3.5 rounded-xl border border-blue-100 dark:border-white/[0.08] shadow-sm">
                        <label class="text-[10px] text-slate-400 block uppercase font-extrabold mb-1">PAYE / APIT Tax (Rs.)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                            <input type="number" step="any" min="0" name="apit_tax" x-model="apit" placeholder="0.00" class="w-full h-9 pl-9 pr-3 text-right border border-slate-200 dark:border-white/10 rounded-lg text-xs font-black bg-slate-50 dark:bg-slate-800 text-rose-600 dark:text-rose-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500">
                        </div>
                    </div>
                </div>

                <!-- Estimated Net Take-Home Hero Pill -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-600/90 dark:to-teal-700/90 text-white p-5 rounded-2xl shadow-lg shadow-emerald-500/20 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-extrabold tracking-wide uppercase text-emerald-100 block">Estimated Net Take-Home</span>
                        <span class="text-xs text-white/80">Calculated after statutory Employee EPF (8%) & APIT tax deductions</span>
                    </div>
                    <span class="text-2xl font-black tracking-tight">Rs. <span x-text="formatNumber(netTakeHome)">173,000</span> <span class="text-sm font-semibold">/ month</span></span>
                </div>
            </div>
        </div>
        @endif

        <!-- SECTION 4 -->
        <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-6 transition-colors">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-200/60 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 font-black text-xs flex items-center justify-center">{{ $showCompensation ? '04' : '03' }}</span>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">FINANCIAL & BANK ACCOUNT DETAILS</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Name</label>
                    <input type="text" name="account_holder_name" value="{{ old('account_holder_name') }}" placeholder="e.g. J. Doe" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Account Number</label>
                    <input type="text" name="account_number" value="{{ old('account_number') }}" placeholder="e.g. 1000293847" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Bank</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. Commercial Bank" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Branch</label>
                    <input type="text" name="bank_branch" value="{{ old('bank_branch') }}" placeholder="e.g. Colombo Fort" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Branch Code</label>
                    <input type="text" name="branch_code" value="{{ old('branch_code') }}" placeholder="e.g. 035" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 p-2.5 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400">
                </div>
            </div>
        </div>

        <!-- SECTION 5: HIGHER EDUCATION & WORKING EXPERIENCE -->
        <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] p-6 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] space-y-6 transition-colors">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 border border-violet-200/60 dark:bg-violet-500/15 dark:text-violet-400 dark:border-violet-500/30 font-black text-xs flex items-center justify-center">05</span>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">HIGHER EDUCATION & WORKING EXPERIENCE</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs font-semibold">
                <!-- Higher Education -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-slate-800 dark:text-slate-200 font-extrabold flex items-center gap-1.5 text-xs">
                            <i class="ph ph-graduation-cap text-blue-600 dark:text-blue-400 text-base"></i> Higher Education
                        </label>
                        <button type="button" @click="addEducation()" class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 dark:text-blue-400 text-[11px] font-bold rounded-lg transition-colors cursor-pointer active:scale-95">
                            <i class="ph ph-plus-circle"></i> Add Qualification
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(edu, eIdx) in educationList" :key="'edu-' + eIdx">
                            <div class="p-3.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-white/[0.06] space-y-3 relative group">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500" x-text="'Qualification #' + (eIdx + 1)"></span>
                                    <button type="button" @click="removeEducation(eIdx)" x-show="educationList.length > 1" class="text-rose-500 hover:text-rose-700 dark:text-rose-400 text-xs font-bold flex items-center gap-0.5 cursor-pointer">
                                        <i class="ph ph-trash"></i> Remove
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Qualification *</label>
                                        <input type="text" :name="'higher_education[' + eIdx + '][qualification]'" x-model="edu.qualification" placeholder="e.g. B.Sc. in Computer Science" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Institute *</label>
                                        <input type="text" :name="'higher_education[' + eIdx + '][institute]'" x-model="edu.institute" placeholder="e.g. University of Colombo" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Country</label>
                                        <input type="text" :name="'higher_education[' + eIdx + '][country]'" x-model="edu.country" placeholder="e.g. Sri Lanka" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Duration</label>
                                        <input type="text" :name="'higher_education[' + eIdx + '][duration]'" x-model="edu.duration" placeholder="e.g. 2018 - 2022 (4 Years)" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Working Experience -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-slate-800 dark:text-slate-200 font-extrabold flex items-center gap-1.5 text-xs">
                            <i class="ph ph-briefcase text-purple-600 dark:text-purple-400 text-base"></i> Working Experience
                        </label>
                        <button type="button" @click="addExperience()" class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-600 dark:bg-purple-500/15 dark:hover:bg-purple-500/25 dark:text-purple-400 text-[11px] font-bold rounded-lg transition-colors cursor-pointer active:scale-95">
                            <i class="ph ph-plus-circle"></i> Add Experience
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(exp, xIdx) in experienceList" :key="'exp-' + xIdx">
                            <div class="p-3.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-white/[0.06] space-y-3 relative group">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500" x-text="'Experience #' + (xIdx + 1)"></span>
                                    <button type="button" @click="removeExperience(xIdx)" x-show="experienceList.length > 1" class="text-rose-500 hover:text-rose-700 dark:text-rose-400 text-xs font-bold flex items-center gap-0.5 cursor-pointer">
                                        <i class="ph ph-trash"></i> Remove
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Company *</label>
                                        <input type="text" :name="'working_experience[' + xIdx + '][company]'" x-model="exp.company" placeholder="e.g. ABC Technologies" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Designation *</label>
                                        <input type="text" :name="'working_experience[' + xIdx + '][designation]'" x-model="exp.designation" placeholder="e.g. Senior Software Engineer" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Duration</label>
                                        <input type="text" :name="'working_experience[' + xIdx + '][duration]'" x-model="exp.duration" placeholder="e.g. 2021 - 2024 (3 Years)" class="w-full border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('employee.index') }}" onclick="handleModuleBack(event, '{{ route('employee.index') }}')" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl border border-slate-200/70 dark:border-white/10 transition-all active:scale-95 cursor-pointer">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-[#0071E3] hover:bg-[#0077ED] text-white text-xs font-semibold rounded-xl shadow-sm shadow-blue-500/25 transition-all flex items-center gap-2 active:scale-95 cursor-pointer">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Create Profile & Save</span>
            </button>
        </div>
    </form>

</div>
@endsection
