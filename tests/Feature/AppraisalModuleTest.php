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
        $this->assertTrue($appraisal->isEmployeeSubmissionLocked());
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

    public function test_admin_can_update_appraisal_template_with_valid_criteria_weightage()
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->put(route('appraisal.templates.update', $this->template->id), [
                'title' => 'Updated Senior Software Engineer Appraisal',
                'applicable_type' => 'designation',
                'designation_id' => $this->staffEmployee->designation_id,
                'description' => 'Updated appraisal criteria description',
                'criteria' => [
                    [
                        'name' => 'System Design & Code Quality',
                        'description' => 'Scalable patterns and test suites',
                        'weightage' => 60,
                    ],
                    [
                        'name' => 'Mentorship & Collaboration',
                        'description' => 'Mentoring junior teammates',
                        'weightage' => 40,
                    ],
                ],
            ]);

        $response->assertSessionHas('success');
        $this->template->refresh();
        $this->assertEquals('Updated Senior Software Engineer Appraisal', $this->template->title);
        $this->assertCount(2, $this->template->criteria);
        $this->assertEquals(100.0, (float) $this->template->criteria->sum('weightage'));

        // Test weightage validation failure when not 100%
        $invalidResponse = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->put(route('appraisal.templates.update', $this->template->id), [
                'title' => 'Invalid Weight Template',
                'applicable_type' => 'designation',
                'criteria' => [
                    ['name' => 'Criterion A', 'weightage' => 50],
                    ['name' => 'Criterion B', 'weightage' => 40], // Sums to 90%, not 100%
                ],
            ]);

        $invalidResponse->assertSessionHas('error');
    }

    public function test_mid_year_and_year_end_appraisal_cycle_structure_and_increment_rules(): void
    {
        // 1. Launch Mid-Year Review cycle
        $midYearResponse = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.cycles.store'), [
                'name' => 'FY 2026/2027 Mid-Year Performance Review',
                'review_period' => 'Mid-Year',
                'cycle_type' => 'mid_year',
                'financial_year' => 'FY 2026/2027',
                'performance_period' => 'April to September',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'self_eval_deadline' => '2026-10-20',
                'manager_eval_deadline' => '2026-10-31',
                'allow_increments' => 0,
            ]);

        $midYearResponse->assertSessionHas('success');
        $midYearCycle = AppraisalCycle::where('name', 'FY 2026/2027 Mid-Year Performance Review')->first();
        $this->assertNotNull($midYearCycle);
        $this->assertTrue($midYearCycle->isMidYear());
        $this->assertFalse($midYearCycle->allowsIncrements());
        $this->assertEquals('April to September', $midYearCycle->performance_period);

        // Create an appraisal under Mid-Year cycle
        $midYearAppraisal = Appraisal::create([
            'cycle_id' => $midYearCycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'completed',
        ]);

        // Attempting to grant increments during Mid-Year review must be rejected
        $incResponse = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.increment-promotion', $midYearAppraisal->id), [
                'increment_percentage' => 10,
            ]);

        $incResponse->assertSessionHas('error');
        $midYearAppraisal->refresh();
        $this->assertEquals(0, (float)$midYearAppraisal->increment_percentage);

        // 2. Launch Year-End Review cycle
        $yearEndResponse = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.cycles.store'), [
                'name' => 'FY 2026/2027 Year-End Performance Review',
                'review_period' => 'Year-End',
                'cycle_type' => 'year_end',
                'financial_year' => 'FY 2026/2027',
                'performance_period' => 'October to March',
                'start_date' => '2027-02-15',
                'end_date' => '2027-03-15',
                'self_eval_deadline' => '2027-03-01',
                'manager_eval_deadline' => '2027-03-15',
                'allow_increments' => 1,
            ]);

        $yearEndResponse->assertSessionHas('success');
        $yearEndCycle = AppraisalCycle::where('name', 'FY 2026/2027 Year-End Performance Review')->first();
        $this->assertNotNull($yearEndCycle);
        $this->assertTrue($yearEndCycle->isYearEnd());
        $this->assertTrue($yearEndCycle->allowsIncrements());
        $this->assertEquals('October to March', $yearEndCycle->performance_period);

        // Create an appraisal under Year-End cycle
        $yearEndAppraisal = Appraisal::create([
            'cycle_id' => $yearEndCycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'completed',
        ]);

        // Year-End allows increment and promotion recommendation
        $incSuccessResponse = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.increment-promotion', $yearEndAppraisal->id), [
                'increment_percentage' => 12.5,
                'is_promoted' => 1,
            ]);

        $incSuccessResponse->assertSessionHas('success');
        $yearEndAppraisal->refresh();
        $this->assertEquals(12.5, (float)$yearEndAppraisal->increment_percentage);
        $this->assertTrue($yearEndAppraisal->is_promoted);
    }

    public function test_employee_has_only_evaluation_form_and_changes_requested(): void
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

        // 1. Employee visits appraisal detail page
        $response = $this->actingAs($this->staffUser)
            ->withSession(['current_role' => 'Employee'])
            ->get(route('appraisal.show', $appraisal->id));

        $response->assertStatus(200);
        $response->assertSee('Evaluation Form');
        $response->assertSee('Changes Requested');
        $response->assertDontSee('Increment & Promotion');
        $response->assertDontSee('Letters & E-Signatures');

        // 2. Employee visits appraisal hub
        $hubResponse = $this->actingAs($this->staffUser)
            ->withSession(['current_role' => 'Employee'])
            ->get(route('appraisal.index'));

        $hubResponse->assertStatus(200);
        $hubResponse->assertSee('Evaluation Form');
        $hubResponse->assertSee('Changes Requested');
        $hubResponse->assertDontSee('Official Letters & E-Signatures');

        // 3. Employee visits change requests page
        $crResponse = $this->actingAs($this->staffUser)
            ->withSession(['current_role' => 'Employee'])
            ->get(route('appraisal.change-requests.index'));

        $crResponse->assertStatus(200);
        $crResponse->assertSee('My Changes Requested');
    }

    public function test_auto_initialization_when_template_enabled(): void
    {
        // Create an inactive template
        $template = AppraisalTemplate::create([
            'title' => 'Auto Init QA Template',
            'applicable_type' => 'General',
            'is_active' => false,
        ]);
        AppraisalTemplateCriterion::create([
            'template_id' => $template->id,
            'name' => 'Testing Rigor',
            'weightage' => 100.00,
            'sort_order' => 1,
        ]);

        // Toggle template to active
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.templates.toggle', $template->id));

        $response->assertSessionHas('success');
        $template->refresh();
        $this->assertTrue($template->is_active);

        // Appraisal should be created for staff employee
        $appraisal = Appraisal::where('cycle_id', $this->cycle->id)
            ->where('employee_id', $this->staffEmployee->id)
            ->first();

        $this->assertNotNull($appraisal);
        $this->assertTrue($appraisal->criteriaScores()->count() > 0);
    }

    public function test_employee_submission_locked_when_deadline_surpassed(): void
    {
        // Cycle where self_eval_deadline passed yesterday
        $overdueCycle = AppraisalCycle::create([
            'name' => 'Overdue Cycle',
            'review_period' => 'Annual',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(2),
            'self_eval_deadline' => now()->subDay(),
            'status' => 'Active',
        ]);

        $appraisal = Appraisal::create([
            'cycle_id' => $overdueCycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'pending_self_eval',
            'is_locked' => false,
        ]);

        $cs = AppraisalCriteriaScore::create([
            'appraisal_id' => $appraisal->id,
            'criteria_name' => 'Quality',
            'weightage' => 100,
            'self_rating' => 4,
        ]);

        // Employee attempts to submit after deadline
        $response = $this->actingAs($this->staffUser)
            ->withSession(['current_role' => 'Employee'])
            ->post(route('appraisal.submit-self', $appraisal->id), [
                'scores' => [$cs->id => ['self_rating' => 4, 'self_comments' => 'Late submission']],
            ]);

        $response->assertSessionHas('error');
        $appraisal->refresh();
        $this->assertEquals('pending_self_eval', $appraisal->status);
        $this->assertTrue($appraisal->isEmployeeSubmissionLocked());
    }

    public function test_manager_review_locked_when_deadline_surpassed(): void
    {
        // Cycle where manager_eval_deadline passed yesterday
        $overdueCycle = AppraisalCycle::create([
            'name' => 'Manager Overdue Cycle',
            'review_period' => 'Annual',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(2),
            'self_eval_deadline' => now()->subDays(5),
            'manager_eval_deadline' => now()->subDay(),
            'status' => 'Active',
        ]);

        $appraisal = Appraisal::create([
            'cycle_id' => $overdueCycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'reporting_manager_id' => $this->hodEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'three_tier',
            'status' => 'pending_manager_eval',
            'is_locked' => false,
        ]);

        $cs = AppraisalCriteriaScore::create([
            'appraisal_id' => $appraisal->id,
            'criteria_name' => 'Quality',
            'weightage' => 100,
            'self_rating' => 4,
            'manager_rating' => 4,
        ]);

        // Manager attempts to submit after manager deadline
        $response = $this->actingAs($this->hodUser)
            ->withSession(['current_role' => 'Manager'])
            ->post(route('appraisal.submit-manager', $appraisal->id), [
                'scores' => [$cs->id => ['manager_rating' => 4, 'manager_comments' => 'Overdue review']],
            ]);

        $response->assertSessionHas('error');
        $appraisal->refresh();
        $this->assertEquals('pending_manager_eval', $appraisal->status);
        $this->assertTrue($appraisal->isManagerReviewLocked());
    }

    public function test_hr_unlock_extends_deadline_and_allows_submission(): void
    {
        $overdueCycle = AppraisalCycle::create([
            'name' => 'Extension Cycle',
            'review_period' => 'Annual',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(2),
            'self_eval_deadline' => now()->subDay(),
            'status' => 'Active',
        ]);

        $appraisal = Appraisal::create([
            'cycle_id' => $overdueCycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'pending_self_eval',
            'is_locked' => false,
        ]);

        $cs = AppraisalCriteriaScore::create([
            'appraisal_id' => $appraisal->id,
            'criteria_name' => 'Quality',
            'weightage' => 100,
            'self_rating' => 4,
        ]);

        // HR grants 7-day unlock extension
        $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->post(route('appraisal.unlock', $appraisal->id), [
                'target_stage' => 'pending_self_eval',
                'extension_days' => 7,
                'notes' => 'Medical leave extension granted.',
            ]);

        $appraisal->refresh();
        $this->assertTrue($appraisal->is_override_unlocked);
        $this->assertFalse($appraisal->isEmployeeSubmissionLocked());

        // Employee now successfully submits within extension
        $submitResponse = $this->actingAs($this->staffUser)
            ->withSession(['current_role' => 'Employee'])
            ->post(route('appraisal.submit-self', $appraisal->id), [
                'scores' => [$cs->id => ['self_rating' => 4, 'self_comments' => 'Extension submission']],
            ]);

        $submitResponse->assertRedirect(route('appraisal.show', $appraisal->id));
        $appraisal->refresh();
        $this->assertEquals('pending_hod_eval', $appraisal->status);
    }

    public function test_admin_unrestricted_access_bypasses_all_duration_locks(): void
    {
        // Cycle overdue across all deadlines
        $overdueCycle = AppraisalCycle::create([
            'name' => 'Closed Deadlines Cycle',
            'review_period' => 'Annual',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subDay(),
            'self_eval_deadline' => now()->subDays(10),
            'manager_eval_deadline' => now()->subDays(5),
            'status' => 'Active',
        ]);

        $appraisal = Appraisal::create([
            'cycle_id' => $overdueCycle->id,
            'template_id' => $this->template->id,
            'employee_id' => $this->staffEmployee->id,
            'hod_id' => $this->hodEmployee->id,
            'workflow_type' => 'two_tier',
            'status' => 'pending_self_eval',
            'is_locked' => true,
        ]);

        $cs = AppraisalCriteriaScore::create([
            'appraisal_id' => $appraisal->id,
            'criteria_name' => 'Leadership',
            'weightage' => 100,
            'self_rating' => 3,
        ]);

        // Admin saves draft despite lock and expired deadlines
        $draftResponse = $this->actingAs($this->adminUser)
            ->withSession(['current_role' => 'Super (Admin)'])
            ->from(route('appraisal.show', $appraisal->id))
            ->post(route('appraisal.draft', $appraisal->id), [
                'scores' => [$cs->id => ['self_rating' => 5, 'manager_rating' => 5]],
                'general_manager_comments' => 'Admin override evaluation.',
            ]);

        $draftResponse->assertRedirect(route('appraisal.show', $appraisal->id));
        $draftResponse->assertSessionHas('success');
        $cs->refresh();
        $this->assertEquals(5, $cs->self_rating);
        $this->assertEquals(5, $cs->manager_rating);
    }
}
