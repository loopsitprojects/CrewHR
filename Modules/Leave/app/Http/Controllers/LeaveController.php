<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $employee = \Modules\Employee\Models\Employee::first(); // Mock auth employee
        if (!$employee) return redirect('/');

        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        $date = \Carbon\Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $date->daysInMonth;
        
        // Generate Calendar Grid (Padding start and end)
        $startDayOfWeek = $date->copy()->firstOfMonth()->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        $endDayOfWeek = $date->copy()->lastOfMonth()->dayOfWeekIso;

        $calendarDays = [];
        
        // Pad beginning of month
        for ($i = 1; $i < $startDayOfWeek; $i++) {
            $calendarDays[] = ['date' => null, 'is_current_month' => false];
        }
        
        // Actual days
        $holidays = \Modules\Leave\Models\Holiday::whereMonth('date', $month)->whereYear('date', $year)->get()->keyBy('date');
        $leaves = \Modules\Leave\Models\LeaveRequest::with('leaveType')->where('employee_id', $employee->id)
            ->whereMonth('start_date', $month)->whereYear('start_date', $year)->get()->keyBy('start_date');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $date->copy()->day($day)->format('Y-m-d');
            $isWeekend = $date->copy()->day($day)->isWeekend();
            
            $calendarDays[] = [
                'day' => $day,
                'date' => $currentDate,
                'is_current_month' => true,
                'is_weekend' => $isWeekend,
                'holiday' => $holidays->has($currentDate) ? $holidays[$currentDate] : null,
                'leave' => $leaves->has($currentDate) ? $leaves[$currentDate] : null,
            ];
        }

        // Pad end of month
        $paddingEnd = 7 - $endDayOfWeek;
        for ($i = 0; $i < $paddingEnd; $i++) {
            $calendarDays[] = ['date' => null, 'is_current_month' => false];
        }

        // Balances
        $balances = \Modules\Leave\Models\EmployeeLeaveBalance::with('leaveType')->where('employee_id', $employee->id)->where('year', $year)->get();
        
        $gazetteCount = $holidays->where('type', 'Gazette')->count();

        return view('leave::index', compact('calendarDays', 'balances', 'date', 'gazetteCount', 'month', 'year'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('leave::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('leave::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('leave::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
