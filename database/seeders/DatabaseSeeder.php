<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Employee\Models\Employee;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\Holiday;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Departments
        $itDept = Department::create(['name' => 'IT', 'code' => 'IT', 'hod_name' => 'Arosh', 'description' => 'Information Technology']);
        $hrDept = Department::create(['name' => 'HR/Admin', 'code' => 'HR', 'hod_name' => 'Supuni Ratnayake', 'description' => 'Human Resources & Administration']);
        $mgmtDept = Department::create(['name' => 'Management', 'code' => 'MGMT', 'hod_name' => 'Super Admin', 'description' => 'Executive Management']);
        $creativeDept = Department::create(['name' => 'Creative', 'code' => 'CR', 'hod_name' => 'Creative Lead', 'description' => 'Design & Creative']);
        $financeDept = Department::create(['name' => 'Finance', 'code' => 'FIN', 'hod_name' => 'Finance Head', 'description' => 'Accounting & Finance']);
        $bizDept = Department::create(['name' => 'Business Development', 'code' => 'BD', 'hod_name' => 'BD Lead', 'description' => 'Sales & Business Growth']);
        $opsDept = Department::create(['name' => 'Operations', 'code' => 'OPS', 'hod_name' => 'Ops Manager', 'description' => 'Daily Operations']);

        // 2. Designations
        $desigHR = Designation::create(['name' => 'HR Manager']);
        $desigHeadIT = Designation::create(['name' => 'Head of IT']);
        $desigDevOps = Designation::create(['name' => 'DevOps Engineer']);
        $desigProdMgr = Designation::create(['name' => 'Product Manager']);
        $desigDev = Designation::create(['name' => 'Developer']);
        $desigAdmin = Designation::create(['name' => 'Managing Director']);

        // 3. Users & Employees

        // User 1: Anjalie De Silva (Product Manager)
        $userAnjalie = User::create([
            'name' => 'Anjalie De Silva',
            'username' => 'anjalie',
            'email' => 'anjalie.d@loopshr.lk',
            'password' => Hash::make('password'),
        ]);
        $empAnjalie = Employee::create([
            'user_id' => $userAnjalie->id,
            'employee_id_number' => 'EMP-0100',
            'title' => 'Ms.',
            'date_of_birth' => '1992-05-14',
            'department_id' => $itDept->id,
            'designation_id' => $desigProdMgr->id,
            'system_role' => 'Manager (Team Approvals)',
            'joined_date' => '2020-05-10',
            'epf_registration_no' => 'EPF/2020/0015',
            'job_category' => 'Full Time (Permanent)',
            'basic_salary' => 240000,
            'budget_allowance' => 10000,
            'travelling_allowance' => 25000,
            'cost_of_living_allowance' => 15000,
            'fixed_allowance' => 10000,
            'staff_category' => 'Executive',
            'cost_classification' => 'Direct',
            'bank_name' => 'Commercial Bank of Ceylon',
            'bank_branch' => 'Kollupitiya',
            'account_number' => '1004829104',
            'account_holder_name' => 'Anjalie De Silva',
        ]);

        // User 2: Sahan Wickramasinghe (DevOps Engineer)
        $userSahan = User::create([
            'name' => 'Sahan Wickramasinghe',
            'username' => 'sahan',
            'email' => 'sahan.w@loopshr.lk',
            'password' => Hash::make('password'),
        ]);
        $empSahan = Employee::create([
            'user_id' => $userSahan->id,
            'employee_id_number' => 'EMP-0104',
            'title' => 'Mr.',
            'profile_picture' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80',
            'date_of_birth' => '1990-03-29',
            'nic_passport' => '900892019V',
            'phone_number' => '+94 70 333 8899',
            'emergency_contact' => 'Chathuri Wickramasinghe (Wife) - +94 70 444 5566',
            'department_id' => $itDept->id,
            'designation_id' => $desigDevOps->id,
            'system_role' => 'Manager (Team Approvals)',
            'reporting_person_id' => $empAnjalie->id,
            'joined_date' => '2021-11-20',
            'epf_registration_no' => 'EPF/2021/0082',
            'job_category' => 'Full Time (Permanent)',
            'increment_amount' => 25000,
            'promotion_designation' => 'DevOps Lead',
            'promotion_date' => '2024-03-01',
            'basic_salary' => 190000,
            'fixed_allowance' => 35000,
            'other_allowance' => 25000,
            'apit_tax' => 12000,
            'bank_name' => 'Nations Trust Bank (NTB)',
            'bank_branch' => 'Bambalapitiya',
            'account_number' => '5003910284',
            'account_holder_name' => 'S. B. Wickramasinghe',
            'higher_education' => 'B.Sc. (Hons) Computer Science — University of Moratuwa',
            'professional_qualifications' => 'AWS Solutions Architect, PMP, CIMA',
        ]);

        // User 3: Supuni Ratnayake (HR Lead)
        $userSupuni = User::create([
            'name' => 'Supuni Ratnayake',
            'username' => 'supuni',
            'email' => 'supuni@loopshr.com',
            'password' => Hash::make('password'),
        ]);
        $empSupuni = Employee::create([
            'user_id' => $userSupuni->id,
            'employee_id_number' => 'EMP-1002',
            'title' => 'Ms.',
            'date_of_birth' => '1994-08-28',
            'department_id' => $hrDept->id,
            'designation_id' => $desigHR->id,
            'system_role' => 'HR Lead',
            'epf_registration_no' => 'EPF-1002',
            'joined_date' => '2023-03-15',
            'basic_salary' => 180000,
        ]);

        // User 4: Arosh (Manager)
        $userArosh = User::create([
            'name' => 'Arosh',
            'username' => 'arosh',
            'email' => 'arosh@loopshr.com',
            'password' => Hash::make('password'),
        ]);
        $empArosh = Employee::create([
            'user_id' => $userArosh->id,
            'employee_id_number' => 'REQ-1093',
            'title' => 'Mr.',
            'date_of_birth' => '1988-08-21',
            'department_id' => $itDept->id,
            'designation_id' => $desigHeadIT->id,
            'system_role' => 'Manager (Team Approvals)',
            'epf_registration_no' => 'EPF-1093',
            'joined_date' => '2023-06-01',
            'reporting_person_id' => $empSupuni->id,
            'basic_salary' => 220000,
        ]);

        // User 5: Shimal (Employee)
        $userShimal = User::create([
            'name' => 'Shimal',
            'username' => 'shimal',
            'email' => 'shimal@loopshr.com',
            'password' => Hash::make('password'),
        ]);
        $empShimal = Employee::create([
            'user_id' => $userShimal->id,
            'employee_id_number' => 'REQ-1092',
            'title' => 'Mr.',
            'date_of_birth' => '1996-08-12',
            'department_id' => $itDept->id,
            'designation_id' => $desigDev->id,
            'system_role' => 'Employee',
            'epf_registration_no' => 'EPF-1092',
            'joined_date' => '2024-01-10',
            'reporting_person_id' => $empArosh->id,
            'basic_salary' => 150000,
        ]);

        // User 6: Super Admin
        $userSuper = User::create([
            'name' => 'Super Admin',
            'username' => 'admin',
            'email' => 'admin@loopshr.com',
            'password' => Hash::make('password'),
        ]);
        $empSuper = Employee::create([
            'user_id' => $userSuper->id,
            'employee_id_number' => 'EMP-1001',
            'title' => 'Mr.',
            'date_of_birth' => '1985-01-10',
            'department_id' => $mgmtDept->id,
            'designation_id' => $desigAdmin->id,
            'system_role' => 'Super (Admin)',
            'epf_registration_no' => 'EPF-1001',
            'joined_date' => '2022-01-01',
            'basic_salary' => 350000,
            'budget_allowance' => 10000,
            'travelling_allowance' => 30000,
            'cost_of_living_allowance' => 15000,
            'staff_category' => 'Management',
            'cost_classification' => 'Indirect',
            'bank_name' => 'Commercial Bank of Ceylon',
            'bank_branch' => 'Head Office',
            'account_number' => '8004921092',
            'account_holder_name' => 'Super Admin',
        ]);

        // 4. Leave Types (LOOPS HR Leave Brief)
        $ltAnnual = LeaveType::updateOrCreate(['code' => 'ANNUAL'], ['name' => 'Annual Leave', 'days' => 14]);
        $ltCasual = LeaveType::updateOrCreate(['code' => 'CASUAL'], ['name' => 'Casual Leave', 'days' => 7]);
        $ltMedical = LeaveType::updateOrCreate(['code' => 'MEDICAL'], ['name' => 'Medical / Sick Leave', 'days' => 7]);
        $ltShort = LeaveType::updateOrCreate(['code' => 'SHORT'], ['name' => 'Short Leave', 'days' => 2]);
        $ltHalfDay = LeaveType::updateOrCreate(['code' => 'HALF_DAY'], ['name' => 'Half Day Leave', 'days' => 7]);
        $ltDuty = LeaveType::updateOrCreate(['code' => 'DUTY'], ['name' => 'Duty Leave', 'days' => 30]);
        $ltLieu = LeaveType::updateOrCreate(['code' => 'LIEU'], ['name' => 'Lieu Leave', 'days' => 5]);
        $ltMaternity = LeaveType::updateOrCreate(['code' => 'MATERNITY'], ['name' => 'Maternity Leave', 'days' => 84]);
        $ltPaternity = LeaveType::updateOrCreate(['code' => 'PATERNITY'], ['name' => 'Paternity Leave', 'days' => 5]);

        // 5. Leave Balances
        // Sahan Wickramasinghe: Annual: 11 left, Casual: 3 left, Medical: 8 left, Paternity: 5 left
        EmployeeLeaveBalance::create(['employee_id' => $empSahan->id, 'leave_type_id' => $ltAnnual->id, 'allocated' => 14, 'used' => 3, 'carried_forward' => 0]);
        EmployeeLeaveBalance::create(['employee_id' => $empSahan->id, 'leave_type_id' => $ltCasual->id, 'allocated' => 7, 'used' => 4, 'carried_forward' => 0]);
        EmployeeLeaveBalance::create(['employee_id' => $empSahan->id, 'leave_type_id' => $ltMedical->id, 'allocated' => 14, 'used' => 6, 'carried_forward' => 0]);
        EmployeeLeaveBalance::create(['employee_id' => $empSahan->id, 'leave_type_id' => $ltPaternity->id, 'allocated' => 5, 'used' => 0, 'carried_forward' => 0]);

        foreach ([$empShimal, $empArosh, $empSupuni, $empSuper, $empAnjalie] as $emp) {
            EmployeeLeaveBalance::create(['employee_id' => $emp->id, 'leave_type_id' => $ltAnnual->id, 'allocated' => 14, 'used' => 5, 'carried_forward' => 2]);
            EmployeeLeaveBalance::create(['employee_id' => $emp->id, 'leave_type_id' => $ltCasual->id, 'allocated' => 7, 'used' => 2, 'carried_forward' => 0]);
            EmployeeLeaveBalance::create(['employee_id' => $emp->id, 'leave_type_id' => $ltMedical->id, 'allocated' => 14, 'used' => 8, 'carried_forward' => 0]);
            EmployeeLeaveBalance::create(['employee_id' => $emp->id, 'leave_type_id' => $ltShort->id, 'allocated' => 2, 'used' => 0, 'carried_forward' => 0]);
            EmployeeLeaveBalance::create(['employee_id' => $emp->id, 'leave_type_id' => $ltDuty->id, 'allocated' => 30, 'used' => 0, 'carried_forward' => 0]);
            EmployeeLeaveBalance::create(['employee_id' => $emp->id, 'leave_type_id' => $ltLieu->id, 'allocated' => 2, 'used' => 0, 'carried_forward' => 0]);
        }

        // 6. Leave Requests
        LeaveRequest::create([
            'req_number' => 'REQ-1092',
            'employee_id' => $empShimal->id,
            'leave_type_id' => $ltCasual->id,
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'duration' => 3,
            'status' => 'Manager Approved (Pending HR)',
            'reason' => 'Personal family matters and errands.',
            'covering_employee_id' => $empArosh->id,
            'covering_status' => 'Approved',
            'manager_employee_id' => $empArosh->id,
            'manager_status' => 'Approved',
            'hr_status' => 'Pending',
            'applied_at' => '2026-08-18',
        ]);

        LeaveRequest::create([
            'req_number' => 'REQ-1093',
            'employee_id' => $empArosh->id,
            'leave_type_id' => $ltAnnual->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'duration' => 3,
            'status' => 'Manager Approved (Pending HR)',
            'reason' => 'Annual vacation leave.',
            'covering_employee_id' => $empShimal->id,
            'covering_status' => 'Pending',
            'manager_employee_id' => $empSupuni->id,
            'manager_status' => 'Approved',
            'hr_status' => 'Pending',
            'applied_at' => '2026-08-10',
        ]);

        // 7. Holidays (2026)
        $slHolidays = [
            ['title' => 'Duruthu Full Moon Poya Day', 'date' => '2026-01-03', 'type' => 'Poya', 'category' => 'Public, Bank & Mercantile'],
            ['title' => 'Tamil Thai Pongal Day', 'date' => '2026-01-15', 'type' => 'Mercantile', 'category' => 'Public, Bank & Mercantile'],
            ['title' => 'Navam Full Moon Poya Day', 'date' => '2026-02-01', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'National Independence Day', 'date' => '2026-02-04', 'type' => 'Mercantile', 'category' => 'Public, Bank & Mercantile'],
            ['title' => 'Maha Sivarathri Day', 'date' => '2026-02-15', 'type' => 'Public', 'category' => 'Public & Bank'],
            ['title' => 'Medin Full Moon Poya Day', 'date' => '2026-03-02', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Id-Ul-Fitr (Ramazan Festival)', 'date' => '2026-03-21', 'type' => 'Public', 'category' => 'Public & Bank'],
            ['title' => 'Bak Full Moon Poya Day', 'date' => '2026-04-01', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Good Friday', 'date' => '2026-04-03', 'type' => 'Public', 'category' => 'Public & Bank'],
            ['title' => 'Day Prior to Sinhala & Tamil New Year', 'date' => '2026-04-13', 'type' => 'Mercantile', 'category' => 'Public, Bank & Mercantile'],
            ['title' => 'Sinhala & Tamil New Year Day', 'date' => '2026-04-14', 'type' => 'Mercantile', 'category' => 'Public, Bank & Mercantile'],
            ['title' => 'May Day (Labour Day)', 'date' => '2026-05-01', 'type' => 'Mercantile', 'category' => 'Public, Bank & Mercantile'],
            ['title' => 'Vesak Full Moon Poya Day', 'date' => '2026-05-01', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Day Following Vesak Full Moon Poya Day', 'date' => '2026-05-02', 'type' => 'Mercantile', 'category' => 'Public, Bank & Mercantile'],
            ['title' => 'Id-Ul-Alha (Hadji Festival)', 'date' => '2026-05-28', 'type' => 'Public', 'category' => 'Public & Bank'],
            ['title' => 'Adhi Poson Full Moon Poya Day', 'date' => '2026-05-30', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Poson Full Moon Poya Day', 'date' => '2026-06-29', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Esala Full Moon Poya Day', 'date' => '2026-07-29', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Milad-Un-Nabi (Holy Prophet Birthday)', 'date' => '2026-08-26', 'type' => 'Mercantile', 'category' => 'Public, Bank & Mercantile'],
            ['title' => 'Nikini Full Moon Poya Day', 'date' => '2026-08-27', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Binara Full Moon Poya Day', 'date' => '2026-09-26', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Vap Full Moon Poya Day', 'date' => '2026-10-25', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Deepavali Festival Day', 'date' => '2026-11-08', 'type' => 'Public', 'category' => 'Public & Bank'],
            ['title' => 'Ill Full Moon Poya Day', 'date' => '2026-11-24', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Unduvap Full Moon Poya Day', 'date' => '2026-12-23', 'type' => 'Poya', 'category' => 'Public & Bank'],
            ['title' => 'Christmas Day', 'date' => '2026-12-25', 'type' => 'Mercantile', 'category' => 'Public, Bank & Mercantile'],

            // Company Holidays
            ['title' => 'Company Founding Day', 'date' => '2026-06-15', 'type' => 'Company', 'category' => 'Company Holiday'],
            ['title' => 'Annual Staff Retreat & Outing', 'date' => '2026-09-18', 'type' => 'Company', 'category' => 'Special Leave'],
            ['title' => 'Christmas Eve Company Half-Day', 'date' => '2026-12-24', 'type' => 'Company', 'category' => 'Year-End Closure'],
            ['title' => 'New Year\'s Eve Year-End Closure', 'date' => '2026-12-31', 'type' => 'Company', 'category' => 'Year-End Closure'],
        ];

        foreach ($slHolidays as $h) {
            Holiday::updateOrCreate(['date' => $h['date'], 'title' => $h['title']], array_merge($h, ['description' => $h['title'] . ' (' . $h['category'] . ')']));
        }

        // 8. Settings
        $settings = [
            'company_name' => 'Loops HR Portal (Sri Lanka)',
            'timezone' => 'Asia/Colombo (GMT+5:30)',
            'base_currency' => 'LKR (Rs.)',
            'fiscal_year_start' => 'January',
            'annual_leave_max' => '21',
            'casual_leave_max' => '7',
            'medical_leave_max' => '14',
            'short_leave_max' => '2',
            'duty_leave_max' => '5',
            'lieu_leave_max' => '2',
            'auto_sync_holidays' => '1',
            'allow_half_day' => '1',
            'leave_management_enabled' => '1',
            'appraisal_system_enabled' => '1',
            'developer_mode' => '1',
            'current_role' => 'Super (Admin)',
            'db_driver' => 'MYSQL',
            'db_name' => 'hr',
            'db_host' => '127.0.0.1:3306',
            'db_user' => 'root',
        ];

        foreach ($settings as $k => $v) {
            DB::table('settings')->updateOrInsert(['key' => $k], ['value' => $v, 'created_at' => now(), 'updated_at' => now()]);
        }

        // 9. Initial HR Notifications
        \App\Models\HrNotification::create([
            'user_id' => $userSahan->id,
            'type' => 'leave_request',
            'title' => 'Leave Request Pending Approval',
            'message' => 'Anjalie De Silva applied for 2.0 days Annual Leave (REQ-1092).',
            'link' => route('approvals.index'),
            'is_read' => false,
        ]);

        \App\Models\HrNotification::create([
            'user_id' => $userSahan->id,
            'type' => 'leave_approved',
            'title' => 'Annual Leave Approved 🎉',
            'message' => 'Your Casual Leave request (REQ-4821) for 1.0 day was approved by HR Manager.',
            'link' => route('my-leaves.index'),
            'is_read' => false,
        ]);

        \App\Models\HrNotification::create([
            'user_id' => $userSahan->id,
            'type' => 'info',
            'title' => 'Public Holiday Notice 🇱🇰',
            'message' => 'Upcoming Holiday: Nikini Full Moon Poya Day on August 27, 2026.',
            'link' => route('dashboard'),
            'is_read' => false,
        ]);

        // 10. Loan Types & Dummy Loans
        $loanTypeDistress = \Modules\Payroll\Models\LoanType::updateOrCreate(['code' => 'DISTRESS'], [
            'name' => 'Distress / Welfare Loan',
            'description' => 'Interest-free emergency financial assistance for urgent employee medical or family distress.',
            'max_amount' => 150000,
            'interest_rate_annual' => 0.00,
            'max_repayment_months' => 24,
            'status' => 'Active',
        ]);

        $loanTypeHousing = \Modules\Payroll\Models\LoanType::updateOrCreate(['code' => 'HOUSING'], [
            'name' => 'Housing & Renovation Loan',
            'description' => 'Subsidized low-interest loan for home purchase, building or renovation.',
            'max_amount' => 500000,
            'interest_rate_annual' => 4.50,
            'max_repayment_months' => 48,
            'status' => 'Active',
        ]);

        $loanTypeFestival = \Modules\Payroll\Models\LoanType::updateOrCreate(['code' => 'FESTIVAL'], [
            'name' => 'Festival Advance / Loan',
            'description' => 'Seasonal advance given during Sinhala & Tamil New Year or Christmas season.',
            'max_amount' => 50000,
            'interest_rate_annual' => 0.00,
            'max_repayment_months' => 10,
            'status' => 'Active',
        ]);

        // Sahan's Active Distress Loan
        $loanSahan = \Modules\Payroll\Models\EmployeeLoan::updateOrCreate(
            ['loan_number' => 'LN-2026-001'],
            [
                'employee_id' => $empSahan->id,
                'loan_type_id' => $loanTypeDistress->id,
                'loan_title' => 'Emergency Home Appliance & Welfare Support',
                'principal_amount' => 120000.00,
                'interest_rate' => 0.00,
                'repayment_months' => 12,
                'monthly_installment' => 10000.00,
                'total_paid' => 30000.00,
                'remaining_balance' => 90000.00,
                'purpose' => 'Urgent home roof repair and electrical upgrades before monsoon season.',
                'status' => 'Active',
                'start_date' => '2026-06-01',
                'applied_at' => '2026-05-25 10:00:00',
                'approved_at' => '2026-05-28 14:30:00',
                'approved_by_user_id' => $userSuper->id,
            ]
        );

        // Arosh's Active Housing Loan
        $loanArosh = \Modules\Payroll\Models\EmployeeLoan::updateOrCreate(
            ['loan_number' => 'LN-2026-002'],
            [
                'employee_id' => $empArosh->id,
                'loan_type_id' => $loanTypeHousing->id,
                'loan_title' => 'Housing Construction Phase 2 Loan',
                'principal_amount' => 360000.00,
                'interest_rate' => 4.50,
                'repayment_months' => 24,
                'monthly_installment' => 15000.00,
                'total_paid' => 45000.00,
                'remaining_balance' => 315000.00,
                'purpose' => 'Finishing upstairs bedroom and solar panel installation.',
                'status' => 'Active',
                'start_date' => '2026-05-01',
                'applied_at' => '2026-04-20 09:15:00',
                'approved_at' => '2026-04-25 16:00:00',
                'approved_by_user_id' => $userSuper->id,
            ]
        );

        // Loan Repayment records
        \Modules\Payroll\Models\LoanRepayment::updateOrCreate(
            ['employee_loan_id' => $loanSahan->id, 'amount_paid' => 10000.00, 'paid_date' => '2026-08-28 17:00:00'],
            [
                'principal_paid' => 10000.00,
                'interest_paid' => 0.00,
                'remaining_balance_after' => 90000.00,
                'repayment_type' => 'Payroll Auto-Deduction',
                'notes' => 'August 2026 Payroll Auto Deduction installment 3/12',
            ]
        );

        // 11. Dummy Salary Advances
        \Modules\Payroll\Models\SalaryAdvance::updateOrCreate(
            ['advance_number' => 'ADV-2026-001'],
            [
                'employee_id' => $empSahan->id,
                'amount' => 25000.00,
                'month' => 9,
                'year' => 2026,
                'reason' => 'Advance needed for child school admission and term book fees.',
                'status' => 'Pending',
                'requested_date' => '2026-09-02',
            ]
        );

        \Modules\Payroll\Models\SalaryAdvance::updateOrCreate(
            ['advance_number' => 'ADV-2026-002'],
            [
                'employee_id' => $empShimal->id,
                'amount' => 15000.00,
                'month' => 9,
                'year' => 2026,
                'reason' => 'Medical checkup and prescription costs for parents.',
                'status' => 'Approved',
                'requested_date' => '2026-09-01',
                'approved_at' => '2026-09-03 11:20:00',
                'approved_by_user_id' => $userSupuni->id,
            ]
        );

        \Modules\Payroll\Models\SalaryAdvance::updateOrCreate(
            ['advance_number' => 'ADV-2026-003'],
            [
                'employee_id' => $empAnjalie->id,
                'amount' => 30000.00,
                'month' => 8,
                'year' => 2026,
                'reason' => 'Vehicle minor repair expenses during outstation travel.',
                'status' => 'Deducted',
                'requested_date' => '2026-08-10',
                'approved_at' => '2026-08-12 09:30:00',
                'approved_by_user_id' => $userSuper->id,
            ]
        );

        // 12. Dummy Overtime Records
        $sahanHourly = round(190000 / 200, 2); // 950.00
        $shimalHourly = round(150000 / 200, 2); // 750.00

        \Modules\Payroll\Models\OvertimeRecord::updateOrCreate(
            ['ot_number' => 'OT-2026-001'],
            [
                'employee_id' => $empSahan->id,
                'ot_date' => '2026-09-05',
                'start_time' => '17:00',
                'end_time' => '20:30',
                'hours' => 3.5,
                'rate_multiplier_type' => 'Standard (1.5x)',
                'multiplier' => 1.5,
                'hourly_rate' => $sahanHourly,
                'estimated_amount' => round(3.5 * $sahanHourly * 1.5, 2), // 4987.50
                'reason' => 'Emergency production database migration and Kubernetes cluster patch release.',
                'status' => 'Approved',
                'approved_at' => '2026-09-06 10:00:00',
                'approved_by_user_id' => $userSupuni->id,
            ]
        );

        \Modules\Payroll\Models\OvertimeRecord::updateOrCreate(
            ['ot_number' => 'OT-2026-002'],
            [
                'employee_id' => $empSahan->id,
                'ot_date' => '2026-09-07',
                'start_time' => '17:00',
                'end_time' => '19:00',
                'hours' => 2.0,
                'rate_multiplier_type' => 'Standard (1.5x)',
                'multiplier' => 1.5,
                'hourly_rate' => $sahanHourly,
                'estimated_amount' => round(2.0 * $sahanHourly * 1.5, 2), // 2850.00
                'reason' => 'Post-deployment system health and monitoring metrics validation.',
                'status' => 'Pending',
            ]
        );

        \Modules\Payroll\Models\OvertimeRecord::updateOrCreate(
            ['ot_number' => 'OT-2026-003'],
            [
                'employee_id' => $empShimal->id,
                'ot_date' => '2026-09-01',
                'start_time' => '07:30',
                'end_time' => '09:00',
                'hours' => 1.5,
                'rate_multiplier_type' => 'Standard (1.5x)',
                'multiplier' => 1.5,
                'hourly_rate' => $shimalHourly,
                'estimated_amount' => round(1.5 * $shimalHourly * 1.5, 2), // 1687.50
                'reason' => 'Early morning critical bugfix resolution for banking client portal.',
                'status' => 'Approved',
                'approved_at' => '2026-09-02 09:30:00',
                'approved_by_user_id' => $userSupuni->id,
            ]
        );

        \Modules\Payroll\Models\OvertimeRecord::updateOrCreate(
            ['ot_number' => 'OT-2026-004'],
            [
                'employee_id' => $empShimal->id,
                'ot_date' => '2026-08-27',
                'start_time' => '10:00',
                'end_time' => '16:00',
                'hours' => 6.0,
                'rate_multiplier_type' => 'Holiday (2.5x)',
                'multiplier' => 2.5,
                'hourly_rate' => $shimalHourly,
                'estimated_amount' => round(6.0 * $shimalHourly * 2.5, 2), // 11250.00
                'reason' => 'Poya Day urgent client support and incident standby coverage.',
                'status' => 'Approved',
                'approved_at' => '2026-08-28 09:00:00',
                'approved_by_user_id' => $userSuper->id,
            ]
        );
    }
}

