<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Leave\Database\Factories\HolidayFactory;

class Holiday extends Model
{
    use HasFactory;

    protected $guarded = [];

    // protected $fillable = [];

    // protected static function newFactory(): HolidayFactory
    // {
    //     // return HolidayFactory::new();
    // }
}
