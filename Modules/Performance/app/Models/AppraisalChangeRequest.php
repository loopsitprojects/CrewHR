<?php

namespace Modules\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class AppraisalChangeRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'actioned_at' => 'datetime',
    ];

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function actionedBy()
    {
        return $this->belongsTo(User::class, 'actioned_by_user_id');
    }
}
