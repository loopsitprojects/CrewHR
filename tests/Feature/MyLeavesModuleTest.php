<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use App\Models\User;

class MyLeavesModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_my_leaves_page_renders_successfully()
    {
        $response = $this->get(route('my-leaves.index'));

        $response->assertStatus(200);
        $response->assertSee('Leave Record Directory');
    }

    public function test_my_leaves_filters_requests_by_status()
    {
        $response = $this->get(route('my-leaves.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('Pending');
    }

    public function test_employee_can_cancel_pending_leave_request_and_refund_balance()
    {
        $emp = Employee::first();
        $annualType = LeaveType::where('code', 'ANNUAL')->first();

        $balance = EmployeeLeaveBalance::updateOrCreate(
            ['employee_id' => $emp->id, 'leave_type_id' => $annualType->id],
            ['allocated' => 14, 'used' => 4, 'carried_forward' => 0]
        );

        $req = LeaveRequest::create([
            'req_number' => 'REQ-TEST-888',
            'employee_id' => $emp->id,
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'duration' => 2.0,
            'status' => 'Pending',
            'is_refunded' => false,
        ]);

        $response = $this->post(route('my-leaves.cancel', $req->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $req->id,
            'status' => 'Canceled',
            'is_refunded' => true,
        ]);

        $balance->refresh();
        $this->assertEquals(2.0, (float)$balance->used); // 4 - 2 = 2 days used!
    }
}
