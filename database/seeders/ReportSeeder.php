<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@labfix.com')->first();
        
        if (!$user) {
            $this->command->warn('User not found. Please run UserSeeder first.');
            return;
        }

        $reports = [
            [
                'lab_location' => 'Computer Lab A',
                'equipment_id' => 'PC-12',
                'category' => 'hardware',
                'title' => 'Computer won\'t start',
                'description' => 'The computer shows a black screen when powered on. No POST beep sound detected. I\'ve tried pressing the power button multiple times, but nothing happens.',
                'status' => 'in-progress',
                'priority' => 'high',
            ],
            [
                'lab_location' => 'Computer Lab B',
                'equipment_id' => 'PC-05',
                'category' => 'hardware',
                'title' => 'Keyboard keys not working',
                'description' => 'Several keys on the keyboard are unresponsive (A, S, D, F keys). I\'ve tried unplugging and replugging but the issue persists.',
                'status' => 'assigned',
                'priority' => 'medium',
            ],
            [
                'lab_location' => 'Computer Lab C',
                'equipment_id' => 'PC-20',
                'category' => 'software',
                'title' => 'Software installation error',
                'description' => 'Unable to install Visual Studio Code. Error message: "Installation failed." I\'ve tried multiple times with the same result.',
                'status' => 'resolved',
                'priority' => 'low',
                'resolved_at' => now()->subDays(3),
            ],
        ];

        foreach ($reports as $reportData) {
            Report::create([
                'ticket_number' => Report::generateTicketNumber(),
                'user_id' => $user->id,
                ...$reportData,
            ]);
        }

        $this->command->info('Sample reports created successfully!');
    }
}