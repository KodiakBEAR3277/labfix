<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;

/**
 * Blocks ticket submission while an institution is in maintenance mode.
 * Attach it only to the routes that create tickets (the web store route
 * and the mobile POST /tickets). Viewing and everything else stays open.
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        // Admins bypass so they can still test the form while it is on.
        if ($request->user() && $request->user()->isAdmin()) {
            return $next($request);
        }

        if (Setting::get('maintenance_mode', false)) {
            $message = Setting::get('maintenance_message')
                ?: 'Ticket submission is temporarily unavailable for maintenance. Please try again later.';

            // Mobile app: a 503 with the message, which the report screen displays.
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'maintenance' => true], 503);
            }

            // Web: back to the create page, which shows the maintenance notice.
            return redirect()
                ->route('user.reports.create')
                ->with('error', $message);
        }

        return $next($request);
    }
}