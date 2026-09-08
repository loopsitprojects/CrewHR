<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Leave\Database\Factories\LeaveTypeFactory;

class LeaveType extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_paid' => 'boolean',
        'days' => 'integer',
        'default_quota' => 'integer',
    ];
}
