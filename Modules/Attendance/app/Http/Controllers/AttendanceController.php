<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceAdjustment;
use Modules\Leave\Models\Holiday;
use Modules\Leave\Models\LeaveRequest;
use Modules\Payroll\Models\OvertimeRecord;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    private function getActiveRole()
    {
        return session('active_role') ?? auth()->user()->role ?? 'HR Admin';
    }

    private function authorizeAdminOrHr()
    {
        $role = $this->getActiveRole();
        if (!in_array($role, ['Super Admin', 'HR Admin', 'HR Manager', 'Department Head', 'Line Manager'])) {
            abort(403, 'Unauthorized access to attendance management operations');
        }
    }

    private function getShiftSettings()
    {
        $settings = DB::table('settings')->pluck('value', 'key');
        return [
            'shift_start' => $settings['shift_start_time'] ?? '09:00',
            'shift_end' => $settings['shift_end_time'] ?? '17:00',
            'grace_period' => (int) ($settings['grace_period_minutes'] ?? 15),
            'ot_multiplier' => (float) ($settings['overtime_rate_multiplier'] ?? 1.5),
            'auto_ot' => ($settings['auto_overtime_calculation'] ?? '1') == '1',
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : Employee::first();
        $role = $this->getActiveRole();
        $tab = $request->get('tab', 'daily');

        $departments = Department::all();
        $totalEmployeesCount = Employee::count();
        $shiftSettings = $this->getShiftSettings();

        // Punch clock status for today
        $todayStr = Carbon::today('Asia/Colombo')->toDateString();
        $userAttendanceToday = $activeEmp ? Attendance::where('employee_id', $activeEmp->id)->whereDate('date', $todayStr)->first() : null;
        $isClockedIn = $userAttendanceToday && $userAttendanceToday->clock_in && !$userAttendanceToday->clock_out;

        // 1. Daily Roster Data
        $date = $request->get('date', $todayStr);
        $deptFilter = $request->get('department_id');

        $employees = Employee::with(['user', 'department', 'designation'])
            ->when($deptFilter, fn($q) => $q->where('department_id', $deptFilter))
            ->get();

        $attendances = Attendance::with(['employee.user', 'employee.department'])
            ->whereDate('date', $date)
            ->get()
            ->keyBy('employee_id');

        // Check leaves for selected date
        $leavesOnDate = LeaveRequest::where('status', 'Approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->pluck('employee_id')
            ->flip()
            ->toArray();

        $presentTodayCount = Attendance::whereDate('date', $date)->whereNotNull('clock_in')->count();
        $totalOtHoursToday = Attendance::whereDate('date', $date)->sum('total_ot_hours');
        $lateArrivalsCount = Attendance::whereDate('date', $date)->where('status', 'Late')->count();
        $halfDayCount = Attendance::whereDate('date', $date)->where('status', 'Half Day')->count();
        $onLeaveTodayCount = count($leavesOnDate);
        $totalWorkHoursToday = Attendance::whereDate('date', $date)->sum('total_hours');

        // 2. Monthly Timesheet Matrix Data
        $month = (int) $request->get('month', Carbon::now('Asia/Colombo')->month);
        $year = (int) $request->get('year', Carbon::now('Asia/Colombo')->year);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $monthDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dt = Carbon::createFromDate($year, $month, $d);
            $monthDays[] = [
                'day' => $d,
                'date' => $dt->toDateString(),
                'day_name' => $dt->format('D'),
                'is_weekend' => $dt->isWeekend(),
            ];
        }

        // Fetch monthly holidays
        $monthlyHolidays = Holiday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->mapWithKeys(function ($h) {
                return [Carbon::parse($h->date)->toDateString() => $h->title];
            })
            ->toArray();

        // Fetch monthly approved leaves
        $monthlyLeaves = LeaveRequest::where('status', 'Approved')
            ->where(function ($q) use ($year, $month, $daysInMonth) {
                $startMonth = Carbon::createFromDate($year, $month, 1)->toDateString();
                $endMonth = Carbon::createFromDate($year, $month, $daysInMonth)->toDateString();
                $q->whereBetween('start_date', [$startMonth, $endMonth])
                  ->orWhereBetween('end_date', [$startMonth, $endMonth])
                  ->orWhere(function ($sub) use ($startMonth, $endMonth) {
                      $sub->where('start_date', '<=', $startMonth)->where('end_date', '>=', $endMonth);
                  });
            })
            ->get();

        // Fetch monthly attendances grouped by employee
        $monthlyAttendances = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('employee_id');

        // Build matrix for each employee
        $matrixEmployees = [];
        $totalMonthlyWorkHours = 0;
        $totalMonthlyOtHours = 0;
        $totalPresentTally = 0;
        $totalLateTally = 0;

        foreach ($employees as $emp) {
            $empAttendances = $monthlyAttendances->get($emp->id, collect())->keyBy(function ($item) {
                return Carbon::parse($item->date)->day;
            });

            $daysData = [];
            $presentDays = 0;
            $lateDays = 0;
            $halfDays = 0;
            $leaveDays = 0;
            $absentDays = 0;
            $empTotalHours = 0;
            $empOtHours = 0;

            foreach ($monthDays as $mDay) {
                $dayNum = $mDay['day'];
                $dDate = $mDay['date'];
                $isWeekend = $mDay['is_weekend'];
                $isHoliday = isset($monthlyHolidays[$dDate]);
                $isFuture = Carbon::parse($dDate)->isFuture();

                // Check leave
                $hasLeave = $monthlyLeaves->first(function ($lv) use ($emp, $dDate) {
                    return $lv->employee_id == $emp->id && $lv->start_date <= $dDate && $lv->end_date >= $dDate;
                });

                if ($empAttendances->has($dayNum)) {
                    $att = $empAttendances->get($dayNum);
                    $status = $att->status;
                    $code = match ($status) {
                        'Present' => 'P',
                        'Late' => 'L',
                        'Half Day' => 'HD',
                        'Absent' => 'A',
                        'On Leave' => 'LV',
                        default => 'P',
                    };

                    if ($status === 'Present') $presentDays++;
                    elseif ($status === 'Late') { $presentDays++; $lateDays++; }
                    elseif ($status === 'Half Day') { $halfDays++; }
                    elseif ($status === 'Absent') { $absentDays++; }
                    elseif ($status === 'On Leave') { $leaveDays++; }

                    $empTotalHours += (float) $att->total_hours;
                    $empOtHours += (float) $att->total_ot_hours;

                    $daysData[$dayNum] = [
                        'code' => $code,
                        'status' => $status,
                        'clock_in' => $att->clock_in ? Carbon::parse($att->clock_in)->format('H:i') : null,
                        'clock_out' => $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : null,
                        'hours' => $att->total_hours,
                        'ot' => $att->total_ot_hours,
                    ];
                } elseif ($hasLeave) {
                    $leaveDays++;
                    $daysData[$dayNum] = ['code' => 'LV', 'status' => 'On Leave', 'badge' => 'bg-purple-100 text-purple-800'];
                } elseif ($isHoliday) {
                    $daysData[$dayNum] = ['code' => 'H', 'status' => 'Holiday: ' . $monthlyHolidays[$dDate], 'badge' => 'bg-blue-100 text-blue-800'];
                } elseif ($isWeekend) {
                    $daysData[$dayNum] = ['code' => 'WO', 'status' => 'Week Off', 'badge' => 'bg-slate-100 text-slate-400'];
                } elseif ($isFuture) {
                    $daysData[$dayNum] = ['code' => '-', 'status' => 'Upcoming', 'badge' => 'text-slate-300'];
                } else {
                    $absentDays++;
                    $daysData[$dayNum] = ['code' => 'A', 'status' => 'Absent', 'badge' => 'bg-rose-100 text-rose-700 font-bold'];
                }
            }

            $totalMonthlyWorkHours += $empTotalHours;
            $totalMonthlyOtHours += $empOtHours;
            $totalPresentTally += $presentDays;
            $totalLateTally += $lateDays;

            $matrixEmployees[] = [
                'employee' => $emp,
                'days' => $daysData,
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'half_days' => $halfDays,
                'leave_days' => $leaveDays,
                'absent_days' => $absentDays,
                'total_hours' => round($empTotalHours, 1),
                'total_ot_hours' => round($empOtHours, 1),
            ];
        }

        // 3. My Attendance Data (Personal Timesheet)
        $myEmp = $activeEmp;
        $myMonthlyAttendances = $myEmp ? Attendance::where('employee_id', $myEmp->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->get() : collect();

        $myStats = [
            'present' => $myMonthlyAttendances->where('status', 'Present')->count() + $myMonthlyAttendances->where('status', 'Late')->count(),
            'late' => $myMonthlyAttendances->where('status', 'Late')->count(),
            'hours' => round($myMonthlyAttendances->sum('total_hours'), 1),
            'ot_hours' => round($myMonthlyAttendances->sum('total_ot_hours'), 1),
        ];

        // 4. Regularization Adjustments Data
        $adjustmentsQuery = AttendanceAdjustment::with(['employee.user', 'employee.department', 'reviewer'])
            ->orderBy('created_at', 'desc');

        if (!in_array($role, ['Super Admin', 'HR Admin', 'HR Manager'])) {
            if ($myEmp) {
                $adjustmentsQuery->where('employee_id', $myEmp->id);
            }
        }

        $adjustments = $adjustmentsQuery->get();
        $pendingAdjustmentsCount = AttendanceAdjustment::where('status', 'Pending')->count();

        // Months and Years lists for filters
        $monthsList = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $yearsList = range(Carbon::now('Asia/Colombo')->year - 2, Carbon::now('Asia/Colombo')->year + 1);

        return view('attendance::index', compact(
            'tab',
            'role',
            'activeEmp',
            'employees',
            'departments',
            'attendances',
            'leavesOnDate',
            'date',
            'deptFilter',
            'totalEmployeesCount',
            'presentTodayCount',
            'totalOtHoursToday',
            'lateArrivalsCount',
            'halfDayCount',
            'onLeaveTodayCount',
            'totalWorkHoursToday',
            'userAttendanceToday',
            'isClockedIn',
            'shiftSettings',
            'month',
            'year',
            'monthDays',
            'matrixEmployees',
            'totalMonthlyWorkHours',
            'totalMonthlyOtHours',
            'totalPresentTally',
            'totalLateTally',
            'myMonthlyAttendances',
            'myStats',
            'adjustments',
            'pendingAdjustmentsCount',
            'monthsList',
            'yearsList'
        ));
    }

    public function clockToggle(Request $request)
    {
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        if (!$activeEmp) {
            return redirect()->back()->with('error', 'Employee profile not found for active user.');
        }

        $now = Carbon::now('Asia/Colombo');
        $today = $now->toDateString();
        $settings = $this->getShiftSettings();

        $shiftStart = Carbon::parse($today . ' ' . $settings['shift_start'], 'Asia/Colombo');
        $shiftEnd = Carbon::parse($today . ' ' . $settings['shift_end'], 'Asia/Colombo');

        $attendance = Attendance::where('employee_id', $activeEmp->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            $attendance = new Attendance([
                'employee_id' => $activeEmp->id,
                'date' => $today,
                'status' => 'Present',
                'clock_in_ip' => $request->ip(),
            ]);
        }

        if (!$attendance->clock_in) {
            // Clock In Action
            $attendance->clock_in = $now;
            
            // Check if late (after shift start + grace period)
            if ($now->greaterThan($shiftStart->copy()->addMinutes($settings['grace_period']))) {
                $attendance->status = 'Late';
            } else {
                $attendance->status = 'Present';
            }
            $attendance->save();

            session(['clocked_in' => true]);
            return redirect()->back()->with('success', 'Clocked In successfully at ' . $now->format('h:i A') . ' (Shift Start: ' . $shiftStart->format('h:i A') . ')');
        } elseif (!$attendance->clock_out) {
            // Clock Out Action
            $attendance->clock_out = $now;
            $attendance->clock_out_ip = $request->ip();

            $clockInTime = Carbon::parse($attendance->clock_in, 'Asia/Colombo');
            $clockOutTime = $now;

            $totalHours = round($clockInTime->diffInMinutes($clockOutTime) / 60, 2);
            $attendance->total_hours = $totalHours;

            // Early Overtime
            $earlyOtHours = 0;
            if ($clockInTime->lessThan($shiftStart)) {
                $earlyOtMinutes = $clockInTime->diffInMinutes($shiftStart);
                $earlyOtHours = round($earlyOtMinutes / 60, 2);
            }

            // Late Overtime
            $lateOtHours = 0;
            if ($clockOutTime->greaterThan($shiftEnd)) {
                $lateOtMinutes = $shiftEnd->diffInMinutes($clockOutTime);
                $lateOtHours = round($lateOtMinutes / 60, 2);
            }

            $totalOtHours = round($earlyOtHours + $lateOtHours, 2);
            $regularHours = max(0, round($totalHours - $totalOtHours, 2));

            $attendance->early_ot_hours = $earlyOtHours;
            $attendance->late_ot_hours = $lateOtHours;
            $attendance->total_ot_hours = $totalOtHours;
            $attendance->regular_hours = $regularHours;

            if ($totalHours < 4.5 && $attendance->status !== 'Late') {
                $attendance->status = 'Half Day';
            }

            $attendance->save();

            // Automatically sync Overtime Record in Payroll module if OT was earned
            if ($totalOtHours > 0 && $settings['auto_ot']) {
                $basic = (float) ($activeEmp->basic_salary ?: 180000);
                $hourlyRate = round($basic / 200, 2);
                $multiplier = $settings['ot_multiplier'];
                $otAmount = round($hourlyRate * $totalOtHours * $multiplier, 2);

                $otNumber = 'OT-AUTO-' . $now->format('ymd') . '-' . $activeEmp->id;

                OvertimeRecord::updateOrCreate(
                    [
                        'employee_id' => $activeEmp->id,
                        'ot_date' => $today,
                    ],
                    [
                        'ot_number' => $otNumber,
                        'start_time' => $clockInTime->format('H:i'),
                        'end_time' => $clockOutTime->format('H:i'),
                        'hours' => $totalOtHours,
                        'rate_multiplier_type' => "System Standard ({$multiplier}x)",
                        'multiplier' => $multiplier,
                        'hourly_rate' => $hourlyRate,
                        'estimated_amount' => $otAmount,
                        'reason' => "Automatic Clock-in/out Overtime calculation (Early: {$earlyOtHours}h, Late: {$lateOtHours}h)",
                        'status' => 'Approved',
                        'approved_at' => $now,
                        'approved_by_user_id' => auth()->id(),
                    ]
                );
            }

            session(['clocked_in' => false]);
            $otMsg = $totalOtHours > 0 ? " [Overtime Recorded: {$totalOtHours} hrs]" : '';
            return redirect()->back()->with('success', 'Clocked Out successfully at ' . $now->format('h:i A') . " (Total: {$totalHours} hrs){$otMsg}");
        } else {
            return redirect()->back()->with('info', 'You have already completed your clock-in/out cycle for today (' . $attendance->total_hours . ' hrs worked).');
        }
    }

    public function storeManual(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'status' => 'nullable|string',
            'clock_in' => 'nullable|string',
            'clock_out' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $settings = $this->getShiftSettings();
        $dateStr = Carbon::parse($request->date)->toDateString();

        $clockInDt = $request->clock_in ? Carbon::parse($dateStr . ' ' . $request->clock_in, 'Asia/Colombo') : null;
        $clockOutDt = $request->clock_out ? Carbon::parse($dateStr . ' ' . $request->clock_out, 'Asia/Colombo') : null;

        $totalHours = 0;
        $earlyOtHours = 0;
        $lateOtHours = 0;
        $totalOtHours = 0;
        $regularHours = 0;

        $computedStatus = 'Present';

        if ($clockInDt && $clockOutDt) {
            $totalHours = round($clockInDt->diffInMinutes($clockOutDt) / 60, 2);
            $shiftStart = Carbon::parse($dateStr . ' ' . $settings['shift_start'], 'Asia/Colombo');
            $shiftEnd = Carbon::parse($dateStr . ' ' . $settings['shift_end'], 'Asia/Colombo');

            if ($clockInDt->lessThan($shiftStart)) {
                $earlyOtHours = round($clockInDt->diffInMinutes($shiftStart) / 60, 2);
            }
            if ($clockOutDt->greaterThan($shiftEnd)) {
                $lateOtHours = round($shiftEnd->diffInMinutes($clockOutDt) / 60, 2);
            }
            $totalOtHours = round($earlyOtHours + $lateOtHours, 2);
            $regularHours = max(0, round($totalHours - $totalOtHours, 2));

            if ($clockInDt->greaterThan($shiftStart->copy()->addMinutes($settings['grace_period']))) {
                $computedStatus = 'Late';
            } elseif ($totalHours < 4.5 && $totalHours > 0) {
                $computedStatus = 'Half Day';
            } else {
                $computedStatus = 'Present';
            }
        } elseif ($clockInDt) {
            $shiftStart = Carbon::parse($dateStr . ' ' . $settings['shift_start'], 'Asia/Colombo');
            if ($clockInDt->greaterThan($shiftStart->copy()->addMinutes($settings['grace_period']))) {
                $computedStatus = 'Late';
            } else {
                $computedStatus = 'Present';
            }
        } else {
            $computedStatus = 'Absent';
        }

        $finalStatus = $request->status ?: $computedStatus;

        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $dateStr)
            ->first();

        if (!$attendance) {
            $attendance = new Attendance([
                'employee_id' => $request->employee_id,
                'date' => $dateStr,
            ]);
        }

        $attendance->fill([
            'clock_in' => $clockInDt,
            'clock_out' => $clockOutDt,
            'status' => $finalStatus,
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'early_ot_hours' => $earlyOtHours,
            'late_ot_hours' => $lateOtHours,
            'total_ot_hours' => $totalOtHours,
            'notes' => $request->notes,
        ]);
        $attendance->save();

        return redirect()->back()->with('success', 'Attendance record saved successfully for ' . $dateStr);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdminOrHr();

        $attendance = Attendance::findOrFail($id);
        $request->validate([
            'status' => 'nullable|string',
            'clock_in' => 'nullable|string',
            'clock_out' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $settings = $this->getShiftSettings();
        $dateStr = Carbon::parse($attendance->date)->toDateString();

        $clockInDt = $request->clock_in ? Carbon::parse($dateStr . ' ' . $request->clock_in, 'Asia/Colombo') : null;
        $clockOutDt = $request->clock_out ? Carbon::parse($dateStr . ' ' . $request->clock_out, 'Asia/Colombo') : null;

        $totalHours = 0;
        $earlyOtHours = 0;
        $lateOtHours = 0;
        $totalOtHours = 0;
        $regularHours = 0;

        $computedStatus = 'Present';

        if ($clockInDt && $clockOutDt) {
            $totalHours = round($clockInDt->diffInMinutes($clockOutDt) / 60, 2);
            $shiftStart = Carbon::parse($dateStr . ' ' . $settings['shift_start'], 'Asia/Colombo');
            $shiftEnd = Carbon::parse($dateStr . ' ' . $settings['shift_end'], 'Asia/Colombo');

            if ($clockInDt->lessThan($shiftStart)) {
                $earlyOtHours = round($clockInDt->diffInMinutes($shiftStart) / 60, 2);
            }
            if ($clockOutDt->greaterThan($shiftEnd)) {
                $lateOtHours = round($shiftEnd->diffInMinutes($clockOutDt) / 60, 2);
            }
            $totalOtHours = round($earlyOtHours + $lateOtHours, 2);
            $regularHours = max(0, round($totalHours - $totalOtHours, 2));

            if ($clockInDt->greaterThan($shiftStart->copy()->addMinutes($settings['grace_period']))) {
                $computedStatus = 'Late';
            } elseif ($totalHours < 4.5 && $totalHours > 0) {
                $computedStatus = 'Half Day';
            } else {
                $computedStatus = 'Present';
            }
        } elseif ($clockInDt) {
            $shiftStart = Carbon::parse($dateStr . ' ' . $settings['shift_start'], 'Asia/Colombo');
            if ($clockInDt->greaterThan($shiftStart->copy()->addMinutes($settings['grace_period']))) {
                $computedStatus = 'Late';
            } else {
                $computedStatus = 'Present';
            }
        } else {
            $computedStatus = 'Absent';
        }

        $finalStatus = $request->status ?: $computedStatus;

        $attendance->update([
            'clock_in' => $clockInDt,
            'clock_out' => $clockOutDt,
            'status' => $finalStatus,
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'early_ot_hours' => $earlyOtHours,
            'late_ot_hours' => $lateOtHours,
            'total_ot_hours' => $totalOtHours,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Attendance record updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorizeAdminOrHr();

        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return redirect()->back()->with('success', 'Attendance record removed successfully.');
    }

    public function storeAdjustment(Request $request)
    {
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $employeeId = $request->employee_id ?: ($activeEmp->id ?? null);
        if (!$employeeId) {
            return redirect()->back()->with('error', 'Employee identity not found.');
        }

        $request->validate([
            'date' => 'required|date',
            'clock_in' => 'required|string',
            'clock_out' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        AttendanceAdjustment::create([
            'employee_id' => $employeeId,
            'date' => $request->date,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        return redirect()->route('attendance.index', ['tab' => 'adjustments'])
            ->with('success', 'Attendance regularization request submitted successfully. Pending manager approval.');
    }

    public function reviewAdjustment(Request $request, $id)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'action' => 'required|in:Approved,Rejected',
            'review_remarks' => 'nullable|string|max:500',
        ]);

        $adjustment = AttendanceAdjustment::with('employee')->findOrFail($id);
        $adjustment->update([
            'status' => $request->action,
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => Carbon::now('Asia/Colombo'),
            'review_remarks' => $request->review_remarks,
        ]);

        // If approved, update or create the Attendance record
        if ($request->action === 'Approved') {
            $settings = $this->getShiftSettings();
            $dateStr = Carbon::parse($adjustment->date)->toDateString();

            $clockInDt = $adjustment->clock_in ? Carbon::parse($dateStr . ' ' . $adjustment->clock_in, 'Asia/Colombo') : null;
            $clockOutDt = $adjustment->clock_out ? Carbon::parse($dateStr . ' ' . $adjustment->clock_out, 'Asia/Colombo') : null;

            $totalHours = 0;
            $earlyOtHours = 0;
            $lateOtHours = 0;
            $totalOtHours = 0;
            $regularHours = 0;

            if ($clockInDt && $clockOutDt) {
                $totalHours = round($clockInDt->diffInMinutes($clockOutDt) / 60, 2);
                $shiftStart = Carbon::parse($dateStr . ' ' . $settings['shift_start'], 'Asia/Colombo');
                $shiftEnd = Carbon::parse($dateStr . ' ' . $settings['shift_end'], 'Asia/Colombo');

                if ($clockInDt->lessThan($shiftStart)) {
                    $earlyOtHours = round($clockInDt->diffInMinutes($shiftStart) / 60, 2);
                }
                if ($clockOutDt->greaterThan($shiftEnd)) {
                    $lateOtHours = round($shiftEnd->diffInMinutes($clockOutDt) / 60, 2);
                }
                $totalOtHours = round($earlyOtHours + $lateOtHours, 2);
                $regularHours = max(0, round($totalHours - $totalOtHours, 2));
            }

            $attendance = Attendance::where('employee_id', $adjustment->employee_id)
                ->whereDate('date', $dateStr)
                ->first();

            if (!$attendance) {
                $attendance = new Attendance([
                    'employee_id' => $adjustment->employee_id,
                    'date' => $dateStr,
                ]);
            }

            $attendance->fill([
                'clock_in' => $clockInDt,
                'clock_out' => $clockOutDt,
                'status' => 'Present',
                'total_hours' => $totalHours,
                'regular_hours' => $regularHours,
                'early_ot_hours' => $earlyOtHours,
                'late_ot_hours' => $lateOtHours,
                'total_ot_hours' => $totalOtHours,
                'notes' => 'Regularized via Adjustment Request #' . $adjustment->id . ($adjustment->reason ? " ({$adjustment->reason})" : ''),
            ]);
            $attendance->save();
        }

        return redirect()->back()->with('success', "Regularization request {$request->action} successfully.");
    }

    public function exportMonthly(Request $request)
    {
        $this->authorizeAdminOrHr();

        $month = (int) $request->get('month', Carbon::now('Asia/Colombo')->month);
        $year = (int) $request->get('year', Carbon::now('Asia/Colombo')->year);
        $deptFilter = $request->get('department_id');

        $employees = Employee::with(['user', 'department'])
            ->when($deptFilter, fn($q) => $q->where('department_id', $deptFilter))
            ->get();

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $monthlyHolidays = Holiday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->mapWithKeys(function ($h) {
                return [Carbon::parse($h->date)->toDateString() => $h->title];
            })
            ->toArray();

        $monthlyAttendances = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('employee_id');

        $filename = "Monthly_Attendance_Sheet_{$month}_{$year}.csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($employees, $monthlyAttendances, $monthlyHolidays, $daysInMonth, $year, $month) {
            $file = fopen('php://output', 'w');

            // Header row
            $header = ['Emp ID', 'EPF No', 'Employee Name', 'Department'];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $header[] = (string) $d;
            }
            $header[] = 'Present Days';
            $header[] = 'Late Days';
            $header[] = 'Total Hours';
            $header[] = 'OT Hours';

            fputcsv($file, $header);

            foreach ($employees as $emp) {
                $empAttendances = $monthlyAttendances->get($emp->id, collect())->keyBy(function ($item) {
                    return Carbon::parse($item->date)->day;
                });

                $row = [
                    $emp->employee_id_number ?? "EMP-{$emp->id}",
                    $emp->epf_registration_no ?? 'N/A',
                    $emp->user->name ?? 'Employee',
                    $emp->department->name ?? 'Corporate',
                ];

                $presentDays = 0;
                $lateDays = 0;
                $totalHours = 0;
                $otHours = 0;

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dDate = Carbon::createFromDate($year, $month, $d)->toDateString();
                    $isWeekend = Carbon::createFromDate($year, $month, $d)->isWeekend();

                    if ($empAttendances->has($d)) {
                        $att = $empAttendances->get($d);
                        $row[] = match ($att->status) {
                            'Present' => 'P',
                            'Late' => 'L',
                            'Half Day' => 'HD',
                            'Absent' => 'A',
                            'On Leave' => 'LV',
                            default => 'P',
                        };
                        if ($att->status === 'Present') $presentDays++;
                        elseif ($att->status === 'Late') { $presentDays++; $lateDays++; }
                        $totalHours += (float) $att->total_hours;
                        $otHours += (float) $att->total_ot_hours;
                    } elseif (isset($monthlyHolidays[$dDate])) {
                        $row[] = 'H';
                    } elseif ($isWeekend) {
                        $row[] = 'WO';
                    } else {
                        $row[] = 'A';
                    }
                }

                $row[] = $presentDays;
                $row[] = $lateDays;
                $row[] = round($totalHours, 1);
                $row[] = round($otHours, 1);

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadSampleCsv(Request $request)
    {
        $this->authorizeAdminOrHr();
        $format = $request->get('format', 'summary');

        $filename = $format === 'raw' 
            ? 'biometric_raw_punches_template.csv' 
            : 'attendance_machine_logs_template.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($format) {
            $file = fopen('php://output', 'w');
            if ($format === 'raw') {
                fputcsv($file, ['Employee_ID', 'Timestamp', 'Punch_Type', 'Device_Name']);
                fputcsv($file, ['EMP-001', '2026-09-10 08:52:00', 'Check-In', 'Main Gate Biometric #1']);
                fputcsv($file, ['EMP-001', '2026-09-10 17:08:00', 'Check-Out', 'Main Gate Biometric #1']);
                fputcsv($file, ['EMP-002', '2026-09-10 09:25:00', 'Check-In', 'Turnstile RFID #2']);
                fputcsv($file, ['EMP-002', '2026-09-10 17:15:00', 'Check-Out', 'Turnstile RFID #2']);
            } else {
                fputcsv($file, ['Employee_ID', 'Date', 'Clock_In', 'Clock_Out', 'Notes']);
                fputcsv($file, ['EMP-001', '2026-09-10', '08:52', '17:08', 'Biometric Turnstile #1']);
                fputcsv($file, ['EMP-002', '2026-09-10', '09:25', '17:15', 'Fingerprint Scanner #2']);
                fputcsv($file, ['EMP-003', '2026-09-10', '08:45', '18:30', 'Face Recognition Terminal']);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkImport(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return redirect()->back()->with('error', 'Unable to read the uploaded CSV file.');
        }

        $settings = $this->getShiftSettings();
        $firstLine = fgetcsv($handle);
        if (!$firstLine) {
            fclose($handle);
            return redirect()->back()->with('error', 'Uploaded CSV file is empty.');
        }

        // Clean & analyze header row
        $cleanHeader = array_map(function ($col) {
            return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$col));
        }, $firstLine);

        $hasHeader = false;
        $colEmp = null;
        $colDate = null;
        $colTime = null;
        $colClockIn = null;
        $colClockOut = null;
        $colStatus = null;
        $colNotes = null;

        foreach ($cleanHeader as $idx => $key) {
            if (in_array($key, ['employeeid', 'employeeidnumber', 'empid', 'badgeid', 'badgeno', 'userid', 'user', 'id', 'enrollno', 'cardno', 'pin', 'code'])) {
                $colEmp = $idx;
                $hasHeader = true;
            } elseif (in_array($key, ['date', 'logdate', 'punchdate', 'attendancedate', 'workdate'])) {
                $colDate = $idx;
                $hasHeader = true;
            } elseif (in_array($key, ['timestamp', 'datetime', 'punchtime', 'logtime', 'time', 'punchedat'])) {
                $colTime = $idx;
                $hasHeader = true;
            } elseif (in_array($key, ['clockin', 'intime', 'checkin', 'in', 'timein', 'start', 'starttime'])) {
                $colClockIn = $idx;
                $hasHeader = true;
            } elseif (in_array($key, ['clockout', 'outtime', 'checkout', 'out', 'timeout', 'end', 'endtime'])) {
                $colClockOut = $idx;
                $hasHeader = true;
            } elseif (in_array($key, ['status', 'attendancestatus', 'state', 'punchstate'])) {
                $colStatus = $idx;
                $hasHeader = true;
            } elseif (in_array($key, ['notes', 'note', 'remark', 'remarks', 'devicename', 'device', 'location'])) {
                $colNotes = $idx;
                $hasHeader = true;
            }
        }

        $rows = [];
        if ($hasHeader) {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) continue;
                $rows[] = $row;
            }
        } else {
            // First row was actually data
            if (!empty(array_filter($firstLine, fn($v) => trim((string)$v) !== ''))) {
                $rows[] = $firstLine;
            }
            while (($row = fgetcsv($handle)) !== false) {
                if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) continue;
                $rows[] = $row;
            }
            // Default position mappings for headerless CSVs
            $colEmp = 0;
            if (count($rows[0] ?? []) >= 4) {
                $colDate = 1;
                $colClockIn = 2;
                $colClockOut = 3;
                $colNotes = 4;
            } elseif (count($rows[0] ?? []) >= 2) {
                $colTime = 1;
                $colNotes = 2;
            }
        }
        fclose($handle);

        if (empty($rows)) {
            return redirect()->back()->with('error', 'No valid attendance data rows found in the CSV file.');
        }

        // Cache employee records for fast lookup
        $allEmployees = Employee::with('user')->get();
        $empLookup = [];
        foreach ($allEmployees as $e) {
            if ($e->employee_id_number) {
                $empLookup[strtolower(trim($e->employee_id_number))] = $e;
            }
            if ($e->epf_registration_no) {
                $empLookup[strtolower(trim($e->epf_registration_no))] = $e;
            }
            $empLookup[(string)$e->id] = $e;
            if ($e->user && $e->user->email) {
                $empLookup[strtolower(trim($e->user->email))] = $e;
            }
            if ($e->user && $e->user->name) {
                $empLookup[strtolower(trim($e->user->name))] = $e;
            }
        }

        // Structure: $records[employee_id][date] = ['clock_in' => ..., 'clock_out' => ..., 'notes' => ..., 'status' => ...]
        $parsedRecords = [];
        $skippedCount = 0;

        foreach ($rows as $r) {
            $empKey = trim((string)($r[$colEmp ?? 0] ?? ''));
            if ($empKey === '') {
                $skippedCount++;
                continue;
            }

            $matchedEmp = $empLookup[strtolower($empKey)] ?? null;
            if (!$matchedEmp) {
                // Try numeric match
                if (is_numeric($empKey) && isset($empLookup[(int)$empKey])) {
                    $matchedEmp = $empLookup[(int)$empKey];
                }
            }

            if (!$matchedEmp) {
                $skippedCount++;
                continue;
            }

            $empId = $matchedEmp->id;

            // Determine if raw timestamp or summary mode
            if ($colTime !== null && isset($r[$colTime]) && trim((string)$r[$colTime]) !== '') {
                // Raw timestamp mode: e.g. "2026-09-10 08:55:00" or ISO format
                try {
                    $parsedDt = Carbon::parse(trim((string)$r[$colTime]), 'Asia/Colombo');
                    $dateKey = $parsedDt->toDateString();
                    $note = isset($colNotes, $r[$colNotes]) ? trim((string)$r[$colNotes]) : 'Biometric Machine Punch';

                    if (!isset($parsedRecords[$empId][$dateKey])) {
                        $parsedRecords[$empId][$dateKey] = [
                            'employee' => $matchedEmp,
                            'punches' => [],
                            'status' => null,
                            'notes' => $note,
                        ];
                    }
                    $parsedRecords[$empId][$dateKey]['punches'][] = $parsedDt;
                } catch (\Exception $e) {
                    $skippedCount++;
                }
            } else {
                // Summary mode: Date + Clock In + Clock Out
                $dateVal = isset($colDate, $r[$colDate]) ? trim((string)$r[$colDate]) : null;
                if (!$dateVal) {
                    $skippedCount++;
                    continue;
                }

                try {
                    $dateKey = Carbon::parse($dateVal, 'Asia/Colombo')->toDateString();
                } catch (\Exception $e) {
                    $skippedCount++;
                    continue;
                }

                $inVal = isset($colClockIn, $r[$colClockIn]) ? trim((string)$r[$colClockIn]) : null;
                $outVal = isset($colClockOut, $r[$colClockOut]) ? trim((string)$r[$colClockOut]) : null;
                $statusVal = isset($colStatus, $r[$colStatus]) ? trim((string)$r[$colStatus]) : null;
                $noteVal = isset($colNotes, $r[$colNotes]) ? trim((string)$r[$colNotes]) : 'Imported via Machine CSV';

                $clockInDt = null;
                $clockOutDt = null;

                if ($inVal) {
                    try {
                        $clockInDt = Carbon::parse($dateKey . ' ' . $inVal, 'Asia/Colombo');
                    } catch (\Exception $e) {}
                }

                if ($outVal) {
                    try {
                        $clockOutDt = Carbon::parse($dateKey . ' ' . $outVal, 'Asia/Colombo');
                    } catch (\Exception $e) {}
                }

                $parsedRecords[$empId][$dateKey] = [
                    'employee' => $matchedEmp,
                    'clock_in' => $clockInDt,
                    'clock_out' => $clockOutDt,
                    'status' => $statusVal,
                    'notes' => $noteVal,
                ];
            }
        }

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($parsedRecords as $empId => $dates) {
            foreach ($dates as $dateKey => $data) {
                $matchedEmp = $data['employee'];
                $clockInDt = null;
                $clockOutDt = null;

                if (isset($data['punches'])) {
                    // Raw punch sorting
                    $punches = $data['punches'];
                    usort($punches, fn($a, $b) => $a->timestamp <=> $b->timestamp);
                    $clockInDt = $punches[0];
                    if (count($punches) > 1) {
                        $clockOutDt = end($punches);
                    }
                } else {
                    $clockInDt = $data['clock_in'] ?? null;
                    $clockOutDt = $data['clock_out'] ?? null;
                }

                $totalHours = 0;
                $earlyOtHours = 0;
                $lateOtHours = 0;
                $totalOtHours = 0;
                $regularHours = 0;
                $computedStatus = 'Present';

                $shiftStart = Carbon::parse($dateKey . ' ' . $settings['shift_start'], 'Asia/Colombo');
                $shiftEnd = Carbon::parse($dateKey . ' ' . $settings['shift_end'], 'Asia/Colombo');

                if ($clockInDt && $clockOutDt) {
                    $totalHours = round($clockInDt->diffInMinutes($clockOutDt) / 60, 2);

                    if ($clockInDt->lessThan($shiftStart)) {
                        $earlyOtHours = round($clockInDt->diffInMinutes($shiftStart) / 60, 2);
                    }
                    if ($clockOutDt->greaterThan($shiftEnd)) {
                        $lateOtHours = round($shiftEnd->diffInMinutes($clockOutDt) / 60, 2);
                    }
                    $totalOtHours = round($earlyOtHours + $lateOtHours, 2);
                    $regularHours = max(0, round($totalHours - $totalOtHours, 2));

                    if ($clockInDt->greaterThan($shiftStart->copy()->addMinutes($settings['grace_period']))) {
                        $computedStatus = 'Late';
                    } elseif ($totalHours < 4.5 && $totalHours > 0) {
                        $computedStatus = 'Half Day';
                    } else {
                        $computedStatus = 'Present';
                    }
                } elseif ($clockInDt) {
                    if ($clockInDt->greaterThan($shiftStart->copy()->addMinutes($settings['grace_period']))) {
                        $computedStatus = 'Late';
                    } else {
                        $computedStatus = 'Present';
                    }
                } else {
                    $computedStatus = 'Absent';
                }

                $finalStatus = !empty($data['status']) ? $data['status'] : $computedStatus;

                $attendance = Attendance::where('employee_id', $empId)
                    ->whereDate('date', $dateKey)
                    ->first();

                $isNew = false;
                if (!$attendance) {
                    $attendance = new Attendance([
                        'employee_id' => $empId,
                        'date' => $dateKey,
                    ]);
                    $isNew = true;
                }

                $attendance->fill([
                    'clock_in' => $clockInDt,
                    'clock_out' => $clockOutDt,
                    'status' => $finalStatus,
                    'total_hours' => $totalHours,
                    'regular_hours' => $regularHours,
                    'early_ot_hours' => $earlyOtHours,
                    'late_ot_hours' => $lateOtHours,
                    'total_ot_hours' => $totalOtHours,
                    'notes' => $data['notes'] ?? 'Machine Log CSV Import',
                ]);
                $attendance->save();

                if ($isNew) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }

                // Sync Overtime Record in Payroll if overtime exists and auto_ot is on
                if ($totalOtHours > 0 && $settings['auto_ot'] && $clockInDt && $clockOutDt) {
                    $basic = (float)($matchedEmp->basic_salary ?: 180000);
                    $hourlyRate = round($basic / 200, 2);
                    $multiplier = $settings['ot_multiplier'];
                    $otAmount = round($hourlyRate * $totalOtHours * $multiplier, 2);
                    $otNumber = 'OT-IMP-' . Carbon::parse($dateKey)->format('ymd') . '-' . $empId;

                    OvertimeRecord::updateOrCreate(
                        [
                            'employee_id' => $empId,
                            'ot_date' => $dateKey,
                        ],
                        [
                            'ot_number' => $otNumber,
                            'start_time' => $clockInDt->format('H:i'),
                            'end_time' => $clockOutDt->format('H:i'),
                            'hours' => $totalOtHours,
                            'rate_multiplier_type' => "System Standard ({$multiplier}x)",
                            'multiplier' => $multiplier,
                            'hourly_rate' => $hourlyRate,
                            'estimated_amount' => $otAmount,
                            'reason' => "Biometric Machine Log Overtime (Early: {$earlyOtHours}h, Late: {$lateOtHours}h)",
                            'status' => 'Approved',
                            'approved_at' => now(),
                            'approved_by_user_id' => auth()->id(),
                        ]
                    );
                }
            }
        }

        $totalProcessed = $createdCount + $updatedCount;
        $msg = "Machine attendance logs processed: {$totalProcessed} record(s) synced ({$createdCount} new, {$updatedCount} updated).";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} row(s) skipped due to unmatched employee code or invalid date format).";
        }

        return redirect()->route('attendance.index', ['tab' => 'daily'])
            ->with('success', $msg);
    }

    public function syncLeaves(Request $request)
    {
        $this->authorizeAdminOrHr();

        $date = $request->get('date', Carbon::today('Asia/Colombo')->toDateString());
        $approvedLeaves = LeaveRequest::where('status', 'Approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get();

        $synced = 0;
        foreach ($approvedLeaves as $leave) {
            $attendance = Attendance::where('employee_id', $leave->employee_id)
                ->whereDate('date', $date)
                ->first();

            if (!$attendance) {
                $attendance = new Attendance([
                    'employee_id' => $leave->employee_id,
                    'date' => $date,
                ]);
            }

            $attendance->fill([
                'status' => 'On Leave',
                'notes' => 'Approved Leave: ' . ($leave->leaveType->name ?? 'Annual Leave'),
            ]);
            $attendance->save();

            $synced++;
        }

        return redirect()->back()->with('success', "Synced {$synced} approved leaves with attendance roster for {$date}.");
    }
}
