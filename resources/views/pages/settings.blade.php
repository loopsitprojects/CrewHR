@extends('layouts.app', [
    'title' => 'Loops HR - ' . ($modulesList[$activeModule]['title'] ?? 'Settings'), 
    'breadcrumb' => 'Settings • ' . ($modulesList[$activeModule]['title'] ?? 'General')
])

@php
    $userSystemRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Employee');
    $isAdminOrHr = in_array($userSystemRole, ['HR Lead', 'Super (Admin)', 'Super Admin']);
    $activeUser = Auth::user() ?? $user;
    $activeEmp = $employee ?? ($activeUser ? \Modules\Employee\Models\Employee::where('user_id', $activeUser->id)->first() : null);
@endphp

@section('content')
<div class="space-y-6" x-data="{ 
    addDeptModal: false, 
    editDeptModal: false, 
    addModuleModal: false, 
    editModuleModal: false,
    addLeaveTypeModal: false,
    editLeaveTypeModal: false,
    editDept: { id: '', name: '', code: '', hod_name: '' },
    editLeaveType: { id: '', name: '', code: '', days: 0, is_paid: 1, is_active: 1, description: '' },
    editModule: { key: '{{ $activeModule }}', title: '{{ addslashes($modulesList[$activeModule]['title'] ?? '') }}', desc: '{{ addslashes($modulesList[$activeModule]['desc'] ?? '') }}', badge: '{{ addslashes($modulesList[$activeModule]['badge'] ?? '') }}' }
}">

    <!-- Flash Status Messages -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-emerald-800 dark:text-emerald-300 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-base text-emerald-600 dark:text-emerald-400"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75"><i class="ph ph-x text-sm"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="ph ph-warning-circle text-base text-rose-600 dark:text-rose-400"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75"><i class="ph ph-x text-sm"></i></button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 rounded-2xl text-xs font-bold space-y-1 shadow-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="ph ph-warning-circle text-base text-rose-600 dark:text-rose-400"></i>
                    <span>Please correct the following errors:</span>
                </div>
                <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75"><i class="ph ph-x text-sm"></i></button>
            </div>
            <ul class="list-disc list-inside pl-4 text-[11px] font-medium space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ACTIVE SETTING PAGE HEADER -->
    <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-4 sm:p-5 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.06),0_1px_3px_0_rgba(15,23,42,0.04)] flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all">
        <div class="flex items-center gap-3.5 min-w-0">
            <div class="w-11 h-11 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center shrink-0 shadow-2xs">
                <i class="ph {{ $modulesList[$activeModule]['icon'] ?? 'ph-gear' }} text-xl font-bold"></i>
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-tight truncate">
                        {{ $modulesList[$activeModule]['title'] ?? 'Settings' }}
                    </h1>
                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800 uppercase tracking-wide shrink-0">
                        {{ $modulesList[$activeModule]['badge'] ?? 'Configuration' }}
                    </span>
                    @if($isAdminOrHr && $activeModule !== 'profile')
                        <button type="button" 
                                @click.stop="editModule = { key: '{{ $activeModule }}', title: '{{ addslashes($modulesList[$activeModule]['title'] ?? '') }}', desc: '{{ addslashes($modulesList[$activeModule]['desc'] ?? '') }}', badge: '{{ addslashes($modulesList[$activeModule]['badge'] ?? '') }}' }; editModuleModal = true" 
                                class="p-1 rounded-md text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" 
                                title="Rename / Customize Module Label">
                            <i class="ph ph-pencil-simple text-xs"></i>
                        </button>
                    @endif
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-normal truncate">
                    {{ $modulesList[$activeModule]['desc'] ?? 'Module system configurations and settings' }}
                </p>
            </div>
        </div>

        @if($isAdminOrHr)
        <!-- Fast Switcher Dropdown -->
        <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 hidden md:inline">Jump to:</span>
            <div class="relative">
                <select onchange="window.location.href=this.value" 
                        class="appearance-none [background-image:none]! bg-slate-100/90 dark:bg-white/[0.06] border border-slate-200/80 dark:border-white/[0.08] rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-2xs">
                    @foreach($modulesList as $mKey => $mInfo)
                        @if($isAdminOrHr || $mKey === 'profile')
                            <option value="{{ route('settings.index', ['module' => $mKey]) }}" {{ $activeModule === $mKey ? 'selected' : '' }}>
                                {{ $mInfo['title'] }} ({{ $mInfo['badge'] }})
                            </option>
                        @endif
                    @endforeach
                </select>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
        </div>
        @endif
    </div>

    <!-- ACTIVE MODULE DEDICATED SETTINGS CONTENT -->
    <div class="space-y-6">
        @if(view()->exists('pages.settings.' . $activeModule))
            @include('pages.settings.' . $activeModule)
        @else
            @include('pages.settings.general')
        @endif
    </div>

    <!-- Add Department Modal -->
    <div x-show="addDeptModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f172a] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-slate-800" @click.away="addDeptModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Add New Department</h3>
                <button @click="addDeptModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('settings.department.store') }}" method="POST" class="space-y-4 text-xs font-semibold">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Department Name *</label>
                    <input type="text" name="name" placeholder="e.g. Engineering & IT" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Department Code *</label>
                    <input type="text" name="code" placeholder="e.g. ENG" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold uppercase p-2.5" required>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Head of Department (HOD)</label>
                    <input type="text" name="hod_name" placeholder="e.g. Supun Perera" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Department functions and responsibilities..." class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addDeptModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 shadow-md">Add Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div x-show="editDeptModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f172a] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-slate-800" @click.away="editDeptModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Department</h3>
                <button @click="editDeptModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form :action="`{{ url('/settings/department') }}/${editDept.id}`" method="POST" class="space-y-4 text-xs font-semibold">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Department Name *</label>
                    <input type="text" name="name" x-model="editDept.name" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Department Code *</label>
                    <input type="text" name="code" x-model="editDept.code" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold uppercase p-2.5" required>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Head of Department (HOD)</label>
                    <input type="text" name="hod_name" x-model="editDept.hod_name" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="editDeptModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 shadow-md">Update Department</button>
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

            <form action="{{ route('settings.module.store') }}" method="POST" class="space-y-4 text-xs font-semibold">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Module Key Name *</label>
                    <input type="text" name="module_key" placeholder="e.g. Training" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Display Title *</label>
                    <input type="text" name="module_name" placeholder="e.g. Employee Training & Skill Development" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <input type="text" name="description" placeholder="Short description of module features" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addModuleModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 text-white text-xs font-bold rounded-xl hover:bg-purple-700 shadow-md">Register Module</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Leave Type Modal -->
    <div x-show="addLeaveTypeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f172a] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-slate-800" @click.away="addLeaveTypeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="ph ph-plus-circle text-base"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Add New Leave Type</h3>
                </div>
                <button @click="addLeaveTypeModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('settings.leave_type.store') }}" method="POST" class="space-y-4 text-xs font-semibold">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Leave Type Name *</label>
                    <input type="text" name="name" placeholder="e.g. Study / Examination Leave" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Leave Code *</label>
                        <input type="text" name="code" placeholder="e.g. STUDY" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold uppercase p-2.5" required>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Annual Quota (Days) *</label>
                        <input type="number" name="days" value="5" min="0" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Eligibility rules and conditions for this leave type..." class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-[#1e2d4d] rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_paid" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-slate-900 dark:text-white">Paid Leave</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-[#1e2d4d] rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-slate-900 dark:text-white">Active (Enabled)</span>
                    </label>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addLeaveTypeModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 shadow-md">Create Leave Type</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Leave Type Modal -->
    <div x-show="editLeaveTypeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f172a] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-slate-800" @click.away="editLeaveTypeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="ph ph-pencil-simple text-base"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Leave Type</h3>
                </div>
                <button @click="editLeaveTypeModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form :action="`{{ url('/settings/leave-type') }}/${editLeaveType.id}`" method="POST" class="space-y-4 text-xs font-semibold">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Leave Type Name *</label>
                    <input type="text" name="name" x-model="editLeaveType.name" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Leave Code *</label>
                        <input type="text" name="code" x-model="editLeaveType.code" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold uppercase p-2.5" required>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Annual Quota (Days) *</label>
                        <input type="number" name="days" x-model="editLeaveType.days" min="0" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" x-model="editLeaveType.description" rows="2" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-[#1e2d4d] rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_paid" value="1" :checked="editLeaveType.is_paid" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-slate-900 dark:text-white">Paid Leave</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-[#1e2d4d] rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editLeaveType.is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-slate-900 dark:text-white">Active (Enabled)</span>
                    </label>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="editLeaveTypeModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 shadow-md">Update Leave Type</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit / Rename Module Modal -->
    <div x-show="editModuleModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f172a] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-slate-800" @click.away="editModuleModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <i class="ph ph-pencil-simple text-base"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Rename / Edit Module</h3>
                </div>
                <button @click="editModuleModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form :action="`{{ url('/settings/module') }}/${editModule.key}`" method="POST" class="space-y-4 text-xs font-semibold">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Display Title *</label>
                    <input type="text" name="module_title" x-model="editModule.title" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" required>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Badge / Tag Label</label>
                    <input type="text" name="module_badge" x-model="editModule.badge" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description & Subtitle</label>
                    <textarea name="module_desc" x-model="editModule.desc" rows="2" class="w-full border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="editModuleModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 shadow-md">Update Module Name</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
