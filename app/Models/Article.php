<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'category',
        'status',
        'views',
        'helpful_count',
        'not_helpful_count',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
    ];

    // Relationship: Author
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Auto-generate slug from title
    public static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
                
                // Ensure unique slug
                $originalSlug = $article->slug;
                $count = 1;
                while (static::where('slug', $article->slug)->exists()) {
                    $article->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    // Calculate helpfulness percentage
    public function getHelpfulnessPercentageAttribute(): int
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return 0;
        }
        return (int) round(($this->helpful_count / $total) * 100);
    }

    // Check if article is published
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    // Scope: Published articles
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    // Scope: Draft articles
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Scope: By category
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Increment view count
    public function incrementViews()
    {
        $this->increment('views');
    }

    // Mark as helpful
    public function markHelpful()
    {
        $this->increment('helpful_count');
    }

    // Mark as not helpful
    public function markNotHelpful()
    {
        $this->increment('not_helpful_count');
    }
}