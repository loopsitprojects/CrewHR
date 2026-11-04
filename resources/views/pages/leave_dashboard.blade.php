@extends('layouts.app', ['title' => 'Crew HR - Leave Management Dashboard', 'breadcrumb' => 'Dashboard'])

@section('content')
@php
    $balancesByCode = collect($leaveBalances ?? [])->keyBy(function($b) {
        return strtoupper($b->leaveType->code ?? '');
    });

    $formatNum = function($n) {
        $whole = floor($n);
        $rem = $n - $whole;
        if ($rem == 0.5) {
            return ($whole > 0 ? $whole . '½' : '½');
        }
        return (string)$n;
    };

    $shortLeftThisMonth = max(0, 2 - (int)($monthlyShortLeavesUsed ?? 0));

    // Get all active leave types from DB, excluding HALF_DAY (which is handled as a half-day toggle checkbox)
    $activeDbLeaveTypes = \Modules\Leave\Models\LeaveType::where('is_active', true)
        ->get()
        ->reject(fn($lt) => strtoupper($lt->code ?? '') === 'HALF_DAY');

    $leaveTypeMap = [];
    $leaveCodeMap = [];
    foreach ($activeDbLeaveTypes as $ltObj) {
        $c = strtoupper($ltObj->code ?? ('TYPE_' . $ltObj->id));
        $leaveTypeMap[$c] = (string)$ltObj->id;
        $leaveCodeMap[(string)$ltObj->id] = $c;
    }

    $empMap = collect($allEmployees ?? [])->mapWithKeys(function($emp) {
        return [(string)$emp->id => ($emp->user->name ?? 'Employee') . ' (' . $emp->employee_id_number . ')'];
    })->toArray();

    // Standard ordering preference
    $preferredOrder = ['CASUAL', 'MEDICAL', 'ANNUAL', 'LIEU', 'SHORT', 'DUTY', 'NIGHT_SHIFT_RECOVERY'];

    $sortedLeaveTypes = $activeDbLeaveTypes->sortBy(function($lt) use ($preferredOrder) {
        $code = strtoupper($lt->code ?? '');
        $idx = array_search($code, $preferredOrder);
        return $idx === false ? 999 : $idx;
    });

    $leaveCatalog = [];
    foreach ($sortedLeaveTypes as $lt) {
        $code = strtoupper($lt->code ?? ('TYPE_' . $lt->id));
        $bal = $balancesByCode->get($code);
        $typeId = (string)$lt->id;

        $allocated = (float)($bal->allocated ?? ($lt->days ?? 0));
        $used = (float)($bal->used ?? 0);
        $avail = max(0, $allocated + (float)($bal->carried_forward ?? 0) - $used);

        $subtext = match($code) {
            'CASUAL' => $formatNum($avail) . ' of ' . $formatNum($allocated) . ' left',
            'MEDICAL' => $formatNum($avail) . ' of ' . $formatNum($allocated) . ' left',
            'ANNUAL' => $formatNum($avail) . ' left',
            'LIEU' => $formatNum($avail) . ' to use',
            'SHORT' => $shortLeftThisMonth . ' left this month',
            'DUTY' => 'Late start or off-site',
            'NIGHT_SHIFT_RECOVERY' => 'Late stay concession',
            'HOSPITALISATION' => $formatNum($avail) . ' of ' . $formatNum($allocated) . ' left',
            'ELECTION' => 'Half day to vote',
            'RELIGIOUS' => 'With HOD approval',
            'PATERNITY' => $formatNum($allocated) . ' days',
            'MATERNITY' => $formatNum($allocated) . ' working days',
            'NO_PAY' => 'Email HOD, copy HR',
            default => ($allocated > 0 ? $formatNum($avail) . ' of ' . $formatNum($allocated) . ' left' : ($avail > 0 ? $formatNum($avail) . ' left' : ($lt->description ?: 'Available')))
        };

        $dotColor = match($code) {
            'CASUAL' => 'bg-purple-400',
            'MEDICAL' => 'bg-emerald-400',
            'ANNUAL' => 'bg-sky-400',
            'LIEU' => 'bg-teal-400',
            'SHORT' => 'bg-amber-400',
            'DUTY' => 'bg-indigo-400',
            'NIGHT_SHIFT_RECOVERY' => 'bg-indigo-500',
            'HOSPITALISATION' => 'bg-rose-400',
            'ELECTION' => 'bg-violet-400',
            'RELIGIOUS' => 'bg-orange-400',
            'PATERNITY' => 'bg-cyan-400',
            'MATERNITY' => 'bg-pink-400',
            'NO_PAY' => 'bg-slate-400',
            default => 'bg-sky-400'
        };

        $cardName = match($code) {
            'CASUAL' => 'Casual leave',
            'MEDICAL' => 'Medical leave',
            'ANNUAL' => 'Annual leave',
            'LIEU' => 'Lieu leave',
            'SHORT' => 'Short leave',
            'DUTY' => 'Duty leave',
            'NIGHT_SHIFT_RECOVERY' => 'Night Shift Recovery',
            'HOSPITALISATION' => 'Hospitalisation leave',
            'ELECTION' => 'Election leave',
            'RELIGIOUS' => 'Religious holiday',
            'PATERNITY' => 'Paternity leave',
            'MATERNITY' => 'Maternity leave',
            'NO_PAY' => 'No pay leave',
            default => ($lt->name ?? ucfirst(strtolower($code)) . ' leave')
        };

        $leaveCatalog[] = [
            'id' => $typeId,
            'code' => $code,
            'name' => $cardName,
            'subtext' => $subtext,
            'dotColor' => $dotColor,
            'available' => $avail,
            'allocated' => $allocated
        ];
    }
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
         leaveCatalog: {{ json_encode($leaveCatalog) }},
         employeesMap: {{ json_encode($empMap) }},
         selectedLeaveTypeId: '{{ (string)($leaveTypeMap['ANNUAL'] ?? ($leaveCatalog[0]['id'] ?? '')) }}',
         selectedLeaveTypeCode: '{{ isset($leaveTypeMap['ANNUAL']) ? 'ANNUAL' : ($leaveCatalog[0]['code'] ?? '') }}',
         selectedDay: {{ json_encode($todayCell) }}, 
         todayCell: {{ json_encode($todayCell) }}, 
         viewMode: 'grid', 
         targetStartDate: '{{ \Carbon\Carbon::now('Asia/Colombo')->toDateString() }}', 
         targetEndDate: '{{ \Carbon\Carbon::now('Asia/Colombo')->toDateString() }}',
         isHalfDay: false,
         halfDaySlot: 'Morning',
         isShortLeave: false,
         shortLeaveSlot: '9:30 AM–10:00 AM',
         coveringEmployeeId: '',
         coveringEmployeeName: '',
         handoverNotes: '',
         anythingElse: '',
         projectClientName: '',
         allHolidays: {{ json_encode($allHolidaysList ?? []) }},
         lieuClaimModalOpen: false,
         lieuWorkedDate: '{{ \Carbon\Carbon::now('Asia/Colombo')->subDay()->toDateString() }}',
         lieuWorkedHours: 4,
         lieuStartTime: '09:00',
         lieuEndTime: '13:00',
         lieuReason: '',
         nightSignOutTime: '22:15',

         setQuickHours(h) {
             this.lieuWorkedHours = h;
             this.syncEndTimeFromHours();
         },

         syncEndTimeFromHours() {
             const hrs = parseFloat(this.lieuWorkedHours);
             if (isNaN(hrs) || hrs <= 0) return;
             if (!this.lieuStartTime) this.lieuStartTime = '09:00';
             const [sh, sm] = this.lieuStartTime.split(':').map(Number);
             const startMins = sh * 60 + sm;
             const addMins = Math.round(hrs * 60);
             const endMins = (startMins + addMins) % (24 * 60);
             const endH = Math.floor(endMins / 60);
             const endM = endMins % 60;
             this.lieuEndTime = String(endH).padStart(2, '0') + ':' + String(endM).padStart(2, '0');
         },

         onStartTimeChange() {
             if (this.lieuEndTime) {
                 this.onTimeChange();
             } else if (this.lieuWorkedHours && parseFloat(this.lieuWorkedHours) > 0) {
                 this.syncEndTimeFromHours();
             }
         },

         onEndTimeChange() {
             this.onTimeChange();
         },

         onTimeChange() {
             if (!this.lieuStartTime || !this.lieuEndTime) return;
             const [sh, sm] = this.lieuStartTime.split(':').map(Number);
             const [eh, em] = this.lieuEndTime.split(':').map(Number);
             let diff = (eh * 60 + em) - (sh * 60 + sm);
             if (diff < 0) diff += 24 * 60;
             const hrs = Math.max(0, diff / 60);
             this.lieuWorkedHours = hrs % 1 === 0 ? hrs : parseFloat(hrs.toFixed(1));
         },

         get calculatedLieuDuration() {
             const h = parseFloat(this.lieuWorkedHours || 0);
             if (h >= 8) {
                 return {
                     type: 'full_day',
                     credit: 1.0,
                     label: 'Full Day',
                     hoursLabel: '8+ hours worked',
                     creditLabel: '+1.0 Day Credit',
                     valid: true,
                     badgeClass: 'bg-emerald-600 text-white',
                     containerClass: 'border-emerald-500/50 bg-emerald-50/50 dark:bg-emerald-950/20'
                 };
             } else if (h >= 4) {
                 return {
                     type: 'half_day',
                     credit: 0.5,
                     label: 'Half Day',
                     hoursLabel: '4–7 hours worked',
                     creditLabel: '+0.5 Day Credit',
                     valid: true,
                     badgeClass: 'bg-teal-600 text-white',
                     containerClass: 'border-teal-500/50 bg-teal-50/50 dark:bg-teal-950/20'
                 };
             } else {
                 return {
                     type: 'half_day',
                     credit: 0,
                     label: 'Under 4 hours',
                     hoursLabel: 'Insufficient hours',
                     creditLabel: 'No Credit (Min 4 hrs required)',
                     valid: false,
                     badgeClass: 'bg-amber-600 text-white',
                     containerClass: 'border-amber-500/50 bg-amber-50/50 dark:bg-amber-950/20'
                 };
             }
         },

         get lieuDateMeta() {
             if (!this.lieuWorkedDate) return null;
             const parts = this.lieuWorkedDate.split('-').map(Number);
             if (parts.length !== 3) return null;
             const d = new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0);
             const day = d.getDay();
             const isWeekend = (day === 0 || day === 6);
             const isHoliday = (this.allHolidays || []).includes(this.lieuWorkedDate);
             const dayName = d.toLocaleDateString('en-US', { weekday: 'long' });
             const isEligible = isWeekend || isHoliday;
             return {
                 isWeekend: isWeekend,
                 isHoliday: isHoliday,
                 isEligible: isEligible,
                 dayName: dayName,
                 label: isHoliday ? 'Public Holiday' : (isWeekend ? (day === 6 ? 'Saturday (Weekend)' : 'Sunday (Weekend)') : 'Regular Weekday')
             };
         },

         get nightShiftConcession() {
             if (!this.nightSignOutTime) return null;
             const parts = this.nightSignOutTime.split(':').map(Number);
             if (parts.length !== 2) return null;
             const mins = parts[0] * 60 + parts[1];

             // Concession 1: Sign out Between 9.00 p.m. - 10.30 p.m. (21:00 - 22:30) -> Report at 10.00 a.m.
             if (mins >= 1260 && mins <= 1350) {
                 return {
                     category: 'report_10am',
                     label: 'Report to work at 10:00 AM',
                     reportTime: '10:00:00',
                     days: 0.0,
                     isOff: false,
                     badgeClass: 'text-blue-500 bg-blue-500/10 border-blue-500/30',
                     description: 'Concession: You can report to work at 10:00 AM next day without affecting your attendance.'
                 };
             }
             // Concession 2: Sign out Between 10.31 p.m. – 12.00 p.m. (midnight) (22:31 - 24:00) -> Report at 11.00 a.m.
             if (mins > 1350 || mins === 0) {
                 return {
                     category: 'report_11am',
                     label: 'Report to work at 11:00 AM',
                     reportTime: '11:00:00',
                     days: 0.0,
                     isOff: false,
                     badgeClass: 'text-indigo-500 bg-indigo-500/10 border-indigo-500/30',
                     description: 'Concession: You can report to work at 11:00 AM next day without affecting your attendance.'
                 };
             }
             // Concession 3: Sign out Between 12.01 – 3.00 a.m. (00:01 - 03:00) -> Half day off
             if (mins > 0 && mins <= 180) {
                 return {
                     category: 'half_day_off',
                     label: 'Half Day Off',
                     reportTime: '',
                     days: 0.5,
                     isOff: true,
                     badgeClass: 'text-amber-500 bg-amber-500/10 border-amber-500/30',
                     description: 'Concession: Half day off granted. Report to work at 1:00 PM for afternoon session without penalty.'
                 };
             }
             // Concession 4: Sign out Beyond 3.00 a.m. (03:01 - 11:59 AM) -> Full day off
             if (mins > 180 && mins < 720) {
                 return {
                     category: 'full_day_off',
                     label: 'Full Day Off',
                     reportTime: '',
                     days: 1.0,
                     isOff: true,
                     badgeClass: 'text-emerald-500 bg-emerald-500/10 border-emerald-500/30',
                     description: 'Concession: Full day off granted for next working day to rest & recover without attendance penalty.'
                 };
             }

             return {
                 category: null,
                 label: 'Outside Concession Window',
                 reportTime: '',
                 days: 0.0,
                 isOff: false,
                 invalid: true,
                 badgeClass: 'text-slate-400 bg-slate-500/10 border-slate-500/30',
                 description: 'Night stay concessions apply for sign-outs between 9:00 PM and morning hours.'
             };
         },

         openLieuClaimModal() {
             this.lieuClaimModalOpen = true;
             this.syncEndTimeFromHours();
             this.$nextTick(() => window.lucide && lucide.createIcons());
         },

         selectLeaveType(code, id) {
             this.selectedLeaveTypeCode = code;
             this.selectedLeaveTypeId = String(id || this.leaveTypesMap[code] || '');
             if (code === 'SHORT') {
                 this.isHalfDay = false;
                 this.isShortLeave = true;
                 this.targetEndDate = this.targetStartDate;
             } else if (code === 'NIGHT_SHIFT_RECOVERY') {
                 this.isHalfDay = false;
                 this.isShortLeave = false;
                 this.targetEndDate = this.targetStartDate;
                 this.$nextTick(() => window.lucide && lucide.createIcons());
             } else {
                 this.isShortLeave = false;
             }
         },

         calculateNetWorkingDays() {
             if (this.selectedLeaveTypeCode === 'SHORT') return 0.2;
             if (this.selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY') {
                 return this.nightShiftConcession ? this.nightShiftConcession.days : 0.0;
             }
             if (this.isHalfDay) return 0.5;
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

         formatDateRangeSummary() {
             const dur = this.calculateNetWorkingDays();
             const durText = dur === 0.2 ? 'Short leave (0.2 d)' : (dur === 0.5 ? 'Half day (0.5 d)' : (dur + (dur === 1 ? ' working day' : ' working days')));
             const item = (this.leaveCatalog || []).find(l => l.code === this.selectedLeaveTypeCode);
             const typeName = (item?.name || 'Leave').toLowerCase();
             
             if (!this.targetStartDate) return `${durText} · ${typeName}`;
             
             const sParts = this.targetStartDate.split('-').map(Number);
             const startDate = new Date(sParts[0], sParts[1] - 1, sParts[2]);
             const sMonth = startDate.toLocaleDateString('en-US', { month: 'short' });
             const sDay = startDate.getDate();
             const sWeekday = startDate.toLocaleDateString('en-US', { weekday: 'short' });
             
             if (this.selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY') {
                 const conc = this.nightShiftConcession;
                 const concLabel = conc ? conc.label : 'Night Shift Concession';
                 return `${concLabel} · ${typeName} ${sWeekday} ${sDay} ${sMonth}`;
             }

             if (this.isHalfDay || this.selectedLeaveTypeCode === 'SHORT' || this.targetStartDate === this.targetEndDate) {
                 return `${durText} · ${typeName} ${sWeekday} ${sDay} ${sMonth}`;
             }
             
             if (!this.targetEndDate) {
                 return `${durText} · ${typeName} ${sDay} ${sMonth}`;
             }
             
             const eParts = this.targetEndDate.split('-').map(Number);
             const endDate = new Date(eParts[0], eParts[1] - 1, eParts[2]);
             const eMonth = endDate.toLocaleDateString('en-US', { month: 'short' });
             const eDay = endDate.getDate();
             const eWeekday = endDate.toLocaleDateString('en-US', { weekday: 'short' });
             
             if (sMonth === eMonth) {
                 return `${durText} · ${typeName} ${sDay} ${sMonth} – ${eWeekday} ${eDay} ${eMonth}`;
             } else {
                 return `${durText} · ${typeName} ${sDay} ${sMonth} – ${eWeekday} ${eDay} ${eMonth}`;
             }
         },

         checkClash() {
             if (!this.targetStartDate || !this.targetEndDate) {
                 return { pass: true, message: 'Clash check — choose dates to verify schedule.' };
             }
             const s = this.targetStartDate;
             const e = this.targetEndDate;
             const clash = (this.allMyHistory || []).find(r => {
                 if (r.status === 'Rejected' || r.status === 'Canceled') return false;
                 return (s <= r.end_date && e >= r.start_date);
             });
             if (clash) {
                 return { 
                     pass: false, 
                     message: `Clash check — overlaps with booked ${clash.leave_type?.name || 'leave'} (${clash.start_date} to ${clash.end_date}).` 
                 };
             }
             return { pass: true, message: 'Clash check — you have no overlapping leaves booked.' };
         },

         checkNoticePeriod() {
             if (!this.targetStartDate) return { pass: true, message: 'Notice period check pending.' };
             
             if (this.selectedLeaveTypeCode === 'ANNUAL') {
                 const today = new Date();
                 today.setHours(0,0,0,0);
                 const sParts = this.targetStartDate.split('-').map(Number);
                 const start = new Date(sParts[0], sParts[1] - 1, sParts[2], 0, 0, 0, 0);
                 const diffDays = Math.round((start.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
                 
                 const earliest = new Date(today.getTime());
                 earliest.setDate(earliest.getDate() + 14);
                 const earliestFormatted = earliest.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' });

                 if (diffDays >= 14) {
                     return { pass: true, message: `Notice period — 14 days required for annual leave. Earliest: ${earliestFormatted}` };
                 } else {
                     return { pass: false, message: `Notice period — 14 days required for annual leave. Earliest date allowed: ${earliestFormatted}` };
                 }
             }
             
             if (this.selectedLeaveTypeCode === 'CASUAL') {
                 const dur = this.calculateNetWorkingDays();
                 if (dur > 3) {
                     return { pass: false, message: `Casual leave — cannot exceed 3 consecutive working days (${dur} requested).` };
                 }
                 return { pass: true, message: 'Casual leave — within 3 consecutive working day limit.' };
             }

             if (this.selectedLeaveTypeCode === 'SHORT') {
                 return { pass: true, message: 'Short leave — max 2 per calendar month, up to 1.5 hours.' };
             }

             if (this.selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY') {
                 return { pass: true, message: 'Night Shift Recovery — subject to prior approval from Head of Department (HOD).' };
             }

             return { pass: true, message: 'Notice period — standard leave guidelines met.' };
         },

         checkBalance() {
             const dur = this.calculateNetWorkingDays();
             if (this.selectedLeaveTypeCode === 'NO_PAY') {
                 return { pass: true, message: `No pay leave — ${dur} day(s) requested. Will deduct from payroll.` };
             }
             if (this.selectedLeaveTypeCode === 'DUTY') {
                 return { pass: true, message: `Duty leave — ${dur} day(s) official company duty.` };
             }
             if (this.selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY') {
                 const conc = this.nightShiftConcession;
                 const text = conc ? conc.label : 'concession';
                 return { pass: true, message: `Night shift concession granted (${text}) — non-deductible from standard leave quotas.` };
             }
             if (this.selectedLeaveTypeCode === 'SHORT') {
                 if ({{ (int)($monthlyShortLeavesUsed ?? 0) }} >= 2) {
                     return { pass: false, message: 'Short leave monthly quota exceeded (2/2 already used this month).' };
                 }
                 return { pass: true, message: `Balance — uses 1 short leave. {{ max(0, 2 - (int)($monthlyShortLeavesUsed ?? 0) - 1) }} left after this.` };
             }
             
             const item = (this.leaveCatalog || []).find(c => c.code === this.selectedLeaveTypeCode);
             const avail = item ? Number(item.available) : 0;
             
             if (dur <= avail) {
                 const remaining = (avail - dur);
                 const remStr = remaining % 1 === 0.5 ? Math.floor(remaining) + '½' : (remaining === 0.5 ? '½' : remaining);
                 return { pass: true, message: `Balance — uses ${dur} day${dur === 1 ? '' : 's'}. ${remStr} left after this.` };
             } else {
                 return { pass: false, message: `Balance — insufficient quota: requested ${dur} day${dur === 1 ? '' : 's'}, only ${avail} available.` };
             }
         },

         checkConsecutiveDays() {
             if (this.selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY') {
                 const conc = this.nightShiftConcession;
                 const label = conc ? conc.label : 'Night Shift Concession';
                 return { pass: true, message: `Working schedule adjusted: ${label}. Attendance record preserved.` };
             }
             const dur = this.calculateNetWorkingDays();
             if (dur <= 0) {
                 return { pass: false, message: 'Working days — selected period contains no working days.' };
             }
             if (this.selectedLeaveTypeCode === 'CASUAL' && dur > 3) {
                 return { pass: false, message: `Consecutive days — casual leave limited to 3 days (${dur} requested).` };
             }
             const daysLabel = dur === 0.2 ? '0.2 days (Short leave)' : (dur === 0.5 ? '0.5 days (Half day)' : `${dur} working day${dur === 1 ? '' : 's'}`);
             return { pass: true, message: `Consecutive working days — ${daysLabel} calculated.` };
         },

         checkCovering() {
             if (this.coveringEmployeeId && this.coveringEmployeeId !== '') {
                 const empName = this.employeesMap[this.coveringEmployeeId] || 'Assigned colleague';
                 return { pass: true, message: `Covering colleague — ${empName}` };
             }
             return { pass: false, message: 'Covering colleague — choose who will cover for you.' };
         },

         checkHandoverNotes() {
             if (this.handoverNotes && this.handoverNotes.trim().length >= 3) {
                 return { pass: true, message: 'Handover brief — details provided.' };
             }
             return { pass: false, message: 'Handover brief — live jobs, deadlines & details needed.' };
         },

         allChecksPass() {
             return this.checkClash().pass &&
                    this.checkNoticePeriod().pass &&
                    this.checkBalance().pass &&
                    this.checkConsecutiveDays().pass &&
                    this.checkCovering().pass &&
                    this.checkHandoverNotes().pass;
         },

         openApplyModal(code) {
             let targetCode = (code || 'ANNUAL').toUpperCase();
             const targetItem = (this.leaveCatalog || []).find(l => l.code === targetCode);
             if (targetItem) {
                 this.selectLeaveType(targetCode, targetItem.id);
             } else {
                 this.selectLeaveType(targetCode, this.leaveTypesMap[targetCode] || '');
             }
             this.applyLeaveModal = true;
             this.$nextTick(() => { 
                 if (window.lucide) lucide.createIcons(); 
             });
         },

         getLeaveTypeColor(nameOrCode) {
             return { 
                 text: 'text-slate-800 dark:text-slate-200', 
                 badge: 'bg-slate-100 dark:bg-white/[0.05] text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-white/[0.08]', 
                 avatar: 'bg-blue-600 text-white' 
             };
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
         },

         getSelectedDayLeaves() {
             if (!this.selectedDay || !this.selectedDay.leaves) return [];
             if (Array.isArray(this.selectedDay.leaves)) return this.selectedDay.leaves;
             return Object.values(this.selectedDay.leaves);
         },

         getWhoIsOnLeaveText(lv) {
             if (!lv) return 'Full Day';
             const isShort = lv.is_short_leave || 
                 ((lv.leave_type?.code || lv.leaveType?.code || '').toUpperCase() === 'SHORT') ||
                 ((lv.leave_type?.name || lv.leaveType?.name || lv.leave_type_name || '').toLowerCase().includes('short'));
             if (isShort) {
                 const rawSlot = String(lv.short_leave_slot || '').toLowerCase();
                 const isEvening = rawSlot.includes('evening') || rawSlot.includes('afternoon') || rawSlot.includes('3:30') || rawSlot.includes('5:00') || (rawSlot.includes('pm') && !rawSlot.includes('am'));
                 return isEvening ? 'Short Leave (Evening)' : 'Short Leave (Morning)';
             }
             const isHalf = lv.is_half_day || (Number(lv.duration) === 0.5);
             if (isHalf) {
                 const rawSlot = String(lv.half_day_slot || '').toLowerCase();
                 const isEvening = rawSlot.includes('evening') || rawSlot.includes('afternoon') || rawSlot.includes('pm');
                 return isEvening ? 'Half Day (Evening)' : 'Half Day (Morning)';
             }
             return 'Full Day';
         },

         hasEventsOrBirthdays() {
             if (!this.selectedDay) return false;
             const hasBdays = Array.isArray(this.selectedDay.birthdays) && this.selectedDay.birthdays.length > 0;
             const hasHoli = Boolean(this.selectedDay.holiday) || (Array.isArray(this.selectedDay.holidays) && this.selectedDay.holidays.length > 0);
             return hasBdays || hasHoli;
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
    $lieuData = $getQuotaData('LIEU', 0, 'days');

    $lieuDaysList = $lieuDaysList ?? [];

    $getLeaveTypeMeta = function($nameOrCode) {
        $s = strtoupper($nameOrCode ?? '');
        if (str_contains($s, 'ANNUAL') || str_contains($s, 'VACATION')) {
            return [
                'dot' => 'bg-sky-500',
                'badge' => 'bg-sky-100/90 dark:bg-sky-950/70 text-sky-900 dark:text-sky-200 border-sky-300 dark:border-sky-800',
                'label' => 'Annual'
            ];
        }
        if (str_contains($s, 'CASUAL')) {
            return [
                'dot' => 'bg-purple-500',
                'badge' => 'bg-purple-100/90 dark:bg-purple-950/70 text-purple-900 dark:text-purple-200 border-purple-300 dark:border-purple-800',
                'label' => 'Casual'
            ];
        }
        if (str_contains($s, 'MED') || str_contains($s, 'SICK')) {
            return [
                'dot' => 'bg-emerald-500',
                'badge' => 'bg-emerald-100/90 dark:bg-emerald-950/70 text-emerald-900 dark:text-emerald-200 border-emerald-300 dark:border-emerald-800',
                'label' => 'Medical'
            ];
        }
        if (str_contains($s, 'SHORT')) {
            return [
                'dot' => 'bg-amber-500',
                'badge' => 'bg-amber-100/90 dark:bg-amber-950/70 text-amber-950 dark:text-amber-200 border-amber-300 dark:border-amber-800',
                'label' => 'Short'
            ];
        }
        if (str_contains($s, 'DUTY')) {
            return [
                'dot' => 'bg-indigo-500',
                'badge' => 'bg-indigo-100/90 dark:bg-indigo-950/70 text-indigo-950 dark:text-indigo-200 border-indigo-300 dark:border-indigo-800',
                'label' => 'Duty'
            ];
        }
        if (str_contains($s, 'LIEU') || str_contains($s, 'HALF')) {
            return [
                'dot' => 'bg-teal-500',
                'badge' => 'bg-teal-100/90 dark:bg-teal-950/70 text-teal-950 dark:text-teal-200 border-teal-300 dark:border-teal-800',
                'label' => 'Lieu'
            ];
        }
        return [
            'dot' => 'bg-slate-500',
            'badge' => 'bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-300 dark:border-slate-700',
            'label' => 'Leave'
        ];
    };
@endphp

    <!-- Top Leave Summary Cards (6 Quotas - Sleek Linear Micro-Meters with Ambient Drop Shadows) -->
    <div class="shrink-0 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        
        <!-- 1. Annual Leave -->
        <div @click="openQuotaModal('ANNUAL', 'Annual Leave', 'plane', 'sky', {{ $annualData['total'] }}, {{ $annualData['used'] }}, {{ $annualData['available'] }}, {{ $annualData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-[0_4px_16px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] hover:shadow-[0_8px_24px_-4px_rgba(15,23,42,0.12)] hover:border-sky-300 dark:hover:border-sky-700 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-1">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-500/20 text-sky-600 dark:text-sky-300 flex items-center justify-center shrink-0 border border-sky-100 dark:border-sky-500/30 shadow-2xs">
                    <i data-lucide="plane" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-none">Annual Leave</span>
            </div>

            <div class="my-2.5 flex items-center gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $annualData['available'] }}</span>
                <span class="text-[10px] font-bold text-[#1E3A8A] dark:text-sky-300 bg-[#E0F2FE] dark:bg-sky-500/20 px-2 py-0.5 rounded-full border border-sky-200/80 dark:border-sky-400/30 leading-none inline-flex items-center shadow-xs">days left</span>
            </div>

            <div class="space-y-1.5 pt-0.5">
                <div class="py-1.5">
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800/90 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-sky-400 dark:bg-sky-500 rounded-full transition-all duration-500" style="width: {{ $annualData['pct'] }}%"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[11px] leading-none">
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $annualData['used'] }} <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">used</span></span>
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $annualData['pct'] }}% <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">left</span></span>
                </div>
            </div>
        </div>

        <!-- 2. Casual Leave -->
        <div @click="openQuotaModal('CASUAL', 'Casual Leave', 'calendar-check', 'purple', {{ $casualData['total'] }}, {{ $casualData['used'] }}, {{ $casualData['available'] }}, {{ $casualData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-[0_4px_16px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] hover:shadow-[0_8px_24px_-4px_rgba(15,23,42,0.12)] hover:border-purple-300 dark:hover:border-purple-700 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-1">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-500/20 text-purple-600 dark:text-purple-300 flex items-center justify-center shrink-0 border border-purple-100 dark:border-purple-500/30 shadow-2xs">
                    <i data-lucide="calendar-check" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-none">Casual Leave</span>
            </div>

            <div class="my-2.5 flex items-center gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $casualData['available'] }}</span>
                <span class="text-[10px] font-bold text-[#581C87] dark:text-purple-300 bg-[#F3E8FF] dark:bg-purple-500/20 px-2 py-0.5 rounded-full border border-purple-200/80 dark:border-purple-400/30 leading-none inline-flex items-center shadow-xs">days left</span>
            </div>

            <div class="space-y-1.5 pt-0.5">
                <div class="py-1.5">
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800/90 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-purple-400 dark:bg-purple-500 rounded-full transition-all duration-500" style="width: {{ $casualData['pct'] }}%"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[11px] leading-none">
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $casualData['used'] }} <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">used</span></span>
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $casualData['pct'] }}% <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">left</span></span>
                </div>
            </div>
        </div>

        <!-- 3. Medical Leave -->
        <div @click="openQuotaModal('MEDICAL', 'Medical Leave', 'activity', 'emerald', {{ $medicalData['total'] }}, {{ $medicalData['used'] }}, {{ $medicalData['available'] }}, {{ $medicalData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-[0_4px_16px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] hover:shadow-[0_8px_24px_-4px_rgba(15,23,42,0.12)] hover:border-emerald-300 dark:hover:border-emerald-700 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-1">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-500/30 shadow-2xs">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-none">Medical Leave</span>
            </div>

            <div class="my-2.5 flex items-center gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $medicalData['available'] }}</span>
                <span class="text-[10px] font-bold text-[#064E3B] dark:text-emerald-300 bg-[#D1FAE5] dark:bg-emerald-500/20 px-2 py-0.5 rounded-full border border-emerald-200/80 dark:border-emerald-400/30 leading-none inline-flex items-center shadow-xs">days left</span>
            </div>

            <div class="space-y-1.5 pt-0.5">
                <div class="py-1.5">
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800/90 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-emerald-400 dark:bg-emerald-500 rounded-full transition-all duration-500" style="width: {{ $medicalData['pct'] }}%"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[11px] leading-none">
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $medicalData['used'] }} <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">used</span></span>
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $medicalData['pct'] }}% <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">left</span></span>
                </div>
            </div>
        </div>

        <!-- 4. Short Leave -->
        <div @click="openQuotaModal('SHORT', 'Short Leave', 'clock', 'amber', {{ $shortData['total'] }}, {{ $shortData['used'] }}, {{ $shortData['available'] }}, {{ $shortData['cf'] }}, 'per month')"
             class="group bg-white dark:bg-[#111927] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-[0_4px_16px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] hover:shadow-[0_8px_24px_-4px_rgba(15,23,42,0.12)] hover:border-amber-300 dark:hover:border-amber-700 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-1">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-500/30 shadow-2xs">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-none">Short Leave</span>
            </div>

            <div class="my-2.5 flex items-center gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $shortData['available'] }}</span>
                <span class="text-[10px] font-bold text-[#78350F] dark:text-amber-200 bg-[#FEF3C7] dark:bg-amber-500/20 px-2 py-0.5 rounded-full border border-amber-200/80 dark:border-amber-400/30 leading-none inline-flex items-center shadow-xs">/ month</span>
            </div>

            <div class="space-y-1.5 pt-0.5">
                <div class="py-1.5">
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800/90 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-amber-400 dark:bg-amber-500 rounded-full transition-all duration-500" style="width: {{ $shortData['pct'] }}%"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[11px] leading-none">
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $shortData['used'] }} <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">used</span></span>
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $shortData['pct'] }}% <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">left</span></span>
                </div>
            </div>
        </div>

        <!-- 5. Duty Leave -->
        <div @click="openQuotaModal('DUTY', 'Duty Leave', 'briefcase', 'indigo', {{ $dutyData['total'] }}, {{ $dutyData['used'] }}, {{ $dutyData['available'] }}, {{ $dutyData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-[0_4px_16px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] hover:shadow-[0_8px_24px_-4px_rgba(15,23,42,0.12)] hover:border-indigo-300 dark:hover:border-indigo-700 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-1">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-500/30 shadow-2xs">
                    <i data-lucide="briefcase" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-none">Duty Leave</span>
            </div>

            <div class="my-2.5 flex items-center gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $dutyData['available'] }}</span>
                <span class="text-[10px] font-bold text-[#312E81] dark:text-indigo-300 bg-[#E0E7FF] dark:bg-indigo-500/20 px-2 py-0.5 rounded-full border border-indigo-200/80 dark:border-indigo-400/30 leading-none inline-flex items-center shadow-xs">days left</span>
            </div>

            <div class="space-y-1.5 pt-0.5">
                <div class="py-1.5">
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800/90 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-indigo-400 dark:bg-indigo-500 rounded-full transition-all duration-500" style="width: {{ $dutyData['pct'] }}%"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[11px] leading-none">
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $dutyData['used'] }} <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">used</span></span>
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $dutyData['pct'] }}% <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">left</span></span>
                </div>
            </div>
        </div>

        <!-- 6. Lieu Leave -->
        <div @click="openQuotaModal('LIEU', 'Lieu Leave', 'refresh-cw', 'teal', {{ $lieuData['total'] }}, {{ $lieuData['used'] }}, {{ $lieuData['available'] }}, {{ $lieuData['cf'] }}, 'days')"
             class="group bg-white dark:bg-[#111927] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-[0_4px_16px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] hover:shadow-[0_8px_24px_-4px_rgba(15,23,42,0.12)] hover:border-teal-300 dark:hover:border-teal-700 flex flex-col justify-between transition-all duration-200 cursor-pointer hover:-translate-y-1">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-500/20 text-teal-600 dark:text-teal-300 flex items-center justify-center shrink-0 border border-teal-100 dark:border-teal-500/30 shadow-2xs">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-none">Lieu Leave</span>
            </div>

            <div class="my-2.5 flex items-center gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $lieuData['available'] }}</span>
                <span class="text-[10px] font-bold text-[#134E4A] dark:text-teal-300 bg-[#CCFBF1] dark:bg-teal-500/20 px-2 py-0.5 rounded-full border border-teal-200/80 dark:border-teal-400/30 leading-none inline-flex items-center shadow-xs">days left</span>
            </div>

            <div class="space-y-1.5 pt-0.5">
                <div class="py-1.5">
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800/90 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-teal-400 dark:bg-teal-500 rounded-full transition-all duration-500" style="width: {{ $lieuData['pct'] }}%"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[11px] leading-none">
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $lieuData['used'] }} <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">used</span></span>
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ $lieuData['pct'] }}% <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-300">left</span></span>
                </div>
            </div>
        </div>

    </div>

    <!-- Main Grid Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-2">

        <!-- Left 2 Cols: Monthly Calendar View -->
        <div class="lg:col-span-2 bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-[0_6px_24px_-4px_rgba(15,23,42,0.08),0_2px_6px_-1px_rgba(15,23,42,0.04)] flex flex-col justify-between">
            <div class="flex flex-col">
                
                <!-- Calendar Top Controls Bar (Clean Modern SaaS Header) -->
                <div class="flex flex-wrap items-center justify-between pb-3.5 border-b border-slate-100 dark:border-slate-800 gap-3">
                    <!-- Month Title & Quick Nav -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ $selectedDate->format('F Y') }}</h2>
                        </div>
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-lg border border-slate-200/60 dark:border-slate-700/60">
                            <a href="{{ route('dashboard', ['year' => $prevMonthDate->year, 'month' => $prevMonthDate->month]) }}" class="w-7 h-7 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/70 rounded-md flex items-center justify-center transition-colors cursor-pointer" title="Previous Month">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>
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
                    <div class="border border-slate-200/90 dark:border-slate-800 rounded-2xl overflow-hidden bg-slate-100/90 dark:bg-[#080d19] shadow-inner p-2.5">
                        <!-- Weekday Headers -->
                        <div class="grid grid-cols-7 mb-2 text-[11px] font-extrabold uppercase tracking-wider py-1 text-center select-none">
                            <div class="text-[#4a5568] dark:text-slate-300">Mon</div>
                            <div class="text-[#4a5568] dark:text-slate-300">Tue</div>
                            <div class="text-[#4a5568] dark:text-slate-300">Wed</div>
                            <div class="text-[#4a5568] dark:text-slate-300">Thu</div>
                            <div class="text-[#4a5568] dark:text-slate-300">Fri</div>
                            <div class="text-rose-600 dark:text-rose-400 font-black">Sat</div>
                            <div class="text-rose-600 dark:text-rose-400 font-black">Sun</div>
                        </div>

                        <!-- Days Grid (Sleek SaaS elevated cards with clean outlines and drop shadows) -->
                        <div class="grid grid-cols-7 gap-1.5">
                            @foreach($calendarDays as $cell)
                                <div @click="selectedDay = {{ json_encode($cell) }}; $nextTick(() => window.lucide && lucide.createIcons())"
                                     @dblclick="dayModalOpen = true"
                                     :class="selectedDay && selectedDay.date === '{{ $cell['date'] }}' ? '!border-blue-500 dark:!border-blue-400 ring-2 ring-blue-500/20 shadow-md z-10' : ''"
                                     class="min-h-[92px] sm:min-h-[100px] p-2 rounded-xl flex flex-col justify-between transition-all duration-150 cursor-pointer relative group border shadow-[0_1px_3px_rgba(15,23,42,0.06)] hover:shadow-md hover:-translate-y-0.5
                                     {{ !$cell['is_current_month'] ? 'bg-slate-100/50 dark:bg-[#090e1c]/50 border-slate-200/60 dark:border-slate-800/50 shadow-none' : '' }}
                                     {{ $cell['is_current_month'] && $cell['is_today'] ? 'bg-white dark:bg-[#111927] border-blue-500/60 dark:border-blue-400/50 ring-1 ring-blue-500/20 shadow-xs' : '' }}
                                     {{ $cell['is_current_month'] && !$cell['is_today'] && $cell['is_weekend'] ? 'bg-slate-50/90 dark:bg-[#0d1526] border-slate-200/80 dark:border-[#1a2947] hover:bg-slate-100 dark:hover:bg-[#121c32]' : '' }}
                                     {{ $cell['is_current_month'] && !$cell['is_today'] && !$cell['is_weekend'] ? 'bg-white dark:bg-[#111927] border-slate-200/90 dark:border-slate-800 hover:bg-slate-50/80 dark:hover:bg-[#162544] hover:border-blue-300 dark:hover:border-blue-500/70' : '' }}
                                ">
                                    <!-- Day Header: Date Number & Badges -->
                                    <div class="flex items-center justify-between mb-1.5">
                                        @if($cell['is_today'] && $cell['is_current_month'])
                                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">{{ $cell['day'] }}</span>
                                            <span class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Today</span>
                                        @elseif(!$cell['is_current_month'])
                                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 pl-0.5">{{ $cell['day'] }}</span>
                                        @elseif($cell['is_weekend'])
                                            <span class="text-xs sm:text-sm font-semibold text-rose-500 dark:text-rose-400 pl-0.5">{{ $cell['day'] }}</span>
                                        @else
                                            <span class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200 pl-0.5">{{ $cell['day'] }}</span>
                                        @endif
                                    </div>

                                     <!-- Events Stack (Unified Minimal Chips with Category Indicator Dots) -->
                                     <div class="flex-1 flex flex-col justify-end gap-1 overflow-hidden">
                                         @if($cell['is_current_month'])
                                             <!-- Holiday Pill -->
                                             @if($cell['holiday'])
                                                 @php
                                                     $isCompany = $cell['holiday']->type === 'Company';
                                                     $hTitle = $cell['holiday']->title;
                                                 @endphp
                                                 <div class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/[0.05] border border-slate-200/60 dark:border-white/[0.08] text-[10px] font-medium text-slate-700 dark:text-[#E2E8F0] flex items-center gap-1.5 leading-tight min-w-0" title="{{ $hTitle }} ({{ $cell['holiday']->category ?? $cell['holiday']->type }})">
                                                     <span class="w-1.5 h-1.5 rounded-full {{ $isCompany ? 'bg-rose-500' : 'bg-amber-400' }} shrink-0"></span>
                                                     <span class="truncate block min-w-0 font-medium">{{ $hTitle }}</span>
                                                 </div>
                                             @endif

                                             <!-- Birthday Pill -->
                                             @if(isset($cell['birthdays']) && count($cell['birthdays']) > 0)
                                                 @foreach($cell['birthdays'] as $bdayName)
                                                     <div class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/[0.05] border border-slate-200/60 dark:border-white/[0.08] text-[10px] font-medium text-slate-700 dark:text-[#E2E8F0] flex items-center gap-1.5 leading-tight min-w-0 tracking-tight" title="🎂 {{ $bdayName }}'s Birthday">
                                                         <i data-lucide="cake" class="w-3 h-3 text-purple-500 dark:text-purple-400 shrink-0"></i>
                                                         <span class="truncate block min-w-0 font-medium">{{ $bdayName }}</span>
                                                     </div>
                                                 @endforeach
                                             @endif

                                             <!-- My Leaves Pill in Calendar Cell (Only show logged-in employee's leaves) -->
                                             @php
                                                 $currentEmpId = $employee->id ?? null;
                                                 $currentUserId = Auth::id();
                                                 $myCellLeaves = collect($cell['leaves'] ?? [])->filter(function($l) use ($currentEmpId, $currentUserId) {
                                                     $empId = is_array($l) ? ($l['employee_id'] ?? null) : ($l->employee_id ?? null);
                                                     $userId = is_array($l) ? ($l['employee']['user_id'] ?? null) : ($l->employee?->user_id ?? null);
                                                     return ($currentEmpId && $empId == $currentEmpId) || ($currentUserId && $userId == $currentUserId);
                                                 })->values();
                                             @endphp
                                             @if($myCellLeaves->count() > 0)
                                                 @php
                                                     $myFirstLeave = $myCellLeaves->first();
                                                     $myLeaveTitle = is_array($myFirstLeave) 
                                                         ? ($myFirstLeave['employee_name'] ?? 'My Leave') 
                                                         : ($myFirstLeave->employee?->user?->name ?? 'My Leave');
                                                 @endphp
                                                 <div class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/[0.05] border border-slate-200/60 dark:border-white/[0.08] text-[10px] font-medium text-slate-700 dark:text-[#E2E8F0] flex items-center gap-1.5 leading-tight min-w-0" title="{{ $myLeaveTitle }} (On Leave)">
                                                     <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                                     <span class="truncate block min-w-0 font-medium">{{ $myLeaveTitle }}</span>
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
                    <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Upcoming Holidays & Scheduled Events ({{ $selectedDate->format('F Y') }})</h3>
                    
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

            <!-- Legend Bar (Clean SaaS Minimal Legend for Event Categories) -->
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between text-[11px] font-medium text-slate-500 dark:text-slate-400 shrink-0 gap-y-2 gap-x-4">
                <div class="flex items-center gap-3.5 flex-wrap">
                    <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> <span class="font-medium text-slate-600 dark:text-slate-300">Employee Leave</span></span>
                    <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> <span class="font-medium text-slate-600 dark:text-slate-300">Public / Mercantile Holiday</span></span>
                    <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> <span class="font-medium text-slate-600 dark:text-slate-300">Company Holiday</span></span>
                    <span class="flex items-center gap-1.5"><i data-lucide="cake" class="w-3.5 h-3.5 text-purple-500 dark:text-purple-400 shrink-0"></i> <span class="font-medium text-slate-600 dark:text-slate-300">Birthday</span></span>
                </div>
                <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-400"></i>
                    <span>Select any date to view who's on leave</span>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Action Buttons & Right Rail Cards (Matching Reference Mockup) -->
        <div class="flex flex-col gap-3">
            <!-- Action Buttons: Solid Primary Accent & Clean Secondary Border -->
            @php
                $activeRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Employee');
                $canAddHoliday = in_array($activeRole, ['HR Lead', 'Super (Admin)']);
            @endphp
            <div class="grid {{ $canAddHoliday ? 'grid-cols-2' : 'grid-cols-1' }} gap-2.5 shrink-0">
                <button @click="openApplyModal('ANNUAL')" class="bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-sm shadow-blue-600/20 transition-all cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4 stroke-[2.5] text-white"></i>
                    <span>Apply Leave</span>
                </button>

                @if($canAddHoliday)
                <button @click="addCompanyLeaveModal = true" class="bg-white hover:bg-slate-50 dark:bg-[#111927] dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition-all cursor-pointer active:scale-[0.99]">
                    <i data-lucide="calendar-plus" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
                    <span>Add Holiday</span>
                </button>
                @endif
            </div>

            <!-- Who's on Leave Card (Shows Today by default, updates when date selected on calendar) -->
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] flex flex-col space-y-3">
                <div class="flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-500/20">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 dark:text-slate-100">Who's on Leave</h3>
                            <p class="text-[10px] text-slate-500 dark:text-slate-300 font-medium" 
                               x-text="selectedDay ? (selectedDay.is_today ? ('Today • ' + selectedDay.formatted_date) : selectedDay.formatted_date) : 'Today'"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <template x-if="selectedDay && !selectedDay.is_today">
                            <button type="button" 
                                    @click="selectedDay = todayCell; $nextTick(() => window.lucide && lucide.createIcons())" 
                                    title="Reset view to Today"
                                    class="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-500/10 border border-blue-200/80 dark:border-blue-500/20 transition-all cursor-pointer">
                                Today
                            </button>
                        </template>
                        <span class="bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200/80 dark:border-blue-500/20 text-[10px] font-bold px-2 py-0.5 rounded-full" 
                              x-text="getSelectedDayLeaves().length"></span>
                        <button type="button" 
                                @click="dayModalOpen = true; $nextTick(() => window.lucide && lucide.createIcons())" 
                                title="View Day Summary Details" 
                                class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                            <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <!-- Leaves List for Selected Day / Today -->
                <div class="space-y-2 max-h-72 overflow-y-auto pr-1 [scrollbar-width:thin]">
                    <template x-if="getSelectedDayLeaves().length > 0">
                        <div class="space-y-2">
                            <template x-for="lv in getSelectedDayLeaves()" :key="'day-' + lv.id">
                                <div class="bg-slate-50/80 hover:bg-slate-100/80 dark:bg-[#0f172a] dark:hover:bg-[#182338] border border-slate-200/70 dark:border-slate-800 p-2.5 rounded-xl flex items-center justify-between gap-2 text-xs transition-all shadow-xs dark:shadow-none">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <!-- Avatar with Pastel Leave Type Color -->
                                        <div class="w-8 h-8 rounded-full text-white font-black text-xs flex items-center justify-center shrink-0 shadow-xs uppercase"
                                             :class="getLeaveTypeColor(lv.leave_type?.code || lv.leave_type?.name || lv.leaveType?.code || lv.leaveType?.name).avatar"
                                             x-text="lv.employee?.user?.name ? lv.employee.user.name.charAt(0) : (lv.employee_name ? lv.employee_name.charAt(0) : 'E')">
                                        </div>
                                        
                                        <div class="min-w-0">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs block" x-text="lv.employee?.user?.name || lv.employee_name || 'Employee'"></span>
                                        </div>
                                    </div>

                                    <!-- Leave Badge: Full Day, Half Day (Morning/Evening), or Short Leave (Morning/Evening) -->
                                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-md border shrink-0 whitespace-nowrap shadow-2xs"
                                          :class="getLeaveTypeColor(lv.leave_type?.code || lv.leave_type?.name || lv.leaveType?.code || lv.leaveType?.name).badge"
                                          x-text="getWhoIsOnLeaveText(lv)">
                                    </span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="getSelectedDayLeaves().length === 0">
                        <div class="text-xs text-slate-600 dark:text-slate-300 font-medium py-6 text-center bg-slate-100/70 dark:bg-[#0f172a] rounded-xl border border-dashed border-slate-300 dark:border-slate-700 flex flex-col items-center justify-center gap-1.5 shadow-2xs">
                            <i data-lucide="user-check" class="w-5 h-5 text-slate-500 dark:text-slate-400"></i>
                            <span x-text="selectedDay?.is_today ? 'No employees on leave today.' : 'No employees on leave on this day.'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Events & Birthdays Card (Shown only if there are events, holidays, or birthdays) -->
            <div x-show="hasEventsOrBirthdays()" 
                 x-cloak
                 class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] flex flex-col space-y-2">
                <div class="flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-amber-500 dark:text-amber-400"></i>
                        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-100">Events & Birthdays</h3>
                    </div>
                    <span class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-slate-200/80 dark:border-slate-700" x-text="selectedDay ? selectedDay.formatted_date : '{{ \Carbon\Carbon::now('Asia/Colombo')->format('M d, Y') }}'"></span>
                </div>

                <!-- Selected Day Event & Birthday Details -->
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <!-- 1. Employee Birthdays -->
                    <template x-if="selectedDay?.birthdays && selectedDay?.birthdays.length > 0">
                        <div class="p-3 bg-slate-50 dark:bg-white/[0.04] rounded-xl border border-slate-200/70 dark:border-white/[0.08] space-y-1.5">
                            <div class="text-[10px] font-semibold uppercase text-purple-600 dark:text-purple-300 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="cake" class="w-3.5 h-3.5 text-purple-500 dark:text-purple-400"></i>
                                <span>Employee Birthday Today</span>
                            </div>
                            <template x-for="bday in selectedDay.birthdays" :key="bday">
                                <div class="font-bold text-xs text-slate-800 dark:text-slate-100" x-text="bday + '\'s Birthday'"></div>
                            </template>
                        </div>
                    </template>

                    <!-- 2. Holidays / Events Scheduled Today -->
                    <template x-if="selectedDay?.holiday">
                        <div class="p-3 bg-slate-50 dark:bg-white/[0.04] rounded-xl border border-slate-200/70 dark:border-white/[0.08] space-y-1.5">
                            <div class="text-[10px] font-semibold uppercase text-amber-600 dark:text-amber-400 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="landmark" class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400"></i>
                                <span>Holiday Scheduled Today</span>
                            </div>
                            <div class="font-bold text-xs text-slate-800 dark:text-slate-100" x-text="selectedDay?.holiday?.title"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Lieu Days Card (Shown only if lieu days are added) -->
            @if(!empty($lieuDaysList) && count($lieuDaysList) > 0)
            <div class="bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08),0_2px_4px_-1px_rgba(15,23,42,0.04)] flex flex-col space-y-3.5">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight leading-snug">Lieu days</h3>
                        <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed">
                            Added when weekend or holiday work is approved. Expire after 30 days.
                        </p>
                    </div>
                    <button type="button" 
                            @click="openLieuClaimModal()" 
                            class="text-xs font-bold text-teal-700 dark:text-teal-300 hover:text-teal-800 dark:hover:text-teal-200 flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 dark:bg-teal-900/40 border border-teal-200 dark:border-teal-700/60 hover:bg-teal-100 transition-all cursor-pointer">
                        <i data-lucide="plus" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                        <span>Claim</span>
                    </button>
                </div>

                <!-- Lieu Days List -->
                <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @foreach($lieuDaysList as $item)
                        <div class="py-3 first:pt-1 last:pb-1 flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Left Indicator (Ring for Active, Clock for Pending, Dash for Used/Expired) -->
                                @if($item['status'] === 'available')
                                    <div class="relative w-10 h-10 shrink-0 flex items-center justify-center">
                                        <svg class="w-10 h-10 -rotate-90" viewBox="0 0 36 36">
                                            <!-- Background Track -->
                                            <circle cx="18" cy="18" r="14" fill="none" class="stroke-slate-100 dark:stroke-slate-800" stroke-width="2.5" />
                                            <!-- Progress Ring -->
                                            <circle cx="18" cy="18" r="14" fill="none" 
                                                    class="{{ $item['days_left'] <= 7 ? 'stroke-rose-400' : 'stroke-teal-500' }}" 
                                                    stroke-width="2.5" 
                                                    stroke-dasharray="88" 
                                                    stroke-dashoffset="{{ round(88 - (88 * ($item['days_left'] / 30))) }}" 
                                                    stroke-linecap="round" />
                                        </svg>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                            <span class="text-[11px] font-black text-slate-900 dark:text-slate-100 leading-none">{{ $item['days_left'] }}</span>
                                            <span class="text-[7.5px] font-extrabold text-slate-500 dark:text-slate-300 leading-none mt-0.5 uppercase tracking-wide">days</span>
                                        </div>
                                    </div>
                                @elseif($item['status'] === 'pending')
                                    <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200/70 dark:border-amber-800 flex items-center justify-center text-amber-500 font-bold shrink-0">
                                        <i data-lucide="clock" class="w-4 h-4"></i>
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#161f30] border border-slate-200/70 dark:border-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-400 font-bold text-sm shrink-0">
                                        <span>—</span>
                                    </div>
                                @endif

                                <!-- Details -->
                                <div class="min-w-0">
                                    <div class="font-extrabold text-slate-900 dark:text-slate-100 text-xs truncate">
                                        <span>{{ $item['type'] }}</span>
                                        <span class="text-slate-300 dark:text-slate-500 font-normal mx-0.5">•</span>
                                        <span class="font-bold text-slate-700 dark:text-slate-200">{{ $item['worked'] }}</span>
                                    </div>
                                    <div class="text-[10.5px] text-slate-600 dark:text-slate-300 mt-0.5 font-medium">
                                        @if($item['status'] === 'used')
                                            <span class="text-slate-600 dark:text-slate-300">{{ $item['subtext'] }}</span>
                                        @elseif($item['status'] === 'pending')
                                            <span class="text-amber-600 dark:text-amber-400 font-semibold">Awaiting Manager Approval</span>
                                            <span class="text-slate-300 dark:text-slate-500 mx-0.5">•</span>
                                            <span class="font-semibold text-slate-500 dark:text-slate-400">{{ $item['code'] }}</span>
                                        @elseif($item['status'] === 'rejected')
                                            <span class="text-rose-600 dark:text-rose-400 font-semibold">Claim Rejected</span>
                                        @elseif($item['status'] === 'expired')
                                            <span class="text-slate-500 dark:text-slate-400 font-medium">{{ $item['subtext'] }}</span>
                                        @else
                                            <span class="text-slate-600 dark:text-slate-300">Use by {{ $item['expiry_date'] }}</span>
                                            <span class="text-slate-300 dark:text-slate-500 mx-0.5">•</span>
                                            <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $item['code'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Right Action / Status Badge -->
                            <div class="shrink-0">
                                @if($item['status'] === 'used')
                                    <span class="bg-slate-100 dark:bg-white/[0.05] text-slate-500 dark:text-slate-400 border border-slate-200/80 dark:border-white/[0.08] text-[11px] font-medium px-2.5 py-1 rounded-lg">
                                        Used
                                    </span>
                                @elseif($item['status'] === 'pending')
                                    <span class="bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60 text-[11px] font-semibold px-2.5 py-1 rounded-lg">
                                        Pending
                                    </span>
                                @elseif($item['status'] === 'rejected')
                                    <span class="bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/60 text-[11px] font-semibold px-2.5 py-1 rounded-lg">
                                        Rejected
                                    </span>
                                @elseif($item['status'] === 'expired')
                                    <span class="bg-slate-100 dark:bg-white/[0.05] text-slate-500 dark:text-slate-400 border border-slate-200/80 dark:border-white/[0.08] text-[11px] font-medium px-2.5 py-1 rounded-lg">
                                        Expired
                                    </span>
                                @else
                                    <button type="button" 
                                            @click="openApplyModal('LIEU')" 
                                            class="bg-blue-50/80 hover:bg-blue-100 dark:bg-blue-500/10 dark:hover:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30 active:scale-95 font-semibold text-xs px-3 py-1.5 rounded-lg transition-all cursor-pointer shadow-none">
                                        Book it
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

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
                        <h4 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Employees Scheduled on Leave</h4>
                        <template x-if="getSelectedDayLeaves().length > 0">
                            <div class="space-y-2">
                                <template x-for="lv in getSelectedDayLeaves()" :key="lv.id">
                                    <div class="p-3 bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d] flex items-center justify-between gap-2 text-xs">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-8 h-8 rounded-full text-white flex items-center justify-center font-bold shrink-0"
                                                 :class="getLeaveTypeColor(lv.leave_type?.code || lv.leave_type?.name || lv.leaveType?.code || lv.leaveType?.name).avatar"
                                                 x-text="lv.employee?.user?.name ? lv.employee.user.name.charAt(0) : (lv.employee_name ? lv.employee_name.charAt(0) : 'E')"></div>
                                            <div class="font-bold text-slate-900 dark:text-slate-100 truncate" x-text="lv.employee?.user?.name || lv.employee_name || 'Employee'"></div>
                                        </div>
                                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-lg border shadow-2xs shrink-0 whitespace-nowrap"
                                              :class="getLeaveTypeColor(lv.leave_type?.code || lv.leave_type?.name || lv.leaveType?.code || lv.leaveType?.name).badge"
                                              x-text="getWhoIsOnLeaveText(lv)"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="getSelectedDayLeaves().length === 0">
                            <div class="p-3 text-xs text-slate-500 dark:text-slate-400 italic bg-slate-50 dark:bg-[#1c2128] rounded-xl border border-slate-200 dark:border-[#30363d]">No employees scheduled on leave for this date.</div>
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

    <!-- APPLY FOR LEAVE MODAL (COMPACT ADAPTIVE - NO SCROLLBAR) -->
    <div x-show="applyLeaveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-900/60 dark:bg-black/80 backdrop-blur-md overflow-hidden">
        <div class="bg-white dark:bg-[#111622] text-slate-800 dark:text-slate-100 rounded-2xl max-w-5xl w-full p-4 sm:p-5 lg:p-6 shadow-2xl border border-slate-200/90 dark:border-slate-800 my-auto relative max-h-[96vh] flex flex-col overflow-hidden" 
             @click.away="applyLeaveModal = false">
            
            <!-- Close Button Top Right -->
            <button @click="applyLeaveModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 z-20">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>

            <!-- Error Banner -->
            @if($errors->any())
                <div class="mb-2 bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 p-2 rounded-lg text-xs space-y-0.5">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-1.5"><i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-500 dark:text-rose-400 shrink-0"></i> <span>{{ $err }}</span></div>
                    @endforeach
                </div>
            @endif

            <!-- Modal Form with 2-Column Layout (Scrollbar hidden) -->
            <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" class="flex-1 min-h-0 flex flex-col justify-between overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                @csrf
                
                <!-- Hidden inputs to submit all needed data -->
                <input type="hidden" name="leave_type_id" :value="selectedLeaveTypeId">
                <input type="hidden" name="is_half_day" :value="isHalfDay || (selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY' && nightShiftConcession?.category === 'half_day_off') ? '1' : '0'">
                <input type="hidden" name="half_day_slot" :value="selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY' && nightShiftConcession?.category === 'half_day_off' ? 'Morning' : halfDaySlot">
                <input type="hidden" name="is_short_leave" :value="isShortLeave || selectedLeaveTypeCode === 'SHORT' ? '1' : '0'">
                <input type="hidden" name="short_leave_slot" :value="shortLeaveSlot">
                <input type="hidden" name="night_sign_out_time" :value="selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY' ? nightSignOutTime : ''">
                <input type="hidden" name="night_report_time" :value="selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY' && nightShiftConcession ? (nightShiftConcession.reportTime || '') : ''">
                <input type="hidden" name="concession_category" :value="selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY' && nightShiftConcession ? (nightShiftConcession.category || '') : ''">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 pb-1">
                    
                    <!-- Left Column: Form Details (8 cols) -->
                    <div class="lg:col-span-7 xl:col-span-8 space-y-3">
                        
                        <!-- Header -->
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight leading-tight">Apply for leave</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Every rule is checked as you fill this in.</p>
                        </div>

                        <!-- Leave Type Selection Cards -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Leave Type</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                                <template x-for="item in leaveCatalog" :key="item.code">
                                    <div @click="selectLeaveType(item.code, item.id)"
                                         :class="selectedLeaveTypeCode === item.code 
                                            ? 'bg-amber-500/10 border-amber-500 dark:border-amber-400/90 ring-1 ring-amber-500/40 dark:ring-amber-400/40 shadow-xs' 
                                            : 'bg-slate-50 hover:bg-slate-100/80 dark:bg-[#182030]/80 dark:hover:bg-[#1e283d] border-slate-200/90 hover:border-slate-300 dark:border-slate-800/80 dark:hover:border-slate-700'"
                                         class="px-2.5 py-1.5 rounded-lg border text-left cursor-pointer transition-all flex flex-col justify-between min-h-[46px]">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="item.dotColor"></span>
                                            <span class="text-[11px] font-bold truncate leading-tight" :class="selectedLeaveTypeCode === item.code ? 'text-amber-900 dark:text-amber-300' : 'text-slate-800 dark:text-slate-200'" x-text="item.name"></span>
                                        </div>
                                        <span class="text-[10px] font-medium pl-3 truncate leading-tight" :class="selectedLeaveTypeCode === item.code ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'" x-text="item.subtext"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Dates Row: FIRST DAY and LAST DAY -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                                    <span>First day</span>
                                </label>
                                <input type="date" name="start_date" x-model="targetStartDate" 
                                       @change="
                                           if (selectedLeaveTypeCode === 'SHORT' || isHalfDay || !targetEndDate || targetEndDate < targetStartDate) {
                                               targetEndDate = targetStartDate;
                                           }
                                       "
                                       class="w-full bg-slate-50 dark:bg-[#182030] border border-slate-200 dark:border-slate-700/80 hover:border-slate-300 dark:hover:border-slate-600 focus:border-amber-500 dark:focus:border-amber-400 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-amber-400 transition-all" required>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                                    <span>Last day</span>
                                </label>
                                <input type="date" name="end_date" x-model="targetEndDate" 
                                       :disabled="selectedLeaveTypeCode === 'SHORT' || selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY' || isHalfDay"
                                       class="w-full bg-slate-50 dark:bg-[#182030] border border-slate-200 dark:border-slate-700/80 hover:border-slate-300 dark:hover:border-slate-600 focus:border-amber-500 dark:focus:border-amber-400 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-amber-400 transition-all disabled:opacity-40 disabled:cursor-not-allowed" required>
                            </div>
                        </div>

                        <!-- Half Day & Short Leave Options -->
                        <div class="space-y-2">
                            <!-- Half Day Toggle -->
                            <div x-show="selectedLeaveTypeCode !== 'SHORT' && selectedLeaveTypeCode !== 'NIGHT_SHIFT_RECOVERY'" class="p-2 bg-slate-50/80 dark:bg-[#182030]/60 rounded-lg border border-slate-200/90 dark:border-slate-800 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                        <input type="checkbox" x-model="isHalfDay" 
                                               @change="if (isHalfDay) targetEndDate = targetStartDate;" 
                                               class="rounded bg-white dark:bg-[#111622] border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-amber-400/30 w-3.5 h-3.5">
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Apply as Half Day (0.5 days)</span>
                                    </label>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold" x-show="isHalfDay">Single date application</span>
                                </div>

                                <div x-show="isHalfDay" class="grid grid-cols-2 gap-2 pt-0.5">
                                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg border text-[11px] cursor-pointer transition-all"
                                           :class="halfDaySlot === 'Morning' ? 'bg-amber-500/10 border-amber-500/70 text-amber-900 dark:text-amber-300 font-semibold' : 'bg-white dark:bg-[#141b29] border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700'">
                                        <input type="radio" x-model="halfDaySlot" value="Morning" class="text-amber-500 w-3 h-3">
                                        <span class="font-semibold">Morning (9:00 AM – 1:00 PM)</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg border text-[11px] cursor-pointer transition-all"
                                           :class="halfDaySlot === 'Afternoon' ? 'bg-amber-500/10 border-amber-500/70 text-amber-900 dark:text-amber-300 font-semibold' : 'bg-white dark:bg-[#141b29] border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700'">
                                        <input type="radio" x-model="halfDaySlot" value="Afternoon" class="text-amber-500 w-3 h-3">
                                        <span class="font-semibold">Afternoon (1:00 PM – 5:00 PM)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Short Leave Slot Selector -->
                            <div x-show="selectedLeaveTypeCode === 'SHORT'" class="p-2 bg-amber-50/70 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-lg space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-amber-900 dark:text-amber-300 flex items-center gap-1.5">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i> Short Leave Slot Selection
                                    </span>
                                    <span class="text-[10px] font-black text-amber-800 dark:text-amber-400 bg-amber-100 dark:bg-amber-500/20 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-500/30">0.2 Days · Max 2 / Month</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg border text-[11px] cursor-pointer transition-all"
                                           :class="shortLeaveSlot === '9:30 AM–10:00 AM' ? 'bg-amber-500/20 border-amber-500 text-amber-950 dark:text-white font-bold' : 'bg-white dark:bg-[#141b29] border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700'">
                                        <input type="radio" x-model="shortLeaveSlot" value="9:30 AM–10:00 AM" class="text-amber-500 w-3 h-3">
                                        <span class="font-semibold">Morning (9:30 AM – 10:00 AM)</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg border text-[11px] cursor-pointer transition-all"
                                           :class="shortLeaveSlot === '3:30 PM–5:00 PM' ? 'bg-amber-500/20 border-amber-500 text-amber-950 dark:text-white font-bold' : 'bg-white dark:bg-[#141b29] border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700'">
                                        <input type="radio" x-model="shortLeaveSlot" value="3:30 PM–5:00 PM" class="text-amber-500 w-3 h-3">
                                        <span class="font-semibold">Evening (3:30 PM – 5:00 PM)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Duty Leave Project/Client Input -->
                            <div x-show="selectedLeaveTypeCode === 'DUTY'" class="space-y-1">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Project / Client Name *</label>
                                <input type="text" name="project_client_name" x-model="projectClientName" placeholder="e.g. Dialog Enterprise Offsite Client Meeting" class="w-full bg-slate-50 dark:bg-[#182030] border border-slate-200 dark:border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-amber-500 dark:focus:border-amber-400">
                            </div>

                            <!-- Medical Certificate Upload -->
                            <div x-show="selectedLeaveTypeCode === 'MEDICAL'" class="p-2 bg-slate-50/80 dark:bg-[#182030]/60 rounded-lg border border-slate-200/90 dark:border-slate-800 space-y-1">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Upload Medical Certificate</label>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">Required if sick leave exceeds 3 consecutive working days (PDF, JPG, PNG).</p>
                                <input type="file" name="medical_certificate_file" class="w-full text-xs text-slate-700 dark:text-slate-300 file:mr-2 file:py-0.5 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-bold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-400">
                            </div>

                            <!-- Night Shift Recovery Concession Section -->
                            <div x-show="selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY'" class="p-3 bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800/60 rounded-xl space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-indigo-950 dark:text-indigo-200 flex items-center gap-1.5">
                                        <i data-lucide="moon" class="w-4 h-4 text-indigo-500"></i> Night Work Concession Policy
                                    </span>
                                    <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/50 px-2 py-0.5 rounded-full border border-indigo-200 dark:border-indigo-800">
                                        HOD Approval Required
                                    </span>
                                </div>

                                <!-- Policy Concessions Grid -->
                                <div class="grid grid-cols-2 gap-1.5 text-[10px]">
                                    <div class="p-1.5 rounded-lg bg-white/80 dark:bg-[#141b29]/80 border border-indigo-100 dark:border-indigo-900/40">
                                        <span class="text-slate-500 dark:text-slate-400 block font-medium">9.00 p.m. – 10.30 p.m.</span>
                                        <span class="font-bold text-indigo-900 dark:text-indigo-300">Report at 10.00 a.m.</span>
                                    </div>
                                    <div class="p-1.5 rounded-lg bg-white/80 dark:bg-[#141b29]/80 border border-indigo-100 dark:border-indigo-900/40">
                                        <span class="text-slate-500 dark:text-slate-400 block font-medium">10.31 p.m. – 12.00 p.m.</span>
                                        <span class="font-bold text-indigo-900 dark:text-indigo-300">Report at 11.00 a.m.</span>
                                    </div>
                                    <div class="p-1.5 rounded-lg bg-white/80 dark:bg-[#141b29]/80 border border-indigo-100 dark:border-indigo-900/40">
                                        <span class="text-slate-500 dark:text-slate-400 block font-medium">12.01 a.m. – 3.00 a.m.</span>
                                        <span class="font-bold text-emerald-700 dark:text-emerald-400">Half day off</span>
                                    </div>
                                    <div class="p-1.5 rounded-lg bg-white/80 dark:bg-[#141b29]/80 border border-indigo-100 dark:border-indigo-900/40">
                                        <span class="text-slate-500 dark:text-slate-400 block font-medium">Beyond 3.00 a.m.</span>
                                        <span class="font-bold text-emerald-700 dark:text-emerald-400">Full day off</span>
                                    </div>
                                </div>

                                <!-- Sign Out Time Input -->
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                        Sign Out Time (Late Night / Early Morning) *
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input type="time" name="night_sign_out_time" x-model="nightSignOutTime"
                                               class="w-full bg-white dark:bg-[#141b29] border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                               :required="selectedLeaveTypeCode === 'NIGHT_SHIFT_RECOVERY'">
                                        <div class="flex gap-1 shrink-0">
                                            <button type="button" @click="nightSignOutTime = '21:45'" class="px-2 py-1 text-[10px] font-bold rounded bg-white dark:bg-[#141b29] border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-indigo-400">9:45 PM</button>
                                            <button type="button" @click="nightSignOutTime = '23:15'" class="px-2 py-1 text-[10px] font-bold rounded bg-white dark:bg-[#141b29] border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-indigo-400">11:15 PM</button>
                                            <button type="button" @click="nightSignOutTime = '01:30'" class="px-2 py-1 text-[10px] font-bold rounded bg-white dark:bg-[#141b29] border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-indigo-400">1:30 AM</button>
                                            <button type="button" @click="nightSignOutTime = '04:00'" class="px-2 py-1 text-[10px] font-bold rounded bg-white dark:bg-[#141b29] border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-indigo-400">4:00 AM</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Concession Summary Badge -->
                                <template x-if="nightShiftConcession">
                                    <div class="p-2.5 rounded-lg border text-xs"
                                         :class="nightShiftConcession.isOff ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-900 dark:text-emerald-200' : (nightShiftConcession.invalid ? 'bg-amber-500/10 border-amber-500/30 text-amber-900 dark:text-amber-200' : 'bg-blue-500/10 border-blue-500/30 text-blue-900 dark:text-blue-200')">
                                        <div class="flex items-start gap-2">
                                            <i data-lucide="sparkles" class="w-4 h-4 mt-0.5 text-indigo-500 shrink-0"></i>
                                            <div class="space-y-0.5">
                                                <div class="font-bold flex items-center gap-2">
                                                    <span>Granted Concession:</span>
                                                    <span class="px-2 py-0.5 rounded bg-white dark:bg-[#111927] border text-xs font-black"
                                                          :class="nightShiftConcession.isOff ? 'text-emerald-600 dark:text-emerald-400 border-emerald-300 dark:border-emerald-700' : 'text-blue-600 dark:text-blue-400 border-blue-300 dark:border-blue-700'"
                                                          x-text="nightShiftConcession.label"></span>
                                                </div>
                                                <p class="text-[11px] opacity-90" x-text="nightShiftConcession.description"></p>
                                                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium pt-0.5">
                                                    ✓ Automatically accommodated in attendance so you will not be penalized for late arrival or absence.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Covering Colleague -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Covering for you</label>
                            <div class="relative">
                                <select name="covering_employee_id" x-model="coveringEmployeeId"
                                        @change="coveringEmployeeName = employeesMap[coveringEmployeeId] || ''"
                                        class="w-full appearance-none bg-slate-50 dark:bg-[#182030] border border-slate-200 dark:border-slate-700/80 hover:border-slate-300 dark:hover:border-slate-600 focus:border-amber-500 dark:focus:border-amber-400 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-amber-400 cursor-pointer">
                                    <option value="" class="bg-white dark:bg-[#141b29] text-slate-400">Choose...</option>
                                    @foreach($allEmployees ?? [] as $empOpt)
                                        <option value="{{ $empOpt->id }}" class="bg-white dark:bg-[#141b29] text-slate-800 dark:text-white">
                                            {{ $empOpt->user->name ?? 'Employee' }} ({{ $empOpt->employee_id_number }})
                                        </option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Single Handover Brief / Notes Field -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Brief / Handover Notes</label>
                            <textarea name="handover_notes" x-model="handoverNotes" rows="2" 
                                      placeholder="Brief details, live jobs, deadlines, file locations or context for your manager..."
                                      class="w-full bg-slate-50 dark:bg-[#182030] border border-slate-200 dark:border-slate-700/80 hover:border-slate-300 dark:hover:border-slate-600 focus:border-amber-500 dark:focus:border-amber-400 rounded-lg p-2 text-xs font-medium text-slate-800 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 resize-none transition-all"></textarea>
                        </div>

                    </div>

                    <!-- Right Column: Policy Check (4 cols) -->
                    <div class="lg:col-span-5 xl:col-span-4 flex flex-col justify-between space-y-3 pt-1 lg:pt-0 lg:border-l lg:border-slate-200/90 dark:lg:border-slate-800 lg:pl-5">
                        
                        <div class="space-y-3">
                            <!-- Policy Check Header -->
                            <div>
                                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Policy check</h3>
                            </div>

                            <!-- Live Summary Card -->
                            <div class="p-2.5 bg-slate-50 dark:bg-[#182030] border border-slate-200/90 dark:border-slate-800 rounded-xl flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate" x-text="formatDateRangeSummary()"></span>
                            </div>

                            <!-- Policy Checklist Items (Dynamic Checkmarks & Crosses) -->
                            <div class="space-y-2 text-[11px]">
                                
                                <!-- 1. Clash Check -->
                                <div class="flex items-start gap-2">
                                    <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] font-black shrink-0 mt-0.5 border"
                                          :class="checkClash().pass ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 dark:border-emerald-500/40' : 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/30 dark:border-rose-500/40'"
                                          x-text="checkClash().pass ? '✓' : '✕'"></span>
                                    <span class="font-medium leading-snug" 
                                          :class="checkClash().pass ? 'text-slate-700 dark:text-slate-300' : 'text-rose-600 dark:text-rose-300 font-semibold'" 
                                          x-text="checkClash().message"></span>
                                </div>

                                <!-- 2. Notice Period -->
                                <div class="flex items-start gap-2">
                                    <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] font-black shrink-0 mt-0.5 border"
                                          :class="checkNoticePeriod().pass ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 dark:border-emerald-500/40' : 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/30 dark:border-rose-500/40'"
                                          x-text="checkNoticePeriod().pass ? '✓' : '✕'"></span>
                                    <span class="font-medium leading-snug" 
                                          :class="checkNoticePeriod().pass ? 'text-slate-700 dark:text-slate-300' : 'text-rose-600 dark:text-rose-300 font-semibold'" 
                                          x-text="checkNoticePeriod().message"></span>
                                </div>

                                <!-- 3. Balance Check -->
                                <div class="flex items-start gap-2">
                                    <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] font-black shrink-0 mt-0.5 border"
                                          :class="checkBalance().pass ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 dark:border-emerald-500/40' : 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/30 dark:border-rose-500/40'"
                                          x-text="checkBalance().pass ? '✓' : '✕'"></span>
                                    <span class="font-medium leading-snug" 
                                          :class="checkBalance().pass ? 'text-slate-700 dark:text-slate-300' : 'text-rose-600 dark:text-rose-300 font-semibold'" 
                                          x-text="checkBalance().message"></span>
                                </div>

                                <!-- 4. Consecutive Working Days -->
                                <div class="flex items-start gap-2">
                                    <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] font-black shrink-0 mt-0.5 border"
                                          :class="checkConsecutiveDays().pass ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 dark:border-emerald-500/40' : 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/30 dark:border-rose-500/40'"
                                          x-text="checkConsecutiveDays().pass ? '✓' : '✕'"></span>
                                    <span class="font-medium leading-snug" 
                                          :class="checkConsecutiveDays().pass ? 'text-slate-700 dark:text-slate-300' : 'text-rose-600 dark:text-rose-300 font-semibold'" 
                                          x-text="checkConsecutiveDays().message"></span>
                                </div>

                                <!-- 5. Covering Colleague -->
                                <div class="flex items-start gap-2">
                                    <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] font-black shrink-0 mt-0.5 border"
                                          :class="checkCovering().pass ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 dark:border-emerald-500/40' : 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/30 dark:border-rose-500/40'"
                                          x-text="checkCovering().pass ? '✓' : '✕'"></span>
                                    <span class="font-medium leading-snug" 
                                          :class="checkCovering().pass ? 'text-slate-700 dark:text-slate-300' : 'text-rose-600 dark:text-rose-300 font-semibold'" 
                                          x-text="checkCovering().message"></span>
                                </div>

                                <!-- 6. Handover Brief -->
                                <div class="flex items-start gap-2">
                                    <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] font-black shrink-0 mt-0.5 border"
                                          :class="checkHandoverNotes().pass ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 dark:border-emerald-500/40' : 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/30 dark:border-rose-500/40'"
                                          x-text="checkHandoverNotes().pass ? '✓' : '✕'"></span>
                                    <span class="font-medium leading-snug" 
                                          :class="checkHandoverNotes().pass ? 'text-slate-700 dark:text-slate-300' : 'text-rose-600 dark:text-rose-300 font-semibold'" 
                                          x-text="checkHandoverNotes().message"></span>
                                </div>

                            </div>
                        </div>

                        <!-- Action Submit Section -->
                        <div class="space-y-1.5 pt-2">
                            <button type="submit"
                                    class="w-full py-2.5 px-4 bg-amber-400 hover:bg-amber-300 active:scale-[0.99] text-slate-950 font-black text-xs rounded-xl shadow-md shadow-amber-400/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                <span>Send to {{ $managerName }}</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </button>

                            <template x-if="allChecksPass()">
                                <p class="text-[10px] text-center font-bold text-emerald-600 dark:text-emerald-400 flex items-center justify-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3"></i> All checks verified. Ready to send.
                                </p>
                            </template>
                            <template x-if="!allChecksPass()">
                                <p class="text-[10px] text-center font-bold text-slate-500 dark:text-slate-400">
                                    Fix the items marked ✕ to send.
                                </p>
                            </template>
                        </div>

                    </div>

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
                <button @click="quotaModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-[#21262d] text-slate-900 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-[#2d333b] font-black text-xs rounded-xl transition-all border border-slate-200 dark:border-[#30363d] cursor-pointer">
                    Close
                </button>
                <div class="flex items-center gap-2">
                    <template x-if="selectedQuota?.code === 'LIEU'">
                        <div class="flex items-center gap-2">
                            <!-- Primary Button: Request Lieu Leave (Employee enters work hours and sends to manager) -->
                            <button type="button" @click="quotaModalOpen = false; openLieuClaimModal()" class="px-4 py-2 bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs rounded-xl shadow-md shadow-teal-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="clock" class="w-4 h-4 text-white"></i>
                                <span>Request Lieu Leave</span>
                            </button>

                            <!-- If employee already has approved available days, allow booking -->
                            <template x-if="parseFloat(selectedQuota?.available || 0) > 0">
                                <button type="button" @click="quotaModalOpen = false; openApplyModal('LIEU')" class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 font-black text-xs rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    <span>Book Lieu Leave</span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <template x-if="selectedQuota?.code !== 'LIEU'">
                        <button @click="quotaModalOpen = false; openApplyModal(selectedQuota?.code)" class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 font-black text-xs rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="plus" class="w-4 h-4"></i> Apply for <span x-text="selectedQuota?.name"></span>
                        </button>
                    </template>
                </div>
            </div>

        </div>
    </div>

    <!-- REQUEST LIEU LEAVE MODAL -->
    <div x-show="lieuClaimModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/70 dark:bg-black/80 backdrop-blur-md transition-opacity overflow-hidden"
         @keydown.escape.window="lieuClaimModalOpen = false">
        
        <div @click.away="lieuClaimModalOpen = false" 
             class="bg-white dark:bg-[#111927] rounded-2xl max-w-lg w-full border border-slate-200/90 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between shrink-0 bg-white dark:bg-[#111927]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center border border-teal-200/80 dark:border-teal-500/20 shrink-0">
                        <i data-lucide="clock" class="w-4 h-4 stroke-[2.5]"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 leading-tight">Request Lieu Leave</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Claim credit for weekend or holiday work</p>
                    </div>
                </div>
                <button type="button" @click="lieuClaimModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="{{ route('lieu-claims.store') }}" method="POST" class="flex-1 min-h-0 flex flex-col overflow-hidden">
                @csrf

                <!-- Body -->
                <div class="p-5 space-y-3.5 overflow-y-auto flex-1 [scrollbar-width:thin]">

                    <!-- Date Worked -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            Date Worked <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            </div>
                            <input type="date" 
                                   name="worked_date" 
                                   x-model="lieuWorkedDate" 
                                   max="{{ \Carbon\Carbon::now('Asia/Colombo')->toDateString() }}" 
                                   required 
                                   class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-[#161f30] border border-slate-200 dark:border-slate-700/80 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-teal-500 transition-all cursor-pointer">
                        </div>

                        <!-- Date Status (One-liner badge/alert) -->
                        <template x-if="lieuDateMeta">
                            <div>
                                <template x-if="!lieuDateMeta.isEligible">
                                    <div class="py-1 px-2.5 rounded-md bg-amber-500/10 border border-amber-300 dark:border-amber-700/60 flex items-center gap-1.5 text-[11px] text-amber-800 dark:text-amber-300">
                                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                                        <span><strong>Weekday selected:</strong> Lieu Leave requires weekend or public holiday work.</span>
                                    </div>
                                </template>

                                <template x-if="lieuDateMeta.isEligible">
                                    <div class="py-1 px-2.5 rounded-md bg-emerald-500/10 border border-emerald-300 dark:border-emerald-800/60 flex items-center justify-between text-[11px] text-emerald-800 dark:text-emerald-300">
                                        <span class="font-semibold" x-text="lieuDateMeta.dayName + ' • ' + lieuDateMeta.label"></span>
                                        <span class="font-bold">✓ Eligible</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Time Range & Hours Worked (Time Range First, Hours Worked Smaller) -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-0.5 items-start">
                        <!-- 1. Time Range (First, Wider for Start & End Time) -->
                        <div class="sm:col-span-8 space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Time Range
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <input type="time" 
                                           name="start_time" 
                                           x-model="lieuStartTime" 
                                           @input="onStartTimeChange()" 
                                           class="w-full px-2.5 py-2 bg-slate-50 dark:bg-[#161f30] border border-slate-200 dark:border-slate-700/80 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                </div>
                                <div>
                                    <input type="time" 
                                           name="end_time" 
                                           x-model="lieuEndTime" 
                                           @input="onEndTimeChange()" 
                                           class="w-full px-2.5 py-2 bg-slate-50 dark:bg-[#161f30] border border-slate-200 dark:border-slate-700/80 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                </div>
                            </div>
                        </div>

                        <!-- 2. Hours Worked (Second, Compact/Smaller Width) -->
                        <div class="sm:col-span-4 space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Hours Worked <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="timer" class="w-3.5 h-3.5"></i>
                                </div>
                                <input type="number" 
                                       name="hours_worked" 
                                       x-model.number="lieuWorkedHours" 
                                       @input="syncEndTimeFromHours()"
                                       min="1" 
                                       max="24" 
                                       step="0.5" 
                                       required 
                                       class="w-full pl-8 pr-8 py-2 bg-slate-50 dark:bg-[#161f30] border border-slate-200 dark:border-slate-700/80 rounded-lg text-xs font-bold text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10.5px] font-semibold text-slate-400">hrs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Clean Granted Duration Strip -->
                    <div class="p-2.5 px-3 rounded-lg border flex items-center justify-between text-xs transition-all" :class="calculatedLieuDuration.containerClass">
                        <input type="hidden" name="duration_type" :value="calculatedLieuDuration.type">
                        <div class="flex items-center gap-2">
                            <i data-lucide="award" class="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0"></i>
                            <span class="font-bold text-slate-900 dark:text-slate-100" x-text="calculatedLieuDuration.label"></span>
                            <span class="text-slate-500 dark:text-slate-400 text-[11px]" x-text="'(' + lieuWorkedHours + ' hrs)'"></span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold" :class="calculatedLieuDuration.badgeClass" x-text="calculatedLieuDuration.creditLabel"></span>
                    </div>

                    <!-- Tasks Completed -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            Tasks Completed <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="reason" 
                                  x-model="lieuReason" 
                                  rows="2" 
                                  required 
                                  placeholder="Briefly describe work completed..." 
                                  class="w-full p-2.5 bg-slate-50 dark:bg-[#161f30] border border-slate-200 dark:border-slate-700/80 rounded-lg text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-500"></textarea>
                    </div>

                </div>

                <!-- Modal Actions Footer -->
                <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/70 dark:bg-[#0e1522] flex items-center justify-between gap-3 shrink-0">
                    <span class="text-[10.5px] text-slate-400 font-medium">30-day validity upon approval</span>
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="lieuClaimModalOpen = false" 
                                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" 
                                :disabled="!lieuDateMeta || !lieuDateMeta.isEligible || !calculatedLieuDuration.valid"
                                :class="(!lieuDateMeta || !lieuDateMeta.isEligible || !calculatedLieuDuration.valid) ? 'opacity-50 cursor-not-allowed bg-slate-400 dark:bg-slate-700' : 'bg-teal-600 hover:bg-teal-700 text-white shadow-xs cursor-pointer'"
                                class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            <span>Send to Manager for Approval</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


</div>
@endsection
