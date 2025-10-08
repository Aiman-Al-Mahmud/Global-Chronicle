<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class RssFeed extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'description',
        'website_url',
        'category_id',
        'status',
        'language',
        'fetch_frequency',
        'max_items',
        'auto_publish',
        'parsing_rules',
        'last_fetched_at',
        'last_successful_fetch_at',
        'last_error',
        'total_items_fetched',
        'error_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auto_publish' => 'boolean',
            'parsing_rules' => 'array',
            'fetch_frequency' => 'integer',
            'max_items' => 'integer',
            'total_items_fetched' => 'integer',
            'error_count' => 'integer',
            'last_fetched_at' => 'datetime',
            'last_successful_fetch_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'deleted_at',
        'last_fetched_at',
        'last_successful_fetch_at',
    ];

    // Relationships

    /**
     * Get the category associated with this RSS feed.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the news articles imported from this RSS feed.
     */
    public function importedNews(): HasMany
    {
        return $this->hasMany(News::class, 'rss_url', 'url');
    }

    // Scopes

    /**
     * Scope a query to only include active RSS feeds.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive RSS feeds.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to only include feeds with errors.
     */
    public function scopeWithErrors(Builder $query): Builder
    {
        return $query->where('status', 'error')
                    ->orWhere('error_count', '>', 0);
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
     * Scope a query to filter feeds that need fetching.
     */
    public function scopeNeedsFetching(Builder $query): Builder
    {
        return $query->active()
                    ->where(function ($q) {
                        $q->whereNull('last_fetched_at')
                          ->orWhereRaw('last_fetched_at < DATE_SUB(NOW(), INTERVAL fetch_frequency MINUTE)');
                    });
    }

    /**
     * Scope a query to search RSS feeds.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('url', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
    }

    // Accessors

    /**
     * Check if RSS feed is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if RSS feed has errors.
     */
    public function getHasErrorsAttribute(): bool
    {
        return $this->status === 'error' || $this->error_count > 0;
    }

    /**
     * Get the next fetch time.
     */
    public function getNextFetchAtAttribute(): ?\Carbon\Carbon
    {
        if (!$this->last_fetched_at) {
            return now();
        }

        return $this->last_fetched_at->addMinutes($this->fetch_frequency);
    }

    /**
     * Check if feed is ready for fetching.
     */
    public function getIsReadyForFetchAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_fetched_at) {
            return true;
        }

        return $this->next_fetch_at <= now();
    }

    /**
     * Get the time since last fetch.
     */
    public function getTimeSinceLastFetchAttribute(): ?string
    {
        if (!$this->last_fetched_at) {
            return 'Never';
        }

        return $this->last_fetched_at->diffForHumans();
    }

    /**
     * Get the success rate.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_items_fetched == 0) {
            return 0;
        }

        return round(((max(0, $this->total_items_fetched - $this->error_count)) / $this->total_items_fetched) * 100, 2);
    }

    /**
     * Get the status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => '<span class="badge badge-success">Active</span>',
            'inactive' => '<span class="badge badge-secondary">Inactive</span>',
            'error' => '<span class="badge badge-danger">Error</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Get the health status.
     */
    public function getHealthStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->error_count > 10) {
            return 'poor';
        }

        if ($this->error_count > 5) {
            return 'fair';
        }

        if ($this->success_rate > 95) {
            return 'excellent';
        }

        if ($this->success_rate > 80) {
            return 'good';
        }

        return 'fair';
    }

    // Helper Methods

    /**
     * Activate the RSS feed.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the RSS feed.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Mark as error status.
     */
    public function markAsError(string $error = null): bool
    {
        return $this->update([
            'status' => 'error',
            'last_error' => $error,
            'error_count' => $this->error_count + 1,
        ]);
    }

    /**
     * Record successful fetch.
     */
    public function recordSuccessfulFetch(int $itemsCount = 0): bool
    {
        return $this->update([
            'status' => 'active',
            'last_fetched_at' => now(),
            'last_successful_fetch_at' => now(),
            'last_error' => null,
            'total_items_fetched' => $this->total_items_fetched + $itemsCount,
        ]);
    }

    /**
     * Record failed fetch.
     */
    public function recordFailedFetch(string $error): bool
    {
        return $this->update([
            'last_fetched_at' => now(),
            'last_error' => $error,
            'error_count' => $this->error_count + 1,
        ]);
    }

    /**
     * Reset error count.
     */
    public function resetErrors(): bool
    {
        return $this->update([
            'error_count' => 0,
            'last_error' => null,
        ]);
    }

    /**
     * Test the RSS feed URL.
     */
    public function testConnection(): array
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'News RSS Reader/1.0',
                ]
            ]);

            $content = file_get_contents($this->url, false, $context);
            
            if ($content === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch RSS feed content',
                ];
            }

            // Try to parse as XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                return [
                    'success' => false,
                    'error' => 'Invalid XML format: ' . ($errors[0]->message ?? 'Unknown XML error'),
                ];
            }

            // Check if it's a valid RSS/Atom feed
            if (!isset($xml->channel) && !isset($xml->entry)) {
                return [
                    'success' => false,
                    'error' => 'Not a valid RSS or Atom feed',
                ];
            }

            return [
                'success' => true,
                'message' => 'RSS feed is accessible and valid',
                'items_found' => isset($xml->channel->item) ? count($xml->channel->item) : (isset($xml->entry) ? count($xml->entry) : 0),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get parsing rule by key.
     */
    public function getParsingRule(string $key, $default = null)
    {
        return data_get($this->parsing_rules, $key, $default);
    }

    /**
     * Set parsing rule.
     */
    public function setParsingRule(string $key, $value): void
    {
        $rules = $this->parsing_rules ?? [];
        data_set($rules, $key, $value);
        $this->parsing_rules = $rules;
        $this->save();
    }

    /**
     * Get the count of imported news articles.
     */
    public function getImportedNewsCount(): int
    {
        return $this->importedNews()->count();
    }

    /**
     * Get recently imported news.
     */
    public function getRecentlyImportedNews(int $limit = 10)
    {
        return $this->importedNews()
                   ->orderBy('created_at', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Duplicate RSS feed.
     */
    public function duplicate(): self
    {
        $clone = $this->replicate();
        $clone->name = $this->name . ' (Copy)';
        $clone->status = 'inactive';
        $clone->last_fetched_at = null;
        $clone->last_successful_fetch_at = null;
        $clone->last_error = null;
        $clone->total_items_fetched = 0;
        $clone->error_count = 0;
        $clone->save();

        return $clone;
    }

    // Static Methods

    /**
     * Get feeds that need fetching.
     */
    public static function getFeedsToFetch(): \Illuminate\Database\Eloquent\Collection
    {
        return static::needsFetching()->get();
    }

    /**
     * Get feed statistics.
     */
    public static function getStatistics(): array
    {
        return [
            'total_feeds' => static::count(),
            'active_feeds' => static::active()->count(),
            'inactive_feeds' => static::inactive()->count(),
            'error_feeds' => static::withErrors()->count(),
            'total_items_fetched' => static::sum('total_items_fetched'),
        ];
    }

    // Model Events

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($feed) {
            // Set default values
            if (empty($feed->fetch_frequency)) {
                $feed->fetch_frequency = 60; // 1 hour
            }
            
            if (empty($feed->max_items)) {
                $feed->max_items = 10;
            }
        });
    }
}