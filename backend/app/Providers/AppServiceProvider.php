<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        // Rate limiters — keep abuse (and free-tier quota) in check. Named so
        // routes opt in with ->middleware('throttle:<name>').
        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));

        RateLimiter::for('tickets', fn (Request $r) => Limit::perMinute(20)
            ->by($r->user()?->id ?: $r->ip()));

        RateLimiter::for('cron', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));

        // Blade directive for maintenance mode check
        Blade::if('maintenance', function () {
            return Setting::get('maintenance_mode', false);
        });

        Blade::if('notmaintenance', function () {
            return !Setting::get('maintenance_mode', false);
        });
    }
}