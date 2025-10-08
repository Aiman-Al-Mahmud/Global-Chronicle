<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\RssController;
use App\Http\Controllers\Admin\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public Frontend Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/news', [HomeController::class, 'news'])->name('news.index');
Route::get('/news/{slug}', [HomeController::class, 'show'])->name('news.show');
Route::get('/category/{slug}', [HomeController::class, 'category'])->name('category.show');
Route::get('/tag/{slug}', [HomeController::class, 'tag'])->name('tag.show');
Route::get('/author/{id}', [HomeController::class, 'author'])->name('author.show');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'submitContact'])->name('contact.submit');

// Authentication Routes (Laravel 12 compatible)
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
    Route::get('/password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])->name('verification.resend');
});

// Public API Routes (for AJAX functionality)
Route::middleware('throttle:60,1')->group(function () {
    // Comments
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    
    // Likes
    Route::post('/news/{news}/like', [LikeController::class, 'toggle'])->name('news.like.toggle');
    Route::get('/news/{news}/likes', [LikeController::class, 'getLikes'])->name('news.likes.get');
    
    // Newsletter subscription
    Route::post('/newsletter/subscribe', [HomeController::class, 'subscribeNewsletter'])->name('newsletter.subscribe');
    Route::post('/newsletter/unsubscribe', [HomeController::class, 'unsubscribeNewsletter'])->name('newsletter.unsubscribe');
    
    // News views tracking
    Route::post('/news/{news}/view', [HomeController::class, 'recordView'])->name('news.view');
    
    // Advertisement tracking
    Route::post('/ads/{advertisement}/impression', [AdvertisementController::class, 'trackImpression'])->name('ads.impression');
    Route::get('/ads/{advertisement}/click', [AdvertisementController::class, 'trackClick'])->name('ads.click');
    Route::get('/ads/position/{position}', [AdvertisementController::class, 'getByPosition'])->name('ads.by.position');
});

// RSS Feeds
Route::get('/rss', [RssController::class, 'generateFeed'])->name('rss.feed');
Route::get('/rss/category/{category}', [RssController::class, 'generateFeed'])->name('rss.category.feed');

