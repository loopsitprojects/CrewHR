@extends('layouts.app', ['title' => 'LOOPS HR - Leave Management Dashboard', 'breadcrumb' => 'Dashboard'])

@section('content')
@php
    $leaveTypeMap = collect($leaveBalances ?? [])->mapWithKeys(function($b) {
        return [strtoupper($b->leaveType->code ?? '') => $b->leave_type_id];
    })->toArray();
    $leaveCodeMap = collect($leaveBalances ?? [])->mapWithKeys(function($b) {
        return [$b->leave_type_id => strtoupper($b->leaveType->code ?? '')];
    })->toArray();
@endphp

<div class="flex-1 flex flex-col min-h-0 space-y-4 h-full overflow-hidden" 
     x-data="{ 
         applyLeaveModal: false, 
         addCompanyLeaveModal: false, 
         dayModalOpen: false, 
         quotaModalOpen: false,
         selectedQuota: null,
         allMyHistory: {{ json_encode($myLeaveHistory) }},
         leaveTypesMap: {{ json_encode($leaveTypeMap) }},
         leaveCodeMap: {{ json_encode($leaveCodeMap) }},
         selectedLeaveTypeId: '{{ $leaveTypeMap['ANNUAL'] ?? ($leaveBalances->first()->leave_type_id ?? '') }}',
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
             if (!this.targetStartDate || !this.targetEndDate) return '0';
             
             let start = new Date(this.targetStartDate + 'T00:00:00');
             let end = new Date(this.targetEndDate + 'T00:00:00');
             if (start > end) return '0';

             let count = 0;
             let curr = new Date(start);
             while (curr <= end) {
                 let dayOfWeek = curr.getDay(); // 0 = Sun, 6 = Sat
                 let isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
                 let y = curr.getFullYear();
                 let m = String(curr.getMonth() + 1).padStart(2, '0');
                 let d = String(curr.getDate()).padStart(2, '0');
                 let dateStr = `${y}-${m}-${d}`;
                 let isHoliday = this.allHolidays.includes(dateStr);

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
             if (this.leaveTypesMap[targetCode]) {
                 this.selectedLeaveTypeId = this.leaveTypesMap[targetCode];
             }
             if (targetCode === 'SHORT') {
                 this.isHalfDay = false;
                 this.isShortLeave = true;
                 this.targetEndDate = this.targetStartDate;
             } else {
                 this.isShortLeave = false;
             }
             this.applyLeaveModal = true;
             this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
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

    <!-- Top Leave Summary Cards (6 Quotas - Clickable Popups - Full Width Title Header Layout) -->
    <div class="shrink-0 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5">
        
        <!-- 1. Annual Leave -->
        <div @click="openQuotaModal('ANNUAL', 'Annual Leave', 'plane', 'blue', {{ $annualData['total'] }}, {{ $annualData['used'] }}, {{ $annualData['available'] }}, {{ $annualData['cf'] }}, 'days')"
             class="bg-white dark:bg-[#161b22] p-3 rounded-2xl border border-slate-200 dark:border-[#30363d] shadow-sm hover:shadow-md hover:shadow-blue-500/10 hover:border-blue-300 dark:hover:border-blue-500 flex flex-col justify-between transition-all cursor-pointer hover:-translate-y-0.5 min-h-[112px]">
            <!-- Top Header: Icon + Full Title + Carry Forward Badge -->
            <div class="flex items-center justify-between gap-1 shrink-0 pb-1.5 border-b border-slate-100 dark:border-[#21262d]">
                <div class="flex items-center gap-1.5 min-w-0">
                    <i data-lucide="plane" class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0"></i>
                    <span class="text-xs font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">Annual Leave</span>
                </div>
                @if($annualData['cf'] > 0)
                    <span class="text-[9px] font-black text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-950 px-1.5 py-0.5 rounded-full border border-amber-200 dark:border-amber-800 shrink-0 leading-none" title="{{ $annualData['cf'] }} Carry Forward Days Allocated">+{{ $annualData['cf'] }} CF</span>
                @endif
            </div>

            <!-- Content: Available Count + Donut Ring -->
            <div class="flex items-center justify-between pt-1">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block leading-none">AVAILABLE</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-2xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $annualData['available'] }}</span>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">days</span>
                    </div>
                </div>
                <!-- Donut Ring -->
                <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                    <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-slate-200 dark:text-[#21262d]" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-blue-600 dark:text-blue-400" stroke-dasharray="{{ $annualData['pct'] }}, 100" stroke-width="3.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="absolute text-[10px] font-black text-slate-900 dark:text-slate-100">{{ $annualData['available'] }}/{{ $annualData['total'] }}</span>
                </div>
            </div>
        </div>

        <!-- 2. Casual Leave -->
        <div @click="openQuotaModal('CASUAL', 'Casual Leave', 'calendar-check', 'purple', {{ $casualData['total'] }}, {{ $casualData['used'] }}, {{ $casualData['available'] }}, {{ $casualData['cf'] }}, 'days')"
             class="bg-white dark:bg-[#161b22] p-3 rounded-2xl border border-slate-200 dark:border-[#30363d] shadow-sm hover:shadow-md hover:shadow-purple-500/10 hover:border-purple-300 dark:hover:border-purple-500 flex flex-col justify-between transition-all cursor-pointer hover:-translate-y-0.5 min-h-[112px]">
            <!-- Top Header: Icon + Full Title -->
            <div class="flex items-center gap-1.5 shrink-0 pb-1.5 border-b border-slate-100 dark:border-[#21262d]">
                <i data-lucide="calendar-check" class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0"></i>
                <span class="text-xs font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">Casual Leave</span>
            </div>

            <!-- Content: Available Count + Donut Ring -->
            <div class="flex items-center justify-between pt-1">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block leading-none">AVAILABLE</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-2xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $casualData['available'] }}</span>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">days</span>
                    </div>
                </div>
                <!-- Donut Ring -->
                <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                    <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-slate-200 dark:text-[#21262d]" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-purple-600 dark:text-purple-400" stroke-dasharray="{{ $casualData['pct'] }}, 100" stroke-width="3.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="absolute text-[10px] font-black text-slate-900 dark:text-slate-100">{{ $casualData['available'] }}/{{ $casualData['total'] }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Medical Leave -->
        <div @click="openQuotaModal('MEDICAL', 'Medical Leave', 'activity', 'emerald', {{ $medicalData['total'] }}, {{ $medicalData['used'] }}, {{ $medicalData['available'] }}, {{ $medicalData['cf'] }}, 'days')"
             class="bg-white dark:bg-[#161b22] p-3 rounded-2xl border border-slate-200 dark:border-[#30363d] shadow-sm hover:shadow-md hover:shadow-emerald-500/10 hover:border-emerald-300 dark:hover:border-emerald-500 flex flex-col justify-between transition-all cursor-pointer hover:-translate-y-0.5 min-h-[112px]">
            <!-- Top Header: Icon + Full Title -->
            <div class="flex items-center gap-1.5 shrink-0 pb-1.5 border-b border-slate-100 dark:border-[#21262d]">
                <i data-lucide="activity" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                <span class="text-xs font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">Medical Leave</span>
            </div>

            <!-- Content: Available Count + Donut Ring -->
            <div class="flex items-center justify-between pt-1">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block leading-none">AVAILABLE</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-2xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $medicalData['available'] }}</span>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">days</span>
                    </div>
                </div>
                <!-- Donut Ring -->
                <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                    <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-slate-200 dark:text-[#21262d]" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-emerald-600 dark:text-emerald-400" stroke-dasharray="{{ $medicalData['pct'] }}, 100" stroke-width="3.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="absolute text-[10px] font-black text-slate-900 dark:text-slate-100">{{ $medicalData['available'] }}/{{ $medicalData['total'] }}</span>
                </div>
            </div>
        </div>

        <!-- 4. Short Leave -->
        <div @click="openQuotaModal('SHORT', 'Short Leave', 'clock', 'amber', {{ $shortData['total'] }}, {{ $shortData['used'] }}, {{ $shortData['available'] }}, {{ $shortData['cf'] }}, 'per month')"
             class="bg-white dark:bg-[#161b22] p-3 rounded-2xl border border-slate-200 dark:border-[#30363d] shadow-sm hover:shadow-md hover:shadow-amber-500/10 hover:border-amber-300 dark:hover:border-amber-500 flex flex-col justify-between transition-all cursor-pointer hover:-translate-y-0.5 min-h-[112px]">
            <!-- Top Header: Icon + Full Title -->
            <div class="flex items-center gap-1.5 shrink-0 pb-1.5 border-b border-slate-100 dark:border-[#21262d]">
                <i data-lucide="clock" class="w-4 h-4 text-amber-500 dark:text-amber-400 shrink-0"></i>
                <span class="text-xs font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">Short Leave</span>
            </div>

            <!-- Content: Available Count + Donut Ring -->
            <div class="flex items-center justify-between pt-1">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block leading-none">AVAILABLE</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-2xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $shortData['available'] }}</span>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">/ month</span>
                    </div>
                </div>
                <!-- Donut Ring -->
                <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                    <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-slate-200 dark:text-[#21262d]" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-amber-500 dark:text-amber-400" stroke-dasharray="{{ $shortData['pct'] }}, 100" stroke-width="3.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="absolute text-[10px] font-black text-slate-900 dark:text-slate-100">{{ $shortData['available'] }}/{{ $shortData['total'] }}</span>
                </div>
            </div>
        </div>

        <!-- 5. Duty Leave -->
        <div @click="openQuotaModal('DUTY', 'Duty Leave', 'briefcase', 'rose', {{ $dutyData['total'] }}, {{ $dutyData['used'] }}, {{ $dutyData['available'] }}, {{ $dutyData['cf'] }}, 'days')"
             class="bg-white dark:bg-[#161b22] p-3 rounded-2xl border border-slate-200 dark:border-[#30363d] shadow-sm hover:shadow-md hover:shadow-rose-500/10 hover:border-rose-400 dark:hover:border-rose-500 flex flex-col justify-between transition-all cursor-pointer hover:-translate-y-0.5 min-h-[112px]">
            <!-- Top Header: Icon + Full Title -->
            <div class="flex items-center gap-1.5 shrink-0 pb-1.5 border-b border-slate-100 dark:border-[#21262d]">
                <i data-lucide="briefcase" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
                <span class="text-xs font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">Duty Leave</span>
            </div>

            <!-- Content: Available Count + Donut Ring -->
            <div class="flex items-center justify-between pt-1">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block leading-none">AVAILABLE</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-2xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $dutyData['available'] }}</span>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">days</span>
                    </div>
                </div>
                <!-- Donut Ring -->
                <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                    <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-slate-200 dark:text-[#21262d]" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-rose-600 dark:text-rose-400" stroke-dasharray="{{ $dutyData['pct'] }}, 100" stroke-width="3.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="absolute text-[9px] font-black text-slate-900 dark:text-slate-100">{{ $dutyData['available'] }}/{{ $dutyData['total'] }}</span>
                </div>
            </div>
        </div>

        <!-- 6. Lieu Leave -->
        <div @click="openQuotaModal('LIEU', 'Lieu Leave', 'refresh-cw', 'teal', {{ $lieuData['total'] }}, {{ $lieuData['used'] }}, {{ $lieuData['available'] }}, {{ $lieuData['cf'] }}, 'days')"
             class="bg-white dark:bg-[#161b22] p-3 rounded-2xl border border-slate-200 dark:border-[#30363d] shadow-sm hover:shadow-md hover:shadow-teal-500/10 hover:border-teal-300 dark:hover:border-teal-500 flex flex-col justify-between transition-all cursor-pointer hover:-translate-y-0.5 min-h-[112px]">
            <!-- Top Header: Icon + Full Title -->
            <div class="flex items-center gap-1.5 shrink-0 pb-1.5 border-b border-slate-100 dark:border-[#21262d]">
                <i data-lucide="refresh-cw" class="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0"></i>
                <span class="text-xs font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">Lieu Leave</span>
            </div>

            <!-- Content: Available Count + Donut Ring -->
            <div class="flex items-center justify-between pt-1">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block leading-none">AVAILABLE</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-2xl font-black text-slate-900 dark:text-slate-100 leading-none">{{ $lieuData['available'] }}</span>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">days</span>
                    </div>
                </div>
                <!-- Donut Ring -->
                <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                    <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-slate-200 dark:text-[#21262d]" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-teal-600 dark:text-teal-400" stroke-dasharray="{{ $lieuData['pct'] }}, 100" stroke-width="3.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="absolute text-[10px] font-black text-slate-900 dark:text-slate-100">{{ $lieuData['available'] }}/{{ $lieuData['total'] }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Main Grid Section (Flexible Height for 100vh fit) -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-3 min-h-0 overflow-hidden mt-2.5">

        <!-- Left 2 Cols: Monthly Calendar View -->
        <div class="lg:col-span-2 bg-white dark:bg-[#161b22] rounded-xl border border-slate-200 dark:border-[#30363d] p-3 shadow-xl shadow-slate-200/60 dark:shadow-none flex flex-col justify-between min-h-0 overflow-hidden">
            <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
                
                <!-- Calendar Top Controls Bar -->
                <div class="flex flex-wrap items-center justify-between pb-3 border-b border-slate-100 dark:border-[#21262d] shrink-0 gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-black text-slate-900 dark:text-slate-100 tracking-tight">{{ $selectedDate->format('F Y') }}</h2>
                                <span class="bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-amber-200 dark:border-amber-800">
                                    {{ $gazetteCount }} Gazette Holidays
                                </span>
                            </div>
                            <span class="text-[10px] font-black text-slate-500 dark:text-slate-500">Asia/Colombo (GMT+5:30) • Sri Lankan Gazette Calendar</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap">
                        <!-- Month & Year Quick Selector Form -->
                        <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2">
                            <!-- Custom Styled Month Select -->
                            <div class="relative flex items-center">
                                <select name="month" onchange="this.form.submit()" class="appearance-none bg-slate-100 dark:bg-[#21262d] hover:bg-slate-200 dark:hover:bg-[#2d333b] border border-slate-200 dark:border-[#30363d] rounded-full text-xs font-bold text-slate-900 dark:text-slate-200 py-1.5 pl-3.5 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                                    @foreach($monthsList as $num => $mName)
                                        <option value="{{ $num }}" {{ $num == $month ? 'selected' : '' }} class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">{{ $mName }}</option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 absolute right-2.5 pointer-events-none"></i>
                            </div>

                            <!-- Custom Styled Year Select -->
                            <div class="relative flex items-center">
                                <select name="year" onchange="this.form.submit()" class="appearance-none bg-slate-100 dark:bg-[#21262d] hover:bg-slate-200 dark:hover:bg-[#2d333b] border border-slate-200 dark:border-[#30363d] rounded-full text-xs font-bold text-slate-900 dark:text-slate-200 py-1.5 pl-3.5 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                                    @foreach($yearsList as $y)
                                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }} class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">{{ $y }}</option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 absolute right-2.5 pointer-events-none"></i>
                            </div>
                        </form>

                        <!-- Prev / Today / Next Controls -->
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('dashboard') }}" class="px-3.5 py-1.5 bg-slate-100 dark:bg-[#21262d] hover:bg-slate-200 dark:hover:bg-[#2d333b] text-slate-800 dark:text-slate-200 text-xs font-bold rounded-full border border-slate-200 dark:border-[#30363d] transition-all">Today</a>
                            <a href="{{ route('dashboard', ['year' => $prevMonthDate->year, 'month' => $prevMonthDate->month]) }}" class="w-7 h-7 bg-slate-100 dark:bg-[#21262d] hover:bg-slate-200 dark:hover:bg-[#2d333b] text-slate-700 dark:text-slate-300 rounded-full border border-slate-200 dark:border-[#30363d] flex items-center justify-center transition-all" title="Previous Month">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>
                            <a href="{{ route('dashboard', ['year' => $nextMonthDate->year, 'month' => $nextMonthDate->month]) }}" class="w-7 h-7 bg-slate-100 dark:bg-[#21262d] hover:bg-slate-200 dark:hover:bg-[#2d333b] text-slate-700 dark:text-slate-300 rounded-full border border-slate-200 dark:border-[#30363d] flex items-center justify-center transition-all" title="Next Month">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- MODE 1: GRID VIEW -->
                <div x-show="viewMode === 'grid'" class="flex-1 flex flex-col min-h-0 mt-3">
                    <div class="grid grid-cols-7 text-center text-[11px] font-black uppercase tracking-wider py-1.5 border-b border-slate-200/80 dark:border-[#21262d] shrink-0">
                        <div class="text-slate-500 dark:text-slate-500">Mon</div><div class="text-slate-500 dark:text-slate-500">Tue</div><div class="text-slate-500 dark:text-slate-500">Wed</div><div class="text-slate-500 dark:text-slate-500">Thu</div><div class="text-slate-500 dark:text-slate-500">Fri</div><div class="text-rose-500 dark:text-rose-500">Sat</div><div class="text-rose-500 dark:text-rose-500">Sun</div>
                    </div>

                    <div class="grid grid-cols-7 gap-1.5 mt-1.5 flex-1 overflow-visible min-h-0" style="grid-template-rows: repeat({{ (int)ceil(count($calendarDays) / 7) }}, minmax(0, 1fr));">
                        @foreach($calendarDays as $cell)
                            @if(!$cell['is_current_month'])
                                <div class="h-full w-full p-1.5 bg-slate-50 dark:bg-[#0d1117] rounded-xl border border-slate-100 dark:border-[#21262d]/60"></div>
                            @else
                                <div @click="selectedDay = {{ json_encode($cell) }}; dayModalOpen = true; targetStartDate = '{{ $cell['date'] }}'; targetEndDate = '{{ $cell['date'] }}'" 
                                     :class="selectedDay && selectedDay.date === '{{ $cell['date'] }}' ? 'outline outline-2 outline-blue-500 dark:outline-blue-400 border-blue-500 dark:border-blue-400 z-10' : ''"
                                     class="h-full w-full p-1.5 rounded-xl flex flex-col justify-between transition-all duration-150 cursor-pointer border group relative min-h-0 overflow-hidden
                                    {{ $cell['is_today'] ? 'bg-blue-50 dark:bg-[#1c2f4a] border-blue-400 dark:border-blue-500 text-blue-950 dark:text-blue-100 shadow-md dark:shadow-blue-950/60' : '' }}
                                    {{ !$cell['is_today'] && $cell['is_poya'] ? 'bg-amber-50 dark:bg-[#2a1f0e] border-amber-300 dark:border-amber-700 text-amber-950 dark:text-amber-200' : '' }}
                                    {{ !$cell['is_today'] && !$cell['is_poya'] && $cell['holiday'] ? 'bg-emerald-50 dark:bg-[#0f2318] border-emerald-300 dark:border-emerald-700 text-emerald-950 dark:text-emerald-200' : '' }}
                                    {{ !$cell['is_today'] && !$cell['holiday'] && $cell['is_weekend'] ? 'bg-slate-50 dark:bg-[#1a1f26] border-slate-200 dark:border-[#2d333b] text-rose-900 dark:text-rose-400' : '' }}
                                    {{ !$cell['is_today'] && !$cell['holiday'] && !$cell['is_weekend'] ? 'bg-white dark:bg-[#1c2128] border-slate-200 dark:border-[#30363d] hover:bg-slate-50 dark:hover:bg-[#22272e] hover:border-blue-300 dark:hover:border-[#388bfd] text-slate-800 dark:text-slate-200' : '' }}
                                ">
                                    <!-- Top Row: Date Number & Centered Today Badge -->
                                    <div class="relative flex items-center justify-between shrink-0">
                                        <span class="text-sm font-black tracking-tight leading-none {{ $cell['is_today'] ? 'text-blue-700 dark:text-blue-300 font-extrabold text-base' : ($cell['is_weekend'] ? 'text-rose-700 dark:text-rose-400' : ($cell['is_poya'] ? 'text-amber-800 dark:text-amber-300' : ($cell['holiday'] ? 'text-emerald-800 dark:text-emerald-300' : 'text-slate-800 dark:text-slate-300'))) }}">{{ $cell['day'] }}</span>
                                        @if($cell['is_today'])
                                            <div class="absolute left-1/2 -translate-x-1/2 -top-0.5">
                                                <span class="text-[8px] bg-blue-600 dark:bg-blue-500 text-white px-1.5 py-0.5 rounded font-black tracking-wider uppercase shadow-2xs leading-none inline-block">Today</span>
                                            </div>
                                        @endif
                                    </div>
                                    <!-- Middle Section: Centered Direct Icons with clear top spacing -->
                                     <div class="flex-1 flex items-center justify-center min-h-0 pt-2 pb-0.5">
                                         <div class="flex flex-wrap items-center justify-center gap-2">
                                             
                                             <!-- 1. Distinct Icon for Gazette Public vs Company Holidays -->
                                             @if($cell['holiday'])
                                                 @if($cell['holiday']->type === 'Company')
                                                     <i data-lucide="building-2" class="w-5 h-5 text-rose-500 dark:text-rose-400 shrink-0" title="🏢 {{ $cell['holiday']->title }} (Company Holiday)"></i>
                                                 @else
                                                     <i data-lucide="landmark" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" title="🏛️ {{ $cell['holiday']->title }} ({{ $cell['holiday']->category }})"></i>
                                                 @endif
                                             @endif

                                             <!-- 2. Employee Birthday Icons -->
                                             @if(isset($cell['birthdays']) && count($cell['birthdays']) > 0)
                                                 <i data-lucide="cake" class="w-5 h-5 text-pink-500 dark:text-pink-400 shrink-0" title="🎂 {{ implode(', ', $cell['birthdays']) }}'s Birthday!"></i>
                                             @endif

                                             <!-- 3. Employee Leave Icons (Direct Icons without Outer Box) -->
                                             @if($cell['leaves']->count() > 0)
                                                 @foreach($cell['leaves']->take(3) as $lv)
                                                     @php
                                                         $leaveCode = strtoupper($lv->leaveType->code ?? 'CASUAL');
                                                     @endphp
                                                     @if($leaveCode === 'ANNUAL')
                                                         <i data-lucide="plane" class="w-5 h-5 text-blue-500 dark:text-blue-400 shrink-0" title="{{ $lv->employee->user->name ?? 'Employee' }} - Annual Leave"></i>
                                                     @elseif($leaveCode === 'CASUAL')
                                                         <i data-lucide="calendar-check" class="w-5 h-5 text-purple-500 dark:text-purple-400 shrink-0" title="{{ $lv->employee->user->name ?? 'Employee' }} - Casual Leave"></i>
                                                     @elseif($leaveCode === 'MEDICAL')
                                                         <i data-lucide="activity" class="w-5 h-5 text-emerald-500 dark:text-emerald-400 shrink-0" title="{{ $lv->employee->user->name ?? 'Employee' }} - Medical Leave"></i>
                                                     @elseif($leaveCode === 'SHORT')
                                                         <i data-lucide="clock" class="w-5 h-5 text-amber-500 dark:text-amber-400 shrink-0" title="{{ $lv->employee->user->name ?? 'Employee' }} - Short Leave"></i>
                                                     @elseif($leaveCode === 'DUTY')
                                                         <i data-lucide="briefcase" class="w-5 h-5 text-rose-500 dark:text-rose-400 shrink-0" title="{{ $lv->employee->user->name ?? 'Employee' }} - Duty Leave"></i>
                                                     @else
                                                         <i data-lucide="refresh-cw" class="w-5 h-5 text-teal-500 dark:text-teal-400 shrink-0" title="{{ $lv->employee->user->name ?? 'Employee' }} - Lieu Leave"></i>
                                                     @endif
                                                 @endforeach
                                                 @if($cell['leaves']->count() > 3)
                                                     <span class="text-[10px] text-slate-600 dark:text-slate-400 font-black">+{{ $cell['leaves']->count() - 3 }}</span>
                                                 @endif
                                             @endif

                                         </div>
                                     </div>
                                 </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- MODE 2: AGENDA / LIST VIEW -->
                <div x-show="viewMode === 'agenda'" x-cloak class="flex-1 flex flex-col min-h-0 mt-3 overflow-y-auto space-y-3">
                    <h3 class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-wider">Upcoming Gazette Holidays & Scheduled Leaves ({{ $selectedDate->format('F Y') }})</h3>
                    
                    <!-- Gazette Holidays List -->
                    <div class="space-y-2">
                        @forelse($monthHolidays as $h)
                            <div class="p-3 bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d] flex items-center justify-between text-xs">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl font-black text-xs flex items-center justify-center {{ str_contains(strtolower($h->title), 'poya') ? 'bg-amber-100 dark:bg-amber-950 text-amber-900 dark:text-amber-300 border border-amber-300 dark:border-amber-700' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-900 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' }}">
                                        {{ \Carbon\Carbon::parse($h->date)->format('d') }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                            <span>{{ $h->title }}</span>
                                            @if(str_contains(strtolower($h->title), 'poya')) <span>🌕</span> @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold">{{ \Carbon\Carbon::parse($h->date)->format('l, d F Y') }} • {{ $h->category }}</div>
                                    </div>
                                </div>
                                <span class="bg-white dark:bg-[#21262d] border border-slate-200 dark:border-[#30363d] text-slate-700 dark:text-slate-300 text-[10px] font-extrabold px-2.5 py-1 rounded-lg">
                                    {{ $h->type }} Holiday
                                </span>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-slate-400 italic bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d]">No gazette holidays scheduled for this month.</div>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- Legend Bar -->
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-[#21262d] flex flex-wrap items-center justify-between text-[10px] font-bold text-slate-500 dark:text-slate-500 shrink-0 gap-2">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 dark:bg-blue-500 ring-2 ring-blue-200 dark:ring-blue-900"></span> Today</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-500 dark:bg-purple-500"></span> Staff Leave</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1"><i data-lucide="landmark" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 inline"></i> Gazette Holiday</span>
                    <span class="flex items-center gap-1"><i data-lucide="building-2" class="w-3.5 h-3.5 text-rose-500 dark:text-rose-400 inline"></i> Company Holiday</span>
                    <span class="flex items-center gap-1"><i data-lucide="cake" class="w-3.5 h-3.5 text-pink-500 dark:text-pink-400 inline"></i> Birthday</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 dark:bg-emerald-500"></span> Mercantile (M)</span>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Action Buttons, Section 1: General Operations (Top - Expands), Section 2: Events & Birthdays (Bottom - Today Only) -->
        <div class="flex flex-col gap-3 min-h-0 h-full overflow-hidden">
            <!-- Action Buttons -->
            @php
                $activeRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Employee');
                $canAddHoliday = in_array($activeRole, ['HR Lead', 'Super (Admin)']);
            @endphp
            <div class="grid {{ $canAddHoliday ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 shrink-0">
                <button @click="applyLeaveModal = true" class="bg-emerald-600 hover:bg-emerald-700 text-white p-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-md shadow-emerald-500/20 transition-all">
                    <i class="ph ph-plus-circle text-base"></i>
                    <span>Apply Leave</span>
                </button>

                @if($canAddHoliday)
                <button @click="addCompanyLeaveModal = true" class="bg-rose-600 hover:bg-rose-700 text-white p-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-md shadow-rose-500/20 transition-all">
                    <i class="ph ph-calendar-plus text-base"></i>
                    <span>Add Holiday</span>
                </button>
                @endif
            </div>

            <!-- Container for Height Split -->
            <div class="flex-1 flex flex-col gap-3 min-h-0 overflow-hidden">

                <!-- TOP CARD: GENERAL OPERATIONS (EXPANDS TO FILL HEIGHT) -->
                <div class="flex-1 bg-white dark:bg-[#161b22] rounded-2xl border border-slate-200 dark:border-[#30363d] p-4 shadow-sm flex flex-col min-h-0 overflow-hidden space-y-3">
                    <div class="flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-sliders-horizontal text-blue-600 dark:text-blue-400 text-base"></i>
                            <h3 class="text-xs font-black text-slate-900 dark:text-slate-100">General Operations</h3>
                        </div>
                        <span class="bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-blue-200 dark:border-blue-900">Approvals & Workflow</span>
                    </div>

                    <!-- Approval Requests & Operations Container -->
                    <div class="flex-1 bg-slate-50 dark:bg-[#0d1117] border border-slate-200 dark:border-[#21262d] rounded-xl p-3 space-y-2.5 flex flex-col min-h-0 overflow-hidden">
                        @php
                            $userSystemRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Employee');
                            $isManagerOrAdmin = in_array($userSystemRole, ['HR Lead', 'Super (Admin)', 'Super Admin', 'Manager (Team Approvals)', 'HOD / Manager']);
                        @endphp
                        
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-[#21262d] pb-1.5 shrink-0">
                            <span class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <i class="ph ph-check-square-offset text-blue-600 dark:text-blue-400 text-base"></i>
                                <span>{{ $isManagerOrAdmin ? 'Pending Approval Requests' : 'My Submitted Requests' }}</span>
                            </span>
                            <span class="bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 text-[10px] font-black px-2 py-0.5 rounded-full border border-blue-200 dark:border-blue-900">{{ count($pendingRequests) }}</span>
                        </div>

                        <div class="flex-1 space-y-2 min-h-0 overflow-y-auto pr-1">
                            @forelse($pendingRequests as $req)
                                <div class="bg-white dark:bg-[#1c2128] border border-slate-200 dark:border-[#30363d] p-2.5 rounded-xl flex items-center justify-between text-xs shadow-sm">
                                    <div>
                                        <div class="font-black text-slate-900 dark:text-slate-100">{{ $req->employee->user->name ?? 'Employee' }}: <span class="text-purple-600 dark:text-purple-400 font-bold">{{ $req->leaveType->name ?? 'Leave' }}</span></div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold">{{ $req->is_half_day ? 'Half Day' : ($req->is_short_leave ? 'Short Leave' : $req->duration . ' working days') }} • {{ $req->start_date }}</div>
                                    </div>
                                    @if($isManagerOrAdmin)
                                        <a href="{{ route('approvals.index') }}" class="bg-blue-600 dark:bg-blue-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-1 shrink-0">
                                            <span>Review</span>
                                            <i class="ph ph-arrow-right text-xs"></i>
                                        </a>
                                    @else
                                        <span class="bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-[10px] font-extrabold px-2 py-1 rounded-lg border border-amber-200 dark:border-amber-800 shrink-0">
                                            {{ $req->status ?? 'Pending Approval' }}
                                        </span>
                                    @endif
                                </div>
                            @empty
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 italic py-2 text-center bg-white dark:bg-[#1c2128] rounded-lg border border-slate-200 dark:border-[#30363d]">
                                    {{ $isManagerOrAdmin ? 'No pending approval requests.' : 'You have no pending leave applications.' }}
                                </div>
                            @endforelse
                        </div>

                        <!-- Active On-Leave Operations for TODAY -->

                    </div>
                </div>

                <!-- BOTTOM CARD: TODAY'S EVENTS & BIRTHDAYS (COMPACT CARD FOR TODAY) -->
                <div class="shrink-0 bg-white dark:bg-[#161b22] rounded-2xl border border-slate-200 dark:border-[#30363d] p-3.5 shadow-sm flex flex-col space-y-2">
                    <div class="flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2">
                            <i data-lucide="sparkles" class="w-4 h-4 text-amber-500 dark:text-amber-400"></i>
                            <h3 class="text-xs font-black text-slate-900 dark:text-slate-100">Today's Events & Birthdays</h3>
                        </div>
                        <span class="bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800" x-text="selectedDay ? selectedDay.formatted_date : '{{ \Carbon\Carbon::now('Asia/Colombo')->format('M d, Y') }}'"></span>
                    </div>

                    <!-- Selected Day / Today Event & Birthday Details -->
                    <div class="bg-slate-50 dark:bg-[#0d1117] border border-slate-200 dark:border-[#21262d] rounded-xl p-2.5 space-y-2 max-h-48 overflow-y-auto">
                        
                        <!-- 1. Employee Birthdays -->
                        <template x-if="selectedDay?.birthdays && selectedDay?.birthdays.length > 0">
                            <div class="p-2 bg-pink-50 dark:bg-pink-950 rounded-lg border border-pink-200 dark:border-pink-900 space-y-0.5">
                                <div class="text-[10px] font-black uppercase text-pink-700 dark:text-pink-300 tracking-wider flex items-center gap-1">
                                    <i data-lucide="cake" class="w-3.5 h-3.5 text-pink-600 dark:text-pink-400"></i>
                                    <span>Employee Birthday Today! 🎂</span>
                                </div>
                                <template x-for="bday in selectedDay.birthdays" :key="bday">
                                    <div class="font-extrabold text-xs text-pink-950 dark:text-pink-200" x-text="bday + '\'s Birthday 🎉'"></div>
                                </template>
                            </div>
                        </template>

                        <!-- 2. Public / Company Holiday -->
                        <template x-if="selectedDay?.holiday">
                            <div class="p-2 rounded-lg border space-y-1"
                                 :class="selectedDay?.holiday?.type === 'Company' ? 'bg-rose-50 dark:bg-rose-950 border-rose-200 dark:border-rose-900' : 'bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-900'">
                                <div class="flex items-center justify-between">
                                    <div class="font-black text-xs text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                        <i :data-lucide="selectedDay?.holiday?.type === 'Company' ? 'building-2' : 'landmark'" :class="selectedDay?.holiday?.type === 'Company' ? 'text-rose-500 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400'" class="w-4 h-4 shrink-0"></i>
                                        <span x-text="selectedDay?.holiday?.title"></span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase"
                                          :class="selectedDay?.holiday?.type === 'Company' ? 'bg-rose-100 dark:bg-rose-950 text-rose-900 dark:text-rose-300 border border-rose-300 dark:border-rose-800' : ((selectedDay?.holiday?.category || '').includes('Mercantile') ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-900 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'bg-amber-100 dark:bg-amber-950 text-amber-900 dark:text-amber-300 border border-amber-300 dark:border-amber-800')"
                                          x-text="selectedDay?.holiday?.type === 'Company' ? 'Company Holiday' : ((selectedDay?.holiday?.category || '').includes('Mercantile') ? 'Mercantile (M)' : 'Public/Bank (P/B)')">
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-bold" x-text="selectedDay?.holiday?.type === 'Company' ? 'Company Holiday' : selectedDay?.holiday?.category"></div>
                            </div>
                        </template>

                        <!-- 3. Scheduled Staff Leaves -->
                        <template x-if="selectedDay?.leaves && selectedDay?.leaves.length > 0">
                            <div class="p-2 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-900 space-y-1">
                                <div class="text-[10px] font-black uppercase text-blue-700 dark:text-blue-300 tracking-wider flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"></i>
                                    <span>Scheduled Staff Leaves</span>
                                </div>
                                <template x-for="lv in selectedDay.leaves" :key="lv.id">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                                        <span x-text="(lv.employee?.user?.name || 'Staff') + ' (' + (lv.leave_type?.name || 'Leave') + ')'"></span>
                                        <span class="text-[10px] font-black text-blue-500 dark:text-blue-400" x-text="lv.is_half_day ? 'Half Day' : (lv.is_short_leave ? 'Short Leave' : lv.duration + 'd')"></span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- 4. Standard Operations Fallback -->
                        <template x-if="!selectedDay?.holiday && (!selectedDay?.birthdays || selectedDay?.birthdays.length === 0) && (!selectedDay?.leaves || selectedDay?.leaves.length === 0)">
                            <div class="text-xs text-slate-600 dark:text-slate-400 font-bold flex items-center gap-1.5 py-1">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 shrink-0"></i>
                                <span>Standard Operations (No Holiday / Birthday Today)</span>
                            </div>
                        </template>
                    </div>
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
                                    <span>Public Gazette Holiday</span>
                                </span>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-white dark:bg-[#21262d] text-slate-900 dark:text-slate-200 border border-slate-200 dark:border-[#30363d]" x-text="selectedDay.holiday.category"></span>
                            </div>
                            <h4 class="text-sm font-black" x-text="selectedDay.holiday.title"></h4>
                            <p class="text-xs font-medium opacity-80" x-text="selectedDay.holiday.description || 'Official Sri Lanka Gazette Holiday'"></p>

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
                                        <span class="bg-white dark:bg-[#21262d] text-purple-800 dark:text-purple-300 text-[10px] font-bold px-2 py-1 rounded-lg border border-purple-200 dark:border-purple-900" x-text="lv.duration + ' Days'"></span>
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
                        <button type="button" @click="dayModalOpen = false; applyLeaveModal = true" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 shadow-sm transition-colors">Apply Leave for this Date</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- APPLY FOR LEAVE MODAL (LOOPS HR LEAVE BRIEF COMPLIANT) -->
    <div x-show="applyLeaveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto border border-slate-100 dark:border-[#30363d]" @click.away="applyLeaveModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#21262d] pb-3">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Apply for Leave Request</h3>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold">LOOPS HR Policy Rules & Pro-rata Allowances Enforced</p>
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
                            <option value="{{ $lb->leave_type_id }}" data-code="{{ strtoupper($lb->leaveType->code) }}" class="bg-white dark:bg-[#1c2128] text-slate-900 dark:text-slate-200">
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
                               @change="if (selectedLeaveTypeCode === 'SHORT') targetEndDate = targetStartDate"
                               class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">End Date *</label>
                        <input type="date" name="end_date" x-model="targetEndDate" 
                               :disabled="selectedLeaveTypeCode === 'SHORT'"
                               class="w-full border border-slate-200 dark:border-[#30363d] bg-slate-50 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 rounded-xl text-xs font-bold p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:opacity-60">
                    </div>
                </div>

                <!-- Live Duration Preview Notice (Weekends & Holidays Excluded) -->
                <div class="p-2.5 bg-blue-50/70 dark:bg-blue-950/40 rounded-xl border border-blue-200 dark:border-blue-900/60 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ph ph-calendar-blank text-blue-600 dark:text-blue-400 text-sm"></i>
                        <span class="text-[11px] text-slate-600 dark:text-slate-400 font-semibold">Total Leave Days:</span>
                    </div>
                    <span class="text-xs font-black text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/60 px-2 py-0.5 rounded-lg" x-text="calculateDuration() + (selectedLeaveTypeCode === 'SHORT' ? '' : ' Day(s)')"></span>
                </div>

                <!-- Half Day Toggle & Slot Selector (Disabled for Short Leave) -->
                <div x-show="selectedLeaveTypeCode !== 'SHORT'" class="p-3 bg-slate-50 dark:bg-[#21262d] rounded-xl border border-slate-200 dark:border-[#30363d] space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_half_day" value="1" x-model="isHalfDay" class="rounded text-blue-600 focus:ring-blue-500">
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
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Add Holiday / Gazette Day</h3>
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
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-black text-slate-900 dark:text-slate-100" x-text="selectedQuota?.name"></h3>
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-900" x-text="(selectedQuota?.code || '') + ' QUOTA'"></span>
                        </div>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Usage breakdown and personal leave record history</p>
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
                        <span class="text-[8px] font-extrabold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-800 inline-block mt-1" x-text="'+' + selectedQuota?.carryForward + ' CF Included'"></span>
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
                    <span class="text-[8px] font-extrabold text-emerald-800 dark:text-emerald-400 mt-1">Ready for Use</span>
                </div>
            </div>

            <!-- Visual Progress Bar -->
            <div class="p-3 bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d] space-y-1.5 shrink-0">
                <div class="flex items-center justify-between text-xs font-black">
                    <span class="text-slate-900 dark:text-slate-200">Usage Progress</span>
                    <span class="text-slate-900 dark:text-slate-200 font-black" x-text="(selectedQuota?.used || 0) + ' of ' + (selectedQuota?.total || 0) + ' ' + (selectedQuota?.unit || '')"></span>
                </div>
                <div class="w-full h-2.5 bg-slate-200 dark:bg-[#21262d] rounded-full overflow-hidden border border-slate-300 dark:border-[#30363d]">
                    <div class="h-full bg-blue-500 dark:bg-blue-500 transition-all duration-500 rounded-full"
                         :style="'width: ' + (selectedQuota?.total > 0 ? Math.min(100, Math.round(((selectedQuota?.used || 0) / selectedQuota?.total) * 100)) : 0) + '%'"></div>
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
                                          x-text="rec.is_short_leave || ((rec.leave_type?.code || '').toUpperCase() === 'SHORT') ? ('Short Leave' + (rec.short_leave_slot ? ' (' + rec.short_leave_slot + ')' : '')) : (rec.is_half_day ? ('Half Day (' + (rec.half_day_slot || '0.5d') + ')') : rec.duration + ' Days')"></span>
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
