<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Employee;

class EmployeeAllowance extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
