<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'general'       => Setting::where('group', 'general')->get(),
            'notifications' => Setting::where('group', 'notifications')->get(),
            'tickets'       => Setting::where('group', 'tickets')->get(),
            'maintenance'   => Setting::where('group', 'maintenance')->get(),
        ];

        return Inertia::render('Admin/Settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings'   => 'required|array',
            'settings.*' => 'nullable',
        ]);

        $allBooleanSettings = Setting::where('type', 'boolean')->get()->keyBy('key');

        foreach ($validated['settings'] as $key => $value) {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                if ($setting->type === 'boolean') {
                    // useForm() sends booleans as true/false; normalise to '1'/'0'
                    $value = ($value === true || $value === 'on' || $value === '1') ? '1' : '0';
                }

                Setting::set($key, $value, $setting->type, $setting->group);
            }
        }

        // Handle unchecked booleans — if a key wasn't submitted, it was turned off
        foreach ($allBooleanSettings as $key => $setting) {
            if (!array_key_exists($key, $validated['settings'])) {
                Setting::set($key, '0', 'boolean', $setting->group);
            }
        }

        // Setting::set() already forgets each key's own cache entry above —
        // no blanket Cache::flush() here anymore, since that would wipe
        // cached data platform-wide, across every institution, not just this one.

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Settings updated successfully!');
    }
}