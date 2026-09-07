<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\EmployeeController;

Route::group([], function () {
    Route::resource('employees', EmployeeController::class)->names('employee');
});
