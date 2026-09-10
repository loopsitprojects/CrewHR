<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Employee;

class Attendance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'total_hours' => 'float',
        'regular_hours' => 'float',
        'early_ot_hours' => 'float',
        'late_ot_hours' => 'float',
        'total_ot_hours' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
