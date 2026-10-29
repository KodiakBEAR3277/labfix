<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Platform-level settings — not tied to any institution. Read by the
        // public contact-info endpoint and the superadmin settings page.
        Setting::setPlatform('platform_name', 'LabFix');
        Setting::setPlatform('platform_contact_email', 'support@labfix.edu');
        Setting::setPlatform('platform_contact_phone', '+63 123 456 7890');

        // Per-institution defaults — the same set a newly registered
        // institution receives from InstitutionRequest::approve().
        foreach (Institution::orderBy('id')->get() as $institution) {
            Setting::seedDefaultsFor($institution->id);
        }

        $this->command->info('Platform and per-institution settings created.');
    }
}
