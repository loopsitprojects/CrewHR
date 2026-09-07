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

    public function test_sri_lanka_gazette_holidays_are_seeded()
    {
        $this->assertDatabaseHas('holidays', [
            'title' => 'Nikini Full Moon Poya Day',
            'type' => 'Gazette'
        ]);

        $this->assertDatabaseHas('holidays', [
            'title' => 'Sinhala & Tamil New Year Day',
            'type' => 'Gazette'
        ]);

        $this->assertDatabaseHas('holidays', [
            'title' => 'Vesak Full Moon Poya Day',
            'type' => 'Gazette'
        ]);
    }

    public function test_leave_dashboard_renders_sri_lanka_calendar()
    {
        $response = $this->get(route('dashboard', ['year' => 2026, 'month' => 8]));

        $response->assertStatus(200);
        $response->assertSee('August 2026');
        $response->assertSee('Gazette Holidays');
    }

    public function test_leave_duration_calculation_excludes_weekends_and_poya_days()
    {
        $leaveType = LeaveType::first();

        // 2026-08-24 (Mon) to 2026-08-28 (Fri). Note 2026-08-26 (Milad-Un-Nabi) & 2026-08-27 (Nikini Poya) are Gazette Holidays.
        // Total 5 calendar days - 2 Gazette Holidays = 3 working days!
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
}
