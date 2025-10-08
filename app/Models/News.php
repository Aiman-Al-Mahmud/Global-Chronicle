<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class News extends Model
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
        'excerpt',
        'content',
        'language',
        'provider',
        'status',
        'views_count',
        'is_featured',
        'allow_comments',
        'author_id',
        'category_id',
        'featured_image_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'rss_url',
        'external_id',
        'external_published_at',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'views_count' => 'integer',
            'meta_keywords' => 'array',
            'published_at' => 'datetime',
            'external_published_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'deleted_at',
        'published_at',
        'external_published_at',
    ];

    // Model Events

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }
            
            // Auto-generate excerpt if not provided
            if (empty($news->excerpt) && !empty($news->content)) {
                $news->excerpt = Str::limit(strip_tags($news->content), 200);
            }
        });

        static::updating(function ($news) {
            if ($news->isDirty('title') && empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }
            
            // Auto-generate excerpt if not provided
            if (empty($news->excerpt) && !empty($news->content)) {
                $news->excerpt = Str::limit(strip_tags($news->content), 200);
            }
        });
    }

    // Relationships

    /**
     * Get the author of the news article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the category of the news article.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the featured image of the news article.
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * Get the tags associated with the news article.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'news_tags');
    }

    /**
     * Get the likes for the news article.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Get the views for the news article.
     */
    public function views(): HasMany
    {
        return $this->hasMany(NewsView::class);
    }

    /**
     * Get the comments for the news article.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get approved comments only.
     */
    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', 'approved');
    }

    // Scopes

    /**
     * Scope a query to only include published articles.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include draft articles.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include archived articles.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope a query to only include featured articles.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to filter by language.
     */
    public function scopeLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by author.
     */
    public function scopeAuthor(Builder $query, $authorId): Builder
    {
        return $query->where('author_id', $authorId);
    }

    /**
     * Scope a query to search articles.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
              ->orWhere('excerpt', 'LIKE', "%{$term}%")
              ->orWhere('content', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope to order by latest.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Scope to order by most viewed.
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('views_count', 'desc');
    }

    /**
     * Scope to get trending articles (popular in recent time).
     * Uses a subquery to count views, which is MySQL ONLY_FULL_GROUP_BY compliant.
     */
    public function scopeTrending(Builder $query, int $days = 7): Builder
    {
        return $query->select('news.*')
                    ->selectSub(function ($subQuery) use ($days) {
                        $subQuery->from('news_views')
                            ->whereColumn('news_views.news_id', 'news.id')
                            ->where('news_views.viewed_at', '>=', now()->subDays($days))
                            ->selectRaw('COUNT(*)');
                    }, 'view_count')
                    ->whereHas('views', function($viewQuery) use ($days) {
                        $viewQuery->where('viewed_at', '>=', now()->subDays($days));
                    })
                    ->orderByDesc('view_count');
    }

    /**
     * Scope to get related articles.
     */
    public function scopeRelated(Builder $query, News $news, int $limit = 5): Builder
    {
        return $query->where('id', '!=', $news->id)
                    ->where('category_id', $news->category_id)
                    ->published()
                    ->latest()
                    ->limit($limit);
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
     * Get the URL for this news article.
     */
    public function getUrlAttribute(): string
    {
        return route('news.show', $this->slug);
    }

    /**
     * Get the reading time estimate.
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $readingSpeed = 200; // words per minute
        
        return max(1, ceil($wordCount / $readingSpeed));
    }

    /**
     * Get the publication status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'published' => '<span class="badge badge-success">Published</span>',
            'draft' => '<span class="badge badge-warning">Draft</span>',
            'archived' => '<span class="badge badge-secondary">Archived</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Check if article is published.
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published' && $this->published_at <= now();
    }

    /**
     * Get meta keywords as comma-separated string.
     */
    public function getMetaKeywordsStringAttribute(): string
    {
        if (is_array($this->meta_keywords)) {
            return implode(', ', $this->meta_keywords);
        }
        
        return $this->meta_keywords ?? '';
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

    /**
     * Set meta keywords from string.
     */
    public function setMetaKeywordsStringAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['meta_keywords'] = array_map('trim', explode(',', $value));
        }
    }

    // Helper Methods

    /**
     * Increment the views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Record a view for analytics.
     */
    public function recordView(array $data = []): NewsView
    {
        $view = $this->views()->create(array_merge([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'user_id' => auth()->id(),
            'viewed_at' => now(),
        ], $data));

        $this->incrementViews();

        return $view;
    }

    /**
     * Get the likes count.
     */
    public function getLikesCount(): int
    {
        return $this->likes()->where('type', 'like')->count();
    }

    /**
     * Get the dislikes count.
     */
    public function getDislikesCount(): int
    {
        return $this->likes()->where('type', 'dislike')->count();
    }

    /**
     * Check if user has liked this article.
     */
    public function isLikedBy($user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->likes()
                   ->where('user_id', $user->id)
                   ->where('type', 'like')
                   ->exists();
    }

    /**
     * Get related articles.
     */
    public function getRelatedArticles(int $limit = 5)
    {
        return static::related($this, $limit)->get();
    }

    /**
     * Publish the article.
     */
    public function publish(): bool
    {
        return $this->update([
            'status' => 'published',
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /**
     * Archive the article.
     */
    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }

    /**
     * Move to draft.
     */
    public function makeDraft(): bool
    {
        return $this->update(['status' => 'draft']);
    }

    /**
     * Check if article allows comments.
     */
    public function allowsComments(): bool
    {
        return $this->allow_comments && $this->is_published;
    }

    /**
     * Get comments count.
     */
    public function getCommentsCount(): int
    {
        return $this->approvedComments()->count();
    }
}