// Admin Routes
Route::middleware(['auth', 'role:admin,editor,author'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.main');
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'getRecentActivity'])->name('dashboard.activity');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart');
    
    // News Management
    Route::resource('news', NewsController::class);
    Route::post('/news/quick-create', [NewsController::class, 'quickCreate'])->name('news.quick.create');
    Route::post('/news/bulk-action', [NewsController::class, 'bulkAction'])->name('news.bulk.action');
    Route::post('/news/{news}/toggle-status', [NewsController::class, 'toggleStatus'])->name('news.toggle.status');
    Route::post('/news/{news}/toggle-featured', [NewsController::class, 'toggleFeatured'])->name('news.toggle.featured');
    Route::post('/news/{news}/clone', [NewsController::class, 'clone'])->name('news.clone');
    Route::get('/news/{news}/preview', [NewsController::class, 'preview'])->name('news.preview');
    Route::get('/news/{news}/analytics', [NewsController::class, 'getAnalytics'])->name('news.analytics');
    
    // Categories Management
    Route::resource('categories', CategoryController::class);
    Route::post('/categories/quick-create', [CategoryController::class, 'quickCreate'])->name('categories.quick.create');
    Route::post('/categories/bulk-action', [CategoryController::class, 'bulkAction'])->name('categories.bulk.action');
    Route::post('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle.status');
    
    // Tags Management
    Route::resource('tags', TagController::class);
    Route::post('/tags/quick-create', [TagController::class, 'quickCreate'])->name('tags.quick.create');
    Route::post('/tags/bulk-action', [TagController::class, 'bulkAction'])->name('tags.bulk.action');
    Route::post('/tags/{tag}/toggle-status', [TagController::class, 'toggleStatus'])->name('tags.toggle.status');
    Route::get('/tags/search', [TagController::class, 'search'])->name('tags.search');
    
    // Media Management
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/media/files', [MediaController::class, 'getFiles'])->name('media.files');
    Route::post('/media/bulk-action', [MediaController::class, 'bulkAction'])->name('media.bulk.action');
    Route::post('/media/{media}/thumbnail', [MediaController::class, 'generateThumbnail'])->name('media.thumbnail');
    Route::get('/media/statistics', [MediaController::class, 'getStatistics'])->name('media.statistics');
    
    // User Management (Admin and Editor only)
    Route::middleware('role:admin,editor')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('/users/quick-create', [UserController::class, 'quickCreate'])->name('users.quick.create');
        Route::post('/users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk.action');
        Route::get('/users/{user}/activity', [UserController::class, 'getUserActivity'])->name('users.activity');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset.password');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle.status');
    });
    
    // Advertisement Management
    Route::resource('advertisements', AdvertisementController::class);
    Route::post('/advertisements/quick-create', [AdvertisementController::class, 'quickCreate'])->name('advertisements.quick.create');
    Route::post('/advertisements/bulk-action', [AdvertisementController::class, 'bulkAction'])->name('advertisements.bulk.action');
    Route::post('/advertisements/{advertisement}/toggle-status', [AdvertisementController::class, 'toggleStatus'])->name('advertisements.toggle.status');
    Route::post('/advertisements/{advertisement}/clone', [AdvertisementController::class, 'clone'])->name('advertisements.clone');
    Route::get('/advertisements/performance/report', [AdvertisementController::class, 'getPerformanceReport'])->name('advertisements.performance.report');
    
    // RSS Management
    Route::resource('rss', RssController::class);
    Route::post('/rss/{rssFeed}/fetch', [RssController::class, 'fetch'])->name('rss.fetch');
    Route::post('/rss/bulk-fetch', [RssController::class, 'bulkFetch'])->name('rss.bulk.fetch');
    Route::post('/rss/test-feed', [RssController::class, 'testFeed'])->name('rss.test.feed');
    Route::post('/rss/bulk-action', [RssController::class, 'bulkAction'])->name('rss.bulk.action');
    Route::post('/rss/{rssFeed}/toggle-status', [RssController::class, 'toggleStatus'])->name('rss.toggle.status');
    
    // Settings (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/settings/get/{key}', [SettingsController::class, 'getSetting'])->name('settings.get');
        Route::post('/settings/update-setting', [SettingsController::class, 'updateSetting'])->name('settings.update.single');
        Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear.cache');
        Route::post('/settings/optimize', [SettingsController::class, 'optimize'])->name('settings.optimize');
        Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test.email');
        Route::get('/settings/export', [SettingsController::class, 'exportSettings'])->name('settings.export');
        Route::post('/settings/import', [SettingsController::class, 'importSettings'])->name('settings.import');
        Route::post('/settings/reset', [SettingsController::class, 'resetToDefault'])->name('settings.reset');
    });
    
    // Static Pages and Additional Routes
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/analytics', [PageController::class, 'analytics'])->name('analytics');
    Route::get('/analytics2', [PageController::class, 'analytics2'])->name('analytics2');
    Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
    Route::get('/gallery', [PageController::class, 'gallery'])->name('gallery');
    Route::get('/manage-news', [PageController::class, 'manageNews'])->name('manage.news');
    Route::get('/news-layout', [PageController::class, 'newsLayout'])->name('news.layout');
    Route::get('/newsletter', [PageController::class, 'newsletter'])->name('newsletter');
    Route::get('/newsletter2', [PageController::class, 'newsletter2'])->name('newsletter2');
    Route::get('/newsletter3', [PageController::class, 'newsletter3'])->name('newsletter3');
    Route::get('/profile', [PageController::class, 'profile'])->name('profile');
    Route::get('/subscribers', [PageController::class, 'subscribers'])->name('subscribers');
    Route::get('/general', [PageController::class, 'general'])->name('general');
    
    // Contact Management
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/contact/details', [PageController::class, 'contactDetails'])->name('contact.details');
    Route::get('/contact/reply', [PageController::class, 'contactReply'])->name('contact.reply');
    
    // API Routes for admin functionality
    Route::get('/api/page-info', [PageController::class, 'getPageInfo'])->name('api.page.info');
    Route::get('/api/sidebar-menu', [PageController::class, 'getSidebarMenu'])->name('api.sidebar.menu');
    Route::get('/api/quick-stats', [PageController::class, 'getQuickStats'])->name('api.quick.stats');
    Route::get('/api/search', [PageController::class, 'search'])->name('api.search');
    
    // Dynamic page routing (should be last)
    Route::get('/{page}', [PageController::class, 'dynamicPage'])->name('dynamic.page')->where('page', '.*');
});

