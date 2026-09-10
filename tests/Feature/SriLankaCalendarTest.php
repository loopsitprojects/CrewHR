<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Leave\Models\Holiday;
use Modules\Leave\Models\LeaveType;
use Modules\Employee\Models\Employee;
use App\Models\User;

class SriLankaCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_application_timezone_is_configured_for_sri_lanka()
    {
        $this->assertEquals('Asia/Colombo', config('app.timezone'));
    }

    public function test_sri_lanka_public_holidays_are_seeded()
    {
        $this->assertDatabaseHas('holidays', [
            'title' => 'Nikini Full Moon Poya Day',
            'type' => 'Poya'
        ]);

        $this->assertDatabaseHas('holidays', [
            'title' => 'Sinhala & Tamil New Year Day',
            'type' => 'Mercantile'
        ]);

        $this->assertDatabaseHas('holidays', [
            'title' => 'Vesak Full Moon Poya Day',
            'type' => 'Poya'
        ]);
    }

    public function test_leave_dashboard_renders_sri_lanka_calendar()
    {
        $response = $this->get(route('dashboard', ['year' => 2026, 'month' => 8]));

        $response->assertStatus(200);
        $response->assertSee('August 2026');
        $response->assertSee('Holiday Calendar');
    }

    public function test_leave_duration_calculation_excludes_weekends_and_poya_days()
    {
        $leaveType = LeaveType::first();

        // 2026-08-24 (Mon) to 2026-08-28 (Fri). Note 2026-08-26 (Milad-Un-Nabi) & 2026-08-27 (Nikini Poya) are Holidays.
        // Total 5 calendar days - 2 Holidays = 3 working days!
        $payload = [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-24',
            'end_date' => '2026-08-28',
            'reason' => 'Family vacation'
        ];

        $response = $this->post(route('leave.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'start_date' => '2026-08-24',
            'end_date' => '2026-08-28',
            'duration' => 3
        ]);
    }

    public function test_manual_holiday_drag_and_drop_assignment()
    {
        $adminUser = User::first();
        session(['current_role' => 'Super (Admin)']);

        // 1. Assign Mercantile Holiday
        $response = $this->actingAs($adminUser)->postJson(route('settings.holidays.assign'), [
            'date' => '2026-09-15',
            'day_type' => 'Public Holiday',
            'title' => 'Mercantile Public Holiday',
            'is_mercantile' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('holidays', [
            'date' => '2026-09-15',
            'is_mercantile' => 1,
            'category' => 'Public, Bank & Mercantile'
        ]);

        // 2. Assign Non-Mercantile Holiday
        $responseNonMerc = $this->actingAs($adminUser)->postJson(route('settings.holidays.assign'), [
            'date' => '2026-09-16',
            'day_type' => 'Public Holiday',
            'title' => 'Non-Mercantile Bank Day',
            'is_mercantile' => false,
        ]);

        $responseNonMerc->assertStatus(200);
        $responseNonMerc->assertJson(['success' => true]);
        $this->assertDatabaseHas('holidays', [
            'date' => '2026-09-16',
            'is_mercantile' => 0,
            'category' => 'Public & Bank Only (Non-Mercantile)'
        ]);

        // 3. Fetch JSON
        $jsonResponse = $this->actingAs($adminUser)->getJson(route('settings.holidays.json', ['year' => 2026, 'month' => 9]));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonFragment(['date' => '2026-09-15']);
        $jsonResponse->assertJsonFragment(['date' => '2026-09-16']);

        // 4. Reset to Working Day
        $resetResponse = $this->actingAs($adminUser)->postJson(route('settings.holidays.assign'), [
            'date' => '2026-09-15',
            'day_type' => 'Working Day'
        ]);
        $resetResponse->assertStatus(200);
        $this->assertDatabaseMissing('holidays', [
            'date' => '2026-09-15'
        ]);
    }
}
