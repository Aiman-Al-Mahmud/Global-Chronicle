<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Category;
use App\Models\User;
use App\Models\Comment;
use App\Models\NewsView;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Check if user has admin access
        if (!auth()->user()->canPublish()) {
            abort(403, 'Unauthorized access.');
        }

        // Get dashboard statistics
        $stats = $this->getDashboardStats();

        // Get recent activities
        $recentNews = News::with(['author', 'category'])
            ->latest()
            ->take(10)
            ->get();

        $recentComments = Comment::with(['news', 'user'])
            ->latest()
            ->take(10)
            ->get();

        // Get trending articles (last 7 days)
        $trendingArticles = News::trending(7)
            ->with(['author', 'category'])
            ->take(5)
            ->get();

        // Get view analytics for the last 30 days
        $viewsAnalytics = $this->getViewsAnalytics();

        return $this->view('admin.dashboard.dashboard', compact(
            'stats',
            'recentNews',
            'recentComments',
            'trendingArticles',
            'viewsAnalytics'
        ));
    }

    /**
     * Get dashboard statistics.
     */
    private function getDashboardStats()
    {
        return [
            'total_articles' => News::count(),
            'published_articles' => News::published()->count(),
            'draft_articles' => News::draft()->count(),
            'total_categories' => Category::count(),
            'total_users' => User::count(),
            'total_comments' => Comment::count(),
            'pending_comments' => Comment::pending()->count(),
            'today_views' => NewsView::today()->count(),
            'this_month_views' => NewsView::thisMonth()->count(),
        ];
    }

    /**
     * Get views analytics for charts.
     */
    private function getViewsAnalytics()
    {
        $last30Days = collect(range(0, 29))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('Y-m-d'),
                'views' => NewsView::whereDate('viewed_at', $date)->count(),
            ];
        })->reverse()->values();

        return $last30Days;
    }

    /**
     * Display dashboard variant 1.
     */
    public function dashboard1()
    {
        return $this->index();
    }

    /**
     * Display dashboard variant 2.
     */
    public function dashboard2()
    {
        // Get additional stats for dashboard 2
        $categoryStats = Category::withCount(['publishedNews'])
            ->orderBy('published_news_count', 'desc')
            ->take(10)
            ->get();

        $authorStats = User::role('author')
            ->withCount(['publishedNews'])
            ->orderBy('published_news_count', 'desc')
            ->take(10)
            ->get();

        $stats = $this->getDashboardStats();

        return $this->view('admin.dashboard.dashboard-2', compact(
            'stats',
            'categoryStats',
            'authorStats'
        ));
    }

    /**
     * Display dashboard variant 3.
     */
    public function dashboard3()
    {
        // Get performance metrics for dashboard 3
        $performanceMetrics = [
            'avg_reading_time' => News::published()->avg('reading_time') ?? 0,
            'most_viewed_article' => News::published()->orderBy('views_count', 'desc')->first(),
            'most_commented_article' => News::published()
                ->withCount('approvedComments')
                ->orderBy('approved_comments_count', 'desc')
                ->first(),
        ];

        $stats = $this->getDashboardStats();

        return $this->view('admin.dashboard.dashboard-3', compact(
            'stats',
            'performanceMetrics'
        ));
    }
}