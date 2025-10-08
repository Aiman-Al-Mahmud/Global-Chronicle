<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'deleted_at',
    ];

    // Model Events

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->title);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('title') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->title);
            }
        });
    }

    // Relationships

    /**
     * Get the news articles that belong to this tag.
     */
    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'news_tags');
    }

    /**
     * Get only published news articles for this tag.
     */
    public function publishedNews(): BelongsToMany
    {
        return $this->news()->where('status', 'published')->where('published_at', '<=', now());
    }

    // Scopes

    /**
     * Scope a query to only include active tags.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search tags by title.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
    }

    /**
     * Scope to order by popularity (news count).
     */
    public function scopePopular($query)
    {
        return $query->withCount(['publishedNews'])
                    ->orderBy('published_news_count', 'desc');
    }

    // Accessors

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the URL for this tag.
     */
    public function getUrlAttribute(): string
    {
        return route('tags.show', $this->slug);
    }

    // Mutators

    /**
     * Set the title attribute and generate slug.
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // Helper Methods

    /**
     * Get the count of published news articles.
     */
    public function getPublishedNewsCount(): int
    {
        return $this->publishedNews()->count();
    }

    /**
     * Check if tag has any published news.
     */
    public function hasPublishedNews(): bool
    {
        return $this->publishedNews()->exists();
    }
}