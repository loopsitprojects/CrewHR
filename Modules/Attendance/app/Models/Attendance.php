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

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'Present' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Late' => 'bg-amber-50 text-amber-700 border-amber-200',
            'Half Day' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'Absent' => 'bg-rose-50 text-rose-700 border-rose-200',
            'On Leave' => 'bg-purple-50 text-purple-700 border-purple-200',
            'Holiday' => 'bg-blue-50 text-blue-700 border-blue-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}
