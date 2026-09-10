<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class);
    }

    public function loanRepayments()
    {
        return $this->hasMany(LoanRepayment::class, 'payslip_id');
    }

    public function getTotalBasePayResolvedAttribute(): float
    {
        return (float) ($this->total_base_pay ?: ($this->basic_salary + $this->increments_basic + $this->budget_allowance));
    }

    public function getTotalFixedAllowanceResolvedAttribute(): float
    {
        return (float) ($this->total_fixed_allowance ?: ($this->travelling_allowance + $this->cost_of_living_allowance + $this->increments_allowance + $this->fixed_allowance));
    }

    public function getTotalVariablePayResolvedAttribute(): float
    {
        return (float) ($this->total_variable_pay ?: ($this->ot_amount + $this->shift_allowance + $this->incentive_commission + $this->salary_arrears_basic + $this->salary_arrears_allowance));
    }

    public function getTotalEpfContributionAttribute(): float
    {
        return round($this->epf_employee + $this->epf_employer, 2);
    }
}