// Admin API Routes (for AJAX calls)
Route::middleware(['auth', 'role:admin,editor,author'])->prefix('api/admin')->name('api.admin.')->group(function () {
    
    // Dashboard API
    Route::get('/dashboard/overview', [DashboardController::class, 'getOverview'])->name('dashboard.overview');
    Route::get('/dashboard/recent-news', [DashboardController::class, 'getRecentNews'])->name('dashboard.recent.news');
    Route::get('/dashboard/popular-news', [DashboardController::class, 'getPopularNews'])->name('dashboard.popular.news');
    
    // News API
    Route::get('/news/search', [NewsController::class, 'search'])->name('news.search');
    Route::get('/news/{news}/related', [NewsController::class, 'getRelated'])->name('news.related');
    Route::post('/news/{news}/publish', [NewsController::class, 'publish'])->name('news.publish');
    Route::post('/news/{news}/unpublish', [NewsController::class, 'unpublish'])->name('news.unpublish');
    
    // Category API
    Route::get('/categories/tree', [CategoryController::class, 'getTree'])->name('categories.tree');
    Route::get('/categories/{category}/news', [CategoryController::class, 'getCategoryNews'])->name('categories.news');
    
    // Tag API
    Route::get('/tags/autocomplete', [TagController::class, 'autocomplete'])->name('tags.autocomplete');
    Route::get('/tags/{tag}/news', [TagController::class, 'getTagNews'])->name('tags.news');
    
    // Media API
    Route::get('/media/picker', [MediaController::class, 'picker'])->name('media.picker');
    Route::post('/media/upload-ajax', [MediaController::class, 'uploadAjax'])->name('media.upload.ajax');
    
    // User API
    Route::get('/users/roles', [UserController::class, 'getRoles'])->name('users.roles');
    Route::get('/users/{user}/permissions', [UserController::class, 'getPermissions'])->name('users.permissions');
    
    // Advertisement API
    Route::get('/advertisements/positions', [AdvertisementController::class, 'getPositions'])->name('advertisements.positions');
    Route::get('/advertisements/{advertisement}/stats', [AdvertisementController::class, 'getStats'])->name('advertisements.stats');
    
    // RSS API
    Route::get('/rss/{rssFeed}/status', [RssController::class, 'getStatus'])->name('rss.status');
    Route::get('/rss/sources', [RssController::class, 'getSources'])->name('rss.sources');
});


// Sitemap Routes
Route::get('/sitemap.xml', function () {
    return response()->view('sitemap.index')->header('Content-Type', 'text/xml');
})->name('sitemap.index');

Route::get('/sitemap/news.xml', function () {
    $news = \App\Models\News::published()->latest()->take(1000)->get();
    return response()->view('sitemap.news', compact('news'))->header('Content-Type', 'text/xml');
})->name('sitemap.news');

Route::get('/sitemap/categories.xml', function () {
    $categories = \App\Models\Category::active()->get();
    return response()->view('sitemap.categories', compact('categories'))->header('Content-Type', 'text/xml');
})->name('sitemap.categories');

// Robots.txt
Route::get('/robots.txt', function () {
    return response()->view('robots')->header('Content-Type', 'text/plain');
})->name('robots');

// Manifest for PWA
Route::get('/manifest.json', function () {
    return response()->json([
        'name' => config('app.name'),
        'short_name' => config('app.name'),
        'start_url' => '/',
        'display' => 'standalone',
        'theme_color' => '#1a365d',
        'background_color' => '#ffffff',
        'icons' => [
            [
                'src' => '/android-chrome-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png'
            ],
            [
                'src' => '/android-chrome-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png'
            ]
        ]
    ]);
})->name('manifest');

// Fallback route for 404 handling
Route::fallback(function () {
    if (request()->is('admin/*')) {
        return response()->view('admin.errors.404', [], 404);
    }
    return response()->view('errors.404', [], 404);
});
