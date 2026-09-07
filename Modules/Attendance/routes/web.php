<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;

Route::group([], function () {
    Route::resource('attendances', AttendanceController::class)->names('attendance');
});
