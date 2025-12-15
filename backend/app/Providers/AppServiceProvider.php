<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Models\Setting;

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
        // Blade directive for maintenance mode check
        Blade::if('maintenance', function () {
            return Setting::get('maintenance_mode', false);
        });

        Blade::if('notmaintenance', function () {
            return !Setting::get('maintenance_mode', false);
        });
    }
}