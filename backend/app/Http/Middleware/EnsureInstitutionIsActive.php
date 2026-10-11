<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstitutionIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only users tied to an institution are checked. Guests and the
        // platform superadmin (institution_id = null) pass straight through.
        if ($user && $user->institution_id && $user->institution && !$user->institution->is_active) {
            // Mobile/API: revoke the token and answer 401 so the app returns to login.
            if ($request->expectsJson()) {
                $token = $user->currentAccessToken();
                if ($token && method_exists($token, 'delete')) {
                    $token->delete();
                }
                return response()->json(['message' => 'This institution has been deactivated.'], 401);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This institution has been deactivated. Please contact LabFix support.',
            ]);
        }

        return $next($request);
    }
}