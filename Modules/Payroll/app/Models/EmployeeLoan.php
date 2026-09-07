<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class);
    }

    public function loanType()
    {
        return $this->belongsTo(LoanType::class, 'loan_type_id');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class, 'employee_loan_id');
    }
}
