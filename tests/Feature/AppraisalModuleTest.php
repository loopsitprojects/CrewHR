<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Performance\Models\AppraisalCycle;
use Modules\Performance\Models\AppraisalTemplate;
use Modules\Performance\Models\AppraisalTemplateCriterion;
use Modules\Performance\Models\Appraisal;
use Modules\Performance\Models\AppraisalCriteriaScore;
use Modules\Performance\Models\AppraisalLetter;
use Modules\Performance\Models\AppraisalChangeRequest;

class AppraisalModuleTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected User $adminUser;
    protected Employee $adminEmployee;
    protected User $staffUser;
    protected Employee $staffEmployee;
    protected User $hodUser;
    protected Employee $hodEmployee;
    protected AppraisalCycle $cycle;
    protected AppraisalTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::firstOrCreate(['name' => 'Engineering']);
        $desigStaff = Designation::firstOrCreate(['name' => 'Software Engineer']);
        $desigHod = Designation::firstOrCreate(['name' => 'Head of Engineering']);

        // Admin User & Employee
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_test@loops.lk'],
            ['name' => 'HR Admin Tester', 'password' => bcrypt('password')]
        );
        $this->adminEmployee = Employee::firstOrCreate(
            ['user_id' => $this->adminUser->id],
            [
                'employee_id_number' => 'ADM-TEST-01',
                'department_id' => $dept->id,
                'designation_id' => $desigHod->id,
                'system_role' => 'Super (Admin)',
            ]
        );

        // HOD User & Employee
        $this->hodUser = User::firstOrCreate(
            ['email' => 'hod_test@loops.lk'],
            ['name' => 'HOD Tester', 'password' => bcrypt('password')]
        );
        $this->hodEmployee = Employee::firstOrCreate(
            ['user_id' => $this->hodUser->id],
            [
                'employee_id_number' => 'HOD-TEST-01',
                'department_id' => $dept->id,
                'designation_id' => $desigHod->id,
                'system_role' => 'Manager (Team Approvals)',
            ]
        );

        // Staff User & Employee
        $this->staffUser = User::firstOrCreate(
            ['email' => 'staff_test@loops.lk'],
            ['name' => 'Staff Tester', 'password' => bcrypt('password')]
        );
        $this->staffEmployee = Employee::firstOrCreate(
            ['user_id' => $this->staffUser->id],
            [
                'employee_id_number' => 'STF-TEST-01',
                'department_id' => $dept->id,
                'designation_id' => $desigStaff->id,
                'system_role' => 'Employee',
                'reporting_person_id' => null,
                'hod_id' => $this->hodEmployee->id,
            ]
        );

        // Cycle & Template
        $this->cycle = AppraisalCycle::firstOrCreate(
            ['name' => 'Test 2026 Review Cycle'],
            [
                'review_period' => 'Annual',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'Active',
            ]
        );

        $this->template = AppraisalTemplate::firstOrCreate(
            ['title' => 'Test Tech Template'],
            ['applicable_type' => 'General', 'is_active' => true]
        );

        if ($this->template->criteria()->count() === 0) {
            AppraisalTemplateCriterion::create([
                'template_id' => $this->template->id,
                'name' => 'Code Quality',
                'description' => 'Clean and tested code',
                'weightage' => 50.00,
                'sort_order' => 1,
            ]);
            AppraisalTemplateCriterion::create([
                'template_id' => $this->template->id,
                'name' => 'Team Collaboration',
                'description' => 'Cross-functional teamwork',
                'weightage' => 50.00,
                'sort_order' => 2,
            ]);
        }
    }

    public function test_appraisal_hub_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->get(route('appraisal.index'));

        $response->assertStatus(200);
        $response->assertSee('Performance Appraisals');
    }

    public function test_self_evaluation_submission_locks_form(): void
    {
        $appraisal = Appraisal::create([
            'cycle_id' => $this->cycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'pending_self_eval',
            'is_locked' => false,
        ]);

        foreach ($this->template->criteria as $crit) {
            AppraisalCriteriaScore::create([
                'appraisal_id' => $appraisal->id,
                'criteria_name' => $crit->name,
                'weightage' => $crit->weightage,
                'self_rating' => 4,
            ]);
        }

        $scoresPayload = [];
        foreach ($appraisal->criteriaScores as $cs) {
            $scoresPayload[$cs->id] = [
                'self_rating' => 4,
                'self_comments' => 'Delivered features on time.',
            ];
        }

        $response = $this->actingAs($this->staffUser)
            ->withSession(['current_role' => 'Employee'])
            ->post(route('appraisal.submit-self', $appraisal->id), [
                'scores' => $scoresPayload,
                'general_employee_comments' => 'Good performance throughout the sprint.',
            ]);

        $response->assertRedirect(route('appraisal.show', $appraisal->id));

        $appraisal->refresh();
        $this->assertTrue($appraisal->is_locked);
        $this->assertEquals('pending_hod_eval', $appraisal->status);
        $this->assertNotNull($appraisal->self_submitted_at);
    }

    public function test_hod_review_completes_appraisal_and_calculates_score(): void
    {
        $appraisal = Appraisal::create([
            'cycle_id' => $this->cycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'pending_hod_eval',
            'is_locked' => true,
        ]);

        $scores = [];
        foreach ($this->template->criteria as $crit) {
            $scores[] = AppraisalCriteriaScore::create([
                'appraisal_id' => $appraisal->id,
                'criteria_name' => $crit->name,
                'weightage' => $crit->weightage,
                'self_rating' => 4,
                'manager_rating' => 5, // 50% * 5 = 2.5 each -> total 5.00
            ]);
        }

        $scoresPayload = [];
        foreach ($scores as $cs) {
            $scoresPayload[$cs->id] = [
                'manager_rating' => 5,
                'manager_comments' => 'Exemplary work.',
            ];
        }

        $response = $this->actingAs($this->hodUser)
            ->withSession(['current_role' => 'Manager'])
            ->post(route('appraisal.submit-hod', $appraisal->id), [
                'scores' => $scoresPayload,
                'general_hod_comments' => 'Exceptional technical contributor.',
            ]);

        $response->assertRedirect(route('appraisal.show', $appraisal->id));

        $appraisal->refresh();
        $this->assertEquals('completed', $appraisal->status);
        $this->assertEquals(5.00, $appraisal->total_weighted_score);
        $this->assertEquals('Exceptional', $appraisal->performance_band);
    }

    public function test_change_request_and_hr_unlock(): void
    {
        $appraisal = Appraisal::create([
            'cycle_id' => $this->cycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'completed',
            'is_locked' => true,
        ]);

        // Submit change request
        $response = $this->actingAs($this->staffUser)
            ->withSession(['current_role' => 'Employee'])
            ->post(route('appraisal.request-change', $appraisal->id), [
                'reason' => 'Need to update self evaluation comments for project delivery metrics.',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('appraisal_change_requests', [
            'appraisal_id' => $appraisal->id,
            'status' => 'Pending',
        ]);

        // HR Unlocks the appraisal
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.unlock', $appraisal->id), [
                'target_stage' => 'pending_self_eval',
                'notes' => 'Approved for metric updates.',
            ]);

        $response->assertSessionHas('success');

        $appraisal->refresh();
        $this->assertFalse($appraisal->is_locked);
        $this->assertEquals('pending_self_eval', $appraisal->status);
    }

    public function test_letter_generation_e_signature_and_pdf_download(): void
    {
        $appraisal = Appraisal::create([
            'cycle_id' => $this->cycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'completed',
            'is_locked' => true,
            'total_weighted_score' => 4.50,
            'performance_band' => 'Exceptional',
            'increment_percentage' => 15.00,
        ]);

        // 1. Generate Letter
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.generate-letter', $appraisal->id), [
                'letter_type' => 'increment',
                'signatory_name' => 'Anoj / Shehan (Executive Directors)',
            ]);

        $response->assertSessionHas('success');
        $letter = AppraisalLetter::where('appraisal_id', $appraisal->id)->latest()->first();
        $this->assertNotNull($letter);
        $this->assertEquals('pending_signatory', $letter->status);

        // 2. Sign by Management
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.letters.sign', $letter->id), [
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ]);

        $response->assertSessionHas('success');
        $letter->refresh();
        $this->assertEquals('signed_by_management', $letter->status);
        $this->assertNotNull($letter->signatory_signed_at);

        // 3. Acknowledge by Employee
        $response = $this->actingAs($this->staffUser)
            ->withSession(['current_role' => 'Employee'])
            ->post(route('appraisal.letters.acknowledge', $letter->id), [
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ]);

        $response->assertSessionHas('success');
        $letter->refresh();
        $this->assertEquals('acknowledged_by_employee', $letter->status);
        $this->assertNotNull($letter->employee_acknowledged_at);

        // 4. Download PDF
        $response = $this->actingAs($this->staffUser)
            ->get(route('appraisal.letters.pdf', $letter->id));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
