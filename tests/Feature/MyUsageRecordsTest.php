<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Employee\Models\Employee;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MyUsageRecordsTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $adminEmployee;
    protected $hrUser;
    protected $hrEmployee;
    protected $managerUser;
    protected $managerEmployee;
    protected $staffUser;
    protected $staffEmployee;
    protected $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminEmployee = Employee::where('system_role', 'Super (Admin)')->first() ?? Employee::first();
        $this->adminUser = User::find($this->adminEmployee->user_id) ?? User::factory()->create();

        $this->hrEmployee = Employee::where('system_role', 'HR Lead')->first();
        $this->hrUser = $this->hrEmployee ? User::find($this->hrEmployee->user_id) : null;

        $this->managerEmployee = Employee::where('system_role', 'Manager (Team Approvals)')->first();
        $this->managerUser = $this->managerEmployee ? User::find($this->managerEmployee->user_id) : null;

        $this->staffEmployee = Employee::where('system_role', 'Employee')->first();
        $this->staffUser = $this->staffEmployee ? User::find($this->staffEmployee->user_id) : null;

        $this->leaveType = LeaveType::where('code', 'ANNUAL')->first() ?? LeaveType::first();
    }

    public function test_admin_and_hr_can_see_all_employee_usage_and_her_own_usage()
    {
        // 1. As HR Lead: Default is My Usage
        if ($this->hrUser) {
            $this->actingAs($this->hrUser);
            session(['current_role' => 'HR Lead']);

            $response = $this->get(route('my-usage-records.index'));
            $response->assertStatus(200);
            $response->assertSee('My Usage Records');
            $response->assertSee('My Usage');
            $response->assertDontSee('Her Usage');

            // 2. HR can view All Employees Usage via scope
            $responseAll = $this->get(route('my-usage-records.index', ['scope' => 'all']));
            $responseAll->assertStatus(200);
            $responseAll->assertSee('All Employee Usage Records');
            $responseAll->assertSee('All Employees Usage');
        }
    }

    public function test_manager_can_see_team_usage_and_their_own_usage()
    {
        if ($this->managerUser) {
            $this->actingAs($this->managerUser);
            session(['current_role' => 'Manager (Team Approvals)']);

            // 1. Manager defaults to their own usage
            $responseOwn = $this->get(route('my-usage-records.index'));
            $responseOwn->assertStatus(200);
            $responseOwn->assertSee('My Usage Records');
            $responseOwn->assertSee('Leave Balance');

            // 2. Manager sees team usage via scope
            $responseTeam = $this->get(route('my-usage-records.index', ['scope' => 'team']));
            $responseTeam->assertStatus(200);
            $responseTeam->assertSee('My Team Usage Records');
            $responseTeam->assertSee('My Team Usage');
            $responseTeam->assertSee('My Usage');
        }
    }

    public function test_employees_can_only_see_their_usage()
    {
        if ($this->staffUser) {
            $this->actingAs($this->staffUser);
            session(['current_role' => 'Employee']);

            // 1. Regular employee sees their usage
            $response = $this->get(route('my-usage-records.index'));
            $response->assertStatus(200);
            $response->assertSee('My Usage Records');
            $response->assertDontSee('All Employees Usage');
            $response->assertDontSee('My Team Usage');
            $response->assertDontSee('Inspect Employee Usage');

            // 2. Even if attempting to pass scope=all, restricted to their own usage
            $responseHacked = $this->get(route('my-usage-records.index', ['scope' => 'all']));
            $responseHacked->assertStatus(200);
            $responseHacked->assertSee('My Usage Records');
            $responseHacked->assertDontSee('All Employee Usage Records');
        }
    }

    public function test_my_usage_records_filters_by_status_and_year()
    {
        $this->actingAs($this->adminUser);
        session(['current_role' => 'Super (Admin)']);

        $now = Carbon::now('Asia/Colombo');
        LeaveRequest::create([
            'employee_id' => $this->adminEmployee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $now->format('Y-m-d'),
            'end_date' => $now->copy()->addDays(2)->format('Y-m-d'),
            'duration' => 3,
            'reason' => 'Annual family vacation and rest',
            'status' => 'Approved',
            'manager_status' => 'Approved',
            'req_number' => 'REQ-TEST-001',
        ]);

        $response = $this->get(route('my-usage-records.index', ['scope' => 'my', 'year' => $now->year, 'month' => $now->month, 'status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('Annual family vacation and rest');
        $response->assertSee('REQ-TEST-001');
    }

    public function test_leave_dashboard_modal_contains_link_to_usage_records()
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('my-usage-records.index'));
    }

    public function test_employee_tiles_and_selection_in_multi_employee_scope()
    {
        $this->actingAs($this->adminUser);
        session(['current_role' => 'Super (Admin)']);

        // 1. Visit overview: tiles should be displayed
        $response = $this->get(route('my-usage-records.index', ['scope' => 'all']));
        $response->assertStatus(200);
        $response->assertSee('Employee Leave Balances');
        $response->assertSee('Staff');

        // 2. Select a specific employee tile
        $target = $this->staffEmployee ?? Employee::where('id', '!=', $this->adminEmployee->id)->first();
        if ($target) {
            $selectResponse = $this->get(route('my-usage-records.index', ['scope' => 'all', 'employee_id' => $target->id]));
            $selectResponse->assertStatus(200);
            $selectResponse->assertSee('Active Selected');
            $selectResponse->assertSee('Deselect Employee');
            $selectResponse->assertSee('Leave Balance');
            $selectResponse->assertSee('Leave Usage History');
        }
    }
}
