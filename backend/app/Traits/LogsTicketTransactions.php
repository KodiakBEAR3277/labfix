<?php

namespace App\Traits;

use App\Models\TicketTransaction;

trait LogsTicketTransactions
{
    /**
     * Log a ticket transaction
     */
    protected function logTransaction($ticket, $action, $description, $oldValue = null, $newValue = null)
    {
        TicketTransaction::create([
            'ticket_id' => is_object($ticket) ? $ticket->id : $ticket,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
        ]);
    }

    /**
     * Log ticket creation
     */
    protected function logCreated($ticket)
    {
        $this->logTransaction(
            $ticket,
            'created',
            auth()->user()->full_name . ' created this ticket'
        );
    }

    /**
     * Log status change
     */
    protected function logStatusChange($ticket, $oldStatus, $newStatus)
    {
        $this->logTransaction(
            $ticket,
            'status_changed',
            'Status changed from ' . ucfirst(str_replace('-', ' ', $oldStatus)) . ' to ' . ucfirst(str_replace('-', ' ', $newStatus)),
            $oldStatus,
            $newStatus
        );
    }

    /**
     * Log assignment
     */
    protected function logAssignment($ticket, $assignedTo)
    {
        $assignedUser = \App\Models\User::find($assignedTo);
        $this->logTransaction(
            $ticket,
            'assigned',
            'Ticket assigned to ' . $assignedUser->full_name,
            null,
            $assignedTo
        );
    }

    /**
     * Log priority change
     */
    protected function logPriorityChange($ticket, $oldPriority, $newPriority)
    {
        $this->logTransaction(
            $ticket,
            'priority_changed',
            'Priority changed from ' . ucfirst($oldPriority) . ' to ' . ucfirst($newPriority),
            $oldPriority,
            $newPriority
        );
    }

    /**
     * Log ticket update
     */
    protected function logUpdate($ticket)
    {
        $this->logTransaction(
            $ticket,
            'updated',
            auth()->user()->full_name . ' updated ticket details'
        );
    }

    /**
     * Log ticket deletion
     */
    protected function logDeletion($ticket)
    {
        $this->logTransaction(
            $ticket,
            'deleted',
            auth()->user()->full_name . ' cancelled this ticket'
        );
    }

    /**
     * Log ticket restoration
     */
    protected function logRestoration($ticket)
    {
        $this->logTransaction(
            $ticket,
            'restored',
            auth()->user()->full_name . ' restored this ticket'
        );
    }
}