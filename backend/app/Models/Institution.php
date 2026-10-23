<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Support\Plans;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'address',
        'is_active',
        'plan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Auto-generate slug from name — same pattern as Article::boot()
    public static function boot()
    {
        parent::boot();

        static::creating(function ($institution) {
            if (empty($institution->slug)) {
                $institution->slug = Str::slug($institution->name);

                $originalSlug = $institution->slug;
                $count = 1;
                while (static::where('slug', $institution->slug)->exists()) {
                    $institution->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    // Relationship: Users belonging to this institution
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Relationship: Labs belonging to this institution
    public function labs(): HasMany
    {
        return $this->hasMany(Lab::class);
    }

    // Relationship: Equipment belonging to this institution. Equipment is
    // institution-scoped directly (via BelongsToInstitution), not only
    // through its lab, so this counts correctly even if a future feature
    // lets equipment exist without a lab assigned.
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    // Plan limits. The single source of truth is App\Support\Plans. A null
    // limit means unlimited. An unset or unknown plan resolves to Free,
    // the safest default for institutions that predate the plan column.
    public function planConfig(): array
    {
        return Plans::get($this->plan);
    }

    public function planLabel(): string
    {
        return $this->planConfig()['label'];
    }

    public function labLimit(): ?int
    {
        return $this->planConfig()['max_labs'];
    }

    public function equipmentLimit(): ?int
    {
        return $this->planConfig()['max_equipment'];
    }

    public function userLimit(): ?int
    {
        return $this->planConfig()['max_users'];
    }

    public function canAddLab(): bool
    {
        $limit = $this->labLimit();
        return $limit === null || $this->labs()->count() < $limit;
    }

    public function canAddEquipment(): bool
    {
        $limit = $this->equipmentLimit();
        return $limit === null || $this->equipment()->count() < $limit;
    }

    public function canAddUser(): bool
    {
        $limit = $this->userLimit();
        return $limit === null || $this->users()->count() < $limit;
    }

    // hasAutoRules() gates the auto-close and auto-escalate commands.
    // hasKnowledgeBase() is reserved for gating article authoring later.
    public function hasAutoRules(): bool
    {
        return (bool) $this->planConfig()['auto_rules'];
    }

    public function hasKnowledgeBase(): bool
    {
        return (bool) $this->planConfig()['knowledge_base'];
    }
}