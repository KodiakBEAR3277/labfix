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
            ->with('transactions')
            ->get()
            ->filter(function($ticket) use ($cutoffDate) {
                // Find the resolved transaction
                $resolvedTransaction = $ticket->transactions()
                    ->where('action', 'status_changed')
                    ->where('new_value', 'resolved')
                    ->first();
                    
                return $resolvedTransaction && $resolvedTransaction->created_at <= $cutoffDate;
            });

        $count = 0;
        foreach ($tickets as $ticket) {
            $oldStatus = $ticket->status;
            
            $ticket->update([
                'status' => 'closed',
            ]);
            
            // Log the status change
            \App\Models\TicketTransaction::create([
                'ticket_id' => $ticket->id,
                'user_id' => 1, // System/Admin
                'action' => 'status_changed',
                'old_value' => $oldStatus,
                'new_value' => 'closed',
                'description' => 'Automatically closed after ' . $days . ' days in resolved status',
                'created_at' => now(),
            ]);
            
            $count++;
        }

        $this->info("Auto-closed {$count} tickets that were resolved over {$days} days ago.");
    }
}