<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Employee\Database\Factories\EmployeeFactory;

class Employee extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    
    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function reportingPerson()
    {
        return $this->belongsTo(Employee::class, 'reporting_person_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'reporting_person_id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(\Modules\Leave\Models\EmployeeLeaveBalance::class, 'employee_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(\Modules\Leave\Models\LeaveRequest::class, 'employee_id');
    }

    public function employeeAllowances()
    {
        return $this->hasMany(\Modules\Payroll\Models\EmployeeAllowance::class, 'employee_id');
    }

    // Calculated Payroll Properties
    public function getTotalBasePayAttribute(): float
    {
        return (float) ($this->basic_salary + ($this->increments_basic ?: 0) + ($this->budget_allowance ?: 0));
    }

    public function getTotalFixedAllowanceAttribute(): float
    {
        $dynamicTotal = $this->relationLoaded('employeeAllowances') 
            ? (float) $this->employeeAllowances->sum('amount') 
            : (float) $this->employeeAllowances()->sum('amount');

        if ($dynamicTotal > 0) {
            return $dynamicTotal;
        }

        return (float) (($this->travelling_allowance ?: 0) + ($this->cost_of_living_allowance ?: 0) + ($this->increments_allowance ?: 0) + ($this->fixed_allowance ?: 0));
    }

    public function getTotalForEpfAttribute(): float
    {
        return $this->total_base_pay;
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float) ($this->total_base_pay + $this->total_fixed_allowance + ($this->other_allowance ?: 0));
    }

    public function getEpfEmployeeAttribute(): float
    {
        $rate = (float) (\Illuminate\Support\Facades\DB::table('settings')->where('key', 'epf_employee_rate')->value('value') ?? 8.0) / 100;
        return (float) round($this->total_for_epf * $rate, 2);
    }

    public function getEpfEmployerAttribute(): float
    {
        $rate = (float) (\Illuminate\Support\Facades\DB::table('settings')->where('key', 'epf_employer_rate')->value('value') ?? 12.0) / 100;
        return (float) round($this->total_for_epf * $rate, 2);
    }

    public function getEtfEmployerAttribute(): float
    {
        $rate = (float) (\Illuminate\Support\Facades\DB::table('settings')->where('key', 'etf_employer_rate')->value('value') ?? 3.0) / 100;
        return (float) round($this->total_for_epf * $rate, 2);
    }

    public function getNetTakeHomeAttribute(): float
    {
        return (float) max(0, $this->gross_salary - $this->epf_employee - ($this->apit_tax ?: 0));
    }
}
