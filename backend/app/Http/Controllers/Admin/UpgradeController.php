<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Plans;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UpgradeController extends Controller
{
    // Every tier from App\Support\Plans, with the institution's current plan
    // marked and its live usage against the current limits. No payment
    // processing here: a plan change is made by the superadmin after the
    // institution contacts us.
    public function index()
    {
        $institution = Auth::user()->institution;

        $tiers = collect(Plans::all())
            ->map(fn ($config, $slug) => array_merge($config, ['slug' => $slug]))
            ->values();

        return Inertia::render('Admin/Upgrade', [
            'currentPlan' => $institution->plan ?: 'free',
            'tiers'       => $tiers,
            'usage'       => [
                'labs'      => ['current' => $institution->labs()->count(),      'max' => $institution->labLimit()],
                'equipment' => ['current' => $institution->equipment()->count(), 'max' => $institution->equipmentLimit()],
                'users'     => ['current' => $institution->users()->count(),     'max' => $institution->userLimit()],
            ],
        ]);
    }
}