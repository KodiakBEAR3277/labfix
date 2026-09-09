<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    // List every institution on the platform, with basic counts —
    // never a merged view of everyone's tickets/labs/users.
    public function index()
    {
        $institutions = Institution::withCount(['users', 'labs'])
            ->orderBy('name')
            ->get()
            ->map(fn ($institution) => [
                'id'         => $institution->id,
                'name'       => $institution->name,
                'slug'       => $institution->slug,
                'is_active'  => $institution->is_active,
                'user_count' => $institution->users_count,
                'lab_count'  => $institution->labs_count,
                'created_at' => $institution->created_at->diffForHumans(),
            ]);

        return Inertia::render('SuperAdmin/Institutions/Index', compact('institutions'));
    }

    // Activate or deactivate an institution platform-wide
    public function toggleStatus(Institution $institution)
    {
        $institution->update(['is_active' => !$institution->is_active]);

        $status = $institution->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('superadmin.institutions.index')
            ->with('success', "{$institution->name} has been {$status}.");
    }
}