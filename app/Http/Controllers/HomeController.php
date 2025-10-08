<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Setting;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the homepage.
     */
    public function index()
    {
        // Get featured news
        $featuredNews = News::published()
            ->featured()
            ->with(['author', 'category', 'featuredImage'])
            ->latest()
            ->take(5)
            ->get();

        // Get latest news
        $latestNews = News::published()
            ->with(['author', 'category', 'featuredImage'])
            ->latest()
            ->take(12)
            ->get();

        // Get trending news (last 7 days)
        $trendingNews = News::published()
            ->trending(7)
            ->with(['author', 'category', 'featuredImage'])
            ->take(8)
            ->get();

        // Get popular categories
        $popularCategories = Category::active()
            ->popular()
            ->take(6)
            ->get();

        // Get popular tags
        $popularTags = Tag::active()
            ->popular()
            ->take(10)
            ->get();

        return $this->view('frontend.index', compact(
            'featuredNews',
            'latestNews',
            'trendingNews',
            'popularCategories',
            'popularTags'
        ));
    }

    /**
     * Display news by category.
     */
    public function category(Category $category, Request $request)
    {
        // Get subcategory IDs if this category has children
        $categoryIds = $category->getDescendantIds();

        $news = News::published()
            ->whereIn('category_id', $categoryIds)
            ->with(['author', 'category', 'featuredImage', 'tags'])
            ->latest();

        // Apply filters
        if ($request->has('tag') && $request->tag) {
            $news->whereHas('tags', function ($query) use ($request) {
                $query->where('slug', $request->tag);
            });
        }

        if ($request->has('author') && $request->author) {
            $news->whereHas('author', function ($query) use ($request) {
                $query->where('username', $request->author);
            });
        }

        $news = $news->paginate(Setting::get('articles_per_page', 12));

        // Get category tags
        $categoryTags = Tag::active()
            ->whereHas('news', function ($query) use ($categoryIds) {
                $query->whereIn('category_id', $categoryIds);
            })
            ->popular()
            ->take(10)
            ->get();

        return $this->view('frontend.category-news', compact(
            'category',
            'news',
            'categoryTags'
        ));
    }

    /**
     * Display news details.
     */
    public function details(News $news)
    {
        // Check if news is published
        if (!$news->is_published) {
            abort(404);
        }

        // Record view
        $news->recordView();

        // Load relationships
        $news->load([
            'author',
            'category',
            'featuredImage',
            'tags',
            'approvedComments.user',
            'approvedComments.children.user'
        ]);

        // Get related articles
        $relatedNews = $news->getRelatedArticles(4);

        // Get article likes/dislikes count
        $likesCount = $news->getLikesCount();
        $dislikesCount = $news->getDislikesCount();

        // Check if current user has liked/disliked
        $userLike = null;
        if (auth()->check()) {
            $userLike = $news->likes()
                ->where('user_id', auth()->id())
                ->first();
        }

        return $this->view('frontend.details', compact(
            'news',
            'relatedNews',
            'likesCount',
            'dislikesCount',
            'userLike'
        ));
    }

    /**
     * Handle news search.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
        ]);

        $query = $request->get('q');
        
        $news = News::published()
            ->search($query)
            ->with(['author', 'category', 'featuredImage'])
            ->latest()
            ->paginate(Setting::get('articles_per_page', 12));

        // Get search suggestions (categories and tags)
        $categories = Category::active()
            ->search($query)
            ->take(5)
            ->get();

        $tags = Tag::active()
            ->search($query)
            ->take(10)
            ->get();

        return $this->view('frontend.search-results', compact(
            'news',
            'query',
            'categories',
            'tags'
        ));
    }

    /**
     * Display news by tag.
     */
    public function tag(Tag $tag)
    {
        $news = $tag->publishedNews()
            ->with(['author', 'category', 'featuredImage'])
            ->latest()
            ->paginate(Setting::get('articles_per_page', 12));

        return $this->view('frontend.tag-news', compact('tag', 'news'));
    }

    /**
     * Display news by author.
     */
    public function author(Request $request, $username)
    {
        $author = \App\Models\User::where('username', $username)->firstOrFail();

        $news = $author->publishedNews()
            ->with(['category', 'featuredImage', 'tags'])
            ->latest()
            ->paginate(Setting::get('articles_per_page', 12));

        return $this->view('frontend.author-news', compact('author', 'news'));
    }

    /**
     * RSS feed.
     */
    public function rss()
    {
        $news = News::published()
            ->with(['author', 'category'])
            ->latest()
            ->take(20)
            ->get();

        $lastBuildDate = $news->first()?->published_at ?? now();

        return response()
            ->view('frontend.rss', compact('news', 'lastBuildDate'))
            ->header('Content-Type', 'application/rss+xml');
    }

    /**
     * Sitemap.
     */
    public function sitemap()
    {
        $news = News::published()
            ->select(['slug', 'updated_at'])
            ->latest()
            ->get();

        $categories = Category::active()
            ->select(['slug', 'updated_at'])
            ->get();

        return response()
            ->view('frontend.sitemap', compact('news', 'categories'))
            ->header('Content-Type', 'application/xml');
    }
}