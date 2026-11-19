<?php

namespace Modules\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppraisalCriteriaScore extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'weightage' => 'float',
        'self_rating' => 'integer',
        'manager_rating' => 'integer',
        'final_rating' => 'integer',
        'weighted_score' => 'float',
    ];

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id');
    }
}
