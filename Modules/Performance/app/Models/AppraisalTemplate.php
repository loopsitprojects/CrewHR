<?php

namespace Modules\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;

class AppraisalTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function criteria()
    {
        return $this->hasMany(AppraisalTemplateCriterion::class, 'template_id')->orderBy('sort_order');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function appraisals()
    {
        return $this->hasMany(Appraisal::class, 'template_id');
    }
}
