<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
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
        'visitor_ip',
        'user_agent',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            //
        ];
    }

    // Relationships

    /**
     * Get the news article that was liked.
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Get the user who liked the article (if registered).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    /**
     * Scope a query to only include likes.
     */
    public function scopeLikes($query)
    {
        return $query->where('type', 'like');
    }

    /**
     * Scope a query to only include dislikes.
     */
    public function scopeDislikes($query)
    {
        return $query->where('type', 'dislike');
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by IP address.
     */
    public function scopeByIp($query, $ip)
    {
        return $query->where('visitor_ip', $ip);
    }

    /**
     * Scope a query to filter by news article.
     */
    public function scopeForNews($query, $newsId)
    {
        return $query->where('news_id', $newsId);
    }

    // Helper Methods

    /**
     * Check if this is a like.
     */
    public function isLike(): bool
    {
        return $this->type === 'like';
    }

    /**
     * Check if this is a dislike.
     */
    public function isDislike(): bool
    {
        return $this->type === 'dislike';
    }

    /**
     * Check if this like is from a registered user.
     */
    public function isFromRegisteredUser(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Check if this like is from an anonymous user.
     */
    public function isFromAnonymousUser(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Toggle like/dislike.
     */
    public function toggle(): self
    {
        $this->type = $this->type === 'like' ? 'dislike' : 'like';
        $this->save();
        
        return $this;
    }

    /**
     * Create or update a like for a news article.
     */
    public static function createOrUpdate(int $newsId, string $type = 'like', ?int $userId = null, ?string $ip = null): self
    {
        $ip = $ip ?? request()->ip();
        $userAgent = request()->userAgent();

        // Build the query conditions
        $conditions = ['news_id' => $newsId];
        
        if ($userId) {
            $conditions['user_id'] = $userId;
        } else {
            $conditions['visitor_ip'] = $ip;
            $conditions['user_id'] = null;
        }

        // Find existing like or create new one
        $like = static::where($conditions)->first();

        if ($like) {
            // If same type, remove the like/dislike
            if ($like->type === $type) {
                $like->delete();
                return $like;
            } else {
                // Otherwise, toggle the type
                $like->type = $type;
                $like->save();
                return $like;
            }
        }

        // Create new like
        return static::create([
            'news_id' => $newsId,
            'user_id' => $userId,
            'visitor_ip' => $ip,
            'user_agent' => $userAgent,
            'type' => $type,
        ]);
    }

    /**
     * Get user identifier (user ID or IP).
     */
    public function getUserIdentifier(): string
    {
        return $this->user_id ? "user:{$this->user_id}" : "ip:{$this->visitor_ip}";
    }
}