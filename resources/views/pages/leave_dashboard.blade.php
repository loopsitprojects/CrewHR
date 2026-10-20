@extends('layouts.app', ['title' => 'Crew HR - Leave Management Dashboard', 'breadcrumb' => 'Dashboard'])

@section('content')
@php
    $leaveTypeMap = collect($leaveBalances ?? [])->mapWithKeys(function($b) {
        return [strtoupper($b->leaveType->code ?? '') => (string)$b->leave_type_id];
    })->toArray();
    $leaveCodeMap = collect($leaveBalances ?? [])->mapWithKeys(function($b) {
        return [(string)$b->leave_type_id => strtoupper($b->leaveType->code ?? '')];
    })->toArray();
@endphp

<div class="flex-1 flex flex-col min-h-0 space-y-4" 
     x-data="{ 
         applyLeaveModal: false, 
         addCompanyLeaveModal: false, 
         dayModalOpen: false, 
         quotaModalOpen: false,
         selectedQuota: null,
         allMyHistory: {{ json_encode($myLeaveHistory) }},
         leaveTypesMap: {{ json_encode($leaveTypeMap) }},
         leaveCodeMap: {{ json_encode($leaveCodeMap) }},
         selectedLeaveTypeId: '{{ (string)($leaveTypeMap['ANNUAL'] ?? ($leaveBalances->first()->leave_type_id ?? '')) }}',
         selectedLeaveTypeCode: 'ANNUAL',
         selectedDay: {{ json_encode($todayCell) }}, 
         viewMode: 'grid', 
         targetStartDate: '{{ \Carbon\Carbon::now('Asia/Colombo')->toDateString() }}', 
         targetEndDate: '{{ \Carbon\Carbon::now('Asia/Colombo')->toDateString() }}',
         isHalfDay: false,
         isShortLeave: false,
         allHolidays: {{ json_encode($allHolidaysList ?? []) }},
         calculateDuration() {
            if (this.selectedLeaveTypeCode === 'SHORT') return '0.2 (Short Leave)';
            if (this.isHalfDay) return '0.5 (Half Day)';
            if (!this.targetStartDate || !this.targetEndDate) return 0;
            
            const sParts = this.targetStartDate.split('-').map(Number);
            const eParts = this.targetEndDate.split('-').map(Number);
            if (sParts.length !== 3 || eParts.length !== 3) return 0;

            const start = new Date(sParts[0], sParts[1] - 1, sParts[2], 0, 0, 0, 0);
            const end = new Date(eParts[0], eParts[1] - 1, eParts[2], 0, 0, 0, 0);
            if (start.getTime() > end.getTime()) return 0;

            const holidays = Array.isArray(this.allHolidays) ? this.allHolidays : [];
            let count = 0;
            let curr = new Date(start.getTime());

            while (curr.getTime() <= end.getTime()) {
                const dayOfWeek = curr.getDay(); // 0 = Sun, 6 = Sat
                const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
                const y = curr.getFullYear();
                const m = String(curr.getMonth() + 1).padStart(2, '0');
                const d = String(curr.getDate()).padStart(2, '0');
                const dateStr = `${y}-${m}-${d}`;
                const isHoliday = holidays.includes(dateStr);

                if (!isWeekend && !isHoliday) {
                    count++;
                }
                curr.setDate(curr.getDate() + 1);
            }
            return count;
        },
        openApplyModal(code) {
            let targetCode = (code || 'ANNUAL').toUpperCase();
            this.selectedLeaveTypeCode = targetCode;
            if (this.leaveTypesMap && this.leaveTypesMap[targetCode]) {
                this.selectedLeaveTypeId = String(this.leaveTypesMap[targetCode]);
            }
            if (targetCode === 'SHORT') {
                this.isHalfDay = false;
                this.isShortLeave = true;
                this.targetEndDate = this.targetStartDate;
            } else {
                this.isShortLeave = false;
            }
            this.applyLeaveModal = true;
            this.$nextTick(() => { 
                if (this.leaveTypesMap && this.leaveTypesMap[targetCode]) {
                    this.selectedLeaveTypeId = String(this.leaveTypesMap[targetCode]);
                }
                if (window.lucide) lucide.createIcons(); 
            });
        },
         openQuotaModal(code, name, icon, color, total, used, available, carryForward, unit) {
             let targetCode = (code || '').toUpperCase();
             this.selectedQuota = {
                 code: targetCode,
                 name: name,
                 icon: icon,
                 color: color,
                 total: total,
                 used: used,
                 available: available,
                 carryForward: carryForward,
                 unit: unit,
                 records: this.allMyHistory.filter(r => (r.leave_type?.code || '').toUpperCase() === targetCode || (targetCode === 'SHORT' && (r.is_short_leave || (r.leave_type?.code || '').toUpperCase() === 'SHORT')))
             };
             this.quotaModalOpen = true;
             this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
         }
     }">


@php
    $balancesByCode = collect($leaveBalances ?? [])->keyBy(function($b) {
        return strtoupper($b->leaveType->code ?? '');
    });

    $getQuotaData = function($code, $defaultAllocated = 0, $defaultUnit = 'days') use ($balancesByCode) {
        $bal = $balancesByCode->get(strtoupper($code));
        $allocated = (float)($bal->allocated ?? $defaultAllocated);
        $cf = (float)($bal->carried_forward ?? 0);
        $total = $allocated + $cf;
        $used = (float)($bal->used ?? 0);
        $available = max(0, $total - $used);
        $pct = $total > 0 ? min(100, round(($available / $total) * 100)) : 100;
        
        return [
            'total' => $total,
            'allocated' => $allocated,
            'cf' => $cf,
            'used' => $used,
            'available' => $available,
            'pct' => $pct,
            'unit' => $defaultUnit
        ];
    };

    $annualData = $getQuotaData('ANNUAL', 14, 'days');
    $casualData = $getQuotaData('CASUAL', 7, 'days');
    $medicalData = $getQuotaData('MEDICAL', 7, 'days');

    // Short Leave Quota (2 per calendar month, reset monthly)
    $shortTotal = 2;
    $shortUsed = (int)($monthlyShortLeavesUsed ?? 0);
    $shortAvailable = max(0, $shortTotal - $shortUsed);
    $shortPct = min(100, round(($shortAvailable / $shortTotal) * 100));
    $shortData = [
        'total' => $shortTotal,
        'allocated' => $shortTotal,
        'cf' => 0,
        'used' => $shortUsed,
        'available' => $shortAvailable,
        'pct' => $shortPct,
        'unit' => 'per month'
    ];

    $dutyData = $getQuotaData('DUTY', 30, 'days');
    $lieuData = $getQuotaData('LIEU', 2, 'days');
