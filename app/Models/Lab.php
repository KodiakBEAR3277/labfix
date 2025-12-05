<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lab extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'location',
        'capacity',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    // Relationship: Equipment in this lab
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    // Get operational equipment count
    public function getOperationalCountAttribute(): int
    {
        return $this->equipment()->where('status', 'operational')->count();
    }

    // Get equipment with issues count
    public function getIssuesCountAttribute(): int
    {
        return $this->equipment()->where('status', 'has-issue')->count();
    }

    // Get equipment under maintenance count
    public function getMaintenanceCountAttribute(): int
    {
        return $this->equipment()->where('status', 'maintenance')->count();
    }

    // Check if lab is operational (at least 50% equipment working)
    public function isOperational(): bool
    {
        $total = $this->equipment()->count();
        if ($total === 0) return false;
        
        $operational = $this->operational_count;
        return ($operational / $total) >= 0.5;
    }
}