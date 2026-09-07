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

    // Calculated Payroll Properties
    public function getGrossSalaryAttribute(): float
    {
        return (float) ($this->basic_salary + $this->fixed_allowance + $this->other_allowance);
    }

    public function getEpfEmployeeAttribute(): float
    {
        return (float) ($this->basic_salary * 0.08);
    }

    public function getEpfEmployerAttribute(): float
    {
        return (float) ($this->basic_salary * 0.12);
    }

    public function getEtfEmployerAttribute(): float
    {
        return (float) ($this->basic_salary * 0.03);
    }

    public function getNetTakeHomeAttribute(): float
    {
        return (float) ($this->gross_salary - $this->epf_employee - $this->apit_tax);
    }
}