@endphp

    <!-- Page Header (Matching Reference UI) -->
    <div class="shrink-0 flex items-center justify-between pb-0.5">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-none">Leave Management</h1>
        </div>

        <!-- Right Controls: Dynamic Date Badge + Today + Month Chevrons -->
        <div class="flex items-center gap-2">
            <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 shadow-2xs">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>{{ \Carbon\Carbon::now('Asia/Colombo')->format('D, d M Y') }}</span>
            </div>

            <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 dark:bg-[#111927] dark:hover:bg-slate-800 border border-slate-200/90 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-2xs transition-colors">
                Today
            </a>

            <div class="flex items-center rounded-xl bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden">
                <a href="{{ route('dashboard', ['year' => $prevMonthDate->year, 'month' => $prevMonthDate->month]) }}" class="p-1.5 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors" title="Previous Month">
                    <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                </a>
                <div class="w-px h-3.5 bg-slate-200 dark:bg-slate-800"></div>
                <a href="{{ route('dashboard', ['year' => $nextMonthDate->year, 'month' => $nextMonthDate->month]) }}" class="p-1.5 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors" title="Next Month">
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Top Leave Summary Cards (6 Quotas - Sleek Linear Micro-Meters) -->
    <div class="shrink-0 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5">
        
        <!-- 1. Annual Leave -->
        <div @click="openQuotaModal('ANNUAL', 'Annual Leave', 'plane', 'blue', {{ $annualData['total'] }}, {{ $annualData['used'] }}, {{ $annualData['available'] }}, {{ $annualData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-3 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md hover:border-blue-400 dark:hover:border-blue-500/70 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-0.5 min-h-[105px]">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100/80 dark:border-blue-900/40">
                    <i data-lucide="plane" class="w-3.5 h-3.5"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">Annual Leave</span>
            </div>

            <div class="my-1 flex items-baseline gap-1.5">
                <span class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $annualData['available'] }}</span>
                <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/70 px-1.5 py-0.5 rounded">days left</span>
            </div>

            <div class="space-y-1">
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 dark:bg-blue-500 rounded-full transition-all duration-500" style="width: {{ $annualData['pct'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[9px] font-semibold text-slate-400 dark:text-slate-500">
                    <span>{{ $annualData['used'] }} used</span>
                    <span>{{ $annualData['pct'] }}% left</span>
                </div>
            </div>
        </div>

        <!-- 2. Casual Leave -->
        <div @click="openQuotaModal('CASUAL', 'Casual Leave', 'calendar-check', 'purple', {{ $casualData['total'] }}, {{ $casualData['used'] }}, {{ $casualData['available'] }}, {{ $casualData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-3 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md hover:border-purple-400 dark:hover:border-purple-500/70 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-0.5 min-h-[105px]">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-purple-50 dark:bg-purple-950/70 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 border border-purple-100/80 dark:border-purple-900/40">
                    <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">Casual Leave</span>
            </div>

            <div class="my-1 flex items-baseline gap-1.5">
                <span class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $casualData['available'] }}</span>
                <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/70 px-1.5 py-0.5 rounded">days left</span>
            </div>

            <div class="space-y-1">
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-purple-600 dark:bg-purple-500 rounded-full transition-all duration-500" style="width: {{ $casualData['pct'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[9px] font-semibold text-slate-400 dark:text-slate-500">
                    <span>{{ $casualData['used'] }} used</span>
                    <span>{{ $casualData['pct'] }}% left</span>
                </div>
            </div>
        </div>

        <!-- 3. Medical Leave -->
        <div @click="openQuotaModal('MEDICAL', 'Medical Leave', 'activity', 'emerald', {{ $medicalData['total'] }}, {{ $medicalData['used'] }}, {{ $medicalData['available'] }}, {{ $medicalData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-3 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md hover:border-emerald-400 dark:hover:border-emerald-500/70 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-0.5 min-h-[105px]">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100/80 dark:border-emerald-900/40">
                    <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">Medical Leave</span>
            </div>

            <div class="my-1 flex items-baseline gap-1.5">
                <span class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $medicalData['available'] }}</span>
                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/70 px-1.5 py-0.5 rounded">days left</span>
            </div>

            <div class="space-y-1">
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-600 dark:bg-emerald-500 rounded-full transition-all duration-500" style="width: {{ $medicalData['pct'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[9px] font-semibold text-slate-400 dark:text-slate-500">
                    <span>{{ $medicalData['used'] }} used</span>
                    <span>{{ $medicalData['pct'] }}% left</span>
                </div>
            </div>
        </div>

        <!-- 4. Short Leave -->
        <div @click="openQuotaModal('SHORT', 'Short Leave', 'clock', 'amber', {{ $shortData['total'] }}, {{ $shortData['used'] }}, {{ $shortData['available'] }}, {{ $shortData['cf'] }}, 'per month')"
             class="group bg-white dark:bg-[#111927] p-3 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md hover:border-amber-400 dark:hover:border-amber-500/70 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-0.5 min-h-[105px]">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-100/80 dark:border-amber-900/40">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">Short Leave</span>
            </div>

            <div class="my-1 flex items-baseline gap-1.5">
                <span class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $shortData['available'] }}</span>
                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/70 px-1.5 py-0.5 rounded">/ month</span>
            </div>

            <div class="space-y-1">
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500 dark:bg-amber-400 rounded-full transition-all duration-500" style="width: {{ $shortData['pct'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[9px] font-semibold text-slate-400 dark:text-slate-500">
                    <span>{{ $shortData['used'] }} used</span>
                    <span>{{ $shortData['pct'] }}% left</span>
                </div>
            </div>
        </div>

        <!-- 5. Duty Leave -->
        <div @click="openQuotaModal('DUTY', 'Duty Leave', 'briefcase', 'rose', {{ $dutyData['total'] }}, {{ $dutyData['used'] }}, {{ $dutyData['available'] }}, {{ $dutyData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-3 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md hover:border-rose-400 dark:hover:border-rose-500/70 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-0.5 min-h-[105px]">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-rose-50 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-100/80 dark:border-rose-900/40">
                    <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">Duty Leave</span>
            </div>

            <div class="my-1 flex items-baseline gap-1.5">
                <span class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $dutyData['available'] }}</span>
                <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/70 px-1.5 py-0.5 rounded">days left</span>
            </div>

            <div class="space-y-1">
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-rose-500 dark:bg-rose-400 rounded-full transition-all duration-500" style="width: {{ $dutyData['pct'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[9px] font-semibold text-slate-400 dark:text-slate-500">
                    <span>{{ $dutyData['used'] }} used</span>
                    <span>{{ $dutyData['pct'] }}% left</span>
                </div>
            </div>
        </div>

        <!-- 6. Lieu Leave -->
        <div @click="openQuotaModal('LIEU', 'Lieu Leave', 'refresh-cw', 'teal', {{ $lieuData['total'] }}, {{ $lieuData['used'] }}, {{ $lieuData['available'] }}, {{ $lieuData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-3 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md hover:border-teal-400 dark:hover:border-teal-500/70 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-0.5 min-h-[105px]">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-950/70 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0 border border-teal-100/80 dark:border-teal-900/40">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">Lieu Leave</span>
            </div>

            <div class="my-1 flex items-baseline gap-1.5">
                <span class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $lieuData['available'] }}</span>
                <span class="text-[10px] font-bold text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/70 px-1.5 py-0.5 rounded">days left</span>
            </div>

            <div class="space-y-1">
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-teal-500 dark:bg-teal-400 rounded-full transition-all duration-500" style="width: {{ $lieuData['pct'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[9px] font-semibold text-slate-400 dark:text-slate-500">
                    <span>{{ $lieuData['used'] }} used</span>
                    <span>{{ $lieuData['pct'] }}% left</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Main Grid Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-2">

        <!-- Left 2 Cols: Monthly Calendar View -->
        <div class="lg:col-span-2 bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex flex-col">
                
                <!-- Calendar Top Controls Bar (Clean Modern SaaS Header) -->
                <div class="flex flex-wrap items-center justify-between pb-3.5 border-b border-slate-100 dark:border-slate-800 gap-3">
                    <!-- Month Title & Quick Nav -->
                    <div class="flex items-center gap-3">
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ $selectedDate->format('F Y') }}</h2>
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-lg border border-slate-200/60 dark:border-slate-700/60">
                            <a href="{{ route('dashboard', ['year' => $prevMonthDate->year, 'month' => $prevMonthDate->month]) }}" class="w-7 h-7 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/70 rounded-md flex items-center justify-center transition-colors cursor-pointer" title="Previous Month">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>
                            <a href="{{ route('dashboard') }}" class="px-2.5 h-7 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/70 text-xs font-semibold rounded-md flex items-center transition-colors">Today</a>
                            <a href="{{ route('dashboard', ['year' => $nextMonthDate->year, 'month' => $nextMonthDate->month]) }}" class="w-7 h-7 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/70 rounded-md flex items-center justify-center transition-colors cursor-pointer" title="Next Month">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Month & Year Jump Selectors -->
                        <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-1.5">
                            <div class="relative flex items-center">
                                <select name="month" onchange="this.form.submit()" class="appearance-none [background-image:none]! h-8 bg-slate-100 hover:bg-slate-200/70 dark:bg-slate-800 dark:hover:bg-slate-700/80 border border-slate-200/80 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-800 dark:text-slate-200 py-1 pl-2.5 pr-7 focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                                    @foreach($monthsList as $num => $mName)
                                        <option value="{{ $num }}" {{ $num == $month ? 'selected' : '' }} class="bg-white dark:bg-[#111927] text-slate-900 dark:text-slate-200">{{ $mName }}</option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2 pointer-events-none"></i>
                            </div>
                            <div class="relative flex items-center">
                                <select name="year" onchange="this.form.submit()" class="appearance-none [background-image:none]! h-8 bg-slate-100 hover:bg-slate-200/70 dark:bg-slate-800 dark:hover:bg-slate-700/80 border border-slate-200/80 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-800 dark:text-slate-200 py-1 pl-2.5 pr-7 focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                                    @foreach($yearsList as $y)
                                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }} class="bg-white dark:bg-[#111927] text-slate-900 dark:text-slate-200">{{ $y }}</option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2 pointer-events-none"></i>
                            </div>
                        </form>

                        <!-- View Toggle (Month / Agenda) -->
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-lg border border-slate-200/60 dark:border-slate-700/60 text-xs font-semibold">
                            <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white dark:bg-[#111927] text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" class="px-2.5 py-1 rounded-md transition-all cursor-pointer">Month</button>
                            <button @click="viewMode = 'agenda'" :class="viewMode === 'agenda' ? 'bg-white dark:bg-[#111927] text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" class="px-2.5 py-1 rounded-md transition-all cursor-pointer">Agenda</button>
                        </div>
                    </div>
                </div>

                <!-- MODE 1: UNIFIED SAAS GRID VIEW -->
                <div x-show="viewMode === 'grid'" class="mt-3.5">
                    <div class="border border-slate-200/90 dark:border-slate-800 rounded-2xl overflow-hidden bg-slate-100/60 dark:bg-[#080d19] p-2.5">
                        <!-- Weekday Headers -->
                        <div class="grid grid-cols-7 mb-2 text-[11px] font-bold uppercase tracking-wider py-1 text-center select-none">
                            <div class="text-slate-600 dark:text-slate-400">Mon</div>
                            <div class="text-slate-600 dark:text-slate-400">Tue</div>
                            <div class="text-slate-600 dark:text-slate-400">Wed</div>
                            <div class="text-slate-600 dark:text-slate-400">Thu</div>
                            <div class="text-slate-600 dark:text-slate-400">Fri</div>
                            <div class="text-rose-500/90 dark:text-rose-400 font-extrabold">Sat</div>
                            <div class="text-rose-500/90 dark:text-rose-400 font-extrabold">Sun</div>
                        </div>

                        <!-- Days Grid (Every cell has subtle blue coloring, drop shadow and distinct outline) -->
                        <div class="grid grid-cols-7 gap-1.5">
                            @foreach($calendarDays as $cell)
                                <div @click="selectedDay = {{ json_encode($cell) }}; dayModalOpen = true"
                                     :class="selectedDay && selectedDay.date === '{{ $cell['date'] }}' ? 'ring-2 ring-blue-500 border-blue-500 dark:border-blue-400 shadow-md z-10' : ''"
                                     class="min-h-[92px] sm:min-h-[100px] p-2 rounded-xl flex flex-col justify-between transition-all duration-150 cursor-pointer relative group border shadow-xs hover:shadow-md hover:-translate-y-0.5
                                     {{ !$cell['is_current_month'] ? 'bg-slate-100/40 dark:bg-[#090e1c]/40 border-slate-200/50 dark:border-slate-800/40 shadow-none opacity-40' : '' }}
                                     {{ $cell['is_current_month'] && $cell['is_today'] ? 'bg-blue-100/70 dark:bg-blue-950/60 border-2 border-blue-500 dark:border-blue-400 shadow-sm' : '' }}
                                     {{ $cell['is_current_month'] && !$cell['is_today'] && $cell['is_weekend'] ? 'bg-slate-50/90 dark:bg-[#0d1526] border-slate-200/80 dark:border-[#1a2947] hover:bg-slate-100 dark:hover:bg-[#121c32]' : '' }}
                                     {{ $cell['is_current_month'] && !$cell['is_today'] && !$cell['is_weekend'] ? 'bg-blue-50/45 dark:bg-[#111c33] border-blue-200/60 dark:border-[#20355c] hover:bg-blue-50/80 dark:hover:bg-[#162544] hover:border-blue-300 dark:hover:border-blue-500/70' : '' }}
                                ">
                                    <!-- Day Header: Date Number -->
                                    <div class="flex items-center justify-between mb-1.5">
                                        @if($cell['is_today'] && $cell['is_current_month'])
                                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">{{ $cell['day'] }}</span>
                                            <span class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Today</span>
                                        @elseif(!$cell['is_current_month'])
                                            <span class="text-xs font-medium text-slate-400 dark:text-slate-600 pl-0.5">{{ $cell['day'] }}</span>
                                        @else
                                            <span class="text-xs font-semibold {{ $cell['is_weekend'] ? 'text-rose-500 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }} pl-0.5">{{ $cell['day'] }}</span>
                                        @endif
                                    </div>

                                    <!-- Events Stack (Pills with labels, never clipped) -->
                                    <div class="flex-1 flex flex-col justify-end gap-1 overflow-hidden">
                                        @if($cell['is_current_month'])
                                            <!-- Holiday Pill -->
                                            @if($cell['holiday'])
                                                @php
                                                    $isCompany = $cell['holiday']->type === 'Company';
                                                    $hTitle = $cell['holiday']->title;
                                                @endphp
                                                <div class="truncate px-1.5 py-0.5 rounded-md text-[10px] font-semibold flex items-center gap-1 {{ $isCompany ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-900/60' : 'bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-200 border border-amber-200/70 dark:border-amber-800/60' }}" title="{{ $hTitle }} ({{ $cell['holiday']->category ?? $cell['holiday']->type }})">
                                                    <span class="shrink-0 text-[10px]">{{ $cell['is_poya'] ? '🌕' : ($isCompany ? '🏢' : '🏛️') }}</span>
                                                    <span class="truncate">{{ $hTitle }}</span>
                                                </div>
                                            @endif

                                            <!-- Birthday Pill -->
                                            @if(isset($cell['birthdays']) && count($cell['birthdays']) > 0)
                                                @foreach($cell['birthdays'] as $bdayName)
                                                    <div class="truncate px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-pink-50 dark:bg-pink-950/50 text-pink-700 dark:text-pink-300 border border-pink-200/60 dark:border-pink-900/60 flex items-center gap-1" title="🎂 {{ $bdayName }}'s Birthday">
                                                        <span class="shrink-0 text-[10px]">🎂</span>
                                                        <span class="truncate">{{ $bdayName }}</span>
                                                    </div>
                                                @endforeach
                                            @endif

                                            <!-- Leave Pill -->
                                            @if($cell['leaves']->count() > 0)
                                                <div class="truncate px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/60 flex items-center gap-1" title="{{ $cell['leaves']->count() }} staff scheduled on leave">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                                    <span class="truncate">{{ $cell['leaves']->count() }} {{ Str::plural('Leave', $cell['leaves']->count()) }}</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- MODE 2: AGENDA / LIST VIEW -->
                <div x-show="viewMode === 'agenda'" x-cloak class="mt-4 space-y-3">
                    <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Upcoming Holidays & Scheduled Leaves ({{ $selectedDate->format('F Y') }})</h3>
                    
                    <!-- Holidays List -->
                    <div class="space-y-2">
                        @forelse($monthHolidays as $h)
                            <div class="p-3 bg-slate-50 dark:bg-[#111927] rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl font-bold text-xs flex items-center justify-center {{ str_contains(strtolower($h->title), 'poya') ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200/80 dark:border-amber-900/60' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-900/60' }}">
                                        {{ \Carbon\Carbon::parse($h->date)->format('d') }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                            <span>{{ $h->title }}</span>
                                            @if(str_contains(strtolower($h->title), 'poya')) <span>🌕</span> @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ \Carbon\Carbon::parse($h->date)->format('l, d F Y') }} • {{ $h->category }}</div>
                                    </div>
                                </div>
                                <span class="bg-white dark:bg-[#1a294b] border border-slate-200/80 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-bold px-2.5 py-1 rounded-lg">
                                    {{ $h->type }} Holiday
                                </span>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-slate-400 italic bg-slate-50 dark:bg-[#111927] rounded-xl border border-slate-200/80 dark:border-slate-800">No holidays scheduled for this month.</div>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- Legend Bar (Clean SaaS Minimal Legend) -->
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between text-[11px] font-medium text-slate-500 dark:text-slate-400 shrink-0 gap-3">
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> Today</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Staff Leave</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Public Holiday</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Company Holiday</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span> Birthday</span>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Action Buttons & Right Rail Cards (Matching Reference Mockup) -->
        <div class="flex flex-col gap-3">
            <!-- Action Buttons: Green Apply Leave & Dynamic White/Red Add Holiday -->
            @php
                $activeRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Employee');
                $canAddHoliday = in_array($activeRole, ['HR Lead', 'Super (Admin)']);
            @endphp
            <div class="grid {{ $canAddHoliday ? 'grid-cols-2' : 'grid-cols-1' }} gap-2.5 shrink-0">
                <button @click="openApplyModal('ANNUAL')" class="bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition-all cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4 stroke-[3]"></i>
                    <span>Apply Leave</span>
                </button>

                @if($canAddHoliday)
                <button @click="addCompanyLeaveModal = true" class="bg-white hover:bg-slate-50 text-slate-700 dark:bg-rose-500 dark:hover:bg-rose-600 dark:text-white border border-slate-200/90 dark:border-transparent py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition-all cursor-pointer">
                    <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                    <span>Add Holiday</span>
                </button>
                @endif
            </div>

            <!-- Pending Approvals Card -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-xs flex flex-col space-y-3">
                <div class="flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-100">Pending Approval Requests</h3>
                    </div>
                    <span class="bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 text-[10px] font-bold px-2 py-0.5 rounded-full">{{ count($pendingRequests) }}</span>
                </div>

                <!-- Approval Requests List -->
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    @php
                        $userSystemRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Employee');
                        $isManagerOrAdmin = in_array($userSystemRole, ['HR Lead', 'Super (Admin)', 'Super Admin', 'Manager (Team Approvals)', 'HOD / Manager']);
                    @endphp
                    
                    @forelse($pendingRequests as $req)
                        <div class="bg-slate-50/70 hover:bg-slate-100/80 dark:bg-[#0f172a] dark:hover:bg-[#182338] border border-slate-200/70 dark:border-slate-800 p-3 rounded-xl flex items-center justify-between text-xs transition-colors">
                            <div>
                                <div class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                    <span>{{ $req->employee->user->name ?? 'Employee' }}</span>
                                    <span class="text-slate-400">•</span>
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $req->leaveType->name ?? 'Leave' }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-0.5">
                                    {{ $req->is_half_day ? 'Half Day' : ($req->is_short_leave ? 'Short Leave' : ((float)$req->duration == 1 ? '1 day' : (float)$req->duration . ' days')) }} • {{ $req->start_date }}
                                </div>
                            </div>
                            @if($isManagerOrAdmin)
                                <a href="{{ route('approvals.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors shadow-2xs flex items-center gap-1 shrink-0 cursor-pointer">
                                    <span>Review</span>
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            @else
                                <span class="bg-amber-50 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 text-[10px] font-bold px-2 py-1 rounded-lg border border-amber-200/80 dark:border-amber-800/60 shrink-0">
                                    {{ $req->status ?? 'Pending Approval' }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="text-[10px] text-slate-400 dark:text-slate-500 italic py-4 text-center bg-slate-50/50 dark:bg-[#0f172a] rounded-xl border border-slate-200/80 dark:border-slate-800">
                            {{ $isManagerOrAdmin ? 'No pending approval requests.' : 'You have no pending leave applications.' }}
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- BOTTOM CARD: TODAY'S EVENTS & BIRTHDAYS (COMPACT CARD FOR TODAY) -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-3.5 shadow-xs flex flex-col space-y-2">
                <div class="flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-amber-500 dark:text-amber-400"></i>
                        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-100">Today's Events & Birthdays</h3>
                    </div>
                    <span class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-slate-200/80 dark:border-slate-700" x-text="selectedDay ? selectedDay.formatted_date : '{{ \Carbon\Carbon::now('Asia/Colombo')->format('M d, Y') }}'"></span>
                </div>

                <!-- Selected Day / Today Event & Birthday Details -->
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <!-- 1. Employee Birthdays (Matching Celebratory Pink Card in Reference Mockup) -->
                    <template x-if="selectedDay?.birthdays && selectedDay?.birthdays.length > 0">
                        <div class="p-3 bg-rose-50/80 dark:bg-rose-950/30 rounded-xl border border-rose-200/80 dark:border-rose-900/50 space-y-1">
                            <div class="text-[10px] font-black uppercase text-rose-600 dark:text-rose-400 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="cake" class="w-4 h-4 text-rose-500"></i>
                                <span>EMPLOYEE BIRTHDAY TODAY! 🎂</span>
                            </div>
                            <template x-for="bday in selectedDay.birthdays" :key="bday">
                                <div class="font-bold text-xs text-slate-800 dark:text-slate-200" x-text="bday + '\'s Birthday 🎉'"></div>
                            </template>
                        </div>
                    </template>

                    <!-- Fallback when no birthday today: Shows Holiday or Friendly Schedule status -->
                    <template x-if="!selectedDay?.birthdays || selectedDay?.birthdays.length === 0">
                        <div>
                            <template x-if="selectedDay?.holiday">
                                <div class="p-3 bg-amber-50/80 dark:bg-amber-950/30 rounded-xl border border-amber-200/80 dark:border-amber-900/50 space-y-1">
                                    <div class="text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400 tracking-wider flex items-center gap-1.5">
                                        <i data-lucide="landmark" class="w-4 h-4 text-amber-500"></i>
                                        <span>Holiday Scheduled Today</span>
                                    </div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200" x-text="selectedDay?.holiday?.title"></div>
                                </div>
                            </template>
                            <template x-if="!selectedDay?.holiday">
                                <div class="p-3 bg-slate-50 dark:bg-[#0f172a] rounded-xl border border-slate-200/70 dark:border-slate-800 text-center text-xs text-slate-400">
                                    No upcoming events or birthdays today.
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

        </div>

    </div>



    <!-- INTERACTIVE DAY DETAIL MODAL -->
    <div x-show="dayModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div @click.away="dayModalOpen = false" class="bg-white dark:bg-[#161b22] rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 dark:border-[#30363d]">
            <template x-if="selectedDay">
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#21262d] pb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-900 dark:text-slate-100" x-text="selectedDay.formatted_date"></h3>
                                <template x-if="selectedDay.is_today">
                                    <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">Today</span>
                                </template>
                            </div>
                            <span class="text-xs text-slate-400 dark:text-slate-500 font-semibold" x-text="selectedDay.is_weekend ? 'Weekend (Non-Working Day)' : 'Standard Working Day'"></span>
                        </div>
                        <button @click="dayModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-lg"></i></button>
                    </div>

                    <!-- Holiday Information Card -->
                    <template x-if="selectedDay.holiday">
                        <div class="p-4 rounded-xl border space-y-2.5" :class="selectedDay.is_poya ? 'bg-amber-50 dark:bg-[#2a1f0e] border-amber-200 dark:border-amber-700 text-amber-950 dark:text-amber-200' : 'bg-emerald-50 dark:bg-[#0f2318] border-emerald-200 dark:border-emerald-700 text-emerald-950 dark:text-emerald-200'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="landmark" class="w-4 h-4 text-amber-700 dark:text-amber-400"></i>
                                    <span>Holiday Details</span>
                                </span>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-white dark:bg-[#21262d] text-slate-900 dark:text-slate-200 border border-slate-200 dark:border-[#30363d]" x-text="selectedDay.holiday.category || selectedDay.holiday.type || 'Holiday'"></span>
                            </div>
                            <h4 class="text-sm font-black" x-text="selectedDay.holiday.title"></h4>
                            <p class="text-xs font-medium opacity-80" x-text="selectedDay.holiday.description || (selectedDay.holiday.category ? selectedDay.holiday.category + ' Holiday' : 'Holiday')"></p>

                            <!-- Mercantile Coverage Badge -->
                            <div class="pt-2 border-t border-slate-200 dark:border-[#30363d] flex items-center justify-between text-xs font-extrabold">
                                <span>Mercantile Holiday Status:</span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black flex items-center gap-1"
                                      :class="(selectedDay.holiday.category || '').includes('Mercantile') ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-[#21262d] text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-[#30363d]'">
                                    <template x-if="(selectedDay.holiday.category || '').includes('Mercantile')">
                                        <span>✓ Mercantile Holiday (Paid Off Day)</span>
                                    </template>
                                    <template x-if="!(selectedDay.holiday.category || '').includes('Mercantile')">
                                        <span>Public & Bank Only (Non-Mercantile)</span>
                                    </template>
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Birthday Celebration Card -->
                    <template x-if="selectedDay.birthdays && selectedDay.birthdays.length > 0">
                        <div class="p-3.5 bg-pink-50 dark:bg-pink-950 rounded-xl border border-pink-200 dark:border-pink-900 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black uppercase tracking-wider text-pink-700 dark:text-pink-300 flex items-center gap-1.5">
                                    <i data-lucide="cake" class="w-4 h-4 text-pink-600 dark:text-pink-400"></i>
                                    <span>Employee Birthday Celebration! 🎂</span>
                                </span>
                                <span class="text-[10px] font-black bg-pink-600 text-white px-2 py-0.5 rounded-full uppercase">Birthday</span>
                            </div>
                            <div class="space-y-1.5 pt-1">
                                <template x-for="bday in selectedDay.birthdays" :key="bday">
                                    <div class="flex items-center gap-2.5 p-2 bg-white dark:bg-[#1c2128] rounded-lg border border-pink-200 dark:border-pink-900">
                                        <div class="w-8 h-8 rounded-full bg-pink-600 text-white font-black text-xs flex items-center justify-center shrink-0 uppercase">
                                            🎂
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-xs text-slate-900 dark:text-slate-100" x-text="bday + '\'s Birthday'"></div>
                                            <div class="text-[10px] text-pink-600 dark:text-pink-400 font-bold">Wish them a Happy Birthday! 🎉</div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Staff on Leave Card -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-black text-slate-700 dark:text-slate-400 uppercase tracking-wider">Employees Scheduled on Leave</h4>
                        <template x-if="selectedDay.leaves && selectedDay.leaves.length > 0">
                            <div class="space-y-2">
                                <template x-for="lv in selectedDay.leaves" :key="lv.id">
                                    <div class="p-3 bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d] flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold" x-text="lv.employee?.user?.name ? lv.employee.user.name.charAt(0) : 'E'"></div>
                                            <div>
                                                <div class="font-bold text-slate-900 dark:text-slate-100" x-text="lv.employee?.user?.name || 'Employee'"></div>
                                                <div class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold" x-text="lv.leave_type?.name || 'Leave'"></div>
                                            </div>
                                        </div>
                                        <span class="bg-white dark:bg-[#21262d] text-purple-800 dark:text-purple-300 text-[10px] font-bold px-2 py-1 rounded-lg border border-purple-200 dark:border-purple-900" x-text="parseFloat(lv.duration) + (parseFloat(lv.duration) === 1 ? ' Day' : ' Days')"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!selectedDay.leaves || selectedDay.leaves.length === 0">
                            <div class="p-3 text-xs text-slate-400 dark:text-slate-500 italic bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d]">No employees scheduled on leave for this date.</div>
                        </template>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-[#21262d]">
                        <button type="button" @click="dayModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-[#21262d] text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-[#2d333b] transition-colors border border-slate-200 dark:border-[#30363d]">Close</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- APPLY FOR LEAVE MODAL (LOOPS HR LEAVE BRIEF COMPLIANT) -->
    <div x-show="applyLeaveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto border border-slate-100 dark:border-[#30363d]" @click.away="applyLeaveModal = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#21262d] pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black">
                        <i class="ph ph-airplane-tilt text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-sm text-slate-900 dark:text-slate-100">Apply for Leave</h3>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 font-bold">Submit a new leave application</p>
                    </div>
                </div>
                <button @click="applyLeaveModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-base"></i></button>
            </div>
            @if($errors->any())
                <div class="bg-rose-50 dark:bg-rose-950 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 p-3 rounded-xl text-xs space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-1.5"><i class="ph ph-warning-circle text-rose-600 dark:text-rose-400"></i> <span>{{ $err }}</span></div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5 text-xs font-semibold">
                @csrf
                
                <!-- Leave Type Selection -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Leave Type *</label>
                    <select name="leave_type_id" 
                            x-model="selectedLeaveTypeId"
                            x-on:change="
                                selectedLeaveTypeCode = leaveCodeMap[selectedLeaveTypeId] || 'ANNUAL';
                                if (selectedLeaveTypeCode === 'SHORT') {
                                    isHalfDay = false;
                                    isShortLeave = true;
                                    targetEndDate = targetStartDate;
                                } else {
                                    isShortLeave = false;
                                }
                            "
                            class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:ring-2 focus:ring-blue-500/30 focus:outline-none">
                        @foreach($leaveBalances as $lb)
                            @if(!in_array(strtolower($lb->leaveType->name ?? ''), ['half day leave', 'half day', 'maternity leave', 'maternity', 'paternity leave', 'paternity']) && !in_array(strtoupper($lb->leaveType->code ?? ''), ['HALF', 'MATERNITY', 'PATERNITY', 'MAT', 'PAT']))
                            @php
                                $isShort = strtoupper($lb->leaveType->code) === 'SHORT';
                                $availText = $isShort ? ($shortData['available'] . ' / 2 available this month') : (max(0, ($lb->allocated + $lb->carried_forward) - $lb->used) . ' days available');
                            @endphp
                            <option value="{{ (string)$lb->leave_type_id }}" data-code="{{ strtoupper($lb->leaveType->code) }}" class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">
                                {{ $lb->leaveType->name }} ({{ $availText }})
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Date Inputs -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date *</label>
                        <input type="date" name="start_date" x-model="targetStartDate" 
                               @change="if (selectedLeaveTypeCode === 'SHORT' || isHalfDay || !targetEndDate || targetEndDate < targetStartDate) targetEndDate = targetStartDate"
                               class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">End Date *</label>
                        <input type="date" name="end_date" x-model="targetEndDate" 
                               :disabled="selectedLeaveTypeCode === 'SHORT' || isHalfDay"
                               class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:opacity-60">
                    </div>
                </div>

                <!-- Live Duration Preview Notice (Weekends & Holidays Excluded) -->
                <div class="p-2.5 bg-blue-50/70 dark:bg-blue-950/40 rounded-xl border border-blue-200 dark:border-blue-900/60 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ph ph-calendar-blank text-blue-600 dark:text-blue-400 text-sm"></i>
                        <span class="text-[11px] text-slate-600 dark:text-slate-400 font-semibold">Total Leave Days:</span>
                    </div>
                    <span class="text-xs font-black text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/60 px-2 py-0.5 rounded-lg" x-text="calculateDuration() + (selectedLeaveTypeCode === 'SHORT' || isHalfDay ? '' : ' Day(s)')"></span>
                </div>

                <!-- Half Day Toggle & Slot Selector (Disabled for Short Leave) -->
                <div x-show="selectedLeaveTypeCode !== 'SHORT'" class="p-3 bg-slate-50 dark:bg-[#21262d] rounded-xl border border-slate-200 dark:border-[#30363d] space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_half_day" value="1" x-model="isHalfDay" @change="if (isHalfDay) targetEndDate = targetStartDate" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="font-bold text-slate-800 dark:text-slate-200">Apply as Half Day Leave (0.5 Days)</span>
                    </label>

                    <div x-show="isHalfDay" class="grid grid-cols-2 gap-2 pt-1">
                        <label class="flex items-center gap-1.5 p-2 bg-white dark:bg-[#1c2128] rounded-lg border border-slate-200 dark:border-[#30363d] text-[11px] text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="radio" name="half_day_slot" value="Morning" checked class="text-blue-600">
                            <span>Morning (9 AM–1 PM)</span>
                        </label>
                        <label class="flex items-center gap-1.5 p-2 bg-white dark:bg-[#1c2128] rounded-lg border border-slate-200 dark:border-[#30363d] text-[11px] text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="radio" name="half_day_slot" value="Afternoon" class="text-blue-600">
                            <span>Afternoon (1 PM–5 PM)</span>
                        </label>
                    </div>
                </div>

                <!-- Short Leave Slot Selector -->
                <div x-show="selectedLeaveTypeCode === 'SHORT'" class="p-3 bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded-xl space-y-2">
                    <span class="font-bold text-amber-900 dark:text-amber-300 text-xs block">Short Leave Slot (Max 2 per month)</span>
                    <input type="hidden" name="is_short_leave" value="1" :disabled="selectedLeaveTypeCode !== 'SHORT'">
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-1.5 p-2 bg-white dark:bg-[#1c2128] rounded-lg border border-amber-200 dark:border-amber-800 text-[11px] text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="radio" name="short_leave_slot" value="9:30 AM–10:00 AM" checked :disabled="selectedLeaveTypeCode !== 'SHORT'" class="text-amber-600">
                            <span>9:30 AM–10:00 AM (Morning)</span>
                        </label>
                        <label class="flex items-center gap-1.5 p-2 bg-white dark:bg-[#1c2128] rounded-lg border border-amber-200 dark:border-amber-800 text-[11px] text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="radio" name="short_leave_slot" value="3:30 PM–5:00 PM" :disabled="selectedLeaveTypeCode !== 'SHORT'" class="text-amber-600">
                            <span>3:30 PM–5:00 PM (Evening)</span>
                        </label>
                    </div>
                </div>

                <!-- Duty Leave Project / Client Name Field -->
                <div x-show="selectedLeaveTypeCode === 'DUTY'" class="space-y-1">
                    <label class="block font-bold text-slate-700 dark:text-slate-300">Project / Client Name *</label>
                    <input type="text" name="project_client_name" placeholder="e.g. Dialog Enterprise Offsite Project" class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <!-- Medical Certificate Upload (Required for > 3 days) -->
                <div x-show="selectedLeaveTypeCode === 'MEDICAL'" class="p-3 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-900 rounded-xl space-y-1.5">
                    <label class="block font-bold text-blue-900 dark:text-blue-300">Upload Medical Certificate</label>
                    <p class="text-[10px] text-blue-700 dark:text-blue-400 font-medium">Required if sick leave exceeds 3 consecutive days (PDF, JPG, PNG).</p>
                    <input type="file" name="medical_certificate_file" class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                </div>

                <!-- Optional Covering Person Selection -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Covering Employee (Optional)</label>
                    <select name="covering_employee_id" class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        <option value="" class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">No covering person required</option>
                        @foreach($allEmployees ?? [] as $empOpt)
                            <option value="{{ $empOpt->id }}" class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">{{ $empOpt->user->name ?? 'Employee' }} ({{ $empOpt->employee_id_number }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reason for Leave -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Reason for Leave *</label>
                    <textarea name="reason" rows="2" class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-medium p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30" placeholder="State reason for leave..." required></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="applyLeaveModal = false" class="px-4 py-2 bg-slate-100 dark:bg-[#21262d] text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-[#2d333b] transition-colors border border-slate-200 dark:border-[#30363d]">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-500/20 transition-colors">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Company Holiday Modal -->
    @if($canAddHoliday)
    <div x-show="addCompanyLeaveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100 dark:border-[#30363d]" @click.away="addCompanyLeaveModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#21262d] pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Add Holiday</h3>
                <button @click="addCompanyLeaveModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><i class="ph ph-x text-base"></i></button>
            </div>

            <form action="{{ route('leave.company_holiday') }}" method="POST" class="space-y-3 text-xs font-semibold">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Holiday Title *</label>
                    <input type="text" name="title" placeholder="e.g. Special Corporate Holiday" class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Date *</label>
                        <input type="date" name="date" x-model="targetStartDate" class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30" required>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category *</label>
                        <select name="category" class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <option value="Public & Bank" class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">Public & Bank</option>
                            <option value="Public, Bank & Mercantile" class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">Public, Bank & Mercantile</option>
                            <option value="Company Holiday" class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">Company Holiday</option>
                            <option value="Special Leave" class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">Special Leave</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-medium p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30" placeholder="Additional details..."></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addCompanyLeaveModal = false" class="px-4 py-2 bg-slate-100 dark:bg-[#21262d] text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-[#2d333b] transition-colors border border-slate-200 dark:border-[#30363d]">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 text-white text-xs font-bold rounded-xl hover:bg-rose-700 shadow-md shadow-rose-500/20 transition-colors">Save Holiday</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- QUOTA BREAKDOWN & USAGE HISTORY MODAL -->
    <div x-show="quotaModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div @click.away="quotaModalOpen = false" 
             class="bg-white dark:bg-[#161b22] rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-[#30363d] space-y-5 transform transition-all overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-[#21262d] shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900">
                        <i :data-lucide="selectedQuota?.icon || 'calendar'" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-slate-100" x-text="selectedQuota?.name"></h3>
                    </div>
                </div>
                <button @click="quotaModalOpen = false" class="p-1.5 text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-[#21262d] transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-3 gap-3 shrink-0">
                <!-- Card 1: Total Entitlement -->
                <div class="p-3 bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d] flex flex-col justify-between">
                    <span class="text-[9px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">Total Entitlement</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-xl font-black text-slate-900 dark:text-slate-100" x-text="selectedQuota?.total"></span>
                        <span class="text-xs font-black text-slate-700 dark:text-slate-400" x-text="selectedQuota?.unit"></span>
                    </div>
                    <template x-if="selectedQuota?.carryForward > 0">
                        <span class="text-[8px] font-extrabold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-800 inline-block mt-1" x-text="'+' + selectedQuota?.carryForward + ' Carry Forward Included'"></span>
                    </template>
                </div>

                <!-- Card 2: Used / Taken -->
                <div class="p-3 bg-amber-50 dark:bg-amber-950 rounded-xl border border-amber-200 dark:border-amber-800 flex flex-col justify-between">
                    <span class="text-[9px] font-black text-amber-900 dark:text-amber-300 uppercase tracking-wider">Used / Taken</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-xl font-black text-amber-950 dark:text-amber-100" x-text="selectedQuota?.used"></span>
                        <span class="text-xs font-black text-amber-900 dark:text-amber-300" x-text="selectedQuota?.unit"></span>
                    </div>
                    <span class="text-[8px] font-extrabold text-amber-800 dark:text-amber-400 mt-1" x-text="(selectedQuota?.total > 0 ? Math.round((selectedQuota?.used / selectedQuota?.total) * 100) : 0) + '% Consumed'"></span>
                </div>

                <!-- Card 3: Available Balance -->
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950 rounded-xl border border-emerald-200 dark:border-emerald-800 flex flex-col justify-between">
                    <span class="text-[9px] font-black text-emerald-900 dark:text-emerald-300 uppercase tracking-wider">Available Balance</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-xl font-black text-emerald-950 dark:text-emerald-100" x-text="selectedQuota?.available"></span>
                        <span class="text-xs font-black text-emerald-900 dark:text-emerald-300" x-text="selectedQuota?.unit"></span>
                    </div>
                </div>
            </div>

            <!-- Usage Record History Section -->
            <div class="flex-1 min-h-0 overflow-y-auto space-y-2 pr-1">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="history" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i> My Usage Records
                    </h4>
                    <span class="text-[10px] font-black bg-slate-100 dark:bg-[#21262d] text-slate-900 dark:text-slate-300 px-2 py-0.5 rounded-full border border-slate-200 dark:border-[#30363d]" x-text="(selectedQuota?.records?.length || 0) + ' Records Found'"></span>
                </div>

                <div class="space-y-2">
                    <template x-for="rec in (selectedQuota?.records || [])" :key="rec.id">
                        <div class="p-3 bg-white dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d] hover:border-blue-300 dark:hover:border-blue-500 transition-all flex items-center justify-between text-xs">
                            <div class="space-y-0.5">
                                <div class="font-extrabold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                    <span x-text="rec.start_date === rec.end_date ? rec.start_date : (rec.start_date + ' to ' + rec.end_date)"></span>
                                    <span class="text-[10px] font-black bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-md border border-blue-200 dark:border-blue-900" 
                                          x-text="rec.is_short_leave || ((rec.leave_type?.code || '').toUpperCase() === 'SHORT') ? ('Short Leave' + (rec.short_leave_slot ? ' (' + rec.short_leave_slot + ')' : '')) : (rec.is_half_day ? ('Half Day (' + (rec.half_day_slot || '0.5d') + ')') : parseFloat(rec.duration) + (parseFloat(rec.duration) === 1 ? ' Day' : ' Days'))"></span>
                                </div>
                                <div class="text-[11px] text-slate-600 dark:text-slate-400 font-bold" x-text="rec.reason"></div>
                            </div>
                            <div class="text-right space-y-1">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black block"
                                      :class="{
                                          'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800': rec.status === 'Approved' || rec.status === 'HR Approved',
                                          'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800': rec.status.includes('Pending'),
                                          'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800': rec.status === 'Rejected'
                                      }"
                                      x-text="rec.status"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="!selectedQuota?.records || selectedQuota?.records?.length === 0">
                        <div class="p-6 text-center bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-dashed border-slate-200 dark:border-[#30363d] space-y-1">
                            <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500 dark:text-emerald-400 mx-auto opacity-80"></i>
                            <p class="text-xs font-black text-slate-900 dark:text-slate-100">No usage recorded for <span x-text="selectedQuota?.name"></span> yet.</p>
                            <p class="text-[10px] text-slate-600 dark:text-slate-400 font-bold">Your full entitlement balance is untouched and available for application.</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-slate-100 dark:border-[#21262d] flex items-center justify-between shrink-0">
                <button @click="quotaModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-[#2d333b] font-black text-xs rounded-xl transition-all border border-slate-200 dark:border-[#30363d]">
                    Close
                </button>
                <button @click="quotaModalOpen = false; openApplyModal(selectedQuota?.code)" class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 font-black text-xs rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-4 h-4"></i> Apply for <span x-text="selectedQuota?.name"></span>
                </button>
            </div>

        </div>
    </div>


</div>
@endsection
