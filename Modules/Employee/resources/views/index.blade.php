@extends('layouts.app', ['title' => 'Loops HR - Employees Directory', 'breadcrumb' => 'Employees Directory'])

@php
    $currentRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Super (Admin)');
    $isHrOrAdmin = in_array($currentRole, ['HR Lead', 'Super (Admin)']);
@endphp

@section('content')
<div class="space-y-4" x-data="{ 
    viewMode: 'grid',
    searchQuery: '{{ addslashes($search ?? '') }}',
    selectedDept: '{{ addslashes($deptId ?? '') }}',
    employees: {{ json_encode($allEmployees ?? []) }},
    get sortedEmployees() {
        let list = this.employees;
        if (this.selectedDept && this.selectedDept !== 'all' && this.selectedDept !== '') {
            list = list.filter(e => String(e.department_id) === String(this.selectedDept));
        }
        const q = (this.searchQuery || '').trim().toLowerCase();
        if (!q) {
            return list;
        }

        const matching = list.filter(e => {
            return (e.name && e.name.toLowerCase().includes(q)) ||
                   (e.username && e.username.toLowerCase().includes(q)) ||
                   (e.emp_no && e.emp_no.toLowerCase().includes(q)) ||
                   (e.epf_no && e.epf_no.toLowerCase().includes(q)) ||
                   (e.designation && e.designation.toLowerCase().includes(q)) ||
                   (e.department && e.department.toLowerCase().includes(q));
        });

        // Realtime intelligent sorting:
        // 1. Full name starts with typed letter(s)
        // 2. Any individual word in name starts with typed letter(s)
        // 3. Substring match
        // 4. Alphabetical tie-breaker
        return matching.sort((a, b) => {
            const aName = (a.name || '').toLowerCase();
            const bName = (b.name || '').toLowerCase();

            const aStarts = aName.startsWith(q);
            const bStarts = bName.startsWith(q);
            if (aStarts && !bStarts) return -1;
            if (!aStarts && bStarts) return 1;

            const aWordStarts = aName.split(/\s+/).some(w => w.startsWith(q));
            const bWordStarts = bName.split(/\s+/).some(w => w.startsWith(q));
            if (aWordStarts && !bWordStarts) return -1;
            if (!aWordStarts && bWordStarts) return 1;

            return aName.localeCompare(bName);
        });
    }
}">

    <!-- Action Toolbar -->
    <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-3.5 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.06),0_1px_3px_0_rgba(15,23,42,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Workforce:</span>
            <span class="bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-bold px-2.5 py-0.5 rounded-full border border-blue-500/20 tabular-nums"
                  x-text="(searchQuery.trim() || (selectedDept && selectedDept !== 'all' && selectedDept !== '') ? sortedEmployees.length : {{ $totalWorkforce ?? $employees->total() }}) + ' Employees'">
                {{ $totalWorkforce ?? $employees->total() }} Employees
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Filter & Search Controls (Realtime In-Page) -->
            <div class="flex flex-wrap items-center gap-2">
                
                <!-- Department Dropdown Filter -->
                <div class="relative">
                    <select name="department_id" 
                            x-model="selectedDept"
                            class="appearance-none [background-image:none]! border border-black/[0.06] dark:border-white/[0.08] rounded-xl text-xs font-semibold bg-slate-100/90 dark:bg-white/[0.06] text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-2xs">
                        <option value="">All Departments ({{ $totalWorkforce ?? $employees->total() }})</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">
                                {{ $dept->name }} ({{ $dept->employees_count ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-300 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>

                <!-- Realtime In-Page Employee Search Input (No Dropdown) -->
                <div class="relative w-64 sm:w-72">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-300 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none z-10"></i>
                    
                    <input type="text" 
                           x-model="searchQuery"
                           placeholder="Search employee, EPF, ID..." 
                           autocomplete="off"
                           class="w-full pl-9 pr-8 py-2 border border-black/[0.06] dark:border-white/[0.08] rounded-xl text-xs font-medium bg-slate-100/90 dark:bg-white/[0.06] text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-white/[0.1] focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-all shadow-2xs">

                    <template x-if="searchQuery.trim().length > 0">
                        <button type="button" 
                                @click="searchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 cursor-pointer"
                                title="Clear search">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </template>
                </div>

                <!-- Reset Filter Button -->
                <template x-if="searchQuery.trim().length > 0 || (selectedDept && selectedDept !== 'all' && selectedDept !== '')">
                    <button type="button" 
                            @click="searchQuery = ''; selectedDept = ''"
                            class="px-2.5 py-2 text-xs font-semibold text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 flex items-center gap-1 transition-colors cursor-pointer" 
                            title="Reset all filters">
                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                        <span>Reset</span>
                    </button>
                </template>
            </div>

            <!-- View Switcher Toggle Button (Grid / List) -->
            <div class="flex items-center bg-slate-100/90 dark:bg-white/[0.06] p-1 rounded-xl border border-black/[0.04] dark:border-white/[0.06] text-xs font-semibold shadow-2xs">
                <button type="button" 
                        @click="viewMode = 'grid'" 
                        :class="viewMode === 'grid' ? 'bg-white dark:bg-white/[0.14] text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="flex items-center gap-1.5 px-3 py-1 rounded-lg transition-all cursor-pointer" 
                        title="Switch to Grid View">
                    <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                    <span>Grid</span>
                </button>
                <button type="button" 
                        @click="viewMode = 'list'" 
                        :class="viewMode === 'list' ? 'bg-white dark:bg-white/[0.14] text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="flex items-center gap-1.5 px-3 py-1 rounded-lg transition-all cursor-pointer" 
                        title="Switch to List View">
                    <i data-lucide="list" class="w-3.5 h-3.5"></i>
                    <span>List</span>
                </button>
            </div>

            <!-- Add Employee Button (Admin / HR Lead Only) -->
            @if($isHrOrAdmin)
            <a href="{{ route('employee.create') }}" class="bg-[#007AFF] hover:bg-[#0071E3] active:scale-[0.98] text-white px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-all cursor-pointer">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Add Employee Profile</span>
            </a>
            @endif
        </div>
    </div>

    <!-- GRID VIEW (5 In A Row on Desktop) -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-5 gap-3">
        <template x-for="emp in sortedEmployees" :key="emp.id">
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-3 sm:p-3.5 shadow-[0_2px_10px_-2px_rgba(15,23,42,0.06),0_1px_3px_0_rgba(15,23,42,0.04)] hover:border-[#007AFF]/50 dark:hover:border-[#0A84FF]/40 hover:-translate-y-0.5 transition-all flex flex-col justify-between space-y-2.5 group">
                <!-- Header Info Row -->
                <div class="flex items-start justify-between gap-1.5 min-w-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <template x-if="emp.avatar">
                            <img :src="emp.avatar" class="w-8 h-8 rounded-full object-cover border border-[#007AFF] shrink-0" alt="Avatar">
                        </template>
                        <template x-if="!emp.avatar">
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold text-[11px] flex items-center justify-center shrink-0 uppercase shadow-xs"
                                 x-text="emp.initials">
                            </div>
                        </template>
                        <div class="leading-tight min-w-0">
                            <h3 class="text-xs font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate" 
                                :title="emp.name" 
                                x-text="emp.name"></h3>
                            <p class="text-[10.5px] font-medium text-slate-600 dark:text-slate-300 mt-0.5 truncate">
                                <span x-text="emp.designation"></span>
                                <span class="text-slate-300 dark:text-slate-600 mx-0.5">•</span>
                                <span class="text-slate-400 dark:text-slate-400 font-normal" x-text="emp.department"></span>
                            </p>
                            <template x-if="emp.username">
                                <span class="text-slate-400 dark:text-slate-500 font-mono text-[9.5px] block truncate" 
                                      x-text="'@ ' + emp.username"></span>
                            </template>
                        </div>
                    </div>
                    <span class="bg-black/[0.04] dark:bg-white/[0.08] text-slate-700 dark:text-[#E2E8F0] border border-black/[0.04] dark:border-white/[0.06] text-[9.5px] font-mono font-semibold px-1.5 py-0.5 rounded-lg shrink-0 tabular-nums"
                          x-text="emp.emp_no">
                    </span>
                </div>

                <!-- Action Footer Bar -->
                <div class="pt-2 border-t border-black/[0.04] dark:border-white/[0.06] flex items-center justify-between text-xs font-semibold">
                    <div>
                        <template x-if="emp.can_delete">
                            <form :action="emp.delete_url" method="POST" :onsubmit="`return confirm('Delete employee profile ' + emp.name + '?')`">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-rose-500 hover:text-rose-700 flex items-center gap-1 text-[10.5px] cursor-pointer">
                                    <i class="ph ph-trash text-xs"></i> Delete
                                </button>
                            </form>
                        </template>
                    </div>

                    <div class="flex items-center gap-2">
                        <template x-if="emp.can_edit">
                            <a :href="emp.edit_url" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-0.5 text-[10.5px]">
                                <i class="ph ph-pencil-simple text-xs"></i> Edit
                            </a>
                        </template>
                        <a :href="emp.url" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 flex items-center gap-0.5 text-[10.5px] font-bold">
                            <i class="ph ph-eye text-xs"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State When No Matches Found -->
        <div x-show="sortedEmployees.length === 0" class="col-span-full py-12 text-center bg-white dark:bg-[#111927] rounded-2xl border border-dashed border-slate-200 dark:border-white/[0.1] space-y-2">
            <i class="ph ph-users text-3xl text-slate-300 dark:text-slate-600"></i>
            <h3 class="text-xs font-bold text-slate-900 dark:text-white">No employees found</h3>
            <p class="text-[11px] text-slate-400">
                <span x-show="searchQuery.trim().length > 0">No records matching "<span class="font-semibold text-slate-600 dark:text-slate-300" x-text="searchQuery"></span>".</span>
                <span x-show="searchQuery.trim().length === 0">Try adjusting your department filter.</span>
            </p>
            <button type="button" @click="searchQuery = ''; selectedDept = ''" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">Clear filters</button>
        </div>
    </div>

    <!-- LIST VIEW -->
    <div x-show="viewMode === 'list'" class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-4 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.06),0_1px_3px_0_rgba(15,23,42,0.04)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium text-slate-600 dark:text-slate-300 border-collapse">
                <thead class="bg-slate-50/70 dark:bg-white/[0.03] border-b border-black/[0.05] dark:border-white/[0.06] text-slate-400 uppercase text-[10px] font-semibold tracking-wider">
                    <tr>
                        <th class="p-3">Employee</th>
                        <th class="p-3">Employee ID</th>
                        <th class="p-3">Department & Title</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/[0.04] dark:divide-white/[0.05]">
                    <template x-for="emp in sortedEmployees" :key="emp.id">
                        <tr class="hover:bg-black/[0.02] dark:hover:bg-white/[0.03] transition-colors">
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    <template x-if="emp.avatar">
                                        <img :src="emp.avatar" class="w-7 h-7 rounded-full object-cover border border-[#007AFF]" alt="Avatar">
                                    </template>
                                    <template x-if="!emp.avatar">
                                        <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-[10px] flex items-center justify-center uppercase shadow-2xs"
                                             x-text="emp.initials">
                                        </div>
                                    </template>
                                    <div class="font-semibold text-slate-900 dark:text-white" x-text="emp.name"></div>
                                </div>
                            </td>
                            <td class="p-3 font-mono font-semibold text-blue-600 dark:text-blue-400 tabular-nums" x-text="emp.emp_no"></td>
                            <td class="p-3">
                                <span x-text="emp.designation"></span>
                                <span class="text-slate-300 dark:text-slate-600 mx-1">•</span>
                                <span class="text-slate-400" x-text="emp.department"></span>
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-2.5">
                                    <template x-if="emp.can_edit">
                                        <a :href="emp.edit_url" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white" title="Edit"><i class="ph ph-pencil-simple text-sm"></i></a>
                                    </template>
                                    <a :href="emp.url" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300" title="View Profile"><i class="ph ph-eye text-sm"></i></a>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="sortedEmployees.length === 0">
                        <td colspan="4" class="p-6 text-center text-slate-400">
                            No matching employees found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Links (Displayed when not actively searching) -->
    <div class="pt-2" x-show="!searchQuery && (!selectedDept || selectedDept === 'all' || selectedDept === '')">
        {{ $employees->links() }}
    </div>

</div>
@endsection
