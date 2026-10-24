<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToInstitution;

class VoiceNote extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $fillable = [
        'institution_id',
        'report_id',
        'user_id',
        'path',
        'mime_type',
        'duration_seconds',
        'size_bytes',
    ];

    protected $casts = [
        'report_id' => 'integer',
        'user_id' => 'integer',
        'duration_seconds' => 'integer',
        'size_bytes' => 'integer',
    ];

    // Relationship: Ticket this note belongs to
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    // Relationship: User who recorded it
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
