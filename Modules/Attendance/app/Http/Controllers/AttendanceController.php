<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Attendance\Models\Attendance;
use Modules\Payroll\Models\OvertimeRecord;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
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
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $date = $request->get('date', Carbon::today('Asia/Colombo')->toDateString());
        $deptFilter = $request->get('department_id');

        $employees = Employee::with(['user', 'department', 'designation'])
            ->when($deptFilter, fn($q) => $q->where('department_id', $deptFilter))
            ->get();
        $departments = Department::all();

        $attendances = Attendance::with(['employee.user', 'employee.department'])
            ->whereDate('date', $date)
            ->get()
            ->keyBy('employee_id');

        // Today's stats
        $totalEmployeesCount = Employee::count();
        $presentTodayCount = Attendance::whereDate('date', $date)->whereNotNull('clock_in')->count();
        $totalOtHoursToday = Attendance::whereDate('date', $date)->sum('total_ot_hours');
        $lateArrivalsCount = Attendance::whereDate('date', $date)->where('status', 'Late')->count();

        // Check if current user is clocked in today
        $userAttendanceToday = $activeEmp ? Attendance::where('employee_id', $activeEmp->id)->whereDate('date', Carbon::today('Asia/Colombo')->toDateString())->first() : null;
        $isClockedIn = $userAttendanceToday && $userAttendanceToday->clock_in && !$userAttendanceToday->clock_out;

        $shiftSettings = $this->getShiftSettings();

        return view('attendance::index', compact(
            'employees',
            'departments',
            'attendances',
            'date',
            'deptFilter',
            'totalEmployeesCount',
            'presentTodayCount',
            'totalOtHoursToday',
            'lateArrivalsCount',
            'userAttendanceToday',
            'isClockedIn',
            'shiftSettings'
        ));
    }

    public function clockToggle(Request $request)
    {
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        if (!$activeEmp) {
            return redirect()->back()->with('error', 'Employee profile not found.');
        }

        $now = Carbon::now('Asia/Colombo');
        $today = $now->toDateString();
        $settings = $this->getShiftSettings();

        $shiftStart = Carbon::parse($today . ' ' . $settings['shift_start'], 'Asia/Colombo');
        $shiftEnd = Carbon::parse($today . ' ' . $settings['shift_end'], 'Asia/Colombo');

        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $activeEmp->id, 'date' => $today],
            [
                'status' => 'Present',
                'clock_in_ip' => $request->ip(),
            ]
        );

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

            // --- Overtime Computation Engine ---
            // 1. Early Overtime: Clocked in before shift_start (e.g., 9:00 AM)
            $earlyOtHours = 0;
            if ($clockInTime->lessThan($shiftStart)) {
                $earlyOtMinutes = $clockInTime->diffInMinutes($shiftStart);
                $earlyOtHours = round($earlyOtMinutes / 60, 2);
            }

            // 2. Late Overtime: Clocked out after shift_end (e.g., 5:00 PM / 17:00)
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
                        'status' => 'Approved', // Auto-computed attendance OT is approved into payroll
                        'approved_at' => $now,
                        'approved_by_user_id' => auth()->id(),
                    ]
                );
            }

            session(['clocked_in' => false]);
            $otMsg = $totalOtHours > 0 ? " [Overtime Recorded: {$totalOtHours} hrs]" : '';
            return redirect()->back()->with('success', 'Clocked Out successfully at ' . $now->format('h:i A') . " (Total: {$totalHours} hrs){$otMsg}");
        } else {
            // Already clocked out today - allow starting a new session or informing
            return redirect()->back()->with('info', 'You have already completed your clock-in/out cycle for today (' . $attendance->total_hours . ' hrs worked).');
        }
    }
}

