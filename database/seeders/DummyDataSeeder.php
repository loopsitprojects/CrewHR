<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create User
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'supuni@loopshr.lk'],
            ['name' => 'Supuni Ratnayake', 'password' => bcrypt('password')]
        );

        // 2. Create Department & Designation
        $dept = \Modules\Employee\Models\Department::firstOrCreate(['name' => 'HR/Admin']);
        $role = \Modules\Employee\Models\Designation::firstOrCreate(['name' => 'HR Manager', 'department_id' => $dept->id]);

        // 3. Create Employee
        $employee = \Modules\Employee\Models\Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'employee_id_number' => 'EMP-001',
                'title' => 'Ms.',
                'department_id' => $dept->id,
                'designation_id' => $role->id,
                'joined_date' => '2020-01-01',
            ]
        );

        // 4. Create Leave Types
        $types = [
            ['name' => 'Annual Leave', 'default_quota' => 14, 'color_code' => 'blue', 'is_paid' => true],
            ['name' => 'Casual Leave', 'default_quota' => 7, 'color_code' => 'purple', 'is_paid' => true],
            ['name' => 'Medical Leave', 'default_quota' => 7, 'color_code' => 'green', 'is_paid' => true],
            ['name' => 'Short Leave', 'default_quota' => 2, 'color_code' => 'yellow', 'is_paid' => true],
            ['name' => 'Duty Leave', 'default_quota' => 30, 'color_code' => 'red', 'is_paid' => true],
            ['name' => 'Lieu Leave', 'default_quota' => 2, 'color_code' => 'teal', 'is_paid' => true],
        ];

        foreach ($types as $typeData) {
            $type = \Modules\Leave\Models\LeaveType::firstOrCreate(['name' => $typeData['name']], $typeData);
            
            // Assign Balances to Employee
            $allocated = $typeData['default_quota'];
            $used = rand(0, $allocated / 2); // Random used days
            \Modules\Leave\Models\EmployeeLeaveBalance::firstOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => date('Y')],
                ['allocated_days' => $allocated, 'used_days' => $used]
            );
        }

        // 5. Create Holidays (Current Month)
        $month = date('m');
        $year = date('Y');
        \Modules\Leave\Models\Holiday::firstOrCreate([
            'date' => "$year-$month-04",
            'title' => 'Company Anniversary',
            'type' => 'Company'
        ]);
        \Modules\Leave\Models\Holiday::firstOrCreate([
            'date' => "$year-$month-15",
            'title' => 'Poya Day',
            'type' => 'Poya'
        ]);

        // 6. Create some Leaves
        $annualType = \Modules\Leave\Models\LeaveType::where('name', 'Annual Leave')->first();
        \Modules\Leave\Models\LeaveRequest::firstOrCreate([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => "$year-$month-20",
            'end_date' => "$year-$month-20",
            'duration' => 1,
            'status' => 'Approved'
        ]);
    }
}
