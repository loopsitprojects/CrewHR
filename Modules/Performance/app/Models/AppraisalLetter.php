<?php

namespace Modules\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Modules\Employee\Models\Employee;

class AppraisalLetter extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'signatory_signed_at' => 'datetime',
        'employee_acknowledged_at' => 'datetime',
    ];

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function signatoryUser()
    {
        return $this->belongsTo(User::class, 'signatory_user_id');
    }
}
