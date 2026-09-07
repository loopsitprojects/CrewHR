<?php

namespace Modules\Employee\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic department, designation, and leave types
        $this->department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'description' => 'Engineering Dept'
        ]);

        $this->designation = Designation::create([
            'department_id' => $this->department->id,
            'name' => 'Software Engineer'
        ]);

        $this->leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'code' => 'ANNUAL',
            'days' => 14
        ]);
    }

    public function test_can_view_employees_index_page()
    {
        $response = $this->get(route('employee.index'));

        $response->assertStatus(200);
        $response->assertSee('Employees Directory');
    }

    public function test_can_create_an_employee_profile()
    {
        $payload = [
            'title' => 'Mr.',
            'full_name' => 'Test Developer',
            'email' => 'test.dev@loopshr.lk',
            'employee_id_number' => 'EMP-TEST-001',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'system_role' => 'Employee',
            'joined_date' => '2026-01-01',
            'basic_salary' => 150000,
            'fixed_allowance' => 20000,
            'other_allowance' => 10000,
            'apit_tax' => 5000,
            'bank_name' => 'Commercial Bank',
            'account_number' => '1234567890',
        ];

        $response = $this->post(route('employee.store'), $payload);

        $response->assertRedirect(route('employee.index'));
        $this->assertDatabaseHas('users', ['email' => 'test.dev@loopshr.lk']);
        $this->assertDatabaseHas('employees', ['employee_id_number' => 'EMP-TEST-001']);

        // Check if leave balance was automatically seeded
        $emp = Employee::where('employee_id_number', 'EMP-TEST-001')->first();
        $this->assertNotNull($emp);
        $this->assertDatabaseHas('employee_leave_balances', [
            'employee_id' => $emp->id,
            'leave_type_id' => $this->leaveType->id,
            'allocated' => 14
        ]);
    }

    public function test_can_view_employee_details_page()
    {
        $user = User::create([
            'name' => 'Jane Developer',
            'email' => 'jane@loopshr.lk',
            'password' => bcrypt('password')
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_id_number' => 'EMP-TEST-002',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'basic_salary' => 180000
        ]);

        $response = $this->get(route('employee.show', $employee->id));

        $response->assertStatus(200);
        $response->assertSee('Jane Developer');
        $response->assertSee('EMP-TEST-002');
    }

    public function test_can_update_employee_profile_and_leave_balances()
    {
        $user = User::create([
            'name' => 'Update Me',
            'email' => 'update.me@loopshr.lk',
            'password' => bcrypt('password')
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_id_number' => 'EMP-TEST-003',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'basic_salary' => 100000
        ]);

        $balance = EmployeeLeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType->id,
            'allocated' => 14,
            'used' => 2
        ]);

        $updatePayload = [
            'title' => 'Ms.',
            'full_name' => 'Updated Name',
            'username' => 'updateme',
            'email' => 'update.me@loopshr.lk',
            'employee_id_number' => 'EMP-TEST-003',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'basic_salary' => 200000,
            'leave_balances' => [
                $balance->id => [
                    'allocated' => 16,
                    'used' => 4
                ]
            ]
        ];

        $response = $this->put(route('employee.update', $employee->id), $updatePayload);

        $response->assertRedirect(route('employee.index'));
        $this->assertDatabaseHas('users', ['name' => 'Updated Name']);
        $this->assertDatabaseHas('employees', ['basic_salary' => 200000]);
        $this->assertDatabaseHas('employee_leave_balances', [
            'id' => $balance->id,
            'allocated' => 16,
            'used' => 4
        ]);
    }

    public function test_can_delete_employee_and_user()
    {
        $user = User::create([
            'name' => 'Delete Me',
            'email' => 'delete.me@loopshr.lk',
            'password' => bcrypt('password')
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_id_number' => 'EMP-TEST-004',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
        ]);

        $response = $this->delete(route('employee.destroy', $employee->id));

        $response->assertRedirect(route('employee.index'));
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_regular_employee_can_view_directory_and_profiles()
    {
        $user = User::create([
            'name' => 'Regular Staff',
            'email' => 'staff@loopshr.lk',
            'password' => bcrypt('password')
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_id_number' => 'EMP-TEST-005',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'system_role' => 'Employee',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('employee.index'));
        $response->assertStatus(200);
        $response->assertSee('Employees Directory');
        $response->assertSee('Regular Staff');

        $showResponse = $this->get(route('employee.show', $employee->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Regular Staff');
    }

    public function test_can_create_employee_with_custom_manual_designation()
    {
        $payload = [
            'title' => 'Mr.',
            'full_name' => 'Custom Desig Staff',
            'email' => 'custom.desig@loopshr.lk',
            'employee_id_number' => 'EMP-TEST-CUSTOM-01',
            'department_id' => $this->department->id,
            'designation_id' => 'custom',
            'custom_designation' => 'Lead Data Scientist',
            'system_role' => 'Employee',
            'joined_date' => '2026-01-01',
        ];

        $response = $this->post(route('employee.store'), $payload);

        $response->assertRedirect(route('employee.index'));
        $this->assertDatabaseHas('designations', ['name' => 'Lead Data Scientist']);
        $this->assertDatabaseHas('employees', ['employee_id_number' => 'EMP-TEST-CUSTOM-01']);
    }
}
