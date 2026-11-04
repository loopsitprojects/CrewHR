<?php

namespace Modules\Leave\Services;

use Modules\Employee\Models\Employee;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\Holiday;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class LeavePolicyService
{
    /**
     * Calculate net working days excluding weekends (Saturday/Sunday configurable)
     * and official public/company holidays in the date range.
     */
    public function calculateWorkingDays(string $startDateStr, string $endDateStr): float
    {
        $startDate = Carbon::parse($startDateStr, 'Asia/Colombo');
        $endDate = Carbon::parse($endDateStr, 'Asia/Colombo');

        if ($startDate->gt($endDate)) {
            return 0.0;
        }

        // Fetch all Holidays (Public, Mercantile, Bank, and Company) in the range
        $holidays = Holiday::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Check configured weekend days off (defaults to Sat & Sun off)
        $satOff = DB::table('settings')->where('key', 'weekend_saturday_off')->value('value') ?? '1';
        $sunOff = DB::table('settings')->where('key', 'weekend_sunday_off')->value('value') ?? '1';

        $workingDays = 0.0;
        $curr = $startDate->copy();

        while ($curr->lte($endDate)) {
            $isSat = $curr->isSaturday();
            $isSun = $curr->isSunday();
            $isWeekendDayOff = ($isSat && $satOff === '1') || ($isSun && $sunOff === '1');
            $isHoliday = in_array($curr->toDateString(), $holidays);

            // Exclude both weekends and public / company holidays
            if (!$isWeekendDayOff && !$isHoliday) {
                $workingDays += 1.0;
            }
            $curr->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate Annual Leave Quota based on joining year pro-rata rules
     */
    public function calculateAnnualLeaveQuota(Employee $employee, int $year): float
    {
        if (!$employee->joined_date) return 14.0;

        $joinedCarbon = Carbon::parse($employee->joined_date);
        $joinedYear = $joinedCarbon->year;
        $joinedMonth = $joinedCarbon->month;

        // 1st Year: You don't get annual leave yet
        if ($joinedYear === $year) {
            return 0.0;
        }

        // 2nd Year: Days depend on when you joined in your first year
        if ($joinedYear === ($year - 1)) {
            if ($joinedMonth >= 1 && $joinedMonth <= 3) return 14.0;
            if ($joinedMonth >= 4 && $joinedMonth <= 6) return 10.0;
            if ($joinedMonth >= 7 && $joinedMonth <= 9) return 7.0;
            return 4.0; // Oct-Dec
        }

        // 3rd Year & Beyond: Full 14 days
        return 14.0;
    }

    /**
     * Calculate Casual Leave Quota (1st yr: 0.5d/month, 2nd yr+: 7 days)
     */
    public function calculateCasualLeaveQuota(Employee $employee, int $year): float
    {
        if (!$employee->joined_date) return 7.0;

        $joinedCarbon = Carbon::parse($employee->joined_date);

        // 1st Year: Earn 0.5 day for every 1 full month worked
        if ($joinedCarbon->year === $year) {
            $now = Carbon::now('Asia/Colombo');
            $fullMonths = max(0, $joinedCarbon->diffInMonths($now));
            return min(7.0, round($fullMonths * 0.5, 1));
        }

        // 2nd Year & Beyond: 7 days
        return 7.0;
    }

    /**
     * Check if employee is on Probation or Intern
     */
    public function isProbationOrIntern(Employee $employee): bool
    {
        $cat = strtolower($employee->job_category ?? '');
        return str_contains($cat, 'probation') || str_contains($cat, 'intern');
    }

    /**
     * Validate leave application against all LOOPS HR Leave Brief rules
     */
    public function validateLeaveApplication(Employee $employee, LeaveType $leaveType, array $data, float $workingDays): void
    {
        $typeCode = strtoupper($leaveType->code);

        // 0. Active Leave Type Rule: Inactive leave types cannot be applied for
        if (!$leaveType->is_active) {
            throw ValidationException::withMessages([
                'leave_type_id' => "The leave type '{$leaveType->name}' is currently deactivated by system administrator."
            ]);
        }

        // 0.1 Same-Day Overlap / Clash Rule:
        // Leaves allocated for the user cannot have different leaves added on the same day,
        // unless it is a short leave or a complementary half-day slot.
        $newStartDate = $data['start_date'] ?? null;
        $newEndDate = $data['end_date'] ?? $newStartDate;
        $newIsHalfDay = !empty($data['is_half_day']) || ($data['concession_category'] ?? null) === 'half_day_off' || $workingDays === 0.5;
        $newHalfDaySlot = $data['half_day_slot'] ?? 'Morning';
        $newIsShortLeave = !empty($data['is_short_leave']) || $typeCode === 'SHORT';
        $newShortLeaveSlot = $data['short_leave_slot'] ?? null;
        $newConcessionCategory = $data['concession_category'] ?? null;
        $newIsFullDay = !$newIsHalfDay && !$newIsShortLeave && !in_array($newConcessionCategory, ['report_10am', 'report_11am']);

        if ($newStartDate && $newEndDate) {
            $overlappingLeaves = LeaveRequest::with('leaveType')
                ->where('employee_id', $employee->id)
                ->whereNotIn('status', ['Rejected', 'Canceled', 'Cancelled'])
                ->where('start_date', '<=', $newEndDate)
                ->where('end_date', '>=', $newStartDate)
                ->get();

            foreach ($overlappingLeaves as $existing) {
                $existingTypeCode = strtoupper($existing->leaveType?->code ?? '');
                $existingTypeName = $existing->leaveType?->name ?? 'Leave';
                $existingIsHalfDay = (bool)$existing->is_half_day || ($existing->concession_category === 'half_day_off') || ((float)$existing->duration === 0.5 && !$existing->is_short_leave);
                $existingHalfDaySlot = $existing->half_day_slot ?: 'Morning';
                $existingIsShortLeave = (bool)$existing->is_short_leave || $existingTypeCode === 'SHORT' || ((float)$existing->duration < 0.5 && (float)$existing->duration > 0.0);
                $existingShortLeaveSlot = $existing->short_leave_slot;
                $existingIsFullDay = !$existingIsHalfDay && !$existingIsShortLeave && !in_array($existing->concession_category, ['report_10am', 'report_11am']);

                // Rule A: Existing is a Full-Day leave -> Blocks ANY other leave on this date
                if ($existingIsFullDay) {
                    throw ValidationException::withMessages([
                        'start_date' => "Cannot apply leave: you already have an active {$existingTypeName} booked ({$existing->start_date} to {$existing->end_date}). Multiple leaves cannot be added on the same day."
                    ]);
                }

                // Rule B: New leave is a Full-Day leave -> Clashes with ANY existing leave on this date
                if ($newIsFullDay) {
                    throw ValidationException::withMessages([
                        'start_date' => "Cannot apply full-day {$leaveType->name}: you already have an active {$existingTypeName} booked on {$existing->start_date}. Multiple leaves cannot be added on the same day unless applying for a half day or short leave."
                    ]);
                }

                // Rule C: Both are Half-Day leaves
                if ($newIsHalfDay && $existingIsHalfDay) {
                    if ($newHalfDaySlot === $existingHalfDaySlot) {
                        throw ValidationException::withMessages([
                            'start_date' => "Slot conflict: you already have a {$existingTypeName} applied for the {$newHalfDaySlot} slot on {$existing->start_date}."
                        ]);
                    }
                }

                // Rule D: Both are Short Leaves
                if ($newIsShortLeave && $existingIsShortLeave) {
                    if ($newShortLeaveSlot && $existingShortLeaveSlot && $newShortLeaveSlot === $existingShortLeaveSlot) {
                        throw ValidationException::withMessages([
                            'start_date' => "Time conflict: you already have a Short Leave booked for {$newShortLeaveSlot} on {$existing->start_date}."
                        ]);
                    }
                }
            }
        }

        // 1. Intern & Probation Restriction Rule
        if ($this->isProbationOrIntern($employee)) {
            if (in_array($typeCode, ['ANNUAL', 'CASUAL', 'MEDICAL'])) {
                throw ValidationException::withMessages([
                    'leave_type_id' => 'Interns and probationary employees are not eligible for standard Annual, Casual, or Medical leave until probation/internship ends. You earn 0.5 days per month worked.'
                ]);
            }
        }

        // 2. Casual Leave Rule: Max 3 days in a row
        if ($typeCode === 'CASUAL' && $workingDays > 3) {
            throw ValidationException::withMessages([
                'end_date' => 'Casual Leave can only be taken up to a maximum of 3 consecutive days per application.'
            ]);
        }

        // 3. Medical / Sick Leave Rule: > 3 days requires Medical Certificate
        if ($typeCode === 'MEDICAL' && $workingDays > 3) {
            $hasCertificate = !empty($data['medical_certificate_file']) || !empty($data['medical_certificate_path']);
            if (!$hasCertificate) {
                throw ValidationException::withMessages([
                    'medical_certificate_file' => 'A Medical Certificate must be provided when applying for Medical Leave exceeding 3 consecutive days.'
                ]);
            }
        }

        // 4. Short Leave Rule: Max two Short Leaves per month
        if ($typeCode === 'SHORT' || !empty($data['is_short_leave'])) {
            $startCarbon = Carbon::parse($data['start_date']);
            $shortCount = LeaveRequest::where('employee_id', $employee->id)
                ->where(function ($q) {
                    $q->where('is_short_leave', true)
                      ->orWhereHas('leaveType', fn($t) => $t->where('code', 'SHORT'));
                })
                ->whereMonth('start_date', $startCarbon->month)
                ->whereYear('start_date', $startCarbon->year)
                ->whereNotIn('status', ['Rejected', 'Canceled'])
                ->count();

            if ($shortCount >= 2) {
                throw ValidationException::withMessages([
                    'leave_type_id' => 'You can only take up to two Short Leaves per calendar month. You have already reached the limit for this month.'
                ]);
            }
        }

        // 5. Duty Leave Rule: Requires project/client name
        if ($typeCode === 'DUTY') {
            if (empty($data['project_client_name'])) {
                throw ValidationException::withMessages([
                    'project_client_name' => 'Please enter the Project or Client name for Duty Leave offsite work.'
                ]);
            }
        }
    }

    /**
     * Instantly refund deducted leave days when a leave request is rejected
     */
    public function refundLeaveBalance(LeaveRequest $request): void
    {
        if ($request->is_refunded) {
            return;
        }

        $typeCode = strtoupper($request->leaveType?->code ?? '');
        if (!in_array($typeCode, ['SHORT', 'DUTY', 'NIGHT_SHIFT_RECOVERY']) && !$request->is_short_leave) {
            $balance = EmployeeLeaveBalance::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->first();

            if ($balance) {
                $newUsed = max(0, $balance->used - $request->duration);
                $balance->update(['used' => $newUsed]);
            }
        }

        $request->update(['is_refunded' => true]);
    }
}
