<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Advertisement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'rate',
        'rate_type',
        'media_id',
        'html_content',
        'click_url',
        'tracking_code',
        'position',
        'sort_order',
        'display_rules',
        'status',
        'starts_at',
        'expires_at',
        'impressions',
        'clicks',
        'click_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'click_rate' => 'decimal:2',
            'sort_order' => 'integer',
            'impressions' => 'integer',
            'clicks' => 'integer',
            'display_rules' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'deleted_at',
        'starts_at',
        'expires_at',
    ];

    // Relationships

    /**
     * Get the media associated with this advertisement.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    // Scopes

    /**
     * Scope a query to only include active advertisements.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive advertisements.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to only include expired advertisements.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired')
                    ->orWhere('expires_at', '<', now());
    }

    /**
     * Scope a query to only include advertisements that should be displayed now.
     */
    public function scopeDisplayable(Builder $query): Builder
    {
        return $query->active()
                    ->where(function ($q) {
                        $q->whereNull('starts_at')
                          ->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope a query to filter by position.
     */
    public function scopePosition(Builder $query, string $position): Builder
    {
        return $query->where('position', $position);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Scope a query to order by performance (CTR).
     */
    public function scopeByPerformance(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('click_rate', $direction);
    }

    // Accessors

    /**
     * Get the advertisement image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->media?->url;
    }

    /**
     * Check if advertisement is currently active.
     */
    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();
        
        if ($this->starts_at && $this->starts_at > $now) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < $now) {
            return false;
        }

        return true;
    }

    /**
     * Check if advertisement has expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Check if advertisement is scheduled for future.
     */
    public function getIsScheduledAttribute(): bool
    {
        return $this->starts_at && $this->starts_at > now();
    }

    /**
     * Get the remaining days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Get the status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_expired) {
            return '<span class="badge badge-danger">Expired</span>';
        }

        if ($this->is_scheduled) {
            return '<span class="badge badge-info">Scheduled</span>';
        }

        return match($this->status) {
            'active' => '<span class="badge badge-success">Active</span>',
            'inactive' => '<span class="badge badge-secondary">Inactive</span>',
            'expired' => '<span class="badge badge-danger">Expired</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Get the performance metrics.
     */
    public function getPerformanceAttribute(): array
    {
        return [
            'impressions' => $this->impressions,
            'clicks' => $this->clicks,
            'ctr' => $this->click_rate,
            'cost' => $this->calculateCost(),
        ];
    }

    // Helper Methods

    /**
     * Record an impression.
     */
    public function recordImpression(): void
    {
        $this->increment('impressions');
        $this->updateClickRate();
    }

    /**
     * Record a click.
     */
    public function recordClick(): void
    {
        $this->increment('clicks');
        $this->updateClickRate();
    }

    /**
     * Update the click-through rate.
     */
    public function updateClickRate(): void
    {
        if ($this->impressions > 0) {
            $ctr = ($this->clicks / $this->impressions) * 100;
            $this->update(['click_rate' => round($ctr, 2)]);
        }
    }

    /**
     * Calculate the cost based on rate type.
     */
    public function calculateCost(): float
    {
        return match($this->rate_type) {
            'cpm' => ($this->impressions / 1000) * $this->rate,
            'cpc' => $this->clicks * $this->rate,
            'fixed' => $this->rate,
            default => 0,
        };
    }

    /**
     * Check if advertisement should be displayed based on rules.
     */
    public function shouldDisplay(array $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->display_rules) {
            return true;
        }

        // Implement custom display rules logic here
        // For example: check page type, user role, device type, etc.
        
        return true;
    }

    /**
     * Get advertisements for a specific position.
     */
    public static function getForPosition(string $position, int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::displayable()
                      ->position($position)
                      ->ordered();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Activate the advertisement.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the advertisement.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    /**
     * Clone advertisement.
     */
    public function duplicate(): self
    {
        $clone = $this->replicate();
        $clone->title = $this->title . ' (Copy)';
        $clone->status = 'inactive';
        $clone->impressions = 0;
        $clone->clicks = 0;
        $clone->click_rate = 0;
        $clone->save();

        return $clone;
    }

    // Model Events

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($advertisement) {
            // Auto-expire if past expiry date
            if ($advertisement->expires_at && $advertisement->expires_at < now()) {
                $advertisement->status = 'expired';
            }
        });
    }
}