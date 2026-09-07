@extends('layouts.app', ['title' => 'LOOPS HR - Employee Profile Details'])

@section('breadcrumbs')
    <span class="text-slate-300">/</span>
    <a href="{{ route('employee.index') }}" class="text-slate-500 hover:text-blue-600 transition-colors">Employees Directory</a>
    <span class="text-slate-300">/</span>
    <span class="text-blue-600 font-black">{{ $employee->user->name ?? 'Profile Details' }}</span>
@endsection

@php
    $currentRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Super (Admin)');
    $isHrOrAdmin = in_array($currentRole, ['HR Lead', 'Super (Admin)']);
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Top Action Bar -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('employee.index') }}" class="w-9 h-9 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center shadow-sm">
                <i class="ph ph-arrow-left text-lg"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-gray-900">{{ $employee->title ?? 'Mr.' }} {{ $employee->user->name ?? 'Employee' }}</h1>
                    <span class="bg-blue-50 text-blue-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-blue-200">{{ $employee->employee_id_number }}</span>
                    @if($employee->user->username)
                        <span class="bg-gray-100 text-gray-700 text-xs font-mono font-bold px-2.5 py-0.5 rounded-full border border-gray-200">@ {{ $employee->user->username }}</span>
                    @endif
                </div>
                <p class="text-xs font-bold text-gray-500 mt-0.5">{{ $employee->designation->name ?? 'Staff' }} • <span class="text-blue-600">{{ $employee->department->name ?? 'General' }}</span></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($isHrOrAdmin || Auth::id() === $employee->user_id)
            <a href="{{ route('employee.edit', $employee->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all">
                <i class="ph ph-pencil-simple text-base"></i>
                <span>Edit Profile</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Main Profile Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Left Column: Personal Card -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-5">
            <div class="flex flex-col items-center text-center pb-4 border-b border-gray-100">
                <img src="{{ $employee->profile_picture ?? ('https://ui-avatars.com/api/?name=' . urlencode($employee->user->name ?? 'Employee') . '&background=0D8ABC&color=fff') }}" class="w-24 h-24 rounded-full object-cover border-4 border-blue-500 shadow-md mb-3" alt="Avatar">
                <h2 class="text-lg font-black text-gray-900">{{ $employee->title ?? 'Mr.' }} {{ $employee->user->name ?? 'Employee' }}</h2>
                <span class="bg-purple-50 text-purple-700 text-xs font-extrabold px-3 py-1 rounded-full mt-1 border border-purple-200">{{ $employee->system_role ?? 'Employee' }}</span>
            </div>

            <div class="space-y-3 text-xs font-semibold text-gray-600">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Username</span>
                    <span class="font-bold text-blue-600 font-mono bg-blue-50 px-2 py-0.5 rounded border border-blue-100">@<span>{{ $employee->user->username ?? 'N/A' }}</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">NIC / Passport</span>
                    <span class="font-bold text-gray-900">{{ $employee->nic_passport ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Date of Birth</span>
                    <span class="font-bold text-gray-900">{{ $employee->date_of_birth ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Email</span>
                    <span class="font-bold text-blue-600">{{ $employee->user->email ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Phone</span>
                    <span class="font-bold text-gray-900">{{ $employee->phone_number ?? 'N/A' }}</span>
                </div>
                <div class="pt-2 border-t border-gray-100">
                    <span class="text-gray-400 block text-[10px] uppercase font-bold mb-1">Emergency Contact</span>
                    <p class="font-bold text-gray-800 text-[11px]">{{ $employee->emergency_contact ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Direct Subordinates -->
            @if($employee->subordinates && $employee->subordinates->count() > 0)
                <div class="pt-3 border-t border-gray-100 space-y-2">
                    <span class="text-xs font-black text-gray-900 uppercase tracking-wider block">Direct Team ({{ $employee->subordinates->count() }})</span>
                    <div class="space-y-1.5">
                        @foreach($employee->subordinates as $sub)
                            <a href="{{ route('employee.show', $sub->id) }}" class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded-xl transition-all">
                                <img src="{{ $sub->profile_picture ?? ('https://ui-avatars.com/api/?name=' . urlencode($sub->user->name ?? 'Employee') . '&background=0D8ABC&color=fff') }}" class="w-6 h-6 rounded-full" alt="Avatar">
                                <span class="text-xs font-bold text-gray-800 hover:text-blue-600">{{ $sub->user->name ?? 'Subordinate' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Middle & Right Columns -->
        <div class="md:col-span-2 space-y-6">

            <!-- Employment Details -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-black text-blue-600 uppercase tracking-wider border-b border-gray-100 pb-2">Employment & Role Details</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-semibold">
                    <div>
                        <span class="text-gray-400 block text-[10px]">EPF Registration No.</span>
                        <span class="font-bold text-gray-900">{{ $employee->epf_registration_no ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Job Category</span>
                        <span class="font-bold text-gray-900">{{ $employee->job_category ?? 'Full Time (Permanent)' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Joined Date</span>
                        <span class="font-bold text-gray-900">{{ $employee->joined_date ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Reporting Person</span>
                        <span class="font-bold text-blue-600">{{ $employee->reportingPerson->user->name ?? 'None' }}</span>
                    </div>
                </div>

                @if($employee->increment_amount > 0 || $employee->promotion_designation)
                    <div class="bg-emerald-50/70 border border-emerald-200/80 p-3.5 rounded-xl space-y-1.5 text-xs font-semibold text-emerald-900">
                        <div class="flex items-center gap-2 font-black uppercase text-[10px] text-emerald-800">
                            <i class="ph ph-trend-up text-base text-emerald-600"></i> Career Growth & Promotion Summary
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 pt-1 text-[11px]">
                            <div>Increment: <strong class="text-emerald-900">+ Rs. {{ number_format($employee->increment_amount) }}</strong></div>
                            <div>Designation: <strong class="text-emerald-900">{{ $employee->promotion_designation ?? 'N/A' }}</strong></div>
                            <div>Effective Date: <strong class="text-emerald-900">{{ $employee->promotion_date ?? 'N/A' }}</strong></div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Leave Quotas Summary -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <h3 class="text-xs font-black text-blue-600 uppercase tracking-wider">Leave Quota Balances</h3>
                    <span class="text-[10px] text-gray-400 font-bold">Annual Quota Tracker</span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                    @forelse($employee->leaveBalances as $bal)
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-200/80 space-y-1">
                            <span class="text-[10px] text-gray-500 font-bold uppercase block">{{ $bal->leaveType->name ?? 'Leave' }}</span>
                            <div class="flex items-baseline justify-between">
                                <span class="text-base font-black text-emerald-600">{{ $bal->allocated - $bal->used }} <span class="text-[10px] text-gray-400 font-normal">left</span></span>
                                <span class="text-[10px] text-gray-400 font-semibold">{{ $bal->used }}/{{ $bal->allocated }} used</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-xs text-gray-400 italic py-2">
                            No leave quotas generated yet. Edit the employee profile to update balances.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Financial & Payroll Details (HR/Admin or Self Only) -->
            @if($isHrOrAdmin || Auth::id() === $employee->user_id)
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-black text-blue-600 uppercase tracking-wider border-b border-gray-100 pb-2">Compensation & Statutory Payroll</h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs font-bold">
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-200">
                        <span class="text-[10px] text-gray-400 block">Basic Salary</span>
                        <span class="text-gray-900">Rs. {{ number_format($employee->basic_salary) }}</span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-200">
                        <span class="text-[10px] text-gray-400 block">Fixed Allowance</span>
                        <span class="text-gray-900">Rs. {{ number_format($employee->fixed_allowance) }}</span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-200">
                        <span class="text-[10px] text-gray-400 block">Other Allowance</span>
                        <span class="text-gray-900">Rs. {{ number_format($employee->other_allowance) }}</span>
                    </div>
                    <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                        <span class="text-[10px] text-emerald-700 block">Gross Salary</span>
                        <span class="text-emerald-700 font-black">Rs. {{ number_format($employee->gross_salary) }}</span>
                    </div>
                </div>

                <!-- Statutory Deductions Cards -->
                <div class="bg-blue-50/40 p-4 rounded-xl border border-blue-100 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs font-bold">
                    <div>
                        <span class="text-[10px] text-gray-400 block">EPF (Employee 8%)</span>
                        <span class="text-rose-600 font-black">- Rs. {{ number_format($employee->epf_employee) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 block">EPF (Employer 12%)</span>
                        <span class="text-blue-600 font-black">Rs. {{ number_format($employee->epf_employer) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 block">ETF (Employer 3%)</span>
                        <span class="text-blue-600 font-black">Rs. {{ number_format($employee->etf_employer) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 block">Estimated Net Take-Home</span>
                        <span class="text-emerald-600 font-black text-sm">Rs. {{ number_format($employee->net_take_home) }}</span>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs font-semibold">
                    <div>
                        <span class="text-gray-400 block text-[10px]">Bank Name</span>
                        <span class="font-bold text-gray-900">{{ $employee->bank_name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Branch</span>
                        <span class="font-bold text-gray-900">{{ $employee->bank_branch ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Account No</span>
                        <span class="font-bold text-gray-900">{{ $employee->account_number ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Account Holder</span>
                        <span class="font-bold text-gray-900">{{ $employee->account_holder_name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Qualifications -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-black text-blue-600 uppercase tracking-wider border-b border-gray-100 pb-2">Qualifications</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-medium">
                    <div class="bg-blue-50/50 p-3.5 rounded-xl border border-blue-100">
                        <span class="font-bold text-blue-900 block mb-1">Higher Education</span>
                        <p class="text-gray-700">{{ $employee->higher_education ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-purple-50/50 p-3.5 rounded-xl border border-purple-100">
                        <span class="font-bold text-purple-900 block mb-1">Professional Qualifications</span>
                        <p class="text-gray-700">{{ $employee->professional_qualifications ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
