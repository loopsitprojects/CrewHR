<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\PayrollController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('payrolls', PayrollController::class)->names('payroll');
});
