<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Leave\Database\Factories\LeaveRequestFactory;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function employee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class);
    }

    public function coveringEmployee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class, 'covering_employee_id');
    }

    public function managerEmployee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class, 'manager_employee_id');
    }
    // protected $fillable = [];

    // protected static function newFactory(): LeaveRequestFactory
    // {
    //     // return LeaveRequestFactory::new();
    // }
}
