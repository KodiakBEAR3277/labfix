<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'address',
        'is_active',
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
}