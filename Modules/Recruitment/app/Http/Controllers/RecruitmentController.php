<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RecruitmentController extends Controller
{
    public function index()
    {
        $jobs = [
            ['title' => 'Senior DevOps Engineer', 'dept' => 'IT', 'type' => 'Full Time', 'applicants' => 14, 'status' => 'Active'],
            ['title' => 'Product Manager', 'dept' => 'IT', 'type' => 'Full Time', 'applicants' => 8, 'status' => 'Active'],
            ['title' => 'HR Executive', 'dept' => 'HR/Admin', 'type' => 'Full Time', 'applicants' => 22, 'status' => 'Active'],
            ['title' => 'UI/UX Designer', 'dept' => 'Creative', 'type' => 'Contract', 'applicants' => 11, 'status' => 'Draft'],
        ];

        return view('recruitment::index', compact('jobs'));
    }
}
