<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AuthController extends Controller
{
    // Show login page — now served through Inertia/inertia.blade.php
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    // Handle login POST — unchanged, session redirect still works with Inertia
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return $this->redirectBasedOnRole(Auth::user());
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // Show register page — now served through Inertia/inertia.blade.php.
    // Carries the plan query param through from the pricing page, if any,
    // so Register.vue can pre-fill and display which plan was picked.
    public function showRegister(Request $request)
    {
        return Inertia::render('Auth/Register', [
            'plan' => $request->query('plan'),
        ]);
    }

    // Shown after a "create new institution" submission. No live account
    // exists yet at this point, so there's nothing to log into — the
    // request sits pending until a superadmin reviews it.
    public function showRequestPending()
    {
        return Inertia::render('Auth/RequestPending');
    }

    // Handle register POST — branches on institution mode:
    //   'join'   → pick an existing, active institution, becomes a student
    //              there immediately, unchanged from before.
    //   'create' → no longer creates a live institution directly. Files an
    //              InstitutionRequest for a superadmin to review instead,
    //              so nobody can spin up an institution (and its own admin
    //              account) unapproved. The actual creation now only
    //              happens via InstitutionRequest::approve().
    public function register(Request $request)
    {
        $validated = $request->validate([
            'mode'                      => ['required', 'in:join,create'],
            'institution_id'            => ['required_if:mode,join', 'nullable', 'exists:institutions,id'],
            'institution_name'          => ['required_if:mode,create', 'nullable', 'string', 'max:255'],
            'institution_contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'plan'                      => ['nullable', 'string', 'in:free,standard,pro,enterprise'],
            'first_name'                => ['required', 'string', 'max:255'],
            'last_name'                 => ['required', 'string', 'max:255'],
            'email'                     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'                  => ['required', 'confirmed', Password::defaults()],
            'terms'                     => ['accepted'],
        ]);

        if ($validated['mode'] === 'join') {
            $institution = Institution::findOrFail($validated['institution_id']);

            if (!$institution->is_active) {
                return back()
                    ->withErrors(['institution_id' => 'This institution is not currently accepting new accounts.'])
                    ->onlyInput('email', 'first_name', 'last_name');
            }

            if (!$institution->canAddUser()) {
                return back()
                    ->withErrors(['institution_id' => 'This institution has reached its user limit and cannot take new accounts right now. Please contact your IT office.'])
                    ->onlyInput('email', 'first_name', 'last_name');
            }

            $user = $this->createUser($validated, $institution->id, 'student');

            Auth::login($user);

            return $this->redirectBasedOnRole($user);
        }

        // 'create' mode — file a request instead of creating anything live.
        if (InstitutionRequest::where('email', $validated['email'])->where('status', 'pending')->exists()) {
            return back()
                ->withErrors(['email' => 'A request for this email is already pending review.'])
                ->onlyInput('first_name', 'last_name', 'institution_name', 'institution_contact_email');
        }

        InstitutionRequest::create([
            'institution_name'          => $validated['institution_name'],
            'institution_contact_email' => $validated['institution_contact_email'] ?: $validated['email'],
            'plan'                      => $validated['plan'] ?? null,
            'first_name'                => $validated['first_name'],
            'last_name'                 => $validated['last_name'],
            'email'                     => $validated['email'],
            'password'                  => Hash::make($validated['password']),
            'status'                    => 'pending',
        ]);

        return redirect()->route('register.pending');
    }

    private function createUser(array $validated, int $institutionId, string $role): User
    {
        return User::create([
            'institution_id' => $institutionId,
            'first_name'     => $validated['first_name'],
            'last_name'      => $validated['last_name'],
            'email'          => $validated['email'],
            'role'           => $role,
            'password'       => Hash::make($validated['password']),
        ]);
    }

    // Handle logout POST — unchanged
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    private function redirectBasedOnRole(User $user)
    {
        return match($user->role) {
            'superadmin' => redirect()->route('superadmin.institutions.index'),
            'admin'      => redirect()->route('admin.dashboard'),
            'it-support' => redirect()->route('it.dashboard'),
            default      => redirect()->route('user.dashboard'),
        };
    }
}