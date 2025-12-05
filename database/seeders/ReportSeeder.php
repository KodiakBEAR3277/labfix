<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use App\Models\Equipment;
use App\Models\Lab;
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

        // Get labs and their equipment
        $labA = Lab::where('code', 'LAB-A')->first();
        $labB = Lab::where('code', 'LAB-B')->first();
        $labC = Lab::where('code', 'LAB-C')->first();

        if (!$labA || !$labB || !$labC) {
            $this->command->warn('Labs not found. Please run LabSeeder and EquipmentSeeder first.');
            return;
        }

        // Get specific equipment
        $equipmentA12 = Equipment::where('lab_id', $labA->id)->where('equipment_code', 'PC-12')->first();
        $equipmentB05 = Equipment::where('lab_id', $labB->id)->where('equipment_code', 'PC-05')->first();
        $equipmentC20 = Equipment::where('lab_id', $labC->id)->where('equipment_code', 'PC-20')->first();

        if (!$equipmentA12 || !$equipmentB05 || !$equipmentC20) {
            $this->command->warn('Equipment not found. Please run EquipmentSeeder first.');
            return;
        }

        $reports = [
            [
                'equipment_id' => $equipmentA12->id,
                'category' => 'hardware',
                'title' => 'Computer won\'t start',
                'description' => 'The computer shows a black screen when powered on. No POST beep sound detected. I\'ve tried pressing the power button multiple times, but nothing happens.',
                'status' => 'in-progress',
                'priority' => 'high',
            ],
            [
                'equipment_id' => $equipmentB05->id,
                'category' => 'hardware',
                'title' => 'Keyboard keys not working',
                'description' => 'Several keys on the keyboard are unresponsive (A, S, D, F keys). I\'ve tried unplugging and replugging but the issue persists.',
                'status' => 'assigned',
                'priority' => 'medium',
            ],
            [
                'equipment_id' => $equipmentC20->id,
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