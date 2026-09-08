<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\Holiday;
use Modules\Leave\Services\LeavePolicyService;
use App\Models\User;
use Carbon\Carbon;

class LeaveManagementFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected LeavePolicyService $policyService;
    protected User $adminUser;
    protected Employee $adminEmployee;
    protected User $managerUser;
    protected Employee $managerEmployee;
    protected User $regularUser;
    protected Employee $regularEmployee;
    protected User $coveringUser;
    protected Employee $coveringEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->policyService = app(LeavePolicyService::class);
        Storage::fake('public');

        // Setup Test Roles & Users
        $dept = Department::firstOrCreate(['name' => 'Engineering']);
        $desig = Designation::firstOrCreate(['name' => 'Senior Developer']);

        // Admin
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@loops.lk',
            'username' => 'admin_user',
            'password' => bcrypt('password'),
        ]);
        $this->adminEmployee = Employee::create([
            'user_id' => $this->adminUser->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'employee_id_number' => 'EMP-0001',
            'joined_date' => '2023-01-01',
            'job_category' => 'Permanent',
            'system_role' => 'Super (Admin)',
            'basic_salary' => 200000,
        ]);

        // Manager
        $this->managerUser = User::create([
            'name' => 'Manager User',
            'email' => 'manager@loops.lk',
            'username' => 'manager_user',
            'password' => bcrypt('password'),
        ]);
        $this->managerEmployee = Employee::create([
            'user_id' => $this->managerUser->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'employee_id_number' => 'EMP-0002',
            'joined_date' => '2024-01-01',
            'job_category' => 'Permanent',
            'system_role' => 'Manager (Team Approvals)',
            'basic_salary' => 150000,
        ]);

        // Regular Employee
        $this->regularUser = User::create([
            'name' => 'John Doe',
            'email' => 'john@loops.lk',
            'username' => 'johndoe',
            'password' => bcrypt('password'),
        ]);
        $this->regularEmployee = Employee::create([
            'user_id' => $this->regularUser->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'employee_id_number' => 'EMP-0003',
            'joined_date' => '2024-01-01',
            'job_category' => 'Permanent',
            'reporting_person_id' => $this->managerEmployee->id,
            'system_role' => 'Employee',
            'basic_salary' => 80000,
        ]);

        // Covering Employee
        $this->coveringUser = User::create([
            'name' => 'Jane Cover',
            'email' => 'jane@loops.lk',
            'username' => 'janecover',
            'password' => bcrypt('password'),
        ]);
        $this->coveringEmployee = Employee::create([
            'user_id' => $this->coveringUser->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'employee_id_number' => 'EMP-0004',
            'joined_date' => '2024-01-01',
            'job_category' => 'Permanent',
            'system_role' => 'Employee',
            'basic_salary' => 80000,
        ]);
    }

    /**
     * TEST SUITE 1: LEAVE APPLICATION & SUBMISSION
     */

    public function test_employee_can_submit_valid_annual_leave_request()
    {
        $this->actingAs($this->regularUser);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $this->regularEmployee->id, 'leave_type_id' => $annualType->id, 'year' => 2026],
            ['allocated' => 14, 'used' => 0, 'carried_forward' => 0]
        );

        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-15',
            'reason' => 'Annual family vacation',
            'covering_employee_id' => $this->coveringEmployee->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->regularEmployee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-15',
            'duration' => 2.0,
            'status' => 'Pending Manager Approval',
            'manager_status' => 'Pending',
            'covering_employee_id' => $this->coveringEmployee->id,
        ]);

        $balance = EmployeeLeaveBalance::where('employee_id', $this->regularEmployee->id)
            ->where('leave_type_id', $annualType->id)
            ->first();
        $this->assertEquals(2.0, (float)$balance->used);
    }

    public function test_leave_submission_fails_with_invalid_dates_end_before_start()
    {
        $this->actingAs($this->regularUser);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();

        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-14', // Invalid: before start
            'reason' => 'Invalid range test',
        ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_leave_submission_fails_when_balance_is_insufficient()
    {
        $this->actingAs($this->regularUser);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $this->regularEmployee->id, 'leave_type_id' => $annualType->id, 'year' => 2026],
            ['allocated' => 2, 'used' => 2, 'carried_forward' => 0] // 0 available
        );

        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-14', // 1 day requested
            'reason' => 'Should fail due to 0 balance',
        ]);

        $response->assertSessionHasErrors('leave_type_id');
    }

    public function test_half_day_leave_application_calculates_point_five_duration()
    {
        $this->actingAs($this->regularUser);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $this->regularEmployee->id, 'leave_type_id' => $annualType->id, 'year' => 2026],
            ['allocated' => 14, 'used' => 0, 'carried_forward' => 0]
        );

        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-14',
            'is_half_day' => 1,
            'half_day_slot' => 'Morning',
            'reason' => 'Doctor appointment morning',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->regularEmployee->id,
            'duration' => 0.5,
            'is_half_day' => 1,
            'half_day_slot' => 'Morning',
        ]);
    }

    public function test_short_leave_application_calculates_point_two_duration()
    {
        $this->actingAs($this->regularUser);

        $shortType = LeaveType::where('code', 'SHORT')->first();
        if (!$shortType) {
            $shortType = LeaveType::create(['name' => 'Short Leave', 'code' => 'SHORT', 'days' => 2]);
        }
        EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $this->regularEmployee->id, 'leave_type_id' => $shortType->id, 'year' => 2026],
            ['allocated' => 2, 'used' => 0, 'carried_forward' => 0]
        );

        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $shortType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-14',
            'short_leave_slot' => '15:30 - 17:00',
            'reason' => 'Personal errand break',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->regularEmployee->id,
            'duration' => 0.2,
            'is_short_leave' => 1,
        ]);

        // Assert Short Leave does not deduct from annual quota balance
        $balance = EmployeeLeaveBalance::where('employee_id', $this->regularEmployee->id)
            ->where('leave_type_id', $shortType->id)
            ->first();
        $this->assertEquals(0, (float)$balance->used);
    }

    public function test_short_leave_max_limit_enforced_per_month()
    {
        $this->actingAs($this->regularUser);

        $shortType = LeaveType::where('code', 'SHORT')->first();
        if (!$shortType) {
            $shortType = LeaveType::create(['name' => 'Short Leave', 'code' => 'SHORT', 'days' => 5]);
        }

        // Create 2 existing short leaves in September 2026
        LeaveRequest::create([
            'employee_id' => $this->regularEmployee->id,
            'leave_type_id' => $shortType->id,
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-02',
            'duration' => 0.2,
            'status' => 'Approved',
            'reason' => 'First short leave',
        ]);
        LeaveRequest::create([
            'employee_id' => $this->regularEmployee->id,
            'leave_type_id' => $shortType->id,
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-08',
            'duration' => 0.2,
            'status' => 'Pending Manager Approval',
            'reason' => 'Second short leave',
        ]);

        // 3rd short leave attempt in same month
        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $shortType->id,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-20',
            'reason' => 'Third short leave should fail',
        ]);

        $response->assertSessionHasErrors('leave_type_id');
    }

    public function test_duty_leave_requires_project_or_client_name()
    {
        $this->actingAs($this->regularUser);

        $dutyType = LeaveType::where('code', 'DUTY')->first();
        if (!$dutyType) {
            $dutyType = LeaveType::create(['name' => 'Duty Leave', 'code' => 'DUTY', 'days' => 0]);
        }

        // Without project name -> fails
        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $dutyType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-14',
            'reason' => 'Client meeting offsite',
            'project_client_name' => '',
        ]);

        $response->assertSessionHasErrors('project_client_name');

        // With project name -> succeeds
        $responseSuccess = $this->post(route('leave.store'), [
            'leave_type_id' => $dutyType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-14',
            'reason' => 'Client meeting offsite',
            'project_client_name' => 'Client XYZ Project Alpha',
        ]);

        $responseSuccess->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->regularEmployee->id,
            'project_client_name' => 'Client XYZ Project Alpha',
        ]);
    }

    public function test_medical_certificate_upload_works_and_is_stored()
    {
        $this->actingAs($this->regularUser);

        $medicalType = LeaveType::where('code', 'MEDICAL')->first();
        if (!$medicalType) {
            $medicalType = LeaveType::create(['name' => 'Medical Leave', 'code' => 'MEDICAL', 'days' => 14]);
        }
        EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $this->regularEmployee->id, 'leave_type_id' => $medicalType->id, 'year' => 2026],
            ['allocated' => 14, 'used' => 0, 'carried_forward' => 0]
        );

        $file = UploadedFile::fake()->create('medical_cert.pdf', 500, 'application/pdf');

        // Medical leave > 3 days (e.g. 4 working days: Mon Sep 14 to Thu Sep 17)
        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $medicalType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-17',
            'reason' => 'Severe fever hospitalization',
            'medical_certificate_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->regularEmployee->id,
            'leave_type_id' => $medicalType->id,
            'duration' => 4.0,
        ]);

        $createdReq = LeaveRequest::where('employee_id', $this->regularEmployee->id)->latest()->first();
        $this->assertNotNull($createdReq->medical_certificate_path);
    }

    /**
     * TEST SUITE 2: APPROVAL & REJECTION WORKFLOW
     */

    public function test_manager_can_approve_subordinate_leave_request()
    {
        $this->actingAs($this->managerUser);
        session(['current_role' => 'Manager (Team Approvals)']);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        $req = LeaveRequest::create([
            'req_number' => 'REQ-APP-001',
            'employee_id' => $this->regularEmployee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-22',
            'duration' => 2.0,
            'status' => 'Pending Manager Approval',
            'manager_status' => 'Pending',
            'manager_employee_id' => $this->managerEmployee->id,
            'is_refunded' => false,
        ]);

        $response = $this->post(route('approvals.action', $req->id), [
            'action_type' => 'manager_approve',
        ]);

        $response->assertRedirect();
        $req->refresh();
        $this->assertEquals('Approved', $req->status);
        $this->assertEquals('Approved', $req->manager_status);
        $this->assertEquals('Approved', $req->hr_status);
    }

    public function test_manager_can_reject_leave_request_and_balance_is_refunded()
    {
        $this->actingAs($this->managerUser);
        session(['current_role' => 'Manager (Team Approvals)']);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        $balance = EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $this->regularEmployee->id, 'leave_type_id' => $annualType->id, 'year' => 2026],
            ['allocated' => 14, 'used' => 2, 'carried_forward' => 0]
        );

        $req = LeaveRequest::create([
            'req_number' => 'REQ-REJ-001',
            'employee_id' => $this->regularEmployee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-22',
            'duration' => 2.0,
            'status' => 'Pending Manager Approval',
            'manager_status' => 'Pending',
            'manager_employee_id' => $this->managerEmployee->id,
            'is_refunded' => false,
        ]);

        $response = $this->post(route('approvals.action', $req->id), [
            'action_type' => 'reject_manager',
        ]);

        $response->assertRedirect();
        $req->refresh();
        $balance->refresh();

        $this->assertEquals('Rejected', $req->status);
        $this->assertEquals('Rejected', $req->manager_status);
        $this->assertTrue((bool)$req->is_refunded);
        $this->assertEquals(0.0, (float)$balance->used); // 2 - 2 = 0 refunded!
    }

    public function test_manager_cannot_approve_own_leave_request_self_approval_restriction()
    {
        $this->actingAs($this->managerUser);
        session(['current_role' => 'Manager (Team Approvals)']);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        $req = LeaveRequest::create([
            'req_number' => 'REQ-OWN-001',
            'employee_id' => $this->managerEmployee->id, // Own leave
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-22',
            'duration' => 2.0,
            'status' => 'Pending Manager Approval',
            'manager_status' => 'Pending',
            'manager_employee_id' => $this->managerEmployee->id,
            'is_refunded' => false,
        ]);

        $response = $this->post(route('approvals.action', $req->id), [
            'action_type' => 'manager_approve',
        ]);

        $response->assertSessionHas('error');
        $req->refresh();
        $this->assertEquals('Pending Manager Approval', $req->status); // Unchanged!
    }

    public function test_regular_employee_cannot_access_approvals_screen()
    {
        $this->actingAs($this->regularUser);
        session(['current_role' => 'Employee']);

        $response = $this->get(route('approvals.index'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * TEST SUITE 3: EMPLOYEE CANCELLATION & BALANCE RESTORATION
     */

    public function test_employee_cannot_cancel_already_approved_leave()
    {
        $this->actingAs($this->regularUser);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        $req = LeaveRequest::create([
            'req_number' => 'REQ-ALREADY-APP',
            'employee_id' => $this->regularEmployee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-22',
            'duration' => 2.0,
            'status' => 'Approved',
            'manager_status' => 'Approved',
            'is_refunded' => false,
        ]);

        $response = $this->post(route('my-leaves.cancel', $req->id));

        $response->assertSessionHasErrors('error');
        $req->refresh();
        $this->assertEquals('Approved', $req->status); // Remains approved
    }

    /**
     * TEST SUITE 4: COMPANY HOLIDAY CREATION PERMISSIONS
     */

    public function test_hr_admin_can_add_company_holiday()
    {
        $this->actingAs($this->adminUser);
        session(['current_role' => 'Super (Admin)']);

        $response = $this->post(route('leave.company_holiday'), [
            'title' => 'Company Annual Hackathon',
            'date' => '2026-10-15',
            'category' => 'Mercantile',
            'description' => 'Special internal hackathon holiday',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('holidays', [
            'title' => 'Company Annual Hackathon',
            'date' => '2026-10-15',
            'type' => 'Company',
        ]);
    }

    public function test_regular_employee_cannot_add_company_holiday()
    {
        $this->actingAs($this->regularUser);
        session(['current_role' => 'Employee']);

        $response = $this->post(route('leave.company_holiday'), [
            'title' => 'Unauthorized Holiday',
            'date' => '2026-10-16',
            'category' => 'Public',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('holidays', [
            'title' => 'Unauthorized Holiday',
        ]);
    }

    /**
     * TEST SUITE 5: EXCLUDING WEEKENDS AND PUBLIC/COMPANY HOLIDAYS
     */

    public function test_leave_duration_excludes_weekends_and_public_holidays_in_range()
    {
        $this->actingAs($this->regularUser);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $this->regularEmployee->id, 'leave_type_id' => $annualType->id, 'year' => 2026],
            ['allocated' => 14, 'used' => 0, 'carried_forward' => 0]
        );

        // August 2026:
        // 2026-08-21 (Friday) -> 1 working day
        // 2026-08-22 (Saturday) -> Weekend (excluded)
        // 2026-08-23 (Sunday) -> Weekend (excluded)
        // 2026-08-24 (Monday) -> 1 working day
        // 2026-08-25 (Tuesday) -> 1 working day
        // 2026-08-26 (Wednesday) -> Milad-Un-Nabi Gazette Holiday (excluded)
        // 2026-08-27 (Thursday) -> Nikini Poya Gazette Holiday (excluded)
        // 2026-08-28 (Friday) -> 1 working day
        // Total span: 8 calendar days. Net working days = 4 working days!

        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-08-21',
            'end_date' => '2026-08-28',
            'reason' => 'Extended vacation across weekends and public holidays',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->regularEmployee->id,
            'start_date' => '2026-08-21',
            'end_date' => '2026-08-28',
            'duration' => 4.0, // Exactly 4 days deducted (weekends and holidays excluded)!
        ]);

        $balance = EmployeeLeaveBalance::where('employee_id', $this->regularEmployee->id)
            ->where('leave_type_id', $annualType->id)
            ->first();
        $this->assertEquals(4.0, (float)$balance->used);
    }

    public function test_leave_request_rejected_if_all_days_in_range_are_weekends_or_holidays()
    {
        $this->actingAs($this->regularUser);

        $annualType = LeaveType::where('code', 'ANNUAL')->first();

        // 2026-08-22 (Sat) to 2026-08-23 (Sun) -> 0 working days
        $response = $this->post(route('leave.store'), [
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-08-22',
            'end_date' => '2026-08-23',
            'reason' => 'Weekend only leave attempt',
        ]);

        $response->assertSessionHasErrors('start_date');
    }
}

