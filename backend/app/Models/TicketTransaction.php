<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTransaction extends Model
{
    use HasFactory;

    public $timestamps = false; // Only created_at, no updated_at
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationship: Ticket
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'ticket_id');
    }

    // Relationship: User who performed the action
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper method to create a transaction
     */
    public static function log($ticketId, $action, $description, $oldValue = null, $newValue = null)
    {
        return self::create([
            'ticket_id' => $ticketId,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
        ]);
    }
}