<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'title',
        'description',
        'original_name',
        'file_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
        'dimensions',
        'alt_text',
        'is_visible',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'dimensions' => 'array',
            'file_size' => 'integer',
        ];
    }

    // Relationships

    /**
     * Get the news articles that use this media as featured image.
     */
    public function newsAsFeaturedImage(): HasMany
    {
        return $this->hasMany(News::class, 'featured_image_id');
    }

    /**
     * Get the advertisements that use this media.
     */
    public function advertisements(): HasMany
    {
        return $this->hasMany(Advertisement::class, 'media_id');
    }

    // Scopes

    /**
     * Scope a query to only include visible media.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope a query to filter by media type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by images only.
     */
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    /**
     * Scope a query to filter by videos only.
     */
    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    /**
     * Scope a query to search media by title or description.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%")
                    ->orWhere('original_name', 'LIKE', "%{$term}%");
    }

    /**
     * Scope to order by file size.
     */
    public function scopeOrderBySize($query, $direction = 'desc')
    {
        return $query->orderBy('file_size', $direction);
    }

    // Accessors

    /**
     * Get the full URL for this media file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    /**
     * Get the absolute path for this media file.
     */
    public function getPathAttribute(): string
    {
        return Storage::disk($this->disk)->path($this->file_path);
    }

    /**
     * Get the file size in human readable format.
     */
    public function getHumanFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get width from dimensions.
     */
    public function getWidthAttribute(): ?int
    {
        return $this->dimensions['width'] ?? null;
    }

    /**
     * Get height from dimensions.
     */
    public function getHeightAttribute(): ?int
    {
        return $this->dimensions['height'] ?? null;
    }

    /**
     * Check if media is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Check if media is a video.
     */
    public function getIsVideoAttribute(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Check if media is audio.
     */
    public function getIsAudioAttribute(): bool
    {
        return $this->type === 'audio';
    }

    /**
     * Check if media is a document.
     */
    public function getIsDocumentAttribute(): bool
    {
        return $this->type === 'document';
    }

    // Helper Methods

    /**
     * Check if the file exists on disk.
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->file_path);
    }

    /**
     * Delete the media file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::disk($this->disk)->delete($this->file_path);
        }
        
        return true;
    }

    /**
     * Get thumbnail URL (for images).
     */
    public function getThumbnailUrl(int $width = 300, int $height = 200): string
    {
        if (!$this->is_image) {
            return $this->url;
        }

        // You can implement image resizing logic here
        // For now, return the original URL
        return $this->url;
    }

    /**
     * Get responsive image srcset.
     */
    public function getResponsiveSrcset(): string
    {
        if (!$this->is_image) {
            return $this->url;
        }

        // You can implement responsive image logic here
        // For now, return the original URL
        return $this->url;
    }

    /**
     * Get file extension.
     */
    public function getExtension(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Check if file is web-safe image format.
     */
    public function isWebSafeImage(): bool
    {
        if (!$this->is_image) {
            return false;
        }

        $webSafeFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        return in_array(strtolower($this->getExtension()), $webSafeFormats);
    }

    // Model Events

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($media) {
            // Delete the file when the model is deleted
            $media->deleteFile();
        });
    }
}