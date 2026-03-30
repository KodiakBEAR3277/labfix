<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template — all pages now served through inertia.blade.php.
     * entry.blade.php and Vue Router are no longer used.
     */
    protected $rootView = 'inertia';

    /**
     * Shared props available in every Inertia page via usePage().props.
     *
     * auth.user is now shared for guests too (returns null when not logged in).
     * This is required because Landing, Contact, Login, and Register are now
     * Inertia pages — NavLanding reads auth.user to decide whether to show
     * "Dashboard" or "Sign Up", and it can no longer use a fetch() for this
     * since there is no separate Vue Router zone anymore.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id'         => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name'  => $request->user()->last_name,
                    'full_name'  => $request->user()->full_name,
                    'initials'   => $request->user()->initials,
                    'email'      => $request->user()->email,
                    'role'       => $request->user()->role,
                ] : null,
            ],
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }
}