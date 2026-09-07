@extends('layouts.app', ['title' => 'LOOPS HR - Add New Employee Profile'])

@section('breadcrumbs')
    <span class="text-slate-300">/</span>
    <a href="{{ route('employee.index') }}" class="text-slate-500 hover:text-blue-600 transition-colors">Employees Directory</a>
    <span class="text-slate-300">/</span>
    <span class="text-blue-600 font-black">Add New Employee</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{
    basic: 150000,
    fixed: 25000,
    other: 15000,
    apit: 5000,
    
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

                <div class="md:col-span-2">
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
                <h2 class="text-xs font-black text-blue-600 uppercase tracking-wider">3. Basic Salary & Monthly Allowances (Rs.)</h2>
                <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1 rounded-full text-xs font-extrabold tracking-wide">
                    GROSS: RS. <span x-text="formatNumber(gross)">190,000</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-gray-700 font-bold mb-1">Basic Salary (Rs.) *</label>
                    <input type="number" name="basic_salary" x-model="basic" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-1">Fixed Allowance (Rs.)</label>
                    <input type="number" name="fixed_allowance" x-model="fixed" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-1">Other Allowance (Rs.)</label>
                    <input type="number" name="other_allowance" x-model="other" class="w-full border-gray-200 rounded-xl text-xs font-bold bg-gray-50 p-2.5">
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
