<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'system_name', 'value' => 'LabFix - Computer Lab Management', 'type' => 'string', 'group' => 'general', 'description' => 'The name displayed across the system'],
            ['key' => 'support_email', 'value' => 'support@labfix.edu', 'type' => 'string', 'group' => 'general', 'description' => 'Primary support contact email'],
            ['key' => 'support_phone', 'value' => '+63 123 456 7890', 'type' => 'string', 'group' => 'general', 'description' => 'Support phone number for contact page'],

            // Notification Settings (Simple flags)
            ['key' => 'notify_user_on_assignment', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Email user when ticket is assigned to IT'],
            ['key' => 'notify_user_on_status_change', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Email user when ticket status changes'],
            ['key' => 'notify_user_on_resolution', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Email user when ticket is resolved'],
            ['key' => 'notify_it_on_new_ticket', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Email IT team when new ticket is created'],
            ['key' => 'notify_it_on_high_priority', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Email IT team immediately for high priority tickets'],

            // Ticket Settings
            ['key' => 'ticket_number_format', 'value' => 'format_1', 'type' => 'string', 'group' => 'tickets', 'description' => 'Format for ticket number generation'],
            ['key' => 'default_priority', 'value' => 'medium', 'type' => 'string', 'group' => 'tickets', 'description' => 'Default priority for new tickets'],
            ['key' => 'auto_close_resolved_after_days', 'value' => '7', 'type' => 'integer', 'group' => 'tickets', 'description' => 'Auto-close resolved tickets after X days (0 to disable)'],
            ['key' => 'auto_escalate_after_hours', 'value' => '24', 'type' => 'integer', 'group' => 'tickets', 'description' => 'Auto-escalate unresolved tickets after X hours (0 to disable)'],
            ['key' => 'allow_attachments', 'value' => '1', 'type' => 'boolean', 'group' => 'tickets', 'description' => 'Allow users to upload attachments to tickets'],

            // Maintenance Mode
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'maintenance', 'description' => 'Enable maintenance mode (blocks ticket creation)'],
            ['key' => 'maintenance_message', 'value' => 'The system is currently undergoing maintenance. Please try again later.', 'type' => 'string', 'group' => 'maintenance', 'description' => 'Message shown to users during maintenance'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}