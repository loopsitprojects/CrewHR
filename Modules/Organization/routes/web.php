<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\OrganizationController;

Route::group([], function () {
    Route::resource('organizations', OrganizationController::class)->names('organization');
});
