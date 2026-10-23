<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $institution = Auth::user()->institution;

        // One flat, typed map built from the defaults, so a missing row
        // (an older institution) still shows a sensible value in the form.
        $values = [];
        foreach (Setting::DEFAULTS as $key => $def) {
            $value = Setting::get($key, $def['value']);
            $values[$key] = match ($def['type']) {
                'boolean' => (bool) $value,
                'integer' => (int) $value,
                default   => (string) ($value ?? ''),
            };
        }

        return Inertia::render('Admin/Settings', [
            'values'   => $values,
            'plan'     => $institution->planLabel(),
            'features' => ['auto_rules' => $institution->hasAutoRules()],
        ]);
    }

    public function update(Request $request)
    {
        $institution = Auth::user()->institution;

        $validated = $request->validate([
            'settings'                                => ['required', 'array'],
            'settings.ticket_number_format'           => ['required', 'in:format_1,format_2,format_3,format_4,format_5'],
            'settings.default_priority'               => ['required', 'in:low,medium,high'],
            'settings.auto_close_resolved_after_days' => ['required', 'integer', 'min:0', 'max:365'],
            'settings.auto_escalate_after_hours'      => ['required', 'integer', 'min:0', 'max:720'],
            'settings.maintenance_mode'               => ['required', 'boolean'],
            'settings.maintenance_message'            => ['nullable', 'string', 'max:500'],
        ], [
            'settings.auto_close_resolved_after_days.max' => 'Auto-close can be at most 365 days.',
            'settings.auto_escalate_after_hours.max'      => 'Auto-escalate can be at most 720 hours (30 days).',
            'settings.*.integer'                          => 'Enter a whole number.',
            'settings.*.min'                              => 'This cannot be negative. Use 0 to turn it off.',
        ]);

        $input = $validated['settings'];

        $keys = ['ticket_number_format', 'default_priority', 'maintenance_mode', 'maintenance_message'];

        // The auto rules only exist on Standard and above. On Free the form
        // shows them locked, and anything submitted for them is ignored.
        if ($institution->hasAutoRules()) {
            $keys[] = 'auto_close_resolved_after_days';
            $keys[] = 'auto_escalate_after_hours';
        }

        foreach ($keys as $key) {
            $def   = Setting::DEFAULTS[$key];
            $value = $input[$key] ?? $def['value'];

            if ($def['type'] === 'boolean') {
                $value = $value ? '1' : '0';
            }

            Setting::set($key, (string) $value, $def['type'], $def['group']);
        }

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Settings updated successfully!');
    }
}