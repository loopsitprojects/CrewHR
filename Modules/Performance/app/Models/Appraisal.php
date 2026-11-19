<?php

namespace Modules\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Designation;

class Appraisal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_locked' => 'boolean',
        'is_promoted' => 'boolean',
        'is_increment_visible_to_employee' => 'boolean',
        'is_increment_visible_to_hod' => 'boolean',
        'self_submitted_at' => 'datetime',
        'manager_submitted_at' => 'datetime',
        'hod_submitted_at' => 'datetime',
        'effective_date' => 'date',
        'total_weighted_score' => 'float',
        'increment_percentage' => 'float',
    ];

    public function cycle()
    {
        return $this->belongsTo(AppraisalCycle::class, 'cycle_id');
    }

    public function template()
    {
        return $this->belongsTo(AppraisalTemplate::class, 'template_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reportingManager()
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    public function hod()
    {
        return $this->belongsTo(Employee::class, 'hod_id');
    }

    public function newDesignation()
    {
        return $this->belongsTo(Designation::class, 'new_designation_id');
    }

    public function criteriaScores()
    {
        return $this->hasMany(AppraisalCriteriaScore::class, 'appraisal_id');
    }

    public function changeRequests()
    {
        return $this->hasMany(AppraisalChangeRequest::class, 'appraisal_id');
    }

    public function letters()
    {
        return $this->hasMany(AppraisalLetter::class, 'appraisal_id');
    }

    /**
     * Compute total score and auto-assign performance band.
     */
    public function recalculateScore(): void
    {
        $scores = $this->criteriaScores;
        $total = 0.00;

        foreach ($scores as $item) {
            // Priority: manager/final rating > self rating if pending manager review
            $rating = $item->final_rating ?? $item->manager_rating ?? 0;
            $weighted = round(($rating * ($item->weightage / 100)), 2);
            $item->weighted_score = $weighted;
            $item->save();

            $total += $weighted;
        }

        $this->total_weighted_score = round($total, 2);
        $this->performance_band = self::scoreToBand($this->total_weighted_score);
        $this->save();
    }

    public static function scoreToBand(float $score): string
    {
        if ($score >= 4.50) return 'Exceptional';
        if ($score >= 4.00) return 'Exceeds Expectations';
        if ($score >= 3.00) return 'Meets Expectations';
        if ($score >= 2.00) return 'Needs Improvement';
        return 'Unsatisfactory';
    }

    public function getPerformanceBandInterpretationAttribute(): string
    {
        return match ($this->performance_band) {
            'Exceptional' => 'Exceeds expectations, leadership potential',
            'Exceeds Expectations' => 'Strong performer',
            'Meets Expectations' => 'Reliable',
            'Needs Improvement' => 'Needs coaching',
            'Unsatisfactory' => 'Immediate action required',
            default => 'Pending Evaluation',
        };
    }
}
