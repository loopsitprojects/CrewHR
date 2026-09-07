<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;

class AppraisalController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['user', 'department', 'designation'])->get();
        return view('pages.appraisal', compact('employees'));
    }
}
