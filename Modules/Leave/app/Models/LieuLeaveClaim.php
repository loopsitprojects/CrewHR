<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LieuLeaveClaim extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'worked_date' => 'date',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'hours_worked' => 'decimal:2',
        'credit_days' => 'decimal:2',
        'is_used' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class);
    }

    public function managerEmployee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class, 'manager_employee_id');
    }

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
