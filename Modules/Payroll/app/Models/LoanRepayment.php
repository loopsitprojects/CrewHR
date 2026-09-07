<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanRepayment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function loan()
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
}
