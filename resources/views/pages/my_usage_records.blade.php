@extends('layouts.app', ['title' => 'Loops HR - Usage Records', 'breadcrumb' => 'Usage Records'])

@section('header_left')
    <div class="h-5 w-px bg-slate-200 dark:bg-white/10 hidden sm:block shrink-0"></div>
    <div class="flex items-center gap-2 min-w-0">
        <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-none truncate">
            @if($scope === 'employee' && $targetEmployee)
                {{ $targetEmployee->user->name ?? $targetEmployee->first_name }}'s Usage Records
            @elseif($scope === 'all')
                All Employee Usage Records
            @elseif($scope === 'team')
                My Team Usage Records
            @else
                My Usage Records
            @endif
        </h1>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 tracking-wide uppercase tabular-nums shrink-0">
            {{ $year }} Leave Cycle
        </span>
    </div>
@endsection

@section('content')
<div class="space-y-6 pb-16" 
     x-data="{
        applyModalOpen: false,
        selectedLeaveTypeId: '{{ $activeLeaveTypes->first()->id ?? 1 }}',
        selectedLeaveTypeCode: '{{ strtoupper($activeLeaveTypes->first()->code ?? 'ANNUAL') }}',
        startDate: '',
        endDate: '',
        isHalfDay: false,
        halfDaySlot: 'Morning',
        isShortLeave: false,
        shortLeaveSlot: '08:30 AM – 10:00 AM',
        coveringEmployeeId: '',
        reason: '',
        leaveQuotas: {{ json_encode(collect($summaryCards)->keyBy('id')) }},
        
        openApplyModal(leaveTypeId = null, leaveCode = null) {
            if (leaveTypeId) {
                this.selectedLeaveTypeId = leaveTypeId;
                this.selectedLeaveTypeCode = (leaveCode || '').toUpperCase();
            }
            this.applyModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        getCurrentQuota() {
            return this.leaveQuotas[this.selectedLeaveTypeId] || null;
        },

        calculateEstimatedDuration() {
            if (this.selectedLeaveTypeCode === 'SHORT' || this.isShortLeave) {
                return 0.2; // 1 slot
            }
            if (this.isHalfDay) {
                return 0.5;
            }
            if (!this.startDate) return 1;
            if (!this.endDate || this.endDate === this.startDate) return 1;
            
            const start = new Date(this.startDate);
            const end = new Date(this.endDate);
            if (end < start) return 0;
            
            let count = 0;
            let cur = new Date(start);
            while (cur <= end) {
                const day = cur.getDay();
                if (day !== 0 && day !== 6) {
                    count++;
                }
                cur.setDate(cur.getDate() + 1);
            }
            return Math.max(1, count);
        },

        getProjectedBalance() {
            const quota = this.getCurrentQuota();
            if (!quota) return 0;
            const currentBal = parseFloat(quota.balance_num) || 0;
            const duration = this.calculateEstimatedDuration();
            return Math.max(0, (currentBal - duration).toFixed(1));
        }
     }">

    @if(!$isEmployee)
        <!-- ============================================================== -->
        <!-- ROLE SCOPE SWITCHER & SELECTION TOOLBAR (Admin / Manager only) -->
        <!-- ============================================================== -->
        <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-3 shadow-xs flex flex-wrap items-center justify-between gap-3 transition-all">
            
            <!-- Left: Segmented Scope Switcher (Apple HIG Segmented Control) -->
            <div class="inline-flex items-center gap-1 bg-slate-100/90 dark:bg-white/[0.06] p-1 rounded-xl border border-slate-200/80 dark:border-white/[0.08] shadow-2xs">
                <!-- My Usage Button -->
                <a href="{{ route('my-usage-records.index', array_filter(['scope' => 'my', 'year' => $year, 'month' => $month, 'status' => $status])) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs transition-all duration-200 ease-out cursor-pointer active:scale-[0.98] {{ $scope === 'my' ? 'bg-white dark:bg-white/[0.16] text-slate-900 dark:text-white font-semibold shadow-[0_1px_3px_rgba(0,0,0,0.08),0_1px_2px_rgba(0,0,0,0.04)] border border-slate-200/60 dark:border-white/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-black/[0.03] dark:hover:bg-white/[0.04]' }}">
                    <i data-lucide="user" class="w-3.5 h-3.5 {{ $scope === 'my' ? 'text-[#007AFF] dark:text-[#0A84FF]' : 'text-slate-400 dark:text-slate-500' }}"></i>
                    <span>My Usage</span>
                </a>

                <!-- Team / All Employees Usage Button -->
                @php
                    $teamScope = ($isSuperAdmin || $isHrLead) ? 'all' : 'team';
                    $isTeamActive = $scope === 'team' || ($scope === 'all' && ($isSuperAdmin || $isHrLead));
                @endphp
                <a href="{{ route('my-usage-records.index', array_filter(['scope' => $teamScope, 'year' => $year, 'month' => $month, 'status' => $status, 'department_id' => $departmentId])) }}"
                   title="{{ $isManager ? 'My Team Usage' : 'All Employees Usage' }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs transition-all duration-200 ease-out cursor-pointer active:scale-[0.98] {{ $isTeamActive ? 'bg-white dark:bg-white/[0.16] text-slate-900 dark:text-white font-semibold shadow-[0_1px_3px_rgba(0,0,0,0.08),0_1px_2px_rgba(0,0,0,0.04)] border border-slate-200/60 dark:border-white/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-black/[0.03] dark:hover:bg-white/[0.04]' }}">
                    <i data-lucide="users" class="w-3.5 h-3.5 {{ $isTeamActive ? 'text-[#007AFF] dark:text-[#0A84FF]' : 'text-slate-400 dark:text-slate-500' }}"></i>
                    <span>{{ $isManager ? 'My Team Usage' : 'All Employees Usage' }}</span>
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full tabular-nums {{ $isTeamActive ? 'bg-slate-100 dark:bg-white/15 text-slate-700 dark:text-slate-200' : 'bg-slate-200/60 dark:bg-white/10 text-slate-500 dark:text-slate-400' }}">{{ $selectableEmployees->count() }}</span>
                </a>
            </div>

            <!-- Right: Searchable Combobox & Department Filter -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Inspect Employee Searchable Combobox -->
                <div x-data="{
                        open: false,
                        query: '',
                        employees: {{ json_encode($selectableEmployees->map(fn($e) => [
                            'id' => $e->id,
                            'name' => $e->user->name ?? ($e->first_name . ' ' . $e->last_name),
                            'emp_id' => $e->employee_id_number ?? ('EMP-' . $e->id),
                            'department' => $e->department->name ?? 'Staff',
                            'initial' => strtoupper(substr($e->user->name ?? $e->first_name ?? 'E', 0, 1)),
                            'url' => route('my-usage-records.index', array_filter([
                                'scope' => $scope === 'my' ? ($isManager ? 'team' : 'all') : $scope,
                                'employee_id' => $e->id,
                                'year' => $year,
                                'month' => $month,
                                'department_id' => $departmentId,
                                'status' => $status
                            ]))
                        ])) }},
                        get filtered() {
                            if (!this.query.trim()) return this.employees;
                            const q = this.query.toLowerCase().trim();
                            return this.employees.filter(e => 
                                e.name.toLowerCase().includes(q) || 
                                e.emp_id.toLowerCase().includes(q) ||
                                e.department.toLowerCase().includes(q)
                            );
                        }
                    }" 
                    @click.away="open = false" 
                    class="relative">
                    
                    <div class="relative flex items-center">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 pointer-events-none"></i>
                        <input type="text" 
                               x-model="query" 
                               @focus="open = true" 
                               @click="open = true"
                               @keydown.escape="open = false"
                               placeholder="Inspect Employee..." 
                               value="{{ $targetEmployee && $scope !== 'my' ? ($targetEmployee->user->name ?? $targetEmployee->first_name) . ' (' . ($targetEmployee->employee_id_number ?? 'EMP-' . $targetEmployee->id) . ')' : '' }}"
                               class="appearance-none bg-slate-100/90 dark:bg-white/[0.06] border {{ $targetEmployee && $scope !== 'my' ? 'border-[#007AFF] dark:border-[#0A84FF] ring-2 ring-[#007AFF]/20 font-bold' : 'border-slate-200/80 dark:border-white/[0.08]' }} rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 py-1.5 pl-7 pr-6 w-44 sm:w-52 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-2xs transition-all">
                        <button type="button" @click="open = !open" class="absolute right-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                    </div>

                    <!-- Dropdown Panel -->
                    <div x-show="open" 
                         x-cloak 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 top-full mt-1.5 w-80 max-h-72 bg-white dark:bg-[#161F32] border border-slate-200/90 dark:border-white/[0.1] rounded-2xl shadow-xl z-50 overflow-hidden flex flex-col">
                        
                        @if($isSuperAdmin || $isHrLead)
                        <a href="{{ route('my-usage-records.index', array_filter(['scope' => 'all', 'year' => $year, 'month' => $month, 'department_id' => $departmentId, 'status' => $status])) }}"
                           class="px-3.5 py-2 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-500/10 border-b border-black/[0.05] dark:border-white/[0.06] flex items-center justify-between transition-colors {{ $scope === 'all' ? 'bg-blue-500/10' : '' }}">
                            <div class="flex items-center gap-2">
                                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                <span>All Employees Usage (Overview)</span>
                            </div>
                            <span class="text-[10px] bg-blue-500/15 text-blue-600 dark:text-blue-300 px-1.5 py-0.2 rounded font-bold tabular-nums">{{ $selectableEmployees->count() }}</span>
                        </a>
                        @endif

                        @if($isManager)
                        <a href="{{ route('my-usage-records.index', array_filter(['scope' => 'team', 'year' => $year, 'month' => $month, 'status' => $status])) }}"
                           class="px-3.5 py-2 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-500/10 border-b border-black/[0.05] dark:border-white/[0.06] flex items-center justify-between transition-colors {{ $scope === 'team' ? 'bg-blue-500/10' : '' }}">
                            <div class="flex items-center gap-2">
                                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                <span>My Team Usage (Overview)</span>
                            </div>
                            <span class="text-[10px] bg-blue-500/15 text-blue-600 dark:text-blue-300 px-1.5 py-0.2 rounded font-bold tabular-nums">{{ $selectableEmployees->count() }}</span>
                        </a>
                        @endif

                        <!-- Filtered Employee Items -->
                        <div class="overflow-y-auto divide-y divide-black/[0.04] dark:divide-white/[0.05] max-h-56">
                            <template x-for="emp in filtered" :key="emp.id">
                                <a :href="emp.url" 
                                   class="px-3.5 py-2 flex items-center justify-between gap-2.5 hover:bg-black/[0.03] dark:hover:bg-white/[0.06] transition-colors text-left group">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-6 h-6 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center shrink-0" x-text="emp.initial"></div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate" x-text="emp.name"></div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium truncate" x-text="emp.department"></div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-mono font-semibold text-slate-600 dark:text-slate-300 bg-black/[0.04] dark:bg-white/[0.08] px-1.5 py-0.5 rounded border border-black/[0.04] dark:border-white/[0.06] shrink-0" x-text="emp.emp_id"></span>
                                </a>
                            </template>
                            <div x-show="filtered.length === 0" class="p-4 text-center text-xs text-slate-400 dark:text-slate-500">
                                No employee found matching "<span x-text="query" class="font-bold"></span>"
                            </div>
                        </div>

                    </div>
                </div>

                @if($isSuperAdmin || $isHrLead)
                    <!-- Department Dropdown Filter -->
                    <form action="{{ route('my-usage-records.index') }}" method="GET" class="inline m-0">
                        <input type="hidden" name="scope" value="{{ $scope === 'my' ? 'all' : $scope }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="relative">
                            <select name="department_id" onchange="this.form.submit()"
                                    class="appearance-none [background-image:none]! bg-slate-100/90 dark:bg-white/[0.06] border border-slate-200/80 dark:border-white/[0.08] rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 py-1.5 pl-3 pr-7 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-2xs">
                                <option value="">All Depts</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ (string)$departmentId === (string)$dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                    </form>
                @endif
            </div>

        </div>
    @endif

    <!-- ============================================================== -->
    <!-- 2. LEAVE BALANCE & QUOTA SECTION                               -->
    <!-- ============================================================== -->
    <div class="space-y-4">
        @if($isIndividualView)
            {{-- INDIVIDUAL QUOTA BREAKDOWN (Single Employee / My Usage View) --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="table-2" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                    <h2 class="text-sm font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                        Leave Balance & Quota Summary ({{ $targetEmployee->user->name ?? $targetEmployee->first_name }})
                    </h2>
                </div>
            </div>

            @include('pages.partials.leave_balance_table')

        @else
            {{-- MULTI-EMPLOYEE SCOPE: TILES VIEW --}}
            <div class="space-y-4">
                
                <!-- Section Header -->
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                    <h2 class="text-sm font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                        Employee Leave Balances & Quotas
                    </h2>
                    <span class="text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800 px-2 py-0.5 rounded-full">
                        {{ count($employeeBalancesList) }} Staff
                    </span>
                </div>

                <!-- Clean Employee Name Tiles Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    @forelse($employeeBalancesList as $item)
                        @php
                            $isSelected = !empty($item['is_selected']);
                        @endphp
                        <div onclick="window.location.href='{{ $item['inspect_url'] }}'"
                             class="group relative rounded-2xl p-3.5 transition-all duration-200 cursor-pointer flex items-center justify-between gap-3
                             {{ $isSelected 
                                 ? 'bg-blue-50/80 dark:bg-blue-950/40 border-2 border-blue-500 shadow-md ring-2 ring-blue-500/20' 
                                 : 'bg-white dark:bg-[#111827] border border-slate-200/90 dark:border-white/[0.08] hover:border-blue-400 dark:hover:border-blue-500/60 hover:shadow-md hover:-translate-y-0.5' }}">
                            
                            <!-- Avatar & Identity -->
                            <div class="flex items-center gap-3 min-w-0">
                                @if(!empty($item['profile_picture']))
                                    <img src="{{ asset('storage/' . $item['profile_picture']) }}" 
                                         alt="{{ $item['name'] }}" 
                                         class="w-10 h-10 rounded-full object-cover shrink-0 border border-black/5 dark:border-white/10 shadow-2xs">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-bold text-sm flex items-center justify-center shrink-0 shadow-2xs">
                                        {{ $item['initial'] }}
                                    </div>
                                @endif

                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate">
                                            {{ $item['name'] }}
                                        </h4>
                                        <span class="text-[10px] font-mono font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-white/[0.08] px-1.5 py-0.2 rounded border border-slate-200 dark:border-white/[0.08] shrink-0">
                                            {{ $item['emp_id'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium truncate mt-0.5">
                                        {{ $item['designation'] ?: $item['department'] }} • {{ $item['department'] }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right Indicator: Selected Pill or Chevron -->
                            <div class="flex items-center shrink-0">
                                @if($isSelected)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-100/90 dark:bg-blue-900/50 px-2 py-0.5 rounded-full border border-blue-200 dark:border-blue-800 shadow-2xs">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        Selected
                                    </span>
                                @else
                                    <div class="w-7 h-7 rounded-xl bg-slate-100/80 dark:bg-white/[0.04] group-hover:bg-blue-50 dark:group-hover:bg-blue-900/30 text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 flex items-center justify-center transition-colors">
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                    </div>
                                @endif
                            </div>

                        </div>
                    @empty
                        <div class="col-span-full py-10 text-center text-slate-400 dark:text-slate-500 text-xs">
                            No employees found in this scope.
                        </div>
                    @endforelse
                </div>

                <!-- SELECTED EMPLOYEE: TABLE OF USAGE (LEAVE BALANCE & QUOTA SUMMARY) -->
                @if($targetEmployee)
                    <div class="mt-4 pt-3 border-t border-slate-200/80 dark:border-white/[0.08] space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 shadow-xs">
                            <div class="flex items-center gap-3">
                                @if(!empty($targetEmployee->profile_picture))
                                    <img src="{{ asset('storage/' . $targetEmployee->profile_picture) }}" 
                                         alt="{{ $targetEmployee->user->name ?? $targetEmployee->first_name }}" 
                                         class="w-9 h-9 rounded-full object-cover border border-blue-300 dark:border-blue-700 shadow-2xs">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shadow-2xs">
                                        {{ strtoupper(substr($targetEmployee->user->name ?? $targetEmployee->first_name ?? 'E', 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                                            {{ $targetEmployee->user->name ?? ($targetEmployee->first_name . ' ' . $targetEmployee->last_name) }}
                                        </span>
                                        <span class="text-[10px] font-mono font-semibold px-1.5 py-0.2 rounded bg-white dark:bg-white/[0.1] text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                            {{ $targetEmployee->employee_id_number ?? ('EMP-' . $targetEmployee->id) }}
                                        </span>
                                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                            Active Selected
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                        Leave Balance & Quota Summary Table and Itemized Leave Usage
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('my-usage-records.index', array_filter(['scope' => $scope, 'department_id' => $departmentId, 'year' => $year, 'month' => $month, 'status' => $status])) }}" 
                               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white px-3 py-1.5 rounded-xl bg-white dark:bg-white/[0.08] border border-slate-200 dark:border-white/[0.1] hover:bg-slate-50 transition-colors shadow-2xs self-start sm:self-auto cursor-pointer">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                <span>Deselect Employee</span>
                            </a>
                        </div>

                        <!-- 5-Column Modern Table of Usage for $targetEmployee -->
                        @include('pages.partials.leave_balance_table')
                    </div>
                @endif

            </div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- 3. LEAVE USAGE SHOWCASE & HISTORY                              -->
    <!-- ============================================================== -->
    <div id="leave-usage-showcase" class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-white/[0.08] p-4 sm:p-5 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.06),0_1px_3px_0_rgba(15,23,42,0.04)] space-y-4">
        
        @php
            $currEmpId = ($targetEmployee && $scope !== 'my') ? $targetEmployee->id : null;
        @endphp

        <!-- Row 1: Header + Inline Search, Unified Date Range & Month Dropdown -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-white/[0.06]">
            <!-- Left: Section Title -->
            <div class="flex items-center gap-2">
                <i data-lucide="list-checks" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    @if($targetEmployee && $scope !== 'my')
                        Leave Usage History • {{ $targetEmployee->user->name ?? $targetEmployee->first_name }}
                    @else
                        Leave Usage History
                    @endif
                </h3>
                <span class="text-xs text-slate-400 dark:text-slate-500 font-medium tabular-nums">({{ $usageRecords->count() }} records)</span>
            </div>

            <!-- Right: Search Input, Unified Date Range & Current Month Selector -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                
                <!-- Search Input Form -->
                <form action="{{ route('my-usage-records.index') }}" method="GET" data-ajax="true" onsubmit="event.preventDefault(); window.updateShowcase(this);" class="relative m-0">
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    @if($currEmpId)
                        <input type="hidden" name="employee_id" value="{{ $currEmpId }}">
                    @endif
                    @if($departmentId && ($scope === 'all' || $scope === 'team'))
                        <input type="hidden" name="department_id" value="{{ $departmentId }}">
                    @endif
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    @if($startDate)
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                    @endif
                    @if($endDate)
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @endif
                    @if($leaveTypeId)
                        <input type="hidden" name="leave_type_id" value="{{ $leaveTypeId }}">
                    @endif

                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" name="search" value="{{ $search }}" 
                           placeholder="Search reason, ID, employee..." 
                           onkeydown="if(event.key === 'Enter') { event.preventDefault(); window.updateShowcase(this.form); }"
                           class="w-40 sm:w-52 pl-7 pr-7 py-1.5 bg-slate-100/90 dark:bg-white/[0.06] border border-slate-200/80 dark:border-white/[0.08] rounded-xl text-xs font-medium text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-white/[0.1] focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-all shadow-2xs">
                    @if($search)
                        <a href="{{ route('my-usage-records.index', array_filter(['scope' => $scope, 'employee_id' => $currEmpId, 'department_id' => $departmentId, 'status' => $status, 'year' => $year, 'month' => $month, 'start_date' => $startDate, 'end_date' => $endDate, 'leave_type_id' => $leaveTypeId])) }}" 
                           class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" title="Clear search">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </form>

                <!-- Unified Date Range Picker Form -->
                <form action="{{ route('my-usage-records.index') }}" method="GET" data-ajax="true" onsubmit="event.preventDefault(); window.updateShowcase(this);" class="inline-flex items-center m-0">
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    @if($currEmpId)
                        <input type="hidden" name="employee_id" value="{{ $currEmpId }}">
                    @endif
                    @if($departmentId && ($scope === 'all' || $scope === 'team'))
                        <input type="hidden" name="department_id" value="{{ $departmentId }}">
                    @endif
                    <input type="hidden" name="status" value="{{ $status }}">
                    @if($leaveTypeId)
                        <input type="hidden" name="leave_type_id" value="{{ $leaveTypeId }}">
                    @endif
                    @if($search)
                        <input type="hidden" name="search" value="{{ $search }}">
                    @endif
                    <input type="hidden" name="month" value="all">

                    <div class="inline-flex items-center gap-1.5 bg-slate-100/90 dark:bg-white/[0.06] border border-slate-200/80 dark:border-white/[0.08] rounded-xl px-2.5 py-1 shadow-2xs">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                        <input type="date" name="start_date" value="{{ $startDate }}" 
                               class="bg-transparent border-0 text-xs font-semibold text-slate-800 dark:text-slate-200 p-0 focus:ring-0 w-24 sm:w-28 cursor-pointer tabular-nums">
                        <span class="text-slate-400 text-xs font-bold">→</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" 
                               class="bg-transparent border-0 text-xs font-semibold text-slate-800 dark:text-slate-200 p-0 focus:ring-0 w-24 sm:w-28 cursor-pointer tabular-nums">
                        <button type="submit" 
                                class="px-2 py-0.5 rounded-lg bg-[#007AFF] hover:bg-[#0071E3] text-white text-[11px] font-semibold shadow-2xs cursor-pointer ml-0.5 transition-colors">
                            Apply
                        </button>
                    </div>
                </form>

                <!-- Current Month Selector Dropdown (Fixed Apple Pop-up Menu: No overlapping dots) -->
                <form action="{{ route('my-usage-records.index') }}" method="GET" data-ajax="true" class="inline-flex items-center m-0">
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    @if($currEmpId)
                        <input type="hidden" name="employee_id" value="{{ $currEmpId }}">
                    @endif
                    @if($departmentId && ($scope === 'all' || $scope === 'team'))
                        <input type="hidden" name="department_id" value="{{ $departmentId }}">
                    @endif
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    @if($leaveTypeId)
                        <input type="hidden" name="leave_type_id" value="{{ $leaveTypeId }}">
                    @endif
                    @if($search)
                        <input type="hidden" name="search" value="{{ $search }}">
                    @endif

                    <div class="relative inline-flex items-center">
                        <select name="month" onchange="window.updateShowcase(this.form)" 
                                class="appearance-none [background-image:none]! bg-blue-500/10 hover:bg-blue-500/15 dark:bg-blue-500/20 dark:hover:bg-blue-500/25 border border-blue-500/20 dark:border-blue-400/20 text-blue-700 dark:text-blue-300 text-xs font-semibold rounded-xl py-1.5 pl-3.5 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-2xs transition-all">
                            @foreach($monthsList as $mKey => $mName)
                                <option value="{{ $mKey }}" {{ (string)$month === (string)$mKey ? 'selected' : '' }} class="bg-white dark:bg-[#161F32] text-slate-800 dark:text-slate-100">
                                    @if($mKey == $currentMonth && $year == $currentYear)
                                        Current Month ({{ \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->format('M Y') }})
                                    @elseif($mKey === 'all')
                                        All Months ({{ $year }})
                                    @else
                                        {{ $mName }} {{ $year }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </form>

            </div>
        </div>

        <!-- Row 2: Combined Filter Strip (Status Tabs + Clean Leave Type Dropdown) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <!-- Left: Status Tabs with Live Badge Counts -->
            <div class="flex items-center gap-1 bg-slate-100/90 dark:bg-white/[0.06] p-1 rounded-xl text-xs font-semibold overflow-x-auto border border-slate-200/80 dark:border-white/[0.06]">
                <a href="{{ route('my-usage-records.index', array_filter(['scope' => $scope, 'employee_id' => $currEmpId, 'department_id' => $departmentId, 'status' => 'all', 'year' => $year, 'month' => $month, 'start_date' => $startDate, 'end_date' => $endDate, 'leave_type_id' => $leaveTypeId, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap flex items-center gap-1.5 {{ $status === 'all' ? 'bg-white dark:bg-white/[0.14] text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' }}">
                    <span>All</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] tabular-nums {{ $status === 'all' ? 'bg-blue-500/15 text-blue-600 dark:text-blue-300 font-bold' : 'bg-black/[0.04] dark:bg-white/[0.1] text-slate-600 dark:text-slate-300' }}">{{ $counts['all'] }}</span>
                </a>

                <a href="{{ route('my-usage-records.index', array_filter(['scope' => $scope, 'employee_id' => $currEmpId, 'department_id' => $departmentId, 'status' => 'approved', 'year' => $year, 'month' => $month, 'start_date' => $startDate, 'end_date' => $endDate, 'leave_type_id' => $leaveTypeId, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap flex items-center gap-1.5 {{ $status === 'approved' ? 'bg-white dark:bg-white/[0.14] text-emerald-600 dark:text-emerald-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' }}">
                    <span>Approved</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] tabular-nums {{ $status === 'approved' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 font-bold' : 'bg-black/[0.04] dark:bg-white/[0.1] text-slate-600 dark:text-slate-300' }}">{{ $counts['approved'] }}</span>
                </a>

                <a href="{{ route('my-usage-records.index', array_filter(['scope' => $scope, 'employee_id' => $currEmpId, 'department_id' => $departmentId, 'status' => 'pending', 'year' => $year, 'month' => $month, 'start_date' => $startDate, 'end_date' => $endDate, 'leave_type_id' => $leaveTypeId, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap flex items-center gap-1.5 {{ $status === 'pending' ? 'bg-white dark:bg-white/[0.14] text-amber-600 dark:text-amber-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' }}">
                    <span>Pending</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] tabular-nums {{ $status === 'pending' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-300 font-bold' : 'bg-black/[0.04] dark:bg-white/[0.1] text-slate-600 dark:text-slate-300' }}">{{ $counts['pending'] }}</span>
                </a>

                <a href="{{ route('my-usage-records.index', array_filter(['scope' => $scope, 'employee_id' => $currEmpId, 'department_id' => $departmentId, 'status' => 'rejected', 'year' => $year, 'month' => $month, 'start_date' => $startDate, 'end_date' => $endDate, 'leave_type_id' => $leaveTypeId, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap flex items-center gap-1.5 {{ $status === 'rejected' ? 'bg-white dark:bg-white/[0.14] text-rose-600 dark:text-rose-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' }}">
                    <span>Rejected</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] tabular-nums {{ $status === 'rejected' ? 'bg-rose-500/15 text-rose-600 dark:text-rose-300 font-bold' : 'bg-black/[0.04] dark:bg-white/[0.1] text-slate-600 dark:text-slate-300' }}">{{ $counts['rejected'] }}</span>
                </a>

                <a href="{{ route('my-usage-records.index', array_filter(['scope' => $scope, 'employee_id' => $currEmpId, 'department_id' => $departmentId, 'status' => 'canceled', 'year' => $year, 'month' => $month, 'start_date' => $startDate, 'end_date' => $endDate, 'leave_type_id' => $leaveTypeId, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap flex items-center gap-1.5 {{ $status === 'canceled' ? 'bg-white dark:bg-white/[0.14] text-slate-800 dark:text-slate-200 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' }}">
                    <span>Canceled</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] tabular-nums {{ $status === 'canceled' ? 'bg-black/[0.04] dark:bg-white/[0.1] text-slate-700 dark:text-slate-300 font-bold' : 'bg-black/[0.04] dark:bg-white/[0.1] text-slate-600 dark:text-slate-300' }}">{{ $counts['canceled'] }}</span>
                </a>
            </div>

            <!-- Right: Leave Type Dropdown (Apple HIG Pop-up) -->
            <form action="{{ route('my-usage-records.index') }}" method="GET" data-ajax="true" class="inline-flex items-center m-0">
                <input type="hidden" name="scope" value="{{ $scope }}">
                @if($currEmpId)
                    <input type="hidden" name="employee_id" value="{{ $currEmpId }}">
                @endif
                @if($departmentId && ($scope === 'all' || $scope === 'team'))
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                @endif
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                @if($startDate)
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                @endif
                @if($endDate)
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                @endif
                @if($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif

                <div class="relative inline-flex items-center">
                    <select name="leave_type_id" onchange="window.updateShowcase(this.form)"
                            class="appearance-none [background-image:none]! bg-slate-100/90 dark:bg-white/[0.06] border border-slate-200/80 dark:border-white/[0.08] rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 py-1.5 pl-3 pr-7 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-2xs hover:bg-slate-200/70 dark:hover:bg-white/[0.1] transition-colors">
                        <option value="">Leave Type: All</option>
                        @foreach($activeLeaveTypes as $lt)
                            @if(!in_array(strtoupper($lt->code ?? ''), ['HALF', 'HALF_DAY']))
                                <option value="{{ $lt->id }}" {{ (string)$leaveTypeId === (string)$lt->id ? 'selected' : '' }}>
                                    {{ $lt->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
            </form>
        </div>

        <!-- High-Density Compact Record Cards -->
        <div class="space-y-2.5 pt-1">
            @forelse($usageRecords as $rec)
                @php
                    $isApproved = in_array($rec->status, ['Approved', 'HR Approved']) || $rec->manager_status === 'Approved';
                    $isRejected = in_array($rec->status, ['Rejected']) || $rec->manager_status === 'Rejected' || $rec->covering_status === 'Rejected';
                    $isCanceled = in_array($rec->status, ['Canceled', 'Cancelled']);
                    $isPending = !$isApproved && !$isRejected && !$isCanceled;

                    $statusAccentBorder = match(true) {
                        $isApproved => 'border-l-4 border-l-emerald-500',
                        $isPending => 'border-l-4 border-l-amber-500',
                        $isRejected => 'border-l-4 border-l-rose-500',
                        default => 'border-l-4 border-l-slate-400'
                    };

                    $statusBadge = match(true) {
                        $isApproved => [
                            'label' => 'Approved',
                            'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/50',
                            'dot' => 'bg-emerald-500'
                        ],
                        $isPending => [
                            'label' => $rec->manager_status === 'Pending' ? 'Pending Manager Approval' : ($rec->covering_status === 'Pending' ? 'Pending Covering Approval' : ($rec->status ?? 'Pending Approval')),
                            'class' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/50',
                            'dot' => 'bg-amber-500'
                        ],
                        $isRejected => [
                            'label' => 'Rejected',
                            'class' => 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/50',
                            'dot' => 'bg-rose-500'
                        ],
                        default => [
                            'label' => 'Canceled',
                            'class' => 'bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
                            'dot' => 'bg-slate-400'
                        ]
                    };

                    $slotText = $rec->short_leave_slot ? trim(preg_replace('/^Short Leave\s*[\(•\-]?\s*/i', '', $rec->short_leave_slot), '() ') : '';
                    $halfDaySlotText = $rec->half_day_slot ? trim($rec->half_day_slot) : 'Morning';
                    $halfDayFormatted = str_contains($halfDaySlotText, '(') 
                        ? 'Half Day • ' . $halfDaySlotText 
                        : 'Half Day (' . $halfDaySlotText . ')';

                    $combinedLeaveBadge = match(true) {
                        $rec->is_short_leave || (strtoupper($rec->leaveType->code ?? '') === 'SHORT') => 
                            'Short Leave • ' . ($slotText ?: '1.5 Hours'),
                        (bool)$rec->is_half_day => 
                            ($rec->leaveType->name ?? 'Leave') . ' • ' . $halfDayFormatted,
                        default => 
                            ($rec->leaveType->name ?? 'Leave') . ' • ' . ((float)$rec->duration) . (((float)$rec->duration) === 1.0 ? ' Day' : ' Days')
                    };
                @endphp

                <!-- 2-Tier High-Density Structured Card -->
                <div class="rounded-xl border border-slate-200/80 dark:border-white/[0.08] {{ $statusAccentBorder }} bg-slate-50/60 dark:bg-white/[0.03] hover:bg-white dark:hover:bg-white/[0.06] hover:shadow-[0_2px_8px_-1px_rgba(15,23,42,0.06)] py-3 px-4 shadow-[0_1px_2px_0_rgba(15,23,42,0.03)] transition-all space-y-2">
                    
                    <!-- Line 1: Primary Header -->
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        
                        <!-- Left: Date, Combined Badge & ID -->
                        <div class="flex flex-wrap items-center gap-2 min-w-0">
                            <!-- Date Anchor -->
                            <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm tabular-nums">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#007AFF] dark:text-[#0A84FF] shrink-0"></i>
                                <span>{{ $rec->start_date }}{{ $rec->start_date !== $rec->end_date ? ' → ' . $rec->end_date : '' }}</span>
                            </div>

                            <!-- Unified Badge: Type & Duration -->
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 tabular-nums">
                                {{ $combinedLeaveBadge }}
                            </span>

                            <!-- Employee Tag (for Team/All view or inspecting others) -->
                            @if(!$isIndividualView || ($targetEmployee && $targetEmployee->id !== $loggedEmployee->id))
                                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-semibold bg-black/[0.04] dark:bg-white/[0.06] text-slate-800 dark:text-slate-200 border border-black/[0.04] dark:border-white/[0.06] shrink-0">
                                    <div class="w-4 h-4 rounded-full bg-blue-600 text-white text-[9px] font-bold flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($rec->employee->user->name ?? $rec->employee->first_name ?? 'E', 0, 1)) }}
                                    </div>
                                    <span class="truncate">{{ $rec->employee->user->name ?? ($rec->employee->first_name . ' ' . $rec->employee->last_name) }}</span>
                                </div>
                            @endif

                            <!-- Request ID Tag -->
                            <span class="text-[11px] font-mono text-slate-400 dark:text-slate-400 font-medium">
                                {{ $rec->req_number ?? 'REQ-' . $rec->id }}
                            </span>
                        </div>

                        <!-- Right: Status Badge & Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Status Badge -->
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusBadge['class'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }} shrink-0"></span>
                                <span>{{ $statusBadge['label'] }}</span>
                            </span>

                            <!-- Action Button: Cancel (Requester) or Review in Approvals -->
                            @if($isPending)
                                @if($rec->employee_id === $loggedEmployee->id)
                                    <form action="{{ route('my-leaves.cancel', $rec->id) }}" method="POST" 
                                          onsubmit="return confirm('Are you sure you want to cancel this pending leave request? Any deducted days will be restored to your balance.')" 
                                          class="inline m-0">
                                        @csrf
                                        <button type="submit" 
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold border border-black/[0.06] dark:border-white/[0.08] text-slate-600 dark:text-slate-300 hover:bg-rose-500/10 hover:text-rose-600 hover:border-rose-500/20 transition-colors cursor-pointer" 
                                                title="Cancel this pending application">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                            <span>Cancel</span>
                                        </button>
                                    </form>
                                @elseif($isSuperAdmin || $isHrLead || $isManager)
                                    <a href="{{ route('approvals.index', ['search' => $rec->req_number ?? '']) }}" 
                                       class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-amber-500/10 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-colors"
                                       title="Review this request in Leave Approvals">
                                        <i data-lucide="check-square" class="w-3 h-3"></i>
                                        <span>Review</span>
                                    </a>
                                @endif
                            @endif
                        </div>

                    </div>

                    <!-- Line 2: Metadata Context -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1.5 border-t border-slate-200/60 dark:border-white/[0.06] text-xs">
                        
                        <!-- Left: Reason | Covering Staff | Project -->
                        <div class="flex flex-wrap items-center gap-2 min-w-0">
                            <!-- Reason String -->
                            @if($rec->reason)
                                <span class="text-slate-600 dark:text-slate-400 font-normal truncate max-w-sm sm:max-w-md">
                                    "{{ $rec->reason }}"
                                </span>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic">
                                    No reason specified
                                </span>
                            @endif

                            <!-- Covering Staff -->
                            @if($rec->coveringEmployee)
                                <span class="text-slate-300 dark:text-slate-600 font-bold">|</span>
                                <span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400">
                                    <i data-lucide="user-check" class="w-3 h-3 text-blue-500 shrink-0"></i>
                                    <span>Covering: <strong class="text-slate-700 dark:text-slate-200 font-medium">{{ $rec->coveringEmployee->user->name ?? ($rec->coveringEmployee->first_name . ' ' . $rec->coveringEmployee->last_name) }}</strong></span>
                                </span>
                            @endif

                            <!-- Project/Client Name if any -->
                            @if($rec->project_client_name)
                                <span class="text-slate-300 dark:text-slate-600 font-bold">|</span>
                                <span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400">
                                    <i data-lucide="briefcase" class="w-3 h-3 text-slate-400 shrink-0"></i>
                                    <span>Project: <strong class="text-slate-700 dark:text-slate-200 font-medium">{{ $rec->project_client_name }}</strong></span>
                                </span>
                            @endif
                        </div>

                        <!-- Right: Submission Timestamp -->
                        <div class="flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-500 shrink-0 tabular-nums">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            <span>Submitted {{ $rec->created_at ? $rec->created_at->format('M d, Y • h:i A') : $rec->start_date }}</span>
                        </div>

                    </div>

                </div>
            @empty
                <div class="py-10 text-center space-y-2.5 bg-slate-50/50 dark:bg-slate-800/30 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-500 flex items-center justify-center mx-auto">
                        <i data-lucide="file-search" class="w-5 h-5"></i>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">No leave records found</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                            No leave applications match the selected criteria for this period. Try clearing search filters or selecting another month.
                        </p>
                    </div>
                </div>
            @endforelse
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- 4. APPLY LEAVE MODAL / DRAWER                                  -->
    <!-- ============================================================== -->
    <div x-show="applyModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="applyModalOpen = false">
        
        <div @click.away="applyModalOpen = false" 
             class="bg-white/95 dark:bg-[#111827]/95 backdrop-blur-2xl rounded-3xl max-w-xl w-full p-6 sm:p-7 border border-black/[0.08] dark:border-white/[0.1] shadow-2xl space-y-5 overflow-hidden flex flex-col max-h-[92vh]"
             x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-3"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-3">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-black/[0.05] dark:border-white/[0.06] shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-500/20">
                        <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Apply for Leave</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-normal">Submit leave request with dynamic balance checks</p>
                    </div>
                </div>
                <button type="button" @click="applyModalOpen = false" class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-xl hover:bg-black/[0.04] dark:hover:bg-white/[0.08] transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" class="flex-1 min-h-0 overflow-y-auto space-y-4 pr-1">
                @csrf
                <input type="hidden" name="is_half_day" :value="isHalfDay ? '1' : '0'">
                <input type="hidden" name="half_day_slot" :value="halfDaySlot">
                <input type="hidden" name="is_short_leave" :value="isShortLeave || selectedLeaveTypeCode === 'SHORT' ? '1' : '0'">
                <input type="hidden" name="short_leave_slot" :value="shortLeaveSlot">

                <!-- Leave Type Selector -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Leave Type</label>
                        <template x-if="getCurrentQuota()">
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 tabular-nums" x-text="'Balance: ' + getCurrentQuota().balance_label"></span>
                        </template>
                    </div>

                    <div class="relative">
                        <select name="leave_type_id" 
                                x-model="selectedLeaveTypeId"
                                @change="selectedLeaveTypeCode = (leaveQuotas[selectedLeaveTypeId]?.code || '').toUpperCase()"
                                class="w-full appearance-none [background-image:none]! bg-slate-100/90 dark:bg-white/[0.06] border border-black/[0.06] dark:border-white/[0.08] rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 py-2.5 pl-3.5 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @foreach($activeLeaveTypes as $lt)
                                @if(!in_array(strtoupper($lt->code ?? ''), ['HALF', 'HALF_DAY']))
                                    <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Date Pickers Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Start Date</label>
                        <input type="date" name="start_date" x-model="startDate" required
                               class="w-full bg-slate-100/90 dark:bg-white/[0.06] border border-black/[0.06] dark:border-white/[0.08] rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 tabular-nums">
                    </div>

                    <div class="space-y-1" x-show="!isHalfDay && selectedLeaveTypeCode !== 'SHORT' && !isShortLeave">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">End Date</label>
                        <input type="date" name="end_date" x-model="endDate"
                               class="w-full bg-slate-100/90 dark:bg-white/[0.06] border border-black/[0.06] dark:border-white/[0.08] rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 tabular-nums">
                    </div>
                </div>

                <!-- Toggles for Half Day and Short Leave -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <!-- Half Day Option -->
                    <div class="p-3 bg-black/[0.02] dark:bg-white/[0.03] rounded-2xl border border-black/[0.05] dark:border-white/[0.06] space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="isHalfDay" @change="if(isHalfDay) isShortLeave = false" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Half Day Request</span>
                        </label>
                        <div x-show="isHalfDay" class="flex items-center gap-2 pt-1">
                            <label class="flex items-center gap-1 text-xs cursor-pointer">
                                <input type="radio" value="Morning" x-model="halfDaySlot" class="text-blue-600">
                                <span>Morning</span>
                            </label>
                            <label class="flex items-center gap-1 text-xs cursor-pointer ml-2">
                                <input type="radio" value="Evening" x-model="halfDaySlot" class="text-blue-600">
                                <span>Evening</span>
                            </label>
                        </div>
                    </div>

                    <!-- Short Leave Option -->
                    <div class="p-3 bg-black/[0.02] dark:bg-white/[0.03] rounded-2xl border border-black/[0.05] dark:border-white/[0.06] space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="isShortLeave" @change="if(isShortLeave) isHalfDay = false" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Short Leave (1.5 hrs)</span>
                        </label>
                        <div x-show="isShortLeave || selectedLeaveTypeCode === 'SHORT'" class="pt-1">
                            <select x-model="shortLeaveSlot" class="w-full text-xs font-semibold bg-white dark:bg-[#161F32] border border-black/[0.06] dark:border-white/[0.08] rounded-xl p-1.5">
                                <option value="08:30 AM – 10:00 AM">08:30 AM – 10:00 AM (Morning)</option>
                                <option value="03:30 PM – 05:00 PM">03:30 PM – 05:00 PM (Evening)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Covering Person -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Covering Employee</label>
                    <div class="relative">
                        <select name="covering_employee_id" x-model="coveringEmployeeId"
                                class="w-full appearance-none [background-image:none]! bg-slate-100/90 dark:bg-white/[0.06] border border-black/[0.06] dark:border-white/[0.08] rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 py-2.5 pl-3.5 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select covering colleague...</option>
                            @foreach($colleagues as $c)
                                <option value="{{ $c->id }}">{{ $c->user->name ?? $c->first_name }} ({{ $c->department->name ?? 'Colleague' }})</option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Reason Textarea -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Reason / Notes</label>
                        <span class="text-[10px] text-slate-400 lowercase">(optional)</span>
                    </div>
                    <textarea name="reason" x-model="reason" rows="2" placeholder="State brief reason or handover note (optional)..."
                              class="w-full bg-slate-100/90 dark:bg-white/[0.06] border border-black/[0.06] dark:border-white/[0.08] rounded-xl text-xs font-medium text-slate-900 dark:text-slate-100 p-3 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                </div>

                <!-- Live Balance Preview Box -->
                <div class="bg-blue-500/10 dark:bg-blue-500/15 border border-blue-500/20 rounded-2xl p-3.5 flex items-center justify-between text-xs">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Estimated Duration</div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white tabular-nums" x-text="calculateEstimatedDuration() + ' Days'"></div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Remaining After Request</div>
                        <div class="text-sm font-bold text-emerald-700 dark:text-emerald-300 tabular-nums" x-text="getProjectedBalance() + ' Days'"></div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="pt-3 border-t border-black/[0.05] dark:border-white/[0.06] flex items-center justify-end gap-2.5">
                    <button type="button" @click="applyModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold bg-black/[0.04] dark:bg-white/[0.08] text-slate-700 dark:text-slate-200 border border-black/[0.06] dark:border-white/[0.08] hover:bg-black/[0.08] dark:hover:bg-white/[0.12] transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-[#007AFF] hover:bg-[#0071E3] active:scale-[0.98] text-white px-5 py-2 rounded-xl text-xs font-semibold cursor-pointer shadow-xs transition-all">
                        Submit Application
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

<!-- Smooth In-Place AJAX Showcase Script: Prevents Full-Page Reload and Scroll Jump -->
<script>
    window.loadShowcase = function(url) {
        const container = document.getElementById('leave-usage-showcase');
        if (!container) {
            window.location.href = url;
            return;
        }

        container.style.opacity = '0.55';
        container.style.pointerEvents = 'none';
        container.style.transition = 'opacity 0.2s ease-in-out';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newShowcase = doc.getElementById('leave-usage-showcase');
                
                if (newShowcase) {
                    container.innerHTML = newShowcase.innerHTML;
                    window.history.pushState(null, '', url);
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                } else {
                    window.location.href = url;
                }
            })
            .catch(err => {
                window.location.href = url;
            })
            .finally(() => {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            });
    };

    window.updateShowcase = function(form) {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        const url = form.action + '?' + params.toString();
        window.loadShowcase(url);
    };

    // Smooth navigation for all internal links inside the showcase (tabs, categories, reset, pagination)
    document.addEventListener('click', function(e) {
        const showcase = document.getElementById('leave-usage-showcase');
        if (!showcase) return;

        const link = e.target.closest('#leave-usage-showcase a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        try {
            const urlObj = new URL(href, window.location.origin);
            if (urlObj.pathname === window.location.pathname) {
                e.preventDefault();
                window.loadShowcase(href);
            }
        } catch (err) {}
    });

    // Handle browser Back / Forward buttons smoothly
    window.addEventListener('popstate', function() {
        if (document.getElementById('leave-usage-showcase')) {
            window.loadShowcase(window.location.href);
        }
    });
</script>
@endsection
