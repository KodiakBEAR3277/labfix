<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use App\Models\Equipment;
use App\Models\Lab;
use App\Models\TicketTransaction;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@labfix.com')->first();
        $adminUser = User::where('email', 'admin@labfix.com')->first();
        $itUser = User::where('email', 'it@labfix.com')->first();
        
        if (!$user || !$adminUser || !$itUser) {
            $this->command->warn('Required users not found. Please run UserSeeder first.');
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

        // Define reports with their lifecycle
        $reportsData = [
            [
                'equipment_id' => $equipmentA12->id,
                'category' => 'hardware',
                'title' => 'Computer won\'t start',
                'description' => 'The computer shows a black screen when powered on. No POST beep sound detected. I\'ve tried pressing the power button multiple times, but nothing happens.',
                'status' => 'in-progress',
                'priority' => 'high',
                'lifecycle' => [
                    ['action' => 'created', 'hours_after' => 0, 'user' => $user],
                    ['action' => 'assigned', 'hours_after' => 2, 'user' => $adminUser, 'assigned_to' => $itUser],
                    ['action' => 'status_changed', 'hours_after' => 3, 'user' => $itUser, 'old_status' => 'assigned', 'new_status' => 'in-progress'],
                ]
            ],
            [
                'equipment_id' => $equipmentB05->id,
                'category' => 'hardware',
                'title' => 'Keyboard keys not working',
                'description' => 'Several keys on the keyboard are unresponsive (A, S, D, F keys). I\'ve tried unplugging and replugging but the issue persists.',
                'status' => 'assigned',
                'priority' => 'medium',
                'lifecycle' => [
                    ['action' => 'created', 'hours_after' => 0, 'user' => $user],
                    ['action' => 'assigned', 'hours_after' => 1, 'user' => $adminUser, 'assigned_to' => $itUser],
                ]
            ],
            [
                'equipment_id' => $equipmentC20->id,
                'category' => 'software',
                'title' => 'Software installation error',
                'description' => 'Unable to install Visual Studio Code. Error message: "Installation failed." I\'ve tried multiple times with the same result.',
                'status' => 'resolved',
                'priority' => 'low',
                'lifecycle' => [
                    ['action' => 'created', 'hours_after' => 0, 'user' => $user],
                    ['action' => 'assigned', 'hours_after' => 1, 'user' => $adminUser, 'assigned_to' => $itUser],
                    ['action' => 'status_changed', 'hours_after' => 2, 'user' => $itUser, 'old_status' => 'assigned', 'new_status' => 'in-progress'],
                    ['action' => 'status_changed', 'hours_after' => 24, 'user' => $itUser, 'old_status' => 'in-progress', 'new_status' => 'resolved'],
                ]
            ],
        ];

        foreach ($reportsData as $reportData) {
            // Extract lifecycle and assigned_to before creating report
            $lifecycle = $reportData['lifecycle'];
            $assignedTo = null;
            
            // Find if there's an assignment in the lifecycle
            foreach ($lifecycle as $event) {
                if ($event['action'] === 'assigned' && isset($event['assigned_to'])) {
                    $assignedTo = $event['assigned_to']->id;
                    break;
                }
            }
            
            unset($reportData['lifecycle']);

            // Create the report
            $report = Report::create([
                'ticket_number' => Report::generateTicketNumber(),
                'user_id' => $user->id,
                'assigned_to' => $assignedTo,
                'equipment_id' => $reportData['equipment_id'],
                'category' => $reportData['category'],
                'title' => $reportData['title'],
                'description' => $reportData['description'],
                'status' => $reportData['status'],
                'priority' => $reportData['priority'],
            ]);

            // Create transaction logs for the lifecycle
            foreach ($lifecycle as $event) {
                $timestamp = $report->created_at->copy()->addHours($event['hours_after']);
                
                switch ($event['action']) {
                    case 'created':
                        TicketTransaction::create([
                            'ticket_id' => $report->id,
                            'user_id' => $event['user']->id,
                            'action' => 'created',
                            'description' => $event['user']->full_name . ' created this ticket',
                            'created_at' => $timestamp,
                        ]);
                        break;
                        
                    case 'assigned':
                        TicketTransaction::create([
                            'ticket_id' => $report->id,
                            'user_id' => $event['user']->id,
                            'action' => 'assigned',
                            'new_value' => $event['assigned_to']->id,
                            'description' => 'Ticket assigned to ' . $event['assigned_to']->full_name,
                            'created_at' => $timestamp,
                        ]);
                        break;
                        
                    case 'status_changed':
                        TicketTransaction::create([
                            'ticket_id' => $report->id,
                            'user_id' => $event['user']->id,
                            'action' => 'status_changed',
                            'old_value' => $event['old_status'],
                            'new_value' => $event['new_status'],
                            'description' => 'Status changed from ' . ucfirst(str_replace('-', ' ', $event['old_status'])) . ' to ' . ucfirst(str_replace('-', ' ', $event['new_status'])),
                            'created_at' => $timestamp,
                        ]);
                        break;
                        
                    case 'priority_changed':
                        TicketTransaction::create([
                            'ticket_id' => $report->id,
                            'user_id' => $event['user']->id,
                            'action' => 'priority_changed',
                            'old_value' => $event['old_priority'],
                            'new_value' => $event['new_priority'],
                            'description' => 'Priority changed from ' . ucfirst($event['old_priority']) . ' to ' . ucfirst($event['new_priority']),
                            'created_at' => $timestamp,
                        ]);
                        break;
                }
            }

            $this->command->info("Created ticket: {$report->ticket_number}");
        }

        $this->command->info('Sample reports with transaction history created successfully!');
    }
}