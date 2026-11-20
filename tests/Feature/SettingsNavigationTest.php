<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Employee\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $adminEmployee = Employee::where('system_role', 'Super (Admin)')->first() ?? Employee::first();
        $this->adminUser = User::find($adminEmployee->user_id) ?? User::factory()->create();

        $staffEmployee = Employee::where('system_role', 'Employee')->first();
        $this->staffUser = $staffEmployee ? User::find($staffEmployee->user_id) : User::factory()->create();
    }

    public function test_settings_module_renders_in_sidebar_and_expands_separate_pages()
    {
        $this->actingAs($this->adminUser);
        session(['current_role' => 'Super (Admin)']);

        // 1. Visit general settings
        $response = $this->followingRedirects()->get(route('settings.index', ['module' => 'general']));
        $response->assertStatus(200);
        $response->assertSee('General Organization');
        $response->assertSee('Leave Management');
        $response->assertSee('Departments & Structure', false);
        $response->assertSee('Roles & Access Control', false);
        $response->assertSee('Attendance & Shifts', false);
        $response->assertSee('Payroll & Tax', false);
        $response->assertSee('Recruitment & ATS', false);
        $response->assertSee('Performance & Appraisal', false);
        $response->assertSee('System Engine');
        $response->assertSee('My Account & Security', false);

        // 2. Visit leave settings directly
        $leaveResponse = $this->get(route('settings.index', ['module' => 'leave']));
        $leaveResponse->assertStatus(200);
        $leaveResponse->assertSee('Leave Management');

        // 3. Visit payroll settings directly
        $payrollResponse = $this->get(route('settings.index', ['module' => 'payroll']));
        $payrollResponse->assertStatus(200);
        $payrollResponse->assertSee('Payroll & Tax', false);
    }

    public function test_employee_can_access_profile_settings_only()
    {
        $this->actingAs($this->staffUser);
        session(['current_role' => 'Employee']);

        $response = $this->get(route('settings.index'));
        $response->assertStatus(200);
        $response->assertSee('My Account & Security');
        $response->assertDontSee('System Engine');
    }
}
