<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     * Points to the new inertia.blade.php you created.
     */
    protected $rootView = 'inertia';

    /**
     * Defines the props that are shared by default to all Inertia pages.
     * auth.user is available in every Vue component via usePage().props.auth.user
     * This replaces the need for any fetch() call to get user data in navs.
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
            // Flash messages — mirrors session('success') / session('error') from blade
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }
}