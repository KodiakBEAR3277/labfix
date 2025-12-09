<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        // Group settings by category
        $settings = [
            'general' => Setting::where('group', 'general')->get(),
            'notifications' => Setting::where('group', 'notifications')->get(),
            'tickets' => Setting::where('group', 'tickets')->get(),
            'maintenance' => Setting::where('group', 'maintenance')->get(),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable',
        ]);

        // Get all boolean settings to check for unchecked checkboxes
        $allBooleanSettings = Setting::where('type', 'boolean')->get()->keyBy('key');

        foreach ($validated['settings'] as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            
            if ($setting) {
                // Handle boolean values from checkboxes
                if ($setting->type === 'boolean') {
                    $value = $value === 'on' || $value === '1' ? '1' : '0';
                }
                
                Setting::set($key, $value, $setting->type, $setting->group);
            }
        }

        // Handle unchecked checkboxes (they don't get submitted in the form)
        foreach ($allBooleanSettings as $key => $setting) {
            if (!isset($validated['settings'][$key])) {
                // Checkbox was not submitted, meaning it was unchecked
                Setting::set($key, '0', 'boolean', $setting->group);
            }
        }

        // Clear cache to ensure changes take effect immediately
        \Illuminate\Support\Facades\Cache::flush();

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Settings updated successfully!');
    }
}