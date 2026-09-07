@extends('layouts.app', ['title' => 'LOOPS HR - Employees Directory', 'breadcrumb' => 'Employees Directory'])

@php
    $currentRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Super (Admin)');
    $isHrOrAdmin = in_array($currentRole, ['HR Lead', 'Super (Admin)']);
@endphp

@section('content')
<div class="space-y-4" x-data="{ viewMode: 'grid' }">

    <!-- Compact Action Header Bar -->
    <div class="bg-white dark:bg-[#152038] rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-900/60">
                <i class="ph ph-users text-lg"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Employees Directory ({{ $employees->total() }})</h1>
                <p class="text-[11px] font-medium text-slate-400 dark:text-slate-400">Search and view company employees, department assignments, and contact details.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Inline Search Form -->
            <form action="{{ route('employee.index') }}" method="GET" class="relative">
                @if(request('department_id'))
                    <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                @endif
                <i class="ph ph-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search employee, EPF, ID..." class="pl-8 pr-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white focus:bg-white dark:focus:bg-[#1e2d4d] focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 w-52">
            </form>

            <!-- View Switcher Toggle Button (Grid / List) -->
            <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700 text-xs font-bold shadow-2xs">
                <button type="button" 
                        @click="viewMode = 'grid'" 
                        :class="viewMode === 'grid' ? 'bg-white dark:bg-blue-600 text-blue-600 dark:text-white shadow-sm font-extrabold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="flex items-center gap-1.5 px-3 py-1 rounded-lg transition-all cursor-pointer" 
                        title="Switch to Grid View">
                    <i class="ph ph-squares-four text-sm"></i>
                    <span>Grid</span>
                </button>
                <button type="button" 
                        @click="viewMode = 'list'" 
                        :class="viewMode === 'list' ? 'bg-white dark:bg-blue-600 text-blue-600 dark:text-white shadow-sm font-extrabold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="flex items-center gap-1.5 px-3 py-1 rounded-lg transition-all cursor-pointer" 
                        title="Switch to List View">
                    <i class="ph ph-list text-sm"></i>
                    <span>List</span>
                </button>
            </div>

            <!-- Add Employee Button (Admin / HR Lead Only) -->
            @if($isHrOrAdmin)
            <a href="{{ route('employee.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all">
                <i class="ph ph-user-plus text-sm"></i>
                <span>Add Employee Profile</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Department Filter Pills -->
    <div class="flex flex-wrap items-center gap-1.5">
        <a href="{{ route('employee.index', array_filter(['search' => $search])) }}" 
           class="px-3 py-1 text-[11px] font-bold rounded-full transition-all {{ !$deptId || $deptId === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-[#152038] border border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            All Staff ({{ $employees->total() }})
        </a>
        @foreach($departments as $dept)
            <a href="{{ route('employee.index', array_filter(['department_id' => $dept->id, 'search' => $search])) }}" 
               class="px-3 py-1 text-[11px] font-bold rounded-full transition-all {{ (string)$deptId === (string)$dept->id ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-[#152038] border border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                {{ $dept->name }} ({{ $dept->employees_count ?? 0 }})
            </a>
        @endforeach
    </div>

    <!-- GRID VIEW -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
        @forelse($employees as $emp)
            <div class="bg-white dark:bg-[#152038] rounded-xl border border-slate-200/80 dark:border-slate-800 p-3.5 shadow-sm hover:border-blue-300 dark:hover:border-blue-500/50 hover:shadow-md transition-all flex flex-col justify-between space-y-3 group">
                <!-- Header Info Row -->
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        @if($emp->profile_picture)
                            <img src="{{ $emp->profile_picture }}" class="w-9 h-9 rounded-full object-cover border border-blue-500 shrink-0" alt="Avatar">
                        @else
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 text-white font-black text-xs flex items-center justify-center border border-blue-400 shrink-0 uppercase shadow-xs">
                                {{ substr($emp->user->name ?? 'EM', 0, 2) }}
                            </div>
                        @endif
                        <div class="leading-tight">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $emp->title ?? 'Mr.' }} {{ $emp->user->name ?? 'Employee' }}</h3>
                            <p class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 mt-0.5">
                                {{ $emp->designation->name ?? 'Staff' }} <span class="text-slate-300 dark:text-slate-700 mx-0.5">•</span> <span class="text-slate-400 dark:text-slate-400 font-normal">{{ $emp->department->name ?? 'General' }}</span>
                                @if($emp->user->username)
                                    <span class="text-slate-400 dark:text-slate-500 font-mono text-[10px] block">@ {{ $emp->user->username }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/60 text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0">
                        {{ $emp->employee_id_number }}
                    </span>
                </div>

                <!-- Action Footer Bar -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-bold">
                    <div>
                        @if($isHrOrAdmin)
                            <form action="{{ route('employee.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Delete employee profile?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-500 hover:text-rose-700 flex items-center gap-1 text-[11px]">
                                    <i class="ph ph-trash text-xs"></i> Delete
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        @if($isHrOrAdmin || Auth::id() === $emp->user_id)
                            <a href="{{ route('employee.edit', $emp->id) }}" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1 text-[11px]">
                                <i class="ph ph-pencil-simple text-xs"></i> Edit
                            </a>
                        @endif
                        <a href="{{ route('employee.show', $emp->id) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 flex items-center gap-1 text-[11px] font-extrabold">
                            <i class="ph ph-eye text-xs"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-8 text-center bg-white dark:bg-[#152038] rounded-xl border border-slate-200/80 dark:border-slate-800">
                <i class="ph ph-users text-3xl text-slate-300 dark:text-slate-600 mb-1"></i>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">No employees found</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Try adjusting your search query or department filter.</p>
            </div>
        @endforelse
    </div>

    <!-- LIST VIEW -->
    <div x-show="viewMode === 'list'" class="bg-white dark:bg-[#152038] rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="p-2.5">Employee</th>
                        <th class="p-2.5">Employee ID</th>
                        <th class="p-2.5">Department & Title</th>
                        <th class="p-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($employees as $emp)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors">
                            <td class="p-2.5">
                                <div class="flex items-center gap-2">
                                    @if($emp->profile_picture)
                                        <img src="{{ $emp->profile_picture }}" class="w-7 h-7 rounded-full object-cover border border-blue-500" alt="Avatar">
                                    @else
                                        <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-[10px] flex items-center justify-center">
                                            {{ substr($emp->user->name ?? 'EM', 0, 2) }}
                                        </div>
                                    @endif
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $emp->title ?? 'Mr.' }} {{ $emp->user->name ?? 'Employee' }}</div>
                                </div>
                            </td>
                            <td class="p-2.5 font-bold text-blue-600 dark:text-blue-400">{{ $emp->employee_id_number }}</td>
                            <td class="p-2.5">{{ $emp->designation->name ?? 'Staff' }} • <span class="text-slate-400">{{ $emp->department->name ?? 'General' }}</span></td>
                            <td class="p-2.5 text-right">
                                <div class="flex items-center justify-end gap-2.5">
                                    @if($isHrOrAdmin || Auth::id() === $emp->user_id)
                                        <a href="{{ route('employee.edit', $emp->id) }}" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white" title="Edit"><i class="ph ph-pencil-simple text-sm"></i></a>
                                    @endif
                                    <a href="{{ route('employee.show', $emp->id) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300" title="View Profile"><i class="ph ph-eye text-sm"></i></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Links -->
    <div class="pt-2">
        {{ $employees->links() }}
    </div>

</div>
@endsection
