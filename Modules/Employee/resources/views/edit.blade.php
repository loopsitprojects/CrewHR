@extends('layouts.app', ['title' => 'LOOPS HR - Edit Employee Profile'])

@section('breadcrumbs')
    <span class="text-slate-300">/</span>
    <a href="{{ route('employee.index') }}" class="text-slate-500 hover:text-blue-600 transition-colors">Employees Directory</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('employee.show', $employee->id) }}" class="text-slate-500 hover:text-blue-600 transition-colors">{{ $employee->user->name ?? 'Profile' }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-blue-600 font-black">Edit Profile</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-8" x-data="{
    basic: {{ old('basic_salary', $employee->basic_salary ?? 190000) }},
    fixed: {{ old('fixed_allowance', $employee->fixed_allowance ?? 35000) }},
    other: {{ old('other_allowance', $employee->other_allowance ?? 25000) }},
    apit: {{ old('apit_tax', $employee->apit_tax ?? 12000) }},
    
    get gross() {
        return (parseFloat(this.basic) || 0) + (parseFloat(this.fixed) || 0) + (parseFloat(this.other) || 0);
    },
    get epfEmployee() {
        return (parseFloat(this.basic) || 0) * 0.08;
    },
    get epfEmployer() {
        return (parseFloat(this.basic) || 0) * 0.12;
    },
    get etfEmployer() {
        return (parseFloat(this.basic) || 0) * 0.03;
    },
    get netTakeHome() {
        return this.gross - this.epfEmployee - (parseFloat(this.apit) || 0);
    },
    formatNumber(num) {
        return new Intl.NumberFormat('en-LK', { maximumFractionDigits: 0 }).format(num);
    }
}">

    <!-- Top Action Header Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('employee.index') }}" class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900 flex items-center justify-center transition-all shadow-sm">
                <i class="ph ph-arrow-left text-lg"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Edit Employee Profile</h1>
                    <span class="bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-extrabold px-2.5 py-0.5 rounded-full">{{ $employee->employee_id_number }}</span>
                </div>
                <p class="text-xs font-bold text-slate-500 mt-0.5">{{ $employee->title ?? 'Mr.' }} {{ $employee->user->name ?? 'Employee' }} <span class="text-slate-300 mx-1">•</span> <span class="text-blue-600 font-extrabold">{{ $employee->designation->name ?? 'Staff' }}</span></p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('employee.index') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-200 transition-all">Cancel</a>
            <button form="editEmployeeForm" type="submit" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-blue-500/25 transition-all">
                <i class="ph ph-floppy-disk text-base"></i>
                <span>Update Profile & Save</span>
            </button>
        </div>
    </div>

    <!-- Edit Form with 6 Numbered Cards -->
    <form id="editEmployeeForm" action="{{ route('employee.update', $employee->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- SECTION 01: PROFILE PICTURE & PERSONAL INFO -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6 relative overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-200/60 font-black text-xs flex items-center justify-center">01</span>
                    <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">PROFILE PICTURE & PERSONAL INFORMATION</h2>
                </div>
            </div>

            <!-- Avatar Uploader -->
            <div class="flex flex-col md:flex-row items-start md:items-center gap-5 bg-slate-50/70 p-4 rounded-2xl border border-slate-200/60">
                <div class="relative shrink-0">
                    <img src="{{ $employee->profile_picture ?? ('https://ui-avatars.com/api/?name=' . urlencode($employee->user->name ?? 'Employee') . '&background=0D8ABC&color=fff') }}" class="w-16 h-16 rounded-full object-cover border-2 border-blue-500 shadow-md" alt="Avatar">
                </div>

                <div class="flex-1 w-full space-y-2">
                    <span class="text-xs font-bold text-slate-700 block">Profile Picture (File or Image URL)</span>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="file" name="profile_picture_file" accept="image/*" class="w-full border-slate-200/80 rounded-xl text-xs font-medium bg-white p-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <input type="text" name="profile_picture" value="{{ old('profile_picture', $employee->profile_picture) }}" placeholder="Image URL: https://..." class="w-full border-slate-200/80 rounded-xl text-xs font-medium bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Form Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Title *</label>
                    <select name="title" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 focus:bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="Mr." {{ old('title', $employee->title) == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                        <option value="Ms." {{ old('title', $employee->title) == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                        <option value="Mrs." {{ old('title', $employee->title) == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                        <option value="Dr." {{ old('title', $employee->title) == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $employee->user->name ?? '') }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 focus:bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" required>
                    @error('full_name') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 focus:bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">NIC / Passport *</label>
                    <input type="text" name="nic_passport" value="{{ old('nic_passport', $employee->nic_passport) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 focus:bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Username *</label>
                    <input type="text" name="username" value="{{ old('username', $employee->user->username ?? '') }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 focus:bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" required>
                    @error('username') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $employee->user->email ?? '') }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 focus:bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" required>
                    @error('email') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $employee->phone_number) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 focus:bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Emergency Contact</label>
                    <input type="text" name="emergency_contact" value="{{ old('emergency_contact', $employee->emergency_contact) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 focus:bg-white p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- SECTION 02: EMPLOYMENT ROLE, SYSTEM PERMISSIONS & IMPORTANT DATES -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200/60 font-black text-xs flex items-center justify-center">02</span>
                    <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">EMPLOYMENT ROLE, SYSTEM PERMISSIONS & IMPORTANT DATES</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Employee ID *</label>
                    <input type="text" name="employee_id_number" value="{{ old('employee_id_number', $employee->employee_id_number) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5" required>
                    @error('employee_id_number') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Department *</label>
                    <select name="department_id" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">System Role *</label>
                    <select name="system_role" class="w-full border-purple-200 rounded-xl text-xs font-bold bg-purple-50 text-purple-900 p-2.5">
                        <option value="Employee" {{ old('system_role', $employee->system_role) == 'Employee' ? 'selected' : '' }}>Employee</option>
                        <option value="Manager (Team Approvals)" {{ old('system_role', $employee->system_role) == 'Manager (Team Approvals)' ? 'selected' : '' }}>Manager (Team Approvals)</option>
                        <option value="HR Lead" {{ old('system_role', $employee->system_role) == 'HR Lead' ? 'selected' : '' }}>HR Lead</option>
                        <option value="Super (Admin)" {{ old('system_role', $employee->system_role) == 'Super (Admin)' ? 'selected' : '' }}>Super (Admin)</option>
                    </select>
                </div>

                <div x-data="{ desigMode: '{{ old('designation_id') === 'custom' || old('custom_designation') ? 'custom' : 'select' }}' }">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-slate-700 font-bold">Current Designation *</label>
                        <button type="button" class="text-[11px] text-blue-600 font-bold hover:underline cursor-pointer" @click="desigMode = (desigMode === 'custom' ? 'select' : 'custom')">
                            <span x-show="desigMode === 'select'">+ Enter Manually</span>
                            <span x-show="desigMode === 'custom'" x-cloak>← Select Existing</span>
                        </button>
                    </div>

                    <div x-show="desigMode === 'select'">
                        <select name="designation_id" x-bind:disabled="desigMode === 'custom'" @change="if ($event.target.value === 'custom') desigMode = 'custom'" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                            @foreach($designations as $desig)
                                <option value="{{ $desig->id }}" {{ old('designation_id', $employee->designation_id) == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
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
                    <label class="block text-slate-700 font-bold mb-1.5">Joined Date *</label>
                    <input type="date" name="joined_date" value="{{ old('joined_date', $employee->joined_date) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Reporting Person</label>
                    <select name="reporting_person_id" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                        <option value="">None (Top Manager)</option>
                        @foreach($reportingPersons as $rp)
                            <option value="{{ $rp->id }}" {{ old('reporting_person_id', $employee->reporting_person_id) == $rp->id ? 'selected' : '' }}>{{ $rp->user->name ?? 'Reporting Person' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">EPF Registration No.</label>
                    <input type="text" name="epf_registration_no" value="{{ old('epf_registration_no', $employee->epf_registration_no) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-slate-700 font-bold mb-1.5">Job Category *</label>
                    <select name="job_category" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                        <option value="Full Time (Permanent)" {{ old('job_category', $employee->job_category) == 'Full Time (Permanent)' ? 'selected' : '' }}>Full Time (Permanent)</option>
                        <option value="Probation" {{ old('job_category', $employee->job_category) == 'Probation' ? 'selected' : '' }}>Probation</option>
                        <option value="Contract" {{ old('job_category', $employee->job_category) == 'Contract' ? 'selected' : '' }}>Contract</option>
                        <option value="Intern" {{ old('job_category', $employee->job_category) == 'Intern' ? 'selected' : '' }}>Intern</option>
                    </select>
                </div>
            </div>

            <!-- Career Growth Promotion Box -->
            <div class="bg-gradient-to-r from-emerald-50/60 to-teal-50/60 border border-emerald-200/80 p-4.5 rounded-2xl space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-emerald-900 flex items-center gap-1.5">
                        <i class="ph ph-trend-up text-emerald-600 text-base"></i> CAREER GROWTH: PROMOTION & SALARY INCREMENT (OPTIONAL)
                    </span>
                    <span class="text-[10px] text-emerald-700 italic font-medium">Increments can be granted with or without a promotion</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs font-semibold">
                    <div>
                        <label class="block text-emerald-900 font-bold mb-1">Salary Increment Value (Rs.)</label>
                        <input type="number" name="increment_amount" value="{{ old('increment_amount', $employee->increment_amount) }}" class="w-full border-emerald-200 rounded-xl text-xs font-bold bg-white p-2.5">
                    </div>
                    <div>
                        <label class="block text-emerald-900 font-bold mb-1">Promotion Designation</label>
                        <input type="text" name="promotion_designation" value="{{ old('promotion_designation', $employee->promotion_designation) }}" class="w-full border-emerald-200 rounded-xl text-xs font-bold bg-white p-2.5">
                    </div>
                    <div>
                        <label class="block text-emerald-900 font-bold mb-1">Promotion Date</label>
                        <input type="date" name="promotion_date" value="{{ old('promotion_date', $employee->promotion_date) }}" class="w-full border-emerald-200 rounded-xl text-xs font-bold bg-white p-2.5">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 03: BASIC SALARY & MONTHLY ALLOWANCES -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200/60 font-black text-xs flex items-center justify-center">03</span>
                    <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">BASIC SALARY & MONTHLY ALLOWANCES (RS.)</h2>
                </div>
                <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1 rounded-full text-xs font-extrabold tracking-wide shadow-sm">
                    GROSS: RS. <span x-text="formatNumber(gross)">250,000</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Basic Salary (Rs.) *</label>
                    <input type="number" name="basic_salary" x-model="basic" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Fixed Allowance (Rs.)</label>
                    <input type="number" name="fixed_allowance" x-model="fixed" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Other Allowance (Rs.)</label>
                    <input type="number" name="other_allowance" x-model="other" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>
            </div>

            <!-- Statutory Deductions HERO BANNER -->
            <div class="bg-blue-50/40 border border-blue-200/80 p-5 rounded-2xl space-y-4">
                <h4 class="text-xs font-extrabold text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="ph ph-receipt text-blue-600 text-base"></i> Sri Lanka Statutory Payroll Deductions & Take-Home Calculation
                </h4>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs font-bold">
                    <div class="bg-white p-3.5 rounded-xl border border-blue-100 shadow-sm">
                        <span class="text-[10px] text-slate-400 block uppercase font-extrabold">EPF (Employee 8%)</span>
                        <span class="text-rose-600 font-black text-sm">- Rs. <span x-text="formatNumber(epfEmployee)">15,200</span></span>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-blue-100 shadow-sm">
                        <span class="text-[10px] text-slate-400 block uppercase font-extrabold">EPF Employer (12%)</span>
                        <span class="text-blue-600 font-black text-sm">Rs. <span x-text="formatNumber(epfEmployer)">22,800</span></span>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-blue-100 shadow-sm">
                        <span class="text-[10px] text-slate-400 block uppercase font-extrabold">ETF Employer (3%)</span>
                        <span class="text-blue-600 font-black text-sm">Rs. <span x-text="formatNumber(etfEmployer)">5,700</span></span>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-blue-100 shadow-sm">
                        <label class="text-[10px] text-slate-400 block uppercase font-extrabold mb-1">PAYE / APIT Tax (Rs.)</label>
                        <input type="number" name="apit_tax" x-model="apit" class="w-full border-slate-200 rounded-lg text-xs font-black p-1 bg-slate-50 text-rose-600">
                    </div>
                </div>

                <!-- Estimated Net Take-Home Hero Pill -->
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white p-5 rounded-xl shadow-lg shadow-emerald-500/20 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-extrabold tracking-wide uppercase text-emerald-100 block">Estimated Net Take-Home</span>
                        <span class="text-xs text-white/80">Calculated after statutory Employee EPF (8%) & APIT tax deductions</span>
                    </div>
                    <span class="text-2xl font-black tracking-tight">Rs. <span x-text="formatNumber(netTakeHome)">222,800</span> <span class="text-sm font-semibold">/ month</span></span>
                </div>
            </div>
        </div>

        <!-- SECTION 04: FINANCIAL & BANK DETAILS -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-200/60 font-black text-xs flex items-center justify-center">04</span>
                    <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">FINANCIAL & BANK ACCOUNT DETAILS</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Bank Name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Bank Branch</label>
                    <input type="text" name="bank_branch" value="{{ old('bank_branch', $employee->bank_branch) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Account Number</label>
                    <input type="text" name="account_number" value="{{ old('account_number', $employee->account_number) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5">Account Holder Name</label>
                    <input type="text" name="account_holder_name" value="{{ old('account_holder_name', $employee->account_holder_name) }}" class="w-full border-slate-200/80 rounded-xl text-xs font-bold bg-slate-50/50 p-2.5">
                </div>
            </div>
        </div>

        <!-- SECTION 05: LEAVE QUOTA ADJUSTMENTS -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-200/60 font-black text-xs flex items-center justify-center">05</span>
                    <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">LEAVE QUOTA ADJUSTMENTS</h2>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">Manually override or adjust total allocated leave days & used balances.</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs font-semibold">
                @forelse($employee->leaveBalances as $bal)
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">{{ $bal->leaveType->name ?? 'Leave' }}</span>
                            <span class="text-[10px] text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200 font-bold">
                                Available: {{ $bal->allocated - $bal->used }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[10px] text-slate-500 font-bold block mb-1">Allocated (Days)</label>
                                <input type="number" name="leave_balances[{{ $bal->id }}][allocated]" value="{{ old('leave_balances.'.$bal->id.'.allocated', $bal->allocated) }}" step="0.5" class="w-full border-slate-200 rounded-lg text-xs font-bold p-1.5 bg-white text-center">
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-500 font-bold block mb-1">Used (Days)</label>
                                <input type="number" name="leave_balances[{{ $bal->id }}][used]" value="{{ old('leave_balances.'.$bal->id.'.used', $bal->used) }}" step="0.5" class="w-full border-slate-200 rounded-lg text-xs font-bold p-1.5 bg-white text-center text-rose-600">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-slate-400 text-xs italic py-2">
                        No active leave balances assigned. Submitting this form will automatically generate leave quotas.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- SECTION 06: QUALIFICATIONS -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 border border-violet-200/60 font-black text-xs flex items-center justify-center">06</span>
                    <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">HIGHER EDUCATION & PROFESSIONAL QUALIFICATIONS</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 font-bold mb-1.5 flex items-center gap-1.5">
                        <i class="ph ph-graduation-cap text-blue-600 text-base"></i> Higher Education Qualifications
                    </label>
                    <textarea name="higher_education" rows="3" class="w-full border-slate-200/80 rounded-xl text-xs font-medium bg-slate-50/50 p-3 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">{{ old('higher_education', $employee->higher_education) }}</textarea>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1.5 flex items-center gap-1.5">
                        <i class="ph ph-certificate text-purple-600 text-base"></i> Professional & Other Qualifications
                    </label>
                    <textarea name="professional_qualifications" rows="3" class="w-full border-slate-200/80 rounded-xl text-xs font-medium bg-slate-50/50 p-3 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">{{ old('professional_qualifications', $employee->professional_qualifications) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('employee.index') }}" class="px-6 py-3 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-200 transition-all">Cancel</a>
            <button type="submit" class="px-7 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/25 transition-all flex items-center gap-2">
                <i class="ph ph-floppy-disk text-base"></i>
                <span>Update Profile & Save</span>
            </button>
        </div>
    </form>

</div>
@endsection
