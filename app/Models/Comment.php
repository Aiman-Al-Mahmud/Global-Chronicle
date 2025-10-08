<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'news_id',
        'user_id',
        'parent_id',
        'guest_name',
        'guest_email',
        'ip_address',
        'content',
        'status',
        'is_pinned',
        'likes_count',
        'replies_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'likes_count' => 'integer',
            'replies_count' => 'integer',
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

    // Relationships

    /**
     * Get the news article this comment belongs to.
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Get the user who made this comment (if registered).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment (for replies).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the child comments (replies).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * Get all children recursively.
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    // Scopes

    /**
     * Scope a query to only include approved comments.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include pending comments.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include rejected comments.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to only include spam comments.
     */
    public function scopeSpam(Builder $query): Builder
    {
        return $query->where('status', 'spam');
    }

    /**
     * Scope a query to only include top-level comments (no parent).
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include replies.
     */
    public function scopeReplies(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope a query to only include pinned comments.
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

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
     * Scope a query to search comments.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('content', 'LIKE', "%{$term}%")
                    ->orWhere('guest_name', 'LIKE', "%{$term}%");
    }

    /**
     * Scope to order by latest.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by oldest.
     */
    public function scopeOldest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope to order by most liked.
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('likes_count', 'desc');
    }

    // Accessors

    /**
     * Get the commenter's name.
     */
    public function getCommenterNameAttribute(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Anonymous';
    }

    /**
     * Get the commenter's email.
     */
    public function getCommenterEmailAttribute(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    /**
     * Get the commenter's avatar.
     */
    public function getCommenterAvatarAttribute(): string
    {
        if ($this->user?->avatar) {
            return $this->user->avatar_url;
        }

        $email = $this->commenter_email;
        if ($email) {
            return 'https://www.gravatar.com/avatar/' . md5(strtolower($email)) . '?d=mp&s=60';
        }

        return 'https://www.gravatar.com/avatar/default?d=mp&s=60';
    }

    /**
     * Check if comment is from a registered user.
     */
    public function getIsFromRegisteredUserAttribute(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Check if comment is from a guest.
     */
    public function getIsFromGuestAttribute(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Check if comment is approved.
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if comment is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if comment is a reply.
     */
    public function getIsReplyAttribute(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if comment is top-level.
     */
    public function getIsTopLevelAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get the status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'approved' => '<span class="badge badge-success">Approved</span>',
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'rejected' => '<span class="badge badge-danger">Rejected</span>',
            'spam' => '<span class="badge badge-dark">Spam</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Get the comment depth (how nested it is).
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $comment = $this;
        
        while ($comment->parent) {
            $depth++;
            $comment = $comment->parent;
        }
        
        return $depth;
    }

    // Helper Methods

    /**
     * Approve the comment.
     */
    public function approve(): bool
    {
        return $this->update(['status' => 'approved']);
    }

    /**
     * Reject the comment.
     */
    public function reject(): bool
    {
        return $this->update(['status' => 'rejected']);
    }

    /**
     * Mark as spam.
     */
    public function markAsSpam(): bool
    {
        return $this->update(['status' => 'spam']);
    }

    /**
     * Pin the comment.
     */
    public function pin(): bool
    {
        return $this->update(['is_pinned' => true]);
    }

    /**
     * Unpin the comment.
     */
    public function unpin(): bool
    {
        return $this->update(['is_pinned' => false]);
    }

    /**
     * Toggle pin status.
     */
    public function togglePin(): bool
    {
        return $this->update(['is_pinned' => !$this->is_pinned]);
    }

    /**
     * Increment likes count.
     */
    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }

    /**
     * Decrement likes count.
     */
    public function decrementLikes(): void
    {
        $this->decrement('likes_count');
    }

    /**
     * Update replies count.
     */
    public function updateRepliesCount(): void
    {
        $count = $this->children()->approved()->count();
        $this->update(['replies_count' => $count]);
    }

    /**
     * Get the comment thread (all ancestors).
     */
    public function getThread(): \Illuminate\Support\Collection
    {
        $thread = collect();
        $comment = $this;
        
        while ($comment) {
            $thread->prepend($comment);
            $comment = $comment->parent;
        }
        
        return $thread;
    }

    /**
     * Get all descendants.
     */
    public function getDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }
        
        return $descendants;
    }

    /**
     * Check if user can edit this comment.
     */
    public function canEdit($user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        // Admin and editor can edit any comment
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // User can edit their own comment within time limit
        if ($this->user_id === $user->id) {
            // Allow editing within 15 minutes
            return $this->created_at->diffInMinutes() <= 15;
        }

        return false;
    }

    /**
     * Check if user can delete this comment.
     */
    public function canDelete($user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        // Admin and editor can delete any comment
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // User can delete their own comment
        if ($this->user_id === $user->id) {
            return true;
        }

        return false;
    }

    // Model Events

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($comment) {
            // Update parent's replies count
            if ($comment->parent) {
                $comment->parent->updateRepliesCount();
            }
        });

        static::deleted(function ($comment) {
            // Update parent's replies count
            if ($comment->parent) {
                $comment->parent->updateRepliesCount();
            }
        });

        static::saving(function ($comment) {
            // Set IP address if not provided
            if (empty($comment->ip_address)) {
                $comment->ip_address = request()->ip();
            }
        });
    }
}