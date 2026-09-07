<?php

namespace Modules\Leave\Services;

use Modules\Employee\Models\Employee;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class LeavePolicyService
{
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
        if ($typeCode === 'SHORT') {
            $startCarbon = Carbon::parse($data['start_date']);
            $shortCount = LeaveRequest::where('employee_id', $employee->id)
                ->whereHas('leaveType', function ($q) {
                    $q->where('code', 'SHORT');
                })
                ->whereMonth('start_date', $startCarbon->month)
                ->whereYear('start_date', $startCarbon->year)
                ->whereNotIn('status', ['Rejected', 'Canceled'])
                ->count();

            if ($shortCount >= 2) {
                throw ValidationException::withMessages([
                    'leave_type_id' => 'You can only take up to two Short Leaves per month.'
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

        $balance = EmployeeLeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->first();

        if ($balance) {
            $newUsed = max(0, $balance->used - $request->duration);
            $balance->update(['used' => $newUsed]);
        }

        $request->update(['is_refunded' => true]);
    }
}
