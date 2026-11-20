@extends('layouts.app', ['title' => 'Loops HR - Edit Employee Profile', 'breadcrumb' => 'Edit Employee'])

@section('breadcrumbs')
    <span class="text-slate-300">/</span>
    <a href="{{ route('employee.index') }}" class="text-slate-500 hover:text-blue-600 transition-colors">Employees Directory</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('employee.show', $employee->id) }}" class="text-slate-500 hover:text-blue-600 transition-colors">{{ $employee->user->name ?? 'Profile' }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-blue-600 font-black">Edit Profile</span>
@endsection

@section('header_left')
    <div class="h-5 w-px bg-slate-200 dark:bg-white/10 hidden sm:block shrink-0"></div>
    <div class="flex items-center gap-2.5 min-w-0">
        <a href="{{ route('employee.index') }}" class="p-1.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg transition-colors shrink-0" title="Back to Directory">
            <i class="ph ph-arrow-left text-base"></i>
        </a>
        <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-none shrink-0">
            Edit Employee
        </h1>
        <span class="text-slate-300 dark:text-slate-600 hidden sm:inline">&bull;</span>
        <span class="text-sm text-slate-500 dark:text-slate-400 font-medium truncate hidden sm:inline">
            {{ $employee->user->name ?? 'Employee' }} &bull; {{ $employee->employee_id_number ?? $employee->employee_id }} &bull; {{ $employee->designation->name ?? 'Staff' }}
        </span>
    </div>
@endsection

