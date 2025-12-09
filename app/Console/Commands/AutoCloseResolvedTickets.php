<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Models\Setting;
use Carbon\Carbon;

class AutoCloseResolvedTickets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tickets:auto-close';

    /**
     * The console command description.
     */
    protected $description = 'Automatically close resolved tickets after specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = Setting::get('auto_close_resolved_after_days', 7);
        
        // If set to 0, auto-close is disabled
        if ($days == 0) {
            $this->info('Auto-close is disabled (set to 0 days).');
            return;
        }
        
        $cutoffDate = Carbon::now()->subDays($days);
        
        $tickets = Report::where('status', 'resolved')
            ->where('resolved_at', '<=', $cutoffDate)
            ->whereNull('closed_at')
            ->get();

        $count = 0;
        foreach ($tickets as $ticket) {
            $ticket->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
            $count++;
        }

        $this->info("Auto-closed {$count} tickets that were resolved over {$days} days ago.");
    }
}