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
     * auth.user is shared for guests too (returns null when not logged in).
     * auth.user.institution is null until the account has one — true for
     * every account right now, pre-backfill, and true going forward for a
     * genuine superadmin account with no single institution.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'          => $user->id,
                    'first_name'  => $user->first_name,
                    'last_name'   => $user->last_name,
                    'full_name'   => $user->full_name,
                    'initials'    => $user->initials,
                    'email'       => $user->email,
                    'role'        => $user->role,
                    'institution' => $user->institution ? [
                        'id'   => $user->institution->id,
                        'name' => $user->institution->name,
                    ] : null,
                ] : null,
            ],
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }
}