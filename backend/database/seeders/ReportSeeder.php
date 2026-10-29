<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Institution;
use App\Models\Report;
use App\Models\TicketTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $year = date('Y');

        // ticket_number is globally unique, but Report::generateTicketNumber()
        // counts under the current institution scope — so per-institution
        // numbering would collide across institutions. One global counter
        // keeps every generated number unique.
        $sequence = 0;

        foreach (Institution::orderBy('id')->get() as $institution) {
            app()->instance('currentInstitutionId', $institution->id);

            $student = User::where('role', 'student')->first();
            $adminUser = User::where('role', 'admin')->first();
            $itUser = User::where('role', 'it-support')->first();
            $equipment = Equipment::orderBy('id')->get();

            if (!$student || !$adminUser || !$itUser || $equipment->isEmpty()) {
                app()->forgetInstance('currentInstitutionId');
                continue;
            }

            // Spread the sample tickets across whatever equipment this
            // institution has (Free only has one lab, so indexes wrap).
            $pick = fn (int $i) => $equipment[$i % $equipment->count()];

            $reportsData = [
                [
                    'equipment' => $pick(0),
                    'category' => 'hardware',
                    'title' => 'Computer won\'t start',
                    'description' => 'The computer shows a black screen when powered on. No POST beep sound detected. I\'ve tried pressing the power button multiple times, but nothing happens.',
                    'status' => 'in-progress',
                    'priority' => 'high',
                    'lifecycle' => [
                        ['action' => 'created', 'hours_after' => 0, 'user' => $student],
                        ['action' => 'assigned', 'hours_after' => 2, 'user' => $adminUser, 'assigned_to' => $itUser],
                        ['action' => 'status_changed', 'hours_after' => 3, 'user' => $itUser, 'old_status' => 'assigned', 'new_status' => 'in-progress'],
                    ],
                ],
                [
                    'equipment' => $pick(1),
                    'category' => 'hardware',
                    'title' => 'Keyboard keys not working',
                    'description' => 'Several keys on the keyboard are unresponsive (A, S, D, F keys). I\'ve tried unplugging and replugging but the issue persists.',
                    'status' => 'assigned',
                    'priority' => 'medium',
                    'lifecycle' => [
                        ['action' => 'created', 'hours_after' => 0, 'user' => $student],
                        ['action' => 'assigned', 'hours_after' => 1, 'user' => $adminUser, 'assigned_to' => $itUser],
                    ],
                ],
                [
                    'equipment' => $pick(2),
                    'category' => 'software',
                    'title' => 'Software installation error',
                    'description' => 'Unable to install Visual Studio Code. Error message: "Installation failed." I\'ve tried multiple times with the same result.',
                    'status' => 'resolved',
                    'priority' => 'low',
                    'lifecycle' => [
                        ['action' => 'created', 'hours_after' => 0, 'user' => $student],
                        ['action' => 'assigned', 'hours_after' => 1, 'user' => $adminUser, 'assigned_to' => $itUser],
                        ['action' => 'status_changed', 'hours_after' => 2, 'user' => $itUser, 'old_status' => 'assigned', 'new_status' => 'in-progress'],
                        ['action' => 'status_changed', 'hours_after' => 24, 'user' => $itUser, 'old_status' => 'in-progress', 'new_status' => 'resolved'],
                    ],
                ],
            ];

            foreach ($reportsData as $reportData) {
                $lifecycle = $reportData['lifecycle'];

                // Find if there's an assignment in the lifecycle
                $assignedTo = null;
                foreach ($lifecycle as $event) {
                    if ($event['action'] === 'assigned' && isset($event['assigned_to'])) {
                        $assignedTo = $event['assigned_to']->id;
                        break;
                    }
                }

                $report = Report::create([
                    'ticket_number' => sprintf('TKT-%s-%04d', $year, ++$sequence),
                    'user_id' => $student->id,
                    'assigned_to' => $assignedTo,
                    'equipment_id' => $reportData['equipment']->id,
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
                                'new_value' => (string) $event['assigned_to']->id,
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
                    }
                }
            }

            app()->forgetInstance('currentInstitutionId');
        }

        $this->command->info('Sample tickets created for every institution.');
    }
}
