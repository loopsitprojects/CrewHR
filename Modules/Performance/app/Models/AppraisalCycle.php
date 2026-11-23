<?php

namespace Modules\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppraisalCycle extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'self_eval_deadline' => 'date',
        'manager_eval_deadline' => 'date',
        'performance_period_start' => 'date',
        'performance_period_end' => 'date',
        'allow_increments' => 'boolean',
    ];

    public function appraisals()
    {
        return $this->hasMany(Appraisal::class, 'cycle_id');
    }

    /**
     * Check if this is a Mid-Year Review cycle.
     */
    public function isMidYear(): bool
    {
        return $this->cycle_type === 'mid_year' || strtolower($this->review_period) === 'mid-year';
    }

    /**
     * Check if this is a Year-End Review cycle.
     */
    public function isYearEnd(): bool
    {
        return $this->cycle_type === 'year_end' || in_array(strtolower($this->review_period), ['year-end', 'annual']);
    }

    /**
     * Check if salary increments are allowed in this cycle.
     */
    public function allowsIncrements(): bool
    {
        if ($this->isMidYear()) {
            return false;
        }

        return (bool) ($this->allow_increments ?? true);
    }
}
