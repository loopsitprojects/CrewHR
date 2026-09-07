<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }
}
