<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Leave\Database\Factories\EmployeeLeaveBalanceFactory;

class EmployeeLeaveBalance extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
    // protected $fillable = [];

    // protected static function newFactory(): EmployeeLeaveBalanceFactory
    // {
    //     // return EmployeeLeaveBalanceFactory::new();
    // }
}
