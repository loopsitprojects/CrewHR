<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Employee\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModuleBackButtonTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->employee = Employee::where('system_role', 'Super (Admin)')->first() ?? Employee::first();
        $this->adminUser = User::find($this->employee->user_id) ?? User::factory()->create();
    }

    public function test_root_page_does_not_render_module_back_button()
    {
        $this->actingAs($this->adminUser);
        session(['current_role' => 'Super (Admin)']);

        // Employee directory root
        $response = $this->get(route('employee.index'));
        $response->assertStatus(200);
        $response->assertDontSee('handleModuleBack(event,', false);

        // Leave dashboard root
        $dashResponse = $this->get(route('dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertDontSee('handleModuleBack(event,', false);
    }

    public function test_inner_page_in_same_module_renders_back_button()
    {
        $this->actingAs($this->adminUser);
        session(['current_role' => 'Super (Admin)']);

        // Employee profile view (inside employee module)
        $showResponse = $this->get(route('employee.show', $this->employee->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('handleModuleBack(event,', false);
        $showResponse->assertSee('title="Back to previous page"', false);

        // Employee edit view (inside employee module)
        $editResponse = $this->get(route('employee.edit', $this->employee->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('handleModuleBack(event,', false);
        $editResponse->assertSee('title="Back to previous page"', false);

        // Settings subpage (inside settings module)
        $settingsResponse = $this->get(route('settings.index', ['module' => 'leave']));
        $settingsResponse->assertStatus(200);
        $settingsResponse->assertSee('handleModuleBack(event,', false);

        // My usage records (inside leave module)
        $usageResponse = $this->get(route('my-usage-records.index'));
        $usageResponse->assertStatus(200);
        $usageResponse->assertSee('handleModuleBack(event,', false);
    }
}
