<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $userRole = session('current_role', auth()->user()?->employee?->system_role ?? 'Employee');
        if (!in_array($userRole, ['Manager (Team Approvals)', 'HOD / Manager', 'HR Lead', 'Super (Admin)'])) {
            return redirect()->route('dashboard')->with('error', 'Access Denied: The Employees Module is restricted to Managers, HR Leads, and Admins.');
        }

        $search = $request->query('search');
        $deptId = $request->query('department_id');

        $query = Employee::with(['user', 'department', 'designation', 'reportingPerson.user']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('employee_id_number', 'like', "%{$search}%")
                ->orWhere('epf_registration_no', 'like', "%{$search}%");
            });
        }

        if ($deptId && $deptId !== 'all') {
            $query->where('department_id', $deptId);
        }

        $employees = $query->latest()->paginate(12)->withQueryString();
        $departments = Department::withCount('employees')->get();

        return view('employee::index', compact('employees', 'departments', 'search', 'deptId'));
    }

    private function authorizeAdminOrHr()
    {
        $userRole = session('current_role', auth()->user()?->employee?->system_role ?? 'Employee');
        if (!in_array($userRole, ['HR Lead', 'Super (Admin)', 'Super Admin'])) {
            abort(403, 'Access Denied: Only HR Lead and Super Admin accounts can create, edit, or delete employee profiles.');
        }
    }

    public function create()
    {
        $this->authorizeAdminOrHr();

        $departments = Department::all();
        $designations = Designation::all();
        $reportingPersons = Employee::with('user')->get();
        $allowanceTypes = \Modules\Payroll\Models\AllowanceType::where('status', 'Active')->orderBy('name')->get();
        $employee = new Employee();

        return view('employee::create', compact('departments', 'designations', 'reportingPersons', 'employee', 'allowanceTypes'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdminOrHr();
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'employee_id_number' => 'required|string|max:100|unique:employees,employee_id_number',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required',
            'custom_designation' => 'nullable|string|max:255',
            'profile_picture_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $designationId = $this->resolveDesignationId($request);

        DB::transaction(function () use ($request, $designationId) {
            $user = User::create([
                'name' => $request->full_name,
                'username' => $request->username ?? strtolower(str_replace(' ', '.', $request->full_name)) . rand(10, 99),
                'email' => $request->email,
                'password' => Hash::make($request->password ?? 'password123'),
            ]);

            $profilePicture = $request->profile_picture;
            if ($request->hasFile('profile_picture_file')) {
                $path = $request->file('profile_picture_file')->store('employees', 'public');
                $profilePicture = Storage::url($path);
            }

            $emp = Employee::create([
                'user_id' => $user->id,
                'employee_id_number' => $request->employee_id_number,
                'title' => $request->title ?? 'Mr.',
                'profile_picture' => $profilePicture,
                'date_of_birth' => $request->date_of_birth,
                'nic_passport' => $request->nic_passport,
                'phone_number' => $request->phone_number,
                'emergency_contact' => $request->emergency_contact,

                'department_id' => $request->department_id,
                'designation_id' => $designationId,
                'system_role' => $request->system_role ?? 'Employee',
                'reporting_person_id' => $request->reporting_person_id,
                'joined_date' => $request->joined_date ?? now()->toDateString(),
                'epf_registration_no' => $request->epf_registration_no,
                'job_category' => $request->job_category ?? 'Full Time (Permanent)',
                'staff_category' => $request->staff_category ?? 'Executive',
                'cost_classification' => $request->cost_classification ?? 'Direct',
                'payment_method' => $request->payment_method ?? 'Bank Transfer',

                'increment_amount' => $request->increment_amount ?? 0,
                'promotion_designation' => $request->promotion_designation,
                'promotion_date' => $request->promotion_date,

                'basic_salary' => $request->basic_salary ?? 0,
                'increments_basic' => $request->increments_basic ?? 0,
                'budget_allowance' => $request->budget_allowance ?? 0,
                'travelling_allowance' => $request->travelling_allowance ?? 0,
                'cost_of_living_allowance' => $request->cost_of_living_allowance ?? 0,
                'increments_allowance' => $request->increments_allowance ?? 0,
                'fixed_allowance' => $request->fixed_allowance ?? 0,
                'other_allowance' => $request->other_allowance ?? 0,
                'apit_tax' => $request->apit_tax ?? 0,

                'bank_name' => $request->bank_name,
                'bank_branch' => $request->bank_branch,
                'account_number' => $request->account_number,
                'account_holder_name' => $request->account_holder_name,

                'higher_education' => $request->higher_education,
                'professional_qualifications' => $request->professional_qualifications,
            ]);

            // Sync dynamic allowances
            if ($request->has('allowances') && is_array($request->allowances)) {
                foreach ($request->allowances as $item) {
                    if (!empty($item['allowance_type_id']) && (float)($item['amount'] ?? 0) > 0) {
                        \Modules\Payroll\Models\EmployeeAllowance::create([
                            'employee_id' => $emp->id,
                            'allowance_type_id' => $item['allowance_type_id'],
                            'amount' => (float)$item['amount'],
                        ]);
                    }
                }
            }

            // Auto-seed default leave balances for the new employee
            $leaveTypes = LeaveType::all();
            foreach ($leaveTypes as $type) {
                EmployeeLeaveBalance::firstOrCreate(
                    ['employee_id' => $emp->id, 'leave_type_id' => $type->id],
                    ['allocated' => $type->days, 'used' => 0, 'carried_forward' => 0]
                );
            }
        });

        return redirect()->route('employee.index')->with('success', 'Employee profile created successfully!');
    }

    public function show($id)
    {
        $employee = Employee::with([
            'user', 
            'department', 
            'designation', 
            'reportingPerson.user',
            'subordinates.user',
            'leaveBalances.leaveType',
            'employeeAllowances.allowanceType'
        ])->findOrFail($id);

        return view('employee::show', compact('employee'));
    }

    public function edit($id)
    {
        $this->authorizeAdminOrHr();

        $employee = Employee::with([
            'user', 
            'department', 
            'designation', 
            'reportingPerson.user',
            'leaveBalances.leaveType',
            'employeeAllowances.allowanceType'
        ])->findOrFail($id);

        $departments = Department::all();
        $designations = Designation::all();
        $reportingPersons = Employee::with('user')->where('id', '!=', $id)->get();
        $allowanceTypes = \Modules\Payroll\Models\AllowanceType::where('status', 'Active')->orderBy('name')->get();

        return view('employee::edit', compact('employee', 'departments', 'designations', 'reportingPersons', 'allowanceTypes'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdminOrHr();

        $employee = Employee::with('user')->findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($employee->user_id)],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'employee_id_number' => ['required', 'string', 'max:100', Rule::unique('employees', 'employee_id_number')->ignore($employee->id)],
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required',
            'custom_designation' => 'nullable|string|max:255',
            'profile_picture_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $designationId = $this->resolveDesignationId($request);

        DB::transaction(function () use ($request, $employee, $designationId) {
            // Update User record
            if ($employee->user) {
                $employee->user->update([
                    'name' => $request->full_name,
                    'username' => $request->username,
                    'email' => $request->email,
                ]);
            }

            // File upload fallback
            $profilePicture = $request->profile_picture ?? $employee->profile_picture;
            if ($request->hasFile('profile_picture_file')) {
                $path = $request->file('profile_picture_file')->store('employees', 'public');
                $profilePicture = Storage::url($path);
            }

            // Update Employee Profile
            $employee->update([
                'title' => $request->title,
                'profile_picture' => $profilePicture,
                'date_of_birth' => $request->date_of_birth,
                'nic_passport' => $request->nic_passport,
                'phone_number' => $request->phone_number,
                'emergency_contact' => $request->emergency_contact,

                'employee_id_number' => $request->employee_id_number,
                'department_id' => $request->department_id,
                'designation_id' => $designationId,
                'system_role' => $request->system_role ?? $employee->system_role,
                'reporting_person_id' => $request->reporting_person_id,
                'joined_date' => $request->joined_date,
                'epf_registration_no' => $request->epf_registration_no,
                'job_category' => $request->job_category ?? $employee->job_category,
                'staff_category' => $request->staff_category ?? ($employee->staff_category ?? 'Executive'),
                'cost_classification' => $request->cost_classification ?? ($employee->cost_classification ?? 'Direct'),
                'payment_method' => $request->payment_method ?? ($employee->payment_method ?? 'Bank Transfer'),

                'increment_amount' => $request->increment_amount ?? 0,
                'promotion_designation' => $request->promotion_designation,
                'promotion_date' => $request->promotion_date,

                'basic_salary' => $request->basic_salary ?? 0,
                'increments_basic' => $request->increments_basic ?? 0,
                'budget_allowance' => $request->budget_allowance ?? 0,
                'travelling_allowance' => $request->travelling_allowance ?? 0,
                'cost_of_living_allowance' => $request->cost_of_living_allowance ?? 0,
                'increments_allowance' => $request->increments_allowance ?? 0,
                'fixed_allowance' => $request->fixed_allowance ?? 0,
                'other_allowance' => $request->other_allowance ?? 0,
                'apit_tax' => $request->apit_tax ?? 0,

                'bank_name' => $request->bank_name,
                'bank_branch' => $request->bank_branch,
                'account_number' => $request->account_number,
                'account_holder_name' => $request->account_holder_name,

                'higher_education' => $request->higher_education,
                'professional_qualifications' => $request->professional_qualifications,
            ]);

            // Update Leave Balances if passed in request
            if ($request->has('leave_balances') && is_array($request->leave_balances)) {
                foreach ($request->leave_balances as $balanceId => $data) {
                    $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)->find($balanceId);
                    if ($balance) {
                        $allocated = isset($data['allocated']) ? (float)$data['allocated'] : $balance->allocated;
                        $used = isset($data['used']) ? (float)$data['used'] : $balance->used;
                        $balance->update([
                            'allocated' => $allocated,
                            'used' => $used,
                        ]);
                    }
                }
            }

            // Sync dynamic allowances
            if ($request->has('allowances')) {
                \Modules\Payroll\Models\EmployeeAllowance::where('employee_id', $employee->id)->delete();
                if (is_array($request->allowances)) {
                    foreach ($request->allowances as $item) {
                        if (!empty($item['allowance_type_id']) && (float)($item['amount'] ?? 0) > 0) {
                            \Modules\Payroll\Models\EmployeeAllowance::create([
                                'employee_id' => $employee->id,
                                'allowance_type_id' => $item['allowance_type_id'],
                                'amount' => (float)$item['amount'],
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()->route('employee.index')->with('success', 'Employee profile updated successfully!');
    }

    public function destroy($id)
    {
        $this->authorizeAdminOrHr();

        $employee = Employee::findOrFail($id);

        DB::transaction(function () use ($employee) {
            $user = $employee->user;
            $employee->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('employee.index')->with('success', 'Employee profile and associated user account deleted successfully!');
    }

    private function resolveDesignationId(Request $request): int
    {
        $custom = trim($request->input('custom_designation', ''));
        if ($request->input('designation_id') === 'custom' || !empty($custom)) {
            if (!empty($custom)) {
                $desig = Designation::firstOrCreate(
                    ['name' => $custom],
                    ['department_id' => $request->department_id]
                );
                return $desig->id;
            }
        }

        if (is_numeric($request->designation_id)) {
            $existing = Designation::find($request->designation_id);
            if ($existing) return $existing->id;
        }

        return Designation::firstOrCreate(['name' => 'Staff'], ['department_id' => $request->department_id])->id;
    }
}
