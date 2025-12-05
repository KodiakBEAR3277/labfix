<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_id',
        'equipment_code',
        'type',
        'status',
        'notes',
    ];

    protected $casts = [
        'lab_id' => 'integer',
    ];

    // Relationship: Lab this equipment belongs to
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    // Relationship: Reports for this equipment
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    // Get active (open) reports for this equipment
    public function activeReports(): HasMany
    {
        return $this->reports()->open();
    }

    // Update equipment status based on reports
    public function updateStatusFromReports(): void
    {
        $openReports = $this->activeReports()->count();
        
        if ($openReports > 0) {
            // Check if any high priority reports
            $highPriorityReports = $this->activeReports()->where('priority', 'high')->count();
            
            if ($highPriorityReports > 0) {
                $this->update(['status' => 'has-issue']);
            } else {
                $this->update(['status' => 'maintenance']);
            }
        } else {
            // No open reports, mark as operational
            $this->update(['status' => 'operational']);
        }
    }

    // Get full equipment identifier (Lab + Code)
    public function getFullCodeAttribute(): string
    {
        return $this->lab->name . ', ' . $this->equipment_code;
    }
}