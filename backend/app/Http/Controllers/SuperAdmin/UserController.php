<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use App\Support\ProfileRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    // Platform-wide user list. Every query drops the institution global scope
    // explicitly: a superadmin has no institution (institution_id = null, so
    // the scope is already inert for them), but stating it makes the intent
    // unambiguous and safe if that ever changes.
    public function index(Request $request)
    {
        $query = User::withoutGlobalScope('institution')->with('institution');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('institution') && $request->institution !== 'all') {
            $query->where('institution_id', $request->institution);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        $unscoped = fn () => User::withoutGlobalScope('institution');

        $stats = [
            'total'      => $unscoped()->count(),
            'admins'     => $unscoped()->where('role', 'admin')->count(),
            'it_support' => $unscoped()->where('role', 'it-support')->count(),
            'staff'      => $unscoped()->where('role', 'staff')->count(),
            'students'   => $unscoped()->where('role', 'student')->count(),
        ];

        return Inertia::render('SuperAdmin/Users/Index', [
            'users'        => $users,
            'stats'        => $stats,
            'filters'      => $request->only(['search', 'role', 'status', 'institution']),
            'institutions' => Institution::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // Show the edit form for any user, including the superadmin themselves.
    public function edit($id)
    {
        $user = User::withoutGlobalScope('institution')->findOrFail($id);

        return Inertia::render('SuperAdmin/Users/Edit', compact('user'));
    }

    // Update any user. A superadmin's role is never changed and a superadmin
    // is never deactivated — that covers editing yourself and prevents
    // locking the platform out of its only oversight account.
    public function update(Request $request, $id)
    {
        $user = User::withoutGlobalScope('institution')->findOrFail($id);
        $targetIsSuperadmin = $user->role === 'superadmin';

        ProfileRules::normalize($request);

        $rules = [
            'first_name'          => ProfileRules::name(),
            'last_name'           => ProfileRules::name(),
            'email'               => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'               => ProfileRules::phone(),
            'student_staff_id'    => ProfileRules::studentId(),
            'password'            => ['nullable', 'confirmed', Password::defaults()],
            'is_active'           => ['boolean'],
            'email_notifications' => ['boolean'],
            'can_submit_tickets'  => ['boolean'],
        ];

        // Role is only editable on ordinary accounts — never on a superadmin.
        if (! $targetIsSuperadmin) {
            $rules['role'] = ['required', 'in:student,staff,it-support,admin'];
        }

        $validated = $request->validate($rules, ProfileRules::messages());

        $updateData = [
            'first_name'          => $validated['first_name'],
            'last_name'           => $validated['last_name'],
            'email'               => $validated['email'],
            'phone'               => $validated['phone'] ?? null,
            'student_staff_id'    => $validated['student_staff_id'] ?? null,
            'email_notifications' => $request->boolean('email_notifications'),
            'can_submit_tickets'  => $request->boolean('can_submit_tickets'),
        ];

        if ($targetIsSuperadmin) {
            $updateData['is_active'] = true;
        } else {
            $updateData['role']      = $validated['role'];
            $updateData['is_active'] = $request->boolean('is_active');
        }

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()
            ->route('superadmin.users.index')
            ->with('success', "{$user->full_name} updated successfully.");
    }

    // Activate or deactivate any non-superadmin account. Superadmins are never
    // deactivated, and nobody can deactivate themselves — same guard rails as
    // update(), applied to the list's inline shortcut.
    public function toggleStatus($id)
    {
        $user = User::withoutGlobalScope('institution')->findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->role === 'superadmin') {
            return back()->with('error', 'Superadmin accounts cannot be deactivated.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "{$user->full_name} has been {$status}.");
    }
}
