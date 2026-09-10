<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\Holiday;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Leave\Services\LeavePolicyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LeaveDashboardController extends Controller
{
    protected LeavePolicyService $leavePolicyService;

    public function __construct(LeavePolicyService $leavePolicyService)
    {
        $this->leavePolicyService = $leavePolicyService;
    }

    public function index(Request $request)
    {
        $timezone = 'Asia/Colombo';
        $now = Carbon::now($timezone);

        $year = (int) $request->get('year', $now->year);
        $month = (int) $request->get('month', $now->month);

        if ($month < 1 || $month > 12) $month = $now->month;
        if ($year < 2000 || $year > 2100) $year = $now->year;

        $selectedDate = Carbon::createFromDate($year, $month, 1, $timezone);

        $employee = $this->getActiveEmployee();
        
        // Ensure pro-rata balances exist for current employee
        if ($employee) {
            $this->ensureEmployeeLeaveBalances($employee, $year);
        }

        $leaveBalances = EmployeeLeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id ?? 1)
            ->whereHas('leaveType', function($q) {
                $q->where('is_active', true);
            })
            ->get();

        $myLeaveHistory = LeaveRequest::with(['leaveType', 'coveringEmployee.user'])
            ->where('employee_id', $employee->id ?? 1)
            ->latest()
            ->get();

        // Calculate short leaves used in the selected calendar month (2 slots/month)
        $monthlyShortLeavesUsed = LeaveRequest::where('employee_id', $employee->id ?? 1)
            ->where(function($q) {
                $q->where('is_short_leave', true)
                  ->orWhereHas('leaveType', fn($t) => $t->where('code', 'SHORT'));
            })
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->whereNotIn('status', ['Rejected', 'Canceled'])
            ->count();

        $activeRole = $this->getActiveRole();
        $activeEmpId = $employee?->id;

        $pendingQuery = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType', 'coveringEmployee.user']);

        if ($activeRole === 'Super (Admin)') {
            $pendingQuery->whereIn('status', ['Pending', 'Pending Covering Approval', 'Pending Manager Approval', 'Manager Approved (Pending HR)', 'Step 2: Pending HR Admin']);
        } elseif ($activeRole === 'HR Lead') {
            $pendingQuery->where(function ($q) {
                $q->where('hr_status', 'Pending')->where('manager_status', 'Approved')
                  ->orWhere('status', 'Step 2: Pending HR Admin');
            });
        } elseif (in_array($activeRole, ['Manager (Team Approvals)', 'HOD / Manager'])) {
            $pendingQuery->where('manager_status', 'Pending')
                ->where('covering_status', 'Approved')
                ->where(function ($q) use ($activeEmpId, $employee) {
                    $q->where('manager_employee_id', $activeEmpId)
                      ->orWhereHas('employee', function ($eq) use ($activeEmpId, $employee) {
                          $eq->where('reporting_person_id', $activeEmpId);
                          if ($employee && $employee->department_id) {
                              $eq->orWhere('department_id', $employee->department_id);
                          }
                      });
                });
        } else {
            $pendingQuery->where('employee_id', $activeEmpId ?? 0)
                         ->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled']);
        }

        $pendingRequests = $pendingQuery->latest()->get();

        // Fetch Staff currently on approved leave TODAY (Actual date)
        $todayStr = $now->format('Y-m-d');
        $todaysActiveLeaves = LeaveRequest::with(['employee.user', 'leaveType'])
            ->where('start_date', '<=', $todayStr)
            ->where('end_date', '>=', $todayStr)
            ->where(function ($q) {
                $q->where('status', 'Approved')
                  ->orWhere('manager_status', 'Approved');
            })
            ->get();

        // Fetch Holidays for selected year/month
        $monthHolidays = Holiday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $holidaysGrouped = $monthHolidays->groupBy(function ($h) {
            return Carbon::parse($h->date)->format('Y-m-d');
        });

        // Fetch Leave Requests for selected year/month
        $monthLeaves = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType'])
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('start_date', $year)->whereMonth('start_date', $month)
                  ->orWhere(function ($q2) use ($year, $month) {
                      $q2->whereYear('end_date', $year)->whereMonth('end_date', $month);
                  });
            })
            ->get();

        // Build Sri Lanka Calendar Month Grid
        $daysInMonth = $selectedDate->daysInMonth;
        $startDayOfWeek = $selectedDate->copy()->firstOfMonth()->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
        $endDayOfWeek = $selectedDate->copy()->lastOfMonth()->dayOfWeekIso;

        $calendarDays = [];

        // Leading empty slots for padding
        for ($i = 1; $i < $startDayOfWeek; $i++) {
            $calendarDays[] = [
                'day' => null,
                'date' => null,
                'is_current_month' => false,
                'is_weekend' => false,
                'is_today' => false,
                'holiday' => null,
                'holidays' => collect(),
                'leaves' => collect(),
                'is_poya' => false,
                'pbm_flags' => [],
            ];
        }

        // Actual days in month
        $todayStr = $now->format('Y-m-d');
        $allEmployees = Employee::with('user')->get();
        $departments = Department::all();

        $sampleBirthdays = [
            12 => ['Shimal Perera'],
            21 => ['Arosh Wickramasinghe'],
            28 => ['Supuni Fernando']
        ];

        $satOff = DB::table('settings')->where('key', 'weekend_saturday_off')->value('value') ?? '1';
        $sunOff = DB::table('settings')->where('key', 'weekend_sunday_off')->value('value') ?? '1';

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentCarbon = $selectedDate->copy()->day($d);
            $dateStr = $currentCarbon->format('Y-m-d');
            $isSat = $currentCarbon->isSaturday();
            $isSun = $currentCarbon->isSunday();
            $isWeekend = ($isSat && $satOff === '1') || ($isSun && $sunOff === '1');
            $isToday = ($dateStr === $todayStr);

            // Filter leaves spanning this day
            $dayLeaves = $monthLeaves->filter(function ($leave) use ($dateStr) {
                return $dateStr >= $leave->start_date && $dateStr <= $leave->end_date;
            });

            // Birthdays on this day
            $dayBirthdays = $allEmployees->filter(function ($emp) use ($currentCarbon) {
                if (!$emp->date_of_birth) return false;
                $dob = Carbon::parse($emp->date_of_birth);
                return $dob->month === $currentCarbon->month && $dob->day === $currentCarbon->day;
            })->map(function($emp) {
                return $emp->user->name ?? 'Employee';
            })->toArray();

            if (empty($dayBirthdays) && isset($sampleBirthdays[$d])) {
                $dayBirthdays = $sampleBirthdays[$d];
            }

            $dayHolidays = $holidaysGrouped->get($dateStr, collect());
            $firstHoliday = $dayHolidays->first();

            $isPoya = $firstHoliday ? str_contains(strtolower($firstHoliday->title), 'poya') : false;

            $pbmFlags = [];
            if ($firstHoliday && $firstHoliday->category) {
                if (str_contains($firstHoliday->category, 'Public')) $pbmFlags[] = 'P';
                if (str_contains($firstHoliday->category, 'Bank')) $pbmFlags[] = 'B';
                if (str_contains($firstHoliday->category, 'Mercantile')) $pbmFlags[] = 'M';
            }

            $calendarDays[] = [
                'day' => $d,
                'date' => $dateStr,
                'formatted_date' => $currentCarbon->format('l, d F Y'),
                'is_current_month' => true,
                'is_weekend' => $isWeekend,
                'is_today' => $isToday,
                'holiday' => $firstHoliday,
                'holidays' => $dayHolidays,
                'leaves' => $dayLeaves,
                'birthdays' => $dayBirthdays,
                'is_poya' => $isPoya,
                'pbm_flags' => $pbmFlags,
            ];
        }

        // Trailing empty slots padding
        $paddingEnd = 7 - $endDayOfWeek;
        for ($i = 0; $i < $paddingEnd; $i++) {
            $calendarDays[] = [
                'day' => null,
                'date' => null,
                'is_current_month' => false,
                'is_weekend' => false,
                'is_today' => false,
                'holiday' => null,
                'holidays' => collect(),
                'leaves' => collect(),
                'is_poya' => false,
                'pbm_flags' => [],
            ];
        }

        $holidaysCount = $monthHolidays->count();

        // Prev & Next Month dates
        $prevMonthDate = $selectedDate->copy()->subMonth();
        $nextMonthDate = $selectedDate->copy()->addMonth();

        // Month Names for Quick Picker
        $monthsList = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $yearsList = range(2024, 2030);

        $upcomingBirthdays = Employee::with(['user', 'department', 'designation'])
            ->whereNotNull('date_of_birth')
            ->get()
            ->map(function ($emp) {
                $dob = Carbon::parse($emp->date_of_birth);
                $nextBday = $dob->copy()->year(now()->year);
                if ($nextBday->isPast() && !$nextBday->isToday()) {
                    $nextBday->addYear();
                }
                $daysUntil = (int)now()->startOfDay()->diffInDays($nextBday->startOfDay(), false);
                return (object)[
                    'id' => $emp->id,
                    'name' => ($emp->title ? $emp->title . ' ' : '') . ($emp->user->name ?? 'Employee'),
                    'username' => $emp->user->username ?? '',
                    'department' => $emp->department->name ?? 'General',
                    'designation' => $emp->designation->name ?? 'Staff',
                    'profile_picture' => $emp->profile_picture,
                    'dob_formatted' => $dob->format('M d') . ' (' . $dob->format('Y') . ')',
                    'month_day' => $dob->format('F d'),
                    'age' => $dob->age,
                    'days_until' => $daysUntil,
                    'is_today' => $dob->format('m-d') === now()->format('m-d'),
                ];
            })->sortBy('days_until')->values();

        $todayCell = collect($calendarDays)->firstWhere('is_today', true) ?? collect($calendarDays)->firstWhere('is_current_month', true);

        // List all holidays as Y-m-d strings for front-end dynamic net working days calculation
        $allHolidaysList = Holiday::pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->unique()->values()->toArray();

        return view('pages.leave_dashboard', compact(
            'employee', 
            'leaveBalances', 
            'myLeaveHistory',
            'monthlyShortLeavesUsed',
            'pendingRequests', 
            'todaysActiveLeaves',
            'monthHolidays', 
            'departments',
            'calendarDays',
            'todayCell',
            'selectedDate',
            'year',
            'month',
            'holidaysCount',
            'prevMonthDate',
            'nextMonthDate',
            'monthsList',
            'yearsList',
            'allEmployees',
            'upcomingBirthdays',
            'allHolidaysList'
        ));
    }

    public function storeLeaveRequest(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'medical_certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5012',
            'project_client_name' => 'nullable|string|max:255',
            'covering_employee_id' => 'nullable|exists:employees,id',
        ]);

        $employee = $this->getActiveEmployee();
        if (!$employee) {
            return redirect()->back()->withErrors(['error' => 'Employee profile not found for active user.']);
        }
        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        $typeCode = strtoupper($leaveType->code);

        // Medical Certificate File Upload
        $certificatePath = null;
        if ($request->hasFile('medical_certificate_file')) {
            $path = $request->file('medical_certificate_file')->store('medical_certificates', 'public');
            $certificatePath = Storage::url($path);
        }

        $data = array_merge($request->all(), ['medical_certificate_path' => $certificatePath]);

        // Calculate Days & Duration
        $isHalfDay = (bool)$request->input('is_half_day', false);
        $isShortLeave = ($typeCode === 'SHORT' || (bool)$request->input('is_short_leave', false));

        if ($isHalfDay) {
            $workingDays = 0.5;
        } elseif ($isShortLeave) {
            $workingDays = 0.2; // 1.5 hour break
        } else {
            // Calculate net working days (excluding weekends and public/company holidays)
            $workingDays = $this->leavePolicyService->calculateWorkingDays($request->start_date, $request->end_date);

            if ($workingDays <= 0) {
                return redirect()->back()->withErrors([
                    'start_date' => 'The selected date range only falls on weekends or public holidays. No working days need to be deducted.'
                ]);
            }
        }

        // Validate against Leave Brief rules using LeavePolicyService
        $this->leavePolicyService->validateLeaveApplication($employee, $leaveType, $data, $workingDays);

        // Check if employee has sufficient available balance (Short Leave and Duty Leave are exempt from annual quota pool deductions)
        $balance = EmployeeLeaveBalance::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id],
            ['allocated' => $leaveType->days, 'used' => 0, 'carried_forward' => 0]
        );

        $available = ($balance->allocated + $balance->carried_forward) - $balance->used;
        if ($workingDays > $available && !in_array($typeCode, ['DUTY', 'LIEU', 'SHORT'])) {
            return redirect()->back()->withErrors([
                'leave_type_id' => "Insufficient leave balance. You have {$available} days available for {$leaveType->name}."
            ]);
        }

        // Determine manager & covering person (informational only - no covering approval required)
        $coveringId = $request->covering_employee_id ?: null;
        $coveringStatus = 'Approved';
        
        $managerId = $employee->reporting_person_id;
        if (!$managerId && $employee->department_id) {
            $managerEmp = Employee::where('department_id', $employee->department_id)
                ->where('id', '!=', $employee->id)
                ->whereIn('system_role', ['Manager (Team Approvals)', 'HOD / Manager'])
                ->first();
            $managerId = $managerEmp?->id;
        }

        $initialStatus = 'Pending Manager Approval';

        DB::transaction(function () use ($request, $employee, $leaveType, $workingDays, $certificatePath, $isHalfDay, $isShortLeave, $coveringId, $coveringStatus, $managerId, $initialStatus, $balance, $typeCode) {
            $leaveReq = LeaveRequest::create([
                'req_number' => 'REQ-' . rand(1100, 9999),
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'duration' => $workingDays,
                'status' => $initialStatus,
                'reason' => $request->reason,
                'covering_employee_id' => $coveringId,
                'covering_status' => $coveringStatus,
                'manager_employee_id' => $managerId,
                'manager_status' => 'Pending',
                'hr_status' => 'Pending',
                'applied_at' => Carbon::now('Asia/Colombo')->toDateString(),
                'is_half_day' => $isHalfDay,
                'half_day_slot' => $isHalfDay ? $request->input('half_day_slot') : null,
                'is_short_leave' => $isShortLeave,
                'short_leave_slot' => $isShortLeave ? $request->input('short_leave_slot') : null,
                'medical_certificate_path' => $certificatePath,
                'project_client_name' => $request->input('project_client_name'),
                'is_refunded' => false,
            ]);

            // Immediately deduct days from balance only for standard quota-deductible leaves
            if (!in_array($typeCode, ['SHORT', 'DUTY'])) {
                $balance->increment('used', $workingDays);
            }

            // Dispatch HR Notifications
            \App\Services\NotificationService::notifyLeaveApplied($leaveReq);
        });

        return redirect()->back()->with('success', 'Leave request submitted successfully!');
    }

    public function storeCompanyLeave(Request $request)
    {
        $role = $this->getActiveRole();
        if (!in_array($role, ['HR Lead', 'Super (Admin)'])) {
            return redirect()->back()->with('error', 'Unauthorized: Only HR Admin or Super Admin can add company holidays.');
        }

        $request->validate([
            'title' => 'required|string',
            'date' => 'required|date',
            'category' => 'required|string',
            'description' => 'nullable|string',
        ]);

        Holiday::create([
            'title' => $request->title,
            'date' => $request->date,
            'type' => 'Company',
            'category' => $request->category,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Company holiday added successfully!');
    }

    private function getActiveEmployee(): ?Employee
    {
        $user = auth()->user();
        if ($user) {
            $emp = Employee::where('user_id', $user->id)->first();
            if ($emp) return $emp;
        }

        return Employee::where('employee_id_number', 'REQ-1092')->first() ?? Employee::first();
    }

    private function getActiveRole(): string
    {
        $user = auth()->user();
        if ($user) {
            $emp = Employee::where('user_id', $user->id)->first();
            if ($emp) {
                return match($emp->system_role ?? '') {
                    'HR Lead' => 'HR Lead',
                    'Manager (Team Approvals)' => 'HOD / Manager',
                    'Employee' => 'Employee',
                    default => 'Super (Admin)',
                };
            }
        }
        return session('current_role', 'Super (Admin)');
    }

    /**
     * Ensure Employee Leave Balances exist following LOOPS HR Leave Brief pro-rata rules
     */
    private function ensureEmployeeLeaveBalances(Employee $employee, int $year): void
    {
        $leaveTypes = LeaveType::all();

        foreach ($leaveTypes as $lt) {
            $code = strtoupper($lt->code);

            if ($code === 'ANNUAL') {
                $allocated = $this->leavePolicyService->calculateAnnualLeaveQuota($employee, $year);
            } elseif ($code === 'CASUAL') {
                $allocated = $this->leavePolicyService->calculateCasualLeaveQuota($employee, $year);
            } else {
                $allocated = $lt->days;
            }

            EmployeeLeaveBalance::firstOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $lt->id, 'year' => $year],
                ['allocated' => $allocated, 'used' => 0, 'carried_forward' => 0]
            );
        }
    }
}
