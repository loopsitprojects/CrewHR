@extends('layouts.app', ['title' => 'LOOPS HR - Module Settings Hub', 'breadcrumb' => 'Settings'])

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
    editModule: { key: '', title: '', desc: '', badge: '' }
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

    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                    <i class="ph ph-sliders text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $isAdminOrHr ? 'Super Admin & Module Settings Hub' : 'My Account & Security Settings' }}
                    </h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/60">
                            Role: {{ $userSystemRole }}
                        </span>
                        <span class="text-xs font-bold text-slate-400 dark:text-slate-500">•</span>
                        <span class="text-xs font-black text-slate-700 dark:text-slate-300">
                            {{ $modulesList[$activeModule]['title'] ?? 'Module Settings' }}
                        </span>
                    </div>
                </div>
            </div>
            <p class="text-xs font-semibold text-slate-400 dark:text-slate-400 mt-2">
                {{ $modulesList[$activeModule]['desc'] ?? 'Configure module parameters, business rules, and permissions.' }}
            </p>
        </div>
    </div>

    <!-- MODULE SELECTOR CARDS GRID -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-3">
        <div class="px-1 flex items-center justify-between">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                {{ $isAdminOrHr ? 'System Modules & Settings' : 'Personal Settings' }}
            </span>
            @if($isAdminOrHr)
                <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/60 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold">
                    {{ count($modulesList) }} Modules Available
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            @foreach($modulesList as $modKey => $modInfo)
                @if($isAdminOrHr || $modKey === 'profile')
                    <div class="group relative flex flex-col justify-between p-3.5 rounded-xl border transition-all duration-200 {{ $activeModule === $modKey ? 'bg-gradient-to-br from-blue-600 to-indigo-600 text-white border-transparent shadow-md shadow-blue-500/25 ring-2 ring-blue-500/50' : 'bg-slate-50/70 dark:bg-[#1a2642] border-slate-200/70 dark:border-slate-800/80 hover:border-blue-300 dark:hover:border-blue-700 hover:bg-white dark:hover:bg-[#1e2d4d] text-slate-700 dark:text-slate-200 shadow-xs' }}">
                        
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <a href="{{ route('settings.index', ['module' => $modKey]) }}" class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $activeModule === $modKey ? 'bg-white/20 text-white' : 'bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 ' . $modInfo['color'] }}">
                                <i class="ph {{ $modInfo['icon'] }} text-base"></i>
                            </a>
                            
                            <div class="flex items-center gap-1">
                                @if($isAdminOrHr && $modKey !== 'profile')
                                    <button type="button" 
                                            @click.stop="editModule = { key: '{{ $modKey }}', title: '{{ addslashes($modInfo['title']) }}', desc: '{{ addslashes($modInfo['desc']) }}', badge: '{{ addslashes($modInfo['badge']) }}' }; editModuleModal = true" 
                                            class="p-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity {{ $activeModule === $modKey ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800' }} cursor-pointer" 
                                            title="Rename / Edit Module Label">
                                        <i class="ph ph-pencil-simple text-xs"></i>
                                    </button>
                                @endif
                                <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full shrink-0 {{ $activeModule === $modKey ? 'bg-white/20 text-white' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700' }}">
                                    {{ $modInfo['badge'] }}
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('settings.index', ['module' => $modKey]) }}" class="space-y-0.5 block flex-1">
                            <div class="text-xs font-black truncate {{ $activeModule === $modKey ? 'text-white' : 'text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400' }}">
                                {{ $modInfo['title'] }}
                            </div>
                            <div class="text-[10px] line-clamp-1 leading-snug {{ $activeModule === $modKey ? 'text-blue-100' : 'text-slate-400' }}" title="{{ $modInfo['desc'] }}">
                                {{ $modInfo['desc'] }}
                            </div>
                        </a>

                        @if($activeModule === $modKey)
                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-8 h-1 bg-white rounded-full"></div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
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
                    <button type="submit" class="px-5 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 shadow-md">Create Leave Type</button>
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
                    <button type="submit" class="px-5 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 shadow-md">Update Leave Type</button>
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
