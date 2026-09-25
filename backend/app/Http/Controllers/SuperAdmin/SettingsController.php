<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/Settings', [
            'settings' => [
                'platform_name'          => Setting::getPlatform('platform_name', 'LabFix'),
                'platform_contact_email' => Setting::getPlatform('platform_contact_email', ''),
                'platform_contact_phone' => Setting::getPlatform('platform_contact_phone', ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'platform_name'          => ['required', 'string', 'max:255'],
            'platform_contact_email' => ['nullable', 'email', 'max:255'],
            'platform_contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        Setting::setPlatform('platform_name', $validated['platform_name']);
        Setting::setPlatform('platform_contact_email', $validated['platform_contact_email'] ?? '');
        Setting::setPlatform('platform_contact_phone', $validated['platform_contact_phone'] ?? '');

        return redirect()
            ->route('superadmin.settings')
            ->with('success', 'Platform settings updated successfully!');
    }
}