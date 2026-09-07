<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Employee\Models\Employee;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Services\LeavePolicyService;
use App\Models\User;
use Carbon\Carbon;

class LeaveBriefPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected LeavePolicyService $policyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->policyService = app(LeavePolicyService::class);
    }

    private function createTestEmployee(string $joinedDate, string $jobCategory = 'Permanent'): Employee
    {
        $user = User::create([
            'name' => 'Test User ' . rand(100, 999),
            'email' => 'testuser' . rand(1000, 9999) . '@example.com',
            'password' => bcrypt('password'),
        ]);

        $dept = \Modules\Employee\Models\Department::first();
        $desig = \Modules\Employee\Models\Designation::first();

        return Employee::create([
            'user_id' => $user->id,
            'department_id' => $dept->id ?? 1,
            'designation_id' => $desig->id ?? 1,
            'employee_id_number' => 'EMP-' . rand(1000, 9999),
            'joined_date' => $joinedDate,
            'job_category' => $jobCategory,
            'basic_salary' => 100000,
        ]);
    }

    public function test_annual_leave_prorata_rules()
    {
        $currentYear = 2026;

        // 1st Year Employee (Joined 2026): 0 days
        $emp1stYr = $this->createTestEmployee('2026-02-15');
        $this->assertEquals(0.0, $this->policyService->calculateAnnualLeaveQuota($emp1stYr, $currentYear));

        // 2nd Year Employee (Joined Jan-Mar 2025): 14 days
        $emp2ndYrJan = $this->createTestEmployee('2025-02-10');
        $this->assertEquals(14.0, $this->policyService->calculateAnnualLeaveQuota($emp2ndYrJan, $currentYear));

        // 2nd Year Employee (Joined Apr-Jun 2025): 10 days
        $emp2ndYrApr = $this->createTestEmployee('2025-05-20');
        $this->assertEquals(10.0, $this->policyService->calculateAnnualLeaveQuota($emp2ndYrApr, $currentYear));

        // 2nd Year Employee (Joined Jul-Sep 2025): 7 days
        $emp2ndYrJul = $this->createTestEmployee('2025-08-01');
        $this->assertEquals(7.0, $this->policyService->calculateAnnualLeaveQuota($emp2ndYrJul, $currentYear));

        // 2nd Year Employee (Joined Oct-Dec 2025): 4 days
        $emp2ndYrOct = $this->createTestEmployee('2025-11-15');
        $this->assertEquals(4.0, $this->policyService->calculateAnnualLeaveQuota($emp2ndYrOct, $currentYear));

        // 3rd Year+ Employee (Joined 2024): 14 days
        $emp3rdYr = $this->createTestEmployee('2024-01-01');
        $this->assertEquals(14.0, $this->policyService->calculateAnnualLeaveQuota($emp3rdYr, $currentYear));
    }

    public function test_casual_leave_max_3_days_restriction()
    {
        $emp = Employee::first();
        $casualType = LeaveType::where('code', 'CASUAL')->first();

        // 4 working days of Casual Leave should fail validation
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->policyService->validateLeaveApplication($emp, $casualType, [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-14',
        ], 4.0);
    }

    public function test_medical_leave_over_3_days_requires_certificate()
    {
        $emp = Employee::first();
        $medicalType = LeaveType::where('code', 'MEDICAL')->first();

        // 4 working days without certificate should fail
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->policyService->validateLeaveApplication($emp, $medicalType, [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-14',
            'medical_certificate_file' => null,
            'medical_certificate_path' => null,
        ], 4.0);
    }

    public function test_probation_and_intern_leave_restrictions()
    {
        $probationEmp = $this->createTestEmployee('2026-01-01', 'Probationary Staff');
        $annualType = LeaveType::where('code', 'ANNUAL')->first();

        // Probationary staff applying for Annual leave should fail
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->policyService->validateLeaveApplication($probationEmp, $annualType, [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
        ], 2.0);
    }

    public function test_instant_balance_refund_on_rejection()
    {
        $emp = Employee::first();
        $annualType = LeaveType::where('code', 'ANNUAL')->first();

        $balance = EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $emp->id, 'leave_type_id' => $annualType->id],
            ['allocated' => 14, 'used' => 5, 'carried_forward' => 0]
        );

        $request = LeaveRequest::create([
            'employee_id' => $emp->id,
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'duration' => 2,
            'status' => 'Pending',
            'is_refunded' => false,
        ]);

        // Refund leave balance
        $this->policyService->refundLeaveBalance($request);

        $balance->refresh();
        $request->refresh();

        $this->assertEquals(3.0, (float)$balance->used); // 5 - 2 = 3
        $this->assertTrue((bool)$request->is_refunded);
    }
}
