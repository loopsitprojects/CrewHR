<?php

namespace Modules\Payroll\Tests\Unit;

use Tests\TestCase;
use Modules\Payroll\Services\PayrollCalculationService;
use Modules\Payroll\Services\PayrollAccountingService;
use Modules\Employee\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollStatutoryCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected PayrollCalculationService $calcService;
    protected PayrollAccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calcService = new PayrollCalculationService();
        $this->accountingService = new PayrollAccountingService();

        $this->department = \Modules\Employee\Models\Department::firstOrCreate(
            ['code' => 'ENG'],
            ['name' => 'Engineering', 'description' => 'Engineering Dept']
        );
        $this->designation = \Modules\Employee\Models\Designation::firstOrCreate(
            ['name' => 'Software Engineer'],
            ['department_id' => $this->department->id]
        );
    }

    public function test_total_for_epf_calculation()
    {
        // Total for EPF = Base Pay (Basic + Increments + Budget) + Arrears Basic - No Pay Basic
        $basePay = 150000.0;
        $salaryArrearsBasic = 10000.0;
        $noPayBasic = 5000.0;

        $totalForEpf = $this->calcService->calculateTotalForEpf($basePay, $salaryArrearsBasic, $noPayBasic);
        $this->assertEquals(155000.0, $totalForEpf);
    }

    public function test_epf_and_etf_statutory_rates()
    {
        $totalForEpf = 200000.0;
        $statutory = $this->calcService->calculateEpfEtf($totalForEpf);

        $this->assertEquals(16000.0, $statutory['epf_employee']); // 8%
        $this->assertEquals(24000.0, $statutory['epf_employer']); // 12%
        $this->assertEquals(6000.0, $statutory['etf_employer']);  // 3%
        $this->assertEquals(40000.0, $statutory['total_epf']);    // 20%
    }

    public function test_no_pay_split_between_basic_and_allowances()
    {
        $totalBasePay = 150000.0; // 5000/day for 30 days
        $totalFixedAllowance = 60000.0; // 2000/day for 30 days
        $noPayDays = 3.0;

        $split = $this->calcService->calculateNoPaySplit($totalBasePay, $totalFixedAllowance, $noPayDays, 30);

        $this->assertEquals(15000.0, $split['no_pay_basic']);
        $this->assertEquals(6000.0, $split['no_pay_allowance']);
        $this->assertEquals(21000.0, $split['total_no_pay']);
    }

    public function test_sri_lanka_apit_progressive_tax_brackets()
    {
        // 1. Below 100k -> 0 tax
        $this->assertEquals(0.0, $this->calcService->calculateApitTax(95000));
        $this->assertEquals(0.0, $this->calcService->calculateApitTax(100000));

        // 2. 120k -> 20k @ 6% = 1,200
        $this->assertEquals(1200.0, $this->calcService->calculateApitTax(120000));

        // 3. 150k -> 41,666.67 @ 6% (2,500) + 8,333.33 @ 12% (1,000) = 3,500
        $this->assertEquals(3500.0, $this->calcService->calculateApitTax(150000));
    }

    public function test_double_entry_accounting_journal_balances_with_zero_variance()
    {
        $user1 = User::factory()->create(['name' => 'Direct Dev']);
        $user2 = User::factory()->create(['name' => 'Indirect Admin']);

        $empDirect = Employee::create([
            'user_id' => $user1->id,
            'employee_id_number' => 'EMP-001',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'basic_salary' => 200000,
            'increments_basic' => 10000,
            'budget_allowance' => 5000,
            'travelling_allowance' => 20000,
            'cost_of_living_allowance' => 10000,
            'cost_classification' => 'Direct',
            'staff_category' => 'Executive',
        ]);

        $empIndirect = Employee::create([
            'user_id' => $user2->id,
            'employee_id_number' => 'EMP-002',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'basic_salary' => 120000,
            'increments_basic' => 0,
            'budget_allowance' => 5000,
            'travelling_allowance' => 15000,
            'cost_of_living_allowance' => 5000,
            'cost_classification' => 'Indirect',
            'staff_category' => 'Non-Executive',
        ]);

        $payroll = Payroll::create([
            'month' => 9,
            'year' => 2026,
            'cycle_name' => 'September 2026 Payroll',
            'status' => 'Processed',
        ]);

        // Process Direct employee payslip
        $calc1 = $this->calcService->calculateEmployeePayroll($empDirect, 9, 2026, [
            'ot_amount' => 15000,
            'no_pay_days' => 2,
            'personal_expense_recovery' => 3500, // PickMe personal ride
        ]);
        $calc1['payroll_id'] = $payroll->id;
        unset($calc1['active_loan'], $calc1['advance_record']);
        Payslip::create($calc1);

        // Process Indirect employee payslip
        $calc2 = $this->calcService->calculateEmployeePayroll($empIndirect, 9, 2026, [
            'salary_advance' => 10000,
            'no_pay_days' => 1,
        ]);
        $calc2['payroll_id'] = $payroll->id;
        unset($calc2['active_loan'], $calc2['advance_record']);
        Payslip::create($calc2);

        $payroll->load('payslips.employee');

        // Generate double-entry journal
        $journal = $this->accountingService->generateJournalEntries($payroll);

        $this->assertTrue($journal['is_balanced'], 'Double entry journal must be perfectly balanced');
        $this->assertEquals(0.0, $journal['variance'], 'Variance between Debits and Credits must be exactly 0.00');
        $this->assertGreaterThan(0, $journal['total_debits']);
        $this->assertEquals($journal['total_debits'], $journal['total_credits']);
    }

    public function test_dynamic_employee_allowances_and_epf_liability_in_payroll_calculation()
    {
        $user = User::factory()->create(['name' => 'Dynamic Allowance Emp']);
        $emp = Employee::create([
            'user_id' => $user->id,
            'employee_id_number' => 'EMP-DYN-01',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'basic_salary' => 100000,
            'increments_basic' => 10000,
            'budget_allowance' => 5000, // total base = 115,000
            'cost_classification' => 'Direct',
            'staff_category' => 'Executive',
        ]);

        $travelType = \Modules\Payroll\Models\AllowanceType::create([
            'name' => 'Fuel Allowance',
            'code' => 'FUEL_DYN',
            'is_epf_liable' => false,
            'default_amount' => 12000,
            'status' => 'Active',
        ]);

        $specialType = \Modules\Payroll\Models\AllowanceType::create([
            'name' => 'Acting Allowance',
            'code' => 'ACTING_DYN',
            'is_epf_liable' => true,
            'default_amount' => 25000,
            'status' => 'Active',
        ]);

        \Modules\Payroll\Models\EmployeeAllowance::create([
            'employee_id' => $emp->id,
            'allowance_type_id' => $travelType->id,
            'amount' => 12000,
        ]);

        \Modules\Payroll\Models\EmployeeAllowance::create([
            'employee_id' => $emp->id,
            'allowance_type_id' => $specialType->id,
            'amount' => 25000,
        ]);

        $emp->load('employeeAllowances.allowanceType');

        $result = $this->calcService->calculateEmployeePayroll($emp, 9, 2026);

        // Total base pay = 100,000 + 10,000 + 5,000 = 115,000
        $this->assertEquals(115000.0, $result['total_base_pay']);
        // Total fixed allowances = 12,000 + 25,000 = 37,000
        $this->assertEquals(37000.0, $result['total_fixed_allowance']);
        // Total for EPF = 115,000 + 25,000 (EPF liable) = 140,000
        $this->assertEquals(140000.0, $result['total_for_epf']);
        // EPF Employee = 140,000 * 8% = 11,200
        $this->assertEquals(11200.0, $result['epf_employee']);
        // EPF Employer = 140,000 * 12% = 16,800
        $this->assertEquals(16800.0, $result['epf_employer']);
        // ETF Employer = 140,000 * 3% = 4,200
        $this->assertEquals(4200.0, $result['etf_employer']);
        // Gross Salary = 115,000 + 37,000 = 152,000
        $this->assertEquals(152000.0, $result['gross_salary']);
    }
}
