<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    protected $description = 'Automatically escalate unresolved tickets after specified hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = Setting::get('auto_escalate_after_hours', 24);
        
        // If set to 0, auto-escalate is disabled
        if ($hours == 0) {
            $this->info('Auto-escalate is disabled (set to 0 hours).');
            return;
        }
        
        $cutoffTime = Carbon::now()->subHours($hours);
        
        // Find tickets that are unresolved and created before cutoff time
        $tickets = Report::whereIn('status', ['new', 'assigned', 'in-progress'])
            ->whereIn('priority', ['low', 'medium'])
            ->where('created_at', '<=', $cutoffTime)
            ->get();

        $count = 0;
        foreach ($tickets as $ticket) {
            // Escalate: low -> medium, medium -> high
            $newPriority = $ticket->priority === 'low' ? 'medium' : 'high';
            
            $ticket->update(['priority' => $newPriority]);
            $count++;
            
            $this->line("Escalated ticket {$ticket->ticket_number} from {$ticket->priority} to {$newPriority}");
        }

        $this->info("Auto-escalated {$count} tickets that were unresolved for over {$hours} hours.");
    }
}