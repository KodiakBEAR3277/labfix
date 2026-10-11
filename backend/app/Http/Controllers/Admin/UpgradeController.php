<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UpgradeController extends Controller
{
    // Shows every tier from config/plans.php, same numbers Pricing.vue
    // shows publicly, with the institution's current plan marked. No
    // payment processing here — upgrading is still a manual, talk-to-us
    // step for now, same as approving a new institution is.
    public function index()
    {
        $institution = Auth::user()->institution;

        $tiers = collect(config('plans'))
            ->map(fn ($config, $slug) => array_merge($config, ['slug' => $slug]))
            ->values();

        return Inertia::render('Admin/Upgrade', [
            'currentPlan' => $institution->plan ?: 'free',
            'tiers'       => $tiers,
        ]);
    }
}