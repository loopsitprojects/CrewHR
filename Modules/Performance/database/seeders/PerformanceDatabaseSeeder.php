<?php

namespace Modules\Performance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Designation;
use Modules\Employee\Models\Department;
use Modules\Performance\Models\AppraisalCycle;
use Modules\Performance\Models\AppraisalTemplate;
use Modules\Performance\Models\AppraisalTemplateCriterion;
use Modules\Performance\Models\Appraisal;
use Modules\Performance\Models\AppraisalCriteriaScore;
use Modules\Performance\Models\AppraisalLetter;

class PerformanceDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Assign HODs and Reporting Managers to establish hierarchy
        $headOfIt = Employee::where('id', 4)->first(); // Arosh
        $superAdmin = Employee::where('id', 6)->first(); // Super Admin

        if ($headOfIt) {
            // Shimal (5): 3-tier: Shimal -> Sahan (2) -> Arosh (4)
            Employee::where('id', 5)->update([
                'reporting_person_id' => 2,
                'hod_id' => 4,
            ]);

            // Sahan (2): 2-tier: Sahan -> Arosh (4)
            Employee::where('id', 2)->update([
                'reporting_person_id' => null,
                'hod_id' => 4,
            ]);

            // Anjalie (1): 2-tier: Anjalie -> Arosh (4)
            Employee::where('id', 1)->update([
                'reporting_person_id' => null,
                'hod_id' => 4,
            ]);
        }

        if ($superAdmin) {
            // Supuni (3): HR Lead reports to Super Admin
            Employee::where('id', 3)->update([
                'reporting_person_id' => null,
                'hod_id' => 6,
            ]);
        }

        // 2. Create Appraisal Templates
        $devDesignation = Designation::where('name', 'like', '%Developer%')->first();
        $pmDesignation = Designation::where('name', 'like', '%Product Manager%')->first();
        $itDept = Department::where('name', 'IT')->first();

        // Template A: Engineering & Technical
        $techTemplate = AppraisalTemplate::firstOrCreate(
            ['title' => 'Software Engineering & Technical Appraisal'],
            [
                'applicable_type' => 'Designation',
                'designation_id' => $devDesignation?->id,
                'department_id' => $itDept?->id,
                'description' => 'Standard evaluation template for Software Engineers, Frontend/Backend Developers, and DevOps.',
                'is_active' => true,
            ]
        );

        $techCriteria = [
            ['name' => 'Technical Proficiency & Architecture', 'description' => 'Knowledge of core stacks, clean architecture, security, and scalability standards.', 'weightage' => 25.00, 'sort_order' => 1],
            ['name' => 'Code Quality & Delivery Speed', 'description' => 'Consistently delivers maintainable, tested, and reliable code on sprint schedules.', 'weightage' => 25.00, 'sort_order' => 2],
            ['name' => 'Problem Solving & Innovation', 'description' => 'Ability to troubleshoot complex bugs, propose optimizations, and engineer resilient solutions.', 'weightage' => 20.00, 'sort_order' => 3],
            ['name' => 'Communication & Team Collaboration', 'description' => 'Active code review participation, clear technical documentation, and cross-functional empathy.', 'weightage' => 15.00, 'sort_order' => 4],
            ['name' => 'Ownership, Reliability & Deadlines', 'description' => 'Accountability for deployed features, proactive on-call support, and dependable task completion.', 'weightage' => 15.00, 'sort_order' => 5],
        ];

        foreach ($techCriteria as $c) {
            AppraisalTemplateCriterion::firstOrCreate(
                ['template_id' => $techTemplate->id, 'name' => $c['name']],
                $c
            );
        }

        // Template B: Product & Project Management
        $pmTemplate = AppraisalTemplate::firstOrCreate(
            ['title' => 'Product & Project Management Appraisal'],
            [
                'applicable_type' => 'Designation',
                'designation_id' => $pmDesignation?->id,
                'department_id' => $itDept?->id,
                'description' => 'Evaluation template tailored for Product Managers and Technical Leads.',
                'is_active' => true,
            ]
        );

        $pmCriteria = [
            ['name' => 'Product Strategy & Vision', 'description' => 'Translates business goals into actionable roadmaps, user stories, and measurable outcomes.', 'weightage' => 30.00, 'sort_order' => 1],
            ['name' => 'Execution & Delivery Management', 'description' => 'Removes sprint blockers, prioritizes backlog, and ensures timely release cadences.', 'weightage' => 25.00, 'sort_order' => 2],
            ['name' => 'Stakeholder Communication', 'description' => 'Transparent updates to leadership, clients, and engineering teams with data-backed decisions.', 'weightage' => 20.00, 'sort_order' => 3],
            ['name' => 'Problem Solving & Adaptability', 'description' => 'Handles scope pivots gracefully, manages release risks, and resolves dependencies.', 'weightage' => 15.00, 'sort_order' => 4],
            ['name' => 'Leadership & Team Mentorship', 'description' => 'Fosters a collaborative environment, motivates peers, and models corporate values.', 'weightage' => 10.00, 'sort_order' => 5],
        ];

        foreach ($pmCriteria as $c) {
            AppraisalTemplateCriterion::firstOrCreate(
                ['template_id' => $pmTemplate->id, 'name' => $c['name']],
                $c
            );
        }

        // Template C: Probationary Review
        $probationTemplate = AppraisalTemplate::firstOrCreate(
            ['title' => 'Probationary Assessment Template'],
            [
                'applicable_type' => 'Probation',
                'description' => 'Standard probation completion evaluation for newly hired personnel.',
                'is_active' => true,
            ]
        );

        $probationCriteria = [
            ['name' => 'Job Competencies & Domain Skill', 'description' => 'Grasps daily responsibilities and demonstrates expected baseline technical knowledge.', 'weightage' => 35.00, 'sort_order' => 1],
            ['name' => 'Culture Alignment & Work Ethic', 'description' => 'Embraces company values, punctuality, respect, and professional conduct.', 'weightage' => 25.00, 'sort_order' => 2],
            ['name' => 'Punctuality & Attendance Reliability', 'description' => 'Consistent daily clock-in discipline and adherence to working hours.', 'weightage' => 20.00, 'sort_order' => 3],
            ['name' => 'Learning Agility & Initiative', 'description' => 'Speed of onboarding, receptiveness to feedback, and proactive curiosity.', 'weightage' => 20.00, 'sort_order' => 4],
        ];

        foreach ($probationCriteria as $c) {
            AppraisalTemplateCriterion::firstOrCreate(
                ['template_id' => $probationTemplate->id, 'name' => $c['name']],
                $c
            );
        }

        // 3. Create Standard Two-Cycle Structure per Financial Year
        
        // Cycle A: Mid-Year Review (Window: Oct 01 - Oct 31, Period: Apr - Sep, No Increments)
        $midYearCycle = AppraisalCycle::updateOrCreate(
            ['name' => 'FY 2026/2027 Mid-Year Performance Review'],
            [
                'review_period' => 'Mid-Year',
                'cycle_type' => 'mid_year',
                'financial_year' => 'FY 2026/2027',
                'performance_period' => 'April to September',
                'performance_period_start' => '2026-04-01',
                'performance_period_end' => '2026-09-30',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'self_eval_deadline' => '2026-10-20',
                'manager_eval_deadline' => '2026-10-31',
                'allow_increments' => false,
                'status' => 'Active',
                'description' => 'Covers the performance period from April to September. Focuses on reviewing employee progress, identifying areas for improvement, and setting or adjusting KPIs for the remainder of the year. This review will not involve salary increments.',
                'focus_areas' => 'Reviewing employee progress, identifying improvement areas, setting & adjusting KPIs for remainder of FY.',
            ]
        );

        // Cycle B: Year-End Review (Window: Feb 15 - Mar 15, Period: Oct - Mar, With Increments & Promotions)
        $yearEndCycle = AppraisalCycle::updateOrCreate(
            ['name' => 'FY 2026/2027 Year-End Performance Review'],
            [
                'review_period' => 'Year-End',
                'cycle_type' => 'year_end',
                'financial_year' => 'FY 2026/2027',
                'performance_period' => 'October to March',
                'performance_period_start' => '2026-10-01',
                'performance_period_end' => '2027-03-31',
                'start_date' => '2027-02-15',
                'end_date' => '2027-03-15',
                'self_eval_deadline' => '2027-03-01',
                'manager_eval_deadline' => '2027-03-15',
                'allow_increments' => true,
                'status' => 'Upcoming',
                'description' => 'Covers the performance period from October to March. Focuses on the final performance evaluation, identifying employees for potential promotions and increments. Increment recommendations are considered alongside the company\'s financial performance and approved budget.',
                'focus_areas' => 'Final performance evaluation, potential promotions, and increment recommendations considered alongside approved company budget.',
            ]
        );

        $cycle = $midYearCycle;

        // 4. Create Appraisals for Testing Different Workflow Stages
        
        // Appraisal 1: Shimal (Developer) -> 3-Tier (Shimal -> Sahan -> Arosh), Status: pending_self_eval
        $shimal = Employee::find(5);
        if ($shimal) {
            $appraisal1 = Appraisal::firstOrCreate(
                ['cycle_id' => $cycle->id, 'employee_id' => $shimal->id],
                [
                    'template_id' => $techTemplate->id,
                    'reporting_manager_id' => 2, // Sahan
                    'hod_id' => 4, // Arosh
                    'workflow_type' => 'three_tier',
                    'status' => 'pending_self_eval',
                    'is_locked' => false,
                    'total_weighted_score' => 0.00,
                    'performance_band' => 'Pending Evaluation',
                ]
            );

            // Populate criteria rows
            foreach ($techTemplate->criteria as $criterion) {
                AppraisalCriteriaScore::firstOrCreate(
                    ['appraisal_id' => $appraisal1->id, 'criteria_name' => $criterion->name],
                    [
                        'criteria_description' => $criterion->description,
                        'weightage' => $criterion->weightage,
                        'self_rating' => null,
                        'self_comments' => null,
                        'manager_rating' => null,
                        'manager_comments' => null,
                        'final_rating' => null,
                        'weighted_score' => 0.00,
                    ]
                );
            }
        }

        // Appraisal 2: Sahan (DevOps Engineer) -> 2-Tier (Sahan -> Arosh), Status: pending_hod_eval (Self-eval submitted)
        $sahan = Employee::find(2);
        if ($sahan) {
            $appraisal2 = Appraisal::firstOrCreate(
                ['cycle_id' => $cycle->id, 'employee_id' => $sahan->id],
                [
                    'template_id' => $techTemplate->id,
                    'reporting_manager_id' => null,
                    'hod_id' => 4, // Arosh
                    'workflow_type' => 'two_tier',
                    'status' => 'pending_hod_eval',
                    'is_locked' => true,
                    'self_submitted_at' => now()->subDays(2),
                    'total_weighted_score' => 0.00,
                    'general_employee_comments' => 'Successfully revamped the CI/CD pipeline and automated infrastructure deployment across AWS environments.',
                ]
            );

            $selfRatings = [4, 4, 5, 4, 4];
            $comments = [
                'Maintained 99.98% uptime across all containerized production clusters.',
                'Wrote automated pipeline tests reducing build failures by 40%.',
                'Migrated legacy logs to centralized OpenSearch stack.',
                'Conducted regular knowledge transfer sessions with developer teams.',
                'Handled multiple critical incident alarms within target SLA response times.',
            ];

            foreach ($techTemplate->criteria as $idx => $criterion) {
                AppraisalCriteriaScore::firstOrCreate(
                    ['appraisal_id' => $appraisal2->id, 'criteria_name' => $criterion->name],
                    [
                        'criteria_description' => $criterion->description,
                        'weightage' => $criterion->weightage,
                        'self_rating' => $selfRatings[$idx] ?? 4,
                        'self_comments' => $comments[$idx] ?? '',
                        'manager_rating' => null,
                        'manager_comments' => null,
                        'final_rating' => null,
                        'weighted_score' => 0.00,
                    ]
                );
            }
        }

        // Appraisal 3: Anjalie (Product Manager) -> Completed, with Increment, Promotion, and Letter
        $anjalie = Employee::find(1);
        if ($anjalie) {
            $appraisal3 = Appraisal::firstOrCreate(
                ['cycle_id' => $cycle->id, 'employee_id' => $anjalie->id],
                [
                    'template_id' => $pmTemplate->id,
                    'reporting_manager_id' => null,
                    'hod_id' => 4, // Arosh
                    'workflow_type' => 'two_tier',
                    'status' => 'completed',
                    'is_locked' => true,
                    'self_submitted_at' => now()->subDays(10),
                    'hod_submitted_at' => now()->subDays(5),
                    'total_weighted_score' => 4.35,
                    'performance_band' => 'Exceeds Expectations',
                    'general_employee_comments' => 'Led the agile product roadmap across Q1-Q4 delivering 3 major customer portal modules ahead of schedule.',
                    'general_hod_comments' => 'Exceptional delivery coordination. Consistently exhibits strong ownership and bridges business and dev effortlessly.',
                    'increment_percentage' => 12.50,
                    'is_promoted' => true,
                    'new_designation_id' => Designation::where('name', 'like', '%Product Manager%')->first()?->id,
                    'effective_date' => '2027-01-01',
                    'is_increment_visible_to_employee' => true,
                    'is_increment_visible_to_hod' => true,
                ]
            );

            $ratings = [
                ['self' => 4, 'mgr' => 5, 'sc' => 'Defined roadmap metrics clearly.', 'mc' => 'Superb roadmap clarity and alignment.'],
                ['self' => 4, 'mgr' => 4, 'sc' => 'Sprint velocity improved by 25%.', 'mc' => 'Reliable sprint burndown.'],
                ['self' => 5, 'mgr' => 4, 'sc' => 'Conducted weekly demos with client executives.', 'mc' => 'Clients consistently praise communication quality.'],
                ['self' => 4, 'mgr' => 4, 'sc' => 'Resolved priority conflicts smoothly.', 'mc' => 'Practical and calm under delivery pressure.'],
                ['self' => 4, 'mgr' => 5, 'sc' => 'Mentored associate PMs in Jira workflows.', 'mc' => 'Great leadership maturity and team empathy.'],
            ];

            $totalWeighted = 0;
            foreach ($pmTemplate->criteria as $idx => $criterion) {
                $r = $ratings[$idx] ?? ['self' => 4, 'mgr' => 4, 'sc' => '', 'mc' => ''];
                $finalRating = $r['mgr'];
                $weighted = round($finalRating * ($criterion->weightage / 100), 2);
                $totalWeighted += $weighted;

                AppraisalCriteriaScore::firstOrCreate(
                    ['appraisal_id' => $appraisal3->id, 'criteria_name' => $criterion->name],
                    [
                        'criteria_description' => $criterion->description,
                        'weightage' => $criterion->weightage,
                        'self_rating' => $r['self'],
                        'self_comments' => $r['sc'],
                        'manager_rating' => $r['mgr'],
                        'manager_comments' => $r['mc'],
                        'final_rating' => $finalRating,
                        'weighted_score' => $weighted,
                    ]
                );
            }
            $appraisal3->update(['total_weighted_score' => $totalWeighted]);

            // Create a Letter for Anjalie
            AppraisalLetter::firstOrCreate(
                ['reference_no' => 'LTR/2026/INC-001'],
                [
                    'appraisal_id' => $appraisal3->id,
                    'employee_id' => $anjalie->id,
                    'letter_type' => 'increment',
                    'title' => 'Annual Performance Increment & Revision Letter - 2026',
                    'template_content' => '<p>Dear Anjalie De Silva,</p><p>We are pleased to inform you that in recognition of your exemplary performance during the <strong>2026 Annual Performance Review</strong> (Performance Band: <strong>Exceeds Expectations</strong>, Score: <strong>4.35 / 5.00</strong>), management has approved a salary increment of <strong>12.50%</strong> effective from <strong>01 January 2027</strong>.</p><p>We look forward to your continued dedication and leadership in driving product excellence.</p>',
                    'status' => 'pending_signatory',
                    'signatory_name' => 'Anoj / Shehan',
                ]
            );
        }
    }
}
