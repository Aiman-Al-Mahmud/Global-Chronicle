<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $settings = Setting::ordered()->get()->groupBy('group');
        return view('admin.settings.settings', compact('settings'));
    }

    /**
     * Update application settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            // Site Settings
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'site_keywords' => 'nullable|string|max:500',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'site_favicon' => 'nullable|image|mimes:ico,png|max:512',
            'contact_email' => 'required|email|max:255',
            'admin_email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            
            // Social Media
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            
            // SEO Settings
            'seo_title_template' => 'nullable|string|max:255',
            'seo_description_template' => 'nullable|string|max:500',
            'google_analytics_id' => 'nullable|string|max:50',
            'google_adsense_id' => 'nullable|string|max:50',
            'google_tag_manager_id' => 'nullable|string|max:50',
            
            // Email Settings
            'mail_driver' => 'required|in:smtp,mailgun,ses,sendmail',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
            
            // Content Settings
            'posts_per_page' => 'required|integer|min:1|max:50',
            'enable_comments' => 'boolean',
            'auto_approve_comments' => 'boolean',
            'enable_likes' => 'boolean',
            'enable_newsletter' => 'boolean',
            'enable_rss' => 'boolean',
            'default_post_status' => 'required|in:published,draft',
            
            // Media Settings
            'max_upload_size' => 'required|integer|min:1|max:102400', // Max 100MB
            'allowed_file_types' => 'nullable|string|max:500',
            'image_quality' => 'required|integer|min:1|max:100',
            'generate_thumbnails' => 'boolean',
            
            // Cache Settings
            'enable_cache' => 'boolean',
            'cache_ttl' => 'required|integer|min:1|max:86400', // Max 24 hours
            
            // Security Settings
            'enable_captcha' => 'boolean',
            'recaptcha_site_key' => 'nullable|string|max:255',
            'recaptcha_secret_key' => 'nullable|string|max:255',
            'max_login_attempts' => 'required|integer|min:1|max:10',
            'lockout_duration' => 'required|integer|min:1|max:1440', // Max 24 hours in minutes
            
            // Appearance Settings
            'theme' => 'required|string|max:50',
            'primary_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'enable_dark_mode' => 'boolean',
            
            // Custom Code
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'header_scripts' => 'nullable|string',
            'footer_scripts' => 'nullable|string',
        ]);

        $settings = $request->except(['_token', '_method', 'site_logo', 'site_favicon']);

        // Handle file uploads
        if ($request->hasFile('site_logo')) {
            $logoPath = $request->file('site_logo')->store('settings', 'public');
            $settings['site_logo'] = $logoPath;
        }

        if ($request->hasFile('site_favicon')) {
            $faviconPath = $request->file('site_favicon')->store('settings', 'public');
            $settings['site_favicon'] = $faviconPath;
        }

        // Update settings
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear settings cache
        Cache::forget('app_settings');

        if ($request->expectsJson()) {
            return $this->successResponse('Settings updated successfully.');
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Get specific setting value.
     */
    public function getSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255',
        ]);

        $setting = Setting::where('key', $request->key)->first();

        if (!$setting) {
            return $this->errorResponse('Setting not found.');
        }

        return $this->successResponse('Setting retrieved successfully.', [
            'key' => $setting->key,
            'value' => $setting->value,
        ]);
    }

    /**
     * Update specific setting.
     */
    public function updateSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
        ]);

        Setting::updateOrCreate(
            ['key' => $request->key],
            ['value' => $request->value]
        );

        // Clear settings cache
        Cache::forget('app_settings');

        return $this->successResponse('Setting updated successfully.');
    }

    /**
     * Clear application cache.
     */
    public function clearCache(Request $request)
    {
        try {
            // Clear various caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            if ($request->expectsJson()) {
                return $this->successResponse('Cache cleared successfully.');
            }

            return redirect()->back()->with('success', 'Cache cleared successfully.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Failed to clear cache: ' . $e->getMessage());
            }

            return redirect()->back()->withErrors(['error' => 'Failed to clear cache.']);
        }
    }

    /**
     * Optimize application.
     */
    public function optimize(Request $request)
    {
        try {
            // Run optimization commands
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            if ($request->expectsJson()) {
                return $this->successResponse('Application optimized successfully.');
            }

            return redirect()->back()->with('success', 'Application optimized successfully.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Failed to optimize application: ' . $e->getMessage());
            }

            return redirect()->back()->withErrors(['error' => 'Failed to optimize application.']);
        }
    }

    /**
     * Test email configuration.
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        try {
            \Mail::raw('This is a test email from your news website.', function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject('Test Email - ' . config('app.name'));
            });

            return $this->successResponse('Test email sent successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send test email: ' . $e->getMessage());
        }
    }

    /**
     * Export settings as JSON.
     */
    public function exportSettings()
    {
        $settings = Setting::all()->pluck('value', 'key');

        $filename = 'settings_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($settings)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Import settings from JSON file.
     */
    public function importSettings(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json|max:2048',
        ]);

        try {
            $file = $request->file('settings_file');
            $content = file_get_contents($file->getRealPath());
            $settings = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->errorResponse('Invalid JSON file.');
            }

            foreach ($settings as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            // Clear settings cache
            Cache::forget('app_settings');

            if ($request->expectsJson()) {
                return $this->successResponse('Settings imported successfully.');
            }

            return redirect()->back()->with('success', 'Settings imported successfully.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Failed to import settings: ' . $e->getMessage());
            }

            return redirect()->back()->withErrors(['error' => 'Failed to import settings.']);
        }
    }

    /**
     * Reset settings to default.
     */
    public function resetToDefault(Request $request)
    {
        try {
            // Get default settings
            $defaultSettings = $this->getDefaultSettings();

            foreach ($defaultSettings as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            // Clear settings cache
            Cache::forget('app_settings');

            if ($request->expectsJson()) {
                return $this->successResponse('Settings reset to default successfully.');
            }

            return redirect()->back()->with('success', 'Settings reset to default successfully.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Failed to reset settings: ' . $e->getMessage());
            }

            return redirect()->back()->withErrors(['error' => 'Failed to reset settings.']);
        }
    }

    /**
     * Get default settings.
     */
    private function getDefaultSettings(): array
    {
        return [
            'site_name' => 'News Website',
            'site_description' => 'Your trusted source for news',
            'contact_email' => 'contact@example.com',
            'admin_email' => 'admin@example.com',
            'posts_per_page' => 10,
            'enable_comments' => true,
            'auto_approve_comments' => false,
            'enable_likes' => true,
            'enable_newsletter' => true,
            'enable_rss' => true,
            'default_post_status' => 'draft',
            'max_upload_size' => 10240, // 10MB
            'image_quality' => 80,
            'generate_thumbnails' => true,
            'enable_cache' => true,
            'cache_ttl' => 3600, // 1 hour
            'enable_captcha' => false,
            'max_login_attempts' => 5,
            'lockout_duration' => 15, // 15 minutes
            'theme' => 'default',
            'enable_dark_mode' => false,
            'mail_driver' => 'smtp',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'News Website',
        ];
    }
}