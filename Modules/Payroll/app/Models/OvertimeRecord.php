<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Employee;
use App\Models\User;

class OvertimeRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ot_date' => 'date',
        'hours' => 'float',
        'multiplier' => 'float',
        'hourly_rate' => 'float',
        'estimated_amount' => 'float',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
}
