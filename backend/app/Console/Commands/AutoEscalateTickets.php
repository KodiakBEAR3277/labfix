<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Institution;
use App\Models\Report;
use App\Models\Setting;
use Carbon\Carbon;

class AutoEscalateTickets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tickets:auto-escalate';

    /**
     * The console command description.
     */
    protected $description = 'Automatically escalate unresolved tickets after each institution\'s configured number of hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalEscalated = 0;

        foreach (Institution::where('is_active', true)->get() as $institution) {
            app()->instance('currentInstitutionId', $institution->id);

            $hours = Setting::get('auto_escalate_after_hours', 24);

            if ($hours == 0) {
                continue;
            }

            $cutoffTime = Carbon::now()->subHours($hours);

            // Find tickets that are unresolved and created before cutoff time
            $tickets = Report::whereIn('status', ['new', 'assigned', 'in-progress'])
                ->whereIn('priority', ['low', 'medium'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            foreach ($tickets as $ticket) {
                // Escalate: low -> medium, medium -> high
                $oldPriority = $ticket->priority;
                $newPriority = $oldPriority === 'low' ? 'medium' : 'high';

                $ticket->update(['priority' => $newPriority]);
                $totalEscalated++;

                $this->line("{$institution->name}: escalated ticket {$ticket->ticket_number} from {$oldPriority} to {$newPriority}");
            }

            $this->info("{$institution->name}: {$tickets->count()} tickets unresolved for over {$hours} hours.");
        }

        app()->forgetInstance('currentInstitutionId');

        $this->info("Done — {$totalEscalated} tickets auto-escalated across all institutions.");
    }
}