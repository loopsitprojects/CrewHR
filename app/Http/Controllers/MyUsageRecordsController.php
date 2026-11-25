<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Leave\Services\LeavePolicyService;
use Carbon\Carbon;

class MyUsageRecordsController extends Controller
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
        $user = auth()->user();
        $loggedEmployee = $user ? (Employee::where('user_id', $user->id)->first() ?? Employee::first()) : Employee::first();

        // Standardized Role Mapping
        $sessionRole = session('current_role');
        $rawRole = $sessionRole ?? ($loggedEmployee->system_role ?? 'Employee');
        $role = match($rawRole) {
            'Super (Admin)', 'Super Admin' => 'Super (Admin)',
            'HR Lead', 'HR', 'Admin' => 'HR Lead',
            'Manager (Team Approvals)', 'HOD / Manager', 'Manager' => 'HOD / Manager',
            default => 'Employee',
        };

        $isSuperAdmin = $role === 'Super (Admin)';
        $isHrLead = $role === 'HR Lead';
        $isManager = $role === 'HOD / Manager';
        $isEmployee = $role === 'Employee';

        // Manager's Team Subordinates (reports_to or same department)
        $managerTeamIds = [];
        if ($isManager && $loggedEmployee) {
            $managerTeamIds = Employee::query()
                ->where(function($q) use ($loggedEmployee) {
                    $q->where('reporting_person_id', $loggedEmployee->id);
                    if ($loggedEmployee->department_id) {
                        $q->orWhere('department_id', $loggedEmployee->department_id);
                    }
                })
                ->pluck('id')
                ->toArray();

            if (!in_array($loggedEmployee->id, $managerTeamIds)) {
                $managerTeamIds[] = $loggedEmployee->id;
            }
        }

        // Scope Resolution:
        // Employee: strictly 'my'
        // Admin / HR / Manager: default 'my', can switch to 'all'/'team' or choose specific 'employee'
        if ($isEmployee) {
            $scope = 'my';
        } else {
            $scope = $request->query('scope');
            if (!$scope) {
                $scope = 'my';
            } elseif ($scope === 'employee') {
                $scope = $isManager ? 'team' : 'all';
            }

            // Security guardrails against tampering
            if ($scope === 'all' && !$isSuperAdmin && !$isHrLead) {
                $scope = $isManager ? 'team' : 'my';
            }
            if ($scope === 'team' && !$isManager && !$isSuperAdmin && !$isHrLead) {
                $scope = 'my';
            }
        }

        // Target Employee & Department Selection
        $departmentId = $request->query('department_id');
        $targetEmployeeId = $request->query('employee_id');
        $targetEmployee = null;

        if ($scope === 'my') {
            $targetEmployee = $loggedEmployee;
        } elseif ($targetEmployeeId) {
            if ($isSuperAdmin || $isHrLead) {
                $targetEmployee = Employee::with(['user', 'department', 'designation'])->find($targetEmployeeId);
            } elseif ($isManager && in_array((int)$targetEmployeeId, $managerTeamIds)) {
                $targetEmployee = Employee::with(['user', 'department', 'designation'])->find($targetEmployeeId);
            }
        }

        if (!$departmentId && $targetEmployee && $targetEmployee->department_id && ($isSuperAdmin || $isHrLead) && $scope === 'all') {
            $departmentId = $targetEmployee->department_id;
        }
        $selectedDepartment = $departmentId ? Department::find($departmentId) : null;

        // Dropdown Selectable Employees based on Role & Department
        $selectableEmployees = collect([]);
        if ($isSuperAdmin || $isHrLead) {
            $selectableQuery = Employee::with(['user', 'department', 'designation']);
            if ($departmentId) {
                $selectableQuery->where('department_id', $departmentId);
            } elseif ($scope === 'all') {
                $selectableQuery->whereRaw('1 = 0');
            }
            $selectableEmployees = $selectableQuery->get()
                ->sortBy(fn($e) => $e->user->name ?? '', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } elseif ($isManager) {
            $selectableEmployees = Employee::with(['user', 'department', 'designation'])
                ->whereIn('id', $managerTeamIds)
                ->get()
                ->sortBy(fn($e) => $e->user->name ?? '', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        }

        // Determine which employee IDs are in scope for queries
        if ($scope === 'my') {
            $scopeEmployeeIds = [$loggedEmployee->id ?? 1];
        } elseif ($scope === 'team') {
            $scopeEmployeeIds = $managerTeamIds;
        } else {
            // 'all': Only show employees if a specific department is selected
            if ($departmentId) {
                $scopeEmployeeIds = Employee::where('department_id', $departmentId)->pluck('id')->toArray();
            } else {
                $scopeEmployeeIds = [];
            }
        }

        $isIndividualView = ($scope === 'my');

        // Date & Filter parameters
        $currentMonth = $now->month;
        $currentYear = $now->year;

        $year = (int)$request->query('year', $currentYear);
        $status = $request->query('status', 'all');
        $leaveTypeId = $request->query('leave_type_id');
        $period = $request->query('period');
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Month filter: defaults to current month unless explicitly filtered
        if ($request->has('month')) {
            $month = $request->query('month');
        } elseif ($period) {
            $month = $period;
        } elseif ($startDate || $endDate) {
            $month = 'all';
        } else {
            $month = (string)$currentMonth;
        }

        // Ensure Leave Balances exist
        if ($targetEmployee) {
            $this->ensureEmployeeLeaveBalances($targetEmployee, $year);
        }

        // Active Leave Types
        $activeLeaveTypes = LeaveType::where('is_active', true)->orderBy('id')->get();
        $summaryCards = [];
        $totalEligibleDays = 0;
        $totalDaysTaken = 0;

        if ($targetEmployee) {
            // Individual Balances
            $leaveBalances = EmployeeLeaveBalance::with('leaveType')
                ->where('employee_id', $targetEmployee->id)
                ->where('year', $year)
                ->whereHas('leaveType', fn($q) => $q->where('is_active', true))
                ->get();

            $balancesByCode = $leaveBalances->keyBy(fn($b) => strtoupper($b->leaveType->code ?? ''));

            $monthlyShortLeavesUsed = LeaveRequest::where('employee_id', $targetEmployee->id)
                ->where(function($q) {
                    $q->where('is_short_leave', true)
                      ->orWhereHas('leaveType', fn($t) => $t->where('code', 'SHORT'));
                })
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $currentMonth)
                ->whereNotIn('status', ['Rejected', 'Canceled', 'Cancelled'])
                ->count();

            foreach ($activeLeaveTypes as $lt) {
                $code = strtoupper($lt->code ?? '');
                if (in_array($code, ['HALF', 'HALF_DAY'])) continue;

                $bal = $balancesByCode->get($code);
                $theme = $this->getThemeForCode($code);
                $icon = $this->getIconForCode($code);

                if ($code === 'SHORT') {
                    $eligibleNum = 2;
                    $takenNum = $monthlyShortLeavesUsed;
                    $balanceNum = max(0, 2 - $takenNum);
                    $unit = 'Slots';
                    $eligibleLabel = '2 per Month';
                    $conditionBadge = '2 per Month';
                    $pctUsed = min(100, round(($takenNum / 2) * 100));
                } elseif ($code === 'NIGHT_SHIFT_RECOVERY') {
                    $allocated = (float)($bal->allocated ?? 0);
                    $eligibleNum = $allocated;
                    $takenNum = (float)($bal->used ?? 0);
                    $balanceNum = max(0, $eligibleNum - $takenNum);
                    $unit = 'Days';
                    $eligibleLabel = $eligibleNum . ' Days';
                    $conditionBadge = 'Shift Rule Concession';
                    $pctUsed = $eligibleNum > 0 ? min(100, round(($takenNum / $eligibleNum) * 100)) : 0;
                } else {
                    $allocated = (float)($bal->allocated ?? $lt->days ?? 0);
                    $cf = (float)($bal->carried_forward ?? 0);
                    $eligibleNum = $allocated + $cf;
                    $takenNum = (float)($bal->used ?? 0);
                    $balanceNum = max(0, $eligibleNum - $takenNum);
                    $unit = 'Days';
                    $eligibleLabel = $eligibleNum . ' Days';
                    $conditionBadge = $cf > 0 ? "+{$cf} Carry Forward Included" : ($code === 'LIEU' ? 'Claim-Based' : ($code === 'DUTY' ? 'Max 30 Days' : null));
                    $pctUsed = $eligibleNum > 0 ? min(100, round(($takenNum / $eligibleNum) * 100)) : 0;

                    if (!in_array($code, ['DUTY', 'NO_PAY'])) {
                        $totalEligibleDays += $eligibleNum;
                        $totalDaysTaken += $takenNum;
                    }
                }

                $pctLeft = max(0, 100 - $pctUsed);

                $summaryCards[] = [
                    'id' => $lt->id,
                    'name' => $lt->name,
                    'code' => $code,
                    'icon' => $icon,
                    'theme' => $theme,
                    'eligible_num' => $eligibleNum,
                    'eligible_label' => $eligibleLabel,
                    'taken_num' => $takenNum,
                    'taken_label' => $takenNum . ' ' . $unit,
                    'balance_num' => $balanceNum,
                    'balance_label' => $balanceNum . ' ' . $unit,
                    'unit' => $unit,
                    'pct_used' => $pctUsed,
                    'pct_left' => $pctLeft,
                    'badge' => $conditionBadge,
                ];
            }
        } else {
            // Organization / Team Aggregate Balances Pool
            $leaveBalances = EmployeeLeaveBalance::with('leaveType')
                ->whereIn('employee_id', $scopeEmployeeIds)
                ->where('year', $year)
                ->whereHas('leaveType', fn($q) => $q->where('is_active', true))
                ->get();

            $balancesGrouped = $leaveBalances->groupBy(fn($b) => strtoupper($b->leaveType->code ?? ''));

            foreach ($activeLeaveTypes as $lt) {
                $code = strtoupper($lt->code ?? '');
                if (in_array($code, ['HALF', 'HALF_DAY'])) continue;

                $group = $balancesGrouped->get($code, collect([]));
                $theme = $this->getThemeForCode($code);
                $icon = $this->getIconForCode($code);

                $allocatedSum = $group->sum(fn($b) => (float)($b->allocated ?? 0) + (float)($b->carried_forward ?? 0));
                $takenSum = $group->sum(fn($b) => (float)($b->used ?? 0));
                $balanceSum = max(0, $allocatedSum - $takenSum);
                $unit = $code === 'SHORT' ? 'Slots' : 'Days';
                $pctUsed = $allocatedSum > 0 ? min(100, round(($takenSum / $allocatedSum) * 100)) : 0;
                $pctLeft = max(0, 100 - $pctUsed);

                if (!in_array($code, ['DUTY', 'NO_PAY', 'SHORT'])) {
                    $totalEligibleDays += $allocatedSum;
                    $totalDaysTaken += $takenSum;
                }

                $summaryCards[] = [
                    'id' => $lt->id,
                    'name' => $lt->name,
                    'code' => $code,
                    'icon' => $icon,
                    'theme' => $theme,
                    'eligible_num' => $allocatedSum,
                    'eligible_label' => $allocatedSum . ' ' . $unit,
                    'taken_num' => $takenSum,
                    'taken_label' => $takenSum . ' ' . $unit,
                    'balance_num' => $balanceSum,
                    'balance_label' => $balanceSum . ' ' . $unit,
                    'unit' => $unit,
                    'pct_used' => $pctUsed,
                    'pct_left' => $pctLeft,
                    'badge' => count($scopeEmployeeIds) . ' Employees in Scope',
                ];
            }
        }

        // Individual Employee Balances for Multi-Employee Scope (Team, Dept, All)
        $employeeBalancesList = [];
        if (!$isIndividualView) {
            $scopeEmployees = Employee::with(['user', 'department', 'designation'])
                ->whereIn('id', $scopeEmployeeIds)
                ->get()
                ->sortBy(fn($e) => $e->user->name ?? '', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            foreach ($scopeEmployees as $emp) {
                $this->ensureEmployeeLeaveBalances($emp, $year);
                $emp->load(['leaveBalances' => fn($q) => $q->where('year', $year)->with('leaveType')]);

                $empBalances = $emp->leaveBalances->keyBy(fn($b) => strtoupper($b->leaveType->code ?? ''));

                $annualBal = $empBalances->get('ANNUAL');
                $annualAlloc = (float)($annualBal->allocated ?? 0) + (float)($annualBal->carried_forward ?? 0);
                $annualTaken = (float)($annualBal->used ?? 0);
                $annualRem = max(0, $annualAlloc - $annualTaken);

                $casualBal = $empBalances->get('CASUAL');
                $casualAlloc = (float)($casualBal->allocated ?? 0);
                $casualTaken = (float)($casualBal->used ?? 0);
                $casualRem = max(0, $casualAlloc - $casualTaken);

                $medicalBal = $empBalances->get('MEDICAL');
                $medicalAlloc = (float)($medicalBal->allocated ?? 0);
                $medicalTaken = (float)($medicalBal->used ?? 0);
                $medicalRem = max(0, $medicalAlloc - $medicalTaken);

                $shortTaken = LeaveRequest::where('employee_id', $emp->id)
                    ->where(function($q) {
                        $q->where('is_short_leave', true)
                          ->orWhereHas('leaveType', fn($lt) => $lt->where('code', 'SHORT'));
                    })
                    ->whereYear('start_date', $year)
                    ->whereMonth('start_date', $month === 'all' || !is_numeric($month) ? $currentMonth : (int)$month)
                    ->whereIn('status', ['Approved', 'HR Approved', 'Pending', 'Pending Manager Approval'])
                    ->count();
                $shortRem = max(0, 2 - $shortTaken);

                $totalTaken = $annualTaken + $casualTaken + $medicalTaken;
                $netBalance = $annualRem + $casualRem + $medicalRem;
                $isSelected = ($targetEmployee && $emp->id === $targetEmployee->id);

                $employeeBalancesList[] = [
                    'id' => $emp->id,
                    'name' => $emp->user->name ?? ($emp->first_name . ' ' . $emp->last_name),
                    'emp_id' => $emp->employee_id_number ?? ('EMP-' . $emp->id),
                    'department' => $emp->department->name ?? 'Staff',
                    'designation' => $emp->designation->name ?? '',
                    'initial' => strtoupper(substr($emp->user->name ?? $emp->first_name ?? 'E', 0, 1)),
                    'profile_picture' => $emp->profile_picture,
                    'username' => $emp->user->username ?? '',
                    'annual' => ['alloc' => $annualAlloc, 'taken' => $annualTaken, 'rem' => $annualRem],
                    'casual' => ['alloc' => $casualAlloc, 'taken' => $casualTaken, 'rem' => $casualRem],
                    'medical' => ['alloc' => $medicalAlloc, 'taken' => $medicalTaken, 'rem' => $medicalRem],
                    'short' => ['taken' => $shortTaken, 'rem' => $shortRem],
                    'total_taken' => $totalTaken,
                    'net_balance' => $netBalance,
                    'is_selected' => $isSelected,
                    'inspect_url' => route('my-usage-records.index', array_filter([
                        'scope' => $scope,
                        'employee_id' => $isSelected ? null : $emp->id,
                        'department_id' => $departmentId,
                        'year' => $year,
                        'month' => $month,
                        'status' => $status
                    ]))
                ];
            }
        }

        // Leave Usage Records Query
        $usageQuery = LeaveRequest::with(['leaveType', 'coveringEmployee.user', 'employee.user', 'employee.department', 'employee.designation']);

        if ($targetEmployee) {
            $usageQuery->where('employee_id', $targetEmployee->id);
        } else {
            $usageQuery->whereIn('employee_id', $scopeEmployeeIds);
            if ($departmentId) {
                $usageQuery->whereHas('employee', fn($eq) => $eq->where('department_id', $departmentId));
            }
        }

        // Date Filtering
        if ($startDate && $endDate) {
            $usageQuery->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            });
        } elseif ($startDate) {
            $usageQuery->where('start_date', '>=', $startDate);
        } elseif ($endDate) {
            $usageQuery->where('start_date', '<=', $endDate);
        } else {
            if ($year) {
                $usageQuery->whereYear('start_date', $year);
            }
            if ($month && $month !== 'all' && is_numeric($month)) {
                $usageQuery->whereMonth('start_date', (int)$month);
            }
        }

        if ($leaveTypeId) {
            $usageQuery->where('leave_type_id', $leaveTypeId);
        }

        if ($search) {
            $usageQuery->where(function ($q) use ($search) {
                $q->where('req_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('project_client_name', 'like', "%{$search}%")
                  ->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('coveringEmployee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('leaveType', fn($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if ($status === 'approved') {
            $usageQuery->where(function($q) {
                $q->where('status', 'Approved')
                  ->orWhere('manager_status', 'Approved');
            });
        } elseif ($status === 'pending') {
            $usageQuery->where(function($q) {
                $q->whereIn('status', ['Pending', 'Pending Covering Approval', 'Pending Manager Approval', 'Step 2: Pending HR Admin'])
                  ->orWhere('manager_status', 'Pending');
            });
        } elseif ($status === 'rejected') {
            $usageQuery->where(function($q) {
                $q->where('status', 'Rejected')
                  ->orWhere('manager_status', 'Rejected')
                  ->orWhere('covering_status', 'Rejected');
            });
        } elseif ($status === 'canceled') {
            $usageQuery->whereIn('status', ['Canceled', 'Cancelled']);
        }

        $usageRecords = $usageQuery->latest('start_date')->get();

        // Count queries for status tabs
        $baseCountQuery = LeaveRequest::query();
        if ($targetEmployee) {
            $baseCountQuery->where('employee_id', $targetEmployee->id);
        } else {
            $baseCountQuery->whereIn('employee_id', $scopeEmployeeIds);
            if ($departmentId) {
                $baseCountQuery->whereHas('employee', fn($eq) => $eq->where('department_id', $departmentId));
            }
        }

        if ($startDate && $endDate) {
            $baseCountQuery->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            });
        } elseif ($startDate) {
            $baseCountQuery->where('start_date', '>=', $startDate);
        } elseif ($endDate) {
            $baseCountQuery->where('start_date', '<=', $endDate);
        } else {
            if ($year) {
                $baseCountQuery->whereYear('start_date', $year);
            }
            if ($month && $month !== 'all' && is_numeric($month)) {
                $baseCountQuery->whereMonth('start_date', (int)$month);
            }
        }

        if ($leaveTypeId) {
            $baseCountQuery->where('leave_type_id', $leaveTypeId);
        }
        if ($search) {
            $baseCountQuery->where(function ($q) use ($search) {
                $q->where('req_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('project_client_name', 'like', "%{$search}%")
                  ->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('coveringEmployee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('leaveType', fn($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'approved' => (clone $baseCountQuery)->where(function($q) {
                $q->where('status', 'Approved')->orWhere('manager_status', 'Approved');
            })->count(),
            'pending' => (clone $baseCountQuery)->where(function($q) {
                $q->whereIn('status', ['Pending', 'Pending Covering Approval', 'Pending Manager Approval', 'Step 2: Pending HR Admin'])
                  ->orWhere('manager_status', 'Pending');
            })->count(),
            'rejected' => (clone $baseCountQuery)->where(function($q) {
                $q->where('status', 'Rejected')->orWhere('manager_status', 'Rejected')->orWhere('covering_status', 'Rejected');
            })->count(),
            'canceled' => (clone $baseCountQuery)->whereIn('status', ['Canceled', 'Cancelled'])->count(),
        ];

        // Header Metrics
        $pendingApprovalsCount = LeaveRequest::whereIn('employee_id', $targetEmployee ? [$targetEmployee->id] : $scopeEmployeeIds)
            ->where(function($q) {
                $q->whereIn('status', ['Pending', 'Pending Covering Approval', 'Pending Manager Approval', 'Step 2: Pending HR Admin'])
                  ->orWhere('manager_status', 'Pending');
            })->count();

        $totalBalanceDays = max(0, $totalEligibleDays - $totalDaysTaken);

        // List of colleagues for Covering Person dropdown
        $colleagues = Employee::with('user')
            ->where('id', '!=', $loggedEmployee->id ?? 0)
            ->get()
            ->sortBy(fn($e) => $e->user->name ?? '');

        // Departments for filtering
        $departments = Department::orderBy('name')->get()->unique('name');

        $yearsList = range($now->year - 2, $now->year + 1);
        $monthsList = [
            'all' => 'All Months',
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $periodsList = [
            'all' => 'All Periods',
            'q1' => 'Q1 (Jan – Mar)',
            'q2' => 'Q2 (Apr – Jun)',
            'q3' => 'Q3 (Jul – Sep)',
            'q4' => 'Q4 (Oct – Dec)',
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return view('pages.my_usage_records', compact(
            'loggedEmployee',
            'targetEmployee',
            'role',
            'isSuperAdmin',
            'isHrLead',
            'isManager',
            'isEmployee',
            'scope',
            'isIndividualView',
            'selectableEmployees',
            'departments',
            'departmentId',
            'selectedDepartment',
            'summaryCards',
            'usageRecords',
            'counts',
            'status',
            'year',
            'month',
            'period',
            'startDate',
            'endDate',
            'currentMonth',
            'currentYear',
            'leaveTypeId',
            'search',
            'yearsList',
            'monthsList',
            'periodsList',
            'activeLeaveTypes',
            'colleagues',
            'totalEligibleDays',
            'totalDaysTaken',
            'totalBalanceDays',
            'pendingApprovalsCount',
            'scopeEmployeeIds',
            'employeeBalancesList'
        ));
    }

    private function getThemeForCode(string $code): array
    {
        return match($code) {
            'ANNUAL' => [
                'accent' => 'emerald',
                'border' => 'border-emerald-200 dark:border-emerald-800/80',
                'hover' => 'hover:border-emerald-400 dark:hover:border-emerald-600',
                'badge' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
                'bar' => 'bg-emerald-500',
                'bar_bg' => 'bg-emerald-100 dark:bg-emerald-950',
                'icon_bg' => 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/40 shadow-xs shadow-emerald-500/10 dark:shadow-[0_0_12px_rgba(16,185,129,0.25)]',
                'icon_text' => 'text-emerald-600 dark:text-emerald-300'
            ],
            'CASUAL' => [
                'accent' => 'purple',
                'border' => 'border-purple-200 dark:border-purple-800/80',
                'hover' => 'hover:border-purple-400 dark:hover:border-purple-600',
                'badge' => 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800',
                'bar' => 'bg-purple-500',
                'bar_bg' => 'bg-purple-100 dark:bg-purple-950',
                'icon_bg' => 'bg-purple-500/10 dark:bg-purple-500/20 text-purple-600 dark:text-purple-300 border border-purple-200 dark:border-purple-500/40 shadow-xs shadow-purple-500/10 dark:shadow-[0_0_12px_rgba(168,85,247,0.25)]',
                'icon_text' => 'text-purple-600 dark:text-purple-300'
            ],
            'MEDICAL' => [
                'accent' => 'rose',
                'border' => 'border-rose-200 dark:border-rose-800/80',
                'hover' => 'hover:border-rose-400 dark:hover:border-rose-600',
                'badge' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
                'bar' => 'bg-rose-500',
                'bar_bg' => 'bg-rose-100 dark:bg-rose-950',
                'icon_bg' => 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-500/40 shadow-xs shadow-rose-500/10 dark:shadow-[0_0_12px_rgba(244,63,94,0.25)]',
                'icon_text' => 'text-rose-600 dark:text-rose-300'
            ],
            'SHORT' => [
                'accent' => 'amber',
                'border' => 'border-amber-200 dark:border-amber-800/80',
                'hover' => 'hover:border-amber-400 dark:hover:border-amber-600',
                'badge' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                'bar' => 'bg-amber-500',
                'bar_bg' => 'bg-amber-100 dark:bg-amber-950',
                'icon_bg' => 'bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-200 dark:border-amber-500/40 shadow-xs shadow-amber-500/10 dark:shadow-[0_0_12px_rgba(245,158,11,0.25)]',
                'icon_text' => 'text-amber-600 dark:text-amber-300'
            ],
            'DUTY' => [
                'accent' => 'indigo',
                'border' => 'border-indigo-200 dark:border-indigo-800/80',
                'hover' => 'hover:border-indigo-400 dark:hover:border-indigo-600',
                'badge' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800',
                'bar' => 'bg-indigo-500',
                'bar_bg' => 'bg-indigo-100 dark:bg-indigo-950',
                'icon_bg' => 'bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/40 shadow-xs shadow-indigo-500/10 dark:shadow-[0_0_12px_rgba(99,102,241,0.25)]',
                'icon_text' => 'text-indigo-600 dark:text-indigo-300'
            ],
            'LIEU' => [
                'accent' => 'teal',
                'border' => 'border-teal-200 dark:border-teal-800/80',
                'hover' => 'hover:border-teal-400 dark:hover:border-teal-600',
                'badge' => 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800',
                'bar' => 'bg-teal-500',
                'bar_bg' => 'bg-teal-100 dark:bg-teal-950',
                'icon_bg' => 'bg-teal-500/10 dark:bg-teal-500/20 text-teal-600 dark:text-teal-300 border border-teal-200 dark:border-teal-500/40 shadow-xs shadow-teal-500/10 dark:shadow-[0_0_12px_rgba(20,184,166,0.25)]',
                'icon_text' => 'text-teal-600 dark:text-teal-300'
            ],
            'NIGHT_SHIFT_RECOVERY' => [
                'accent' => 'sky',
                'border' => 'border-sky-200 dark:border-sky-800/80',
                'hover' => 'hover:border-sky-400 dark:hover:border-sky-600',
                'badge' => 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800',
                'bar' => 'bg-sky-500',
                'bar_bg' => 'bg-sky-100 dark:bg-sky-950',
                'icon_bg' => 'bg-sky-500/10 dark:bg-sky-500/20 text-sky-600 dark:text-sky-300 border border-sky-200 dark:border-sky-500/40 shadow-xs shadow-sky-500/10 dark:shadow-[0_0_12px_rgba(14,165,233,0.25)]',
                'icon_text' => 'text-sky-600 dark:text-sky-300'
            ],
            'NO_PAY' => [
                'accent' => 'slate',
                'border' => 'border-slate-200 dark:border-slate-800/80',
                'hover' => 'hover:border-slate-400 dark:hover:border-slate-600',
                'badge' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700',
                'bar' => 'bg-slate-500',
                'bar_bg' => 'bg-slate-200 dark:bg-slate-800',
                'icon_bg' => 'bg-slate-500/10 dark:bg-slate-500/20 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-500/40 shadow-xs shadow-slate-500/10 dark:shadow-[0_0_12px_rgba(148,163,184,0.2)]',
                'icon_text' => 'text-slate-600 dark:text-slate-300'
            ],
            'MATERNITY' => [
                'accent' => 'pink',
                'border' => 'border-pink-200 dark:border-pink-800/80',
                'hover' => 'hover:border-pink-400 dark:hover:border-pink-600',
                'badge' => 'bg-pink-50 dark:bg-pink-950/60 text-pink-700 dark:text-pink-300 border border-pink-200 dark:border-pink-800',
                'bar' => 'bg-pink-500',
                'bar_bg' => 'bg-pink-100 dark:bg-pink-950',
                'icon_bg' => 'bg-pink-500/10 dark:bg-pink-500/20 text-pink-600 dark:text-pink-300 border border-pink-200 dark:border-pink-500/40 shadow-xs shadow-pink-500/10 dark:shadow-[0_0_12px_rgba(236,72,153,0.25)]',
                'icon_text' => 'text-pink-600 dark:text-pink-300'
            ],
            'PATERNITY' => [
                'accent' => 'cyan',
                'border' => 'border-cyan-200 dark:border-cyan-800/80',
                'hover' => 'hover:border-cyan-400 dark:hover:border-cyan-600',
                'badge' => 'bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800',
                'bar' => 'bg-cyan-500',
                'bar_bg' => 'bg-cyan-100 dark:bg-cyan-950',
                'icon_bg' => 'bg-cyan-500/10 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-500/40 shadow-xs shadow-cyan-500/10 dark:shadow-[0_0_12px_rgba(6,182,212,0.25)]',
                'icon_text' => 'text-cyan-600 dark:text-cyan-300'
            ],
            'HOSPITALISATION' => [
                'accent' => 'red',
                'border' => 'border-red-200 dark:border-red-800/80',
                'hover' => 'hover:border-red-400 dark:hover:border-red-600',
                'badge' => 'bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800',
                'bar' => 'bg-red-500',
                'bar_bg' => 'bg-red-100 dark:bg-red-950',
                'icon_bg' => 'bg-red-500/10 dark:bg-red-500/20 text-red-600 dark:text-red-300 border border-red-200 dark:border-red-500/40 shadow-xs shadow-red-500/10 dark:shadow-[0_0_12px_rgba(239,68,68,0.25)]',
                'icon_text' => 'text-red-600 dark:text-red-300'
            ],
            default => [
                'accent' => 'blue',
                'border' => 'border-blue-200 dark:border-blue-800/80',
                'hover' => 'hover:border-blue-400 dark:hover:border-blue-600',
                'badge' => 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
                'bar' => 'bg-blue-500',
                'bar_bg' => 'bg-blue-100 dark:bg-blue-950',
                'icon_bg' => 'bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 border border-blue-200 dark:border-blue-500/40 shadow-xs shadow-blue-500/10 dark:shadow-[0_0_12px_rgba(59,130,246,0.25)]',
                'icon_text' => 'text-blue-600 dark:text-blue-300'
            ]
        };
    }

    private function getIconForCode(string $code): string
    {
        return match($code) {
            'ANNUAL' => 'palmtree',
            'CASUAL' => 'calendar-check',
            'MEDICAL' => 'stethoscope',
            'SHORT' => 'clock',
            'LIEU' => 'award',
            'DUTY' => 'briefcase',
            'NIGHT_SHIFT_RECOVERY' => 'moon',
            'NO_PAY' => 'circle-slash',
            'MATERNITY' => 'baby',
            'PATERNITY' => 'users',
            'HOSPITALISATION' => 'heart-pulse',
            'ELECTION' => 'vote',
            'RELIGIOUS' => 'sun',
            default => 'calendar'
        };
    }

    private function ensureEmployeeLeaveBalances(Employee $employee, int $year): void
    {
        $leaveTypes = LeaveType::where('is_active', true)->get();

        foreach ($leaveTypes as $lt) {
            $code = strtoupper($lt->code ?? '');

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
