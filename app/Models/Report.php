<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    protected $casts = [
        'attachments' => 'array',
        'deleted_at' => 'datetime',
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

    // Relationship: Equipment
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Generate unique ticket number based on system settings
     */
    public static function generateTicketNumber(): string
    {
        $format = \App\Models\Setting::get('ticket_number_format', 'format_1');
        
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        
        // Get the last ticket number for this year to increment
        $lastTicket = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        // Extract the last 4-digit number and increment
        $number = $lastTicket ? (int) substr($lastTicket->ticket_number, -4) + 1 : 1;
        $paddedNumber = str_pad($number, 4, '0', STR_PAD_LEFT);
        
        return match($format) {
            'format_1' => "TKT-{$year}-{$paddedNumber}",              // TKT-2025-0001
            'format_2' => "TKT{$year}{$paddedNumber}",                // TKT20250001
            'format_3' => "TKT-{$year}{$month}-{$paddedNumber}",      // TKT-202512-0001
            'format_4' => "TKT-{$year}{$month}{$day}-{$paddedNumber}", // TKT-20251209-0001
            'format_5' => "TICKET-{$year}-{$paddedNumber}",           // TICKET-2025-0001
            default => "TKT-{$year}-{$paddedNumber}",                 // Fallback to format_1
        };
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

    // Override boot to update equipment status when report changes
    protected static function booted()
    {
        static::created(function ($report) {
            $report->equipment->updateStatusFromReports();
        });

        static::updated(function ($report) {
            if ($report->wasChanged('status')) {
                $report->equipment->updateStatusFromReports();
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TicketTransaction::class, 'ticket_id');
    }
}