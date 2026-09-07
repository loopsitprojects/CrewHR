<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payroll extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }
}
