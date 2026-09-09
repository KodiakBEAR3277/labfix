<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Institution;
use App\Models\Report;
use App\Models\Setting;
use App\Models\TicketTransaction;
use App\Models\User;
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
    protected $description = 'Automatically close resolved tickets after each institution\'s configured number of days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $systemUserId = User::withoutGlobalScope('institution')
            ->where('role', 'superadmin')
            ->value('id');

        if (!$systemUserId) {
            $this->error('No superadmin account exists yet — auto-close needs one to attribute system actions to. Run `php artisan make:superadmin` first.');
            return 1;
        }

        $totalClosed = 0;

        foreach (Institution::where('is_active', true)->get() as $institution) {
            app()->instance('currentInstitutionId', $institution->id);

            $days = Setting::get('auto_close_resolved_after_days', 7);

            if ($days == 0) {
                continue;
            }

            $cutoffDate = Carbon::now()->subDays($days);

            $tickets = Report::where('status', 'resolved')
                ->with('transactions')
                ->get()
                ->filter(function ($ticket) use ($cutoffDate) {
                    // Find the resolved transaction
                    $resolvedTransaction = $ticket->transactions()
                        ->where('action', 'status_changed')
                        ->where('new_value', 'resolved')
                        ->first();

                    return $resolvedTransaction && $resolvedTransaction->created_at <= $cutoffDate;
                });

            foreach ($tickets as $ticket) {
                $oldStatus = $ticket->status;

                $ticket->update([
                    'status' => 'closed',
                ]);

                // Log the status change
                TicketTransaction::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $systemUserId,
                    'action'      => 'status_changed',
                    'old_value'   => $oldStatus,
                    'new_value'   => 'closed',
                    'description' => 'Automatically closed after ' . $days . ' days in resolved status',
                    'created_at'  => now(),
                ]);

                $totalClosed++;
            }

            $this->info("{$institution->name}: auto-closed {$tickets->count()} tickets that were resolved over {$days} days ago.");
        }

        app()->forgetInstance('currentInstitutionId');

        $this->info("Done — {$totalClosed} tickets auto-closed across all institutions.");
    }
}