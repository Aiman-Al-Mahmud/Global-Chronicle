<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group',
        'is_public',
        'is_autoload',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_public' => 'boolean',
            'is_autoload' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Cache key for settings.
     */
    protected static string $cacheKey = 'app_settings';

    // Scopes

    /**
     * Scope a query to only include public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include autoload settings.
     */
    public function scopeAutoload($query)
    {
        return $query->where('is_autoload', true);
    }

    /**
     * Scope a query to filter by group.
     */
    public function scopeGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope a query to search settings.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('key', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
    }

    /**
     * Scope to order by group and sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('group')->orderBy('sort_order')->orderBy('key');
    }

    // Accessors

    /**
     * Get the decoded value based on type.
     */
    public function getDecodedValueAttribute()
    {
        return $this->decodeValue($this->value, $this->type);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    // Helper Methods

    /**
     * Decode value based on type.
     */
    protected function decodeValue($value, $type)
    {
        if (is_array($value)) {
            $value = $value[0] ?? $value;
        }

        return match($type) {
            'boolean' => (bool) $value,
            'number', 'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'array', 'object' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Encode value based on type.
     */
    protected function encodeValue($value, $type)
    {
        return match($type) {
            'boolean' => (bool) $value,
            'number', 'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'array', 'object' => is_array($value) ? $value : [$value],
            default => (string) $value,
        };
    }

    // Static Methods

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $settings = static::getAllCached();
        
        if (isset($settings[$key])) {
            return $settings[$key]['decoded_value'];
        }

        return $default;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, $value, string $type = 'string', array $options = []): self
    {
        $setting = static::firstOrNew(['key' => $key]);
        
        $setting->fill(array_merge([
            'value' => $setting->encodeValue($value, $type),
            'type' => $type,
            'group' => 'general',
            'is_public' => false,
            'is_autoload' => false,
            'sort_order' => 0,
        ], $options));

        $setting->save();
        
        // Clear cache
        static::clearCache();
        
        return $setting;
    }

    /**
     * Check if a setting exists.
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Delete a setting.
     */
    public static function forget(string $key): bool
    {
        $deleted = static::where('key', $key)->delete();
        
        if ($deleted) {
            static::clearCache();
        }
        
        return $deleted > 0;
    }

    /**
     * Get all settings grouped by key.
     */
    public static function getAll(): array
    {
        return static::all()->keyBy('key')->map(function ($setting) {
            return [
                'value' => $setting->value,
                'decoded_value' => $setting->decoded_value,
                'type' => $setting->type,
                'description' => $setting->description,
                'group' => $setting->group,
                'is_public' => $setting->is_public,
                'is_autoload' => $setting->is_autoload,
            ];
        })->toArray();
    }

    /**
     * Get all settings with caching.
     */
    public static function getAllCached(): array
    {
        return Cache::remember(static::$cacheKey, 3600, function () {
            return static::getAll();
        });
    }

    /**
     * Get settings by group.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->get()->keyBy('key')->map(function ($setting) {
            return $setting->decoded_value;
        })->toArray();
    }

    /**
     * Get public settings (for frontend).
     */
    public static function getPublic(): array
    {
        return static::public()->get()->keyBy('key')->map(function ($setting) {
            return $setting->decoded_value;
        })->toArray();
    }

    /**
     * Get autoload settings.
     */
    public static function getAutoload(): array
    {
        return static::autoload()->get()->keyBy('key')->map(function ($setting) {
            return $setting->decoded_value;
        })->toArray();
    }

    /**
     * Set multiple settings at once.
     */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $config) {
            if (is_array($config)) {
                static::set(
                    $key,
                    $config['value'],
                    $config['type'] ?? 'string',
                    array_except($config, ['value', 'type'])
                );
            } else {
                static::set($key, $config);
            }
        }
    }

    /**
     * Clear settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(static::$cacheKey);
    }

    /**
     * Initialize default settings.
     */
    public static function initializeDefaults(): void
    {
        $defaults = [
            'site_name' => [
                'value' => 'News Website',
                'type' => 'string',
                'description' => 'The name of the website',
                'group' => 'general',
                'is_public' => true,
                'is_autoload' => true,
            ],
            'site_description' => [
                'value' => 'Latest news and articles',
                'type' => 'string',
                'description' => 'Website description for SEO',
                'group' => 'general',
                'is_public' => true,
                'is_autoload' => true,
            ],
            'site_logo' => [
                'value' => '',
                'type' => 'string',
                'description' => 'Website logo URL',
                'group' => 'general',
                'is_public' => true,
                'is_autoload' => true,
            ],
            'articles_per_page' => [
                'value' => 12,
                'type' => 'integer',
                'description' => 'Number of articles per page',
                'group' => 'display',
                'is_public' => true,
                'is_autoload' => true,
            ],
            'enable_comments' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable comments on articles',
                'group' => 'features',
                'is_public' => true,
                'is_autoload' => true,
            ],
            'moderate_comments' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Moderate comments before publishing',
                'group' => 'features',
                'is_public' => false,
                'is_autoload' => true,
            ],
            'enable_rss' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable RSS feeds',
                'group' => 'features',
                'is_public' => true,
                'is_autoload' => true,
            ],
        ];

        static::setMany($defaults);
    }

    // Model Events

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }
}