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
    ];

    public function appraisals()
    {
        return $this->hasMany(Appraisal::class, 'cycle_id');
    }
}
