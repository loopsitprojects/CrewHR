<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Employee;
use App\Models\User;

class SalaryAdvance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'month' => 'integer',
        'year' => 'integer',
        'requested_date' => 'date',
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
}

