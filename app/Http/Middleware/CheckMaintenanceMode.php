<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        // Allow admins to bypass maintenance mode
        if (auth()->check() && auth()->user()->isAdmin()) {
            return $next($request);
        }

        // Check if maintenance mode is enabled
        if (Setting::get('maintenance_mode', false)) {
            // Only block report creation, not viewing
            if ($request->routeIs('user.reports.create') || $request->routeIs('user.reports.store')) {
                return redirect()
                    ->route('user.dashboard')
                    ->with('maintenance', Setting::get('maintenance_message'));
            }
        }

        return $next($request);
    }
}