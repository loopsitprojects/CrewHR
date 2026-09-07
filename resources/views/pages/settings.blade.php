@extends('layouts.app', ['title' => 'LOOPS HR - System & Profile Settings', 'breadcrumb' => 'Settings'])

@php
    $userSystemRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Employee');
    $isAdminOrHr = in_array($userSystemRole, ['HR Lead', 'Super (Admin)']);
    $activeUser = Auth::user() ?? $user;
    $activeEmp = $employee ?? ($activeUser ? \Modules\Employee\Models\Employee::where('user_id', $activeUser->id)->first() : null);
@endphp

@section('content')
<div class="space-y-6" x-data="{ addDeptModal: false, editDeptModal: false, addModuleModal: false, editDept: { id: '', name: '', code: '', hod_name: '' } }">

    <!-- Flash Status Messages -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs font-bold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-base text-emerald-600 dark:text-emerald-400"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75"><i class="ph ph-x text-sm"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-bold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="ph ph-warning-circle text-base text-rose-600 dark:text-rose-400"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75"><i class="ph ph-x text-sm"></i></button>
        </div>
    @endif

    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-900/60">
                    <i class="ph ph-gear text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $isAdminOrHr ? 'System Configurations & Security' : 'My Account & Security Settings' }}
                    </h1>
                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/60">
                        Role: {{ $userSystemRole }}
                    </span>
                </div>
            </div>
            <p class="text-xs font-semibold text-slate-400 dark:text-slate-400 mt-1">
                {{ $isAdminOrHr ? 'Manage organization configurations, department structures, leave settings, role permissions, and admin account security.' : 'Update your personal profile information, contact details, bank details, and account password.' }}
            </p>
        </div>
    </div>

    <!-- SECTION A: PERSONAL PROFILE INFORMATION (FOR REGULAR EMPLOYEES ONLY) -->
    @if(!$isAdminOrHr)
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="ph ph-user-circle text-blue-600 dark:text-blue-400 text-lg"></i>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Personal Profile Information</h2>
            </div>
            <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-blue-200/60 dark:border-blue-900/60">
                User Account ID: {{ $activeUser->id ?? 1 }}
            </span>
        </div>

        <form action="{{ route('settings.profile.update') }}" method="POST" class="space-y-4 text-xs font-semibold">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $activeUser->name ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                    @error('name') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $activeUser->email ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                    @error('email') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $activeEmp->phone_number ?? '') }}" placeholder="e.g. +94 77 123 4567" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Emergency Contact Number</label>
                    <input type="text" name="emergency_contact" value="{{ old('emergency_contact', $activeEmp->emergency_contact ?? '') }}" placeholder="e.g. +94 71 987 6543" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">NIC / Passport Number</label>
                    <input type="text" name="nic_passport" value="{{ old('nic_passport', $activeEmp->nic_passport ?? '') }}" placeholder="e.g. 199512345678" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Employee ID</label>
                    <input type="text" value="{{ $activeEmp->employee_id_number ?? 'EMP-001' }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-xl text-xs font-bold p-2.5 cursor-not-allowed" readonly>
                </div>
            </div>

            <!-- Sub-Section 1: Bank Account Details -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-bank text-amber-600 dark:text-amber-400 text-base"></i>
                    <h3 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">Financial & Bank Account Details</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $activeEmp->bank_name ?? '') }}" placeholder="e.g. Commercial Bank of Ceylon" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Bank Branch</label>
                        <input type="text" name="bank_branch" value="{{ old('bank_branch', $activeEmp->bank_branch ?? '') }}" placeholder="e.g. Colombo Fort Branch" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Account Number</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $activeEmp->account_number ?? '') }}" placeholder="e.g. 8001234567" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Account Holder Name</label>
                        <input type="text" name="account_holder_name" value="{{ old('account_holder_name', $activeEmp->account_holder_name ?? '') }}" placeholder="e.g. Shimal Perera" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                    </div>
                </div>
            </div>

            <!-- Sub-Section 2: Educational & Professional Qualifications -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-graduation-cap text-violet-600 dark:text-violet-400 text-base"></i>
                    <h3 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">Higher Education & Professional Qualifications</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Higher Education Qualifications</label>
                        <textarea name="higher_education" rows="2" placeholder="e.g. BSc (Hons) Software Engineering - UoM" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5">{{ old('higher_education', $activeEmp->higher_education ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Professional Qualifications</label>
                        <textarea name="professional_qualifications" rows="2" placeholder="e.g. CIMA / AWS Certified Solutions Architect" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5">{{ old('professional_qualifications', $activeEmp->professional_qualifications ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/20 transition-all">
                    <i class="ph ph-user-check text-sm"></i>
                    <span>Update Profile Details</span>
                </button>
            </div>
        </form>
    </div>
    @else
    <!-- INFO NOTICE FOR ADMIN ACCOUNTS -->
    <div class="bg-slate-50 dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 font-bold">
            <i class="ph ph-shield-check text-lg"></i>
        </div>
        <div class="text-xs font-semibold text-slate-600 dark:text-slate-300">
            <span class="font-extrabold text-slate-900 dark:text-white">Admin System Account:</span> Personal employee profile data editing (bank details, emergency contacts, qualifications) is excluded for System Administrator accounts. Use Employee Directory profiles to manage employee personal records.
        </div>
    </div>
    @endif

    <!-- SECTION B: ACCOUNT SECURITY & PASSWORD UPDATE (FOR ALL USERS) -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="ph ph-lock-key text-blue-600 dark:text-blue-400 text-lg"></i>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Account Security & Password Update</h2>
            </div>
            <span class="bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-amber-200/80 dark:border-amber-800/60">
                Min 8 Characters Required
            </span>
        </div>

        <form action="{{ route('settings.password.update') }}" method="POST" 
              x-data="{ showCurrent: false, showNew: false, showConfirm: false }" 
              class="space-y-4 text-xs font-semibold">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Current Password -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Current Password *</label>
                    <div class="relative flex items-center">
                        <input :type="showCurrent ? 'text' : 'password'" name="current_password" placeholder="••••••••" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 pr-10 focus:ring-2 focus:ring-blue-500/20" required>
                        <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i data-lucide="eye" x-show="!showCurrent" class="w-4 h-4"></i>
                            <i data-lucide="eye-off" x-show="showCurrent" x-cloak class="w-4 h-4 text-blue-600 dark:text-sky-400"></i>
                        </button>
                    </div>
                    @error('current_password') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">New Password *</label>
                    <div class="relative flex items-center">
                        <input :type="showNew ? 'text' : 'password'" name="password" placeholder="••••••••" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 pr-10 focus:ring-2 focus:ring-blue-500/20" required>
                        <button type="button" @click="showNew = !showNew" class="absolute right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i data-lucide="eye" x-show="!showNew" class="w-4 h-4"></i>
                            <i data-lucide="eye-off" x-show="showNew" x-cloak class="w-4 h-4 text-blue-600 dark:text-sky-400"></i>
                        </button>
                    </div>
                    @error('password') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Confirm New Password *</label>
                    <div class="relative flex items-center">
                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" placeholder="••••••••" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 pr-10 focus:ring-2 focus:ring-blue-500/20" required>
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i data-lucide="eye" x-show="!showConfirm" class="w-4 h-4"></i>
                            <i data-lucide="eye-off" x-show="showConfirm" x-cloak class="w-4 h-4 text-blue-600 dark:text-sky-400"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-emerald-500/20 transition-all cursor-pointer">
                    <i class="ph ph-key text-sm"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ADMINISTRATIVE SYSTEM SETTINGS (HR LEAD & SUPER ADMIN ONLY) -->
    @if($isAdminOrHr)
    <form id="settingsForm" action="{{ route('settings.save') }}" method="POST" class="space-y-6">
        @csrf

        <!-- 1. General Organization Profile -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <i class="ph ph-buildings text-blue-600 dark:text-blue-400 text-lg"></i>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">1. General Organization Profile</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Company / App Name</label>
                    <input type="text" name="company_name" value="{{ $settingsRaw['company_name'] ?? 'Loops HR Portal (Sri Lanka)' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Timezone</label>
                    <select name="timezone" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                        <option value="Asia/Colombo (GMT+5:30)" selected class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white">Asia/Colombo (GMT+5:30)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Base Currency</label>
                    <input type="text" name="base_currency" value="{{ $settingsRaw['base_currency'] ?? 'LKR (Rs.)' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Fiscal Year Start Month</label>
                    <select name="fiscal_year_start" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                        <option value="January" selected class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white">January</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. Sri Lanka Shop & Office Employees Act Leave Entitlements -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-scales text-blue-600 dark:text-blue-400 text-lg"></i>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">2. Sri Lanka Shop & Office Employees Act Leave Entitlements</h2>
                </div>
                <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-blue-200/60 dark:border-blue-900/60">Act No. 19 of 1954 Compliant</span>
            </div>

            <!-- Rules Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50/50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/60 p-3.5 rounded-xl space-y-1 text-xs">
                    <h4 class="font-bold text-blue-900 dark:text-blue-300 flex items-center gap-1.5"><i class="ph ph-info text-blue-600 dark:text-blue-400"></i> ANNUAL LEAVE ENTITLEMENT</h4>
                    <p class="text-[11px] text-blue-700 dark:text-blue-400">1st Calendar Year: Based on joined date (Jan-Mar: 14d, Apr-Jun: 10d, Jul-Sep: 7d, Oct-Dec: 4d).</p>
                    <p class="text-[11px] font-bold text-blue-900 dark:text-blue-200">2nd Year Onwards: 14 Days Full Entitlement.</p>
                </div>

                <div class="bg-emerald-50/50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 p-3.5 rounded-xl space-y-1 text-xs">
                    <h4 class="font-bold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5"><i class="ph ph-info text-emerald-600 dark:text-emerald-400"></i> CASUAL LEAVE & STATUTORY HOLIDAYS</h4>
                    <p class="text-[11px] text-emerald-700 dark:text-emerald-400">Permanent: 7 Days Max Casual Leave per year.</p>
                    <p class="text-[11px] font-bold text-emerald-900 dark:text-emerald-200">Statutory Mercantile Holidays: 8 Paid Holidays / Year.</p>
                </div>
            </div>

            <!-- Quota Input Fields -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-xs font-semibold">
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Annual Leave</label>
                    <input type="number" name="annual_leave_max" value="{{ $settingsRaw['annual_leave_max'] ?? 21 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2 text-center">
                </div>
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Casual Leave</label>
                    <input type="number" name="casual_leave_max" value="{{ $settingsRaw['casual_leave_max'] ?? 7 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2 text-center">
                </div>
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Medical Leave</label>
                    <input type="number" name="medical_leave_max" value="{{ $settingsRaw['medical_leave_max'] ?? 14 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2 text-center">
                </div>
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Short Leave (Hrs/Mo)</label>
                    <input type="number" name="short_leave_max" value="{{ $settingsRaw['short_leave_max'] ?? 2 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2 text-center">
                </div>
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Duty Leave (Days)</label>
                    <input type="number" name="duty_leave_max" value="{{ $settingsRaw['duty_leave_max'] ?? 5 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2 text-center">
                </div>
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Lieu Leave (Days)</label>
                    <input type="number" name="lieu_leave_max" value="{{ $settingsRaw['lieu_leave_max'] ?? 2 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2 text-center">
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="space-y-2 text-xs font-bold text-slate-700 dark:text-slate-300 pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="auto_sync_holidays" value="1" {{ ($settingsRaw['auto_sync_holidays'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Auto-sync official Sri Lanka Gazette Public & Poya Holidays via remote API</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="allow_half_day" value="1" {{ ($settingsRaw['allow_half_day'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Allow staff members to submit Half-Day leave requests</span>
                </label>

                <!-- Weekend Day Off System Toggles -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-4">
                    <span class="text-slate-900 dark:text-white font-black flex items-center gap-1.5">
                        <i class="ph ph-calendar-x text-rose-500 text-base"></i> Weekend Day Off Configuration:
                    </span>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-[#1e2d4d] px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                        <input type="checkbox" name="weekend_saturday_off" value="1" {{ ($settingsRaw['weekend_saturday_off'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Saturday Off</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-[#1e2d4d] px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                        <input type="checkbox" name="weekend_sunday_off" value="1" {{ ($settingsRaw['weekend_sunday_off'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Sunday Off</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 3. Organization Departments -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-tree-structure text-blue-600 dark:text-blue-400 text-lg"></i>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">3. Organization Departments ({{ count($departments) }})</h2>
                </div>
                <button type="button" @click="addDeptModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md cursor-pointer">
                    <i class="ph ph-plus text-base"></i> Add New Department
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($departments as $dept)
                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex items-center justify-between hover:border-blue-300 dark:hover:border-blue-500 transition-all">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-bold text-slate-900 dark:text-white">{{ $dept->name }}</h3>
                                <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-[10px] font-extrabold px-1.5 py-0.5 rounded">{{ $dept->code }}</span>
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Head: {{ $dept->hod_name ?? 'Super Admin' }}</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="editDept = { id: '{{ $dept->id }}', name: '{{ addslashes($dept->name) }}', code: '{{ $dept->code }}', hod_name: '{{ addslashes($dept->hod_name ?? '') }}' }; editDeptModal = true" class="p-1 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" title="Edit Department">
                                <i class="ph ph-pencil-simple text-sm"></i>
                            </button>
                            <form action="{{ route('settings.department.destroy', $dept->id) }}" method="POST" onsubmit="return confirm('Delete department {{ addslashes($dept->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition-colors" title="Delete Department">
                                    <i class="ph ph-trash text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 4. System Roles & Module Access Control Matrix -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-shield-check text-blue-600 dark:text-blue-400 text-lg"></i>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">4. System Role Module & Sub-Module Access Control</h2>
                        <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Toggle role access privileges for main modules and their sub-modules.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="addModuleModal = true" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm cursor-pointer">
                        <i class="ph ph-plus text-sm"></i> Register Module
                    </button>
                    <span class="bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-purple-200 dark:border-purple-800">RBAC Controls</span>
                </div>
            </div>

            <!-- Role Access Matrix Table grouped by Parent Module with direct ON/OFF Switches -->
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3">Module Category & Sub-Modules</th>
                            <th class="p-3 text-center text-slate-900 dark:text-white">Global Enable (ON/OFF)</th>
                            <th class="p-3 text-center text-blue-600 dark:text-blue-400">Super (Admin)</th>
                            <th class="p-3 text-center text-purple-600 dark:text-purple-400">HR Lead</th>
                            <th class="p-3 text-center text-amber-600 dark:text-amber-400">HOD / Manager</th>
                            <th class="p-3 text-center text-emerald-600 dark:text-emerald-400">Employee</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-bold text-slate-800 dark:text-slate-200">
                        @php
                            $moduleGroups = [
                                [
                                    'title' => 'Leave Management',
                                    'slug' => 'leave-management',
                                    'icon' => 'ph-calendar',
                                    'color' => 'text-blue-600 dark:text-blue-400',
                                    'master_status' => ($moduleStatuses['Leave'] ?? true) && ($moduleStatuses['Approvals'] ?? true) && ($moduleStatuses['Analytics'] ?? true),
                                    'submodules' => [
                                        'Leave' => [
                                            'name' => 'My Leaves & Calendar', 
                                            'desc' => 'Personal Leave Application & Sri Lanka Gazette Calendar',
                                            'toggle' => 'module_leave_enabled',
                                            'status' => $moduleStatuses['Leave'] ?? true
                                        ],
                                        'Approvals' => [
                                            'name' => 'Leave Approvals Workflow', 
                                            'desc' => 'Team & HR Leave Request Approvals Screen',
                                            'toggle' => 'module_approvals_enabled',
                                            'status' => $moduleStatuses['Approvals'] ?? true
                                        ],
                                        'Analytics' => [
                                            'name' => 'Reports & Leave Analytics', 
                                            'desc' => 'Organizational Leave & Attendance Reports',
                                            'toggle' => 'module_analytics_enabled',
                                            'status' => $moduleStatuses['Analytics'] ?? true
                                        ],
                                    ]
                                ],
                                [
                                    'title' => 'Employees & Workforce',
                                    'slug' => 'employees-workforce',
                                    'icon' => 'ph-users',
                                    'color' => 'text-indigo-600 dark:text-indigo-400',
                                    'master_status' => ($moduleStatuses['Employee'] ?? true) && ($moduleStatuses['Attendance'] ?? true),
                                    'submodules' => [
                                        'Employee' => [
                                            'name' => 'Employee Directory & Structure', 
                                            'desc' => 'Employee Profiles, Directory & Org Chart',
                                            'toggle' => 'module_employee_enabled',
                                            'status' => $moduleStatuses['Employee'] ?? true
                                        ],
                                        'Attendance' => [
                                            'name' => 'Attendance Tracking', 
                                            'desc' => 'Clock-In/Clock-Out & Attendance Logs',
                                            'toggle' => 'module_attendance_enabled',
                                            'status' => $moduleStatuses['Attendance'] ?? true
                                        ],
                                    ]
                                ],
                                [
                                    'title' => 'Payroll & Payslips',
                                    'slug' => 'payroll-payslips',
                                    'icon' => 'ph-file-text',
                                    'color' => 'text-emerald-600 dark:text-emerald-400',
                                    'master_status' => $moduleStatuses['Payroll'] ?? true,
                                    'submodules' => [
                                        'Payroll' => [
                                            'name' => 'Payroll & Salary Management', 
                                            'desc' => 'Salary Records, Payslips, APIT Tax & EPF/ETF',
                                            'toggle' => 'module_payroll_enabled',
                                            'status' => $moduleStatuses['Payroll'] ?? true
                                        ],
                                    ]
                                ],
                                [
                                    'title' => 'Recruitment (ATS)',
                                    'slug' => 'recruitment-ats',
                                    'icon' => 'ph-briefcase',
                                    'color' => 'text-purple-600 dark:text-purple-400',
                                    'master_status' => $moduleStatuses['Recruitment'] ?? true,
                                    'submodules' => [
                                        'Recruitment' => [
                                            'name' => 'Recruitment ATS Pipeline', 
                                            'desc' => 'Job Postings, Applicants & Candidate ATS Pipeline',
                                            'toggle' => 'module_recruitment_enabled',
                                            'status' => $moduleStatuses['Recruitment'] ?? true
                                        ],
                                    ]
                                ],
                                [
                                    'title' => 'Performance Management',
                                    'slug' => 'performance-management',
                                    'icon' => 'ph-award',
                                    'color' => 'text-amber-600 dark:text-amber-400',
                                    'master_status' => $moduleStatuses['Performance'] ?? true,
                                    'submodules' => [
                                        'Performance' => [
                                            'name' => 'Appraisal System', 
                                            'desc' => 'Performance Appraisals & Goal Tracking',
                                            'toggle' => 'module_performance_enabled',
                                            'status' => $moduleStatuses['Performance'] ?? true
                                        ],
                                    ]
                                ]
                            ];
                            $matrixRoles = ['Super Admin', 'HR Lead', 'Manager', 'Employee'];
                        @endphp

                        @foreach($moduleGroups as $group)
                            <!-- Parent Module Header Row with Alpine Master Suite Toggle -->
                            <tr x-data="{ 
                                suiteActive: {{ $group['master_status'] ? 'true' : 'false' }},
                                toggleSuite(val) {
                                    document.querySelectorAll('input[data-suite=&quot;{{ $group['slug'] }}&quot;]').forEach(cb => {
                                        cb.checked = val;
                                        cb.dispatchEvent(new Event('change'));
                                    });
                                }
                            }" class="bg-slate-100/90 dark:bg-slate-900/90 border-t border-b border-slate-200 dark:border-slate-800 font-extrabold">
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <i class="ph {{ $group['icon'] }} {{ $group['color'] }} text-base"></i>
                                        <span class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-xs">{{ $group['title'] }} Suite</span>
                                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            {{ count($group['submodules']) }} Sub-modules
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <label class="inline-flex items-center cursor-pointer select-none group" title="Toggle all sub-modules in {{ $group['title'] }}">
                                        <input type="checkbox" x-model="suiteActive" @change="toggleSuite(suiteActive)" class="sr-only peer">
                                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30 transition-all group-hover:scale-105">
                                            <div class="relative w-7 h-4 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-3"></div>
                                            <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 peer-checked:inline-block hidden uppercase tracking-wider">SUITE ON</span>
                                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 peer-checked:hidden inline-block uppercase tracking-wider">SUITE OFF</span>
                                        </div>
                                    </label>
                                </td>
                                <td colspan="4" class="p-3 text-right">
                                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Master Suite Toggle (Controls all below)</span>
                                </td>
                            </tr>

                            <!-- Sub-modules under Parent Module -->
                            @foreach($group['submodules'] as $modKey => $modInfo)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                    <td class="p-3 pl-7">
                                        <div class="flex items-start gap-2">
                                            <span class="text-slate-400 text-sm font-bold">↳</span>
                                            <div>
                                                <div class="font-extrabold text-slate-900 dark:text-white">{{ $modInfo['name'] }}</div>
                                                <div class="text-[10px] text-slate-400 font-semibold">{{ $modInfo['desc'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Global Enable ON/OFF Switch Column -->
                                    <td class="p-3 text-center">
                                        <label class="inline-flex items-center cursor-pointer select-none group" title="Toggle {{ $modInfo['name'] }}">
                                            <input type="checkbox" data-suite="{{ $group['slug'] }}" name="{{ $modInfo['toggle'] }}" value="1" {{ $modInfo['status'] ? 'checked' : '' }} class="sr-only peer">
                                            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xs peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-950/30 transition-all group-hover:scale-105">
                                                <div class="relative w-7 h-4 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-3"></div>
                                                <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 peer-checked:inline-block hidden uppercase tracking-wider">ON</span>
                                                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 peer-checked:hidden inline-block uppercase tracking-wider">OFF</span>
                                            </div>
                                        </label>
                                    </td>
                                    @foreach($matrixRoles as $rKey)
                                        @php
                                            $inputName = 'perm_' . str_replace([' ', '(', ')'], '_', $rKey) . '_' . $modKey;
                                            $isChecked = $rolePermissions[$rKey][$modKey] ?? false;
                                        @endphp
                                        <td class="p-3 text-center">
                                            <input type="checkbox" name="{{ $inputName }}" value="1" {{ $isChecked ? 'checked' : '' }} class="w-4 h-4 text-blue-600 rounded cursor-pointer">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom Action Bar for Save Settings -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                <i class="ph ph-info text-blue-600 dark:text-blue-400 text-base"></i>
                <span>Ensure module toggles and administrative settings are verified before saving.</span>
            </div>
            <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-lg shadow-blue-500/25 transition-all cursor-pointer">
                <i class="ph ph-floppy-disk text-base"></i>
                <span>Save System Configurations</span>
            </button>
        </div>
    </form>
    @endif

    <!-- Add Department Modal -->
    <div x-show="addDeptModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f172a] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-slate-800" @click.away="addDeptModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Add New Department</h3>
                <button @click="addDeptModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('settings.department.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Department Name</label>
                    <input type="text" name="name" placeholder="e.g. Finance" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold p-2.5">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Department Code</label>
                    <input type="text" name="code" placeholder="e.g. FIN" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold uppercase p-2.5">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Head of Department (HOD)</label>
                    <input type="text" name="hod_name" placeholder="e.g. John Doe" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold p-2.5">
                </div>

    <!-- Edit Department Modal -->
    <div x-show="editDeptModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f172a] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-slate-800" @click.away="editDeptModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Department</h3>
                <button @click="editDeptModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form :action="`{{ url('/settings/department') }}/${editDept.id}`" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Department Name</label>
                    <input type="text" name="name" x-model="editDept.name" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold p-2.5" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Department Code</label>
                    <input type="text" name="code" x-model="editDept.code" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold uppercase p-2.5" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Head of Department (HOD)</label>
                    <input type="text" name="hod_name" x-model="editDept.hod_name" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold p-2.5">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="editDeptModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 shadow-md">Update Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Register New Module Modal -->
    <div x-show="addModuleModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f172a] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-slate-800" @click.away="addModuleModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Register New Module</h3>
                <button @click="addModuleModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('settings.module.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Module Key Name (e.g. Training)</label>
                    <input type="text" name="module_key" placeholder="e.g. Training" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold p-2.5" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Display Title</label>
                    <input type="text" name="module_name" placeholder="e.g. Employee Training & Skill Development" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold p-2.5" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <input type="text" name="description" placeholder="Short description of module features" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-semibold p-2.5">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addModuleModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-xs font-bold rounded-xl hover:bg-purple-700 shadow-md">Register Module</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
