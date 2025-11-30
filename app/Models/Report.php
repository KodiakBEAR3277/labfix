<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'assigned_to',
        'lab_location',
        'equipment_id',
        'category',
        'title',
        'description',
        'status',
        'priority',
        'attachments',
        'assigned_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationship: Reporter (User who created the report)
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship: Assigned IT Staff
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Generate unique ticket number
    public static function generateTicketNumber(): string
    {
        $year = date('Y');
        $lastTicket = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastTicket ? (int) substr($lastTicket->ticket_number, -3) + 1 : 1;
        
        return 'TKT-' . $year . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    // Get formatted ticket ID for display
    public function getFormattedIdAttribute(): string
    {
        return '#' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    // Get priority badge color
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'success',
            default => 'secondary',
        };
    }

    // Get status badge color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'new' => 'info',
            'assigned' => 'assigned',
            'in-progress' => 'progress',
            'resolved' => 'resolved',
            'closed' => 'resolved',
            default => 'secondary',
        };
    }

    // Check if report is open
    public function isOpen(): bool
    {
        return in_array($this->status, ['new', 'assigned', 'in-progress']);
    }

    // Check if report is closed
    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    // Scope: Open reports
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['new', 'assigned', 'in-progress']);
    }

    // Scope: Closed reports
    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    // Scope: By priority
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // Scope: By status
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}