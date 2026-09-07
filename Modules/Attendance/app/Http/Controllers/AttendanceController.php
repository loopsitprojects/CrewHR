<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;

class AttendanceController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['user', 'department', 'designation'])->get();
        $departments = Department::all();

        return view('attendance::index', compact('employees', 'departments'));
    }

    public function clockToggle(Request $request)
    {
        $status = session('clocked_in', false);
        session(['clocked_in' => !$status]);

        $msg = !$status ? 'Clocked In successfully at ' . now()->format('h:i A') . '!' : 'Clocked Out successfully at ' . now()->format('h:i A') . '!';

        return redirect()->back()->with('success', $msg);
    }
}
