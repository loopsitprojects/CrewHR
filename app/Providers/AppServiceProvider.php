<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            base_path('Modules/Employee/database/migrations'),
            base_path('Modules/Leave/database/migrations'),
            base_path('Modules/Attendance/database/migrations'),
            base_path('Modules/Payroll/database/migrations'),
            base_path('Modules/Recruitment/database/migrations'),
            base_path('Modules/Performance/database/migrations'),
        ]);
    }
}
