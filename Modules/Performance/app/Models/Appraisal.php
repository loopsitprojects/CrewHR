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
        'is_override_unlocked' => 'boolean',
        'unlocked_until' => 'datetime',
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

    public function unlockedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'unlocked_by_user_id');
    }

    /**
     * Check if employee self-evaluation is locked (due to manual lock or overdue deadline).
     */
    public function isEmployeeSubmissionLocked(): bool
    {
        // Not in self-eval stage -> employee cannot edit
        if ($this->status !== 'pending_self_eval') {
            return true;
        }

        // Active HR override unlock takes precedence
        if ($this->is_override_unlocked) {
            if ($this->unlocked_until && now()->gt($this->unlocked_until)) {
                return true; // Extension expired
            }
            return false; // Still within approved unlock window
        }

        // Explicit manual lock
        if ($this->is_locked) {
            return true;
        }

        // Auto-lock if self-evaluation deadline passed
        if ($this->cycle && $this->cycle->self_eval_deadline) {
            return now()->gt($this->cycle->self_eval_deadline->endOfDay());
        }

        return false;
    }

    /**
     * Check if manager review is locked (due to manual lock or overdue deadline).
     */
    public function isManagerReviewLocked(): bool
    {
        // Not in manager review stage -> manager cannot edit
        if (!in_array($this->status, ['submitted_self_eval', 'pending_manager_eval'])) {
            return true;
        }

        // Active HR override unlock takes precedence
        if ($this->is_override_unlocked) {
            if ($this->unlocked_until && now()->gt($this->unlocked_until)) {
                return true;
            }
            return false;
        }

        // Explicit manual lock
        if ($this->is_locked) {
            return true;
        }

        // Auto-lock if manager evaluation deadline passed
        if ($this->cycle && $this->cycle->manager_eval_deadline) {
            return now()->gt($this->cycle->manager_eval_deadline->endOfDay());
        }

        return false;
    }

    /**
     * Helper to get active deadline for current stage.
     */
    public function currentStageDeadline(): ?\Carbon\Carbon
    {
        if ($this->is_override_unlocked && $this->unlocked_until) {
            return $this->unlocked_until;
        }

        if ($this->status === 'pending_self_eval') {
            return $this->cycle?->self_eval_deadline;
        }

        if (in_array($this->status, ['submitted_self_eval', 'pending_manager_eval'])) {
            return $this->cycle?->manager_eval_deadline;
        }

        return $this->cycle?->end_date;
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
