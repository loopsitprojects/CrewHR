<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DayType extends Model
{
    use HasFactory;

    protected $table = 'day_types';

    protected $guarded = [];
}
