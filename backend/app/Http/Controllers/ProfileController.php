<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Support\ProfileRules;

class ProfileController extends Controller
{
    // Show profile page
    public function show()
    {
        $user = Auth::user();
        return Inertia::render('Profile/Show', compact('user'));
    }

    // Show edit profile form
    public function edit()
    {
        return redirect()->route('profile.show');
    }

    // Update profile information
    public function update(Request $request)
    {
        $user = Auth::user();

        ProfileRules::normalize($request);

        $validated = $request->validate([
            'first_name' => ProfileRules::name(),
            'last_name' => ProfileRules::name(),
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ProfileRules::phone(),
            'student_staff_id' => ProfileRules::studentId(),
        ], ProfileRules::messages());

        $user->update($validated);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profile updated successfully!');
    }

    // Update password
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Password updated successfully!');
    }

    // Update notification preferences
    public function updatePreferences(Request $request)
    {
        Auth::user()->update([
            'email_notifications' => $request->boolean('email_notifications'),
        ]);
        
        return redirect()->route('profile.show')->with('success', 'Preferences updated successfully!');
    }
}