<?php

namespace Modules\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppraisalTemplateCriterion extends Model
{
    use HasFactory;

    protected $table = 'appraisal_template_criteria';
    protected $guarded = [];

    protected $casts = [
        'weightage' => 'float',
    ];

    public function template()
    {
        return $this->belongsTo(AppraisalTemplate::class, 'template_id');
    }
}
