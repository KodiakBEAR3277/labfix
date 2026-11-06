<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\BelongsToInstitution;

class Equipment extends Model
{
    use HasFactory, BelongsToInstitution;

    // Scanned QR payloads carry this prefix. It is deliberately not a URL so
    // an OS can never auto-open it — the app resolves the payload server-side.
    public const QR_PREFIX = 'LABFIX-EQ:';

    protected $fillable = [
        'institution_id',
        'lab_id',
        'equipment_code',
        'qr_token',
        'type',
        'status',
        'notes',
        'brand',
        'model',
        'specifications',
        'serial_number',
        'purchase_date',
        'warranty_expiry',
    ];

    protected $casts = [
        'lab_id' => 'integer',
        'purchase_date' => 'date:Y-m-d',
        'warranty_expiry' => 'date:Y-m-d',
    ];

    protected $appends = ['qr_payload'];

    protected static function booted(): void
    {
        static::creating(function (self $equipment) {
            if (empty($equipment->qr_token) && $equipment->equipment_code !== 'GENERAL') {
                $equipment->qr_token = (string) Str::uuid();
            }
        });
    }

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

    // Printable QR payload for this device (null for the 'GENERAL' placeholder).
    public function getQrPayloadAttribute(): ?string
    {
        return $this->qr_token ? self::QR_PREFIX . $this->qr_token : null;
    }

    // Resolve a scanned payload to this institution's equipment. The
    // BelongsToInstitution global scope means another tenant's token misses.
    public static function findByQrPayload(string $payload): ?self
    {
        $payload = trim($payload);

        if (!str_starts_with($payload, self::QR_PREFIX)) {
            return null;
        }

        $token = substr($payload, strlen(self::QR_PREFIX));

        if ($token === '') {
            return null;
        }

        return static::where('qr_token', $token)->with('lab')->first();
    }

    // Check if equipment is still within its warranty period
    public function isUnderWarranty(): bool
    {
        return $this->warranty_expiry !== null && $this->warranty_expiry->isFuture();
    }
}