@section('header_actions')
    <a href="{{ route('employee.index') }}" class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 rounded-lg transition-colors">
        Cancel
    </a>
    <button form="editEmployeeForm" type="submit" class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-[#0071E3] hover:bg-[#0077ED] active:scale-95 text-white rounded-lg text-xs font-semibold shadow-sm shadow-blue-500/25 transition-all cursor-pointer">
        <i class="ph ph-floppy-disk text-sm"></i>
        <span>Update Profile & Save</span>
    </button>
@endsection

@php
    $allowanceTypes = $allowanceTypes ?? (class_exists(\Modules\Payroll\Models\AllowanceType::class) ? \Modules\Payroll\Models\AllowanceType::where('status', 'Active')->orderBy('name')->get() : collect());
    $departments = $departments ?? collect();
    $designations = $designations ?? collect();
    $reportingPersons = $reportingPersons ?? collect();

    $initialAllowances = [];
    if (isset($employee) && $employee->relationLoaded('employeeAllowances') && $employee->employeeAllowances->isNotEmpty()) {
        foreach ($employee->employeeAllowances as $ea) {
            $initialAllowances[] = [
                'allowance_type_id' => $ea->allowance_type_id,
                'amount' => (float) $ea->amount,
            ];
        }
    } elseif (isset($employee) && $employee->id) {
        $loadedEa = $employee->employeeAllowances()->get();
        if ($loadedEa->isNotEmpty()) {
            foreach ($loadedEa as $ea) {
                $initialAllowances[] = [
                    'allowance_type_id' => $ea->allowance_type_id,
                    'amount' => (float) $ea->amount,
                ];
            }
        } else {
            if (($employee->travelling_allowance ?? 0) > 0) {
                $t = $allowanceTypes->firstWhere('code', 'TRAVEL') ?? $allowanceTypes->firstWhere('name', 'Travelling Allowance');
                if ($t) $initialAllowances[] = ['allowance_type_id' => $t->id, 'amount' => (float)$employee->travelling_allowance];
            }
            if (($employee->cost_of_living_allowance ?? 0) > 0) {
                $t = $allowanceTypes->firstWhere('code', 'COLA') ?? $allowanceTypes->firstWhere('name', 'Cost of Living Allowance');
                if ($t) $initialAllowances[] = ['allowance_type_id' => $t->id, 'amount' => (float)$employee->cost_of_living_allowance];
            }
            if (($employee->increments_allowance ?? 0) > 0) {
                $t = $allowanceTypes->firstWhere('code', 'INC_ALLOW') ?? $allowanceTypes->firstWhere('name', 'Increments Allowance');
                if ($t) $initialAllowances[] = ['allowance_type_id' => $t->id, 'amount' => (float)$employee->increments_allowance];
            }
            if (($employee->fixed_allowance ?? 0) > 0) {
                $t = $allowanceTypes->firstWhere('code', 'OTHER_FIXED') ?? $allowanceTypes->firstWhere('name', 'Other Fixed Allowance');
                if ($t) $initialAllowances[] = ['allowance_type_id' => $t->id, 'amount' => (float)$employee->fixed_allowance];
            }
        }
    }

    $initialEducation = old('higher_education');
    if (!$initialEducation) {
        $initialEducation = !empty($employee->higher_education_list) ? $employee->higher_education_list : [['qualification' => '', 'institute' => '', 'country' => '', 'duration' => '']];
    }

    $initialExperience = old('working_experience');
    if (!$initialExperience) {
        $initialExperience = !empty($employee->working_experience_list) ? $employee->working_experience_list : [['company' => '', 'designation' => '', 'duration' => '']];
    }

    $ecRaw = $employee->emergency_contact ?? '';
    $ecName = '';
    $ecPhone = '';
    if (str_contains($ecRaw, ' - ')) {
        $parts = explode(' - ', $ecRaw, 2);
        $ecName = $parts[0] ?? '';
        $ecPhone = $parts[1] ?? '';
    } elseif (str_contains($ecRaw, ',')) {
        $parts = explode(',', $ecRaw, 2);
        $ecName = trim($parts[0] ?? '');
        $ecPhone = trim($parts[1] ?? '');
    } else {
        $ecName = $ecRaw;
    }
    $currentRole = old('role', $employee->user?->roles?->first()?->name ?? ($employee->system_role ?? 'Employee'));
    $showCompensation = $showCompensation ?? is_compensation_enabled();
@endphp

@section('content')
<div class="max-w-5xl mx-auto mt-6 pb-12 w-full" x-data="{
    activeTab: '{{ ($showCompensation || request('tab') !== 'compensation') ? request('tab', 'personal') : 'personal' }}',
    showCompensation: {{ $showCompensation ? 'true' : 'false' }},
    avatarPreview: '{{ $employee->profile_picture ? asset($employee->profile_picture) : ('https://ui-avatars.com/api/?name=' . urlencode($employee->user->name ?? 'Employee') . '&background=0D8ABC&color=fff') }}',
    showUrlInput: false,
    handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) {
            this.avatarPreview = URL.createObjectURL(file);
        }
    },

    ecName: @js(old('emergency_contact_name', $ecName)),
    ecPhone: @js(old('emergency_contact_phone', $ecPhone)),
    get combinedEmergencyContact() {
        if (this.ecName && this.ecPhone) return `${this.ecName.trim()} - ${this.ecPhone.trim()}`;
        return (this.ecName || this.ecPhone || '').trim();
    },

    basic: {{ old('basic_salary', $employee->basic_salary ?? 0) }},
    incrementsBasic: {{ old('increments_basic', $employee->increments_basic ?? 0) }},
    budgetAllowance: {{ old('budget_allowance', $employee->budget_allowance ?? 0) }},
    other: {{ old('other_allowance', $employee->other_allowance ?? 0) }},
    apit: {{ old('apit_tax', $employee->apit_tax ?? 0) }},
    allowanceTypes: @js($allowanceTypes),
    allowances: @js(old('allowances', $initialAllowances)),

    educationList: @js($initialEducation),
    experienceList: @js($initialExperience),

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
    isAllowanceEpfLiable(typeId) {
        if (!typeId) return false;
        const selected = this.allowanceTypes.find(t => t.id == typeId);
        return selected ? !!selected.is_epf_liable : false;
    },
    getAllowanceName(typeId) {
        if (!typeId) return 'Select';
        const selected = this.allowanceTypes.find(t => t.id == typeId);
        return selected ? selected.name : 'Unknown';
    },

    get totalBase() {
        return (parseFloat(this.basic) || 0) + (parseFloat(this.incrementsBasic) || 0) + (parseFloat(this.budgetAllowance) || 0);
    },
    get epfLiableAllowances() {
        if (!this.allowances || !Array.isArray(this.allowances)) return 0;
        return this.allowances.reduce((sum, item) => {
            if (this.isAllowanceEpfLiable(item.allowance_type_id)) {
                return sum + (parseFloat(item.amount) || 0);
            }
            return sum;
        }, 0);
    },
    get totalFixed() {
        if (!this.allowances || !Array.isArray(this.allowances)) return 0;
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
        const val = parseFloat(num);
        if (isNaN(val) || val === null || val === undefined) return '0.00';
        return new Intl.NumberFormat('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
    }
}">

    <!-- Main Unified Form Card with Attached Tabs -->
    <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/80 dark:border-white/[0.08] shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] transition-colors overflow-hidden">
        
        <!-- Attached Tab Navigation Bar (Underline active indicators) -->
        <div class="border-b border-slate-200 dark:border-white/[0.08] px-6 pt-4 bg-white dark:bg-[#111827]">
            <nav class="flex items-center space-x-6 sm:space-x-8 overflow-x-auto scrollbar-none -mb-px" aria-label="Tabs">
                <button type="button" @click="activeTab = 'personal'"
                        :class="activeTab === 'personal' ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 font-bold' : 'border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 font-medium'"
                        class="pb-3.5 px-1 text-xs sm:text-sm whitespace-nowrap transition-all cursor-pointer flex items-center gap-2">
                    <span class="text-[11px] font-black opacity-60">01</span>
                    <span>Personal & Contact</span>
                </button>

                <button type="button" @click="activeTab = 'employment'"
                        :class="activeTab === 'employment' ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 font-bold' : 'border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 font-medium'"
                        class="pb-3.5 px-1 text-xs sm:text-sm whitespace-nowrap transition-all cursor-pointer flex items-center gap-2">
                    <span class="text-[11px] font-black opacity-60">02</span>
                    <span>Role & Employment</span>
                </button>

                @if($showCompensation)
                <button type="button" @click="activeTab = 'compensation'"
                        :class="activeTab === 'compensation' ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 font-bold' : 'border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 font-medium'"
                        class="pb-3.5 px-1 text-xs sm:text-sm whitespace-nowrap transition-all cursor-pointer flex items-center gap-2">
                    <span class="text-[11px] font-black opacity-60">03</span>
                    <span>Compensation & Payroll</span>
                </button>
                @endif

                <button type="button" @click="activeTab = 'bank_qualifications'"
                        :class="activeTab === 'bank_qualifications' ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 font-bold' : 'border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 font-medium'"
                        class="pb-3.5 px-1 text-xs sm:text-sm whitespace-nowrap transition-all cursor-pointer flex items-center gap-2">
                    <span class="text-[11px] font-black opacity-60">{{ $showCompensation ? '04' : '03' }}</span>
                    <span>Bank & Qualifications</span>
                </button>
            </nav>
        </div>

        <!-- Form Element -->
        <form id="editEmployeeForm" action="{{ route('employee.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Form Body Area -->
            <div class="p-6 sm:p-8">

                <!-- TAB 01: PERSONAL & CONTACT -->
                <div x-show="activeTab === 'personal'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-200/60 dark:bg-blue-500/15 dark:text-blue-400 dark:border-blue-500/30 font-black text-xs flex items-center justify-center">01</span>
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">PROFILE PICTURE & PERSONAL INFORMATION</h2>
                                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">Core employee identity, profile photo, and official contact credentials</p>
                            </div>
                        </div>
                    </div>

                    <!-- Fix 1: Clean Avatar Dropzone Component -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-4.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-2xl border border-slate-200/70 dark:border-white/[0.06] transition-colors">
                        <div class="relative shrink-0">
                            <img :src="avatarPreview" class="w-[72px] h-[72px] rounded-full object-cover ring-2 ring-blue-500/30 shadow-md bg-white dark:bg-slate-900" alt="Avatar Preview">
                        </div>

                        <div class="flex-1 w-full space-y-1.5">
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 shadow-2xs transition-all active:scale-95">
                                    <i class="ph ph-upload-simple text-blue-600 dark:text-blue-400 text-base"></i>
                                    <span>Upload Photo</span>
                                    <input type="file" name="profile_picture_file" accept="image/png,image/jpeg,image/webp,image/jpg" @change="handleFileSelect($event)" class="hidden">
                                </label>
                                <button type="button" @click="showUrlInput = !showUrlInput" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 cursor-pointer">
                                    <i class="ph ph-link"></i>
                                    <span x-text="showUrlInput ? 'Hide URL' : 'Paste Image URL'"></span>
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">JPG, PNG or WebP. Max 2MB.</p>

                            <div x-show="showUrlInput" x-cloak class="pt-2">
                                <input type="text" name="profile_picture" value="{{ old('profile_picture', $employee->profile_picture) }}" @input="if ($event.target.value.startsWith('http')) avatarPreview = $event.target.value" placeholder="Paste image URL: https://example.com/photo.jpg" class="w-full max-w-md h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Fix 2: Strict 3-Column Grid Alignment -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs font-semibold">
                        <!-- Row 1 -->
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Employee ID <span class="text-rose-500">*</span></label>
                            <input type="text" name="employee_id_number" value="{{ old('employee_id_number', $employee->employee_id_number ?? $employee->employee_id) }}" required class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="full_name" value="{{ old('full_name', $employee->user->name ?? '') }}" required class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Gender <span class="text-rose-500">*</span></label>
                            <select name="gender" required class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender', $employee->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender', $employee->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender', $employee->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <!-- Row 2 -->
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Date of Birth</label>
                            <input type="date" name="dob" value="{{ old('dob', $employee->dob) }}" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">NIC / Passport <span class="text-rose-500">*</span></label>
                            <input type="text" name="nic_passport" value="{{ old('nic_passport', $employee->nic_passport) }}" required class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">System Username <span class="text-rose-500">*</span></label>
                            <input type="text" name="username" value="{{ old('username', $employee->user->username ?? '') }}" required class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <!-- Row 3 -->
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Personal Email</label>
                            <input type="email" name="personal_email" value="{{ old('personal_email', $employee->personal_email) }}" placeholder="e.g. personal@gmail.com" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Company Official Email <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $employee->user->email ?? $employee->company_email) }}" required placeholder="e.g. employee@company.com" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Phone Number</label>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $employee->phone_number) }}" placeholder="07X XXXXXXX" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>
                    </div>

                    <!-- Fix 2: Structured Emergency Contact Sub-Fields -->
                    <div class="p-4 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/70 dark:border-white/[0.06] space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-slate-700 dark:text-slate-300 font-extrabold text-xs">
                                Emergency Contact Person
                            </label>
                            <span class="text-[11px] text-slate-400">Next of kin or primary emergency contact</span>
                        </div>
                        <input type="hidden" name="emergency_contact" :value="combinedEmergencyContact">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Contact Name & Relation</label>
                                <input type="text" name="emergency_contact_name" x-model="ecName" placeholder="e.g. Jane Doe (Spouse)" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Emergency Phone Number</label>
                                <input type="text" name="emergency_contact_phone" x-model="ecPhone" placeholder="e.g. 077 1234567" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Tab Navigation Footer -->
                    <div class="flex items-center justify-end pt-4 border-t border-slate-100 dark:border-white/[0.06]">
                        <button type="button" @click="activeTab = 'employment'; window.scrollTo({top: 0, behavior: 'smooth'})" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                            <span>Continue to Role & Employment</span>
                            <i class="ph ph-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- TAB 02: ROLE & EMPLOYMENT -->
                <div x-show="activeTab === 'employment'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200/60 dark:bg-indigo-500/15 dark:text-indigo-400 dark:border-indigo-500/30 font-black text-xs flex items-center justify-center">02</span>
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">EMPLOYMENT ROLE, SYSTEM PERMISSIONS & IMPORTANT DATES</h2>
                                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">Designation, organizational department, access roles, and reporting structure</p>
                            </div>
                        </div>
                    </div>

                    <!-- Core Employment Fields in Strict 3-Column Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs font-semibold">
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Department <span class="text-rose-500">*</span></label>
                            <select name="department_id" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Joined Date <span class="text-rose-500">*</span></label>
                            <input type="date" name="joined_date" value="{{ old('joined_date', $employee->joined_date) }}" required class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">System Role <span class="text-rose-500">*</span></label>
                            <select name="role" required class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                <option value="Employee" {{ $currentRole == 'Employee' ? 'selected' : '' }}>Employee</option>
                                <option value="Manager" {{ $currentRole == 'Manager' ? 'selected' : '' }}>Manager</option>
                                <option value="HR Manager" {{ $currentRole == 'HR Manager' ? 'selected' : '' }}>HR Manager</option>
                                <option value="HR Staff" {{ $currentRole == 'HR Staff' ? 'selected' : '' }}>HR Staff</option>
                                <option value="Accountant" {{ $currentRole == 'Accountant' ? 'selected' : '' }}>Accountant</option>
                                <option value="Admin" {{ $currentRole == 'Admin' ? 'selected' : '' }}>Admin</option>
                                <option value="Super (Admin)" {{ $currentRole == 'Super (Admin)' ? 'selected' : '' }}>Super (Admin)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Job Category <span class="text-rose-500">*</span></label>
                            <select name="job_category" required class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                <option value="Full Time" {{ in_array(old('job_category', $employee->job_category), ['Full Time']) ? 'selected' : '' }}>Full Time</option>
                                <option value="Part Time" {{ in_array(old('job_category', $employee->job_category), ['Part Time']) ? 'selected' : '' }}>Part Time</option>
                                <option value="Probation" {{ in_array(old('job_category', $employee->job_category), ['Probation', 'Probationary']) ? 'selected' : '' }}>Probation</option>
                                <option value="Contract" {{ in_array(old('job_category', $employee->job_category), ['Contract', 'Contractor']) ? 'selected' : '' }}>Contract</option>
                                <option value="Intern" {{ in_array(old('job_category', $employee->job_category), ['Intern', 'Internship']) ? 'selected' : '' }}>Intern</option>
                                <option value="Freelance" {{ in_array(old('job_category', $employee->job_category), ['Freelance']) ? 'selected' : '' }}>Freelance</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5 flex items-center justify-between">
                                <span>Reporting Person (Manager / Supervisor)</span>
                                <span class="text-[10px] text-slate-400 font-normal">Optional</span>
                            </label>
                            <select name="reporting_person_id" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                <option value="">None (Reports Directly to HOD)</option>
                                @foreach($reportingPersons as $rep)
                                    <option value="{{ $rep->id }}" {{ old('reporting_person_id', $employee->reporting_person_id) == $rep->id ? 'selected' : '' }}>
                                        {{ $rep->user->name ?? 'User' }} &bull; {{ $rep->designation->name ?? 'Role' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5 flex items-center justify-between">
                                <span>Head of Department (HOD) <span class="text-rose-500 font-black">*</span></span>
                                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-normal">Department Head</span>
                            </label>
                            <select name="hod_id" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                <option value="">Select Department HOD</option>
                                @foreach($reportingPersons as $rep)
                                    <option value="{{ $rep->id }}" {{ old('hod_id', $employee->hod_id) == $rep->id ? 'selected' : '' }}>
                                        {{ $rep->user->name ?? 'User' }} &bull; {{ $rep->designation->name ?? 'Role' }} ({{ $rep->department->name ?? 'General' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">EPF Registration No.</label>
                            <input type="text" name="epf_registration_no" value="{{ old('epf_registration_no', $employee->epf_registration_no ?? $employee->epf_number) }}" placeholder="e.g. EPF-1092" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">ETF Registration No.</label>
                            <input type="text" name="etf_registration_no" value="{{ old('etf_registration_no', $employee->etf_registration_no ?? $employee->etf_number) }}" placeholder="e.g. ETF-1092" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Cost Center Classification</label>
                            <select name="cost_classification" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                <option value="Direct" {{ in_array(old('cost_classification', $employee->cost_classification ?? $employee->cost_center), ['Direct', 'Direct (Cost of Sales / Production)']) ? 'selected' : '' }}>Direct (Cost of Sales / Production)</option>
                                <option value="Indirect" {{ in_array(old('cost_classification', $employee->cost_classification ?? $employee->cost_center), ['Indirect', 'Indirect (Operating / Admin)']) ? 'selected' : '' }}>Indirect (Operating / Admin)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Payment Method</label>
                            <select name="payment_method" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                <option value="Bank Transfer" {{ old('payment_method', $employee->payment_method) == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="Cash" {{ old('payment_method', $employee->payment_method) == 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Cheque" {{ old('payment_method', $employee->payment_method) == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                            </select>
                        </div>
                    </div>

                    <!-- Separate Section: Career Path, Designation & Growth (Promotions) -->
                    <div x-data="{
                        desigMode: '{{ old('designation_id') === 'custom' || old('custom_designation') ? 'custom' : 'select' }}',
                        selectedDesigId: '{{ old('designation_id', $employee->designation_id) }}',
                        customDesig: '{{ old('custom_designation', '') }}',
                        promotionDesig: '{{ old('promotion_designation', $employee->promotion_designation ?? '') }}',
                        promotionDate: '{{ old('promotion_date', $employee->promotion_date ?? '') }}',
                        incrementAmt: '{{ old('increment_amount', $employee->increment_amount ?? 0) }}',
                        designationsMap: @js($designations->pluck('name', 'id')),
                        get currentDesigDisplay() {
                            if (this.desigMode === 'custom' && this.customDesig) return this.customDesig;
                            return this.designationsMap[this.selectedDesigId] || '{{ $employee->designation->name ?? 'Designation' }}';
                        },
                        formatRs(val) {
                            let num = parseFloat(val) || 0;
                            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }" class="p-5 sm:p-6 rounded-2xl bg-slate-50/70 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/[0.08] space-y-5 shadow-xs">
                        
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-white/[0.06] pb-4">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200/70 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30 flex items-center justify-center font-black">
                                    <i class="ph ph-trend-up text-lg"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">DESIGNATION & CAREER GROWTH TRAJECTORY</h3>
                                    <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">Current active designation title and recorded promotions with salary increment tracking</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-bold px-3 py-1 rounded-full border transition-all"
                                  :class="promotionDesig ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-white/10'">
                                <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5" :class="promotionDesig ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                <span x-text="promotionDesig ? 'Promotion Tracked' : 'Active Role Only'"></span>
                            </span>
                        </div>

                        <!-- Visual Growth Pathway Timeline -->
                        <div class="p-4 sm:p-5 rounded-xl bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-emerald-50/70 dark:from-blue-950/20 dark:via-indigo-950/15 dark:to-emerald-950/20 border border-slate-200/60 dark:border-white/[0.06]">
                            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 sm:gap-4">
                                
                                <!-- Current Role Box -->
                                <div class="flex items-center gap-3.5 bg-white dark:bg-slate-800/90 p-3.5 rounded-xl border border-blue-200/70 dark:border-blue-500/30 shadow-xs flex-1">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/50 flex items-center justify-center shrink-0">
                                        <i class="ph ph-briefcase text-xl"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                                            <span class="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">Current Role</span>
                                        </div>
                                        <h4 class="text-sm font-extrabold text-slate-900 dark:text-white truncate" x-text="currentDesigDisplay">
                                            {{ $employee->designation->name ?? 'Current Designation' }}
                                        </h4>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Active Position</p>
                                    </div>
                                </div>

                                <!-- Growth Arrow & Increment -->
                                <div class="flex md:flex-col items-center justify-center gap-1.5 shrink-0 px-2 py-1">
                                    <template x-if="parseFloat(incrementAmt) > 0">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/70 dark:border-emerald-700/50 shadow-2xs whitespace-nowrap">
                                            + Rs. <span x-text="formatRs(incrementAmt)"></span>
                                        </span>
                                    </template>
                                    <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-extrabold">
                                        <span class="text-[10px] uppercase tracking-wider hidden sm:inline">Growth</span>
                                        <i class="ph ph-arrow-right text-base"></i>
                                    </div>
                                </div>

                                <!-- Promoted Role Box -->
                                <div class="flex items-center gap-3.5 bg-white dark:bg-slate-800/90 p-3.5 rounded-xl border border-emerald-200/70 dark:border-emerald-500/30 shadow-xs flex-1 transition-all"
                                     :class="promotionDesig ? 'ring-2 ring-emerald-500/20' : 'opacity-80'">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/50 flex items-center justify-center shrink-0">
                                        <i class="ph ph-trend-up text-xl"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-block w-2 h-2 rounded-full" :class="promotionDesig ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                            <span class="text-[10px] font-black uppercase tracking-wider" :class="promotionDesig ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'">Promoted Role</span>
                                        </div>
                                        <h4 class="text-sm font-extrabold text-slate-900 dark:text-white truncate" x-text="promotionDesig || 'No promotion logged yet'">
                                            {{ $employee->promotion_designation ?: 'No promotion logged yet' }}
                                        </h4>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium" x-text="promotionDate ? ('Effective: ' + promotionDate) : (promotionDesig ? 'Effective date pending' : 'Next career milestone')"></p>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Fields Grid (Current Designation + Promotion Details) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs font-semibold">
                            <!-- Current Designation Field -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-slate-700 dark:text-slate-300 font-bold">Current Designation <span class="text-rose-500">*</span></label>
                                    <button type="button" @click="desigMode = (desigMode === 'custom' ? 'select' : 'custom')" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                                        <span x-show="desigMode === 'select'">+ Enter Manually</span>
                                        <span x-show="desigMode === 'custom'" x-cloak>&larr; Select Existing</span>
                                    </button>
                                </div>

                                <div x-show="desigMode === 'select'">
                                    <select name="designation_id" x-model="selectedDesigId" x-bind:disabled="desigMode === 'custom'" @change="if ($event.target.value === 'custom') desigMode = 'custom'" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                                        @foreach($designations as $desig)
                                            <option value="{{ $desig->id }}" {{ old('designation_id', $employee->designation_id) == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
                                        @endforeach
                                        <option value="custom">+ Type New Designation Manually...</option>
                                    </select>
                                </div>

                                <div x-show="desigMode === 'custom'" x-cloak>
                                    <input type="hidden" name="designation_id" value="custom" x-bind:disabled="desigMode !== 'custom'">
                                    <input type="text" name="custom_designation" x-model="customDesig" value="{{ old('custom_designation') }}" placeholder="Type custom designation title..." class="w-full h-10 border border-blue-300 dark:border-blue-500/50 bg-blue-50/70 dark:bg-blue-950/40 rounded-xl text-xs font-bold px-3 text-blue-900 dark:text-blue-200 focus:ring-2 focus:ring-blue-500/20">
                                </div>
                            </div>

                            <!-- Promotion Designation Field -->
                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Promotion Designation</label>
                                <input type="text" name="promotion_designation" x-model="promotionDesig" value="{{ old('promotion_designation', $employee->promotion_designation) }}" placeholder="e.g. Lead Software Engineer" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            </div>

                            <!-- Promotion Effective Date Field -->
                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Promotion Effective Date</label>
                                <input type="date" name="promotion_date" x-model="promotionDate" value="{{ old('promotion_date', $employee->promotion_date) }}" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            </div>

                            <!-- Salary Increment Value (Rs.) Field -->
                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Salary Increment Value (Rs.)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                                    <input type="number" step="any" min="0" name="increment_amount" x-model="incrementAmt" value="{{ old('increment_amount', $employee->increment_amount) }}" placeholder="0.00" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Tab Navigation Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-white/[0.06]">
                        <button type="button" @click="activeTab = 'personal'; window.scrollTo({top: 0, behavior: 'smooth'})" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl border border-slate-200/70 dark:border-white/10 transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                            <i class="ph ph-arrow-left"></i>
                            <span>Back to Personal</span>
                        </button>
                        @if($showCompensation)
                        <button type="button" @click="activeTab = 'compensation'; window.scrollTo({top: 0, behavior: 'smooth'})" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                            <span>Continue to Compensation & Payroll</span>
                            <i class="ph ph-arrow-right"></i>
                        </button>
                        @else
                        <button type="button" @click="activeTab = 'bank_qualifications'; window.scrollTo({top: 0, behavior: 'smooth'})" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                            <span>Continue to Bank & Qualifications</span>
                            <i class="ph ph-arrow-right"></i>
                        </button>
                        @endif
                    </div>
                </div>

                @if($showCompensation)
                <!-- TAB 03: COMPENSATION & PAYROLL -->
                <div x-show="activeTab === 'compensation'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-white/[0.06] pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200/60 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30 font-black text-xs flex items-center justify-center shadow-2xs">03</span>
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">COMPENSATION & STATUTORY EARNINGS ARCHITECTURE</h2>
                                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">Base salary structure, fixed allowances, and statutory contributions</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bg-indigo-50 text-indigo-700 border border-indigo-200/80 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30 px-3 py-1 rounded-full text-xs font-extrabold shadow-2xs">
                                EPF BASE: RS. <span x-text="formatNumber(totalForEpf)">150,000.00</span>
                            </span>
                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30 px-3 py-1 rounded-full text-xs font-extrabold shadow-2xs">
                                GROSS: RS. <span x-text="formatNumber(gross)">160,000.00</span>
                            </span>
                        </div>
                    </div>

                    <!-- Balanced 60/40 Split Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Left 60% Column: Pay Structure, Dynamic Allowances & Tax Deductions -->
                        <div class="lg:col-span-7 space-y-5">
                            <!-- Subsection 1: Base Pay Architecture -->
                            <div class="p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/70 dark:border-white/[0.06] space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i class="ph ph-money text-blue-600 dark:text-blue-400 text-base"></i>
                                        <h3 class="text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">1. Base Pay Architecture (EPF Liable Base)</h3>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400">Qualifying Base for EPF</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 text-xs font-semibold">
                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Basic Salary (Rs.) <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                                            <input type="number" step="any" min="0" name="basic_salary" x-model.number="basic" required placeholder="0.00" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Increments Basic (Rs.)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                                            <input type="number" step="any" min="0" name="increments_basic" x-model.number="incrementsBasic" placeholder="0.00" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Budget Allowance (Rs.)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                                            <input type="number" step="any" min="0" name="budget_allowance" x-model.number="budgetAllowance" placeholder="0.00" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subsection 2: Fixed Allowances Repeater -->
                            <div class="p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/70 dark:border-white/[0.06] space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i class="ph ph-receipt text-indigo-600 dark:text-indigo-400 text-base"></i>
                                        <h3 class="text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">2. Fixed Allowances Architecture</h3>
                                    </div>
                                    <button type="button" @click="addAllowance()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:hover:bg-indigo-500/25 dark:text-indigo-400 rounded-xl text-xs font-bold transition-all cursor-pointer shadow-2xs active:scale-95">
                                        <i class="ph ph-plus-circle text-sm"></i>
                                        <span>Add Allowance</span>
                                    </button>
                                </div>

                                <div class="space-y-2.5">
                                    <template x-for="(allowance, index) in allowances" :key="index">
                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-white/10 shadow-2xs transition-all">
                                            <!-- Allowance Type Select -->
                                            <div class="flex-1">
                                                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1 sm:hidden">Allowance Type</label>
                                                <select :name="'allowances[' + index + '][allowance_type_id]'" x-model="allowance.allowance_type_id" class="w-full h-10 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                                    <template x-for="type in allowanceTypes" :key="type.id">
                                                        <option :value="type.id" :selected="type.id == allowance.allowance_type_id" x-text="type.name + (type.is_epf_liable ? ' (EPF Liable)' : ' (Non-EPF)')"></option>
                                                    </template>
                                                </select>
                                            </div>

                                            <!-- Amount Input with Rs. Prefix -->
                                            <div class="w-full sm:w-44">
                                                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1 sm:hidden">Amount (Rs.)</label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                                                    <input type="number" step="any" min="0" :name="'allowances[' + index + '][amount]'" x-model.number="allowance.amount" placeholder="0.00" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200 dark:border-white/10 rounded-xl text-xs font-black bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                                </div>
                                            </div>

                                            <!-- Statutory Base Badge -->
                                            <div class="w-full sm:w-28 flex items-center justify-between sm:justify-center">
                                                <span class="sm:hidden text-[11px] font-bold text-slate-500 dark:text-slate-400">Statutory:</span>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px] font-extrabold border"
                                                      :class="isAllowanceEpfLiable(allowance.allowance_type_id) 
                                                          ? 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800/60' 
                                                          : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-white/10'">
                                                    <i class="ph" :class="isAllowanceEpfLiable(allowance.allowance_type_id) ? 'ph-check-circle' : 'ph-minus-circle'"></i>
                                                    <span x-text="isAllowanceEpfLiable(allowance.allowance_type_id) ? 'EPF Liable' : 'Non-EPF'"></span>
                                                </span>
                                            </div>

                                            <!-- Remove Button -->
                                            <div class="flex items-center justify-end">
                                                <button type="button" @click="removeAllowance(index)" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-rose-950/40 text-rose-500 dark:text-rose-400 border border-slate-200/80 dark:border-white/10 hover:border-rose-300 dark:hover:border-rose-800/60 flex items-center justify-center transition-all cursor-pointer shadow-2xs active:scale-95" title="Remove Allowance">
                                                    <i class="ph ph-trash text-base"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="!allowances || allowances.length === 0" class="text-center py-5 bg-white/60 dark:bg-slate-900/40 rounded-xl border border-dashed border-slate-200 dark:border-white/10 text-xs text-slate-400 font-medium">
                                        No fixed allowances added yet. Click &ldquo;Add Allowance&rdquo; above.
                                    </div>
                                </div>
                            </div>

                            <!-- Subsection 3: Statutory Deductions & Tax -->
                            <div class="p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/70 dark:border-white/[0.06] space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i class="ph ph-receipt text-rose-600 dark:text-rose-400 text-base"></i>
                                        <h3 class="text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">3. Statutory Deductions & Tax</h3>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400">Monthly Tax Deduction</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">APIT / PAYE Tax (Rs.)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">Rs.</span>
                                            <input type="number" step="any" min="0" name="apit_tax" x-model.number="apit" placeholder="0.00" class="w-full h-10 pl-9 pr-3 text-right border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-black bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500">
                                        </div>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium sm:pt-4">
                                        Advance Personal Income Tax deducted monthly from taxable gross earnings per Inland Revenue guidelines.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right 40% Column: Live Sticky Summary Card -->
                        <div class="lg:col-span-5 lg:sticky lg:top-6 space-y-4">
                            <div class="p-5 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200/80 dark:border-white/10 shadow-sm space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10">
                                    <div class="flex items-center gap-2">
                                        <i class="ph ph-chart-line-up text-blue-600 dark:text-blue-400 text-base"></i>
                                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Live Salary Summary</span>
                                    </div>
                                    <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-200/60 dark:border-blue-500/30">
                                        Sri Lanka Statutory
                                    </span>
                                </div>

                                <div class="space-y-2.5 text-xs">
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium">EPF Qualifying Base</span>
                                        <span class="text-slate-900 dark:text-white font-extrabold">Rs. <span x-text="formatNumber(totalForEpf)">0.00</span></span>
                                    </div>
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium">Fixed Allowances Total</span>
                                        <span class="text-slate-900 dark:text-white font-extrabold">Rs. <span x-text="formatNumber(totalFixed)">0.00</span></span>
                                    </div>
                                    <div class="flex items-center justify-between py-2 border-t border-slate-100 dark:border-white/10 bg-slate-50/60 dark:bg-slate-900/40 px-3 rounded-xl">
                                        <span class="text-slate-800 dark:text-slate-200 font-black">Gross Earnings</span>
                                        <span class="text-slate-900 dark:text-white font-black text-sm">Rs. <span x-text="formatNumber(gross)">0.00</span></span>
                                    </div>

                                    <div class="pt-2 border-t border-slate-100 dark:border-white/10 space-y-2">
                                        <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
                                            <span class="font-medium">EPF Employee (8%)</span>
                                            <span class="font-extrabold">- Rs. <span x-text="formatNumber(epfEmployee)">0.00</span></span>
                                        </div>

                                        <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
                                            <span class="font-medium">APIT Tax (Monthly)</span>
                                            <span class="font-extrabold" x-text="apit > 0 ? '- Rs. ' + formatNumber(apit) : 'Rs. 0.00'">Rs. 0.00</span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 pt-2 text-[11px]">
                                            <div class="bg-blue-50/70 dark:bg-blue-950/40 p-2.5 rounded-xl border border-blue-200/80 dark:border-blue-800/60">
                                                <span class="text-slate-500 dark:text-slate-400 block font-bold text-[10px] uppercase">EPF Employer 12%</span>
                                                <span class="text-blue-700 dark:text-blue-300 font-black text-xs">Rs. <span x-text="formatNumber(epfEmployer)">0.00</span></span>
                                            </div>
                                            <div class="bg-blue-50/70 dark:bg-blue-950/40 p-2.5 rounded-xl border border-blue-200/80 dark:border-blue-800/60">
                                                <span class="text-slate-500 dark:text-slate-400 block font-bold text-[10px] uppercase">ETF Employer 3%</span>
                                                <span class="text-blue-700 dark:text-blue-300 font-black text-xs">Rs. <span x-text="formatNumber(etfEmployer)">0.00</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estimated Net Take-Home Hero Card -->
                                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 dark:from-emerald-500 dark:to-teal-600 text-white p-5 rounded-2xl shadow-lg shadow-emerald-500/20 space-y-1.5">
                                    <span class="text-[10px] font-black tracking-wider uppercase text-emerald-100 block">Estimated Net Take-Home</span>
                                    <div class="text-2xl font-black tracking-tight">
                                        Rs. <span x-text="formatNumber(netTakeHome)">0.00</span>
                                        <span class="text-xs font-semibold text-emerald-100">/ mo</span>
                                    </div>
                                    <span class="text-[11px] text-emerald-100/90 block pt-0.5 leading-snug">Net pay after statutory Employee EPF (8%) & APIT tax deductions</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Navigation Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-white/[0.06]">
                        <button type="button" @click="activeTab = 'employment'; window.scrollTo({top: 0, behavior: 'smooth'})" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200/80 dark:border-white/10 transition-all flex items-center gap-2 cursor-pointer active:scale-95 shadow-2xs">
                            <i class="ph ph-arrow-left"></i>
                            <span>Back to Role</span>
                        </button>
                        <button type="button" @click="activeTab = 'bank_qualifications'; window.scrollTo({top: 0, behavior: 'smooth'})" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                            <span>Continue to Bank & Qualifications</span>
                            <i class="ph ph-arrow-right"></i>
                        </button>
                    </div>
                </div>
                @endif

                <!-- TAB 04: BANK & QUALIFICATIONS (Sections 04 + 05 Combined) -->
                <div x-show="activeTab === 'bank_qualifications'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-8">
                    <!-- Part 1: Financial & Bank Account Details (2-Column Grid) -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-200/60 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 font-black text-xs flex items-center justify-center">{{ $showCompensation ? '04A' : '03A' }}</span>
                                <div>
                                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">FINANCIAL & BANK ACCOUNT DETAILS</h2>
                                    <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">Official bank disbursement credentials for automated salary remittance</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs font-semibold">
                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Account Holder Name</label>
                                <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $employee->bank_account_name ?? $employee->user->name) }}" placeholder="e.g. J. Doe" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Account Number</label>
                                <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" placeholder="e.g. 1000293847" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Bank Name</label>
                                <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" placeholder="e.g. Commercial Bank of Ceylon" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Branch Name</label>
                                <input type="text" name="bank_branch" value="{{ old('bank_branch', $employee->bank_branch) }}" placeholder="e.g. Colombo Fort" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">Branch Code</label>
                                <input type="text" name="branch_code" value="{{ old('branch_code', $employee->branch_code) }}" placeholder="e.g. 035" class="w-full md:w-1/2 h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-bold bg-slate-50/70 dark:bg-slate-800/60 text-slate-900 dark:text-white px-3 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- Part 2: Higher Education & Working Experience -->
                    <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-white/[0.06]">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 border border-violet-200/60 dark:bg-violet-500/15 dark:text-violet-400 dark:border-violet-500/30 font-black text-xs flex items-center justify-center">{{ $showCompensation ? '04B' : '03B' }}</span>
                                <div>
                                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">HIGHER EDUCATION & WORKING EXPERIENCE</h2>
                                    <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">Academic credentials, university degrees, and past professional career history</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs font-semibold">
                            <!-- Higher Education -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-slate-800 dark:text-slate-200 font-extrabold flex items-center gap-1.5 text-xs">
                                        <i class="ph ph-graduation-cap text-blue-600 dark:text-blue-400 text-base"></i> Higher Education Qualifications
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
                                                    <input type="text" :name="'higher_education[' + eIdx + '][qualification]'" x-model="edu.qualification" placeholder="e.g. BSc in Computer Science" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Institute *</label>
                                                    <input type="text" :name="'higher_education[' + eIdx + '][institute]'" x-model="edu.institute" placeholder="e.g. University of Colombo" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Country</label>
                                                    <input type="text" :name="'higher_education[' + eIdx + '][country]'" x-model="edu.country" placeholder="e.g. Sri Lanka" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Duration</label>
                                                    <input type="text" :name="'higher_education[' + eIdx + '][duration]'" x-model="edu.duration" placeholder="e.g. 2018 - 2022 (4 Years)" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
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
                                                    <input type="text" :name="'working_experience[' + xIdx + '][company]'" x-model="exp.company" placeholder="e.g. ABC Technologies" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Designation *</label>
                                                    <input type="text" :name="'working_experience[' + xIdx + '][designation]'" x-model="exp.designation" placeholder="e.g. Senior Software Engineer" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Duration</label>
                                                    <input type="text" :name="'working_experience[' + xIdx + '][duration]'" x-model="exp.duration" placeholder="e.g. 2021 - 2024 (3 Years)" class="w-full h-10 border border-slate-200/80 dark:border-white/10 rounded-xl text-xs font-medium bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Navigation Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-white/[0.06]">
                        <button type="button" @click="activeTab = '{{ $showCompensation ? 'compensation' : 'employment' }}'; window.scrollTo({top: 0, behavior: 'smooth'})" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl border border-slate-200/70 dark:border-white/10 transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                            <i class="ph ph-arrow-left"></i>
                            <span>Back to {{ $showCompensation ? 'Compensation' : 'Role' }}</span>
                        </button>
                        <button type="submit" class="px-6 py-2.5 bg-[#0071E3] hover:bg-[#0077ED] active:scale-95 text-white text-xs font-bold rounded-xl shadow-sm shadow-blue-500/25 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="ph ph-floppy-disk text-base"></i>
                            <span>Update Profile & Save</span>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
@endsection
