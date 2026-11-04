<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LieuLeaveClaim;
use Modules\Leave\Models\Holiday;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Leave\Services\LeavePolicyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $startOfMonth = $selectedDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $selectedDate->copy()->endOfMonth()->toDateString();

        $monthLeaves = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType'])
            ->whereNotIn('status', ['Rejected', 'Canceled', 'Cancelled'])
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->where('start_date', '<=', $endOfMonth)
                  ->where('end_date', '>=', $startOfMonth);
            })
            ->orderBy('start_date')
            ->get();

        // Build all on leave list for the Who's on Leave view
        $allMonthLeaves = $monthLeaves->map(function ($lv) use ($todayStr) {
            $startDate = Carbon::parse($lv->start_date);
            $endDate = Carbon::parse($lv->end_date);
            $isToday = ($todayStr >= $lv->start_date && $todayStr <= $lv->end_date);
            $isUpcoming = ($lv->start_date > $todayStr);
            $isPast = ($lv->end_date < $todayStr);

            $formattedDates = $startDate->format('M d');
            if ($lv->start_date !== $lv->end_date) {
                $formattedDates .= ' – ' . $endDate->format('M d');
            }

            $durationLabel = (float)$lv->duration === 1.0 ? '1 Day' : ((float)$lv->duration . ' Days');
            if ($lv->leaveType?->code === 'NIGHT_SHIFT_RECOVERY') {
                $durationLabel = match($lv->concession_category) {
                    'report_10am' => 'Night Shift (Report 10 AM)',
                    'report_11am' => 'Night Shift (Report 11 AM)',
                    'half_day_off' => 'Night Shift (Half Day Off)',
                    'full_day_off' => 'Night Shift (Full Day Off)',
                    default => 'Night Shift Recovery'
                };
            } elseif ($lv->is_half_day) {
                $durationLabel = 'Half Day (' . ($lv->half_day_slot ?: 'Slot') . ')';
            } elseif ($lv->is_short_leave) {
                $durationLabel = 'Short Leave (' . ($lv->short_leave_slot ?: 'Slot') . ')';
            }

            return [
                'id' => $lv->id,
                'employee_name' => $lv->employee?->user?->name ?? 'Employee',
                'employee_id' => $lv->employee?->employee_id_number ?? '',
                'department' => $lv->employee?->department?->name ?? 'General',
                'leave_type_name' => $lv->leaveType?->name ?? 'Leave',
                'leave_type_code' => strtoupper($lv->leaveType?->code ?? ''),
                'start_date' => $lv->start_date,
                'end_date' => $lv->end_date,
                'duration' => (float)$lv->duration,
                'duration_label' => $durationLabel,
                'formatted_dates' => $formattedDates,
                'is_half_day' => (bool)$lv->is_half_day,
                'half_day_slot' => $lv->half_day_slot,
                'is_short_leave' => (bool)$lv->is_short_leave,
                'short_leave_slot' => $lv->short_leave_slot,
                'is_today' => $isToday,
                'is_upcoming' => $isUpcoming,
                'is_past' => $isPast,
                'status' => $lv->status ?? 'Approved',
                'reason' => $lv->reason,
            ];
        })->sort(function ($a, $b) {
            if ($a['is_today'] && !$b['is_today']) return -1;
            if (!$a['is_today'] && $b['is_today']) return 1;
            if ($a['is_upcoming'] && !$b['is_upcoming']) return -1;
            if (!$a['is_upcoming'] && $b['is_upcoming']) return 1;
            return strcmp($a['start_date'], $b['start_date']);
        })->values();

        // Build Sri Lanka Calendar Month Grid
        $daysInMonth = $selectedDate->daysInMonth;
        $startDayOfWeek = $selectedDate->copy()->firstOfMonth()->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
        $endDayOfWeek = $selectedDate->copy()->lastOfMonth()->dayOfWeekIso;

        $calendarDays = [];

        // Leading slots: dates from previous month
        $prevMonthDate = $selectedDate->copy()->subMonth();
        $daysInPrevMonth = $prevMonthDate->daysInMonth;
        for ($i = 1; $i < $startDayOfWeek; $i++) {
            $prevDayNum = $daysInPrevMonth - ($startDayOfWeek - 1) + $i;
            $prevCarbon = $prevMonthDate->copy()->day($prevDayNum);
            $calendarDays[] = [
                'day' => $prevDayNum,
                'date' => $prevCarbon->format('Y-m-d'),
                'formatted_date' => $prevCarbon->format('l, d F Y'),
                'is_current_month' => false,
                'is_weekend' => $prevCarbon->isSaturday() || $prevCarbon->isSunday(),
                'is_today' => false,
                'holiday' => null,
                'holidays' => collect()->values(),
                'leaves' => collect()->values(),
                'birthdays' => [],
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

            // Filter leaves spanning this day and reindex with values()
            $dayLeaves = $monthLeaves->filter(function ($leave) use ($dateStr) {
                return $dateStr >= $leave->start_date && $dateStr <= $leave->end_date;
            })->values();

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

            $dayHolidays = $holidaysGrouped->get($dateStr, collect())->values();
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

        // Trailing slots: dates from next month
        $nextMonthDate = $selectedDate->copy()->addMonth();
        $paddingEnd = 7 - $endDayOfWeek;
        for ($i = 1; $i <= $paddingEnd; $i++) {
            $nextCarbon = $nextMonthDate->copy()->day($i);
            $calendarDays[] = [
                'day' => $i,
                'date' => $nextCarbon->format('Y-m-d'),
                'formatted_date' => $nextCarbon->format('l, d F Y'),
                'is_current_month' => false,
                'is_weekend' => $nextCarbon->isSaturday() || $nextCarbon->isSunday(),
                'is_today' => false,
                'holiday' => null,
                'holidays' => collect()->values(),
                'leaves' => collect()->values(),
                'birthdays' => [],
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

        // Determine Reporting Manager Name
        $managerEmp = null;
        if ($employee && $employee->reporting_person_id) {
            $managerEmp = Employee::with('user')->find($employee->reporting_person_id);
        }
        if (!$managerEmp && $employee && $employee->department_id) {
            $managerEmp = Employee::with('user')->where('department_id', $employee->department_id)
                ->where('id', '!=', $employee->id)
                ->whereIn('system_role', ['Manager (Team Approvals)', 'HOD / Manager'])
                ->first();
        }
        $managerName = $managerEmp?->user?->name ?? 'Nadeesha Perera';

        // List all holidays as Y-m-d strings for front-end dynamic net working days calculation
        $allHolidaysList = Holiday::pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->unique()->values()->toArray();

        // Build added lieu days for current employee (automatically added when weekend or holiday work claims are approved)
        $lieuDaysList = [];
        if ($employee) {
            $claims = LieuLeaveClaim::with('leaveRequest')
                ->where('employee_id', $employee->id)
                ->orderByDesc('worked_date')
                ->get();

            if ($claims->isNotEmpty()) {
                foreach ($claims as $claim) {
                    $workedDate = Carbon::parse($claim->worked_date);
                    $approvedDate = $claim->approved_at ? Carbon::parse($claim->approved_at) : $workedDate;
                    $expiryDate = $claim->expires_at ? Carbon::parse($claim->expires_at) : $approvedDate->copy()->addDays(30);
                    $daysLeft = (int) max(0, $now->diffInDays($expiryDate, false));
                    $isExpired = $now->greaterThan($expiryDate);

                    if ($claim->is_used) {
                        $status = 'used';
                        $subtext = 'Taken ' . ($claim->leaveRequest ? Carbon::parse($claim->leaveRequest->start_date)->format('D j M') : 'used');
                    } elseif ($claim->status === 'Pending Manager Approval') {
                        $status = 'pending';
                        $subtext = 'Pending Approval';
                    } elseif ($claim->status === 'Rejected') {
                        $status = 'rejected';
                        $subtext = 'Claim Rejected';
                    } elseif ($isExpired) {
                        $status = 'expired';
                        $subtext = 'Expired ' . $expiryDate->format('D j M');
                    } else {
                        $status = 'available';
                        $subtext = null;
                    }

                    $durationLabel = $claim->duration_type === 'half_day' ? 'Half day (4h)' : 'Full day (8h)';

                    $lieuDaysList[] = [
                        'id' => $claim->id,
                        'type' => $durationLabel,
                        'worked' => 'worked ' . $workedDate->format('l j M'),
                        'status' => $status,
                        'subtext' => $subtext,
                        'expiry_date' => $expiryDate->format('D j M'),
                        'code' => $claim->claim_number,
                        'days_left' => $status === 'available' ? $daysLeft : 0,
                    ];
                }
            } else {
                // Fallback to legacy overtime_records if no claims created yet
                $otRecords = DB::table('overtime_records')
                    ->where('employee_id', $employee->id)
                    ->where('status', 'Approved')
                    ->where(function($q) {
                        $q->where('rate_multiplier_type', 'like', '%Holiday%')
                          ->orWhere('rate_multiplier_type', 'like', '%Weekend%')
                          ->orWhereRaw('DAYOFWEEK(ot_date) IN (1, 7)');
                    })
                    ->orderByDesc('ot_date')
                    ->get();

                $lieuLeavesTaken = LeaveRequest::where('employee_id', $employee->id)
                    ->whereHas('leaveType', fn($t) => $t->where('code', 'LIEU'))
                    ->whereNotIn('status', ['Rejected', 'Canceled', 'Cancelled'])
                    ->orderBy('start_date')
                    ->get();

                $usedIndex = 0;
                foreach ($otRecords as $ot) {
                    $workedDate = Carbon::parse($ot->ot_date);
                    $approvedDate = $ot->approved_at ? Carbon::parse($ot->approved_at) : $workedDate;
                    $expiryDate = $approvedDate->copy()->addDays(30);
                    $daysLeft = (int) max(0, $now->diffInDays($expiryDate, false));
                    $isExpired = $now->greaterThan($expiryDate);

                    $usedLeave = $lieuLeavesTaken->get($usedIndex);
                    if ($usedLeave) {
                        $status = 'used';
                        $subtext = 'Taken ' . Carbon::parse($usedLeave->start_date)->format('D j M');
                        $usedIndex++;
                    } elseif ($isExpired) {
                        $status = 'expired';
                        $subtext = 'Expired ' . $expiryDate->format('D j M');
                    } else {
                        $status = 'available';
                        $subtext = null;
                    }

                    $lieuDaysList[] = [
                        'type' => ((float)$ot->hours >= 6) ? 'Full day (8h)' : 'Half day (4h)',
                        'worked' => 'worked ' . $workedDate->format('l j M'),
                        'status' => $status,
                        'subtext' => $subtext,
                        'expiry_date' => $expiryDate->format('D j M'),
                        'code' => $ot->ot_number ?? ('LI-' . $ot->id),
                        'days_left' => $status === 'available' ? $daysLeft : 0,
                    ];
                }
            }
        }

        $allLeaveTypes = LeaveType::orderBy('id')->get();

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
            'allMonthLeaves',
            'upcomingBirthdays',
            'allHolidaysList',
            'managerName',
            'lieuDaysList',
            'allLeaveTypes'
        ));
    }

    public function storeLeaveRequest(Request $request)
    {
        // Handle Handover Notes and Additional Notes
        $handoverNotes = $request->input('handover_notes');
        $anythingElse = $request->input('anything_else') ?? $request->input('reason');
        if (!$request->filled('reason') && $request->filled('handover_notes')) {
            $request->merge(['reason' => $handoverNotes]);
        } elseif ($request->filled('handover_notes') && $request->filled('anything_else')) {
            $request->merge(['reason' => "Handover Notes: {$handoverNotes}\n\nAdditional Notes: {$anythingElse}"]);
        } elseif (!$request->filled('reason')) {
            $request->merge(['reason' => 'Leave applied']);
        }

        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'handover_notes' => 'nullable|string',
            'anything_else' => 'nullable|string',
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
        $category = $request->input('concession_category');
        $signOutTime = $request->input('night_sign_out_time');
        $reportTime = $request->input('night_report_time');

        if ($typeCode === 'NIGHT_SHIFT_RECOVERY') {
            if ($category === 'report_10am') {
                $workingDays = 0.0;
                $isHalfDay = false;
                $reportTime = '10:00:00';
            } elseif ($category === 'report_11am') {
                $workingDays = 0.0;
                $isHalfDay = false;
                $reportTime = '11:00:00';
            } elseif ($category === 'half_day_off') {
                $workingDays = 0.5;
                $isHalfDay = true;
                $reportTime = null;
            } elseif ($category === 'full_day_off') {
                $workingDays = 1.0;
                $isHalfDay = false;
                $reportTime = null;
            } else {
                if ($signOutTime) {
                    $timeParts = explode(':', $signOutTime);
                    $hour = (int)$timeParts[0];
                    $min = isset($timeParts[1]) ? (int)$timeParts[1] : 0;
                    $mins = $hour * 60 + $min;

                    if ($mins >= 1260 && $mins <= 1350) { // 21:00 to 22:30
                        $category = 'report_10am';
                        $reportTime = '10:00:00';
                        $workingDays = 0.0;
                        $isHalfDay = false;
                    } elseif ($mins > 1350 || $mins === 0) { // 22:31 to midnight
                        $category = 'report_11am';
                        $reportTime = '11:00:00';
                        $workingDays = 0.0;
                        $isHalfDay = false;
                    } elseif ($mins > 0 && $mins <= 180) { // 00:01 to 03:00
                        $category = 'half_day_off';
                        $reportTime = null;
                        $workingDays = 0.5;
                        $isHalfDay = true;
                    } elseif ($mins > 180 && $mins < 720) { // Beyond 03:00
                        $category = 'full_day_off';
                        $reportTime = null;
                        $workingDays = 1.0;
                        $isHalfDay = false;
                    } else {
                        $category = 'report_10am';
                        $reportTime = '10:00:00';
                        $workingDays = 0.0;
                        $isHalfDay = false;
                    }
                } else {
                    $category = 'report_10am';
                    $reportTime = '10:00:00';
                    $workingDays = 0.0;
                    $isHalfDay = false;
                }
            }
        } elseif ($isHalfDay) {
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

        // Check if employee has sufficient available balance (Short Leave, Duty Leave and Night Shift Recovery are exempt from annual quota pool deductions)
        $balance = EmployeeLeaveBalance::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id],
            ['allocated' => $leaveType->days, 'used' => 0, 'carried_forward' => 0]
        );

        $available = ($balance->allocated + $balance->carried_forward) - $balance->used;
        if ($workingDays > $available && !in_array($typeCode, ['DUTY', 'SHORT', 'NIGHT_SHIFT_RECOVERY'])) {
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

        DB::transaction(function () use ($request, $employee, $leaveType, $workingDays, $certificatePath, $isHalfDay, $isShortLeave, $coveringId, $coveringStatus, $managerId, $initialStatus, $balance, $typeCode, $signOutTime, $reportTime, $category) {
            $leaveReq = LeaveRequest::create([
                'req_number' => 'REQ-' . rand(1100, 9999),
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date ?: $request->start_date,
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
                'half_day_slot' => $isHalfDay ? ($request->input('half_day_slot') ?: 'Morning') : null,
                'is_short_leave' => $isShortLeave,
                'short_leave_slot' => $isShortLeave ? $request->input('short_leave_slot') : null,
                'medical_certificate_path' => $certificatePath,
                'project_client_name' => $request->input('project_client_name'),
                'night_sign_out_time' => $signOutTime,
                'night_report_time' => $reportTime,
                'concession_category' => $category,
                'is_refunded' => false,
            ]);

            // Immediately deduct days from balance only for standard quota-deductible leaves
            if (!in_array($typeCode, ['SHORT', 'DUTY', 'NIGHT_SHIFT_RECOVERY'])) {
                $balance->increment('used', $workingDays);
            }

            // If Lieu Leave is applied, link the oldest active approved claim
            if ($typeCode === 'LIEU') {
                $availableClaim = LieuLeaveClaim::where('employee_id', $employee->id)
                    ->where('status', 'Approved')
                    ->where('is_used', false)
                    ->orderBy('worked_date')
                    ->first();
                if ($availableClaim) {
                    $availableClaim->update([
                        'is_used' => true,
                        'leave_request_id' => $leaveReq->id,
                    ]);
                }
            }

            // Dispatch HR Notifications
            \App\Services\NotificationService::notifyLeaveApplied($leaveReq);
        });

        return redirect()->back()->with('success', 'Leave request submitted successfully!');
    }

    public function storeLieuClaim(Request $request)
    {
        $user = auth()->user();
        $employee = $user ? Employee::where('user_id', $user->id)->first() : null;

        if (!$employee) {
            return redirect()->back()->with('error', 'Employee profile not found.');
        }

        $request->validate([
            'worked_date' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:1000',
        ]);

        $hours = (float) $request->input('hours_worked', 0);
        if ($hours <= 0 && $request->input('start_time') && $request->input('end_time')) {
            $st = Carbon::parse($request->input('start_time'));
            $et = Carbon::parse($request->input('end_time'));
            $hours = max(0, $et->diffInMinutes($st) / 60);
        }

        // Automatically determine lieu leave duration and credit based on hours worked
        if ($hours >= 8) {
            $durationType = 'full_day';
            $creditDays = 1.00;
        } elseif ($hours >= 4) {
            $durationType = 'half_day';
            $creditDays = 0.50;
        } else {
            return redirect()->back()->withErrors([
                'hours_worked' => 'A minimum of 4 hours worked is required to request Lieu Leave (Half Day = 4 hrs, Full Day = 8 hrs).'
            ]);
        }

        $workedCarbon = Carbon::parse($request->worked_date);

        // Check if date was a weekend or public holiday
        $isWeekend = $workedCarbon->isWeekend();
        $isHoliday = Holiday::whereDate('date', $request->worked_date)->exists();

        if (!$isWeekend && !$isHoliday) {
            return redirect()->back()->withErrors([
                'worked_date' => 'Lieu Leave can only be requested for work performed on weekends (Saturday/Sunday) or official public holidays.'
            ]);
        }

        $dayType = $isHoliday ? 'Public Holiday' : ($isWeekend ? 'Weekend' : 'Off-day / Non-working Day');

        // Check duplicate active claims on same date
        $existing = LieuLeaveClaim::where('employee_id', $employee->id)
            ->whereDate('worked_date', $request->worked_date)
            ->whereIn('status', ['Pending Manager Approval', 'Approved'])
            ->first();

        if ($existing) {
            return redirect()->back()->withErrors([
                'worked_date' => "A Lieu Leave request for {$request->worked_date} is already {$existing->status}."
            ]);
        }

        $startTime = $request->input('start_time') ?: '09:00';
        $endTime = $request->input('end_time') ?: ($durationType === 'half_day' ? '13:00' : '17:00');
        $claimNumber = 'LI-' . $workedCarbon->format('Ymd') . '-' . strtoupper(Str::random(4));

        $claim = LieuLeaveClaim::create([
            'claim_number' => $claimNumber,
            'employee_id' => $employee->id,
            'worked_date' => $request->worked_date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'day_type' => $dayType,
            'duration_type' => $durationType,
            'hours_worked' => $hours,
            'credit_days' => $creditDays,
            'reason' => $request->reason,
            'status' => 'Pending Manager Approval',
        ]);

        \App\Services\NotificationService::notifyLieuClaimSubmitted($claim);

        return redirect()->back()->with('success', "Lieu Leave request submitted for {$hours} work hours! Sent to Manager for approval (+{$creditDays} day).");
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
        $leaveTypes = LeaveType::where('is_active', true)->get();

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
