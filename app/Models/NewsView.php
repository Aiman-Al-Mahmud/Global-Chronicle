<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class NewsView extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'news_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
        'device_info',
        'viewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'device_info' => 'array',
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    // Relationships

    /**
     * Get the news article that was viewed.
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Get the user who viewed the article (if registered).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    /**
     * Scope a query to filter by news article.
     */
    public function scopeForNews(Builder $query, $newsId): Builder
    {
        return $query->where('news_id', $newsId);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by IP address.
     */
    public function scopeByIp(Builder $query, $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope a query to filter by registered users only.
     */
    public function scopeRegisteredUsers(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope a query to filter by anonymous users only.
     */
    public function scopeAnonymousUsers(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('viewed_at', today());
    }

    /**
     * Scope a query to filter by this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('viewed_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to filter by this month.
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('viewed_at', now()->month)
                    ->whereYear('viewed_at', now()->year);
    }

    /**
     * Scope a query to filter by last N days.
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('viewed_at', '>=', now()->subDays($days));
    }

    // Accessors

    /**
     * Check if view is from a registered user.
     */
    public function getIsFromRegisteredUserAttribute(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Check if view is from an anonymous user.
     */
    public function getIsFromAnonymousUserAttribute(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Get the viewer identifier.
     */
    public function getViewerIdentifierAttribute(): string
    {
        if ($this->user_id) {
            return "User #{$this->user_id}";
        }
        
        return "IP: {$this->ip_address}";
    }

    /**
     * Get the device type from device info.
     */
    public function getDeviceTypeAttribute(): ?string
    {
        return $this->device_info['type'] ?? null;
    }

    /**
     * Get the browser from device info.
     */
    public function getBrowserAttribute(): ?string
    {
        return $this->device_info['browser'] ?? null;
    }

    /**
     * Get the operating system from device info.
     */
    public function getOperatingSystemAttribute(): ?string
    {
        return $this->device_info['os'] ?? null;
    }

    /**
     * Get the referer domain.
     */
    public function getRefererDomainAttribute(): ?string
    {
        if (!$this->referer) {
            return null;
        }

        $parsed = parse_url($this->referer);
        return $parsed['host'] ?? null;
    }

    // Static Methods

    /**
     * Record a view for a news article.
     */
    public static function recordView(
        int $newsId, 
        ?int $userId = null, 
        ?string $ip = null, 
        ?string $userAgent = null,
        ?string $referer = null,
        ?array $deviceInfo = null
    ): self {
        return static::create([
            'news_id' => $newsId,
            'user_id' => $userId,
            'ip_address' => $ip ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'referer' => $referer ?? request()->header('referer'),
            'device_info' => $deviceInfo ?? static::parseDeviceInfo(request()->userAgent()),
            'viewed_at' => now(),
        ]);
    }

    /**
     * Parse device information from user agent.
     */
    protected static function parseDeviceInfo(?string $userAgent): array
    {
        if (!$userAgent) {
            return [];
        }

        // Simple device detection (you can use a more sophisticated library)
        $info = [];

        // Detect device type
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $info['type'] = 'mobile';
        } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
            $info['type'] = 'tablet';
        } else {
            $info['type'] = 'desktop';
        }

        // Detect browser
        if (preg_match('/Chrome/i', $userAgent)) {
            $info['browser'] = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $info['browser'] = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $info['browser'] = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $info['browser'] = 'Edge';
        }

        // Detect OS
        if (preg_match('/Windows/i', $userAgent)) {
            $info['os'] = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $info['os'] = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $info['os'] = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $info['os'] = 'Android';
        } elseif (preg_match('/iOS/i', $userAgent)) {
            $info['os'] = 'iOS';
        }

        return $info;
    }

    /**
     * Get view statistics for a news article.
     */
    public static function getStatsForNews(int $newsId): array
    {
        $views = static::forNews($newsId);

        return [
            'total_views' => $views->count(),
            'unique_visitors' => $views->distinct('ip_address')->count('ip_address'),
            'registered_users' => $views->registeredUsers()->distinct('user_id')->count('user_id'),
            'today_views' => $views->today()->count(),
            'this_week_views' => $views->thisWeek()->count(),
            'this_month_views' => $views->thisMonth()->count(),
        ];
    }

    /**
     * Get trending articles based on views.
     */
    public static function getTrendingArticles(int $days = 7, int $limit = 10): \Illuminate\Support\Collection
    {
        return static::select('news_id')
                    ->with('news')
                    ->lastDays($days)
                    ->groupBy('news_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit($limit)
                    ->get()
                    ->pluck('news');
    }

    /**
     * Get view analytics for a date range.
     */
    public static function getAnalytics($startDate, $endDate): array
    {
        $views = static::dateRange($startDate, $endDate);

        return [
            'total_views' => $views->count(),
            'unique_visitors' => $views->distinct('ip_address')->count('ip_address'),
            'daily_breakdown' => $views->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
                                     ->groupBy('date')
                                     ->orderBy('date')
                                     ->get()
                                     ->keyBy('date')
                                     ->toArray(),
            'device_breakdown' => $views->get()
                                       ->groupBy('device_type')
                                       ->map->count()
                                       ->toArray(),
            'browser_breakdown' => $views->get()
                                        ->groupBy('browser')
                                        ->map->count()
                                        ->toArray(),
        ];
    }

    /**
     * Clean old view records.
     */
    public static function cleanOldRecords(int $daysToKeep = 90): int
    {
        return static::where('viewed_at', '<', now()->subDays($daysToKeep))->delete();
    }
}