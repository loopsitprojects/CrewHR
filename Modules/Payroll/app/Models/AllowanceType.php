<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AllowanceType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function employeeAllowances()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }
}
